<?php
require 'db.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS ia_comandos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        comando VARCHAR(50) NOT NULL UNIQUE,
        descricao VARCHAR(255) NOT NULL,
        icone VARCHAR(50) NOT NULL,
        cor VARCHAR(20) NOT NULL
    )");

    $comandos = [
        ['/ajuda', 'Ver todos os comandos disponíveis', 'fa-question-circle', '#a8c7fa'],
        ['/adicionar', '[qtd] [produto] - Dá entrada no estoque', 'fa-plus-circle', '#10b981'],
        ['/remover', '[qtd] [produto] - Dá saída no estoque', 'fa-minus-circle', '#ef4444'],
        ['/banir', '[usuario] - Bane um usuário do sistema', 'fa-gavel', '#ef4444'],
        ['/desbanir', '[usuario] - Retira o ban', 'fa-unlock', '#10b981'],
        ['/promover', '[usuario] - Dá cargo de admin', 'fa-star', '#f59e0b'],
        ['/rebaixar', '[usuario] - Remove cargo de admin', 'fa-arrow-down', '#ef4444'],
        ['/usuarios', 'Lista todos os usuários', 'fa-users', '#3b82f6'],
        ['/deletar', '[produto] - Exclui o produto', 'fa-trash', '#ef4444'],
        ['/valor', 'Mostra o valor total em estoque', 'fa-dollar-sign', '#10b981'],
        ['/vendidos', 'Total de itens já saíram', 'fa-chart-line', '#3b82f6'],
        ['/estoque', '[produto] - Verifica a quantidade', 'fa-box', '#f59e0b'],
        ['/entradas', 'Últimas 5 entradas', 'fa-arrow-right-to-bracket', '#10b981'],
        ['/saidas', 'Últimas 5 saídas', 'fa-arrow-right-from-bracket', '#ef4444'],
        ['/fornecedores', 'Lista de fornecedores', 'fa-truck', '#a8c7fa'],
        ['/comprar', '[produto] - Pesquisa no Mercado Livre', 'fa-shopping-cart', '#f59e0b'],
        ['/pesquisar', '[termo] - Pesquisa na Wikipedia', 'fa-globe', '#a8c7fa'],
        ['/limpar', 'Limpa o histórico do terminal atual', 'fa-eraser', '#94a3b8'],
        ['/dashboard', 'Abre o Painel Principal', 'fa-chart-line', '#10b981'],
        ['/produtos', 'Abre a página de Produtos', 'fa-box', '#3b82f6']
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO ia_comandos (comando, descricao, icone, cor) VALUES (?, ?, ?, ?)");
    foreach ($comandos as $c) {
        $stmt->execute($c);
    }

    echo "Tabela ia_comandos criada e populada com sucesso.\n";
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
?>
