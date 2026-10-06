<?php
/**
 * =====================================================
 * FinnMestre - Minha Assinatura
 * Gerenciamento de plano e status — Premium
 * =====================================================
 */
require_once 'includes/header.php';
require_once 'includes/assinaturas.php';

$userId = $_SESSION['usuario_id'] ?? null;
$assinatura = $userId ? obterAssinaturaUsuario($userId) : null;

$planos = [
    'mensal' => ['nome' => 'Mensal', 'preco' => 39.90, 'mensal' => 39.90, 'periodo' => '1 mês', 'desc' => 'Para começar a organizar'],
    'semestral' => ['nome' => 'Semestral', 'preco' => 197.90, 'mensal' => 32.98, 'periodo' => '6 meses', 'desc' => 'Compromisso com resultados'],
    'anual' => ['nome' => 'Anual', 'preco' => 297.90, 'mensal' => 24.83, 'periodo' => '12 meses', 'desc' => 'Melhor investimento']
];

$planoAtual = $assinatura ? ($assinatura['plano'] ?? null) : null;
$isAtivo = $assinatura && $assinatura['status'] === 'active';

// Calcular economia potencial do upgrade
$economiaMensal = 0;
$economiaAnual = 0;
if ($isAtivo && $planoAtual !== 'anual') {
    $precoAtualMensal = $planos[$planoAtual]['mensal'] ?? 39.90;
    $economiaMensal = $precoAtualMensal - $planos['anual']['mensal'];
    $economiaAnual = $economiaMensal * 12;
}

// Frases motivacionais
$frases = [
    "Investir R$ 24 por mês para organizar seu dinheiro é mais barato do que continuar perdendo controle.",
    "Disciplina hoje, liberdade amanhã.",
    "Quem controla o dinheiro, controla o futuro.",
    "O preço de não se organizar é muito maior do que qualquer assinatura.",
    "Cada real economizado é um passo mais perto da sua liberdade."
];
$fraseAleatoria = $frases[array_rand($frases)];
?>

