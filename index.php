<?php
session_start();
require 'db.php';

// Se já estiver logado, redireciona para a tela inicial
if (isset($_SESSION['usuario_id'])) {
    header("Location: dashboard.php");
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'login') {
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];

    if (!empty($email) && !empty($senha)) {
        $stmt = $pdo->prepare("SELECT id, nome, senha, nivel_acesso, status FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($senha, $usuario['senha'])) {
            if ($usuario['status'] === 'banido') {
                $erro = "Sua conta foi banida. Entre em contato com o administrador.";
            } else {
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nome'] = $usuario['nome'];
                $_SESSION['nivel_acesso'] = $usuario['nivel_acesso'];
                
                // Atualizar última atividade
                $_SESSION['ultima_atividade'] = time();
                
                header("Location: dashboard.php");
                exit;
            }
        } else {
            $erro = "E-mail ou senha incorretos.";
        }
    } else {
        $erro = "Preencha todos os campos.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ALMOX | Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="premium.css">
    
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

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }

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

        .form-container { padding: 2.5rem 2rem; }
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

        .alert {
            padding: 12px; border-radius: 8px; margin-bottom: 15px; font-size: 0.9rem; text-align: center; font-weight: 500;
        }
        .alert-error { background-color: #fef2f2; color: #ef4444; border: 1px solid #fecaca; }

        /* MODO ESCURO GLOBAL */
        body.dark-mode { background: #0f172a; color: #f1f5f9; }
        body.dark-mode .auth-card { background: #1e293b; box-shadow: none; }
        body.dark-mode header { background: #0f172a; border-bottom: 1px solid #334155; }
        body.dark-mode .field label { color: #cbd5e1; }
        body.dark-mode .form-utils { color: #94a3b8; }
        body.dark-mode .alert-error { background: #450a0a; border-color: #7f1d1d; color: #fca5a5; }
        body.dark-mode input { background: #0f172a !important; border: 1px solid #334155 !important; color: white !important; }
        body.dark-mode input:focus { border-color: #38bdf8 !important; box-shadow: 0 0 0 2px rgba(56, 189, 248, 0.2) !important; }
    </style>
</head>
<body>

<main class="auth-card">
    <header>
        <div class="logo">
            <h2>ALMOX</h2>
        </div>
        <p>Acesso Restrito ao Sistema</p>
    </header>

    <div class="form-container">
        <?php if (!empty($erro)): ?>
            <div class="alert alert-error"><i class="fa fa-exclamation-circle"></i> <?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>
        
        <?php if (isset($_GET['timeout'])): ?>
            <div class="alert alert-error"><i class="fa fa-clock"></i> Sua sessão expirou por inatividade.</div>
        <?php endif; ?>

        <form method="POST" action="index.php">
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

            <button type="submit" class="btn-submit"><i class="fa fa-sign-in-alt"></i> Entrar no Sistema</button>
        </form>
    </div>
</main>

<script>
    window.addEventListener('DOMContentLoaded', () => {
        if (localStorage.getItem("darkMode") === "true") {
            document.body.classList.add("dark-mode");
        }
    });
</script>
</body>
</html>
