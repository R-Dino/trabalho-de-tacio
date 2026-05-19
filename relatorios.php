<?php
session_start();
require 'db.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

// Estatísticas
$totalProdutos = $pdo->query("SELECT COUNT(*) FROM produtos")->fetchColumn() ?: 0;
$totalMovimentacoes = $pdo->query("SELECT COUNT(*) FROM movimentacoes")->fetchColumn() ?: 0;
$totalFornecedores = $pdo->query("SELECT COUNT(*) FROM fornecedores")->fetchColumn() ?: 0;
$baixoEstoque = $pdo->query("SELECT COUNT(*) FROM produtos WHERE status = 'Baixo' OR status = 'Zerado'")->fetchColumn() ?: 0;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Relatórios</title>

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

/* CARDS */
.cards{ display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:20px; margin-top:25px; }
.card{ background:white; padding:20px; border-radius:12px; box-shadow:0 2px 5px rgba(0,0,0,0.1); }
.card h3{ color:#64748b; }
.card p{ margin-top:10px; font-size:28px; font-weight:bold; }

/* RELATÓRIOS */
.report-container{ margin-top:30px; display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:20px; }
.report-card{ background:white; padding:25px; border-radius:12px; box-shadow:0 2px 5px rgba(0,0,0,0.1); }
.report-card h2{ margin-bottom:10px; color:#0f172a; }
.report-card p{ color:#64748b; margin-bottom:20px; }

button{ padding:12px 20px; border:none; border-radius:8px; background:#2563eb; color:white; cursor:pointer; font-weight:bold; transition:0.3s; }
button:hover{ background:#1d4ed8; }

/* TABELA */
.table-container{ margin-top:30px; background:white; padding:20px; border-radius:12px; box-shadow:0 2px 5px rgba(0,0,0,0.1); }
table{ width:100%; border-collapse:collapse; margin-top:20px; }
table th, table td{ padding:12px; border-bottom:1px solid #ddd; text-align:left; }
table th{ background:#e2e8f0; }
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
        <h1>Relatórios do Almoxarifado</h1>
    </div>

    <!-- CARDS -->
    <div class="cards">
        <div class="card">
            <h3>Total de Produtos</h3>
            <p><?= $totalProdutos ?></p>
        </div>
        <div class="card">
            <h3>Movimentações</h3>
            <p><?= $totalMovimentacoes ?></p>
        </div>
        <div class="card">
            <h3>Fornecedores</h3>
            <p><?= $totalFornecedores ?></p>
        </div>
        <div class="card">
            <h3>Baixo Estoque</h3>
            <p><?= $baixoEstoque ?></p>
        </div>
    </div>

    <!-- RELATÓRIOS -->
    <div class="report-container">
        <div class="report-card">
            <h2>Relatório de Produtos</h2>
            <p>Visualize todos os produtos cadastrados no sistema.</p>
            <button onclick="gerarRelatorio('Produtos')">Simular Relatório</button>
        </div>
        <div class="report-card">
            <h2>Relatório de Estoque</h2>
            <p>Consulte entradas e saídas de produtos.</p>
            <button onclick="gerarRelatorio('Estoque')">Simular Relatório</button>
        </div>
        <div class="report-card">
            <h2>Relatório de Fornecedores</h2>
            <p>Veja os fornecedores cadastrados no sistema.</p>
            <button onclick="gerarRelatorio('Fornecedores')">Simular Relatório</button>
        </div>
    </div>

    <!-- TABELA -->
    <div class="table-container">
        <h2>Histórico de Relatórios Gerados na Sessão</h2>
        <table id="tabelaRelatorios">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Relatório</th>
                    <th>Data</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>

</div>

<script>
function toggleMenu(){
    let sidebar = document.getElementById("sidebar");
    sidebar.classList.toggle("active");
}

let contador = 1;

function gerarRelatorio(tipo){
    let tabela = document.querySelector("#tabelaRelatorios tbody");
    let linha = tabela.insertRow();
    let dataAtual = new Date().toLocaleDateString("pt-BR") + ' ' + new Date().toLocaleTimeString("pt-BR");

    linha.innerHTML = `
        <td>${contador}</td>
        <td>${tipo}</td>
        <td>${dataAtual}</td>
        <td>Gerado</td>
    `;
    contador++;
    alert("Relatório de " + tipo + " gerado com sucesso! (Demonstração)");
}
</script>

</body>
</html>
