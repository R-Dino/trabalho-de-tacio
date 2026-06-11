<?php
require 'db.php';
$stmt = $pdo->query("SELECT comando as cmd, descricao as `desc`, icone as icon, cor FROM ia_comandos ORDER BY comando ASC");
$res = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($res, JSON_UNESCAPED_UNICODE);
?>
