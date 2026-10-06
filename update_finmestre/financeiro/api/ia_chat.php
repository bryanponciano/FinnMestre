<?php
// api/ia_chat.php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

require_once '../includes/db.php';
require_once '../includes/funcoes.php';
require_once '../includes/ia_financeiro.php';

session_start();

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['perfil_id'])) {
    echo json_encode(['sucesso' => false, 'erro' => 'Sessão expirada ou não autenticada.']);
    exit;
}

$perfilId = $_SESSION['perfil_id'];
$method = $_SERVER['REQUEST_METHOD'];
$acao = $_GET['acao'] ?? '';

function responder($dados) {
    ob_end_clean();
    echo json_encode($dados);
    exit;
}

if ($method === 'GET') {
    if ($acao === 'historico') {
        $stmt = $conn->prepare("SELECT id, titulo, criado_em, atualizado_em FROM ia_conversas WHERE perfil_id = ? ORDER BY atualizado_em DESC");
        $stmt->bind_param("i", $perfilId);
        $stmt->execute();
        $res = $stmt->get_result();
        $conversas = $res->fetch_all(MYSQLI_ASSOC);
        responder(['sucesso' => true, 'conversas' => $conversas]);
    }
    
    if ($acao === 'conversa' && isset($_GET['id'])) {
        $conversaId = (int)$_GET['id'];
        
        $stmt = $conn->prepare("SELECT id FROM ia_conversas WHERE id = ? AND perfil_id = ?");
        $stmt->bind_param("ii", $conversaId, $perfilId);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            responder(['sucesso' => false, 'erro' => 'Conversa não encontrada ou acesso negado.']);
        }
        
        $stmt = $conn->prepare("SELECT id, papel, conteudo, criado_em FROM ia_mensagens WHERE conversa_id = ? ORDER BY criado_em ASC");
        $stmt->bind_param("i", $conversaId);
        $stmt->execute();
        $res = $stmt->get_result();
        $mensagens = $res->fetch_all(MYSQLI_ASSOC);
        responder(['sucesso' => true, 'mensagens' => $mensagens]);
    }

    if ($acao === 'alertas') {
        $alertas = gerarAlertasInteligentes($perfilId);
        responder(['sucesso' => true, 'alertas' => $alertas]);
    }

    if ($acao === 'analise') {
        $analise = gerarAnaliseAutomatica($perfilId);
        responder(['sucesso' => true, 'analise' => $analise]);
    }
}

if ($method === 'DELETE' && $acao === 'excluir' && isset($_GET['id'])) {
    $conversaId = (int)$_GET['id'];
    $stmt = $conn->prepare("DELETE FROM ia_conversas WHERE id = ? AND perfil_id = ?");
    $stmt->bind_param("ii", $conversaId, $perfilId);
    if ($stmt->execute()) {
        responder(['sucesso' => true]);
    } else {
        responder(['sucesso' => false, 'erro' => 'Erro ao excluir conversa.']);
    }
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if ($acao === 'nova_conversa') {
        $titulo = "Nova Conversa";
        $stmt = $conn->prepare("INSERT INTO ia_conversas (perfil_id, titulo) VALUES (?, ?)");
        $stmt->bind_param("is", $perfilId, $titulo);
        if ($stmt->execute()) {
            responder(['sucesso' => true, 'conversa_id' => $conn->insert_id]);
        } else {
            responder(['sucesso' => false, 'erro' => 'Erro ao criar conversa.']);
        }
    }

    // Mensagem
    $mensagem = trim($input['mensagem'] ?? '');
    $conversaId = isset($input['conversa_id']) ? (int)$input['conversa_id'] : null;

    if (empty($mensagem)) {
        responder(['sucesso' => false, 'erro' => 'Mensagem vazia.']);
    }

    $iaAtivo = function_exists('obterConfiguracao') ? obterConfiguracao('ia_ativo', true) : true;
    if (!$iaAtivo) {
        responder(['sucesso' => false, 'erro' => 'A Inteligência Artificial está desativada no momento.']);
    }

    $apiKey = function_exists('obterConfiguracao') ? obterConfiguracao('ia_api_key', '') : '';
    if (empty($apiKey)) {
        responder(['sucesso' => false, 'erro' => 'A chave de API da IA não está configurada. Por favor, acesse as configurações para definir sua chave.']);
    }

    if (!$conversaId) {
        $titulo = mb_substr($mensagem, 0, 30) . (mb_strlen($mensagem) > 30 ? '...' : '');
        $stmt = $conn->prepare("INSERT INTO ia_conversas (perfil_id, titulo) VALUES (?, ?)");
        $stmt->bind_param("is", $perfilId, $titulo);
        $stmt->execute();
        $conversaId = $conn->insert_id;
    } else {
        $stmt = $conn->prepare("SELECT titulo FROM ia_conversas WHERE id = ? AND perfil_id = ?");
        $stmt->bind_param("ii", $conversaId, $perfilId);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows === 0) {
            responder(['sucesso' => false, 'erro' => 'Conversa inválida.']);
        }
        $row = $res->fetch_assoc();
        if ($row['titulo'] === 'Nova Conversa') {
            $novoTitulo = mb_substr($mensagem, 0, 30) . (mb_strlen($mensagem) > 30 ? '...' : '');
            $stmtUp = $conn->prepare("UPDATE ia_conversas SET titulo = ? WHERE id = ?");
            $stmtUp->bind_param("si", $novoTitulo, $conversaId);
            $stmtUp->execute();
        }
    }

    $historico = [];
    $stmt = $conn->prepare("SELECT papel, conteudo FROM ia_mensagens WHERE conversa_id = ? ORDER BY criado_em ASC");
    $stmt->bind_param("i", $conversaId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $historico[] = $row;
    }

    $papelUser = 'user';
    $stmt = $conn->prepare("INSERT INTO ia_mensagens (conversa_id, papel, conteudo) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $conversaId, $papelUser, $mensagem);
    $stmt->execute();

    $stmt = $conn->prepare("UPDATE ia_conversas SET atualizado_em = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->bind_param("i", $conversaId);
    $stmt->execute();

    $contexto = coletarContextoFinanceiro($perfilId);
    $respostaIA = enviarMensagemIA($mensagem, $contexto, $historico);

    $papelModel = 'model';
    $stmt = $conn->prepare("INSERT INTO ia_mensagens (conversa_id, papel, conteudo) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $conversaId, $papelModel, $respostaIA);
    $stmt->execute();

    responder([
        'sucesso' => true,
        'resposta' => $respostaIA,
        'conversa_id' => $conversaId
    ]);
}

responder(['sucesso' => false, 'erro' => 'Método inválido.']);
?>
