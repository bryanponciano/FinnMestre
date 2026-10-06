<?php
/**
 * =====================================================
 * FinnMestre - API de Contas Programadas
 * =====================================================
 */
require_once __DIR__ . '/api_auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/contas-programadas.php';

header('Content-Type: application/json');

$acao = $_GET['acao'] ?? '';

switch ($acao) {
    case 'listar':
        echo json_encode([
            'sucesso' => true,
            'contas' => listarContasProgramadas()
        ]);
        break;

    case 'obter':
        $id = intval($_GET['id'] ?? 0);
        $conta = obterContaProgramada($id);
        echo json_encode([
            'sucesso' => $conta ? true : false,
            'conta' => $conta
        ]);
        break;

    case 'salvar':
        $dados = json_decode(file_get_contents('php://input'), true);
        if (!$dados) {
            echo json_encode(['sucesso' => false, 'erro' => 'Dados inválidos']);
            break;
        }

        $resultado = salvarContaProgramada($dados);
        echo json_encode(['sucesso' => $resultado]);
        break;

    case 'excluir':
        $id = intval($_GET['id'] ?? 0);
        $resultado = excluirContaProgramada($id);
        echo json_encode(['sucesso' => $resultado]);
        break;

    case 'pagar':
        $dados = json_decode(file_get_contents('php://input'), true);
        if (!$dados || !isset($dados['conta_id'])) {
            echo json_encode(['sucesso' => false, 'erro' => 'Dados inválidos']);
            break;
        }

        $resultado = pagarContaProgramada(
            $dados['conta_id'],
            $dados['valor'] ?? null,
            $dados['data'] ?? null
        );
        echo json_encode(['sucesso' => $resultado]);
        break;

    case 'proximas':
        $dias = intval($_GET['dias'] ?? 7);
        echo json_encode([
            'sucesso' => true,
            'contas' => obterContasProximasVencimento($dias)
        ]);
        break;

    default:
        echo json_encode(['sucesso' => false, 'erro' => 'Ação não reconhecida']);
}
