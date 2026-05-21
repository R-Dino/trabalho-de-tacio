<?php
$host = 'localhost';
$dbname = 'almoxarifado';
$user = 'root';
$pass = ''; // Por padrão, no XAMPP, a senha do root é vazia.

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro de conexão com o banco de dados: " . $e->getMessage());
}

// Lógica Global de Logoff por Inatividade (30 minutos)
if (isset($_SESSION['usuario_id'])) {
    if (isset($_SESSION['ultima_atividade']) && (time() - $_SESSION['ultima_atividade'] > 1800)) {
        session_unset();
        session_destroy();
        header("Location: index.php?timeout=1");
        exit;
    }
    $_SESSION['ultima_atividade'] = time();
}
?>
