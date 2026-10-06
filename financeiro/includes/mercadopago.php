<?php
/**
 * =====================================================
 * FinnMestre - Mercado Pago Integration
 * SDK e funções de pagamento
 * =====================================================
 */

// Configurações do Mercado Pago
define('MP_ACCESS_TOKEN', getenv('MERCADOPAGO_ACCESS_TOKEN') ?: 'APP_USR-325623470028729-031715-8575644cad598f50c1fe121dcdd5ad72-437045189');
define('MP_PUBLIC_KEY', getenv('MERCADOPAGO_PUBLIC_KEY') ?: 'APP_USR-797cb425-13fb-4330-820c-108c7f35af90');

/**
 * Cria um pagamento via Mercado Pago
 */
function criarPagamentoMercadoPago($dados)
{
    $url = 'https://api.mercadopago.com/v1/payments';

    $payload = [
        'transaction_amount' => (float) $dados['valor'],
        'description' => $dados['descricao'],
        'payment_method_id' => $dados['metodo'], // pix, bolbradesco, credit_card
        'payer' => [
            'email' => $dados['email'],
            'first_name' => $dados['nome'],
        ],
        'external_reference' => $dados['referencia'], // assinatura_id
        'notification_url' => $dados['webhook_url'] ?? null,
    ];

    if (!empty($dados['cpf'])) {
        $payload['payer']['identification'] = [
            'type' => 'CPF',
            'number' => $dados['cpf']
        ];
    }

    // Se for cartão, adicionar token
    if (!empty($dados['card_token'])) {
        $payload['token'] = $dados['card_token'];
        $payload['installments'] = $dados['parcelas'] ?? 1;
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . MP_ACCESS_TOKEN,
            'X-Idempotency-Key: ' . uniqid('fin_', true)
        ]
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $result = json_decode($response, true);

    if ($httpCode >= 200 && $httpCode < 300) {
        return [
            'sucesso' => true,
            'payment_id' => $result['id'] ?? null,
            'status' => $result['status'] ?? 'pending',
            'status_detail' => $result['status_detail'] ?? null,
            'pix_qr_code' => $result['point_of_interaction']['transaction_data']['qr_code'] ?? null,
            'pix_qr_code_base64' => $result['point_of_interaction']['transaction_data']['qr_code_base64'] ?? null,
            'boleto_url' => $result['transaction_details']['external_resource_url'] ?? null,
            'boleto_barcode' => $result['barcode']['content'] ?? null,
            'data' => $result
        ];
    }

    return [
        'sucesso' => false,
        'erro' => $result['message'] ?? 'Erro ao processar pagamento',
        'detalhes' => $result
    ];
}

/**
 * Consulta status de um pagamento
 */
function consultarPagamentoMercadoPago($paymentId)
{
    $url = "https://api.mercadopago.com/v1/payments/{$paymentId}";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . MP_ACCESS_TOKEN
        ]
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        return json_decode($response, true);
    }

    return null;
}

/**
 * Mapeia método de pagamento para MP
 */
function mapearMetodoPagamento($forma)
{
    $map = [
        'pix' => 'pix',
        'cartao' => 'credit_card',
        'boleto' => 'bolbradesco'
    ];
    return $map[$forma] ?? 'pix';
}

/**
 * Gera URL de webhook
 */
function gerarUrlWebhook()
{
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $path = dirname($_SERVER['SCRIPT_NAME']);
    
    // Mercado Pago não aceita URLs localhost para webhooks. 
    // Em produção (com domínio real), isso funcionará normalmente.
    if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
        return null;
    }
    
    return "{$protocol}://{$host}{$path}/api/mercadopago-webhook.php";
}
