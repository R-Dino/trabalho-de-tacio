<?php
session_start();
require 'db.php';

// 1. GARANTIA DE SEGURANÇA: Se não houver sessão, chuta de volta para o login
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

try {
    // 2. METRICAS: Busca dos dados para os cards informativos
    // Total de produtos cadastrados
    $stmt = $pdo->query("SELECT COUNT(*) FROM produtos");
    $totalProdutos = $stmt->fetchColumn() ?: 0;

    // Produtos com estoque crítico (Baixo ou Zerado)
    $stmt = $pdo->query("SELECT COUNT(*) FROM produtos WHERE status IN ('Baixo', 'Zerado')");
    $baixoEstoque = $stmt->fetchColumn() ?: 0;

    // Soma total de todos os itens físicos no almoxarifado
    $stmt = $pdo->query("SELECT COALESCE(SUM(quantidade), 0) FROM produtos");
    $totalEstoque = $stmt->fetchColumn();

    // 3. TABELA: Últimos 5 produtos cadastrados para o histórico recente
    $stmt = $pdo->query("SELECT id, nome, quantidade, status FROM produtos ORDER BY id DESC LIMIT 5");
    $ultimosProdutos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Tratamento de erro caso a tabela não exista ou o banco caia
    die("Erro ao carregar dados do painel: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ALMOX | Painel de Controle</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-hover: #1d4ed8;
            --dark-color: #0f172a;
            --dark-light: #1e293b;
            --accent-color: #38bdf8;
            --bg-color: #f8fafc;
            --white: #ffffff;
            --text-color: #1e293b;
            --text-muted: #64748b;
            --shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        body {
            background: var(--bg-color);
            color: var(--text-color);
            display: flex;
            min-height: 100vh;
        }

        /* MENU LATERAL (SIDEBAR) */
        .sidebar {
            width: 260px;
            background: var(--dark-color);
            color: var(--white);
            padding: 24px;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            transition: var(--transition);
            z-index: 999;
            box-shadow: 4px 0 10px rgba(0,0,0,0.05);
        }

        .logo {
            text-align: center;
            padding-bottom: 24px;
            border-bottom: 1px solid var(--dark-light);
            margin-bottom: 24px;
        }

        .logo h2 {
            color: var(--accent-color);
            font-size: 1.6rem;
            letter-spacing: 1px;
        }

        .menu {
            list-style: none;
        }

        .menu li {
            margin-bottom: 8px;
        }

        .menu a {
            color: #94a3b8;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border-radius: 8px;
            font-weight: 500;
            transition: var(--transition);
        }

        .menu a:hover, .menu li.active a {
            background: var(--dark-light);
            color: var(--white);
        }

        /* CONTEÚDO PRINCIPAL (MAIN) */
        .main {
            flex: 1;
            padding: 40px;
            margin-left: 260px;
            transition: var(--transition);
        }

        /* BARRA SUPERIOR (TOPBAR) */
        .topbar {
            background: var(--white);
            padding: 20px 32px;
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow);
            margin-bottom: 32px;
        }

        .topbar h1 {
            font-size: 1.5rem;
            color: var(--text-color);
        }

        .btn-logout {
            background: #ef4444;
            color: var(--white);
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
        }

        .btn-logout:hover {
            background: #dc2626;
            transform: translateY(-1px);
        }

        /* BOTÃO DO MENU RESPONSIVO */
        .menu-toggle {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1000;
            border: none;
            background: var(--primary-color);
            color: var(--white);
            width: 45px;
            height: 45px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.2rem;
            display: none;
            box-shadow: 0 4px 6px rgba(37, 99, 235, 0.2);
            transition: var(--transition);
        }

        .menu-toggle:hover {
            background: var(--primary-hover);
        }

        /* CARTÕES DE MÉTRICAS (CARDS) */
        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }

        .card {
            background: var(--white);
            padding: 24px;
            border-radius: 12px;
            box-shadow: var(--shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .card-info h3 {
            color: var(--text-muted);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .card-info p {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-color);
        }

        .card-icon {
            font-size: 2.2rem;
        }

        /* Cores dinâmicas nos ícones dos cards */
        .card:nth-child(1) .card-icon { color: #3b82f6; }
        .card:nth-child(2) .card-icon { color: #f59e0b; }
        .card:nth-child(3) .card-icon { color: #10b981; }

        /* TABELA DE DADOS */
        .table-container {
            background: var(--white);
            padding: 28px;
            border-radius: 12px;
            box-shadow: var(--shadow);
        }

        .table-container h2 {
            font-size: 1.2rem;
            color: var(--text-color);
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th, table td {
            padding: 14px 16px;
            text-align: left;
            font-size: 0.95rem;
        }

        table th {
            background: #f1f5f9;
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
        }

        table tr {
            border-bottom: 1px solid #e2e8f0;
            transition: var(--transition);
        }

        table tbody tr:hover {
            background: #f8fafc;
        }

        table tr:last-child {
            border-bottom: none;
        }

        /* BADGES DE STATUS */
        .status {
            padding: 6px 12px;
            border-radius: 50px;
            color: var(--white);
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            display: inline-block;
        }

        .disponivel { background-color: #10b981; }
        .baixo { background-color: #f59e0b; }
        .zerado { background-color: #ef4444; }

        /* RESPONSIVIDADE (TABLETS E CELULARES) */
        @media (max-width: 992px) {
            .sidebar { left: -260px; }
            .sidebar.active { left: 0; }
            .main { margin-left: 0; padding: 24px; padding-top: 85px; }
            .menu-toggle { display: block; }
            .topbar { margin-bottom: 24px; }
        }
    </style>
</head>
<body>

    <button class="menu-toggle" onclick="toggleMenu()" aria-label="Abrir menu">
        <i class="fa fa-bars"></i>
    </button>

    <aside class="sidebar" id="sidebar">
        <div class="logo">
            <h2>ALMOX</h2>
        </div>
        <ul class="menu">
            <li class="active"><a href="dashboard.php"><i class="fa fa-chart-line"></i> Dashboard</a></li>
            <li><a href="produtos.php"><i class="fa fa-box"></i> Produtos</a></li>
            <li><a href="estoque.php"><i class="fa fa-warehouse"></i> Estoque</a></li>
            <li><a href="fornecedores.php"><i class="fa fa-truck"></i> Fornecedores</a></li>
            <li><a href="relatorios.php"><i class="fa fa-file-alt"></i> Relatórios</a></li>
            <li><a href="configuracoes.php"><i class="fa fa-cog"></i> Configurações</a></li>
        </ul>
    </aside>

    <main class="main">

        <header class="topbar">
            <h1>Olá, <?= htmlspecialchars($_SESSION['usuario_nome'] ?? 'Usuário') ?> 👋</h1>
            <a href="logout.php" class="btn-logout"><i class="fa fa-sign-out-alt"></i> Sair</a>
        </header>

        <section class="cards">
            <div class="card">
                <div class="card-info">
                    <h3>Total de Produtos</h3>
                    <p><?= $totalProdutos ?></p>
                </div>
                <i class="fa fa-box card-icon"></i>
            </div>

            <div class="card">
                <div class="card-info">
                    <h3>Atenção / Crítico</h3>
                    <p><?= $baixoEstoque ?></p>
                </div>
                <i class="fa fa-exclamation-triangle card-icon"></i>
            </div>

            <div class="card">
                <div class="card-info">
                    <h3>Itens Estocados</h3>
                    <p><?= $totalEstoque ?></p>
                </div>
                <i class="fa fa-boxes-stacked card-icon"></i>
            </div>
        </section>

        <section class="table-container">
            <h2>Últimos Produtos Cadastrados</h2>
            <table>
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
                            // Definição da cor do badge baseada no status do banco
                            $classe = 'disponivel';
                            if ($prod['status'] === 'Baixo') $classe = 'baixo';
                            elseif ($prod['status'] === 'Zerado') $classe = 'zerado';
                        ?>
                        <tr>
                            <td>#<?= $prod['id'] ?></td>
                            <td><strong><?= htmlspecialchars($prod['nome']) ?></strong></td>
                            <td><?= (int)$prod['quantidade'] ?> un.</td>
                            <td>
                                <span class="status <?= $classe ?>">
                                    <?= htmlspecialchars($prod['status']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($ultimosProdutos)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 40px;">
                                <i class="fa fa-folder-open" style="font-size: 2.5rem; display: block; margin-bottom: 12px; opacity: 0.3;"></i>
                                Nenhum produto cadastrado no momento.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

    </main>

    <script>
    function toggleMenu(){
        document.getElementById("sidebar").classList.toggle("active");
    }
    </script>
</body>
</html>