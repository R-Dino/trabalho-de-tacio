<?php
session_start();
require 'db.php';
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}
$stats = $pdo->query("
    SELECT 
        COUNT(*) as total_produtos,
        COALESCE(SUM(quantidade), 0) as total_estoque,
        SUM(CASE WHEN status IN ('Baixo', 'Zerado') THEN 1 ELSE 0 END) as baixo_estoque
    FROM produtos
")->fetch();
$totalProdutos = $stats['total_produtos'] ?? 0;
$totalEstoque = $stats['total_estoque'] ?? 0;
$baixoEstoque = $stats['baixo_estoque'] ?? 0;
$totalFornecedores = $pdo->query("SELECT COUNT(*) FROM fornecedores")->fetchColumn() ?: 0;
$movimentacoes = $pdo->query("
    SELECT m.*, p.nome as produto_nome 
    FROM movimentacoes m 
    JOIN produtos p ON m.produto_id = p.id 
    ORDER BY m.data_movimentacao DESC LIMIT 4
")->fetchAll();
$ultimosProdutos = $pdo->query("
    SELECT p.*, c.nome as categoria_nome 
    FROM produtos p 
    LEFT JOIN categorias c ON p.categoria_id = c.id 
    ORDER BY p.id DESC LIMIT 4
")->fetchAll();
$categoriasEstoque = $pdo->query("
    SELECT c.nome, SUM(p.quantidade) as total 
    FROM produtos p 
    JOIN categorias c ON p.categoria_id = c.id 
    GROUP BY c.id
")->fetchAll();
$catNomes = [];
$catTotais = [];
foreach($categoriasEstoque as $cat) {
    $catNomes[] = $cat['nome'];
    $catTotais[] = $cat['total'];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Almoxarifado</title>
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
.usuario{ display:flex; align-items:center; gap:10px; }
.usuario img{ width:45px; height:45px; border-radius:50%; }
.cards{ display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:20px; margin-top:25px; }
.card{ background:white; padding:20px; border-radius:12px; box-shadow:0 2px 5px rgba(0,0,0,0.1); transition:0.3s; }
.card:hover{ transform:translateY(-5px); }
.card i{ font-size:35px; color:#2563eb; margin-bottom:15px; }
.card h3{ color:#64748b; }
.card p{ margin-top:10px; font-size:28px; font-weight:bold; }
.dashboard-grid{ margin-top:30px; display:grid; grid-template-columns:2fr 1fr; gap:20px; }
.chart-box, .activity-box{ background:white; padding:20px; border-radius:12px; box-shadow:0 2px 5px rgba(0,0,0,0.1); }
.chart{ margin-top:20px; }
.bar{ margin-bottom:20px; }
.bar span{ display:block; margin-bottom:8px; font-weight:bold; }
.progress{ width:100%; height:20px; background:#e2e8f0; border-radius:20px; overflow:hidden; }
.progress div{ height:100%; border-radius:20px; }
.azul{ width:85%; background:#2563eb; }
.verde{ width:65%; background:green; }
.vermelho{ width:35%; background:red; }
.amarelo{ width:50%; background:orange; }
.activity{ margin-top:20px; }
.activity-item{ padding:15px; border-bottom:1px solid #ddd; }
.activity-item:last-child{ border:none; }
.activity-item h4{ margin-bottom:5px; }
.activity-item p{ color:#64748b; font-size:14px; }
.table-container{ margin-top:30px; background:white; padding:20px; border-radius:12px; box-shadow:0 2px 5px rgba(0,0,0,0.1); }
table{ width:100%; border-collapse:collapse; margin-top:20px; }
table th, table td{ padding:12px; border-bottom:1px solid #ddd; text-align:left; }
table th{ background:#e2e8f0; }
.status{ padding:5px 10px; border-radius:20px; color:white; font-size:12px; }
.disponivel{ background:green; }
.baixo{ background:orange; }
.zerado{ background:red; }
@media(max-width: 768px) {
    .dashboard-grid{ grid-template-columns: 1fr; }
}
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
        <h1>Dashboard</h1>
        <div class="usuario">
            <img src="https://i.pravatar.cc/100" alt="Usuário">
            <div>
                <strong><?= htmlspecialchars($_SESSION['usuario_nome']) ?></strong>
                <p>Almoxarifado Central <?= (isset($_SESSION['nivel_acesso']) && $_SESSION['nivel_acesso'] === 'admin') ? '<span style="color:var(--primary-color);font-weight:bold;">[Admin]</span>' : '' ?></p>
            </div>
        </div>
    </div>
    <div class="cards">
        <div class="card"><i class="fa fa-box"></i><h3>Total de Produtos</h3><p><?= $totalProdutos ?></p></div>
        <div class="card"><i class="fa fa-warehouse"></i><h3>Itens em Estoque</h3><p><?= $totalEstoque ?></p></div>
        <div class="card"><i class="fa fa-triangle-exclamation"></i><h3>Baixo Estoque</h3><p><?= $baixoEstoque ?></p></div>
        <div class="card"><i class="fa fa-truck"></i><h3>Fornecedores</h3><p><?= $totalFornecedores ?></p></div>
    </div>
    <div class="dashboard-grid">
        <div class="chart-box">
            <h2>Estoque por Categoria (Gráfico)</h2>
            <div style="width: 100%; height: 250px; display: flex; justify-content: center; margin-top: 20px;">
                <canvas id="graficoEstoque"></canvas>
            </div>
        </div>
        <div class="activity-box">
            <h2>Atividades Recentes</h2>
            <div class="activity">
                <?php foreach($movimentacoes as $mov): ?>
                <div class="activity-item">
                    <h4><?= $mov['tipo'] ?> de <?= htmlspecialchars($mov['produto_nome']) ?></h4>
                    <p><?= $mov['quantidade'] ?> itens <?= $mov['tipo'] == 'Entrada' ? 'adicionados' : 'removidos' ?>.</p>
                </div>
                <?php endforeach; ?>
                <?php if(empty($movimentacoes)): ?>
                <p style="padding: 15px;">Nenhuma movimentação recente.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="table-container">
        <h2>Últimos Produtos</h2>
        <table>
            <thead>
                <tr><th>ID</th><th>Produto</th><th>Categoria</th><th>Quantidade</th><th>Status</th></tr>
            </thead>
            <tbody>
                <?php foreach($ultimosProdutos as $prod): ?>
                    <?php
                        $classe = 'disponivel';
                        if ($prod['status'] == 'Baixo') $classe = 'baixo';
                        elseif ($prod['status'] == 'Zerado') $classe = 'zerado';
                    ?>
                <tr>
                    <td><?= $prod['id'] ?></td>
                    <td><?= htmlspecialchars($prod['nome']) ?></td>
                    <td><?= htmlspecialchars($prod['categoria_nome'] ?? 'Sem Categoria') ?></td>
                    <td><?= $prod['quantidade'] ?></td>
                    <td><span class="status <?= $classe ?>"><?= htmlspecialchars($prod['status']) ?></span></td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($ultimosProdutos)): ?>
                <tr><td colspan="5" style="text-align: center;">Nenhum produto cadastrado.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function toggleMenu(){
    let sidebar = document.getElementById("sidebar");
    sidebar.classList.toggle("active");
}
window.addEventListener('DOMContentLoaded', () => {
    if (localStorage.getItem("darkMode") === "true") {
        document.body.classList.add("dark-mode");
    }
    const ctx = document.getElementById('graficoEstoque');
    if (ctx) {
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($catNomes) ?>,
                datasets: [{
                    label: 'Quantidade em Estoque',
                    data: <?= json_encode($catTotais) ?>,
                    backgroundColor: [
                        '#2563eb', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4'
                    ],
                    borderWidth: 2,
                    borderColor: localStorage.getItem("darkMode") === "true" ? '#1e293b' : '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            color: localStorage.getItem("darkMode") === "true" ? '#f1f5f9' : '#0f172a'
                        }
                    }
                }
            }
        });
    }
});
</script>
</body>
</html>
