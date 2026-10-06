<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description"
        content="FinnMestre - Gestão financeira com disciplina e clareza. Controle seus gastos, defina metas e entenda o verdadeiro custo das suas decisões.">
    <title>FinnMestre — Gestão Financeira com Disciplina e Clareza</title>
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
                <i class="fas fa-chart-line" style="color: var(--fm-primary);"></i>
                <span class="logo-text">FinnMestre</span>
            </a>
            <div class="nav-links">
                <a href="#como-funciona">Como Funciona</a>
                <a href="#recursos">Recursos</a>
                <a href="vendas.php">Preços</a>
                <a href="login.php" class="nav-btn-secondary">Entrar</a>
                <a href="vendas.php" class="nav-btn-primary">Assinar Agora</a>
            </div>
            <button class="nav-mobile-toggle" id="mobileToggle">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-bg"></div>
        <div class="hero-content">
            <div class="hero-badge">
                <i class="fas fa-chart-line"></i>
                Gestão Financeira Inteligente
            </div>
            <h1>Tome controle do seu dinheiro com <span class="gradient-text">disciplina e clareza</span></h1>
            <p class="hero-subtitle">
                Pare de perder dinheiro sem perceber. Com o FinnMestre, você enxerga o verdadeiro custo
                de cada decisão — em horas de vida, não apenas em reais.
            </p>
            <div class="hero-ctas">
                <a href="vendas.php" class="btn-hero-primary">
                    <i class="fas fa-rocket"></i>
                    Começar Agora
                </a>
                <a href="#como-funciona" class="btn-hero-secondary">
                    <i class="fas fa-play-circle"></i>
                    Ver Como Funciona
                </a>
            </div>
            <div class="hero-stats">
                <div class="stat-item">
                    <span class="stat-value">🎯</span>
                    <span class="stat-label">Controle Total</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value">⏱️</span>
                    <span class="stat-label">Custo em Tempo</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value">📈</span>
                    <span class="stat-label">Metas Realistas</span>
                </div>
            </div>
        </div>
        <div class="hero-visual">
            <div class="dashboard-preview">
                <div class="preview-header">
                    <div class="preview-dots">
                        <span></span><span></span><span></span>
                    </div>
                    <span class="preview-title">FinnMestre Dashboard</span>
                </div>
                <div class="preview-content">
                    <div class="preview-card card-balance">
                        <span class="preview-label">Saldo do Mês</span>
                        <span class="preview-value">R$ 2.450,00</span>
                    </div>
                    <div class="preview-card card-salary">
                        <span class="preview-label">Salário Restante</span>
                        <span class="preview-value green">67%</span>
                    </div>
                    <div class="preview-card card-hours">
                        <span class="preview-label">Maior Gasto</span>
                        <span class="preview-value small">Delivery = 12h de trabalho</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 3 Pillars Section -->
    <section class="pillars" id="pilares">
        <div class="container">
            <div class="section-header">
                <span class="section-badge">Pilares do Sistema</span>
                <h2>Três fundamentos para transformar sua vida financeira</h2>
            </div>
            <div class="pillars-grid">
                <div class="pillar-card">
                    <div class="pillar-icon blue">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <h3>Controle</h3>
                    <p>Visualize todas as suas contas, cartões e transações em um só lugar.
                        Saiba exatamente para onde vai cada centavo.</p>
                    <ul class="pillar-features">
                        <li><i class="fas fa-check"></i> Multi-contas e cartões</li>
                        <li><i class="fas fa-check"></i> Categorização inteligente</li>
                    </ul>
                </div>
                <div class="pillar-card featured">
                    <div class="pillar-icon purple">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <h3>Metas</h3>
                    <p>Defina objetivos financeiros com aportes mensais planejados.
                        Acompanhe seu progresso de forma realista, sem pressão irreal.</p>
                    <ul class="pillar-features">
                        <li><i class="fas fa-check"></i> Aportes mensais</li>
                        <li><i class="fas fa-check"></i> Progresso visual</li>
                        <li><i class="fas fa-check"></i> Projeções realistas</li>
                    </ul>
                </div>
                <div class="pillar-card">
                    <div class="pillar-icon green">
                        <i class="fas fa-brain"></i>
                    </div>
                    <h3>Consciência</h3>
                    <p>Entenda o custo real de cada gasto convertido em horas de vida.
                        Tome decisões mais conscientes sobre seu dinheiro.</p>
                    <ul class="pillar-features">
                        <li><i class="fas fa-check"></i> Custo em horas de trabalho</li>
                        <li><i class="fas fa-check"></i> Top 5 maiores gastos</li>
                        <li><i class="fas fa-check"></i> Reflexões financeiras</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- How it Works -->
    <section class="how-it-works" id="como-funciona">
        <div class="container">
            <div class="section-header">
                <span class="section-badge">Simples e Direto</span>
                <h2>Como funciona em 3 passos</h2>
            </div>
            <div class="steps-container">
                <div class="step">
                    <div class="step-number">1</div>
                    <div class="step-content">
                        <h3>Configure seu perfil</h3>
                        <p>Informe seu salário e horas de trabalho mensais.
                            Cadastre suas contas e cartões.</p>
                    </div>
                </div>
                <div class="step-connector"></div>
                <div class="step">
                    <div class="step-number">2</div>
                    <div class="step-content">
                        <h3>Registre suas transações</h3>
                        <p>Lance entradas e saídas. O sistema calcula automaticamente
                            o impacto de cada gasto em horas de vida.</p>
                    </div>
                </div>
                <div class="step-connector"></div>
                <div class="step">
                    <div class="step-number">3</div>
                    <div class="step-content">
                        <h3>Acompanhe e evolua</h3>
                        <p>Visualize relatórios, acompanhe metas e tome decisões
                            financeiras mais conscientes.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section class="features" id="recursos">
        <div class="container">
            <div class="section-header">
                <span class="section-badge">Recursos Completos</span>
                <h2>Tudo que você precisa para organizar suas finanças</h2>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-chart-pie"></i></div>
                    <h4>Dashboard Inteligente</h4>
                    <p>Visão completa do mês: saldo, entradas, saídas e % do salário restante.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-credit-card"></i></div>
                    <h4>Multi-Contas</h4>
                    <p>Gerencie todas as suas contas bancárias e cartões em um só lugar.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-exchange-alt"></i></div>
                    <h4>Transações</h4>
                    <p>Registre entradas e saídas com categorias e veja o impacto em tempo real.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-bullseye"></i></div>
                    <h4>Metas Financeiras</h4>
                    <p>Defina metas com aportes mensais planejados e acompanhe o progresso.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-clock"></i></div>
                    <h4>Custo em Tempo</h4>
                    <p>Veja quanto cada gasto custa em horas de trabalho da sua vida.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-file-alt"></i></div>
                    <h4>Relatórios</h4>
                    <p>Análises detalhadas por categoria, período e comparativos mensais.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-mobile-alt"></i></div>
                    <h4>Responsivo</h4>
                    <p>Acesse de qualquer dispositivo: computador, tablet ou celular.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-card">
                <div class="cta-content">
                    <h2>Pronto para tomar controle do seu dinheiro?</h2>
                    <p>Comece hoje a transformar sua relação com as finanças.
                        Teste o FinnMestre e veja a diferença na sua vida.</p>
                    <div class="cta-buttons">
                        <a href="vendas.php" class="btn-cta-primary">
                            <i class="fas fa-rocket"></i>
                            Ver Planos e Preços
                        </a>
                    </div>
                </div>
                <div class="cta-visual">
                    <div class="cta-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="public-footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <div class="footer-logo">
                        <i class="fas fa-chart-line" style="color: var(--fm-primary);"></i>
                        <span class="logo-text">FinnMestre</span>
                    </div>
                    <p>Gestão financeira com disciplina e clareza.
                        Tome controle do seu dinheiro.</p>
                </div>
                <div class="footer-links">
                    <h4>Produto</h4>
                    <a href="#recursos">Recursos</a>
                    <a href="vendas.php">Preços</a>
                    <a href="#como-funciona">Como Funciona</a>
                </div>
                <div class="footer-links">
                    <h4>Suporte</h4>
                    <a href="mailto:suporte@finnmestre.com.br">Contato</a>
                    <a href="#">FAQ</a>
                    <a href="#">Termos de Uso</a>
                </div>
                <div class="footer-links">
                    <h4>Acesso</h4>
                    <a href="login.php">Entrar</a>
                    <a href="vendas.php">Assinar</a>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2026 FinnMestre. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>

    <script>
        // Mobile menu toggle
        document.getElementById('mobileToggle')?.addEventListener('click', function () {
            document.querySelector('.nav-links').classList.toggle('active');
        });

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
    </script>
</body>

</html>