<?php
require 'db.php';
try {
    $pdo->exec("ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS status ENUM('ativo', 'banido') DEFAULT 'ativo'");
    echo "Coluna 'status' adicionada com sucesso ou já existia.<br>";
} catch (PDOException $e) {
    echo "Erro ao atualizar banco: " . $e->getMessage() . "<br>";
}
?>
