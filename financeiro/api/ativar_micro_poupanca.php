<?php
/**
 * API para ativar meta de micro-poupança
 */
header('Content-Type: application/json');
session_start();

require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método não permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$valorDiario = isset($data['valor_diario']) ? floatval($data['valor_diario']) : 15;

if ($valorDiario <= 0 || $valorDiario > 1000) {
    echo json_encode(['success' => false, 'error' => 'Valor inválido']);
    exit;
}

if (ativarMicroPoupanca($valorDiario)) {
    echo json_encode([
        'success' => true,
        'message' => 'Meta de micro-poupança ativada!',
        'valor_diario' => $valorDiario,
        'projecao_mensal' => $valorDiario * 30
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Erro ao ativar meta']);
}
