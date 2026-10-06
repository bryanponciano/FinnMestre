<?php
/**
 * API: Gerenciar Alertas
 */
header('Content-Type: application/json');
require_once '../includes/functions.php';

$acao = $_GET['acao'] ?? 'listar';

switch ($acao) {
    case 'listar':
        $alertas = listarAlertas(true, 10);
        echo json_encode(['sucesso' => true, 'alertas' => $alertas, 'nao_lidos' => contarAlertasNaoLidos()]);
        break;

    case 'marcar_lido':
        $id = intval($_GET['id'] ?? 0);
        $resultado = marcarAlertaLido($id);
        echo json_encode(['sucesso' => $resultado]);
        break;

    case 'marcar_todos':
        $resultado = marcarTodosAlertasLidos();
        echo json_encode(['sucesso' => true]);
        break;

    default:
        echo json_encode(['sucesso' => false, 'erro' => 'Ação inválida']);
}
