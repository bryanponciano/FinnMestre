<?php
session_start();
require_once 'includes/functions.php';

// Verificar se está logado e é novo usuário
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['novo_usuario'])) {
    header('Location: index.php');
    exit;
}

$apelido = $_SESSION['usuario_nome'] ?? 'Mestre';
$plano = $_SESSION['plano'] ?? 'premium';

// Marcar como já viu a boas-vindas
unset($_SESSION['novo_usuario']);

// Frases impactantes
$frases = [
    "Seu tempo é o único recurso que você nunca recupera. A partir de agora, cada gasto passa por essa lente.",
    "Dinheiro vai e vem. Mas as horas que você trabalhou para ganhá-lo? Essas são insubstituíveis.",
    "A verdadeira liberdade financeira começa quando você entende o peso real de cada decisão.",
    "Hoje você deu o primeiro passo. A maioria nunca sai do lugar.",
    "Não se trata de quanto você ganha, mas de quanto você guarda para a vida que você merece.",
    "A partir de agora, você não controla apenas seu dinheiro — você controla seu tempo de vida."
];
$frase = $frases[array_rand($frases)];
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bem-vindo ao FinnMestre!</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet">
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
            --gradient-accent: linear-gradient(135deg, #06d6a0 0%, #06b6d4 100%);
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
            padding: 40px 24px;
            overflow-y: auto;
        }

        /* Background animation */
        .bg-animation {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 0;
            overflow: hidden;
        }

        .bg-animation::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(ellipse at center, rgba(139, 92, 246, 0.1) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 20%, rgba(6, 182, 212, 0.08) 0%, transparent 40%),
                radial-gradient(ellipse at 20% 80%, rgba(6, 214, 160, 0.08) 0%, transparent 40%);
            animation: pulse 8s ease-in-out infinite alternate;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
                opacity: 0.5;
            }

            100% {
                transform: scale(1.1);
                opacity: 1;
            }
        }

        .welcome-container {
            max-width: 700px;
            width: 100%;
            text-align: center;
            position: relative;
            z-index: 1;
        }

        .logo {
            margin-bottom: 48px;
            animation: fadeInDown 0.8s ease;
        }

        .logo i {
            font-size: 4rem;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            display: block;
            margin-bottom: 16px;
        }

        .logo span {
            font-size: 2rem;
            font-weight: 800;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .greeting {
            margin-bottom: 40px;
            animation: fadeInUp 0.8s ease 0.2s both;
        }

        .greeting h1 {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .greeting h1 span {
            background: var(--gradient-accent);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .greeting p {
            color: var(--text-secondary);
            font-size: 1.25rem;
        }

        .quote-card {
            background: var(--bg-card);
            border: 1px solid rgba(139, 92, 246, 0.3);
            border-radius: 24px;
            padding: 48px 40px;
            margin-bottom: 48px;
            position: relative;
            animation: fadeInUp 0.8s ease 0.4s both;
        }

        .quote-card::before {
            content: '"';
            position: absolute;
            top: 20px;
            left: 30px;
            font-size: 6rem;
            font-family: Georgia, serif;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            opacity: 0.3;
            line-height: 1;
        }

        .quote-text {
            font-size: 1.5rem;
            font-weight: 500;
            line-height: 1.6;
            position: relative;
            z-index: 1;
        }

        .features-preview {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 48px;
            animation: fadeInUp 0.8s ease 0.6s both;
        }

        .feature-item {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 20px;
            transition: all 0.3s;
        }

        .feature-item:hover {
            border-color: var(--primary);
            background: rgba(139, 92, 246, 0.1);
        }

        .feature-item i {
            font-size: 1.5rem;
            color: var(--secondary);
            margin-bottom: 12px;
        }

        .feature-item span {
            display: block;
            font-size: 0.9rem;
            color: var(--text-secondary);
        }

        .btn-start {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            padding: 18px 48px;
            background: var(--gradient-primary);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-size: 1.25rem;
            font-weight: 700;
            transition: all 0.3s;
            box-shadow: 0 8px 30px rgba(139, 92, 246, 0.4);
            animation: fadeInUp 0.8s ease 0.8s both;
        }

        .btn-start:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(139, 92, 246, 0.6);
        }

        .icon-pulse {
            animation: iconPulse 2s ease-in-out infinite;
        }

        @keyframes iconPulse {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.2);
            }
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Confetti */
        .confetti {
            position: fixed;
            width: 10px;
            height: 10px;
            top: -10px;
            opacity: 0;
            animation: confettiFall 4s ease-out forwards;
        }

        @keyframes confettiFall {
            0% {
                opacity: 1;
                top: -10px;
            }

            100% {
                opacity: 0;
                top: 100vh;
            }
        }

        @media (max-width: 768px) {
            .greeting h1 {
                font-size: 2rem;
            }

            .quote-text {
                font-size: 1.2rem;
            }

            .features-preview {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="bg-animation"></div>

    <div class="welcome-container">
        <div class="logo">
            <i class="fas fa-chart-line"></i>
            <span>FinnMestre</span>
        </div>

        <div class="greeting">
            <h1>Bem-vindo, <span>
                    <?= htmlspecialchars($apelido) ?>
                </span>! 🎉</h1>
            <p>Sua jornada para a liberdade financeira começa agora.</p>
        </div>

        <div class="quote-card">
            <p class="quote-text">
                <?= htmlspecialchars($frase) ?>
            </p>
        </div>

        <div class="features-preview">
            <div class="feature-item">
                <i class="fas fa-clock"></i>
                <span>Custo em Horas de Vida</span>
            </div>
            <div class="feature-item">
                <i class="fas fa-chart-pie"></i>
                <span>Dashboard Completo</span>
            </div>
            <div class="feature-item">
                <i class="fas fa-bullseye"></i>
                <span>Metas Inteligentes</span>
            </div>
        </div>

        <a href="index.php" class="btn-start">
            <i class="fas fa-rocket icon-pulse"></i>
            Acessar meu painel
        </a>
    </div>

    <script>
        // Confetti effect
        const colors = ['#8b5cf6', '#06d6a0', '#06b6d4', '#f59e0b', '#ec4899'];

        for (let i = 0; i < 50; i++) {
            setTimeout(() => {
                const confetti = document.createElement('div');
                confetti.className = 'confetti';
                confetti.style.left = Math.random() * 100 + 'vw';
                confetti.style.background = colors[Math.floor(Math.random() * colors.length)];
                confetti.style.animationDelay = Math.random() * 2 + 's';
                confetti.style.animationDuration = (3 + Math.random() * 2) + 's';
                confetti.style.transform = 'rotate(' + Math.random() * 360 + 'deg)';
                document.body.appendChild(confetti);

                setTimeout(() => confetti.remove(), 5000);
            }, i * 50);
        }
    </script>
</body>

</html>