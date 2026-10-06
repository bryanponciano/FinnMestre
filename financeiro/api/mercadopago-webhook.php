<?php
/**
 * =====================================================
 * FinnMestre - Mercado Pago Webhook
 * Recebe notificações de pagamento
 * =====================================================
 */

// Desabilitar output de erros
error_reporting(0);
ini_set('display_errors', 0);

// Log de debug
function logWebhook($message, $data = null)
{
    $logFile = __DIR__ . '/../logs/mercadopago.log';
    $dir = dirname($logFile);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $timestamp = date('Y-m-d H:i:s');
    $log = "[{$timestamp}] {$message}";
    if ($data) {
        $log .= "\n" . json_encode($data, JSON_PRETTY_PRINT);
    }
    $log .= "\n---\n";

    file_put_contents($logFile, $log, FILE_APPEND);
}

// Headers
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../includes/assinaturas.php';
    require_once __DIR__ . '/../includes/mercadopago.php';

    // Obter dados da requisição
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    logWebhook('Webhook recebido', $data);

    // Verificar tipo de notificação
    $type = $data['type'] ?? $_GET['type'] ?? null;
    $topic = $data['topic'] ?? $_GET['topic'] ?? null;

    if ($type === 'payment' || $topic === 'payment') {
        $paymentId = $data['data']['id'] ?? $_GET['data_id'] ?? null;

        if ($paymentId) {
            // Consultar detalhes do pagamento
            $pagamento = consultarPagamentoMercadoPago($paymentId);

            if ($pagamento) {
                logWebhook('Detalhes do pagamento', $pagamento);

                $status = $pagamento['status'] ?? 'unknown';
                $externalRef = $pagamento['external_reference'] ?? null;

                // External reference contém o ID da assinatura
                if ($externalRef && strpos($externalRef, 'assinatura_') === 0) {
                    $assinaturaId = str_replace('assinatura_', '', $externalRef);

                    // Buscar assinatura
                    $stmt = $pdo->prepare("SELECT * FROM assinaturas WHERE id = ?");
                    $stmt->execute([$assinaturaId]);
                    $assinatura = $stmt->fetch();

                    if ($assinatura) {
                        // Registrar evento
                        registrarEventoPagamento(
                            $paymentId,
                            $assinatura['usuario_id'],
                            $status,
                            'webhook_mp',
                            $pagamento
                        );

                        // Processar status
                        switch ($status) {
                            case 'approved':
                                atualizarStatusAssinatura($assinaturaId, 'active', $paymentId);
                                logWebhook("Assinatura {$assinaturaId} ativada");
                                break;

                            case 'pending':
                            case 'in_process':
                                // Manter como pendente
                                logWebhook("Assinatura {$assinaturaId} pendente");
                                break;

                            case 'rejected':
                            case 'cancelled':
                            case 'refunded':
                                atualizarStatusAssinatura($assinaturaId, 'canceled', $paymentId);
                                logWebhook("Assinatura {$assinaturaId} cancelada");
                                break;
                        }
                    }
                }
            }
        }
    }

    // Sempre retornar 200 para o Mercado Pago
    http_response_code(200);
    echo json_encode(['status' => 'ok']);
} catch (Exception $e) {
    logWebhook('Erro no webhook', ['error' => $e->getMessage()]);
    http_response_code(200); // Sempre 200 para não reenviar
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
