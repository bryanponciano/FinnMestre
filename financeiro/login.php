<?php
/**
 * =====================================================
 * FinnMestre - Página de Login
 * =====================================================
 */
session_start();
require_once 'config/database.php';

$erro = '';

// Se já estiver logado
if (isset($_SESSION['usuario_logado']) && $_SESSION['usuario_logado'] === true) {
    require_once 'includes/assinaturas.php';
    $userId = $_SESSION['usuario_id'] ?? null;
    $isAdmin = ($userId == 1 || (isset($_SESSION['usuario_email']) && $_SESSION['usuario_email'] === ADMIN_EMAIL));
    
    // Se o usuário logado não tem acesso e está tentando abrir a tela de login (Entrar), deslogamos ele para não prender no loop.
    if (!$isAdmin && !usuarioTemAcesso($userId)) {
        session_destroy();
        $erro = 'Sua sessão anterior não possui um plano ativo. Faça login com uma conta assinante válida.';
    } else {
        header('Location: index.php');
        exit;
    }
}

// Processa o login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (empty($email) || empty($senha)) {
        $erro = 'Preencha todos os campos.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? AND ativo = 1");
            $stmt->execute([$email]);
            $usuario = $stmt->fetch();

            if ($usuario && password_verify($senha, $usuario['senha'])) {
                require_once 'includes/assinaturas.php';
                $isAdmin = ($usuario['id'] == 1 || $usuario['email'] === ADMIN_EMAIL);
                
                if (!$isAdmin && !usuarioTemAcesso($usuario['id'])) {
                    // Assinatura não ativa — NÃO excluir, só informar
                    $erro = 'Sua assinatura está inativa ou expirada. Renove seu plano na página de vendas ou entre em contato com o suporte.';
                } else {
                    session_regenerate_id(true); // Proteção contra Session Fixation
                    $_SESSION['usuario_logado'] = true;
                    $_SESSION['usuario_id'] = $usuario['id'];
                    $_SESSION['usuario_nome'] = $usuario['nome'];
                    $_SESSION['usuario_email'] = $usuario['email'];

                    // Atualizar último acesso
                    $pdo->prepare("UPDATE usuarios SET ultimo_acesso = NOW() WHERE id = ?")
                        ->execute([$usuario['id']]);

                    header('Location: index.php');
                    exit;
                }
            } else {
                $erro = 'E-mail ou senha incorretos.';
            }
        } catch (PDOException $e) {
            $erro = 'Erro ao acessar. Tente novamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="FinnMestre - Sistema de Controle Financeiro Inteligente">
    <title>Login - FinnMestre</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/svg+xml"
        href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Cdefs%3E%3ClinearGradient id='g' x1='0%25' y1='0%25' x2='100%25' y2='100%25'%3E%3Cstop offset='0%25' stop-color='%231e293b'/%3E%3Cstop offset='100%25' stop-color='%230f172a'/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width='100' height='100' rx='20' fill='url(%23g)'/%3E%3Ctext x='50' y='70' font-size='55' text-anchor='middle'%3E%F0%9F%A4%96%3C/text%3E%3C/svg%3E">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-box {
            background: var(--bg-card);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-xl);
            padding: 50px 40px;
            width: 100%;
            max-width: 440px;
            box-shadow: var(--shadow-lg);
        }

        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .login-logo {
            width: 80px;
            height: 80px;
            background: var(--fm-gradient-primary);
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin: 0 auto 20px;
            box-shadow: var(--shadow-glow-primary);
            color: white;
            position: relative;
            overflow: hidden;
        }

        .login-logo::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transform: rotate(45deg);
            animation: shimmer 3s infinite;
        }

        @keyframes shimmer {
            0% {
                transform: translateX(-100%) rotate(45deg);
            }

            100% {
                transform: translateX(100%) rotate(45deg);
            }
        }

        .login-title {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 8px;
            background: var(--fm-gradient-hero);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .login-subtitle {
            color: var(--text-secondary);
            font-size: 0.95rem;
        }

        .login-form .form-group {
            margin-bottom: 24px;
        }

        .login-form .form-group label {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .login-form .form-control {
            padding: 14px 18px;
            font-size: 1rem;
        }

        .login-form .btn-primary {
            width: 100%;
            padding: 16px;
            font-size: 1.05rem;
            margin-top: 10px;
        }

        .login-error {
            background: var(--danger-light);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: var(--danger);
            padding: 14px 18px;
            border-radius: var(--radius-md);
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.9rem;
        }

        .login-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 24px;
            border-top: 1px solid var(--border-color);
        }

        .login-footer p {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-bottom: 12px;
        }

        .login-footer a {
            color: var(--fm-primary);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition-fast);
        }

        .login-footer a:hover {
            color: var(--fm-primary-light);
            text-decoration: underline;
        }

        .login-links {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 16px;
            font-size: 0.85rem;
        }

        .login-links a {
            color: var(--text-muted);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: var(--transition-fast);
        }

        .login-links a:hover {
            color: var(--fm-primary);
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-box animate-in">
            <div class="login-header">
                <div class="login-logo">
                    <i class="fas fa-robot"></i>
                </div>
                <h1 class="login-title">FinnMestre</h1>
                <p class="login-subtitle">Faça login para acessar o sistema</p>
            </div>

            <?php if ($erro): ?>
                <div class="login-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($erro) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="login-form">
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i>
                        E-mail
                    </label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="Digite seu e-mail"
                        required autofocus value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="senha">
                        <i class="fas fa-lock"></i>
                        Senha
                    </label>
                    <input type="password" id="senha" name="senha" class="form-control" placeholder="Digite sua senha"
                        required>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i>
                    Entrar
                </button>
            </form>

            <div class="login-footer">
                <p>Ainda não tem conta?</p>
                <a href="cadastro.php">
                    <i class="fas fa-user-plus"></i> Criar conta
                </a>

                <div class="login-links">
                    <a href="suporte.php">
                        <i class="fas fa-life-ring"></i> Suporte
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>

</html>