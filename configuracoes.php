<?php
session_start();
require 'db.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

// Proteção para Admin
if (!isset($_SESSION['nivel_acesso']) || $_SESSION['nivel_acesso'] !== 'admin') {
    die("Acesso negado. Apenas administradores podem acessar as configurações.");
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações Globais - ALMOX</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* BASE & RESET */
        *{ margin:0; padding:0; box-sizing:border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body{ background:#f1f5f9; color: #1e293b; transition: background 0.3s; }

        /* MODO ESCURO */
        body.dark-mode { background: #0f172a; color: #f1f5f9; }
        body.dark-mode .config-section, body.dark-mode .topbar { background: #1e293b; border: none; }
        body.dark-mode .input-group input, body.dark-mode .input-group select { background: #334155; border-color: #475569; color: white; }
        body.dark-mode .section-title { border-bottom-color: #334155; }
        body.dark-mode .setting-info p { color: #94a3b8; }

        /* MENU LATERAL */
        .menu-toggle{ position:fixed; top:15px; left:15px; z-index:1000; border:none; background:#2563eb; color:white; width:45px; height:45px; border-radius:8px; cursor:pointer; font-size:20px; }
        .sidebar{ width:250px; height:100vh; background:#0f172a; color:white; padding:20px; position:fixed; left:-250px; top:0; transition:0.4s; z-index:999; }
        .sidebar.active{ left:0; }
        .logo{ text-align:center; margin-bottom:40px; }
        .logo h2{ color:#38bdf8; }
        .menu{ list-style:none; }
        .menu li{ margin:15px 0; }
        .menu a{ color:white; text-decoration:none; display:flex; align-items:center; gap:10px; padding:12px; border-radius:8px; transition:0.3s; }
        .menu a:hover{ background:#1e293b; }

        /* CONTEÚDO PRINCIPAL */
        .main{ width:100%; padding:20px; transition: 0.4s; }
        .topbar{ background:white; padding:15px 20px; border-radius:10px; display:flex; justify-content:space-between; align-items:center; box-shadow:0 2px 5px rgba(0,0,0,0.1); margin-top:60px; }

        /* SEÇÕES DE CONFIGURAÇÃO */
        .config-section { background: white; margin-top: 25px; padding: 25px; border-radius: 12px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .section-title { display: flex; align-items: center; gap: 10px; color: #2563eb; margin-bottom: 20px; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; }
        .input-group { display: flex; flex-direction: column; gap: 8px; }
        .input-group label { font-weight: bold; font-size: 13px; color: #64748b; }
        .input-group input, .input-group select { padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; background: #f8fafc; outline: none; }

        /* CONFIGURAÇÕES DE LINHA */
        .setting-row { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; border-bottom: 1px solid #f1f5f9; }
        .setting-info h4 { font-size: 16px; }
        .setting-info p { font-size: 13px; color: #64748b; }

        /* SWITCH & RANGE */
        .switch { position: relative; display: inline-block; width: 44px; height: 22px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 34px; }
        .slider:before { position: absolute; content: ""; height: 16px; width: 16px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: #2563eb; }
        input:checked + .slider:before { transform: translateX(22px); }

        /* BOTÃO SALVAR */
        .btn-save-fixed { position: fixed; bottom: 20px; right: 20px; background: #10b981; color: white; border: none; padding: 15px 40px; border-radius: 50px; font-weight: bold; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4); cursor: pointer; display: flex; align-items: center; gap: 10px; z-index: 1001; }
        .badge-pro { background: #fbbf24; color: #000; padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: bold; }
    </style>
    <link rel="stylesheet" href="premium.css">
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

    <button class="menu-toggle" onclick="toggleMenu()">
        <i class="fa fa-bars"></i>
    </button>

    <div class="sidebar" id="sidebar">
        <div class="logo"><h2>ALMOX</h2></div>
        <ul class="menu">
            <li><a href="telainicial.php"><i class="fa fa-house"></i> Início</a></li>
            <li><a href="dashboard.php"><i class="fa fa-chart-line"></i> Dashboard</a></li>
            <li><a href="produtos.php"><i class="fa fa-box"></i> Produtos</a></li>
            <li><a href="estoque.php"><i class="fa fa-warehouse"></i> Estoque</a></li>
            <li><a href="fornecedores.php"><i class="fa fa-truck"></i> Fornecedores</a></li>
            <li><a href="usuarios.php"><i class="fa fa-users"></i> Usuários</a></li>
            <li><a href="relatorios.php"><i class="fa fa-file"></i> Relatórios</a></li>
            <li><a href="configuracoes.php"><i class="fa fa-gear"></i> Configurações</a></li>
        </ul>
    </div>

    <div class="main" id="main-wrapper">
        <div class="topbar">
            <h1>Configurações do Sistema</h1>
            <div>
                <span class="badge-pro">VERSÃO PHP/MariaDB</span>
            </div>
        </div>

        <div class="config-section">
            <div class="section-title"><i class="fa fa-user-circle"></i> <h3>Meu Perfil</h3></div>
            <div class="form-grid">
                <div class="input-group"><label>Nome de Exibição</label><input type="text" value="<?= htmlspecialchars($_SESSION['usuario_nome']) ?>" readonly></div>
                <div class="input-group"><label>Sessão ID</label><input type="text" value="<?= $_SESSION['usuario_id'] ?>" readonly></div>
            </div>
        </div>

        <div class="config-section">
            <div class="section-title"><i class="fa fa-paint-roller"></i> <h3>Personalização de Interface</h3></div>
            <div class="setting-row">
                <div class="setting-info"><h4>Modo Escuro</h4><p>Reduz o cansaço visual em ambientes de baixa luminosidade.</p></div>
                <label class="switch"><input type="checkbox" id="darkModeToggle" onclick="toggleDarkMode()"><span class="slider"></span></label>
            </div>
            <div class="setting-row">
                <div class="setting-info"><h4>Ajuste de Brilho</h4><p>Controla a opacidade da interface.</p></div>
                <input type="range" id="brightnessRange" min="0.5" max="1" step="0.01" value="1" oninput="adjustBrightness()">
            </div>
        </div>

        <div class="config-section">
            <div class="section-title"><i class="fa fa-shield-halved"></i> <h3>Segurança e Acesso</h3></div>
            <div class="form-grid">
                <div class="input-group">
                    <label>Tempo de Logoff Automático</label>
                    <select><option>15 minutos</option><option selected>30 minutos</option><option>1 hora</option></select>
                </div>
            </div>
        </div>
    </div>

    <button class="btn-save-fixed" onclick="alert('Configurações da interface atualizadas e salvas com sucesso!')">
        <i class="fa fa-save"></i> SALVAR ALTERAÇÕES
    </button>

    <script>
        function toggleMenu(){
            document.getElementById("sidebar").classList.toggle("active");
        }

        function toggleDarkMode() {
            document.body.classList.toggle("dark-mode");
            localStorage.setItem("darkMode", document.body.classList.contains("dark-mode"));
        }

        function adjustBrightness() {
            let val = document.getElementById("brightnessRange").value;
            document.getElementById("main-wrapper").style.opacity = val;
        }

        window.onload = () => {
            if (localStorage.getItem("darkMode") === "true") {
                document.body.classList.add("dark-mode");
                document.getElementById("darkModeToggle").checked = true;
            }
        };
    </script>
</body>
</html>
