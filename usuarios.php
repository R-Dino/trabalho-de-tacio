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

// 1. Adicionar Novo Usuário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'adicionar_usuario') {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
    $nivel_acesso = $_POST['nivel_acesso'] ?? 'comum';
    $idade = !empty($_POST['idade']) ? (int)$_POST['idade'] : null;
    $genero = $_POST['genero'] ?? null;
    $sexo = $_POST['sexo'] ?? null;
    $cidade = $_POST['cidade'] ?? null;

    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $_SESSION['msg_erro'] = "O Email já está cadastrado no sistema.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, nivel_acesso, idade, genero, sexo, cidade) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nome, $email, $senha, $nivel_acesso, $idade, $genero, $sexo, $cidade]);
        $_SESSION['msg_sucesso'] = "Usuário cadastrado com sucesso!";
    }
    header("Location: usuarios.php");
    exit;
}

// 2. Editar Usuário (inclui alterar ID)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'editar_usuario') {
    $id_atual = (int)$_POST['id_atual'];
    $novo_id = !empty($_POST['novo_id']) ? (int)$_POST['novo_id'] : $id_atual;
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $idade = !empty($_POST['idade']) ? (int)$_POST['idade'] : null;
    $genero = $_POST['genero'] ?? null;
    $sexo = $_POST['sexo'] ?? null;
    $cidade = $_POST['cidade'] ?? null;

    try {
        if ($novo_id !== $id_atual) {
            $check = $pdo->prepare("SELECT id FROM usuarios WHERE id = ?");
            $check->execute([$novo_id]);
            if ($check->fetch()) {
                throw new Exception("O ID $novo_id já está em uso por outro usuário.");
            }
        }
        
        $stmt = $pdo->prepare("UPDATE usuarios SET id = ?, nome = ?, email = ?, idade = ?, genero = ?, sexo = ?, cidade = ? WHERE id = ?");
        $stmt->execute([$novo_id, $nome, $email, $idade, $genero, $sexo, $cidade, $id_atual]);
        $_SESSION['msg_sucesso'] = "Dados do usuário atualizados com sucesso!";
        
        // Atualizar sessão se editou a si mesmo
        if ($id_atual === $_SESSION['usuario_id']) {
            $_SESSION['usuario_id'] = $novo_id;
            $_SESSION['usuario_nome'] = $nome;
        }
    } catch(Exception $e) {
        $_SESSION['msg_erro'] = "Erro ao editar: " . $e->getMessage();
    }
    header("Location: usuarios.php");
    exit;
}

// Lidar com a exclusão de usuário
if (isset($_GET['excluir_usuario'])) {
    $id_excluir = (int)$_GET['excluir_usuario'];
    if ($id_excluir !== $_SESSION['usuario_id']) { 
        $usr_nome = $pdo->query("SELECT nome FROM usuarios WHERE id = $id_excluir")->fetchColumn();
        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->execute([$id_excluir]);
        
        $pdo->prepare("INSERT INTO logs_atividades (usuario_id, acao, detalhes) VALUES (?, ?, ?)")
            ->execute([$_SESSION['usuario_id'], 'Excluir Usuário', "Excluiu o usuário ID $id_excluir (" . ($usr_nome ?: 'Desconhecido') . ")"]);

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
        $usr_nome = $pdo->query("SELECT nome FROM usuarios WHERE id = $id_banir")->fetchColumn();
        $stmt = $pdo->prepare("UPDATE usuarios SET status = ? WHERE id = ?");
        $stmt->execute([$novo_status, $id_banir]);
        
        $acao = $novo_status === 'banido' ? 'Banir Usuário' : 'Desbanir Usuário';
        $detalhes = $novo_status === 'banido' ? "Baniu o usuário ID $id_banir (" . ($usr_nome ?: 'Desconhecido') . ")" : "Desbaniu o usuário ID $id_banir (" . ($usr_nome ?: 'Desconhecido') . ")";
        
        $pdo->prepare("INSERT INTO logs_atividades (usuario_id, acao, detalhes) VALUES (?, ?, ?)")
            ->execute([$_SESSION['usuario_id'], $acao, $detalhes]);

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
        $usr_nome = $pdo->query("SELECT nome FROM usuarios WHERE id = $id_usuario")->fetchColumn();
        $stmt = $pdo->prepare("UPDATE usuarios SET nivel_acesso = ? WHERE id = ?");
        $stmt->execute([$novo_nivel, $id_usuario]);
        
        $pdo->prepare("INSERT INTO logs_atividades (usuario_id, acao, detalhes) VALUES (?, ?, ?)")
            ->execute([$_SESSION['usuario_id'], 'Mudar Nível', "Alterou nível do ID $id_usuario (" . ($usr_nome ?: 'Desconhecido') . ") para $novo_nivel"]);

        $_SESSION['msg_sucesso'] = "Nível de acesso alterado com sucesso!";
    }
    header("Location: usuarios.php");
    exit;
}

