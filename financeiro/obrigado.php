<?php
/**
 * =====================================================
 * FinnMestre - Thank You Page
 * Página de confirmação após checkout
 * =====================================================
 */
session_start();

$sucesso = $_SESSION['checkout_sucesso'] ?? false;
$plano = $_SESSION['checkout_plano'] ?? 'mensal';
$forma = $_SESSION['checkout_forma'] ?? 'pix';
$email = $_SESSION['checkout_email'] ?? '';

// Limpar sessão de checkout
unset($_SESSION['checkout_sucesso'], $_SESSION['checkout_plano'], $_SESSION['checkout_forma'], $_SESSION['checkout_email']);

$planos = [
    'mensal' => 'Mensal',
    'semestral' => 'Semestral',
    'anual' => 'Anual'
];

$isPending = in_array($forma, ['boleto', 'pix']);
$pixQrCode = $_SESSION['pix_qr_code_base64'] ?? '';
$pixCode = $_SESSION['pix_qr_code'] ?? '';

// Apagar códigos da sessão após pegar
unset($_SESSION['pix_qr_code_base64'], $_SESSION['pix_qr_code']);
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Obrigado! — FinnMestre</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/public.css">
</head>

<body>
    <!-- Navigation -->
    <nav class="public-nav">
        <div class="nav-container">
            <a href="home.php" class="nav-logo">
                <span class="logo-icon">📊</span>
                <span class="logo-text">FinnMestre</span>
            </a>
        </div>
    </nav>

    <div class="thankyou-page">
        <?php if ($isPending): ?>
            <!-- Boleto: Aguardando pagamento -->
            <div class="thankyou-card">
                <div class="thankyou-icon pending">
                    <i class="fas fa-clock"></i>
                </div>
                <h1 class="thankyou-title">Quase lá!</h1>
                <p class="thankyou-message">
                    Seu cadastro foi realizado com sucesso. Estamos aguardando a confirmação
                    do pagamento do <?= strtoupper($forma) ?>.
                </p>

                <?php if ($forma === 'pix'): ?>
                <!-- BLOCO PIX -->
                <div style="background: var(--bg-glass); border: 1px solid var(--border-color); padding: 24px; border-radius: var(--radius-lg); margin-bottom: 32px; text-align: center;">
                    <h3 style="margin-bottom: 16px; color: var(--secondary);">Escaneie o QR Code abaixo</h3>
                    
                    <?php if ($pixQrCode): ?>
                        <div style="background: white; padding: 16px; display: inline-block; border-radius: 12px; margin-bottom: 16px;">
                            <img src="data:image/png;base64,<?= $pixQrCode ?>" alt="QR Code PIX" style="width: 200px; height: 200px;">
                        </div>
                    <?php else: ?>
                        <div style="width: 200px; height: 200px; background: rgba(255,255,255,0.1); margin: 0 auto 16px; display: flex; align-items: center; justify-content: center; border-radius: 12px;">
                            <i class="fas fa-qrcode" style="font-size: 3rem; color: var(--text-muted);"></i>
                        </div>
                    <?php endif; ?>

                    <p style="color: var(--text-secondary); margin-bottom: 8px; font-size: 0.9rem;">Ou copie o código PIX copia e cola:</p>
                    <div style="display: flex; gap: 8px; justify-content: center; align-items: stretch; margin-bottom: 16px;">
                        <input type="text" id="pixCodeInput" value="<?= htmlspecialchars($pixCode) ?>" readonly 
                            style="background: rgba(0,0,0,0.2); border: 1px solid var(--border-color); color: var(--text-primary); padding: 10px 16px; border-radius: 8px; font-family: monospace; font-size: 0.8rem; width: 100%; max-width: 300px;">
                        <button onclick="copiarPixFinal()" class="btn-access" style="padding: 10px 20px; font-size: 0.9rem; border-radius: 8px; min-width: auto; margin: 0;">
                            <i class="fas fa-copy"></i> Copiar
                        </button>
                    </div>
                </div>

                <script>
                function copiarPixFinal() {
                    const input = document.getElementById('pixCodeInput');
                    input.select();
                    document.execCommand('copy');
                    alert('Código PIX copiado!');
                }
                </script>
                <?php else: ?>
                <!-- BLOCO BOLETO -->
                <div
                    style="background: rgba(245, 158, 11, 0.1); border: 1px solid var(--warning); padding: 20px; border-radius: var(--radius-md); margin-bottom: 32px; text-align: left;">
                    <div style="display: flex; align-items: center; gap: 10px; color: var(--warning); margin-bottom: 12px;">
                        <i class="fas fa-info-circle"></i>
                        <strong>Importante sobre pagamento via Boleto</strong>
                    </div>
                    <p style="color: var(--text-secondary); font-size: 0.95rem;">
                        Boletos podem levar <strong>até 1 dia útil</strong> para serem compensados pelo banco.
                        Assim que o pagamento for confirmado, seu acesso será liberado automaticamente e
                        você receberá um email de confirmação.
                    </p>
                </div>
                <?php endif; ?>

                <div class="thankyou-steps">
                    <h4>Próximos passos:</h4>
                    <ol>
                        <?php if ($forma === 'pix'): ?>
                            <li>Escaneie o QR Code ou copie a chave aleatória.</li>
                            <li>Acesse seu app do banco e realize o pagamento via PIX.</li>
                            <li>Logo após pagar, seu acesso é liberado e você receberá o login por e-mail!</li>
                        <?php else: ?>
                            <li>Verifique seu email para o boleto</li>
                            <li>Efetue o pagamento do boleto</li>
                            <li>Aguarde a confirmação (até 1 dia útil)</li>
                            <li>Receba o email de liberação de acesso</li>
                            <li>Acesse o FinnMestre e comece a usar!</li>
                        <?php endif; ?>
                    </ol>
                </div>

                <p style="color: var(--text-muted); margin-bottom: 24px;">
                    Enviamos as instruções para: <strong style="color: var(--text-primary);">
                        <?= htmlspecialchars($email) ?>
                    </strong>
                </p>

                <a href="login.php" class="btn-access"
                    style="background: var(--bg-glass); border: 2px solid var(--border-color);">
                    <i class="fas fa-sign-in-alt"></i>
                    Ir para Login
                </a>

                <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 24px;">
                    Dúvidas? Entre em contato: <a href="mailto:suporte@finnmestre.com.br"
                        style="color: var(--primary-light);">suporte@finnmestre.com.br</a>
                </p>
            </div>
        <?php else: ?>
            <!-- PIX/Cartão: Acesso liberado -->
            <div class="thankyou-card">
                <div class="thankyou-icon">
                    <i class="fas fa-check"></i>
                </div>
                <h1 class="thankyou-title">Pagamento Confirmado!</h1>
                <p class="thankyou-message">
                    Parabéns! Sua assinatura do plano <strong>
                        <?= $planos[$plano] ?>
                    </strong> foi ativada
                    com sucesso. Seu acesso está liberado!
                </p>

                <div class="thankyou-steps">
                    <h4>Primeiros passos:</h4>
                    <ol>
                        <li>Acesse o dashboard com seu email e senha</li>
                        <li>Configure seu salário mensal nas configurações</li>
                        <li>Cadastre suas contas e cartões</li>
                        <li>Comece a registrar suas transações</li>
                        <li>Defina suas primeiras metas financeiras</li>
                    </ol>
                </div>

                <a href="login.php" class="btn-access">
                    <i class="fas fa-rocket"></i>
                    Acessar o FinnMestre
                </a>

                <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 24px;">
                    Enviamos os detalhes da sua assinatura para: <strong style="color: var(--text-primary);">
                        <?= htmlspecialchars($email) ?>
                    </strong>
                </p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Confetti Animation for Success -->
    <?php if (!$isPending && $sucesso): ?>
        <script>
            // Simple confetti effect
            function createConfetti() {
                const colors = ['#8b5cf6', '#06b6d4', '#10b981', '#f59e0b', '#ef4444'];
                for (let i = 0; i < 100; i++) {
                    const confetti = document.createElement('div');
                    confetti.style.cssText = `
                    position: fixed;
                    width: 10px;
                    height: 10px;
                    background: ${colors[Math.floor(Math.random() * colors.length)]};
                    top: -10px;
                    left: ${Math.random() * 100}vw;
                    opacity: ${Math.random() + 0.5};
                    border-radius: ${Math.random() > 0.5 ? '50%' : '0'};
                    pointer-events: none;
                    animation: fall ${Math.random() * 3 + 2}s linear forwards;
                `;
                    document.body.appendChild(confetti);
                    setTimeout(() => confetti.remove(), 5000);
                }
            }

            const style = document.createElement('style');
            style.textContent = `
            @keyframes fall {
                to {
                    transform: translateY(110vh) rotate(720deg);
                    opacity: 0;
                }
            }
        `;
            document.head.appendChild(style);

            createConfetti();
        </script>
    <?php endif; ?>
</body>

</html>