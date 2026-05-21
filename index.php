<?php
session_start();
require 'db.php';

// Se já estiver logado, redireciona para a tela inicial
if (isset($_SESSION['usuario_id'])) {
    header("Location: dashboard.php");
    exit;
}

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['acao']) && $_POST['acao'] === 'login') {
        $email = trim($_POST['email']);
        $senha = $_POST['senha'];

        if (!empty($email) && !empty($senha)) {
            $stmt = $pdo->prepare("SELECT id, nome, senha, nivel_acesso FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            $usuario = $stmt->fetch();

            if ($usuario && password_verify($senha, $usuario['senha'])) {
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nome'] = $usuario['nome'];
<<<<<<< HEAD
                header("Location: dashboard.php");
=======
                $_SESSION['nivel_acesso'] = $usuario['nivel_acesso'];
                header("Location: telainicial.php");
>>>>>>> 81a7f9b176d2662fcd675e03b2180fbfeff8468b
                exit;
            } else {
                $erro = "E-mail ou senha incorretos.";
            }
        } else {
            $erro = "Preencha todos os campos.";
        }
    } elseif (isset($_POST['acao']) && $_POST['acao'] === 'register') {
        $nome = trim($_POST['nome']);
        $email = trim($_POST['email']);
        $senha = $_POST['senha'];

        if (!empty($nome) && !empty($email) && !empty($senha)) {
            // Verifica se email já existe
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $erro = "E-mail já cadastrado.";
            } else {
                $hash = password_hash($senha, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)");
                if ($stmt->execute([$nome, $email, $hash])) {
                    $sucesso = "Cadastro realizado! Agora faça login.";
                } else {
                    $erro = "Erro ao cadastrar usuário.";
                }
            }
        } else {
            $erro = "Preencha todos os campos.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ALMOX | Login e Cadastro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-hover: #1d4ed8;
            --dark-color: #0f172a;
            --accent-color: #38bdf8;
            --bg-color: #f1f5f9;
            --white: #ffffff;
            --text-color: #1e293b;
            --text-muted: #64748b;
            --shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            --transition: all 0.3s ease;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }

        body {
            background: var(--bg-color);
            color: var(--text-color);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 15px;
        }

        .auth-card {
            background-color: var(--white);
            width: 100%;
            max-width: 420px;
            border-radius: 12px;
            box-shadow: var(--shadow);
            overflow: hidden;
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        header {
            background: var(--dark-color);
            color: var(--white);
            padding: 2.5rem 1.5rem;
            text-align: center;
        }

        .logo h2 {
            color: var(--accent-color);
            font-size: 2.5rem;
            letter-spacing: 2px;
            margin-bottom: 5px;
        }

        header p {
            font-size: 0.95rem;
            color: #cbd5e1;
        }

        .tabs { display: flex; background: #f8fafc; border-bottom: 1px solid #e2e8f0; }

        .tab-btn {
            flex: 1; padding: 15px; border: none; background: none;
            cursor: pointer; font-weight: 600; color: var(--text-muted); transition: var(--transition);
            font-size: 0.9rem;
        }

        .tab-btn:hover {
            color: var(--primary-color);
        }

        .tab-btn.active {
            color: var(--primary-color); background: var(--white);
            border-bottom: 3px solid var(--primary-color);
        }

        .form-container { padding: 2rem; }
        form { display: grid; gap: 1.2rem; }
        .field { display: flex; flex-direction: column; gap: 6px; }
        label { font-size: 0.85rem; font-weight: bold; color: var(--text-muted); }

        input {
            padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px;
            font-size: 1rem; color: var(--text-color); transition: var(--transition);
            background: #f8fafc;
        }

        input:focus {
            outline: none; border-color: var(--primary-color); background: var(--white);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .form-utils {
            display: flex; justify-content: space-between;
            align-items: center; font-size: 0.85rem; color: var(--text-muted);
        }

        .btn-submit {
            background-color: var(--primary-color); color: var(--white);
            padding: 14px; border: none; border-radius: 8px;
            font-weight: bold; font-size: 1rem; cursor: pointer; transition: var(--transition);
            margin-top: 5px;
        }

        .btn-submit:hover {
            background-color: var(--primary-hover);
            transform: translateY(-1px);
        }

        .hidden { display: none; }

        .alert {
            padding: 12px; border-radius: 8px; margin-bottom: 15px; font-size: 0.9rem; text-align: center; font-weight: 500;
        }
        .alert-error { background-color: #fef2f2; color: #ef4444; border: 1px solid #fecaca; }
        .alert-success { background-color: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }

    
        /* MODO ESCURO GLOBAL */
        body.dark-mode { background: #0f172a; color: #f1f5f9; }
        body.dark-mode .topbar, body.dark-mode .card, body.dark-mode .table-container, body.dark-mode .form-container, body.dark-mode .report-card, body.dark-mode .chart-box, body.dark-mode .activity-box { background: #1e293b; box-shadow: none; color: #f1f5f9; }
        body.dark-mode .topbar h1, body.dark-mode .form-container h2, body.dark-mode .table-container h2 { color: #f1f5f9; }
        body.dark-mode .card h3 { color: #94a3b8; }
        body.dark-mode input, body.dark-mode select { background: #334155 !important; border: 1px solid #475569 !important; color: white !important; }
        body.dark-mode table th { background: #0f172a !important; color: #f1f5f9; border-bottom: 1px solid #334155;}
        body.dark-mode table td, body.dark-mode tr { border-bottom: 1px solid #334155 !important; color: #cbd5e1; }
        body.dark-mode .activity-item { border-bottom: 1px solid #334155; }
        body.dark-mode .activity-item p { color: #94a3b8; }
        
        /* Ajustes extras para Tela de Login */
        body.dark-mode .auth-card { background: #1e293b; box-shadow: none; }
        body.dark-mode header { background: #0f172a; border-bottom: 1px solid #334155; }
        body.dark-mode .tabs { background: #1e293b; border-bottom: 1px solid #334155; }
        body.dark-mode .tab-btn { color: #94a3b8; }
        body.dark-mode .tab-btn.active { background: #1e293b; color: #38bdf8; border-bottom: 3px solid #38bdf8; }
        body.dark-mode .field label { color: #cbd5e1; }
        body.dark-mode .form-utils { color: #94a3b8; }
        body.dark-mode .alert-error { background: #450a0a; border-color: #7f1d1d; color: #fca5a5; }
        body.dark-mode .alert-success { background: #052e16; border-color: #14532d; color: #86efac; }
</style>
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

<main class="auth-card">
    <header>
        <div class="logo">
            <h2>ALMOX</h2>
        </div>
        <p>Sistema de Gerenciamento</p>
    </header>

    <nav class="tabs">
        <button class="tab-btn active" id="tab-login" onclick="switchTab('login')">LOGIN</button>
        <button class="tab-btn" id="tab-register" onclick="switchTab('register')">CADASTRO</button>
    </nav>

    <div class="form-container">
        <?php if (!empty($erro)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>
        <?php if (!empty($sucesso)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($sucesso) ?></div>
        <?php endif; ?>

        <form id="form-login" method="POST" action="index.php">
            <input type="hidden" name="acao" value="login">
            <div class="field">
                <label for="l-email">Login (E-mail)</label>
                <input type="email" id="l-email" name="email" placeholder="nome@exemplo.com" required>
            </div>
            <div class="field">
                <label for="l-pass">Senha</label>
                <input type="password" id="l-pass" name="senha" placeholder="••••••••" required>
            </div>
            
            <div class="form-utils">
                <label style="font-weight: normal; cursor: pointer;"><input type="checkbox"> Lembrar de mim</label>
            </div>

            <button type="submit" class="btn-submit">Entrar no Sistema</button>
        </form>

        <form id="form-register" class="hidden" method="POST" action="index.php">
            <input type="hidden" name="acao" value="register">
            <div class="field">
                <label for="r-name">Nome Completo</label>
                <input type="text" id="r-name" name="nome" placeholder="Seu nome" required>
            </div>
            <div class="field">
                <label for="r-email">E-mail</label>
                <input type="email" id="r-email" name="email" placeholder="seu@email.com" required>
            </div>
            <div class="field">
                <label for="r-pass">Crie uma Senha</label>
                <input type="password" id="r-pass" name="senha" minlength="6" placeholder="Mín. 6 caracteres" required>
            </div>
            <button type="submit" class="btn-submit">Criar Conta</button>
        </form>
    </div>
</main>

<script>
    function switchTab(mode) {
        const isLogin = mode === 'login';
        document.getElementById('tab-login').classList.toggle('active', isLogin);
        document.getElementById('tab-register').classList.toggle('active', !isLogin);
        document.getElementById('form-login').classList.toggle('hidden', !isLogin);
        document.getElementById('form-register').classList.toggle('hidden', isLogin);
    }

    <?php if (isset($_POST['acao']) && $_POST['acao'] === 'register' && !empty($erro)): ?>
        switchTab('register');
    <?php endif; ?>

        window.addEventListener('DOMContentLoaded', () => {
            if (localStorage.getItem("darkMode") === "true") {
                document.body.classList.add("dark-mode");
            }
        });
</script>
</body>
</html>
