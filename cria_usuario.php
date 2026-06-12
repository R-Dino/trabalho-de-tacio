<?php
require 'db.php';
try {
    $email = 'joaquim.moura@aluno.ifsertao-pe.edu.br';
    $senha_hash = '$2y$10$KRuNlMTOple1ed2nCpQhjuBX.OLJnBUYkSLQJfMzAzZVNWhxunzCu';
    
    // Check if user exists
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        // Update password if exists
        $stmt = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE email = ?");
        $stmt->execute([$senha_hash, $email]);
        echo "Senha atualizada com sucesso!";
    } else {
        // Insert new admin user
        $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, nivel_acesso, status) VALUES (?, ?, ?, 'admin', 'ativo')");
        $stmt->execute(['Joaquim Moura', $email, $senha_hash]);
        echo "Usuario criado com sucesso!";
    }
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
?>
