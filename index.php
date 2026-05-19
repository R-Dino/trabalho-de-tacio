<?php
session_start();
require 'db.php';

// Se já estiver logado, redireciona para a tela inicial
if (isset($_SESSION['usuario_id'])) {
    header("Location: telainicial.php");
    exit;
}

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['acao']) && $_POST['acao'] === 'login') {
        $email = trim($_POST['email']);
        $senha = $_POST['senha'];

        if (!empty($email) && !empty($senha)) {
            $stmt = $pdo->prepare("SELECT id, nome, senha FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            $usuario = $stmt->fetch();

            if ($usuario && password_verify($senha, $usuario['senha'])) {
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nome'] = $usuario['nome'];
                header("Location: telainicial.php");
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
    <title>Clínica Fision+ | Login e Cadastro</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #00796b;
            --secondary-color: #004d40;
            --bg-gradient: linear-gradient(135deg, #e0f2f1 0%, #b2ebf2 100%);
            --white: #ffffff;
            --shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', system-ui, sans-serif; }

        body {
            background: var(--bg-gradient);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 15px;
        }

        .auth-card {
            background-color: var(--white);
            width: 100%;
            max-width: 450px;
            border-radius: 16px;
            box-shadow: var(--shadow);
            overflow: hidden;
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        header {
            background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)),
                url('imagem.png');
            background-size: cover;
            background-position: center;
             background-repeat: no-repeat;

            color: var(--white);
            padding: 1.5rem 1rem;
            text-align: center;
        }

        .clinic-logo {
            width: 150px;
            height: 150px;
            background: rgba(255,255,255,0.2);
            margin: 0 auto 10px;
            border-radius: 50%;
            border: 2px solid white;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }
        .clinic-logo img { width: 100%; height: 100%; object-fit: cover; }

        header h1 { font-size: 1.8rem; margin-bottom: 5px; }
        header p { font-size: 0.9rem; opacity: 0.9; }

        .tabs { display: flex; background: #f8f9fa; border-bottom: 1px solid #eee; }

        .tab-btn {
            flex: 1; padding: 15px; border: none; background: none;
            cursor: pointer; font-weight: 600; color: #666; transition: var(--transition);
        }

        .tab-btn.active {
            color: var(--primary-color); background: var(--white);
            border-bottom: 3px solid var(--primary-color);
        }

        .form-container { padding: 2rem; }
        form { display: grid; gap: 1.2rem; }
        .field { display: flex; flex-direction: column; gap: 5px; }
        label { font-size: 0.85rem; font-weight: 700; color: #444; }

        input {
            padding: 12px; border: 1.5px solid #ddd; border-radius: 8px;
            font-size: 1rem; transition: var(--transition);
        }

        input:focus {
            outline: none; border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 121, 107, 0.1);
        }

        .form-utils {
            display: flex; justify-content: space-between;
            align-items: center; font-size: 0.8rem;
        }

        .btn-submit {
            background-color: var(--primary-color); color: var(--white);
            padding: 14px; border: none; border-radius: 8px;
            font-weight: bold; font-size: 1rem; cursor: pointer; transition: var(--transition);
        }

        .divider {
            text-align: center; margin: 1.5rem 0; position: relative;
            font-size: 0.8rem; color: #aaa;
        }
        .divider::before, .divider::after {
            content: ""; position: absolute; top: 50%; width: 35%; border-bottom: 1px solid #eee;
        }
        .divider::before { left: 0; }
        .divider::after { right: 0; }

        .hidden { display: none; }

        .alert {
            padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 0.9rem; text-align: center;
        }
        .alert-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }

        @media (max-width: 480px) {
            .form-container { padding: 1.2rem; }
            .form-utils { flex-direction: column; gap: 10px; align-items: flex-start; }
            header h1 { font-size: 1.5rem; }
        }
    </style>
</head>
<body>

<main class="auth-card">
    <header>
        <div class="clinic-logo">
            <img src="imagem.png" alt="Logo Clínica">
        </div>
        <h1>Almoxarifado</h1>
        <p>Sistema do Almoxarifado</p>
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
                <label><input type="checkbox"> Lembrar acesso</label>
                <a href="#" style="color: var(--primary-color); text-decoration: none;">Esqueceu a senha?</a>
            </div>

            <button type="submit" class="btn-submit">Acessar</button>
        </form>

        <form id="form-register" class="hidden" method="POST" action="index.php">
            <input type="hidden" name="acao" value="register">
            <div class="field">
                <label for="r-name">Nome Completo</label>
                <input type="text" id="r-name" name="nome" placeholder="Como deseja ser chamado?" required>
            </div>
            <div class="field">
                <label for="r-email">E-mail</label>
                <input type="email" id="r-email" name="email" placeholder="seu@email.com" required>
            </div>
            <div class="field">
                <label for="r-pass">Crie uma Senha</label>
                <input type="password" id="r-pass" name="senha" minlength="6" placeholder="Mín. 6 caracteres" required>
            </div>
            <button type="submit" class="btn-submit">FINALIZAR CADASTRO</button>
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

    // Se houve sucesso no cadastro ou usuário escolheu registrar, pode manter a aba de registro ativa se necessário
    // Aqui usamos uma heurística simples: se foi acao=register e houve erro, reabrir a aba.
    <?php if (isset($_POST['acao']) && $_POST['acao'] === 'register' && !empty($erro)): ?>
        switchTab('register');
    <?php endif; ?>
</script>
</body>
</html>
