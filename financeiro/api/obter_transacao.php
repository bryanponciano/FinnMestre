<?php
/**
 * API: Obter Transação
 */
ob_start();
require_once __DIR__ . '/api_auth.php';
require_once __DIR__ . '/../includes/functions.php';
ob_end_clean();

header('Content-Type: application/json');

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['sucesso' => false, 'erro' => 'ID inválido']);
    exit;
}

$transacao = obterTransacao($id);

if ($transacao) {
    echo json_encode(['sucesso' => true, 'transacao' => $transacao]);
} else {
    echo json_encode(['sucesso' => false, 'erro' => 'Transação não encontrada']);
}
