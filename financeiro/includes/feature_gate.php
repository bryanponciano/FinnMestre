<?php
/**
 * =====================================================
 * FinnMestre - Feature Gate Component
 * Exibe bloqueio visual para features premium
 * =====================================================
 * 
 * Uso: incluir e chamar exibirFeatureGate($feature, $titulo, $descricao)
 * Retorna true se a feature está bloqueada (para uso em if)
 */

/**
 * Verifica e exibe bloqueio de feature
 * @param string $feature Nome da feature (ex: 'contas_programadas')
 * @param string $titulo Título da feature bloqueada
 * @param string $descricao Descrição do que a feature faz
 * @return bool true se bloqueado, false se liberado
 */
function exibirFeatureGate($feature, $titulo = '', $descricao = '')
{
    $userId = $_SESSION['usuario_id'] ?? null;

    if (!$userId || usuarioTemFeature($userId, $feature)) {
        return false; // Feature liberada
    }

    $planoMinimo = planoMinimoPara($feature);
    $nomePlano = getNomePlano($planoMinimo);

    if (empty($titulo)) {
        $titulo = 'Recurso Premium';
    }
    if (empty($descricao)) {
        $descricao = 'Este recurso está disponível a partir do plano ' . $nomePlano . '.';
    }

    ?>
    <div class="feature-gate-overlay" style="
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 400px;
        padding: 60px 24px;
        text-align: center;
    ">
        <div style="
            width: 100px;
            height: 100px;
            background: rgba(139, 92, 246, 0.15);
            border: 2px solid rgba(139, 92, 246, 0.3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 32px;
        ">
            <i class="fas fa-lock" style="font-size: 2.5rem; color: var(--fm-primary);"></i>
        </div>

        <h2 style="font-size: 1.75rem; font-weight: 800; margin-bottom: 12px;">
            <?= htmlspecialchars($titulo) ?>
        </h2>

        <p style="color: var(--fm-text-secondary); font-size: 1.05rem; margin-bottom: 8px; max-width: 500px;">
            <?= htmlspecialchars($descricao) ?>
        </p>

        <div style="
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(139, 92, 246, 0.1);
            border: 1px solid rgba(139, 92, 246, 0.3);
            border-radius: 50px;
            padding: 8px 20px;
            margin-bottom: 32px;
            color: var(--fm-primary-light, #a78bfa);
            font-weight: 600;
            font-size: 0.9rem;
        ">
            <i class="fas fa-crown"></i>
            Disponível no plano
            <?= htmlspecialchars($nomePlano) ?> ou superior
        </div>

        <a href="vendas.php#planos" class="btn btn-primary" style="
            padding: 16px 40px;
            font-size: 1.1rem;
            gap: 10px;
        ">
            <i class="fas fa-rocket"></i>
            Fazer Upgrade
        </a>

        <p style="color: var(--fm-text-muted, #666); font-size: 0.85rem; margin-top: 16px;">
            <i class="fas fa-shield-alt"></i> 7 dias de garantia incondicional
        </p>
    </div>
    <?php

    return true; // Feature bloqueada
}

/**
 * Verifica se uma feature está bloqueada (sem exibir UI)
 * @return bool true se bloqueado
 */
function featureBloqueada($feature)
{
    $userId = $_SESSION['usuario_id'] ?? null;
    if (!$userId)
        return true;
    return !usuarioTemFeature($userId, $feature);
}
