<?php
session_start();
require 'db.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

// Lidar com a exclusão de produto
if (isset($_GET['excluir'])) {
    if (!isset($_SESSION['nivel_acesso']) || $_SESSION['nivel_acesso'] !== 'admin') {
        die("Acesso negado. Apenas administradores podem excluir.");
    }
    $id = (int)$_GET['excluir'];
    $stmt = $pdo->prepare("DELETE FROM produtos WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: produtos.php");
    exit;
}

// Lidar com a adição de produto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'adicionar') {
    $nome = trim($_POST['nome']);
    $quantidade = (int)$_POST['quantidade'];
    $preco = (float)$_POST['preco'];
    $categoria_id = (int)$_POST['categoria_id'];

    if (!empty($nome) && $categoria_id > 0) {
        $status = 'Disponível';
        if ($quantidade == 0) {
            $status = 'Zerado';
        } elseif ($quantidade <= 20) {
            $status = 'Baixo';
        }

        $stmt = $pdo->prepare("INSERT INTO produtos (nome, quantidade, preco, categoria_id, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nome, $quantidade, $preco, $categoria_id, $status]);
        header("Location: produtos.php");
        exit;
    }
}

// Obter categorias para o formulário
$categorias = $pdo->query("SELECT * FROM categorias ORDER BY nome ASC")->fetchAll();

// Obter produtos para a tabela
$termo_pesquisa = $_GET['pesquisa'] ?? '';
if (!empty($termo_pesquisa)) {
    $stmt = $pdo->prepare("SELECT p.*, c.nome as categoria_nome FROM produtos p LEFT JOIN categorias c ON p.categoria_id = c.id WHERE p.nome LIKE ? ORDER BY p.id DESC");
    $stmt->execute(["%$termo_pesquisa%"]);
    $produtos = $stmt->fetchAll();
} else {
    $produtos = $pdo->query("SELECT p.*, c.nome as categoria_nome FROM produtos p LEFT JOIN categorias c ON p.categoria_id = c.id ORDER BY p.id DESC")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Página de Produtos</title>

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
.topbar form { display: flex; gap: 10px; }
.topbar input{ width:300px; padding:10px; border:1px solid #ccc; border-radius:8px; }

/* FORMULÁRIO */
.form-container{ margin-top:30px; background:white; padding:20px; border-radius:12px; box-shadow:0 2px 5px rgba(0,0,0,0.1); }
.form-container h2{ margin-bottom:20px; }
.form-grid{ display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:15px; }
.form-grid input, .form-grid select{ padding:12px; border:1px solid #ccc; border-radius:8px; }

button.btn-primary{ margin-top:20px; padding:12px 20px; border:none; border-radius:8px; background:#2563eb; color:white; cursor:pointer; font-weight:bold; transition:0.3s; }
button.btn-primary:hover{ background:#1d4ed8; }

/* TABELA */
.table-container{ margin-top:30px; background:white; padding:20px; border-radius:12px; box-shadow:0 2px 5px rgba(0,0,0,0.1); overflow-x: auto; }
table{ width:100%; border-collapse:collapse; margin-top:20px; }
table th, table td{ padding:12px; border-bottom:1px solid #ddd; text-align:left; }
table th{ background:#e2e8f0; }

/* STATUS */
.status{ padding:5px 10px; border-radius:20px; color:white; font-size:12px; }
.disponivel{ background:green; }
.baixo{ background:orange; }
.zerado{ background:red; }

/* AÇÕES */
.acoes a.btn-delete { padding:8px 12px; background:red; color:white; text-decoration:none; border-radius:8px; font-size:14px; }
.acoes a.btn-delete:hover{ background:darkred; }

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
        <h1>Produtos</h1>
        <form method="GET" action="produtos.php">
            <input type="text" name="pesquisa" placeholder="Pesquisar produto..." value="<?= htmlspecialchars($termo_pesquisa) ?>">
            <button type="submit" class="btn-primary" style="margin-top:0;">Buscar</button>
        </form>
    </div>

    <!-- FORMULÁRIO -->
    <div class="form-container">
        <h2>Cadastrar Produto</h2>
        <form method="POST" action="produtos.php">
            <input type="hidden" name="acao" value="adicionar">
            <div class="form-grid">
                <input type="text" name="nome" placeholder="Nome do produto" required>
                <input type="number" name="quantidade" placeholder="Quantidade inicial" value="0" required>
                <input type="number" step="0.01" name="preco" placeholder="Preço" value="0.00" required>
                <select name="categoria_id" required>
                    <option value="">Categoria</option>
                    <?php foreach($categorias as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn-primary">Adicionar Produto</button>
        </form>
    </div>

    <!-- TABELA -->
    <div class="table-container">
        <h2>Lista de Produtos</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Produto</th>
                    <th>Categoria</th>
                    <th>Quantidade</th>
                    <th>Preço</th>
                    <th>Status</th>
                    <?php if (isset($_SESSION['nivel_acesso']) && $_SESSION['nivel_acesso'] === 'admin'): ?>
                    <th>Ações</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach($produtos as $prod): ?>
                    <?php
                        $classe = 'disponivel';
                        if ($prod['status'] == 'Baixo') $classe = 'baixo';
                        elseif ($prod['status'] == 'Zerado') $classe = 'zerado';
                    ?>
                <tr>
                    <td><?= $prod['id'] ?></td>
                    <td><?= htmlspecialchars($prod['nome']) ?></td>
                    <td><?= htmlspecialchars($prod['categoria_nome'] ?? 'N/A') ?></td>
                    <td><?= $prod['quantidade'] ?></td>
                    <td>R$ <?= number_format($prod['preco'], 2, ',', '.') ?></td>
                    <td><span class="status <?= $classe ?>"><?= htmlspecialchars($prod['status']) ?></span></td>
                    <?php if (isset($_SESSION['nivel_acesso']) && $_SESSION['nivel_acesso'] === 'admin'): ?>
                    <td class="acoes">
                        <a href="#" onclick="editarProduto(<?= $prod['id'] ?>, '<?= addslashes(htmlspecialchars($prod['nome'])) ?>', <?= $prod['quantidade'] ?>, <?= $prod['preco'] ?>, <?= $prod['categoria_id'] ?>)" style="padding:8px 12px; background:#f59e0b; color:white; text-decoration:none; border-radius:8px; font-size:14px; margin-right:5px;"><i class='fa fa-pen'></i> Editar</a>
                        <a href="produtos.php?excluir=<?= $prod['id'] ?>" class="btn-delete" onclick="return confirm('Tem certeza que deseja excluir?');">Excluir</a>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($produtos)): ?>
                <tr><td colspan="7" style="text-align: center;">Nenhum produto encontrado.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<script>
function editarProduto(id, nome, quantidade, preco, categoria_id) {
    document.querySelector('input[name="acao"]').value = 'editar';
    let idInput = document.querySelector('input[name="id_produto"]');
    if(!idInput) {
        idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'id_produto';
        document.querySelector('.form-container form').appendChild(idInput);
    }
    idInput.value = id;
    document.querySelector('input[name="nome"]').value = nome;
    document.querySelector('input[name="quantidade"]').value = quantidade;
    document.querySelector('input[name="preco"]').value = preco;
    document.querySelector('select[name="categoria_id"]').value = categoria_id;
    document.querySelector('.form-container h2').innerText = "Editar Produto";
    document.querySelector('.form-container button').innerText = "Salvar Alterações";
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
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
