<?php
/**
 * API - Dados para Gráficos
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/functions.php';

try {
    $mes = isset($_GET['mes']) ? intval($_GET['mes']) : date('m');
    $ano = isset($_GET['ano']) ? intval($_GET['ano']) : date('Y');

    $gastosPorCategoria = obterGastosPorCategoria($mes, $ano);
    $entradasVsSaidas = obterEntradasVsSaidas($ano);

    echo json_encode([
        'success' => true,
        'gastos_categoria' => $gastosPorCategoria,
        'entradas_saidas' => $entradasVsSaidas
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
