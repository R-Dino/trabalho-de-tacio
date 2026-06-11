<?php
$toast_html = <<<HTML
<style>
.toast-container { position: fixed; top: 20px; right: 20px; z-index: 9999; }
.toast { background: #333; color: white; padding: 15px 20px; border-radius: 8px; margin-bottom: 10px; font-weight: bold; box-shadow: 0 4px 6px rgba(0,0,0,0.1); display: flex; align-items: center; gap: 10px; animation: slideIn 0.3s, fadeOut 0.5s 2.5s forwards; }
.toast.sucesso { background: #10b981; }
.toast.erro { background: #ef4444; }
@keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
@keyframes fadeOut { from { opacity: 1; } to { opacity: 0; display: none; } }
</style>
<div class="toast-container">
    <?php if (isset(\$_SESSION['msg_sucesso'])): ?>
        <div class="toast sucesso"><i class="fa fa-check-circle"></i> <?= htmlspecialchars(\$_SESSION['msg_sucesso']) ?></div>
        <?php unset(\$_SESSION['msg_sucesso']); ?>
    <?php endif; ?>
    <?php if (isset(\$_SESSION['msg_erro'])): ?>
        <div class="toast erro"><i class="fa fa-exclamation-circle"></i> <?= htmlspecialchars(\$_SESSION['msg_erro']) ?></div>
        <?php unset(\$_SESSION['msg_erro']); ?>
    <?php endif; ?>
</div>
HTML;
$files = ['index.php', 'telainicial.php', 'dashboard.php', 'produtos.php', 'estoque.php', 'fornecedores.php', 'relatorios.php', 'usuarios.php', 'configuracoes.php'];
foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);
    if (strpos($content, 'toast-container') === false) {
        $content = str_replace('<body>', "<body>\n" . $toast_html, $content);
        file_put_contents($file, $content);
    }
}
$estoque = file_get_contents('estoque.php');
if (strpos($estoque, 'Validação contra estoque negativo') === false) {
    $search = "\$stmt = \$pdo->prepare(\"INSERT INTO movimentacoes (produto_id, quantidade, tipo) VALUES (?, ?, ?)\");";
    $replace = <<<PHP
        if (\$tipo == 'Saída') {
            \$check = \$pdo->query("SELECT quantidade FROM produtos WHERE id = \$produto_id")->fetch();
            if (\$check && \$check['quantidade'] < \$quantidade) {
                \$_SESSION['msg_erro'] = "Erro: Estoque insuficiente! Existem apenas {\$check['quantidade']} unidades no estoque.";
                header("Location: estoque.php");
                exit;
            }
        }
        \$stmt = \$pdo->prepare("INSERT INTO movimentacoes (produto_id, quantidade, tipo) VALUES (?, ?, ?)");
PHP;
    $estoque = str_replace($search, $replace, $estoque);
    $estoque = str_replace('header("Location: estoque.php");', "\$_SESSION['msg_sucesso'] = \"Operação realizada com sucesso!\";\n        header(\"Location: estoque.php\");", $estoque);
    file_put_contents('estoque.php', $estoque);
}
$produtos = file_get_contents('produtos.php');
if (strpos($produtos, "acao === 'editar'") === false) {
    $search = <<<PHP
    if (!empty(\$nome) && \$categoria_id > 0) {
        \$status = 'Disponível';
        if (\$quantidade == 0) {
            \$status = 'Zerado';
        } elseif (\$quantidade <= 20) {
            \$status = 'Baixo';
        }
        \$stmt = \$pdo->prepare("INSERT INTO produtos (nome, quantidade, preco, categoria_id, status) VALUES (?, ?, ?, ?, ?)");
        \$stmt->execute([\$nome, \$quantidade, \$preco, \$categoria_id, \$status]);
        header("Location: produtos.php");
        exit;
    }
}
PHP;
    $replace = <<<PHP
    if (!empty(\$nome) && \$categoria_id > 0) {
        \$status = 'Disponível';
        if (\$quantidade <= 0) \$status = 'Zerado';
        elseif (\$quantidade <= 20) \$status = 'Baixo';
        \$stmt = \$pdo->prepare("INSERT INTO produtos (nome, quantidade, preco, categoria_id, status) VALUES (?, ?, ?, ?, ?)");
        \$stmt->execute([\$nome, \$quantidade, \$preco, \$categoria_id, \$status]);
        \$_SESSION['msg_sucesso'] = "Produto adicionado com sucesso!";
        header("Location: produtos.php");
        exit;
    }
} elseif (\$_SERVER['REQUEST_METHOD'] === 'POST' && isset(\$_POST['acao']) && \$_POST['acao'] === 'editar') {
    \$id = (int)\$_POST['id_produto'];
    \$nome = trim(\$_POST['nome']);
    \$quantidade = (int)\$_POST['quantidade'];
    \$preco = (float)\$_POST['preco'];
    \$categoria_id = (int)\$_POST['categoria_id'];
    if (\$id > 0 && !empty(\$nome) && \$categoria_id > 0) {
        \$status = 'Disponível';
        if (\$quantidade <= 0) \$status = 'Zerado';
        elseif (\$quantidade <= 20) \$status = 'Baixo';
        \$stmt = \$pdo->prepare("UPDATE produtos SET nome = ?, quantidade = ?, preco = ?, categoria_id = ?, status = ? WHERE id = ?");
        \$stmt->execute([\$nome, \$quantidade, \$preco, \$categoria_id, \$status, \$id]);
        \$_SESSION['msg_sucesso'] = "Produto atualizado com sucesso!";
        header("Location: produtos.php");
        exit;
    }
}
PHP;
    $produtos = str_replace($search, $replace, $produtos);
    $search_btn = "<a href=\"produtos.php?excluir=<?= \$prod['id'] ?>\" class=\"btn-delete\"";
    $replace_btn = "<a href=\"#\" onclick=\"editarProduto(<?= \$prod['id'] ?>, '<?= addslashes(htmlspecialchars(\$prod['nome'])) ?>', <?= \$prod['quantidade'] ?>, <?= \$prod['preco'] ?>, <?= \$prod['categoria_id'] ?>)\" style=\"padding:8px 12px; background:#f59e0b; color:white; text-decoration:none; border-radius:8px; font-size:14px; margin-right:5px;\"><i class='fa fa-pen'></i> Editar</a>\n                        <a href=\"produtos.php?excluir=<?= \$prod['id'] ?>\" class=\"btn-delete\"";
    $produtos = str_replace($search_btn, $replace_btn, $produtos);
    $js_edit = <<<JS
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
JS;
    $produtos = str_replace("function toggleMenu(){", $js_edit . "\nfunction toggleMenu(){", $produtos);
    file_put_contents('produtos.php', $produtos);
}
$fornecedores = file_get_contents('fornecedores.php');
if (strpos($fornecedores, "acao === 'editar'") === false) {
    $search = <<<PHP
    if (!empty(\$nome) && !empty(\$cnpj)) {
        \$stmt = \$pdo->prepare("INSERT INTO fornecedores (nome, cnpj, telefone, email) VALUES (?, ?, ?, ?)");
        \$stmt->execute([\$nome, \$cnpj, \$telefone, \$email]);
        header("Location: fornecedores.php");
        exit;
    }
}
PHP;
    $replace = <<<PHP
    if (!empty(\$nome) && !empty(\$cnpj)) {
        \$stmt = \$pdo->prepare("INSERT INTO fornecedores (nome, cnpj, telefone, email) VALUES (?, ?, ?, ?)");
        \$stmt->execute([\$nome, \$cnpj, \$telefone, \$email]);
        \$_SESSION['msg_sucesso'] = "Fornecedor adicionado!";
        header("Location: fornecedores.php");
        exit;
    }
} elseif (\$_SERVER['REQUEST_METHOD'] === 'POST' && isset(\$_POST['acao']) && \$_POST['acao'] === 'editar') {
    \$id = (int)\$_POST['id_fornecedor'];
    \$nome = trim(\$_POST['nome']);
    \$cnpj = trim(\$_POST['cnpj']);
    \$telefone = trim(\$_POST['telefone']);
    \$email = trim(\$_POST['email']);
    if (\$id > 0 && !empty(\$nome) && !empty(\$cnpj)) {
        \$stmt = \$pdo->prepare("UPDATE fornecedores SET nome = ?, cnpj = ?, telefone = ?, email = ? WHERE id = ?");
        \$stmt->execute([\$nome, \$cnpj, \$telefone, \$email, \$id]);
        \$_SESSION['msg_sucesso'] = "Fornecedor atualizado!";
        header("Location: fornecedores.php");
        exit;
    }
}
PHP;
    $fornecedores = str_replace($search, $replace, $fornecedores);
    $search_btn = "<a href=\"fornecedores.php?excluir=<?= \$forn['id'] ?>\" class=\"btn-delete\"";
    $replace_btn = "<a href=\"#\" onclick=\"editarFornecedor(<?= \$forn['id'] ?>, '<?= addslashes(htmlspecialchars(\$forn['nome'])) ?>', '<?= addslashes(htmlspecialchars(\$forn['cnpj'])) ?>', '<?= addslashes(htmlspecialchars(\$forn['telefone'])) ?>', '<?= addslashes(htmlspecialchars(\$forn['email'])) ?>')\" style=\"padding:8px 12px; background:#f59e0b; color:white; text-decoration:none; border-radius:8px; font-size:14px; margin-right:5px;\"><i class='fa fa-pen'></i> Editar</a>\n                        <a href=\"fornecedores.php?excluir=<?= \$forn['id'] ?>\" class=\"btn-delete\"";
    $fornecedores = str_replace($search_btn, $replace_btn, $fornecedores);
    $js_edit = <<<JS
function editarFornecedor(id, nome, cnpj, telefone, email) {
    document.querySelector('input[name="acao"]').value = 'editar';
    let idInput = document.querySelector('input[name="id_fornecedor"]');
    if(!idInput) {
        idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'id_fornecedor';
        document.querySelector('.form-container form').appendChild(idInput);
    }
    idInput.value = id;
    document.querySelector('input[name="nome"]').value = nome;
    document.querySelector('input[name="cnpj"]').value = cnpj;
    document.querySelector('input[name="telefone"]').value = telefone;
    document.querySelector('input[name="email"]').value = email;
    document.querySelector('.form-container h2').innerText = "Editar Fornecedor";
    document.querySelector('.form-container button').innerText = "Salvar Alterações";
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
JS;
    $fornecedores = str_replace("function toggleMenu(){", $js_edit . "\nfunction toggleMenu(){", $fornecedores);
    file_put_contents('fornecedores.php', $fornecedores);
}
echo "Patch Improvements Aplicado.";
?>
