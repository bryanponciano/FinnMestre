<?php
/**
 * =====================================================
 * FinnMestre - Funções de Assinatura
 * Gerenciamento de planos e status
 * =====================================================
 */

/**
 * Auto-migração: cria tabelas de assinatura se não existirem
 */
function executarMigracaoAssinaturas()
{
    global $pdo;

    try {
        // Tabela de assinaturas
        $pdo->exec("CREATE TABLE IF NOT EXISTS assinaturas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT NOT NULL,
            plano ENUM('mensal', 'semestral', 'anual') DEFAULT 'mensal',
            status ENUM('active', 'pending', 'pending_boleto', 'canceled', 'overdue') DEFAULT 'pending',
            forma_pagamento VARCHAR(50),
            valor DECIMAL(10,2),
            payment_id VARCHAR(255),
            expires_at DATETIME,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_usuario (usuario_id),
            INDEX idx_status (status)
        )");

        // Tabela de eventos de pagamento (log)
        $pdo->exec("CREATE TABLE IF NOT EXISTS payment_events (
            id INT AUTO_INCREMENT PRIMARY KEY,
            payment_id VARCHAR(255),
            usuario_id INT,
            status VARCHAR(50),
            tipo_evento VARCHAR(50),
            payload JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_payment (payment_id),
            INDEX idx_usuario (usuario_id)
        )");

        // Adicionar coluna apelido na tabela usuarios se não existir
        try {
            $pdo->exec("ALTER TABLE usuarios ADD COLUMN apelido VARCHAR(100) DEFAULT NULL");
        } catch (Exception $e) {
            // Coluna já existe
        }
    } catch (Exception $e) {
        error_log("Erro na migração de assinaturas: " . $e->getMessage());
    }
}

// Executar migração
executarMigracaoAssinaturas();

// ==========================================
// FEATURE GATING POR PLANO
// ==========================================

/**
 * Mapa de features por plano
 * Cada feature lista os planos que têm acesso
 */
define('FEATURES_POR_PLANO', [
    'dashboard' => ['mensal', 'semestral', 'anual'],
    'transacoes' => ['mensal', 'semestral', 'anual'],
    'contas' => ['mensal', 'semestral', 'anual'],
    'metas' => ['mensal', 'semestral', 'anual'],
    'custo_tempo_vida' => ['mensal', 'semestral', 'anual'],
    'perfis_ilimitados' => ['semestral', 'anual'],
    'relatorios_avancados' => ['semestral', 'anual'],
    'exportacao' => ['anual'],
    'suporte_whatsapp' => ['semestral', 'anual'],
]);

/**
 * E-mail do administrador (acesso total sempre)
 */
define('ADMIN_EMAIL', 'poncianobryan988@gmail.com');

/**
 * Verifica se o usuário atual é admin
 */
function isAdmin()
{
    return isset($_SESSION['usuario_email']) && $_SESSION['usuario_email'] === ADMIN_EMAIL;
}

/**
 * Obtém o plano ativo do usuário
 * Retorna 'mensal', 'semestral', 'anual' ou null
 */
function obterPlanoUsuario($userId)
{
    $assinatura = obterAssinaturaUsuario($userId);

    if (!$assinatura)
        return null;

    if ($assinatura['status'] === 'active') {
        // Verificar expiração
        if ($assinatura['expires_at'] && strtotime($assinatura['expires_at']) < time()) {
            atualizarStatusAssinatura($assinatura['id'], 'overdue');
            return null;
        }
        return $assinatura['plano'];
    }

    return null;
}

/**
 * Verifica se o usuário tem acesso a determinada feature
 */
function usuarioTemFeature($userId, $feature)
{
    // Admin tem acesso total
    if (isAdmin())
        return true;

    $plano = obterPlanoUsuario($userId);
    if (!$plano)
        return false;

    $featuresPermitidas = FEATURES_POR_PLANO[$feature] ?? [];
    return in_array($plano, $featuresPermitidas);
}

/**
 * Retorna o limite de perfis do usuário
 */
function obterLimitePerfis($userId)
{
    if (isAdmin())
        return 999;

    $plano = obterPlanoUsuario($userId);
    if (!$plano)
        return 0;

    return in_array($plano, ['semestral', 'anual']) ? 999 : 3;
}

/**
 * Retorna nome amigável do plano
 */
function getNomePlano($plano)
{
    $nomes = [
        'mensal' => 'Mensal',
        'semestral' => 'Semestral',
        'anual' => 'Anual'
    ];
    return $nomes[$plano] ?? 'Nenhum';
}

/**
 * Retorna o plano mínimo para uma feature
 */
function planoMinimoPara($feature)
{
    $planos = FEATURES_POR_PLANO[$feature] ?? [];
    if (empty($planos))
        return 'anual';

    $ordem = ['mensal' => 1, 'semestral' => 2, 'anual' => 3];
    $menor = 'anual';
    foreach ($planos as $p) {
        if (($ordem[$p] ?? 99) < ($ordem[$menor] ?? 99)) {
            $menor = $p;
        }
    }
    return $menor;
}

/**
 * Cria assinatura manual (admin) sem pagamento
 */
