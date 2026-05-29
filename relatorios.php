<?php
session_start();
require 'db.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

// Lógica para exportar os logs (CSV)
if (isset($_GET['exportar'])) {
    $tipo = $_GET['exportar'];
    
    if ($tipo === 'produtos') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=log_produtos.csv');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Produto', 'Categoria', 'Quantidade', 'Preço', 'Status']);
        
        $stmt = $pdo->query("SELECT p.id, p.nome, c.nome as categoria, p.quantidade, p.preco, p.status FROM produtos p LEFT JOIN categorias c ON p.categoria_id = c.id ORDER BY p.id DESC");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, $row);
        }
        exit;
    }

    if ($tipo === 'estoque') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=log_movimentacoes.csv');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Produto', 'Quantidade Movimentada', 'Tipo', 'Data']);
        
        $stmt = $pdo->query("SELECT m.id, p.nome as produto, m.quantidade, m.tipo, m.data_movimentacao FROM movimentacoes m JOIN produtos p ON m.produto_id = p.id ORDER BY m.id DESC");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, $row);
        }
        exit;
    }
}

// Estatísticas
$totalProdutos = $pdo->query("SELECT COUNT(*) FROM produtos")->fetchColumn() ?: 0;
$totalMovimentacoes = $pdo->query("SELECT COUNT(*) FROM movimentacoes")->fetchColumn() ?: 0;
$totalFornecedores = $pdo->query("SELECT COUNT(*) FROM fornecedores")->fetchColumn() ?: 0;
$baixoEstoque = $pdo->query("SELECT COUNT(*) FROM produtos WHERE status = 'Baixo' OR status = 'Zerado'")->fetchColumn() ?: 0;

// Obter logs do sistema
$logsSistema = [];
try {
    $stmtLogs = $pdo->query("SELECT l.*, u.nome as responsavel FROM logs_atividades l LEFT JOIN usuarios u ON l.usuario_id = u.id ORDER BY l.id DESC LIMIT 50");
    if ($stmtLogs) {
        $logsSistema = $stmtLogs->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    // Caso a tabela não exista, $logsSistema continua vazio
}
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

button, .btn-export{ display:inline-block; padding:12px 20px; border:none; border-radius:8px; background:#2563eb; color:white; cursor:pointer; font-weight:bold; transition:0.3s; text-decoration:none; text-align:center; }
button:hover, .btn-export:hover{ background:#1d4ed8; }

/* TABELA */
.table-container{ margin-top:30px; background:white; padding:20px; border-radius:12px; box-shadow:0 2px 5px rgba(0,0,0,0.1); }
table{ width:100%; border-collapse:collapse; margin-top:20px; }
table th, table td{ padding:12px; border-bottom:1px solid #ddd; text-align:left; }
table th{ background:#e2e8f0; }

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

<button class="menu-toggle" onclick="toggleMenu()"><i class="fa fa-bars"></i></button>

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
            <p>Faça o download de todos os produtos do seu estoque.</p>
            <a href="relatorios.php?exportar=produtos" class="btn-export"><i class="fa fa-download"></i> Baixar Log (CSV)</a>
        </div>
        <div class="report-card">
            <h2>Log de Estoque</h2>
            <p>Baixe o histórico completo de entradas e saídas.</p>
            <a href="relatorios.php?exportar=estoque" class="btn-export"><i class="fa fa-download"></i> Baixar Log (CSV)</a>
        </div>
        <div class="report-card">
            <h2>Relatório de Fornecedores</h2>
            <p>Veja os fornecedores cadastrados no sistema.</p>
            <button onclick="gerarRelatorio('Fornecedores')">Simular Relatório</button>
        </div>
        <div class="report-card">
            <h2>Logs em PDF</h2>
            <p>Gere um arquivo PDF com os últimos logs de atividades.</p>
            <button onclick="exportarPDF()" style="background:#ef4444;"><i class="fa fa-file-pdf"></i> Gerar PDF</button>
        </div>
    </div>

    <!-- TABELA -->
    <div class="table-container">
        <h2>Logs do Sistema (Atividades Recentes)</h2>
        <table id="tabelaLogs">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Responsável</th>
                    <th>Ação</th>
                    <th>Detalhes</th>
                    <th>Data/Hora</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($logsSistema as $log): ?>
                <tr>
                    <td><?= $log['id'] ?></td>
                    <td><?= htmlspecialchars($log['responsavel'] ?? 'Sistema') ?></td>
                    <td><strong><?= htmlspecialchars($log['acao']) ?></strong></td>
                    <td><?= htmlspecialchars($log['detalhes']) ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($log['data_hora'])) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($logsSistema)): ?>
                <tr>
                    <td colspan="5" style="text-align: center;">Nenhuma atividade recente registrada.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
<script>
function toggleMenu(){
    let sidebar = document.getElementById("sidebar");
    sidebar.classList.toggle("active");
}

let contador = 1;

function gerarRelatorio(tipo){
    alert("Simulação de geração de relatório de " + tipo + " disparada.");
}

function exportarPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    
    doc.setFontSize(18);
    doc.text("Relatório de Logs do Sistema", 14, 22);
    
    doc.setFontSize(11);
    doc.text("Gerado em: " + new Date().toLocaleString("pt-BR"), 14, 30);
    
    doc.autoTable({
        html: '#tabelaLogs',
        startY: 35,
        theme: 'striped',
        styles: { fontSize: 9 },
        headStyles: { fillColor: [37, 99, 235] }
    });
    
    doc.save('logs_sistema.pdf');
}

        window.addEventListener('DOMContentLoaded', () => {
            if (localStorage.getItem("darkMode") === "true") {
                document.body.classList.add("dark-mode");
            }
        });
</script>

</body>
</html>
