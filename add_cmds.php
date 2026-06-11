<?php
require 'db.php';
$cmds = [
    ['/alertas', 'Mostra produtos com estoque crítico', 'fa-exclamation-triangle', '#f59e0b'],
    ['/historico', '[produto] - Vê as últimas 5 movimentações', 'fa-history', '#a8c7fa'],
    ['/status', 'Visão geral da saúde do sistema', 'fa-server', '#10b981']
];

foreach ($cmds as $c) {
    $check = $pdo->prepare("SELECT id FROM ia_comandos WHERE comando = ?");
    $check->execute([$c[0]]);
    if (!$check->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO ia_comandos (comando, descricao, icone, cor) VALUES (?, ?, ?, ?)");
        $stmt->execute($c);
        echo "Adicionado: {$c[0]}\n";
    } else {
        echo "Já existe: {$c[0]}\n";
    }
}
?>