<style>
    .upgrade-section {
        position: relative;
        overflow: hidden;
    }

    .upgrade-section::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: conic-gradient(from 0deg, transparent, rgba(139, 92, 246, 0.05), transparent, rgba(6, 214, 160, 0.05), transparent);
        animation: rotateGlow 8s linear infinite;
    }

    @keyframes rotateGlow {
        to {
            transform: rotate(360deg);
        }
    }

    .plan-card-new {
        background: var(--fm-card-bg, #1a1a2e);
        border: 2px solid rgba(255, 255, 255, 0.08);
        border-radius: 16px;
        padding: 28px 24px;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .plan-card-new:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.25);
    }

    .plan-card-new.destaque {
        border: 2px solid #06d6a0;
        background: linear-gradient(135deg, var(--fm-card-bg, #1a1a2e) 0%, rgba(6, 214, 160, 0.08) 100%);
        transform: scale(1.02);
    }

    .plan-card-new.destaque:hover {
        transform: scale(1.02) translateY(-4px);
        box-shadow: 0 16px 50px rgba(6, 214, 160, 0.2);
    }

    .selo-melhor {
        position: absolute;
        top: -1px;
        right: 20px;
        background: linear-gradient(135deg, #06d6a0, #059669);
        color: #000;
        font-weight: 800;
        font-size: 0.7rem;
        padding: 6px 14px 8px;
        border-radius: 0 0 10px 10px;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        box-shadow: 0 4px 15px rgba(6, 214, 160, 0.3);
    }

    .badge-economia {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(6, 214, 160, 0.12);
        border: 1px solid rgba(6, 214, 160, 0.3);
        color: #06d6a0;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.78rem;
        font-weight: 600;
    }

    .plan-preco {
        font-size: 2rem;
        font-weight: 900;
    }

    .plan-preco-mensal {
        font-size: 0.85rem;
        color: var(--fm-text-secondary, #a1a1aa);
    }

    .upgrade-cta {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        width: 100%;
        padding: 14px 24px;
        border-radius: 12px;
        font-weight: 700;
        font-size: 0.95rem;
        border: none;
        cursor: pointer;
        transition: all 0.3s;
        text-decoration: none;
        color: #000;
        background: linear-gradient(135deg, #06d6a0 0%, #34d399 100%);
        box-shadow: 0 4px 20px rgba(6, 214, 160, 0.3);
    }

    .upgrade-cta:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 30px rgba(6, 214, 160, 0.5);
    }

    .motivational-card {
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.08) 0%, rgba(6, 214, 160, 0.08) 100%);
        border: 1px solid rgba(139, 92, 246, 0.2);
        border-radius: 16px;
        padding: 24px;
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .motivational-card::before {
        content: '"';
        position: absolute;
        top: -10px;
        left: 16px;
        font-size: 5rem;
        color: rgba(139, 92, 246, 0.15);
        font-family: Georgia, serif;
    }

    .garantia-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(245, 158, 11, 0.12);
        border: 1px solid rgba(245, 158, 11, 0.3);
        color: #f59e0b;
        padding: 8px 16px;
        border-radius: 10px;
        font-size: 0.85rem;
        font-weight: 600;
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid var(--fm-border-color, rgba(255, 255, 255, 0.08));
    }

    .info-row:last-child {
        border-bottom: none;
    }

    .plan-atual-badge {
        background: linear-gradient(135deg, #8b5cf6, #6d28d9);
        color: white;
        padding: 3px 10px;
        border-radius: 12px;
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.3px;
    }
</style>

<div class="page-header">
    <h1><i class="fas fa-id-card" style="color: var(--fm-primary);"></i> Minha Assinatura</h1>
    <p>Gerencie seu plano e impulsione sua evolução financeira</p>
</div>

<!-- Frase Motivacional -->
<div class="motivational-card" style="margin-bottom: 24px;">
    <p
        style="font-size: 1.1rem; font-weight: 600; color: var(--fm-text-primary, #fff); position: relative; z-index: 1; font-style: italic; margin: 0;">
        "<?= $fraseAleatoria ?>"
    </p>
</div>

<div class="row" style="display: flex; gap: 24px; flex-wrap: wrap;">
    <!-- Status Atual -->
    <div class="col" style="flex: 1; min-width: 300px;">
        <div class="card">
            <div class="card-header">
                <span class="card-title"><i class="fas fa-shield-alt" style="margin-right: 6px;"></i> Status da
                    Assinatura</span>
            </div>

            <?php if ($assinatura): ?>
                <div style="padding: 24px;">
                    <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 24px;">
                        <div
                            style="width: 64px; height: 64px; background: rgba(139, 92, 246, 0.2); border-radius: 16px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-crown" style="font-size: 1.5rem; color: var(--fm-primary);"></i>
                        </div>
                        <div>
                            <h3 style="font-size: 1.25rem; margin-bottom: 4px;">
                                Plano <?= $planos[$assinatura['plano']]['nome'] ?? 'Premium' ?>
                            </h3>
                            <span class="badge badge-<?= getCorStatusAssinatura($assinatura['status']) ?>">
                                <?= getNomeStatusAssinatura($assinatura['status']) ?>
                            </span>
                        </div>
                    </div>

                    <div class="info-grid" style="display: grid; gap: 0;">
                        <div class="info-row">
                            <span style="color: var(--fm-text-secondary);">Valor do plano</span>
                            <strong><?= formatarMoeda($assinatura['valor'] ?? $planos[$assinatura['plano']]['preco']) ?></strong>
                        </div>
                        <div class="info-row">
                            <span style="color: var(--fm-text-secondary);">Custo por mês</span>
                            <strong
                                style="color: var(--fm-primary);"><?= formatarMoeda($planos[$assinatura['plano']]['mensal'] ?? 0) ?>/mês</strong>
                        </div>
                        <div class="info-row">
                            <span style="color: var(--fm-text-secondary);">Forma de pagamento</span>
                            <strong>
                                <?php
                                $formas = ['pix' => 'PIX', 'cartao' => 'Cartão', 'boleto' => 'Boleto', 'manual' => 'Manual'];
                                echo $formas[$assinatura['forma_pagamento']] ?? ucfirst($assinatura['forma_pagamento'] ?? 'N/A');
                                ?>
                            </strong>
                        </div>
                        <div class="info-row">
                            <span style="color: var(--fm-text-secondary);">Data de início</span>
                            <strong><?= $assinatura['created_at'] ? date('d/m/Y', strtotime($assinatura['created_at'])) : 'N/A' ?></strong>
                        </div>
                        <?php if ($assinatura['expires_at']): ?>
                            <div class="info-row">
                                <span style="color: var(--fm-text-secondary);">Válido até</span>
                                <strong><?= date('d/m/Y', strtotime($assinatura['expires_at'])) ?></strong>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($assinatura['status'] === 'pending_boleto'): ?>
                        <div class="alert alert-warning" style="margin-top: 24px;">
                            <i class="fas fa-clock"></i>
                            <div>
                                <strong>Aguardando pagamento do boleto</strong>
                                <p style="margin-top: 4px; font-size: 0.9rem;">
                                    Seu acesso será liberado automaticamente após a confirmação (até 1 dia útil).
                                </p>
                            </div>
                        </div>
                    <?php elseif ($assinatura['status'] === 'active'): ?>
                        <div class="alert alert-success" style="margin-top: 24px;">
                            <i class="fas fa-check-circle"></i>
                            <div>
                                <strong>Sua assinatura está ativa!</strong>
                                <p style="margin-top: 4px; font-size: 0.9rem;">
                                    Acesso completo a todos os recursos do FinnMestre.
                                </p>
                            </div>
                        </div>

                        <!-- Upgrade Inteligente (se não é anual) -->
                        <?php if ($planoAtual !== 'anual'): ?>
                            <div class="upgrade-section"
                                style="margin-top: 24px; background: linear-gradient(135deg, rgba(6,214,160,0.06), rgba(139,92,246,0.06)); border: 1px solid rgba(6,214,160,0.2); border-radius: 16px; padding: 24px; position: relative;">
                                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 16px;">
                                    <i class="fas fa-arrow-up" style="color: #06d6a0; font-size: 1.2rem;"></i>
                                    <h4 style="margin: 0; font-size: 1rem;">Faça o Upgrade Inteligente</h4>
                                </div>
                                <p style="color: var(--fm-text-secondary); font-size: 0.9rem; margin-bottom: 12px;">
                                    Migre para o <strong style="color: #06d6a0;">Plano Anual</strong> e economize
                                    <strong style="color: #06d6a0;"><?= formatarMoeda($economiaAnual) ?> por ano</strong>
                                    (<?= formatarMoeda($economiaMensal) ?>/mês a menos).
                                </p>
                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 16px;">
                                    <span class="badge-economia"><i class="fas fa-piggy-bank"></i> De
                                        <?= formatarMoeda($planos[$planoAtual]['mensal']) ?>/mês para apenas
                                        <?= formatarMoeda($planos['anual']['mensal']) ?>/mês</span>
                                </div>
                                <a href="vendas.php#planos" class="upgrade-cta">
                                    <i class="fas fa-rocket"></i> Fazer Upgrade Inteligente
                                </a>
                            </div>
                        <?php endif; ?>

                    <?php elseif ($assinatura['status'] === 'overdue' || $assinatura['status'] === 'canceled'): ?>
                        <div class="alert alert-danger" style="margin-top: 24px;">
                            <i class="fas fa-exclamation-triangle"></i>
                            <div>
                                <strong>Assinatura
                                    <?= $assinatura['status'] === 'overdue' ? 'expirada' : 'cancelada' ?></strong>
                                <p style="margin-top: 4px; font-size: 0.9rem;">
                                    Renove sua assinatura para continuar tendo acesso completo.
                                </p>
                            </div>
                        </div>
                        <a href="vendas.php#planos" class="upgrade-cta"
                            style="margin-top: 16px; background: linear-gradient(135deg, #8b5cf6, #6d28d9); color: white;">
                            <i class="fas fa-sync"></i> Renovar Assinatura
                        </a>
                    <?php endif; ?>

                    <div style="margin-top: 24px; padding-top: 16px; border-top: 1px solid rgba(255,255,255,0.05);">
                        <a href="suporte.php" style="font-size: 0.9rem; color: #8b95a5; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 8px; transition: 0.2s;" onmouseover="this.style.color='var(--fm-primary)'" onmouseout="this.style.color='#8b95a5'">
                            <i class="fas fa-question-circle"></i> Precisa de ajuda com sua assinatura?
                        </a>
                    </div>
                </div>
            <?php elseif (isAdmin()): ?>
                <div style="padding: 40px; text-align: center;">
                    <div
                        style="width: 80px; height: 80px; background: rgba(245, 158, 11, 0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px;">
                        <i class="fas fa-shield-alt" style="font-size: 2rem; color: #f59e0b;"></i>
                    </div>
                    <h3 style="margin-bottom: 12px; color: #f59e0b;">Administrador</h3>
                    <p style="color: var(--fm-text-secondary); margin-bottom: 24px;">
                        Você possui acesso total ao sistema como administrador.
                    </p>
                    <span class="badge"
                        style="background: linear-gradient(135deg, #f59e0b, #d97706); color: white; padding: 8px 20px; font-size: 0.9rem; border-radius: 20px;">
                        <i class="fas fa-crown"></i> Acesso Ilimitado
                    </span>
                </div>
            <?php else: ?>
                <div style="padding: 40px; text-align: center;">
                    <div
                        style="width: 80px; height: 80px; background: rgba(139,92,246,0.15); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px;">
                        <i class="fas fa-rocket" style="font-size: 2rem; color: var(--fm-primary);"></i>
                    </div>
                    <h3 style="margin-bottom: 12px;">Comece sua jornada financeira</h3>
                    <p style="color: var(--fm-text-secondary); margin-bottom: 24px;">
                        Organizar seu dinheiro custa menos do que um lanche por semana.
                    </p>
                    <a href="vendas.php#planos" class="upgrade-cta"
                        style="background: linear-gradient(135deg, #8b5cf6, #6d28d9); color: white;">
                        <i class="fas fa-rocket"></i> Assinar Agora
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Suporte -->
        <div class="card" style="margin-top: 24px;">
            <div class="card-header">
                <span class="card-title">Precisa de Ajuda?</span>
            </div>
            <div style="padding: 24px;">
                <p style="color: var(--fm-text-secondary); margin-bottom: 16px;">
                    Tem dúvidas sobre sua assinatura ou precisa de suporte?
                </p>
                <a href="suporte.php" class="btn"
                    style="width: 100%; background: linear-gradient(135deg, #06d6a0, #059669); color: white; border: none; font-weight: 600;">
                    <i class="fas fa-headset"></i> Contatar Suporte
                </a>
            </div>
        </div>
    </div>

    <!-- Planos Disponíveis -->
    <div class="col" style="flex: 1; min-width: 300px;">
        <div class="card">
            <div class="card-header">
                <span class="card-title"><i class="fas fa-gem" style="margin-right: 6px;"></i> Planos Disponíveis</span>
            </div>
            <div style="padding: 24px;">

                <?php foreach ($planos as $key => $plano):
                    $isAtual = $isAtivo && $planoAtual === $key;
                    $isAnual = $key === 'anual';
                    $econPerc = round(100 - ($plano['mensal'] / $planos['mensal']['mensal'] * 100));
                    ?>
                    <div class="plan-card-new <?= $isAnual ? 'destaque' : '' ?>"
                        style="margin-bottom: 16px; <?= $isAtual ? 'border-color: var(--fm-primary);' : '' ?>">
                        <?php if ($isAnual): ?>
                            <div class="selo-melhor"><i class="fas fa-trophy"></i> MELHOR ESCOLHA</div>
                        <?php endif; ?>

                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <div>
                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                                    <h4
                                        style="margin: 0; font-size: 1.05rem; color: <?= $isAnual ? '#06d6a0' : ($key === 'semestral' ? '#8b5cf6' : '#3b82f6') ?>;">
                                        <i class="fas fa-<?= $isAnual ? 'crown' : ($key === 'semestral' ? 'star' : 'circle') ?>"
                                            style="margin-right: 4px;"></i>
                                        <?= $plano['nome'] ?>
                                    </h4>
                                    <?php if ($isAtual): ?>
                                        <span class="plan-atual-badge">SEU PLANO</span>
                                    <?php endif; ?>
                                </div>
                                <p style="color: var(--fm-text-secondary, #a1a1aa); font-size: 0.82rem; margin: 0;">
                                    <?= $plano['desc'] ?></p>
                            </div>
                            <div style="text-align: right;">
                                <div class="plan-preco"
                                    style="color: <?= $isAnual ? '#06d6a0' : ($key === 'semestral' ? '#8b5cf6' : '#3b82f6') ?>;">
                                    <?= formatarMoeda($plano['preco']) ?>
                                </div>
                                <?php if ($key !== 'mensal'): ?>
                                    <div class="plan-preco-mensal"><?= formatarMoeda($plano['mensal']) ?>/mês</div>
                                <?php else: ?>
                                    <div class="plan-preco-mensal">/mês</div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if ($econPerc > 0): ?>
                            <div style="margin-top: 12px;">
                                <span class="badge-economia">
                                    <i class="fas fa-tag"></i> Economize <?= $econPerc ?>% em relação ao mensal
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <!-- Garantia -->
                <div style="text-align: center; margin-top: 20px;">
                    <div class="garantia-badge">
                        <i class="fas fa-shield-alt"></i> Garantia incondicional de 7 dias
                    </div>
                    <p style="color: var(--fm-text-secondary); font-size: 0.8rem; margin-top: 10px;">
                        <i class="fas fa-bolt" style="color: #f59e0b;"></i> Cartão e Pix → Acesso imediato
                    </p>
                </div>

                <?php if (!$isAtivo): ?>
                    <a href="vendas.php#planos" class="upgrade-cta"
                        style="margin-top: 16px; background: linear-gradient(135deg, #8b5cf6, #6d28d9); color: white;">
                        <i class="fas fa-rocket"></i> Ver Todos os Benefícios
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

</main>
<script src="assets/js/app.js"></script>
</body>

</html>