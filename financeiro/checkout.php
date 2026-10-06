<?php
session_start();
require_once 'includes/functions.php';
require_once 'config/database.php';

// Se não comprou ainda, redireciona
if (!isset($_GET['plano'])) {
    header('Location: vendas.php');
    exit;
}

$plano = $_GET['plano'];
$planoNomes = [
    'mensal' => 'Mensal',
    'semestral' => 'Semestral',
    'anual' => 'Anual'
];
$planoNome = $planoNomes[$plano] ?? 'Premium';

// Processar formulário
$erro = '';
$sucesso = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $apelido = trim($_POST['apelido'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (empty($apelido) || empty($email) || empty($senha)) {
        $erro = 'Preencha todos os campos.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Email inválido.';
    } elseif (strlen($senha) < 6) {
        $erro = 'A senha deve ter no mínimo 6 caracteres.';
    } else {
        // Verificar se email já existe
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $usuario_existente = $stmt->fetch();

        if ($usuario_existente) {
            require_once 'includes/assinaturas.php';
            if (usuarioTemAcesso($usuario_existente['id']) || $usuario_existente['id'] == 1) {
                $erro = 'Este email já está cadastrado e com assinatura ativa. Faça login.';
            } else {
                // Conta existe mas sem assinatura ativa — verificar senha para renovação
                $stmtSenha = $pdo->prepare("SELECT senha FROM usuarios WHERE id = ?");
                $stmtSenha->execute([$usuario_existente['id']]);
                $senhaHash = $stmtSenha->fetchColumn();

                if (!password_verify($senha, $senhaHash)) {
                    $erro = 'Senha incorreta. Para renovar, digite a senha da sua conta.';
                }
            }
        }

        if (!$erro) {
            require_once 'includes/mercadopago.php';
            require_once 'includes/assinaturas.php';

            try {
                $pdo->beginTransaction();

                if ($usuario_existente) {
                    // Renovação: usar conta existente (mantém dados, perfis, transações)
                    $usuario_id = $usuario_existente['id'];
                } else {
                    // 1. Criar usuário novo
                    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, ativo) VALUES (?, ?, ?, 1)");
                    $stmt->execute([$apelido, $email, $senha_hash]);
                    $usuario_id = $pdo->lastInsertId();
                }

                // 2. Criar assinatura PENDING
                $planoStr = $_SESSION['plano_selecionado'] ?? $plano;
                $valorPlano = $_SESSION['valor_pagamento'] ?? 0;
                $formaReq = $_SESSION['metodo_pagamento'] ?? 'cartao';
                
                $stmtAssinatura = $pdo->prepare("INSERT INTO assinaturas (usuario_id, plano, status, forma_pagamento, valor) VALUES (?, ?, 'pending', ?, ?)");
                $stmtAssinatura->execute([$usuario_id, $planoStr, $formaReq, $valorPlano]);
                $assinatura_id = $pdo->lastInsertId();

                // 3. Processar API do Mercado Pago
                $dadosMp = [
                    'valor' => $valorPlano,
                    'descricao' => 'Assinatura FinnMestre - ' . ucfirst($planoStr),
                    'metodo' => mapearMetodoPagamento($formaReq),
                    'email' => $email,
                    'nome' => $apelido,
                    'cpf' => $_SESSION['cpf_comprador'] ?? null,
                    'referencia' => 'assinatura_' . $assinatura_id,
                    'webhook_url' => gerarUrlWebhook(),
                ];

                if ($formaReq === 'cartao') {
                    $dadosMp['card_token'] = $_SESSION['card_token'] ?? '';
                    $dadosMp['parcelas'] = $_SESSION['parcelas_pagamento'] ?? 1;
                }

                $retornoMp = criarPagamentoMercadoPago($dadosMp);

                if ($retornoMp['sucesso']) {
                    $payment_id = $retornoMp['payment_id'];
                    $statusMp = $retornoMp['status'];

                    if ($statusMp === 'approved') {
                        atualizarStatusAssinatura($assinatura_id, 'active', $payment_id);
                        
                        // Logar automaticamente
                        $_SESSION['usuario_logado'] = true;
                        $_SESSION['usuario_id'] = $usuario_id;
                        $_SESSION['usuario_nome'] = $apelido;
                        $_SESSION['usuario_email'] = $email;
                        $_SESSION['plano'] = $planoStr;

                        if ($usuario_existente) {
                            // Renovação: manter perfil e dados existentes
                            $_SESSION['novo_usuario'] = false;

                            $stmtPerfil = $pdo->prepare("SELECT id FROM perfis WHERE usuario_email = ? ORDER BY id ASC LIMIT 1");
                            $stmtPerfil->execute([$email]);
                            $perfilExistente = $stmtPerfil->fetchColumn();
                            if ($perfilExistente) {
                                $_SESSION['perfil_id'] = $perfilExistente;
                            }
                        } else {
                            // Novo usuário: criar perfil padrão
                            $_SESSION['novo_usuario'] = true;

                            $stmtPerfil = $pdo->prepare("INSERT INTO perfis (usuario_email, nome, tipo, cor, icone) VALUES (?, 'Pessoal', 'pessoal', '#10b981', 'fa-user')");
                            $stmtPerfil->execute([$email]);
                            $_SESSION['perfil_id'] = $pdo->lastInsertId();
                        }

                        // Ativar FinBot se contratado no Order Bump
                        if (!empty($_SESSION['com_finbot'])) {
                            $stmtFinBot = $pdo->prepare("UPDATE usuarios SET finbot_whatsapp = 1 WHERE id = ?");
                            $stmtFinBot->execute([$usuario_id]);
                        }

                        $pdo->commit();
                        header('Location: ' . ($usuario_existente ? 'home.php' : 'bem-vindo.php'));
                        exit;
                    } else {
                        // Pendente (PIX/Boleto ou Cartão em análise)
                        atualizarStatusAssinatura($assinatura_id, 'pending', $payment_id);
                        
                        if ($formaReq === 'pix') {
                            $_SESSION['pix_qr_code_base64'] = $retornoMp['pix_qr_code_base64'];
                            $_SESSION['pix_qr_code'] = $retornoMp['pix_qr_code'] ?? $retornoMp['data']['point_of_interaction']['transaction_data']['qr_code'] ?? '';
                        }
                        
                        $_SESSION['checkout_sucesso'] = true;
                        $_SESSION['checkout_plano'] = $planoStr;
                        $_SESSION['checkout_forma'] = $formaReq;
                        $_SESSION['checkout_email'] = $email;
                        
                        $pdo->commit();
                        header('Location: obrigado.php');
                        exit;
                    }
                } else {
                    $pdo->rollBack();
                    $erro = 'Erro ao processar pagamento com o Mercado Pago: ' . htmlspecialchars($retornoMp['erro'] ?? 'Verifique os dados informados.');
                }
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $erro = 'Erro interno: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalize sua Compra — FinnMestre</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --primary: #8b5cf6;
            --primary-light: #a78bfa;
            --secondary: #06d6a0;
            --accent: #06b6d4;
            --bg-dark: #0a0a0f;
            --bg-card: #12121a;
            --text-primary: #ffffff;
            --text-secondary: #a1a1aa;
            --gradient-primary: linear-gradient(135deg, #8b5cf6 0%, #06b6d4 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-dark);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .checkout-container {
            max-width: 480px;
            width: 100%;
        }

        .checkout-card {
            background: var(--bg-card);
            border: 2px solid rgba(139, 92, 246, 0.3);
            border-radius: 24px;
            padding: 48px 40px;
            position: relative;
            overflow: hidden;
        }

        .checkout-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
        }

        .logo {
            text-align: center;
            margin-bottom: 32px;
        }

        .logo i {
            font-size: 2.5rem;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .logo span {
            display: block;
            font-size: 1.5rem;
            font-weight: 800;
            margin-top: 8px;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .plan-badge {
            text-align: center;
            margin-bottom: 32px;
        }

        .plan-badge span {
            display: inline-block;
            background: rgba(6, 214, 160, 0.15);
            border: 1px solid rgba(6, 214, 160, 0.3);
            color: var(--secondary);
            padding: 8px 20px;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        h1 {
            text-align: center;
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .subtitle {
            text-align: center;
            color: var(--text-secondary);
            margin-bottom: 32px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 8px;
            color: var(--text-secondary);
        }

        .form-group input {
            width: 100%;
            padding: 14px 16px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: var(--text-primary);
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            background: rgba(139, 92, 246, 0.1);
        }

        .form-group input::placeholder {
            color: rgba(255, 255, 255, 0.3);
        }

        .error-msg {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #ef4444;
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }

        .btn-submit {
            width: 100%;
            padding: 16px 24px;
            background: var(--gradient-primary);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(139, 92, 246, 0.4);
        }

        .security-note {
            text-align: center;
            margin-top: 24px;
            color: var(--text-secondary);
            font-size: 0.85rem;
        }

        .security-note i {
            color: var(--secondary);
            margin-right: 6px;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 24px;
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.9rem;
        }

        .back-link:hover {
            color: var(--primary-light);
        }
    </style>
</head>

<body>
    <div class="checkout-container">
        <div class="checkout-card">
            <div class="logo">
                <i class="fas fa-chart-line"></i>
                <span>FinnMestre</span>
            </div>

            <div class="plan-badge">
                <span><i class="fas fa-crown"></i> Plano <?= htmlspecialchars($planoNome) ?></span>
            </div>

            <h1>Como quer ser chamado?</h1>
            <p class="subtitle">Crie sua conta para começar sua jornada</p>

            <?php if ($erro): ?>
                <div class="error-msg">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($erro) ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="apelido">Seu nome ou apelido</label>
                    <input type="text" id="apelido" name="apelido" placeholder="Ex: Bryan"
                        value="<?= htmlspecialchars($_POST['apelido'] ?? '') ?>" required autofocus>
                </div>

                <div class="form-group">
                    <label for="email">Seu melhor email</label>
                    <input type="email" id="email" name="email" placeholder="seu@email.com"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="senha">Crie uma senha</label>
                    <input type="password" id="senha" name="senha" placeholder="Mínimo 6 caracteres" required>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fas fa-rocket"></i>
                    Criar minha conta
                </button>
            </form>

            <p class="security-note">
                <i class="fas fa-lock"></i>
                Seus dados estão protegidos e seguros
            </p>

            <a href="vendas.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Voltar para os planos
            </a>
        </div>
    </div>
</body>

</html>