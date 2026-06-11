<?php
session_start();
$_SESSION['usuario_id'] = 1;
$_SESSION['usuario_nome'] = 'Antigravity Test';
header("Location: ia.php");
exit;
