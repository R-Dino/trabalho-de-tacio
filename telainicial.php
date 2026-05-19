<?php
session_start();
require 'db.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

// Obter totais
$stmt = $pdo->query("SELECT COUNT(*) FROM produtos");
$totalProdutos = $stmt->fetchColumn() ?: 0;

$stmt = $pdo->query("SELECT COUNT(*) FROM produtos WHERE status = 'Baixo' OR status = 'Zerado'");
$baixoEstoque = $stmt->fetchColumn() ?: 0;

$stmt = $pdo->query("SELECT SUM(quantidade) FROM produtos");
$totalEstoque = $stmt->fetchColumn() ?: 0;

// Obter últimos produtos
$stmt = $pdo->query("SELECT * FROM produtos ORDER BY id DESC LIMIT 5");
$ultimosProdutos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Almoxarifado Funcional</title>

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:Arial, Helvetica, sans-serif;
}

body{
    background:#f1f5f9;
}

/* BOTÃO MENU */

.menu-toggle{
    position:fixed;
    top:15px;
    left:15px;
    z-index:1000;
    border:none;
    background:#2563eb;
    color:white;
    width:45px;
    height:45px;
    border-radius:8px;
    cursor:pointer;
    font-size:20px;
}

/* MENU */

.sidebar{
    width:250px;
    height:100vh;
    background:#0f172a;
    color:white;
    padding:20px;
    position:fixed;
    left:-250px;
    top:0;
    transition:0.4s;
    z-index:999;
}

.sidebar.active{
    left:0;
}

.logo{
    text-align:center;
    margin-bottom:40px;
}

.logo h2{
    color:#38bdf8;
}

.menu{
    list-style:none;
}

.menu li{
    margin:15px 0;
}

.menu a{
    color:white;
    text-decoration:none;
    display:flex;
    align-items:center;
    gap:10px;
    padding:12px;
    border-radius:8px;
    transition:0.3s;
}

.menu a:hover{
    background:#1e293b;
}

/* MAIN */

.main{
    width:100%;
    padding:20px;
}

/* TOPO */

.topbar{
    background:white;
    padding:15px 20px;
    border-radius:10px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    box-shadow:0 2px 5px rgba(0,0,0,0.1);
    margin-top:60px;
}

.topbar input{
    width:300px;
    padding:10px;
    border:1px solid #ccc;
    border-radius:8px;
}

/* CARDS */

.cards{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
    gap:20px;
    margin-top:25px;
}

.card{
    background:white;
    padding:20px;
    border-radius:12px;
    box-shadow:0 2px 5px rgba(0,0,0,0.1);
}

.card h3{
    color:#64748b;
}

.card p{
    margin-top:10px;
    font-size:28px;
    font-weight:bold;
}


/* TABELA */

.table-container{
    margin-top:30px;
    background:white;
    padding:20px;
    border-radius:12px;
    box-shadow:0 2px 5px rgba(0,0,0,0.1);
}

table{
    width:100%;
    border-collapse:collapse;
    margin-top:20px;
}

table th,
table td{
    padding:12px;
    border-bottom:1px solid #ddd;
    text-align:left;
}

table th{
    background:#e2e8f0;
}

.status{
    padding:5px 10px;
    border-radius:20px;
    color:white;
    font-size:12px;
}

.disponivel{
    background:green;
}

.baixo{
    background:orange;
}
.zerado{
    background:red;
}

.btn-logout {
    background: #ef4444; color: white; padding: 10px 15px; border-radius: 8px; text-decoration: none; font-weight: bold; font-size: 14px;
}
.btn-logout:hover {
    background: #dc2626;
}

</style>
</head>
<body>

<!-- BOTÃO MENU -->

<button class="menu-toggle" onclick="toggleMenu()">
    <i class="fa fa-bars"></i>
</button>

<!-- MENU -->
<div class="sidebar" id="sidebar">

    <div class="logo">
        <h2>ALMOX</h2>
    </div>

    <ul class="menu">
        <li><a href="dashboard.php"><i class="fa fa-chart-line"></i> Dashboard</a></li>
        <li><a href="produtos.php"><i class="fa fa-box"></i> Produtos</a></li>
        <li><a href="estoque.php"><i class="fa fa-warehouse"></i> Estoque</a></li>
        <li><a href="fornecedores.php"><i class="fa fa-truck"></i> Fornecedores</a></li>
        <li><a href="relatorios.php"><i class="fa fa-file"></i> Relatórios</a></li>
        <li><a href="configuracoes.php"><i class="fa fa-gear"></i> Configurações</a></li>
    </ul>

</div>

<!-- CONTEÚDO -->
<div class="main">

    <!-- TOPO -->
    <div class="topbar">
        <h1>Olá, <?= htmlspecialchars($_SESSION['usuario_nome']) ?></h1>
        <a href="logout.php" class="btn-logout"><i class="fa fa-sign-out-alt"></i> Sair</a>
    </div>

    <!-- CARDS -->
    <div class="cards">

        <div class="card">
            <h3>Total de Produtos</h3>
            <p id="totalProdutos"><?= $totalProdutos ?></p>
        </div>

        <div class="card">
            <h3>Produtos Baixo/Zerado</h3>
            <p id="baixoEstoque"><?= $baixoEstoque ?></p>
        </div>

        <div class="card">
            <h3>Total em Estoque</h3>
            <p id="totalEstoque"><?= $totalEstoque ?></p>
        </div>

    </div>

    <!-- TABELA -->
    <div class="table-container">

        <h2>Últimos Produtos Cadastrados</h2>

        <table id="tabelaProdutos">

            <thead>
                <tr>
                    <th>ID</th>
                    <th>Produto</th>
                    <th>Quantidade</th>
                    <th>Status</th>
                </tr>
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
                    <td><?= $prod['quantidade'] ?></td>
                    <td>
                        <span class="status <?= $classe ?>">
                            <?= htmlspecialchars($prod['status']) ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($ultimosProdutos)): ?>
                <tr>
                    <td colspan="4" style="text-align: center;">Nenhum produto cadastrado.</td>
                </tr>
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
