<?php
session_start();
require 'db.php';
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}
if (isset($_GET['excluir'])) {
    if (!isset($_SESSION['nivel_acesso']) || $_SESSION['nivel_acesso'] !== 'admin') {
        die("Acesso negado. Apenas administradores podem excluir.");
    }
    $id = (int)$_GET['excluir'];
    $stmt = $pdo->prepare("SELECT produto_id, quantidade, tipo FROM movimentacoes WHERE id = ?");
    $stmt->execute([$id]);
    $mov = $stmt->fetch();
    if ($mov) {
        $qtd = $mov['quantidade'];
        if ($mov['tipo'] == 'Entrada') {
            $pdo->query("UPDATE produtos SET quantidade = quantidade - $qtd WHERE id = " . $mov['produto_id']);
        } else {
            $pdo->query("UPDATE produtos SET quantidade = quantidade + $qtd WHERE id = " . $mov['produto_id']);
        }
        $pdo->prepare("DELETE FROM movimentacoes WHERE id = ?")->execute([$id]);
        $pdo->query("UPDATE produtos SET status = CASE WHEN quantidade = 0 THEN 'Zerado' WHEN quantidade <= 20 THEN 'Baixo' ELSE 'Disponível' END WHERE id = " . $mov['produto_id']);
    }
    $_SESSION['msg_sucesso'] = "Operação realizada com sucesso!";
        header("Location: estoque.php");
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'movimentar') {
    $produto_id = (int)$_POST['produto_id'];
    $quantidade = (int)$_POST['quantidade'];
    $tipo = $_POST['tipo'];
    if ($produto_id > 0 && $quantidade > 0 && in_array($tipo, ['Entrada', 'Saída'])) {
        if ($tipo == 'Saída') {
            $check = $pdo->query("SELECT quantidade FROM produtos WHERE id = $produto_id")->fetch();
            if ($check && $check['quantidade'] < $quantidade) {
                $_SESSION['msg_erro'] = "Erro: Estoque insuficiente! Existem apenas {$check['quantidade']} unidades no estoque.";
                $_SESSION['msg_sucesso'] = "Operação realizada com sucesso!";
        header("Location: estoque.php");
                exit;
            }
        }
        $stmt = $pdo->prepare("INSERT INTO movimentacoes (produto_id, quantidade, tipo) VALUES (?, ?, ?)");
        $stmt->execute([$produto_id, $quantidade, $tipo]);
        if ($tipo == 'Entrada') {
            $pdo->query("UPDATE produtos SET quantidade = quantidade + $quantidade WHERE id = $produto_id");
        } else {
            $pdo->query("UPDATE produtos SET quantidade = quantidade - $quantidade WHERE id = $produto_id");
        }
        $pdo->query("UPDATE produtos SET status = CASE WHEN quantidade <= 0 THEN 'Zerado' WHEN quantidade <= 20 THEN 'Baixo' ELSE 'Disponível' END WHERE id = $produto_id");
        $_SESSION['msg_sucesso'] = "Operação realizada com sucesso!";
        header("Location: estoque.php");
        exit;
    }
}
$produtos = $pdo->query("SELECT id, nome, quantidade FROM produtos ORDER BY nome ASC")->fetchAll();
$totalMovimentos = $pdo->query("SELECT COUNT(*) FROM movimentacoes")->fetchColumn() ?: 0;
$totalEntradas = $pdo->query("SELECT COUNT(*) FROM movimentacoes WHERE tipo = 'Entrada'")->fetchColumn() ?: 0;
$totalSaidas = $pdo->query("SELECT COUNT(*) FROM movimentacoes WHERE tipo = 'Saída'")->fetchColumn() ?: 0;
$termo_pesquisa = $_GET['pesquisa'] ?? '';
if (!empty($termo_pesquisa)) {
    $stmt = $pdo->prepare("
        SELECT m.*, p.nome as produto_nome 
        FROM movimentacoes m 
        JOIN produtos p ON m.produto_id = p.id 
        WHERE p.nome LIKE ? 
        ORDER BY m.id DESC
    ");
    $stmt->execute(["%$termo_pesquisa%"]);
    $movimentacoes = $stmt->fetchAll();
} else {
    $movimentacoes = $pdo->query("
        SELECT m.*, p.nome as produto_nome 
        FROM movimentacoes m 
        JOIN produtos p ON m.produto_id = p.id 
        ORDER BY m.id DESC
    ")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Controle de Estoque</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*{ margin:0; padding:0; box-sizing:border-box; font-family:Arial, Helvetica, sans-serif; }
body{ background:#f1f5f9; }
.menu-toggle{ position:fixed; top:15px; left:15px; z-index:1000; border:none; background:#2563eb; color:white; width:45px; height:45px; border-radius:8px; cursor:pointer; font-size:20px; }
.sidebar{ width:250px; height:100vh; background:#0f172a; color:white; padding:20px; position:fixed; left:-250px; top:0; transition:0.4s; z-index:999; }
.sidebar.active{ left:0; }
.logo{ text-align:center; margin-bottom:40px; }
.logo h2{ color:#38bdf8; }
.menu{ list-style:none; }
.menu li{ margin:15px 0; }
.menu a{ color:white; text-decoration:none; display:flex; align-items:center; gap:10px; padding:12px; border-radius:8px; transition:0.3s; }
.menu a:hover{ background:#1e293b; }
.main{ width:100%; padding:20px; }
.topbar{ background:white; padding:15px 20px; border-radius:10px; display:flex; justify-content:space-between; align-items:center; box-shadow:0 2px 5px rgba(0,0,0,0.1); margin-top:60px; }
.topbar form { display: flex; gap: 10px; }
.topbar input{ width:300px; padding:10px; border:1px solid #ccc; border-radius:8px; }
.cards{ display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:20px; margin-top:25px; }
.card{ background:white; padding:20px; border-radius:12px; box-shadow:0 2px 5px rgba(0,0,0,0.1); }
.card h3{ color:#64748b; }
.card p{ margin-top:10px; font-size:28px; font-weight:bold; }
.form-container{ margin-top:30px; background:white; padding:20px; border-radius:12px; box-shadow:0 2px 5px rgba(0,0,0,0.1); }
.form-container h2{ margin-bottom:20px; }
.form-grid{ display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:15px; }
.form-grid input, .form-grid select{ padding:12px; border:1px solid #ccc; border-radius:8px; }
button.btn-primary{ margin-top:20px; padding:12px 20px; border:none; border-radius:8px; background:#2563eb; color:white; cursor:pointer; font-weight:bold; transition:0.3s; }
button.btn-primary:hover{ background:#1d4ed8; }
.table-container{ margin-top:30px; background:white; padding:20px; border-radius:12px; box-shadow:0 2px 5px rgba(0,0,0,0.1); overflow-x: auto;}
table{ width:100%; border-collapse:collapse; margin-top:20px; }
table th, table td{ padding:12px; border-bottom:1px solid #ddd; text-align:left; }
table th{ background:#e2e8f0; }
.status{ padding:5px 10px; border-radius:20px; color:white; font-size:12px; }
.entrada{ background:green; }
.saida{ background:red; }
.acoes a.btn-delete{ padding:8px 12px; background:red; color:white; text-decoration:none; border-radius:8px; font-size:14px; }
.acoes a.btn-delete:hover{ background:darkred; }
        body.dark-mode { background: #0f172a; color: #f1f5f9; }
        body.dark-mode .topbar, body.dark-mode .card, body.dark-mode .table-container, body.dark-mode .form-container, body.dark-mode .report-card, body.dark-mode .chart-box, body.dark-mode .activity-box { background: #1e293b; box-shadow: none; color: #f1f5f9; }
        body.dark-mode .topbar h1, body.dark-mode .form-container h2, body.dark-mode .table-container h2 { color: #f1f5f9; }
        body.dark-mode .card h3 { color: #94a3b8; }
        body.dark-mode input, body.dark-mode select { background: #334155 !important; border: 1px solid #475569 !important; color: white !important; }
        body.dark-mode table th { background: #0f172a !important; color: #f1f5f9; border-bottom: 1px solid #334155;}
        body.dark-mode table td, body.dark-mode tr { border-bottom: 1px solid #334155 !important; color: #cbd5e1; }
        body.dark-mode .activity-item { border-bottom: 1px solid #334155; }
        body.dark-mode .activity-item p { color: #94a3b8; }
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
    <link rel="stylesheet" href="premium.css">
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
<button class="menu-toggle" onclick="toggleMenu()"><i class="fa fa-bars"></i></button>
<div class="sidebar" id="sidebar">
    <div class="logo"><h2>ALMOX</h2></div>
    <ul class="menu">
        <li><a href="telainicial.php"><i class="fa fa-house"></i> Início</a></li>
        <li><a href="dashboard.php"><i class="fa fa-chart-line"></i> Dashboard</a></li>
        <li><a href="ia.php" style="color: #38bdf8; font-weight: bold;"><i class="fa fa-robot"></i> Assistente IA</a></li>
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
        <h1>Controle de Estoque</h1>
        <form method="GET" action="estoque.php">
            <input type="text" name="pesquisa" placeholder="Pesquisar produto na movimentação..." value="<?= htmlspecialchars($termo_pesquisa) ?>">
            <button type="submit" class="btn-primary" style="margin-top:0;">Buscar</button>
        </form>
    </div>
    <div class="cards">
        <div class="card">
            <h3>Total de Movimentações</h3>
            <p><?= $totalMovimentos ?></p>
        </div>
        <div class="card">
            <h3>Entradas</h3>
            <p><?= $totalEntradas ?></p>
        </div>
        <div class="card">
            <h3>Saídas</h3>
            <p><?= $totalSaidas ?></p>
        </div>
    </div>
    <div class="form-container">
        <h2>Registrar Movimentação</h2>
        <form method="POST" action="estoque.php">
            <input type="hidden" name="acao" value="movimentar">
            <div class="form-grid">
                <select name="produto_id" required>
                    <option value="">Selecione o Produto</option>
                    <?php foreach($produtos as $prod): ?>
                        <option value="<?= $prod['id'] ?>"><?= htmlspecialchars($prod['nome']) ?> (Estoque: <?= $prod['quantidade'] ?>)</option>
                    <?php endforeach; ?>
                </select>
                <input type="number" name="quantidade" placeholder="Quantidade" required>
                <select name="tipo" required>
                    <option value="">Tipo</option>
                    <option value="Entrada">Entrada</option>
                    <option value="Saída">Saída</option>
                </select>
            </div>
            <button type="submit" class="btn-primary">Registrar</button>
        </form>
    </div>
    <div class="table-container">
        <h2>Movimentações</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Produto</th>
                    <th>Quantidade</th>
                    <th>Tipo</th>
                    <th>Data</th>
                    <?php if (isset($_SESSION['nivel_acesso']) && $_SESSION['nivel_acesso'] === 'admin'): ?>
                    <th>Ações</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach($movimentacoes as $mov): ?>
                    <?php $classe = ($mov['tipo'] == 'Entrada') ? 'entrada' : 'saida'; ?>
                <tr>
                    <td><?= $mov['id'] ?></td>
                    <td><?= htmlspecialchars($mov['produto_nome']) ?></td>
                    <td><?= $mov['quantidade'] ?></td>
                    <td><span class="status <?= $classe ?>"><?= htmlspecialchars($mov['tipo']) ?></span></td>
                    <td><?= date('d/m/Y H:i', strtotime($mov['data_movimentacao'])) ?></td>
                    <?php if (isset($_SESSION['nivel_acesso']) && $_SESSION['nivel_acesso'] === 'admin'): ?>
                    <td class="acoes">
                        <a href="estoque.php?excluir=<?= $mov['id'] ?>" class="btn-delete" onclick="return confirm('Tem certeza que deseja excluir esta movimentação? Isso reverterá o estoque.');">Excluir</a>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($movimentacoes)): ?>
                <tr><td colspan="6" style="text-align: center;">Nenhuma movimentação encontrada.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<script>
function toggleMenu(){
    let sidebar = document.getElementById("sidebar");
    sidebar.classList.toggle("active");
}
        window.addEventListener('DOMContentLoaded', () => {
            if (localStorage.getItem("darkMode") === "true") {
                document.body.classList.add("dark-mode");
            }
        });
</script>
</body>
</html>