function criarAssinaturaManual($userId, $plano)
{
    global $pdo;

    // Cancelar assinaturas anteriores
    $pdo->prepare("UPDATE assinaturas SET status = 'canceled' WHERE usuario_id = ? AND status = 'active'")
        ->execute([$userId]);

    $dias = obterDuracaoPlano($plano);
    $valor = obterPrecoPlano($plano);

    $stmt = $pdo->prepare("INSERT INTO assinaturas (usuario_id, plano, status, forma_pagamento, valor, expires_at) 
                           VALUES (?, ?, 'active', 'admin', ?, DATE_ADD(NOW(), INTERVAL ? DAY))");
    return $stmt->execute([$userId, $plano, $valor, $dias]);
}

/**
 * Preços dos planos
 */
function obterPrecoPlano($plano)
{
    $precos = [
        'mensal' => 39.90,
        'semestral' => 197.90,
        'anual' => 297.90
    ];
    return $precos[$plano] ?? 39.90;
}

/**
 * Duração em dias
 */
function obterDuracaoPlano($plano)
{
    $duracoes = [
        'mensal' => 30,
        'semestral' => 180,
        'anual' => 365
    ];
    return $duracoes[$plano] ?? 30;
}

/**
 * Obtém assinatura ativa do usuário
 */
function obterAssinaturaUsuario($userId)
{
    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM assinaturas WHERE usuario_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

/**
 * Verifica se usuário tem acesso ativo
 */
function usuarioTemAcesso($userId)
{
    $assinatura = obterAssinaturaUsuario($userId);

    if (!$assinatura) {
        return false;
    }

    if ($assinatura['status'] === 'active') {
        // Verificar se não expirou
        if ($assinatura['expires_at'] && strtotime($assinatura['expires_at']) < time()) {
            // Marcar como expirada
            atualizarStatusAssinatura($assinatura['id'], 'overdue');
            return false;
        }
        return true;
    }

    return false;
}

/**
 * Atualiza status da assinatura
 */
function atualizarStatusAssinatura($assinaturaId, $status, $paymentId = null)
{
    global $pdo;

    $sql = "UPDATE assinaturas SET status = ?, updated_at = NOW()";
    $params = [$status];

    if ($paymentId) {
        $sql .= ", payment_id = ?";
        $params[] = $paymentId;
    }

    if ($status === 'active') {
        // Calcular data de expiração
        $stmt = $pdo->prepare("SELECT plano FROM assinaturas WHERE id = ?");
        $stmt->execute([$assinaturaId]);
        $assinatura = $stmt->fetch();
        $dias = obterDuracaoPlano($assinatura['plano']);
        $sql .= ", expires_at = DATE_ADD(NOW(), INTERVAL ? DAY)";
        $params[] = $dias;
    }

    $sql .= " WHERE id = ?";
    $params[] = $assinaturaId;

    $stmt = $pdo->prepare($sql);
    return $stmt->execute($params);
}

/**
 * Registra evento de pagamento
 */
function registrarEventoPagamento($paymentId, $userId, $status, $tipoEvento, $payload)
{
    global $pdo;

    $stmt = $pdo->prepare("INSERT INTO payment_events (payment_id, usuario_id, status, tipo_evento, payload) 
                           VALUES (?, ?, ?, ?, ?)");
    return $stmt->execute([
        $paymentId,
        $userId,
        $status,
        $tipoEvento,
        json_encode($payload)
    ]);
}

/**
 * Processa confirmação de pagamento (chamado pelo webhook)
 */
function processarConfirmacaoPagamento($paymentId, $status, $payload = [])
{
    global $pdo;

    // Buscar assinatura pelo payment_id
    $stmt = $pdo->prepare("SELECT * FROM assinaturas WHERE payment_id = ?");
    $stmt->execute([$paymentId]);
    $assinatura = $stmt->fetch();

    if (!$assinatura) {
        // Tentar encontrar pendente sem payment_id
        return false;
    }

    // Registrar evento
    registrarEventoPagamento($paymentId, $assinatura['usuario_id'], $status, 'webhook', $payload);

    // Atualizar status conforme resultado
    switch ($status) {
        case 'approved':
            atualizarStatusAssinatura($assinatura['id'], 'active', $paymentId);
            return true;

        case 'pending':
        case 'in_process':
            // Manter como pendente
            return true;

        case 'rejected':
        case 'cancelled':
            atualizarStatusAssinatura($assinatura['id'], 'canceled', $paymentId);
            return false;

        default:
            return false;
    }
}

/**
 * Obtém nome amigável do status
 */
function getNomeStatusAssinatura($status)
{
    $nomes = [
        'active' => 'Ativa',
        'pending' => 'Aguardando Pagamento',
        'pending_boleto' => 'Aguardando Boleto',
        'canceled' => 'Cancelada',
        'overdue' => 'Expirada'
    ];
    return $nomes[$status] ?? 'Desconhecido';
}

/**
 * Obtém cor do badge do status
 */
function getCorStatusAssinatura($status)
{
    $cores = [
        'active' => 'success',
        'pending' => 'warning',
        'pending_boleto' => 'warning',
        'canceled' => 'danger',
        'overdue' => 'danger'
    ];
    return $cores[$status] ?? 'secondary';
}
