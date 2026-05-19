<?php
session_start();
require 'db.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

// Estatísticas para os cards
$totalProdutos = $pdo->query("SELECT COUNT(*) FROM produtos")->fetchColumn() ?: 0;
$totalEstoque = $pdo->query("SELECT SUM(quantidade) FROM produtos")->fetchColumn() ?: 0;
$baixoEstoque = $pdo->query("SELECT COUNT(*) FROM produtos WHERE status = 'Baixo' OR status = 'Zerado'")->fetchColumn() ?: 0;
$totalFornecedores = $pdo->query("SELECT COUNT(*) FROM fornecedores")->fetchColumn() ?: 0;

// Atividades Recentes (Movimentações)
$movimentacoes = $pdo->query("
    SELECT m.*, p.nome as produto_nome 
    FROM movimentacoes m 
    JOIN produtos p ON m.produto_id = p.id 
    ORDER BY m.data_movimentacao DESC LIMIT 4
")->fetchAll();

// Últimos Produtos
$ultimosProdutos = $pdo->query("
    SELECT p.*, c.nome as categoria_nome 
    FROM produtos p 
    LEFT JOIN categorias c ON p.categoria_id = c.id 
    ORDER BY p.id DESC LIMIT 4
")->fetchAll();

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

/* MENU */
.menu-toggle{ position:fixed; top:15px; left:15px; z-index:1000; border:none; background:#2563eb; color:white; width:45px; height:45px; border-radius:8px; cursor:pointer; font-size:20px; }
.sidebar{ width:250px; height:100vh; background:#0f172a; color:white; padding:20px; position:fixed; left:-250px; top:0; transition:0.4s; z-index:999; }
.sidebar.active{ left:0; }
.logo{ text-align:center; margin-bottom:40px; }
.logo h2{ color:#38bdf8; }
.menu{ list-style:none; }
.menu li{ margin:15px 0; }
.menu a{ color:white; text-decoration:none; display:flex; align-items:center; gap:10px; padding:12px; border-radius:8px; transition:0.3s; }
.menu a:hover{ background:#1e293b; }

/* MAIN */
.main{ width:100%; padding:20px; }

/* TOPO */
.topbar{ background:white; padding:15px 20px; border-radius:10px; display:flex; justify-content:space-between; align-items:center; box-shadow:0 2px 5px rgba(0,0,0,0.1); margin-top:60px; }
.usuario{ display:flex; align-items:center; gap:10px; }
.usuario img{ width:45px; height:45px; border-radius:50%; }

/* CARDS */
.cards{ display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:20px; margin-top:25px; }
.card{ background:white; padding:20px; border-radius:12px; box-shadow:0 2px 5px rgba(0,0,0,0.1); transition:0.3s; }
.card:hover{ transform:translateY(-5px); }
.card i{ font-size:35px; color:#2563eb; margin-bottom:15px; }
.card h3{ color:#64748b; }
.card p{ margin-top:10px; font-size:28px; font-weight:bold; }

/* GRÁFICOS E ATIVIDADES */
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

/* ATIVIDADES */
.activity{ margin-top:20px; }
.activity-item{ padding:15px; border-bottom:1px solid #ddd; }
.activity-item:last-child{ border:none; }
.activity-item h4{ margin-bottom:5px; }
.activity-item p{ color:#64748b; font-size:14px; }

/* TABELA */
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
</style>
</head>
<body>

<button class="menu-toggle" onclick="toggleMenu()"><i class="fa fa-bars"></i></button>

<div class="sidebar" id="sidebar">
    <div class="logo"><h2>ALMOX</h2></div>
    <ul class="menu">
        <li><a href="telainicial.php"><i class="fa fa-house"></i> Início</a></li>
        <li><a href="dashboard.php"><i class="fa fa-chart-line"></i> Dashboard</a></li>
        <li><a href="produtos.php"><i class="fa fa-box"></i> Produtos</a></li>
        <li><a href="estoque.php"><i class="fa fa-warehouse"></i> Estoque</a></li>
        <li><a href="fornecedores.php"><i class="fa fa-truck"></i> Fornecedores</a></li>
        <li><a href="relatorios.php"><i class="fa fa-file"></i> Relatórios</a></li>
        <li><a href="configuracoes.php"><i class="fa fa-gear"></i> Configurações</a></li>
    </ul>
</div>

<div class="main">
    <div class="topbar">
        <h1>Dashboard</h1>
        <div class="usuario">
            <img src="https://i.pravatar.cc/100" alt="Usuário">
            <div>
                <strong><?= htmlspecialchars($_SESSION['usuario_nome']) ?></strong>
                <p>Almoxarifado Central</p>
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
            <h2>Movimentação de Estoque (Demo Visual)</h2>
            <div class="chart">
                <div class="bar"><span>Produtos de Limpeza</span><div class="progress"><div class="azul"></div></div></div>
                <div class="bar"><span>Ferramentas</span><div class="progress"><div class="verde"></div></div></div>
                <div class="bar"><span>Equipamentos</span><div class="progress"><div class="vermelho"></div></div></div>
                <div class="bar"><span>Escritório</span><div class="progress"><div class="amarelo"></div></div></div>
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

<script>
function toggleMenu(){
    let sidebar = document.getElementById("sidebar");
    sidebar.classList.toggle("active");
}
</script>
</body>
</html>
