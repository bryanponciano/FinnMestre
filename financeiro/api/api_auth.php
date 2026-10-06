<?php
/**
 * Middleware para autenticação e verificação de assinatura da API
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_logado']) || !$_SESSION['usuario_logado']) {
    http_response_code(401);
    echo json_encode(['sucesso' => false, 'erro' => 'Não autenticado']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/assinaturas.php';

$userId = $_SESSION['usuario_id'] ?? null;
if (!isAdmin() && $userId) {
    if (!usuarioTemAcesso($userId)) {
        http_response_code(403);
        echo json_encode(['sucesso' => false, 'erro' => 'Assinatura inativa ou expirada']);
        exit;
    }
}
