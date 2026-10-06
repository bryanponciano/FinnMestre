<?php
/**
 * API: Webhook para integração n8n (Assistente IA via WhatsApp)
 */

// Permite requisições de qualquer origem (ou restritivo para n8n)
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

// Permitir apenas requisições POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['sucesso' => false, 'erro' => 'Método não permitido']);
    exit;
}

// Carregar o banco de dados e funções
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Ler o JSON de entrada enviado pelo n8n
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'erro' => 'Dados inválidos ou mal formatados']);
    exit;
}

$telefone_origem = trim($input['telefone_origem'] ?? '');

// Limpar para manter apenas números
$telefone_origem = preg_replace('/[^0-9]/', '', $telefone_origem);

if (empty($telefone_origem)) {
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'erro' => 'Telefone de origem não fornecido']);
    exit;
}

try {
    global $pdo;

    // 1. Encontrar o usuário pelo telefone de WhatsApp
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE telefone_whatsapp = ? AND ativo = 1 LIMIT 1");
    $stmt->execute([$telefone_origem]);
    $usuarioId = $stmt->fetchColumn();

    if (!$usuarioId) {
        http_response_code(404);
        echo json_encode(['sucesso' => false, 'erro' => 'Usuário não encontrado para o número de telefone informado.']);
        exit;
    }

    // 2. Encontrar o perfil principal do usuário (ou o primeiro)
    $stmt = $pdo->prepare("SELECT id FROM perfis WHERE usuario_id = ? ORDER BY id ASC LIMIT 1");
    $stmt->execute([$usuarioId]);
    $perfilId = $stmt->fetchColumn();

    if (!$perfilId) {
        http_response_code(404);
        echo json_encode(['sucesso' => false, 'erro' => 'Nenhum perfil encontrado para este usuário.']);
        exit;
    }

    // 3. Montar os dados da transação
    $dados = [
        'perfil_id' => $perfilId,
        'descricao' => trim($input['descricao'] ?? 'Transação via WhatsApp'),
        'valor' => floatval($input['valor'] ?? 0),
        'tipo' => $input['tipo'] ?? 'saida', // 'entrada' ou 'saida'
        'categoria_id' => $input['categoria_id'] ?? null,
        'conta_id' => $input['conta_id'] ?? null,
        'data_transacao' => $input['data_transacao'] ?? date('Y-m-d'),
        'total_parcelas' => 1,
        'observacao' => 'Registrado via Assistente IA do WhatsApp'
    ];

    if ($dados['valor'] <= 0) {
        http_response_code(400);
        echo json_encode(['sucesso' => false, 'erro' => 'O valor da transação deve ser maior que zero.']);
        exit;
    }

    // 4. Como não temos sessão aqui, vamos tentar inserir direto no banco
    // A função salvarTransacao pode precisar de $_SESSION['usuario_id']. 
    // Vamos simular a sessão temporariamente
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['usuario_id'] = $usuarioId;
    $_SESSION['usuario_logado'] = true;

    $resultado = salvarTransacao($dados);

    if ($resultado) {
        echo json_encode(['sucesso' => true, 'mensagem' => 'Transação salva com sucesso pelo Assistente IA!']);
    } else {
        http_response_code(500);
        echo json_encode(['sucesso' => false, 'erro' => 'Erro ao salvar a transação no banco de dados.']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'erro' => 'Erro interno do servidor: ' . $e->getMessage()]);
}
