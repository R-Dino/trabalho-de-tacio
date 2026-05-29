<?php
require 'db.php';
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS logs_atividades (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT,
        acao VARCHAR(100),
        detalhes TEXT,
        data_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "Tabela logs_atividades criada com sucesso!";
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
?> 
