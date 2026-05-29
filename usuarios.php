<?php
session_start();
require 'db.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

// Proteção para Admin
if (!isset($_SESSION['nivel_acesso']) || $_SESSION['nivel_acesso'] !== 'admin') {
    die("Acesso negado. Apenas administradores podem gerenciar usuários.");
}

// Lidar com a exclusão de usuário
if (isset($_GET['excluir_usuario'])) {
    $id_excluir = (int)$_GET['excluir_usuario'];
    if ($id_excluir !== $_SESSION['usuario_id']) { 
        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->execute([$id_excluir]);
        $_SESSION['msg_sucesso'] = "Usuário excluído com sucesso!";
    } else {
        $_SESSION['msg_erro'] = "Ação negada.";
    }
    header("Location: usuarios.php");
    exit;
}

// Lidar com banimento/desbanimento de usuário
if (isset($_GET['banir_usuario'])) {
    $id_banir = (int)$_GET['banir_usuario'];
    $status_atual = $_GET['status'] ?? 'ativo';
    $novo_status = $status_atual === 'banido' ? 'ativo' : 'banido';
    
    if ($id_banir !== $_SESSION['usuario_id']) { 
        $stmt = $pdo->prepare("UPDATE usuarios SET status = ? WHERE id = ?");
        $stmt->execute([$novo_status, $id_banir]);
        $_SESSION['msg_sucesso'] = "Status de banimento atualizado!";
    }
    header("Location: usuarios.php");
    exit;
}

// Lidar com a mudança de nível
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'mudar_nivel') {
    $id_usuario = (int)$_POST['usuario_id'];
    $novo_nivel = $_POST['nivel_acesso']; 
    
    if ($id_usuario !== $_SESSION['usuario_id'] && in_array($novo_nivel, ['admin', 'comum'])) {
        $stmt = $pdo->prepare("UPDATE usuarios SET nivel_acesso = ? WHERE id = ?");
        $stmt->execute([$novo_nivel, $id_usuario]);
        $_SESSION['msg_sucesso'] = "Nível de acesso alterado com sucesso!";
    }
    header("Location: usuarios.php");
    exit;
}

// Obter usuários (com filtro de pesquisa)
$busca = $_GET['busca'] ?? '';
if (!empty($busca)) {
    $stmt = $pdo->prepare("SELECT id, nome, email, nivel_acesso, status, criado_em FROM usuarios WHERE id = ? OR nome LIKE ? ORDER BY id ASC");
    $stmt->execute([$busca, "%$busca%"]);
    $usuarios = $stmt->fetchAll();
} else {
    $usuarios = $pdo->query("SELECT id, nome, email, nivel_acesso, status, criado_em FROM usuarios ORDER BY id ASC")->fetchAll();
}

