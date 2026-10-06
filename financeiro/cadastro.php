<?php
/**
 * =====================================================
 * FinnMestre - Página de Cadastro
 * =====================================================
 */
session_start();
require_once 'config/database.php';

// Se já estiver logado, redireciona para o dashboard
if (isset($_SESSION['usuario_logado']) && $_SESSION['usuario_logado'] === true) {
    header('Location: index.php');
    exit;
}

$erro = '';
$sucesso = '';

// Processa o cadastro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmarSenha = $_POST['confirmar_senha'] ?? '';

    // Validações
    if (empty($nome) || empty($email) || empty($senha)) {
        $erro = 'Preencha todos os campos obrigatórios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Digite um e-mail válido.';
    } elseif (strlen($senha) < 6) {
        $erro = 'A senha deve ter pelo menos 6 caracteres.';
    } elseif ($senha !== $confirmarSenha) {
        $erro = 'As senhas não conferem.';
    } else {
        try {
            // Verificar se e-mail já existe
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);

            if ($stmt->fetch()) {
                $erro = 'Este e-mail já está cadastrado. <a href="login.php">Faça login</a>';
            } else {
                // Criar usuário
                $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)");
                $stmt->execute([$nome, $email, $senhaHash]);

                $sucesso = 'Conta criada com sucesso! Você já pode fazer login.';
            }
        } catch (PDOException $e) {
            $erro = 'Erro ao criar conta. Tente novamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Crie sua conta no FinnMestre - Sistema de Controle Financeiro Inteligente">
    <title>Criar Conta - FinnMestre</title>
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
            max-width: 480px;
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
            margin-bottom: 20px;
        }

        .login-form .form-group label {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .login-form .form-group label .required {
            color: var(--danger);
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

        .login-error a {
            color: var(--danger);
            font-weight: 600;
        }

        .login-success {
            background: var(--success-light);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: var(--success);
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

        .features-list {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid var(--border-color);
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .feature-item i {
            color: var(--fm-primary);
        }

        .password-hint {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 6px;
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
                <h1 class="login-title">Criar Conta</h1>
                <p class="login-subtitle">Comece a controlar suas finanças agora</p>
            </div>

            <?php if ($erro): ?>
                <div class="login-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>
                        <?= $erro ?>
                    </span>
                </div>
            <?php endif; ?>

            <?php if ($sucesso): ?>
                <div class="login-success">
                    <i class="fas fa-check-circle"></i>
                    <span>
                        <?= htmlspecialchars($sucesso) ?>
                    </span>
                </div>
                <a href="login.php" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-sign-in-alt"></i> Ir para Login
                </a>
            <?php else: ?>
                <form method="POST" class="login-form">
                    <div class="form-group">
                        <label for="nome">
                            <i class="fas fa-user"></i>
                            Nome <span class="required">*</span>
                        </label>
                        <input type="text" id="nome" name="nome" class="form-control" placeholder="Seu nome completo"
                            required value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="email">
                            <i class="fas fa-envelope"></i>
                            E-mail <span class="required">*</span>
                        </label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="seu@email.com"
                            required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="senha">
                            <i class="fas fa-lock"></i>
                            Senha <span class="required">*</span>
                        </label>
                        <input type="password" id="senha" name="senha" class="form-control"
                            placeholder="Mínimo 6 caracteres" required minlength="6">
                        <p class="password-hint">A senha deve ter pelo menos 6 caracteres</p>
                    </div>

                    <div class="form-group">
                        <label for="confirmar_senha">
                            <i class="fas fa-lock"></i>
                            Confirmar Senha <span class="required">*</span>
                        </label>
                        <input type="password" id="confirmar_senha" name="confirmar_senha" class="form-control"
                            placeholder="Digite a senha novamente" required minlength="6">
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i>
                        Criar Minha Conta
                    </button>
                </form>

                <div class="features-list">
                    <div class="feature-item">
                        <i class="fas fa-check"></i> Fácil de usar
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-check"></i> Seguro
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-check"></i> Relatórios
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-check"></i> Metas
                    </div>
                </div>
            <?php endif; ?>

            <div class="login-footer">
                <p>Já tem uma conta?</p>
                <a href="login.php">
                    <i class="fas fa-sign-in-alt"></i> Fazer login
                </a>
            </div>
        </div>
    </div>
</body>

</html>