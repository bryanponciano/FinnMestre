<?php
/**
 * =====================================================
 * FinnMestre - Header do Sistema
 * =====================================================
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/perfis.php';
require_once __DIR__ . '/feature_gate.php';

$paginaAtual = basename($_SERVER['PHP_SELF'], '.php');
$alertasNaoLidos = contarAlertasNaoLidos();
$alertas = listarAlertas(true, 5);
$finbotConfig = obterConfiguracao('finbot_ativo', true);
$finbotMensagem = $finbotConfig ? obterMensagemFinBot() : null;

// Perfis
$perfilAtual = obterPerfilAtual();
$listaPerfis = listarPerfis();
$modoConsolidado = isModoConsolidado();

// Perfil atual já foi carregado
$usuario_foto = null; // Removido
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="FinnMestre - Sistema de Controle Financeiro Inteligente">
    <meta name="theme-color" content="#10b981">
    <title>FinnMestre - Controle Financeiro Inteligente</title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml"
        href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Cdefs%3E%3ClinearGradient id='g' x1='0%25' y1='0%25' x2='100%25' y2='100%25'%3E%3Cstop offset='0%25' stop-color='%231e293b'/%3E%3Cstop offset='100%25' stop-color='%230f172a'/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width='100' height='100' rx='20' fill='url(%23g)'/%3E%3Ctext x='50' y='70' font-size='55' text-anchor='middle'%3E%F0%9F%A4%96%3C/text%3E%3C/svg%3E">

    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
    <link rel="stylesheet" href="assets/css/behavioral.css?v=<?= time() ?>">
    <link rel="stylesheet" href="assets/css/theme-light.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Desktop padding */
        @media (min-width: 1025px) {
            .main-container-padding {
                padding: 0 40px;
            }
        }
    </style>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Configuração de Moeda -->
    <script>
        <?php $moedaAtual = getMoedaAtual(); ?>
        const MOEDA_CONFIG = {
            simbolo: '<?= $moedaAtual['simbolo'] ?>',
            decimal: '<?= $moedaAtual['decimal'] ?>',
            milhares: '<?= $moedaAtual['milhares'] ?>'
        };
    </script>
    <script src="assets/js/moeda.js"></script>
    <script src="assets/js/moeda-input.js"></script>
    <script src="assets/js/behavioral.js"></script>
</head>