// Estatísticas
$totalUsuarios = count($usuarios);
$totalAdmins = array_reduce($usuarios, function($carry, $usr) { return $carry + ($usr['nivel_acesso'] === 'admin' ? 1 : 0); }, 0);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Usuários - ALMOX</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* BASE & RESET */
        *{ margin:0; padding:0; box-sizing:border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body{ background:#f1f5f9; color: #1e293b; transition: background 0.3s; }

        /* MENU LATERAL */
        .menu-toggle{ position:fixed; top:15px; left:15px; z-index:1000; border:none; background:#2563eb; color:white; width:45px; height:45px; border-radius:8px; cursor:pointer; font-size:20px; }
        .sidebar{ width:250px; height:100vh; background:#0f172a; color:white; padding:20px; position:fixed; left:-250px; top:0; transition:0.4s; z-index:999; }
        .sidebar.active{ left:0; }
        .logo{ text-align:center; margin-bottom:40px; }
        .logo h2{ color:#38bdf8; }
        .menu{ list-style:none; }
        .menu li{ margin:15px 0; }
        .menu a{ color:white; text-decoration:none; display:flex; align-items:center; gap:10px; padding:12px; border-radius:8px; transition:0.3s; }
        .menu a:hover{ background:#1e293b; }

        /* CONTEÚDO PRINCIPAL */
        .main{ width:100%; padding:20px; transition: 0.4s; }
        .topbar{ background:white; padding:15px 20px; border-radius:10px; display:flex; justify-content:space-between; align-items:center; box-shadow:0 2px 5px rgba(0,0,0,0.1); margin-top:60px; }
        
        /* CARDS */
        .cards{ display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:20px; margin-top:25px; }
        .card{ background:white; padding:20px; border-radius:12px; box-shadow:0 2px 5px rgba(0,0,0,0.1); }
        .card h3{ color:#64748b; }
        .card p{ margin-top:10px; font-size:28px; font-weight:bold; color: #2563eb;}

        /* TABELA */
        .table-container{ margin-top:30px; background:white; padding:20px; border-radius:12px; box-shadow:0 2px 5px rgba(0,0,0,0.1); overflow-x:auto;}
        table{ width:100%; border-collapse:collapse; margin-top:20px; min-width:600px;}
        table th, table td{ padding:12px; border-bottom:1px solid #f1f5f9; text-align:left; }
        table th{ background:#e2e8f0; border-radius: 4px; }
    
        /* MODO ESCURO GLOBAL */
        body.dark-mode { background: #0f172a; color: #f1f5f9; }
        body.dark-mode .topbar, body.dark-mode .card, body.dark-mode .table-container, body.dark-mode .form-container, body.dark-mode .report-card, body.dark-mode .chart-box, body.dark-mode .activity-box { background: #1e293b; box-shadow: none; color: #f1f5f9; }
        body.dark-mode .topbar h1, body.dark-mode .form-container h2, body.dark-mode .table-container h2 { color: #f1f5f9; }
        body.dark-mode .card h3 { color: #94a3b8; }
        body.dark-mode input, body.dark-mode select { background: #334155 !important; border: 1px solid #475569 !important; color: white !important; }
        body.dark-mode table th { background: #0f172a !important; color: #f1f5f9; border-bottom: 1px solid #334155;}
        body.dark-mode table td, body.dark-mode tr { border-bottom: 1px solid #334155 !important; color: #cbd5e1; }
        body.dark-mode .activity-item { border-bottom: 1px solid #334155; }
        body.dark-mode .activity-item p { color: #94a3b8; }
        
        /* Ajustes extras para Tela de Login */
        body.dark-mode .auth-card { background: #1e293b; box-shadow: none; }
        body.dark-mode header { background: #0f172a; border-bottom: 1px solid #334155; }
        body.dark-mode .tabs { background: #1e293b; border-bottom: 1px solid #334155; }
        body.dark-mode .tab-btn { color: #94a3b8; }
        body.dark-mode .tab-btn.active { background: #1e293b; color: #38bdf8; border-bottom: 3px solid #38bdf8; }
        body.dark-mode .field label { color: #cbd5e1; }
        body.dark-mode .form-utils { color: #94a3b8; }
        body.dark-mode .alert-error { background: #450a0a; border-color: #7f1d1d; color: #fca5a5; }
        body.dark-mode .alert-success { background: #052e16; border-color: #14532d; color: #86efac; }
</style>
</head>
<body>
<style>
.toast-container { position: fixed; top: 20px; right: 20px; z-index: 9999; }
.toast { background: #333; color: white; padding: 15px 20px; border-radius: 8px; margin-bottom: 10px; font-weight: bold; box-shadow: 0 4px 6px rgba(0,0,0,0.1); display: flex; align-items: center; gap: 10px; animation: slideIn 0.3s, fadeOut 0.5s 2.5s forwards; }
.toast.sucesso { background: #10b981; }
.toast.erro { background: #ef4444; }
@keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
@keyframes fadeOut { from { opacity: 1; } to { opacity: 0; display: none; } }
</style>
<div class="toast-container">
    <?php if (isset($_SESSION['msg_sucesso'])): ?>
        <div class="toast sucesso"><i class="fa fa-check-circle"></i> <?= htmlspecialchars($_SESSION['msg_sucesso']) ?></div>
        <?php unset($_SESSION['msg_sucesso']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['msg_erro'])): ?>
        <div class="toast erro"><i class="fa fa-exclamation-circle"></i> <?= htmlspecialchars($_SESSION['msg_erro']) ?></div>
        <?php unset($_SESSION['msg_erro']); ?>
    <?php endif; ?>
</div>

    <button class="menu-toggle" onclick="toggleMenu()">
        <i class="fa fa-bars"></i>
    </button>

    <div class="sidebar" id="sidebar">
        <div class="logo"><h2>ALMOX</h2></div>
        <ul class="menu">
            <li><a href="telainicial.php"><i class="fa fa-house"></i> Início</a></li>
            <li><a href="dashboard.php"><i class="fa fa-chart-line"></i> Dashboard</a></li>
            <li><a href="produtos.php"><i class="fa fa-box"></i> Produtos</a></li>
            <li><a href="estoque.php"><i class="fa fa-warehouse"></i> Estoque</a></li>
            <li><a href="fornecedores.php"><i class="fa fa-truck"></i> Fornecedores</a></li>
            <?php if (isset($_SESSION['nivel_acesso']) && $_SESSION['nivel_acesso'] === 'admin'): ?>
            <li><a href="usuarios.php"><i class="fa fa-users"></i> Usuários</a></li>
            <li><a href="relatorios.php"><i class="fa fa-file"></i> Relatórios</a></li>
            <li><a href="configuracoes.php"><i class="fa fa-gear"></i> Configurações</a></li>
            <?php endif; ?>
        </ul>
    </div>

    <div class="main">
        <div class="topbar">
            <h1>Gestão de Usuários e Privilégios</h1>
        </div>

        <div class="cards">
            <div class="card">
                <h3>Total de Usuários</h3>
                <p><?= $totalUsuarios ?></p>
            </div>
            <div class="card">
                <h3>Administradores</h3>
                <p><?= $totalAdmins ?></p>
            </div>
        </div>

        <div class="table-container">
            <h2>Lista de Usuários</h2>
            <form method="GET" action="usuarios.php" style="margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
                <input type="text" name="busca" placeholder="Pesquisar por ID ou Nome..." value="<?= htmlspecialchars($busca) ?>" style="padding: 10px; border-radius: 8px; border: 1px solid #cbd5e1; width: 100%; max-width: 350px; outline: none;">
                <button type="submit" style="padding: 10px 15px; background: #2563eb; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold;"><i class="fa fa-search"></i> Pesquisar</button>
                <?php if(!empty($busca)): ?>
                    <a href="usuarios.php" style="padding: 10px 15px; background: #94a3b8; color: white; text-decoration: none; border-radius: 8px; font-weight: bold; display: flex; align-items: center;">Limpar</a>
                <?php endif; ?>
            </form>
            
            <div style="margin-bottom: 15px; background: #e0f2fe; color: #0284c7; padding: 12px; border-radius: 8px; font-size: 0.9rem; font-weight: 500;">
                <i class="fa fa-info-circle"></i> <strong>Categorias de Liberdade:</strong> <br>
                - <strong>Usuário Comum:</strong> Pode visualizar produtos, estoque e fornecedores. <br>
                - <strong>Administrador:</strong> Acesso total. Pode gerenciar produtos, aprovar estoque, banir e promover usuários.
            </div>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome / Status</th>
                        <th>Email</th>
                        <th>Nível de Acesso (Gerenciamento)</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($usuarios as $usr): ?>
                    <tr>
                        <td><?= $usr['id'] ?></td>
                        <td>
                            <strong><?= htmlspecialchars($usr['nome']) ?></strong>
                            <?php if($usr['status'] === 'banido'): ?>
                                <span style="margin-left: 8px; padding: 3px 8px; background: #ef4444; color: white; font-size: 11px; border-radius: 12px; font-weight: bold;">Banido</span>
                            <?php else: ?>
                                <span style="margin-left: 8px; padding: 3px 8px; background: #10b981; color: white; font-size: 11px; border-radius: 12px; font-weight: bold;">Ativo</span>
                            <?php endif; ?>
                        </td>
                        <td style="color:#64748b;"><?= htmlspecialchars($usr['email']) ?></td>
                        <td>
                            <form method="POST" action="usuarios.php" style="display:flex; gap:8px; align-items:center; flex-wrap: wrap;">
                                <input type="hidden" name="acao" value="mudar_nivel">
                                <input type="hidden" name="usuario_id" value="<?= $usr['id'] ?>">
                                <select name="nivel_acesso" style="padding:6px; border-radius:6px; border:1px solid #cbd5e1; outline:none;" <?= $usr['id'] === $_SESSION['usuario_id'] ? 'disabled' : '' ?>>
                                    <option value="comum" <?= $usr['nivel_acesso'] == 'comum' ? 'selected' : '' ?>>Usuário Comum (Leitura)</option>
                                    <option value="admin" <?= $usr['nivel_acesso'] == 'admin' ? 'selected' : '' ?>>Administrador (Total)</option>
                                </select>
                                <?php if ($usr['id'] !== $_SESSION['usuario_id']): ?>
                                    <button type="submit" style="padding:6px 12px; background:#2563eb; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:bold; font-size:12px;">Atualizar Cargo</button>
                                <?php endif; ?>
                            </form>
                        </td>
                        <td style="display: flex; gap: 8px;">
                            <?php if ($usr['id'] !== $_SESSION['usuario_id']): ?>
                                <?php if($usr['status'] === 'banido'): ?>
                                    <a href="usuarios.php?banir_usuario=<?= $usr['id'] ?>&status=banido" style="color:#10b981; text-decoration:none; font-weight:bold; font-size: 13px; display:inline-block; padding: 6px 12px; background: #d1fae5; border-radius: 6px;"><i class="fa fa-unlock"></i> Desbanir</a>
                                <?php else: ?>
                                    <a href="usuarios.php?banir_usuario=<?= $usr['id'] ?>&status=ativo" onclick="return confirm('Deseja realmente banir o usuário <?= htmlspecialchars($usr['nome']) ?>? Ele perderá o acesso ao sistema.');" style="color:#f59e0b; text-decoration:none; font-weight:bold; font-size: 13px; display:inline-block; padding: 6px 12px; background: #fef3c7; border-radius: 6px;"><i class="fa fa-ban"></i> Banir</a>
                                <?php endif; ?>
                                <a href="usuarios.php?excluir_usuario=<?= $usr['id'] ?>" onclick="return confirm('ATENÇÃO: Deseja realmente excluir o usuário <?= htmlspecialchars($usr['nome']) ?> de forma permanente?');" style="color:#ef4444; text-decoration:none; font-weight:bold; font-size: 13px; display:inline-block; padding: 6px 12px; background: #fef2f2; border-radius: 6px;"><i class="fa fa-user-xmark"></i> Remover</a>
                            <?php else: ?>
                                <span style="color:#94a3b8; font-size:13px; font-weight: bold;"><i class="fa fa-user-check"></i> Você (Logado)</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function toggleMenu(){
            document.getElementById("sidebar").classList.toggle("active");
        }
    
        window.addEventListener('DOMContentLoaded', () => {
            if (localStorage.getItem("darkMode") === "true") {
                document.body.classList.add("dark-mode");
            }
        });
</script>
</body>
</html>
