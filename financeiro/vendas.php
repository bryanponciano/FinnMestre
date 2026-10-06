<?php
$mensagem = '';
if (isset($_GET['expired']) && $_GET['expired'] == 1) {
    $mensagem = 'Você precisa de uma assinatura ativa para acessar o sistema. Escolha um plano abaixo!';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description"
        content="FinnMestre - Transforme sua relação com dinheiro. Sistema de gestão financeira que mostra o custo real das suas decisões em horas de vida.">
    <title>FinnMestre — Domine suas Finanças, Liberte seu Tempo</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Cdefs%3E%3ClinearGradient id='g' x1='0%25' y1='0%25' x2='100%25' y2='100%25'%3E%3Cstop offset='0%25' stop-color='%231e293b'/%3E%3Cstop offset='100%25' stop-color='%230f172a'/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width='100' height='100' rx='20' fill='url(%23g)'/%3E%3Ctext x='50' y='70' font-size='55' text-anchor='middle'%3E%F0%9F%A4%96%3C/text%3E%3C/svg%3E">
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

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Inter', sans-serif;
            color: var(--text-primary);
            line-height: 1.6;
            overflow-x: hidden;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
        }

        /* Navigation */
        .nav {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: rgba(21, 25, 33, 0.97);
            backdrop-filter: blur(24px);
            padding: 12px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }

        .nav-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
        }

        .nav-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            font-size: 1.5rem;
            font-weight: 800;
        }

        .nav-logo i {
            background: linear-gradient(135deg, #0084ff, #06d6a0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .nav-logo span {
            background: linear-gradient(135deg, #0084ff, #06d6a0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .nav-links a {
            color: #8b95a5;
            text-decoration: none;
            padding: 10px 18px;
            border-radius: 10px;
            font-weight: 500;
            font-size: 0.95rem;
            transition: all 0.3s;
        }

        .nav-links a:hover {
            color: #fff;
            background: rgba(0, 132, 255, 0.1);
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 32px;
            background: linear-gradient(135deg, #0084ff, #06d6a0);
            color: white !important;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 1rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 20px rgba(0, 132, 255, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(0, 132, 255, 0.5);
        }

        .btn-secondary {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 32px;
            background: transparent;
            color: #0084ff !important;
            text-decoration: none;
            border: 2px solid rgba(0, 132, 255, 0.4);
            border-radius: 10px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-secondary:hover {
            background: rgba(0, 132, 255, 0.1);
            border-color: #0084ff;
        }

        /* Hero Section */
        .hero {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 1fr 1fr;
            align-items: center;
            padding: 120px 24px 80px;
            max-width: 1400px;
            margin: 0 auto;
            gap: 60px;
            position: relative;
        }

        .hero::before {
            display: none;
        }

        .hero-content {
            position: relative;
            z-index: 1;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: rgba(139, 92, 246, 0.15);
            border: 1px solid rgba(139, 92, 246, 0.3);
            border-radius: 50px;
            font-size: 0.875rem;
            color: var(--primary-light);
            margin-bottom: 24px;
        }

        .hero h1 {
            font-size: 3.5rem;
            font-weight: 900;
            line-height: 1.1;
            margin-bottom: 24px;
        }

        .hero h1 .highlight {
            background: var(--gradient-accent);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-subtitle {
            font-size: 1.25rem;
            color: var(--text-secondary);
            margin-bottom: 32px;
            max-width: 500px;
        }

        .hero-subtitle strong {
            color: var(--secondary);
        }

        .hero-ctas {
            display: flex;
            gap: 16px;
            margin-bottom: 48px;
            flex-wrap: wrap;
        }

        .hero-visual {
            position: relative;
            z-index: 1;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .hero-visual::before {
            display: none;
        }

        .hero-visual::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 180px;
            height: 20px;
            background: radial-gradient(ellipse, rgba(0, 0, 0, 0.4) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
            z-index: 2;
        }

        .hero-image {
            max-width: 100%;
            height: auto;
            border-radius: 0;
            box-shadow: none;
            position: relative;
            z-index: 1;
            animation: float 3s ease-in-out infinite;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .hero-image.loaded {
            opacity: 1;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-15px);
            }
        }


        /* Stats Bar */
        .stats-bar {
            background: linear-gradient(135deg, #151921 0%, #1a1d24 100%);
            border-top: 1px solid rgba(255,255,255,0.06);
            border-bottom: 1px solid rgba(255,255,255,0.06);
            padding: 30px 0;
        }

        .stats-container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 60px;
            flex-wrap: wrap;
        }

        .stat-item {
            text-align: center;
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 900;
        }

        .stat-label {
            font-size: 0.95rem;
            opacity: 0.9;
        }

        /* Ideal Para Section */
        .ideal-section {
            padding: 100px 0;
        }

        .ideal-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
        }

        .ideal-content h2 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 16px;
        }

        .ideal-content h2 .highlight {
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .ideal-content .subtitle {
            font-size: 1.1rem;
            color: var(--text-secondary);
            margin-bottom: 32px;
        }

        .ideal-list {
            list-style: none;
        }

        .ideal-list li {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            padding: 16px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .ideal-list li:last-child {
            border-bottom: none;
        }

        .ideal-list .icon {
            width: 48px;
            height: 48px;
            background: rgba(6, 214, 160, 0.15);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--secondary);
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .ideal-list .text h4 {
            font-size: 1.1rem;
            margin-bottom: 4px;
            color: var(--secondary);
        }

        .ideal-list .text p {
            color: var(--text-secondary);
            font-size: 0.95rem;
        }

        .ideal-features {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .ideal-feature {
            background: var(--bg-card);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 24px;
        }

        .ideal-feature .icon {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, #0084ff, #06d6a0);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 16px;
        }

        .ideal-feature h4 {
            font-size: 1.1rem;
            margin-bottom: 8px;
        }

        .ideal-feature p {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        /* Pillars Section */
        .pillars-section {
            padding: 100px 0;
        }

        .pillars-header {
            text-align: center;
            margin-bottom: 60px;
        }

        .pillars-header h2 {
            font-size: 2.5rem;
            font-weight: 800;
        }

        .pillars-header .highlight {
            background: var(--gradient-accent);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .pillars-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
        }

        .pillar-card {
            background: #151921;
            border: 1px solid rgba(0, 132, 255, 0.15);
            border-radius: 20px;
            padding: 32px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s;
        }

        .pillar-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(135deg, #0084ff, #06d6a0);
        }

        .pillar-card:hover {
            transform: translateY(-8px);
            border-color: rgba(0, 132, 255, 0.4);
            box-shadow: 0 20px 40px rgba(0, 132, 255, 0.15);
        }

        .pillar-card .icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #0084ff, #06d6a0);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            margin-bottom: 20px;
        }

        .pillar-card h3 {
            font-size: 1.5rem;
            margin-bottom: 12px;
            color: var(--secondary);
        }

        .pillar-card p {
            color: var(--text-secondary);
            font-size: 0.95rem;
            margin-bottom: 20px;
        }

        .pillar-card ul {
            list-style: none;
        }

        .pillar-card ul li {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-secondary);
            font-size: 0.9rem;
            padding: 8px 0;
        }

        .pillar-card ul li i {
            color: var(--secondary);
        }

        /* CTA Section */
        .cta-section {
            padding: 80px 0;
            text-align: center;
        }

        .cta-section h2 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 16px;
        }

        .cta-section .highlight {
            background: var(--gradient-accent);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .cta-section p {
            color: var(--text-secondary);
            font-size: 1.1rem;
            margin-bottom: 32px;
        }

        /* Pricing Section */
        .pricing-section {
            padding: 100px 0;
        }

        .pricing-card-main {
            max-width: 500px;
            margin: 0 auto;
            background: var(--bg-card);
            border: 2px solid var(--primary);
            border-radius: 24px;
            padding: 48px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .pricing-card-main::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
        }

        .pricing-logo {
            margin-bottom: 24px;
        }

        .pricing-logo span {
            font-size: 1.5rem;
            font-weight: 800;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .pricing-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 24px;
        }

        .pricing-features-list {
            text-align: left;
            margin-bottom: 32px;
        }

        .pricing-features-list li {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 0;
            color: var(--text-secondary);
            font-size: 0.95rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .pricing-features-list li:last-child {
            border-bottom: none;
        }

        .pricing-features-list li i {
            color: var(--secondary);
        }

        .pricing-bonus {
            background: rgba(6, 214, 160, 0.1);
            border: 1px solid rgba(6, 214, 160, 0.3);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 32px;
            color: var(--secondary);
            font-weight: 600;
        }

        .pricing-old {
            color: var(--text-secondary);
            font-size: 1rem;
            text-decoration: line-through;
            margin-bottom: 8px;
        }

        .pricing-current {
            font-size: 1rem;
            margin-bottom: 8px;
        }

        .pricing-current .price {
            font-size: 3rem;
            font-weight: 900;
            color: var(--secondary);
        }

        .pricing-full {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-bottom: 24px;
        }

        /* Mentor Section */
        .mentor-section {
            padding: 100px 0;
        }

        .mentor-grid {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 60px;
            align-items: center;
        }

        .mentor-image {
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .mentor-image::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 160px;
            height: 18px;
            background: radial-gradient(ellipse, rgba(0, 0, 0, 0.35) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
            z-index: 2;
        }

        .mentor-image img {
            width: 100%;
            max-width: 400px;
            border-radius: 0;
            box-shadow: none;
            position: relative;
            z-index: 1;
            animation: float 3s ease-in-out infinite;
        }

        .mentor-content .label {
            color: var(--primary-light);
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .mentor-content h2 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 24px;
        }

        .mentor-content h2 .highlight {
            background: var(--gradient-accent);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .mentor-content p {
            color: var(--text-secondary);
            font-size: 1.05rem;
            line-height: 1.8;
            margin-bottom: 20px;
        }

        .mentor-content .quote {
            background: rgba(0, 132, 255, 0.08);
            border-left: 4px solid #0084ff;
            padding: 20px 24px;
            border-radius: 0 12px 12px 0;
            margin: 24px 0;
        }

        .mentor-content .quote p {
            font-style: italic;
            color: var(--text-primary);
            margin: 0;
        }

        /* Footer */
        .footer {
            padding: 40px 0;
            text-align: center;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
            background: #151921;
        }

        .footer p {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .hero {
                grid-template-columns: 1fr;
                text-align: center;
                padding-top: 140px;
            }

            .hero-content {
                order: 2;
            }

            .hero-visual {
                order: 1;
            }

            .hero-subtitle {
                margin: 0 auto 32px;
            }

            .hero-ctas {
                justify-content: center;
            }

            .ideal-grid,
            .mentor-grid {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .testimonials-grid {
                grid-template-columns: 1fr;
            }

            .ideal-list li {
                flex-direction: column;
                text-align: center;
            }

            .pillars-grid {
                grid-template-columns: 1fr;
            }

            .mentor-image {
                display: flex;
                justify-content: center;
            }
            /* Testimonials and Pricing responsive */
            .testimonials-grid {
                grid-template-columns: 1fr !important;
            }
            .pricing-grid {
                grid-template-columns: 1fr !important;
                max-width: 500px !important;
                padding: 0 16px;
            }
            .pricing-card {
                transform: none !important;
                box-shadow: none !important;
                margin-bottom: 24px;
            }
            .pricing-card:last-child {
                margin-bottom: 0;
            }
            .pricing-card:hover {
                transform: translateY(-4px) !important;
            }
            .finbot-preview-grid {
                grid-template-columns: 1fr !important;
                gap: 30px !important;
                text-align: center;
            }
            .finbot-preview-grid img {
                max-width: 320px;
                margin: 0 auto;
            }
        }

        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.5rem;
            }

            .stats-container {
                gap: 30px;
            }

            .stat-value {
                font-size: 2rem;
            }

            .ideal-features {
                grid-template-columns: 1fr;
            }

            .nav-links {
                display: none;
            }

            .hero-ctas {
                flex-direction: column;
                align-items: stretch;
                width: 100%;
                gap: 12px;
            }

            .hero-ctas a {
                width: 100%;
                justify-content: center;
                text-align: center;
            }
        }

        @media (max-width: 480px) {
            .hero h1 {
                font-size: 2.2rem;
            }
            .pricing-card-main {
                padding: 30px 20px;
            }
            .stat-value {
                font-size: 1.8rem;
            }
        }

        /* Animations */
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

        .animate-in {
            animation: fadeInUp 0.8s ease-out forwards;
        }

        .delay-1 {
            animation-delay: 0.1s;
        }

        .delay-2 {
            animation-delay: 0.2s;
        }

        .delay-3 {
            animation-delay: 0.3s;
        }

        /* Speech Bubble */
        .speech-bubble {
            position: absolute;
            top: 10px;
            right: 60%;
            background: rgba(139, 92, 246, 0.15);
            border: 1px solid rgba(139, 92, 246, 0.4);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 16px 20px;
            max-width: 220px;
            z-index: 10;
            opacity: 0;
            animation: bubbleIn 0.5s ease-out 1.5s forwards;
        }

        .speech-bubble::after {
            content: '';
            position: absolute;
            bottom: -10px;
            right: 20px;
            width: 0;
            height: 0;
            border-left: 10px solid transparent;
            border-right: 10px solid transparent;
            border-top: 10px solid rgba(139, 92, 246, 0.4);
        }

        .speech-bubble .bubble-text {
            color: #e2e8f0;
            font-size: 0.9rem;
            line-height: 1.5;
            min-height: 1.5em;
        }

        .speech-bubble .cursor {
            display: none;
        }

        @keyframes bubbleIn {
            from {
                opacity: 0;
                transform: translateY(10px) scale(0.9);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes blink {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0;
            }
        }

        @media (max-width: 1024px) {
            .speech-bubble {
                top: -60px;
                right: auto;
                left: 50%;
                transform: translateX(-50%);
            }

            .speech-bubble::after {
                left: 50%;
                right: auto;
                transform: translateX(-50%);
            }
        }

        .bg-page-graphics {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
            background-color: #0a0d14;
            background-image:
                linear-gradient(160deg, 
                    transparent 0%, 
                    transparent 30%, 
                    rgba(0, 132, 255, 0.02) 45%, 
                    rgba(6, 214, 160, 0.015) 60%, 
                    transparent 90%
                ),
                linear-gradient(0deg, rgba(255,255,255,0.01) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.01) 1px, transparent 1px),
                radial-gradient(ellipse at 80% 100%, rgba(6, 214, 160, 0.04) 0%, transparent 50%),
                radial-gradient(ellipse at 40% 60%, rgba(0, 132, 255, 0.03) 0%, transparent 45%),
                radial-gradient(ellipse at 15% 5%, rgba(0, 132, 255, 0.04) 0%, transparent 45%);
            background-size: 
                100% 100%,
                60px 60px,
                60px 60px,
                100% 100%,
                100% 100%,
                100% 100%;
        }

        .top-banner {
            position: relative;
            z-index: 10;
            margin-top: 130px;
            background: linear-gradient(135deg, #151921 0%, #1a1d24 100%);
            border-bottom: 1px solid rgba(0, 132, 255, 0.15);
            padding: 14px 24px;
            text-align: center;
            font-size: 0.95rem;
            font-weight: 600;
            color: #8b95a5;
            letter-spacing: 0.5px;
        }

        .top-banner i {
            color: var(--secondary);
            margin-right: 8px;
        }

        .top-banner .highlight-text {
            background: var(--gradient-accent);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 800;
        }

        /* Mini graph decorations scattered across the page */
        .bg-mini-charts {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='400' height='400'%3E%3Cg opacity='0.035'%3E%3C!-- Mini bar chart 1 --%3E%3Crect x='30' y='50' width='6' height='20' fill='%238b5cf6' rx='1'/%3E%3Crect x='38' y='42' width='6' height='28' fill='%2306d6a0' rx='1'/%3E%3Crect x='46' y='55' width='6' height='15' fill='%2306b6d4' rx='1'/%3E%3Crect x='54' y='38' width='6' height='32' fill='%238b5cf6' rx='1'/%3E%3C!-- Mini line chart 1 --%3E%3Cpolyline points='200,60 215,45 230,52 245,35 260,40 275,25' fill='none' stroke='%2306d6a0' stroke-width='2' stroke-linecap='round'/%3E%3Ccircle cx='245' cy='35' r='2.5' fill='%2306d6a0'/%3E%3Ccircle cx='275' cy='25' r='2.5' fill='%2306d6a0'/%3E%3C!-- Mini candlestick 1 --%3E%3Cline x1='330' y1='30' x2='330' y2='65' stroke='%238b5cf6' stroke-width='1'/%3E%3Crect x='327' y='38' width='6' height='15' fill='%238b5cf6' rx='1'/%3E%3Cline x1='345' y1='25' x2='345' y2='58' stroke='%2306d6a0' stroke-width='1'/%3E%3Crect x='342' y='30' width='6' height='18' fill='%2306d6a0' rx='1'/%3E%3Cline x1='360' y1='35' x2='360' y2='60' stroke='%238b5cf6' stroke-width='1'/%3E%3Crect x='357' y='40' width='6' height='12' fill='%238b5cf6' rx='1'/%3E%3C!-- Mini pie/donut --%3E%3Ccircle cx='80' cy='185' r='15' fill='none' stroke='%238b5cf6' stroke-width='3' stroke-dasharray='25 70' stroke-dashoffset='0'/%3E%3Ccircle cx='80' cy='185' r='15' fill='none' stroke='%2306d6a0' stroke-width='3' stroke-dasharray='20 75' stroke-dashoffset='-25'/%3E%3Ccircle cx='80' cy='185' r='15' fill='none' stroke='%2306b6d4' stroke-width='3' stroke-dasharray='15 80' stroke-dashoffset='-45'/%3E%3C!-- Mini bar chart 2 --%3E%3Crect x='230' y='180' width='5' height='12' fill='%2306b6d4' rx='1'/%3E%3Crect x='237' y='175' width='5' height='17' fill='%238b5cf6' rx='1'/%3E%3Crect x='244' y='170' width='5' height='22' fill='%2306d6a0' rx='1'/%3E%3Crect x='251' y='165' width='5' height='27' fill='%2306b6d4' rx='1'/%3E%3Crect x='258' y='160' width='5' height='32' fill='%238b5cf6' rx='1'/%3E%3C!-- Mini line chart 2 --%3E%3Cpolyline points='320,195 335,180 350,185 365,168 380,172' fill='none' stroke='%238b5cf6' stroke-width='2' stroke-linecap='round'/%3E%3Ccircle cx='365' cy='168' r='2.5' fill='%238b5cf6'/%3E%3C!-- Mini trending arrow --%3E%3Cpolyline points='30,350 50,335 70,340 90,320 110,310' fill='none' stroke='%2306d6a0' stroke-width='2' stroke-linecap='round'/%3E%3Cpolygon points='110,310 105,318 112,316' fill='%2306d6a0'/%3E%3C!-- Mini candlestick 2 --%3E%3Cline x1='200' y1='310' x2='200' y2='350' stroke='%2306d6a0' stroke-width='1'/%3E%3Crect x='197' y='318' width='6' height='18' fill='%2306d6a0' rx='1'/%3E%3Cline x1='212' y1='320' x2='212' y2='355' stroke='%238b5cf6' stroke-width='1'/%3E%3Crect x='209' y='328' width='6' height='16' fill='%238b5cf6' rx='1'/%3E%3Cline x1='224' y1='305' x2='224' y2='345' stroke='%2306d6a0' stroke-width='1'/%3E%3Crect x='221' y='312' width='6' height='20' fill='%2306d6a0' rx='1'/%3E%3C!-- Mini bar chart 3 --%3E%3Crect x='335' y='340' width='6' height='18' fill='%2306b6d4' rx='1'/%3E%3Crect x='343' y='332' width='6' height='26' fill='%2306d6a0' rx='1'/%3E%3Crect x='351' y='325' width='6' height='33' fill='%238b5cf6' rx='1'/%3E%3C/g%3E%3C/svg%3E");
            background-size: 400px 400px;
        }
    </style>
</head>

<body>
    <div class="bg-page-graphics"></div>
    <div class="bg-mini-charts"></div>

    <!-- Navigation -->
    <nav class="nav">
        <div class="nav-container">
            <a href="vendas.php" class="nav-logo">
                <i class="fas fa-robot"></i>
                <span>FinnMestre</span>
            </a>
            <div class="nav-links">
                <a href="#funcionalidades">Funcionalidades</a>
                <a href="#planos">Planos</a>
                <a href="#sobre">Sobre</a>
                <a href="login.php">Entrar</a>
                <a href="#planos" class="btn-primary" style="padding: 10px 24px;">Assinar Agora</a>
            </div>
        </div>
    </nav>
    
    <?php if (!empty($mensagem)): ?>
        <div style="background: #ef4444; color: white; text-align: center; padding: 15px; margin-top: 80px; font-weight: bold; z-index: 1000; position: relative; box-shadow: 0 4px 15px rgba(239, 68, 68, 0.4);">
            <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($mensagem) ?>
        </div>
    <?php endif; ?>

    <!-- Top Banner -->
    <div class="top-banner" <?= !empty($mensagem) ? 'style="margin-top: 0;"' : '' ?>>
        <i class="fas fa-clock"></i>
        Seu dinheiro vale <span class="highlight-text">horas de vida.</span> Cada real gasto é tempo que não volta.
    </div>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content animate-in">
            <div class="hero-badge">
                <i class="fas fa-star"></i>
                Transforme sua Vida Financeira
            </div>
            <h1>
                Pare de perder dinheiro <span class="highlight">sem perceber!</span>
            </h1>
            <p class="hero-subtitle">
                Desenvolva sua <strong>CONSCIÊNCIA FINANCEIRA</strong> e descubra o custo real de cada compra
                em <strong>horas de vida</strong> — para você pensar duas vezes antes de gastar.
            </p>
            <div class="hero-ctas">
                <a href="#planos" class="btn-primary">
                    <i class="fas fa-rocket"></i>
                    Garantir minha vaga!
                </a>
                <a href="#funcionalidades" class="btn-secondary">
                    <i class="fas fa-play"></i>
                    Ver como funciona
                </a>
            </div>
        </div>
        <div class="hero-visual animate-in delay-2">

            <div class="speech-bubble">
                <div class="bubble-text" id="bot-phrase">💡 Descubra quanto tempo de vida cada gasto realmente custa — e tome decisões mais inteligentes!</div>
            </div>
            <img src="assets/img/finbot-mascote.png" alt="FinBot - Mascote do FinnMestre" class="hero-image"
                style="max-width: 450px;">
        </div>
    </section>

    <!-- Stats Bar -->
    <section class="stats-bar">
        <div class="container">
            <div class="stats-container">
                <div class="stat-item animate-in delay-1">
                    <div class="stat-value">+5.400</div>
                    <div class="stat-label">Usuários Ativos</div>
                </div>
                <div class="stat-item animate-in delay-2">
                    <div class="stat-value">+R$ 2.5M</div>
                    <div class="stat-label">Capital Gerenciado</div>
                </div>
                <div class="stat-item animate-in delay-3">
                    <div class="stat-value">4.3/5</div>
                    <div class="stat-label">Avaliação dos usuários</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Ideal Para Section -->
    <section class="ideal-section" id="funcionalidades">
        <div class="container">
            <div class="ideal-grid">
                <div class="ideal-content animate-in">
                    <h2>O <span class="highlight">FinnMestre</span><br>é ideal para:</h2>
                    <p class="subtitle">Profissionais que buscam ter controle real sobre suas finanças e tempo de vida
                    </p>

                    <ul class="ideal-list">
                        <li>
                            <div class="icon"><i class="fas fa-user-tie"></i></div>
                            <div class="text">
                                <h4>Ser um consumidor mais consciente</h4>
                                <p>Entenda o impacto real de cada compra antes de fazê-la</p>
                            </div>
                        </li>
                        <li>
                            <div class="icon"><i class="fas fa-brain"></i></div>
                            <div class="text">
                                <h4>Mudar sua mentalidade sobre dinheiro</h4>
                                <p>Veja cada gasto convertido em horas de trabalho da SUA VIDA</p>
                            </div>
                        </li>
                        <li>
                            <div class="icon"><i class="fas fa-chart-line"></i></div>
                            <div class="text">
                                <h4>Alcançar suas metas financeiras</h4>
                                <p>Defina objetivos realistas e acompanhe seu progresso</p>
                            </div>
                        </li>
                    </ul>
                </div>

                <div class="ideal-features animate-in delay-2">
                    <div class="ideal-feature">
                        <div class="icon"><i class="fas fa-clock"></i></div>
                        <h4>Custo em Tempo de Vida</h4>
                        <p>Descubra quantas horas de trabalho cada compra representa na sua vida</p>
                    </div>
                    <div class="ideal-feature">
                        <div class="icon"><i class="fas fa-chart-pie"></i></div>
                        <h4>Dashboard Completo</h4>
                        <p>Visualize todas suas finanças em um único painel intuitivo</p>
                    </div>
                    <div class="ideal-feature">
                        <div class="icon"><i class="fas fa-bullseye"></i></div>
                        <h4>Metas Inteligentes</h4>
                        <p>Defina e acompanhe suas metas com aportes mensais planejados</p>
                    </div>
                    <div class="ideal-feature">
                        <div class="icon"><i class="fas fa-bell"></i></div>
                        <h4>Alertas Personalizados</h4>
                        <p>Receba avisos quando estiver perto do limite de gastos</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pillars Section -->
    <section class="pillars-section">
        <div class="container">
            <div class="pillars-header">
                <h2>Domine os <span class="highlight">3 pilares</span><br>da liberdade financeira</h2>
            </div>

            <div class="pillars-grid">
                <div class="pillar-card animate-in">
                    <div class="icon"><i class="fas fa-eye"></i></div>
                    <h3>Clareza</h3>
                    <p>Veja exatamente para onde vai cada centavo do seu dinheiro</p>
                    <ul>
                        <li><i class="fas fa-check"></i> Dashboard visual completo</li>
                        <li><i class="fas fa-check"></i> Categorização automática</li>
                        <li><i class="fas fa-check"></i> Relatórios detalhados</li>
                        <li><i class="fas fa-check"></i> Histórico de transações</li>
                    </ul>
                </div>

                <div class="pillar-card animate-in delay-1">
                    <div class="icon"><i class="fas fa-hourglass-half"></i></div>
                    <h3>Consciência</h3>
                    <p>Entenda o custo real das suas decisões em tempo de vida</p>
                    <ul>
                        <li><i class="fas fa-check"></i> Conversão para horas de vida</li>
                        <li><i class="fas fa-check"></i> Reflexão antes de gastar</li>
                        <li><i class="fas fa-check"></i> Mudança de comportamento</li>
                        <li><i class="fas fa-check"></i> Decisões mais inteligentes</li>
                    </ul>
                </div>

                <div class="pillar-card animate-in delay-2">
                    <div class="icon"><i class="fas fa-flag-checkered"></i></div>
                    <h3>Disciplina</h3>
                    <p>Construa hábitos financeiros saudáveis que duram</p>
                    <ul>
                        <li><i class="fas fa-check"></i> Limites de gastos mensais</li>
                        <li><i class="fas fa-check"></i> Metas com progresso visual</li>
                        <li><i class="fas fa-check"></i> Micro-poupança diária</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <h2>Não perca essa chance,<br>garanta hoje a <span class="highlight">sua liberdade!</span></h2>
            <p>Junte-se a centenas de pessoas que já transformaram sua relação com dinheiro</p>
            <a href="#planos" class="btn-primary">
                <i class="fas fa-rocket"></i>
                Quero fazer parte do FinnMestre!
            </a>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials-section" style="padding: 100px 0; background: #151921;">
        <div class="container">
            <div style="text-align: center; margin-bottom: 60px;">
                <h2 style="font-size: 2.5rem; font-weight: 800; margin-bottom: 16px;">
                    O que dizem os <span class="highlight">nossos usuários</span>
                </h2>
                <p style="color: var(--text-secondary); font-size: 1.1rem;">Resultados reais de quem já transformou sua vida financeira.</p>
            </div>
            <div class="testimonials-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; max-width: 1100px; margin: 0 auto;">
                <div class="testimonial-card animate-in" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 32px; position: relative;">
                    <div style="color: #f59e0b; font-size: 1rem; margin-bottom: 16px;">
                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                    </div>
                    <p style="color: var(--text-secondary); font-size: 0.95rem; margin-bottom: 24px; font-style: italic; line-height: 1.6;">
                        "O FinnMestre mudou completamente minha forma de ver o dinheiro. Antes eu gastava sem pensar, agora, sabendo que aquele gasto me custa 2 dias de trabalho, eu desisto na hora. Já consegui poupar muito mais do que imaginava!"
                    </p>
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="width: 48px; height: 48px; border-radius: 50%; background: var(--gradient-primary); display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1.2rem; color: #fff;">
                            C
                        </div>
                        <div>
                            <div style="font-weight: 700; font-size: 0.95rem;">Carlos Eduardo</div>
                            <div style="color: var(--secondary); font-size: 0.8rem; font-weight: 600;">Assinante Anual</div>
                        </div>
                    </div>
                </div>

                <div class="testimonial-card animate-in delay-1" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 32px; position: relative;">
                    <div style="color: #f59e0b; font-size: 1rem; margin-bottom: 16px;">
                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                    </div>
                    <p style="color: var(--text-secondary); font-size: 0.95rem; margin-bottom: 24px; font-style: italic; line-height: 1.6;">
                        "A funcionalidade de 'Custo em horas de vida' é genial. Me fez cancelar várias assinaturas que eu nem usava e parar de pedir comida por aplicativo toda semana. O dashboard é super intuitivo e fácil de usar. Recomendo muito!"
                    </p>
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="width: 48px; height: 48px; border-radius: 50%; background: var(--gradient-accent); display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1.2rem; color: #000;">
                            A
                        </div>
                        <div>
                            <div style="font-weight: 700; font-size: 0.95rem;">Aline Ferreira</div>
                            <div style="color: var(--secondary); font-size: 0.8rem; font-weight: 600;">Assinante Semestral</div>
                        </div>
                    </div>
                </div>

                <div class="testimonial-card animate-in delay-2" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 32px; position: relative;">
                    <div style="color: #f59e0b; font-size: 1rem; margin-bottom: 16px;">
                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                    </div>
                    <p style="color: var(--text-secondary); font-size: 0.95rem; margin-bottom: 24px; font-style: italic; line-height: 1.6;">
                        "Sempre tive dificuldade com planilhas financeiras, achava muito chato. O FinnMestre converteu meus gastos na moeda mais valiosa do mundo: meu tempo de vida. A interface é linda e o sistema excepcional."
                    </p>
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="width: 48px; height: 48px; border-radius: 50%; background: linear-gradient(135deg, #f59e0b, #ef4444); display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1.2rem; color: #fff;">
                            R
                        </div>
                        <div>
                            <div style="font-weight: 700; font-size: 0.95rem;">Rafael Souza</div>
                            <div style="color: var(--secondary); font-size: 0.8rem; font-weight: 600;">Assinante Anual</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section class="pricing-section" id="planos">
        <div class="container">
            <div class="pricing-header" style="text-align: center; margin-bottom: 20px;">
                <p style="color: var(--secondary); font-weight: 700; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 12px;">
                    <i class="fas fa-tag"></i> INVESTIMENTO
                </p>
                <h2 style="font-size: 2.5rem; font-weight: 800; margin-bottom: 16px;">
                    Escolha o plano <span class="highlight">mais inteligente</span> para você
                </h2>
                <p style="color: var(--text-secondary); font-size: 1.15rem; margin-bottom: 8px;">
                    Quem controla o dinheiro, controla o futuro.
                </p>
                <p style="color: var(--secondary); font-size: 1rem; font-weight: 600;">
                    <i class="fas fa-lightbulb" style="margin-right: 4px;"></i>
                    Organizar seu dinheiro custa menos do que um lanche por semana.
                </p>
            </div>

            <!-- Comparação visual -->
            <div style="display: flex; justify-content: center; gap: 16px; flex-wrap: wrap; margin-bottom: 40px;">
                <div style="display: flex; align-items: center; gap: 8px; background: rgba(6,214,160,0.08); border: 1px solid rgba(6,214,160,0.2); padding: 8px 16px; border-radius: 50px; font-size: 0.85rem;">
                    <i class="fas fa-shield-alt" style="color: var(--secondary);"></i>
                    <span style="color: var(--text-secondary);">Garantia de 7 dias</span>
                </div>
                <div style="display: flex; align-items: center; gap: 8px; background: rgba(139,92,246,0.08); border: 1px solid rgba(139,92,246,0.2); padding: 8px 16px; border-radius: 50px; font-size: 0.85rem;">
                    <i class="fas fa-bolt" style="color: var(--primary-light);"></i>
                    <span style="color: var(--text-secondary);">Cartão e Pix → Acesso imediato</span>
                </div>
                <div style="display: flex; align-items: center; gap: 8px; background: rgba(245,158,11,0.08); border: 1px solid rgba(245,158,11,0.2); padding: 8px 16px; border-radius: 50px; font-size: 0.85rem;">
                    <i class="fas fa-lock" style="color: #f59e0b;"></i>
                    <span style="color: var(--text-secondary);">Pagamento 100% seguro</span>
                </div>
            </div>

            <div class="pricing-grid"
                style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; max-width: 1100px; margin: 0 auto; align-items: start;">

                <!-- Plano Mensal -->
                <div class="pricing-card animate-in"
                    style="background: var(--bg-card); border: 2px solid rgba(255,255,255,0.08); border-radius: 24px; padding: 40px 28px; text-align: center; position: relative; transition: all 0.3s;"
                    onmouseover="this.style.transform='translateY(-8px)'; this.style.boxShadow='0 20px 50px rgba(0,0,0,0.3)'"
                    onmouseout="this.style.transform=''; this.style.boxShadow=''">
                    <div style="margin-bottom: 8px;">
                        <i class="fas fa-seedling" style="font-size: 2rem; color: #3b82f6;"></i>
                    </div>
                    <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 4px; color: #3b82f6;">
                        Mensal</h3>
                    <p style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 24px;">Para começar a organizar</p>

                    <div style="margin-bottom: 24px;">
                        <span style="font-size: 3rem; font-weight: 900; color: var(--text-primary);">R$ 39</span>
                        <span style="color: var(--text-secondary);">,90/mês</span>
                    </div>

                    <ul style="list-style: none; text-align: left; margin-bottom: 32px;">
                        <li style="display: flex; align-items: center; gap: 10px; padding: 10px 0; color: var(--text-secondary); font-size: 0.9rem; border-bottom: 1px solid rgba(255,255,255,0.06);">
                            <i class="fas fa-check" style="color: #3b82f6;"></i> Dashboard completo
                        </li>
                        <li style="display: flex; align-items: center; gap: 10px; padding: 10px 0; color: var(--text-secondary); font-size: 0.9rem; border-bottom: 1px solid rgba(255,255,255,0.06);">
                            <i class="fas fa-check" style="color: #3b82f6;"></i> Custo em tempo de vida
                        </li>
                        <li style="display: flex; align-items: center; gap: 10px; padding: 10px 0; color: var(--text-secondary); font-size: 0.9rem; border-bottom: 1px solid rgba(255,255,255,0.06);">
                            <i class="fas fa-check" style="color: #3b82f6;"></i> Até 3 perfis
                        </li>
                        <li style="display: flex; align-items: center; gap: 10px; padding: 10px 0; color: var(--text-secondary); font-size: 0.9rem;">
                            <i class="fas fa-check" style="color: #3b82f6;"></i> Suporte por email
                        </li>
                    </ul>

                    <div style="background: rgba(59,130,246,0.06); border-radius: 12px; padding: 12px; margin-bottom: 24px;">
                        <div style="font-size: 0.75rem; color: var(--text-secondary); margin-bottom: 2px;">Custo mensal médio</div>
                        <div style="font-size: 1.1rem; font-weight: 800; color: #3b82f6;">R$ 39,90 <span style="font-size: 0.8rem; font-weight: 400; color: var(--text-secondary);">/mês</span></div>
                    </div>

                    <a href="pagamento.php?plano=mensal" class="btn-secondary"
                        style="width: 100%; justify-content: center; padding: 14px 24px;">
                        Começar agora
                    </a>
                </div>

                <!-- Plano Semestral -->
                <div class="pricing-card animate-in delay-1"
                    style="background: linear-gradient(135deg, var(--bg-card) 0%, rgba(139, 92, 246, 0.08) 100%); border: 2px solid rgba(139,92,246,0.3); border-radius: 24px; padding: 40px 28px; text-align: center; position: relative; transition: all 0.3s;"
                    onmouseover="this.style.transform='translateY(-8px)'; this.style.boxShadow='0 20px 50px rgba(139,92,246,0.2)'"
                    onmouseout="this.style.transform=''; this.style.boxShadow=''">
                    <div style="margin-bottom: 8px;">
                        <i class="fas fa-chart-line" style="font-size: 2rem; color: var(--primary-light);"></i>
                    </div>
                    <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 4px; color: var(--primary-light);">
                        Semestral</h3>
                    <p style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 16px;">Compromisso com resultados</p>

                    <div style="background: rgba(6, 214, 160, 0.1); border: 1px solid rgba(6, 214, 160, 0.25); border-radius: 8px; padding: 6px 14px; display: inline-block; margin-bottom: 16px;">
                        <span style="color: var(--secondary); font-weight: 700; font-size: 0.82rem;">
                            <i class="fas fa-tag"></i> ECONOMIZE R$ 42,40
                        </span>
                    </div>

                    <div style="margin-bottom: 6px;">
                        <span style="text-decoration: line-through; color: var(--text-secondary); font-size: 0.9rem;">De R$ 239,40</span>
                    </div>
                    <div style="margin-bottom: 6px;">
                        <span style="font-size: 3rem; font-weight: 900; color: var(--primary-light);">R$ 197</span>
                        <span style="color: var(--text-secondary);">,00</span>
                    </div>
                    <p style="color: var(--secondary); font-size: 0.9rem; font-weight: 600; margin-bottom: 24px;">
                        <i class="fas fa-arrow-right" style="font-size: 0.7rem;"></i> R$ 32,83/mês
                    </p>

                    <ul style="list-style: none; text-align: left; margin-bottom: 32px;">
                        <li style="display: flex; align-items: center; gap: 10px; padding: 10px 0; color: var(--text-secondary); font-size: 0.9rem; border-bottom: 1px solid rgba(255,255,255,0.06);">
                            <i class="fas fa-check" style="color: var(--primary-light);"></i> Tudo do plano Mensal
                        </li>
                        <li style="display: flex; align-items: center; gap: 10px; padding: 10px 0; color: var(--text-secondary); font-size: 0.9rem; border-bottom: 1px solid rgba(255,255,255,0.06);">
                            <i class="fas fa-check" style="color: var(--primary-light);"></i> Perfis ilimitados
                        </li>
                        <li style="display: flex; align-items: center; gap: 10px; padding: 10px 0; color: var(--text-secondary); font-size: 0.9rem; border-bottom: 1px solid rgba(255,255,255,0.06);">
                            <i class="fas fa-check" style="color: var(--primary-light);"></i> Relatórios avançados
                        </li>
                        <li style="display: flex; align-items: center; gap: 10px; padding: 10px 0; color: var(--text-secondary); font-size: 0.9rem;">
                            <i class="fas fa-check" style="color: var(--primary-light);"></i> Suporte prioritário
                        </li>
                    </ul>

                    <div style="background: rgba(139,92,246,0.06); border-radius: 12px; padding: 12px; margin-bottom: 24px;">
                        <div style="font-size: 0.75rem; color: var(--text-secondary); margin-bottom: 2px;">Custo mensal médio</div>
                        <div style="font-size: 1.1rem; font-weight: 800; color: var(--primary-light);">R$ 32,83 <span style="font-size: 0.8rem; font-weight: 400; color: var(--text-secondary);">/mês</span></div>
                        <div style="font-size: 0.7rem; color: var(--secondary); font-weight: 700; margin-top: 2px;">ECONOMIA DE 18%</div>
                    </div>

                    <a href="pagamento.php?plano=semestral" class="btn-primary"
                        style="width: 100%; justify-content: center;">
                        <i class="fas fa-rocket"></i> Quero esse!
                    </a>
                </div>

                <!-- Plano Anual — DESTAQUE -->
                <div class="pricing-card animate-in delay-2"
                    style="background: linear-gradient(135deg, var(--bg-card) 0%, rgba(6,214,160,0.1) 100%); border: 2px solid #06d6a0; border-radius: 24px; padding: 40px 28px; text-align: center; position: relative; transition: all 0.4s; transform: scale(1.04); z-index: 10; box-shadow: 0 0 40px rgba(6,214,160,0.12);"
                    onmouseover="this.style.transform='scale(1.04) translateY(-8px)'; this.style.boxShadow='0 24px 60px rgba(6,214,160,0.25)'"
                    onmouseout="this.style.transform='scale(1.04)'; this.style.boxShadow='0 0 40px rgba(6,214,160,0.12)'">

                    <!-- Selo -->
                    <div style="position: absolute; top: -16px; left: 50%; transform: translateX(-50%); background: linear-gradient(135deg, #06d6a0, #34d399); padding: 8px 28px; border-radius: 50px; font-size: 0.8rem; font-weight: 800; color: #000; letter-spacing: 0.5px; box-shadow: 0 4px 20px rgba(6,214,160,0.4);">
                        <i class="fas fa-trophy"></i> MELHOR ESCOLHA
                    </div>

                    <div style="margin-bottom: 8px; margin-top: 8px;">
                        <i class="fas fa-crown" style="font-size: 2.2rem; color: #06d6a0;"></i>
                    </div>
                    <h3 style="font-size: 1.35rem; font-weight: 800; margin-bottom: 4px; color: var(--secondary);">Anual</h3>
                    <p style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 16px;">O investimento mais inteligente</p>

                    <div style="background: rgba(6, 214, 160, 0.12); border: 1px solid rgba(6, 214, 160, 0.3); border-radius: 10px; padding: 8px 16px; display: inline-block; margin-bottom: 16px;">
                        <span style="color: var(--secondary); font-weight: 800; font-size: 0.85rem;">
                            <i class="fas fa-fire"></i> ECONOMIZE R$ 181,80
                        </span>
                    </div>

                    <div style="margin-bottom: 6px;">
                        <span style="text-decoration: line-through; color: var(--text-secondary); font-size: 0.9rem;">De R$ 478,80</span>
                    </div>
                    <div style="margin-bottom: 6px;">
                        <span style="font-size: 3.2rem; font-weight: 900; color: var(--secondary);">R$ 297</span>
                        <span style="color: var(--secondary);">,00</span>
                    </div>
                    <p style="color: var(--secondary); font-size: 1rem; font-weight: 700; margin-bottom: 8px;">
                        Apenas <strong style="text-decoration: underline;">R$ 24,75/mês</strong>
                    </p>
                    <p style="color: var(--text-secondary); font-size: 0.8rem; margin-bottom: 24px; font-style: italic;">
                        Menos que R$ 1 por dia para controlar seu dinheiro
                    </p>

                    <ul style="list-style: none; text-align: left; margin-bottom: 32px;">
                        <li style="display: flex; align-items: center; gap: 10px; padding: 10px 0; color: var(--text-secondary); font-size: 0.9rem; border-bottom: 1px solid rgba(6,214,160,0.15);">
                            <i class="fas fa-check-double" style="color: var(--secondary);"></i> <strong style="color: var(--text-primary);">Tudo do Semestral</strong>
                        </li>
                        <li style="display: flex; align-items: center; gap: 10px; padding: 10px 0; color: var(--text-secondary); font-size: 0.9rem; border-bottom: 1px solid rgba(6,214,160,0.15);">
                            <i class="fas fa-check-double" style="color: var(--secondary);"></i> Exportação para Excel
                        </li>
                        <li style="display: flex; align-items: center; gap: 10px; padding: 10px 0; color: var(--text-primary); font-size: 0.9rem; font-weight: 600;">
                            <i class="fas fa-star" style="color: #f59e0b;"></i> Acesso vitalício a updates
                        </li>
                    </ul>

                    <div style="background: rgba(6,214,160,0.08); border: 1px solid rgba(6,214,160,0.2); border-radius: 12px; padding: 12px; margin-bottom: 24px;">
                        <div style="font-size: 0.75rem; color: var(--text-secondary); margin-bottom: 2px;">Custo mensal médio</div>
                        <div style="font-size: 1.1rem; font-weight: 800; color: var(--secondary);">R$ 24,75 <span style="font-size: 0.8rem; font-weight: 400; color: var(--text-secondary);">/mês</span></div>
                        <div style="font-size: 0.7rem; color: var(--secondary); font-weight: 800; margin-top: 2px;">ECONOMIA DE 38%</div>
                    </div>

                    <a href="pagamento.php?plano=anual" class="btn-primary"
                        style="width: 100%; justify-content: center; background: linear-gradient(135deg, #06d6a0, #34d399); color: #000; font-weight: 800; font-size: 1rem; padding: 16px 24px; box-shadow: 0 6px 25px rgba(6,214,160,0.35);"
                        onmouseover="this.style.boxShadow='0 10px 40px rgba(6,214,160,0.5)'; this.style.transform='translateY(-2px)'"
                        onmouseout="this.style.boxShadow='0 6px 25px rgba(6,214,160,0.35)'; this.style.transform=''">
                        <i class="fas fa-crown"></i> Investir no meu futuro
                    </a>
            </div>




        </div>
    </section>

    <!-- FinBot Section (Order Bump Preview) -->
    <section style="padding: 100px 0; background: radial-gradient(circle at 50% 50%, rgba(139, 92, 246, 0.1) 0%, transparent 70%);">
        <div class="container">
            <div class="finbot-preview-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: center;">
                <div>
                    <div style="background: rgba(139, 92, 246, 0.15); border: 1px solid rgba(139, 92, 246, 0.3); border-radius: 50px; padding: 8px 20px; display: inline-flex; align-items: center; gap: 8px; margin-bottom: 24px; color: var(--primary-light); font-weight: 700; font-size: 0.85rem;">
                        <i class="fas fa-robot"></i> POWER-UP EXCLUSIVO
                    </div>
                    <h2 style="font-size: 2.5rem; font-weight: 800; margin-bottom: 24px; line-height: 1.2;">
                        FinBot AI: Registre gastos<br>via <span class="highlight">WhatsApp</span>
                    </h2>
                    <p style="color: var(--text-secondary); font-size: 1.1rem; margin-bottom: 32px;">
                        Esqueça a preguiça de anotar. Envie um áudio, uma foto do comprovante ou uma mensagem de texto para o nosso robô e ele registra tudo automaticamente no seu FinnMestre.
                    </p>
                    <div style="display: grid; gap: 16px; margin-bottom: 32px;">
                        <div style="display: flex; gap: 12px; align-items: center;">
                            <i class="fab fa-whatsapp" style="color: #25d366; font-size: 1.5rem;"></i>
                            <span style="color: var(--text-secondary);">Integração oficial via API</span>
                        </div>
                        <div style="display: flex; gap: 12px; align-items: center;">
                            <i class="fas fa-microphone" style="color: var(--primary-light); font-size: 1.5rem;"></i>
                            <span style="color: var(--text-secondary);">Entende áudios e fotos de recibos</span>
                        </div>
                    </div>
                    <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 20px; padding: 32px; text-align: center; position: relative; overflow: hidden;">
                        <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(2px); z-index: 1;"></div>
                        
                        <div style="position: relative; z-index: 2;">
                            <div style="background: #f59e0b; color: #000; display: inline-block; padding: 4px 16px; border-radius: 50px; font-weight: 800; font-size: 0.75rem; margin-bottom: 16px; text-transform: uppercase; letter-spacing: 1px;">
                                <i class="fas fa-tools"></i> Em Desenvolvimento
                            </div>
                            <h4 style="font-size: 1.5rem; font-weight: 800; margin-bottom: 12px; color: #fff;">Lançamento em Breve!</h4>
                            <p style="color: var(--text-secondary); font-size: 0.95rem; line-height: 1.6;">
                                O FinBot AI está passando pelos últimos ajustes de inteligência para garantir a melhor experiência. 
                                <strong>Em breve você poderá registrar tudo via WhatsApp!</strong>
                            </p>
                            <div style="margin-top: 24px; color: var(--secondary); font-weight: 600; font-size: 0.9rem;">
                                <i class="fas fa-bell"></i> Notificaremos todos os usuários no lançamento
                            </div>
                        </div>
                    </div>
                </div>
                <div style="position: relative;">
                    <div style="background: var(--bg-card); border: 1px solid rgba(255,255,255,0.1); border-radius: 32px; padding: 12px; box-shadow: 0 40px 80px rgba(0,0,0,0.5);">
                        <img src="assets/img/finbot-mockup.png" alt="FinBot WhatsApp" style="width: 100%; border-radius: 24px; display: block;">
                    </div>

                </div>
            </div>
        </div>
    </section>



            <!-- Garantia -->
            <div
                style="max-width: 600px; margin: 50px auto 0; text-align: center; background: rgba(6, 214, 160, 0.08); border: 1px solid rgba(6, 214, 160, 0.25); border-radius: 16px; padding: 32px;">
                <div
                    style="width: 64px; height: 64px; background: var(--gradient-accent); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                    <i class="fas fa-shield-alt" style="font-size: 1.5rem; color: #000;"></i>
                </div>
                <h4 style="font-size: 1.25rem; margin-bottom: 8px;">Garantia incondicional de 7 dias</h4>
                <p style="color: var(--text-secondary); font-size: 0.95rem; margin-bottom: 16px;">
                    Se você não ficar satisfeito nos primeiros 7 dias, devolvemos 100% do seu dinheiro. Sem burocracia.
                </p>
                <div style="display: flex; justify-content: center; gap: 20px; flex-wrap: wrap;">
                    <div style="display: flex; align-items: center; gap: 6px; color: var(--text-secondary); font-size: 0.85rem;">
                        <i class="fas fa-credit-card" style="color: var(--primary-light);"></i> Cartão de crédito
                    </div>
                    <div style="display: flex; align-items: center; gap: 6px; color: var(--text-secondary); font-size: 0.85rem;">
                        <i class="fas fa-qrcode" style="color: var(--secondary);"></i> PIX
                    </div>
                    <div style="display: flex; align-items: center; gap: 6px; color: var(--secondary); font-size: 0.85rem; font-weight: 600;">
                        <i class="fas fa-bolt" style="color: #f59e0b;"></i> Acesso imediato
                    </div>
                </div>
            </div>
        </div>
    </section>


    <!-- Mentor Section -->
    <section class="mentor-section" id="sobre">
        <div class="container">
            <div class="mentor-grid">
                <div class="mentor-image animate-in">
                    <img src="assets/img/finbot-mascote.png" alt="FinBot - Mascote do FinnMestre"
                        style="filter: drop-shadow(0 20px 60px rgba(139, 92, 246, 0.3));">
                </div>
                <div class="mentor-content animate-in delay-2">
                    <p class="label">Criador do FinnMestre</p>
                    <h2>Quem vai ser o<br>seu <span class="highlight">mentor?</span></h2>

                    <p>
                        Olá! Sou Bryan, e criei o FinnMestre depois de passar anos lutando para entender
                        para onde ia meu dinheiro. Ganhava bem, mas no final do mês não sobrava nada.
                    </p>

                    <p>
                        Testei dezenas de aplicativos de controle financeiro — todos tinham o mesmo problema:
                        mostravam números, mas não criavam consciência.
                    </p>

                    <div class="quote">
                        <p>"E se cada gasto mostrasse quantas horas da minha vida eu estava trocando por aquele
                            produto?"</p>
                    </div>

                    <p>
                        Quando vi que um jantar de R$ 120 representava 3 horas de trabalho, minha forma de gastar
                        mudou completamente. O FinnMestre nasceu dessa necessidade pessoal — não é apenas um controle
                        financeiro, é uma ferramenta de <strong style="color: var(--secondary);">consciência
                            financeira</strong>.
                    </p>

                    <a href="#planos" class="btn-primary">
                        <i class="fas fa-check"></i>
                        Garantir minha vaga!
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>© 2024 FinnMestre. Todos os direitos reservados.</p>
        </div>
    </footer>

    <script>
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Animate on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        document.querySelectorAll('.animate-in').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';
            el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(el);
        });
    </script>

    <script>
        // Remove dark background from robot images — process immediately, show after done
        function removeDarkBackground(img) {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            canvas.width = img.naturalWidth;
            canvas.height = img.naturalHeight;
            ctx.drawImage(img, 0, 0);

            const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            const data = imageData.data;
            const threshold = 35;
            const fadeZone = 15;

            // Process all pixels at once (fast, no chunking)
            for (let i = 0; i < data.length; i += 4) {
                const maxVal = Math.max(data[i], data[i + 1], data[i + 2]);
                if (maxVal < threshold) {
                    data[i + 3] = 0;
                } else if (maxVal < threshold + fadeZone) {
                    data[i + 3] = Math.round(((maxVal - threshold) / fadeZone) * 255);
                }
            }

            ctx.putImageData(imageData, 0, 0);
            img.src = canvas.toDataURL('image/png');
            img.classList.add('loaded');
        }

        document.querySelectorAll('.hero-image, .mentor-image img').forEach(img => {
            if (img.complete && img.naturalWidth > 0) {
                removeDarkBackground(img);
            } else {
                img.addEventListener('load', () => removeDarkBackground(img));
            }
        });
    </script>

    <script>
        // Speech bubble fade in
        setTimeout(() => {
            const bubble = document.querySelector('.speech-bubble');
            if (bubble) bubble.style.opacity = '1';
        }, 1500);

        // Randomize Bot Phrases
        const botPhrases = [
            "💡 Descubra quanto tempo de vida cada gasto realmente custa — e tome decisões mais inteligentes!",
            "🚀 Transforme sua relação com o dinheiro agora mesmo. Sua liberdade começa aqui!",
            "🧠 Consciência financeira não é sobre cortar gastos, é sobre valorizar seu tempo de vida.",
            "⌚ Seu tempo é seu bem mais valioso. Já pensou em quanto dele você troca por coisas que não precisa?",
            "📊 No FinnMestre, os números contam uma história: a história do seu tempo.",
            "💰 Pare de perder dinheiro sem perceber. Eu te ajudo a enxergar o invisível!",
            "🌟 Domine suas finanças hoje para poder desfrutar do seu tempo amanhã.",
            "📱 Envie seus gastos pelo WhatsApp e eu registro tudo pra você. Simples assim!"
        ];

        function randomizePhrase() {
            const phraseElement = document.getElementById('bot-phrase');
            if (phraseElement) {
                const randomIndex = Math.floor(Math.random() * botPhrases.length);
                phraseElement.innerText = botPhrases[randomIndex];
            }
        }

        document.addEventListener('DOMContentLoaded', randomizePhrase);
    </script>
</body>

</html>