// Lidar com a redefinição de senha
if (isset($_GET['redefinir_senha'])) {
    $id_redefinir = (int)$_GET['redefinir_senha'];
    if ($id_redefinir !== $_SESSION['usuario_id']) {
        $usr_nome = $pdo->query("SELECT nome FROM usuarios WHERE id = $id_redefinir")->fetchColumn();
        $senha_padrao = password_hash('123456', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
        $stmt->execute([$senha_padrao, $id_redefinir]);
        
        $pdo->prepare("INSERT INTO logs_atividades (usuario_id, acao, detalhes) VALUES (?, ?, ?)")
            ->execute([$_SESSION['usuario_id'], 'Redefinir Senha', "Redefiniu a senha do ID $id_redefinir (" . ($usr_nome ?: 'Desconhecido') . ") para '123456'"]);

        $_SESSION['msg_sucesso'] = "Senha redefinida para '123456' com sucesso!";
    }
    header("Location: usuarios.php");
    exit;
}

// Obter usuários (com filtro de pesquisa e status)
$busca = $_GET['busca'] ?? '';
$filtro_status = $_GET['status_filtro'] ?? '';

$sql = "SELECT id, nome, email, nivel_acesso, status, criado_em, idade, genero, sexo, cidade FROM usuarios WHERE 1=1";
$params = [];

if (!empty($busca)) {
    $sql .= " AND (id = ? OR nome LIKE ?)";
    $params[] = $busca;
    $params[] = "%$busca%";
}

if (!empty($filtro_status) && in_array($filtro_status, ['ativo', 'banido'])) {
    $sql .= " AND status = ?";
    $params[] = $filtro_status;
}

$sql .= " ORDER BY id ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$usuarios = $stmt->fetchAll();

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
    <link rel="stylesheet" href="premium.css">
    <style>
        /* BASE & RESET */
        *{ margin:0; padding:0; box-sizing:border-box; font-family: 'Inter', sans-serif; }
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

        /* MODAL */
        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(5px);
            display: flex; justify-content: center; align-items: center;
            z-index: 10000; opacity: 0; pointer-events: none; transition: 0.3s;
        }
        .modal-overlay.active { opacity: 1; pointer-events: auto; }
        .modal-content {
            background: white; padding: 25px; border-radius: 12px;
            width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto;
            transform: translateY(-20px); transition: 0.3s; box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .modal-overlay.active .modal-content { transform: translateY(0); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .modal-close { background: none; border: none; font-size: 20px; cursor: pointer; color: #64748b; }
        .grid-forms { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .form-group { margin-bottom: 15px; }
        .form-group.full { grid-column: span 2; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #475569; font-size: 14px; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; outline: none; background: #f8fafc; }
        .form-group input:focus, .form-group select:focus { border-color: #3b82f6; box-shadow: 0 0 0 2px rgba(59,130,246,0.2); }
        
        .btn-add { background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important; margin-left:15px; }

        /* MODO ESCURO GLOBAL */
        body.dark-mode { background: #0f172a; color: #f1f5f9; }
        body.dark-mode .topbar, body.dark-mode .card, body.dark-mode .table-container, body.dark-mode .modal-content { background: #1e293b; box-shadow: none; color: #f1f5f9; border: 1px solid rgba(255,255,255,0.05); }
        body.dark-mode .topbar h1, body.dark-mode .table-container h2, body.dark-mode .modal-header h2 { color: #f1f5f9; }
        body.dark-mode .card h3, body.dark-mode .form-group label { color: #94a3b8; }
        body.dark-mode input, body.dark-mode select { background: #0f172a !important; border: 1px solid #334155 !important; color: white !important; }
        body.dark-mode table th { background: #0f172a !important; color: #f1f5f9; border-bottom: 1px solid #334155;}
        body.dark-mode table td, body.dark-mode tr { border-bottom: 1px solid #334155 !important; color: #cbd5e1; }
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
            <button class="btn btn-add" onclick="abrirModalAdd()"><i class="fa fa-user-plus"></i> Novo Usuário</button>
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
                <select name="status_filtro" style="padding: 10px; border-radius: 8px; border: 1px solid #cbd5e1; outline: none;">
                    <option value="">Todos os Status</option>
                    <option value="ativo" <?= (isset($_GET['status_filtro']) && $_GET['status_filtro'] == 'ativo') ? 'selected' : '' ?>>Ativos</option>
                    <option value="banido" <?= (isset($_GET['status_filtro']) && $_GET['status_filtro'] == 'banido') ? 'selected' : '' ?>>Banidos</option>
                </select>
                <button type="submit" style="padding: 10px 15px; background: #2563eb; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold;"><i class="fa fa-search"></i> Filtrar</button>
                <?php if(!empty($busca) || !empty($filtro_status)): ?>
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
                        <th>Perfil</th>
                        <th>Email & Contato</th>
                        <th>Nível (Gerenciar)</th>
                        <th>Ações Rápidas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($usuarios as $usr): ?>
                    <tr>
                        <td style="font-weight:bold; font-size:16px;">#<?= $usr['id'] ?></td>
                        <td>
                            <strong style="font-size:15px; display:block;"><?= htmlspecialchars($usr['nome']) ?></strong>
                            <span style="font-size:12px; color:#64748b;">
                                <?= $usr['idade'] ? $usr['idade'].' anos • ' : '' ?>
                                <?= htmlspecialchars($usr['genero'] ?? '') ?>
                                <?= $usr['sexo'] ? ' ('.htmlspecialchars($usr['sexo']).')' : '' ?>
                            </span>
                            <div style="margin-top:5px;">
                                <?php if($usr['status'] === 'banido'): ?>
                                    <span style="padding: 3px 8px; background: #ef4444; color: white; font-size: 11px; border-radius: 12px; font-weight: bold;">Banido</span>
                                <?php else: ?>
                                    <span style="padding: 3px 8px; background: #10b981; color: white; font-size: 11px; border-radius: 12px; font-weight: bold;">Ativo</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td style="color:#64748b;">
                            <i class="fa fa-envelope" style="margin-right:5px;"></i> <?= htmlspecialchars($usr['email']) ?><br>
                            <?php if($usr['cidade']): ?>
                            <i class="fa fa-map-marker-alt" style="margin-right:5px; margin-top:5px;"></i> <?= htmlspecialchars($usr['cidade']) ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="POST" action="usuarios.php" style="display:flex; gap:8px; align-items:center; flex-wrap: wrap;">
                                <input type="hidden" name="acao" value="mudar_nivel">
                                <input type="hidden" name="usuario_id" value="<?= $usr['id'] ?>">
                                <select name="nivel_acesso" style="padding:6px; border-radius:6px; border:1px solid #cbd5e1; outline:none;" <?= $usr['id'] === $_SESSION['usuario_id'] ? 'disabled' : '' ?>>
                                    <option value="comum" <?= $usr['nivel_acesso'] == 'comum' ? 'selected' : '' ?>>Usuário Comum</option>
                                    <option value="admin" <?= $usr['nivel_acesso'] == 'admin' ? 'selected' : '' ?>>Administrador</option>
                                </select>
                                <?php if ($usr['id'] !== $_SESSION['usuario_id']): ?>
                                    <button type="submit" style="padding:6px 12px; background:#2563eb; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:bold; font-size:12px;">Salvar Cargo</button>
                                <?php endif; ?>
                            </form>
                        </td>
                        <td style="display: flex; gap: 8px; flex-wrap:wrap;">
                            <button onclick='abrirModalEdit(<?= json_encode($usr) ?>)' style="color:#0ea5e9; font-weight:bold; font-size: 13px; display:inline-block; padding: 6px 12px; background: #e0f2fe; border-radius: 6px;"><i class="fa fa-pen"></i> Editar</button>
                            <?php if ($usr['id'] !== $_SESSION['usuario_id']): ?>
                                <?php if($usr['status'] === 'banido'): ?>
                                    <a href="usuarios.php?banir_usuario=<?= $usr['id'] ?>&status=banido" style="color:#10b981; text-decoration:none; font-weight:bold; font-size: 13px; display:inline-block; padding: 6px 12px; background: #d1fae5; border-radius: 6px;"><i class="fa fa-unlock"></i> Desbanir</a>
                                <?php else: ?>
                                    <a href="usuarios.php?banir_usuario=<?= $usr['id'] ?>&status=ativo" onclick="return confirm('Deseja banir <?= htmlspecialchars($usr['nome']) ?>?');" style="color:#f59e0b; text-decoration:none; font-weight:bold; font-size: 13px; display:inline-block; padding: 6px 12px; background: #fef3c7; border-radius: 6px;"><i class="fa fa-ban"></i> Banir</a>
                                <?php endif; ?>
                                <a href="usuarios.php?excluir_usuario=<?= $usr['id'] ?>" onclick="return confirm('Deseja realmente excluir <?= htmlspecialchars($usr['nome']) ?> permanentemente?');" style="color:#ef4444; text-decoration:none; font-weight:bold; font-size: 13px; display:inline-block; padding: 6px 12px; background: #fef2f2; border-radius: 6px;"><i class="fa fa-trash"></i> Excluir</a>
                            <?php else: ?>
                                <span style="color:#94a3b8; font-size:13px; font-weight: bold;"><i class="fa fa-user-check"></i> Você</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL ADICIONAR USUÁRIO -->
    <div class="modal-overlay" id="modalAdd">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Criar Nova Conta</h2>
                <button class="modal-close" onclick="fecharModal('modalAdd')"><i class="fa fa-times"></i></button>
            </div>
            <form method="POST" action="usuarios.php">
                <input type="hidden" name="acao" value="adicionar_usuario">
                <div class="grid-forms">
                    <div class="form-group full">
                        <label>Nome Completo</label>
                        <input type="text" name="nome" required placeholder="João da Silva">
                    </div>
                    <div class="form-group full">
                        <label>Email (Gmail ou outros)</label>
                        <input type="email" name="email" required placeholder="exemplo@gmail.com">
                    </div>
                    <div class="form-group">
                        <label>Senha de Acesso</label>
                        <input type="password" name="senha" required placeholder="******">
                    </div>
                    <div class="form-group">
                        <label>Nível de Acesso</label>
                        <select name="nivel_acesso" required>
                            <option value="comum">Usuário Comum</option>
                            <option value="admin">Administrador</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Idade</label>
                        <input type="number" name="idade" placeholder="Ex: 25" min="1" max="120">
                    </div>
                    <div class="form-group">
                        <label>Gênero</label>
                        <select name="genero">
                            <option value="">Não informar</option>
                            <option value="Masculino">Masculino</option>
                            <option value="Feminino">Feminino</option>
                            <option value="Não-Binário">Não-Binário</option>
                            <option value="Outro">Outro</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Sexo Biológico</label>
                        <select name="sexo">
                            <option value="">Não informar</option>
                            <option value="M">Masculino (M)</option>
                            <option value="F">Feminino (F)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Cidade</label>
                        <input type="text" name="cidade" placeholder="Ex: São Paulo">
                    </div>
                </div>
                <div style="margin-top:20px; display:flex; justify-content:flex-end;">
                    <button type="submit" style="padding: 12px 25px;"><i class="fa fa-check"></i> Cadastrar Usuário</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDITAR USUÁRIO -->
    <div class="modal-overlay" id="modalEdit">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Editar Usuário e ID</h2>
                <button class="modal-close" onclick="fecharModal('modalEdit')"><i class="fa fa-times"></i></button>
            </div>
            <form method="POST" action="usuarios.php">
                <input type="hidden" name="acao" value="editar_usuario">
                <input type="hidden" name="id_atual" id="edit_id_atual">
                <div class="grid-forms">
                    <div class="form-group">
                        <label>ID do Usuário (CUIDADO!)</label>
                        <input type="number" name="novo_id" id="edit_novo_id" required>
                    </div>
                    <div class="form-group">
                        <label>Nome Completo</label>
                        <input type="text" name="nome" id="edit_nome" required>
                    </div>
                    <div class="form-group full">
                        <label>Email</label>
                        <input type="email" name="email" id="edit_email" required>
                    </div>
                    <div class="form-group">
                        <label>Idade</label>
                        <input type="number" name="idade" id="edit_idade" min="1" max="120">
                    </div>
                    <div class="form-group">
                        <label>Gênero</label>
                        <select name="genero" id="edit_genero">
                            <option value="">Não informar</option>
                            <option value="Masculino">Masculino</option>
                            <option value="Feminino">Feminino</option>
                            <option value="Não-Binário">Não-Binário</option>
                            <option value="Outro">Outro</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Sexo Biológico</label>
                        <select name="sexo" id="edit_sexo">
                            <option value="">Não informar</option>
                            <option value="M">Masculino (M)</option>
                            <option value="F">Feminino (F)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Cidade</label>
                        <input type="text" name="cidade" id="edit_cidade">
                    </div>
                </div>
                <div style="background:#fffbeb; color:#d97706; padding:10px; border-radius:8px; margin-top:10px; font-size:12px;">
                    <i class="fa fa-warning"></i> Alterar o ID mudará o vínculo no banco de dados. Os logs continuarão atrelados ao novo ID graças à configuração em cascata.
                </div>
                <div style="margin-top:20px; display:flex; justify-content:flex-end;">
                    <button type="submit" style="padding: 12px 25px;"><i class="fa fa-save"></i> Salvar Alterações</button>
                </div>
            </form>
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

        function abrirModalAdd() {
            document.getElementById('modalAdd').classList.add('active');
        }

        function abrirModalEdit(user) {
            document.getElementById('edit_id_atual').value = user.id;
            document.getElementById('edit_novo_id').value = user.id;
            document.getElementById('edit_nome').value = user.nome;
            document.getElementById('edit_email').value = user.email;
            document.getElementById('edit_idade').value = user.idade || '';
            document.getElementById('edit_genero').value = user.genero || '';
            document.getElementById('edit_sexo').value = user.sexo || '';
            document.getElementById('edit_cidade').value = user.cidade || '';
            
            document.getElementById('modalEdit').classList.add('active');
        }

        function fecharModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }
    </script>
</body>
</html>