<body>
    <div class="layout-wrapper">
        <aside class="sidebar">
            <!-- Logo -->
            <a href="index.php" class="logo">
                <div class="logo-icon">
                    <i class="fas fa-robot"></i>
                </div>
                <span class="logo-text">FinnMestre</span>
            </a>

            <!-- Menu Mobile Toggle (caso necessário) -->
            <button class="mobile-menu-toggle" onclick="toggleMobileMenu()" id="mobileMenuToggle" style="display: none; background: transparent; border: none; color: white; font-size: 1.5rem;">
                <i class="fas fa-bars"></i>
            </button>

            <!-- Categoria Principal -->
            <div class="sidebar-category">Gerenciar</div>
            <ul class="nav-links" id="navLinksManage">
                <li>
                    <a href="index.php" class="<?= $paginaAtual === 'index' ? 'active' : '' ?>">
                        <i class="fas fa-border-all"></i>
                        <span><?= __('dashboard') ?></span>
                    </a>
                </li>
                <li>
                    <a href="contas.php" class="<?= $paginaAtual === 'contas' ? 'active' : '' ?>">
                        <i class="fas fa-wallet"></i>
                        <span><?= __('contas') ?></span>
                    </a>
                </li>
                <li>
                    <a href="transacoes.php" class="<?= $paginaAtual === 'transacoes' ? 'active' : '' ?>">
                        <i class="fas fa-exchange-alt"></i>
                        <span><?= __('transacoes') ?></span>
                    </a>
                </li>
                <li>
                    <a href="metas.php" class="<?= $paginaAtual === 'metas' ? 'active' : '' ?>">
                        <i class="fas fa-bullseye"></i>
                        <span><?= __('metas') ?></span>
                    </a>
                </li>
                <li>
                    <a href="poupanca.php" class="<?= $paginaAtual === 'poupanca' ? 'active' : '' ?>">
                        <i class="fas fa-piggy-bank"></i>
                        <span>Poupança</span>
                    </a>
                </li>
                <?php if (!featureBloqueada('relatorios_avancados')): ?>
                <li>
                    <a href="relatorios.php" class="<?= $paginaAtual === 'relatorios' ? 'active' : '' ?>">
                        <i class="fas fa-chart-line"></i>
                        <span><?= __('relatorios') ?></span>
                    </a>
                </li>
                <?php endif; ?>
            </ul>

            <!-- Categoria Inteligência -->
            <div class="sidebar-category">Inteligência</div>
            <ul class="nav-links" id="navLinksIA">
                <li>
                    <a href="assistente.php" class="<?= $paginaAtual === 'assistente' ? 'active' : '' ?>">
                        <i class="fas fa-robot"></i>
                        <span>Assistente IA</span>
                    </a>
                </li>
            </ul>

            <!-- Categoria Preferências -->
            <div class="sidebar-category">Preferências</div>
            <ul class="nav-links" id="navLinksPrefs">
                <li>
                    <a href="configuracoes.php" class="<?= $paginaAtual === 'configuracoes' ? 'active' : '' ?>">
                        <i class="fas fa-cog"></i>
                        <span><?= __('configuracoes') ?></span>
                    </a>
                </li>
                <li>
                    <a href="assinatura.php" class="<?= $paginaAtual === 'assinatura' ? 'active' : '' ?>">
                        <i class="fas fa-crown"></i>
                        <span>Assinatura</span>
                    </a>
                </li>
                <li>
                    <a href="suporte.php" class="<?= $paginaAtual === 'suporte' ? 'active' : '' ?>">
                        <i class="fas fa-headset"></i>
                        <span>Suporte</span>
                    </a>
                </li>
                <?php if (function_exists('isAdmin') && isAdmin()): ?>
                    <li>
                        <a href="admin.php" class="<?= $paginaAtual === 'admin' ? 'active' : '' ?>" style="color: #f59e0b;">
                            <i class="fas fa-shield-alt"></i>
                            <span>Admin</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>

            <!-- Spacer para empurrar logout ao fundo -->
            <div class="sidebar-spacer" style="flex: 1;"></div>

            <!-- Logout no final da sidebar -->
            <div id="logoutContainer" class="logout-container" style="border-top: 1px solid rgba(255,255,255,0.06); padding: 16px; margin-bottom: 40px;">
                <a href="logout.php" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 10px; text-decoration: none; color: #ef4444; font-size: 0.95rem; font-weight: 500; transition: all 0.2s; background: rgba(239,68,68,0.05);" onmouseover="this.style.background='rgba(239,68,68,0.12)'" onmouseout="this.style.background='rgba(239,68,68,0.05)'">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Sair</span>
                </a>
            </div>
        </aside>

        <!-- Container Principal -->
        <main class="main-content">

            <header class="main-header">
                <div class="header-title">
                    <h1 id="greeting">Olá, <?= htmlspecialchars(explode(' ', $_SESSION['usuario_nome'] ?? 'Visitante')[0]) ?>!</h1>
                    <p>Aqui está o resumo das suas finanças hoje.</p>
                </div>
                
                <div class="header-actions-wrapper" style="display: flex; gap: 20px; align-items: center;">
                    <div class="header-search">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Pesquisar...">
                    </div>

                    <!-- Área do Usuário (Migrado do antigo topo) -->
                    <div class="nav-user">
                        <!-- Botão de Alertas -->
                        <div style="position: relative;">
                            <button class="btn-alerts" onclick="toggleAlertas()" id="btnAlertas" title="Alertas">
                                <i class="fas fa-bell"></i>
                                <?php if ($alertasNaoLidos > 0): ?>
                                    <span class="alert-count"><?= $alertasNaoLidos > 9 ? '9+' : $alertasNaoLidos ?></span>
                                <?php endif; ?>
                            </button>

                            <!-- Dropdown de Alertas -->
                            <div class="alerts-dropdown" id="alertasDropdown">
                                <div class="alerts-dropdown-header">
                                    <h4><i class="fas fa-bell"></i> Notificações</h4>
                                    <?php if ($alertasNaoLidos > 0): ?>
                                        <button class="btn btn-ghost btn-sm" onclick="marcarTodosLidos()">
                                            Marcar todas
                                        </button>
                                    <?php endif; ?>
                                </div>
                                <div class="alerts-dropdown-body">
                                    <?php if (empty($alertas)): ?>
                                        <div class="empty-state" style="padding: 30px;">
                                            <i class="fas fa-check-circle" style="font-size: 2rem;"></i>
                                            <p style="margin-top: 10px;">Nenhuma notificação</p>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($alertas as $alerta): ?>
                                            <div class="alert-item" onclick="window.location='<?= $alerta['acao_url'] ?? '#' ?>'">
                                                <div class="alert-item-icon"
                                                    style="background: <?= $alerta['cor'] ?>20; color: <?= $alerta['cor'] ?>;">
                                                    <i class="fas <?= $alerta['icone'] ?>"></i>
                                                </div>
                                                <div class="alert-item-content">
                                                    <div class="alert-item-title"><?= htmlspecialchars($alerta['titulo']) ?></div>
                                                    <div class="alert-item-text"><?= htmlspecialchars($alerta['mensagem']) ?></div>
                                                    <div class="alert-item-time">
                                                        <i class="fas fa-clock"></i>
                                                        <?= date('d/m H:i', strtotime($alerta['created_at'])) ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Foto do Usuário e Perfil Selector -->
                        <div class="profile-selector-wrapper">
                            <button class="btn-profile" onclick="toggleProfileDropdown()" id="btnProfile" style="border: none; background: transparent; padding: 0;">
                                <div style="width: 44px; height: 44px; border-radius: 12px; background: <?= $perfilAtual['cor'] ?? 'var(--fm-primary)' ?>; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.2rem;">
                                    <i class="fas <?= $perfilAtual['icone'] ?? 'fa-user' ?>"></i>
                                </div>
                            </button>

                            <!-- Dropdown de Perfis -->
                            <div class="profile-dropdown" id="profileDropdown" style="right: 0;">
                                <div class="profile-dropdown-header">
                                    <h4><i class="fas fa-users"></i> Seus Perfis</h4>
                                </div>
                                <div class="profile-dropdown-list">
                                    <?php foreach ($listaPerfis as $p): ?>
                                        <a href="api/perfis.php?acao=trocar&id=<?= $p['id'] ?>"
                                            class="profile-dropdown-item <?= ($perfilAtual && $p['id'] == $perfilAtual['id'] && !$modoConsolidado) ? 'active' : '' ?>">
                                            <div class="profile-item-badge" style="background: <?= $p['cor'] ?>;">
                                                <i class="fas <?= $p['icone'] ?>"></i>
                                            </div>
                                            <span><?= htmlspecialchars($p['nome']) ?></span>
                                            <?php if ($perfilAtual && $p['id'] == $perfilAtual['id'] && !$modoConsolidado): ?>
                                                <i class="fas fa-check" style="margin-left: auto; color: var(--success);"></i>
                                            <?php endif; ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                                <div class="profile-dropdown-footer">
                                    <a href="perfis.php" class="btn btn-primary btn-sm" style="width: 100%;">
                                        <i class="fas fa-cog"></i> Gerenciar Perfis
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Botão Logout (Mover para a sidebar no fim ou deixar aqui) -->
                    </div>
                </div>
            </header>

            <div class="container-fluid main-container-padding">

        <?php if ($finbotConfig && $finbotMensagem): ?>
            <!-- FinBot - Mascote Animado com Olhos que Seguem o Mouse -->
            <div class="finbot-container" id="finbotContainer">
                <!-- Mensagens do FinBot -->
                <div class="finbot-bubble" id="finbotBubble" style="display: none;">
                    <button onclick="fecharFinBot()"
                        style="position: absolute; top: 8px; right: 8px; background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1rem;">
                        <i class="fas fa-times"></i>
                    </button>
                    <p style="font-weight: 600; margin-bottom: 8px; color: var(--text-secondary);">
                        <?= $finbotMensagem['saudacao'] ?>
                    </p>
                    <?php if (!empty($finbotMensagem['mensagens'])): ?>
                        <?php $msg = $finbotMensagem['mensagens'][0]; ?>
                        <div class="finbot-message">
                            <div class="finbot-message-icon"
                                style="background: <?= $msg['cor'] ?>20; color: <?= $msg['cor'] ?>;">
                                <i class="fas <?= $msg['icone'] ?>"></i>
                            </div>
                            <div class="finbot-message-text">
                                <?= $msg['texto'] ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Avatar do FinBot com Olhos Interativos -->
                <div class="finbot-avatar" id="finbotAvatar" onclick="toggleFinBot()"
                    title="Clique para ver dicas do FinBot">
                    <div class="finbot-face">
                        <div class="finbot-antenna"></div>
                        <div class="finbot-eyes">
                            <div class="finbot-eye"
                                style="width: 14px; height: 14px; display: flex; align-items: center; justify-content: center;">
                                <div class="finbot-pupil" id="pupilLeft"
                                    style="width: 6px; height: 6px; background: #1e293b; border-radius: 50%; transition: transform 0.1s ease;">
                                </div>
                            </div>
                            <div class="finbot-eye"
                                style="width: 14px; height: 14px; display: flex; align-items: center; justify-content: center;">
                                <div class="finbot-pupil" id="pupilRight"
                                    style="width: 6px; height: 6px; background: #1e293b; border-radius: 50%; transition: transform 0.1s ease;">
                                </div>
                            </div>
                        </div>
                        <div class="finbot-mouth"></div>
                    </div>
                </div>
            </div>

            <!-- Script para olhos seguirem o mouse -->
            <script>
                (function () {
                    const avatar = document.getElementById('finbotAvatar');
                    const pupilLeft = document.getElementById('pupilLeft');
                    const pupilRight = document.getElementById('pupilRight');

                    if (avatar && pupilLeft && pupilRight) {
                        document.addEventListener('mousemove', function (e) {
                            const rect = avatar.getBoundingClientRect();
                            const avatarCenterX = rect.left + rect.width / 2;
                            const avatarCenterY = rect.top + rect.height / 2;

                            const angle = Math.atan2(e.clientY - avatarCenterY, e.clientX - avatarCenterX);
                            const distance = Math.min(3, Math.hypot(e.clientX - avatarCenterX, e.clientY - avatarCenterY) / 80);

                            const moveX = Math.cos(angle) * distance;
                            const moveY = Math.sin(angle) * distance;

                            pupilLeft.style.transform = 'translate(' + moveX + 'px, ' + moveY + 'px)';
                            pupilRight.style.transform = 'translate(' + moveX + 'px, ' + moveY + 'px)';
                        });
                    }
                })();
            </script>
        <?php endif; ?>

        <script>
            // ==========================================
            // FUNÇÕES GLOBAIS DO HEADER
            // ==========================================

            // Toggle Alertas Dropdown
            function toggleAlertas() {
                const dropdown = document.getElementById('alertasDropdown');
                dropdown.classList.toggle('active');
                // Fechar dropdown de perfis
                document.getElementById('profileDropdown')?.classList.remove('active');
            }

            // Toggle Profile Dropdown
            function toggleProfileDropdown() {
                const dropdown = document.getElementById('profileDropdown');
                dropdown.classList.toggle('active');
                // Fechar dropdown de alertas
                document.getElementById('alertasDropdown')?.classList.remove('active');
            }

            // Fechar dropdowns ao clicar fora
            document.addEventListener('click', function (e) {
                const btnAlertas = document.getElementById('btnAlertas');
                const dropdownAlertas = document.getElementById('alertasDropdown');
                const btnProfile = document.getElementById('btnProfile');
                const dropdownProfile = document.getElementById('profileDropdown');

                if (btnAlertas && dropdownAlertas && !btnAlertas.contains(e.target) && !dropdownAlertas.contains(e.target)) {
                    dropdownAlertas.classList.remove('active');
                }
                if (btnProfile && dropdownProfile && !btnProfile.contains(e.target) && !dropdownProfile.contains(e.target)) {
                    dropdownProfile.classList.remove('active');
                }
            });

            // Marcar todos alertas como lidos
            function marcarTodosLidos() {
                fetch('api/alertas.php?acao=marcar_todos')
                    .then(r => r.json())
                    .then(d => {
                        if (d.sucesso) {
                            document.querySelector('.alert-count')?.remove();
                            document.querySelectorAll('.alert-item').forEach(el => el.style.opacity = '0.6');
                        }
                    });
            }

            // FinBot Functions
            let finbotAberto = false;

            function toggleFinBot() {
                const bubble = document.getElementById('finbotBubble');
                const avatar = document.querySelector('.finbot-avatar');

                finbotAberto = !finbotAberto;
                bubble.style.display = finbotAberto ? 'block' : 'none';

                if (finbotAberto) {
                    avatar.classList.add('finbot-celebration');
                    setTimeout(() => avatar.classList.remove('finbot-celebration'), 500);
                }
            }

            function fecharFinBot() {
                document.getElementById('finbotBubble').style.display = 'none';
                finbotAberto = false;
            }

            // Auto-mostrar FinBot após 3 segundos na primeira visita
            setTimeout(() => {
                if (!sessionStorage.getItem('finbot_shown')) {
                    const bubble = document.getElementById('finbotBubble');
                    if (bubble) {
                        bubble.style.display = 'block';
                        finbotAberto = true;
                        sessionStorage.setItem('finbot_shown', '1');
                    }
                }
            }, 3000);

            // Modal Functions
            function openModal(id) {
                document.getElementById(id).classList.add('active');
                document.body.style.overflow = 'hidden';
            }

            function closeModal(id) {
                document.getElementById(id).classList.remove('active');
                document.body.style.overflow = '';
            }

            // Fechar modal com ESC
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    document.querySelectorAll('.modal-overlay.active').forEach(el => {
                        el.classList.remove('active');
                    });
                    document.body.style.overflow = '';
                }
            });

            // Fechar modal ao clicar no overlay
            document.addEventListener('click', function (e) {
                if (e.target.classList.contains('modal-overlay')) {
                    e.target.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });

            // Mobile Menu Toggle
            function toggleMobileMenu() {
                const navLinks = document.querySelectorAll('.nav-links');
                const categories = document.querySelectorAll('.sidebar-category');
                const logoutContainer = document.getElementById('logoutContainer');
                const toggle = document.getElementById('mobileMenuToggle');
                
                let isActive = false;
                navLinks.forEach(nav => {
                    nav.classList.toggle('active');
                    isActive = nav.classList.contains('active');
                });
                
                categories.forEach(cat => cat.classList.toggle('active', isActive));
                if (logoutContainer) logoutContainer.classList.toggle('active', isActive);

                if (toggle) {
                    const icon = toggle.querySelector('i');
                    if (isActive) {
                        icon.className = 'fas fa-times';
                    } else {
                        icon.className = 'fas fa-bars';
                    }
                }
            }

            // Handle responsive navigation
            function handleResponsiveNav() {
                const mobileToggle = document.getElementById('mobileMenuToggle');
                const navLinks = document.querySelectorAll('.nav-links');
                const categories = document.querySelectorAll('.sidebar-category');
                const logoutContainer = document.getElementById('logoutContainer');

                if (window.innerWidth <= 1024) {
                    if (mobileToggle) mobileToggle.style.display = 'flex';
                    if (logoutContainer && !logoutContainer.classList.contains('active')) {
                        logoutContainer.style.setProperty('--logout-display', 'none');
                    }
                } else {
                    if (mobileToggle) mobileToggle.style.display = 'none';
                    navLinks.forEach(nav => nav.classList.remove('active'));
                    categories.forEach(cat => cat.classList.remove('active'));
                    if (logoutContainer) {
                        logoutContainer.classList.remove('active');
                        logoutContainer.style.setProperty('--logout-display', 'block');
                    }
                }
            }

            // Run on load and resize
            handleResponsiveNav();
            window.addEventListener('resize', handleResponsiveNav);

            // Close mobile menu when clicking outside
            document.addEventListener('click', function (e) {
                if (window.innerWidth <= 1024) {
                    const sidebar = document.querySelector('.sidebar');
                    const toggle = document.getElementById('mobileMenuToggle');
                    
                    let isMenuOpen = false;
                    document.querySelectorAll('.nav-links').forEach(nav => {
                        if (nav.classList.contains('active')) isMenuOpen = true;
                    });
                    
                    if (isMenuOpen && sidebar && !sidebar.contains(e.target)) {
                        document.querySelectorAll('.nav-links').forEach(nav => nav.classList.remove('active'));
                        document.querySelectorAll('.sidebar-category').forEach(cat => cat.classList.remove('active'));
                        
                        const logoutContainer = document.getElementById('logoutContainer');
                        if (logoutContainer) {
                            logoutContainer.classList.remove('active');
                            logoutContainer.style.setProperty('--logout-display', 'none');
                        }
                        
                        if (toggle) toggle.querySelector('i').className = 'fas fa-bars';
                    }
                }
            });
        </script>
