<?php
// Desativar exibição de erros na saída
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Limpar qualquer saída anterior (como espaços em branco antes da tag php)
if (ob_get_level() > 0) {
    ob_clean();
}
ob_start();

require_once 'api_auth.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

try {
    $acao = $_POST['acao'] ?? $_GET['acao'] ?? '';
    $perfilId = $_SESSION['perfil_id'] ?? null;

    if (!$perfilId) {
        echo json_encode(['success' => false, 'message' => 'Usuário não autenticado ou perfil inválido.']);
        exit;
    }

    switch ($acao) {
        case 'depositar':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método inválido para depósito.');
            }
            $valor = floatval($_POST['valor'] ?? 0);
            $descricao = trim($_POST['descricao'] ?? 'Depósito na Poupança');
            $data = $_POST['data'] ?? date('Y-m-d');
            $contaOrigemId = $_POST['conta_origem_id'] ?? null;

            if ($valor <= 0 || !$contaOrigemId) {
                throw new Exception('Valor ou conta de origem inválidos.');
            }

            $sucesso = depositarPoupanca($perfilId, $valor, $descricao, $data, $contaOrigemId);
            echo json_encode([
                'success' => $sucesso,
                'message' => $sucesso ? 'Depósito realizado com sucesso.' : 'Erro ao realizar depósito.'
            ]);
            break;

        case 'retirar':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método inválido para retirada.');
            }
            $valor = floatval($_POST['valor'] ?? 0);
            $descricao = trim($_POST['descricao'] ?? 'Retirada da Poupança');
            $data = $_POST['data'] ?? date('Y-m-d');
            $contaDestinoId = $_POST['conta_destino_id'] ?? null;

            if ($valor <= 0 || !$contaDestinoId) {
                throw new Exception('Valor ou conta de destino inválidos.');
            }

            $sucesso = retirarPoupanca($perfilId, $valor, $descricao, $data, $contaDestinoId);
            echo json_encode([
                'success' => $sucesso,
                'message' => $sucesso ? 'Retirada realizada com sucesso.' : 'Erro ao realizar retirada. Verifique o saldo.'
            ]);
            break;

        case 'historico':
            $mes = $_GET['mes'] ?? date('m');
            $ano = $_GET['ano'] ?? date('Y');
            $historico = listarHistoricoPoupanca($perfilId, $mes, $ano);
            echo json_encode(['success' => true, 'data' => $historico]);
            break;

        case 'saldo':
            $saldo = obterSaldoPoupanca($perfilId);
            $resumo = obterResumoPoupanca($perfilId);
            echo json_encode(['success' => true, 'saldo' => $saldo, 'resumo' => $resumo]);
            break;

        case 'resumo':
            $resumo = obterResumoPoupanca($perfilId);
            echo json_encode(['success' => true, 'data' => $resumo]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Ação não reconhecida.']);
            break;
    }

} catch (Exception $e) {
    ob_clean(); // Limpa qualquer saída que possa ter sido gerada
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// Enviar a saída
ob_end_flush();
