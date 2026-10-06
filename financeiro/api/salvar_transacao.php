<?php
/**
 * API: Salvar Transação
 * 
 * Output buffering + error suppression para garantir JSON limpo
 */

// PRIMEIRA COISA: buffer e supressão de erros
ob_start();
error_reporting(0);
ini_set('display_errors', '0');

// Includes
require_once __DIR__ . '/api_auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Limpar TUDO que os includes possam ter gerado
while (ob_get_level()) {
    ob_end_clean();
}

// Agora começamos do zero com output limpo
header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['sucesso' => false, 'erro' => 'Dados inválidos']);
    exit;
}

// Obter perfil atual
$perfilId = $_SESSION['perfil_id'] ?? null;

$dados = [
    'id' => $input['id'] ?? null,
    'perfil_id' => $perfilId,
    'descricao' => trim($input['descricao'] ?? ''),
    'valor' => floatval($input['valor'] ?? 0),
    'tipo' => $input['tipo'] ?? 'saida',
    'categoria_id' => $input['categoria_id'] ?: null,
    'conta_id' => $input['conta_id'] ?: null,
    'conta_destino_id' => $input['conta_destino_id'] ?: null,
    'data_transacao' => $input['data_transacao'] ?? date('Y-m-d'),
    'total_parcelas' => intval($input['total_parcelas'] ?? 1),
    'observacao' => trim($input['observacao'] ?? '')
];

if (empty($dados['descricao']) || $dados['valor'] <= 0) {
    echo json_encode(['sucesso' => false, 'erro' => 'Descrição e valor são obrigatórios']);
    exit;
}

// Validação para transferências: conta origem e destino são obrigatórias
if ($dados['tipo'] === 'transferencia') {
    if (empty($dados['conta_id'])) {
        echo json_encode(['sucesso' => false, 'erro' => 'Selecione a conta de ORIGEM da transferência']);
        exit;
    }
    if (empty($dados['conta_destino_id'])) {
        echo json_encode(['sucesso' => false, 'erro' => 'Selecione a conta de DESTINO da transferência']);
        exit;
    }
    if ($dados['conta_id'] == $dados['conta_destino_id']) {
        echo json_encode(['sucesso' => false, 'erro' => 'Conta de origem e destino não podem ser iguais']);
        exit;
    }
}

try {
    ob_start();
    $resultado = salvarTransacao($dados);
    $lixo = ob_get_clean();
    
    echo json_encode(['sucesso' => $resultado, 'mensagem' => 'Transação salva com sucesso!']);
} catch (Exception $e) {
    if (ob_get_level()) ob_end_clean();
    echo json_encode(['sucesso' => false, 'erro' => 'Erro ao salvar: ' . $e->getMessage()]);
}
