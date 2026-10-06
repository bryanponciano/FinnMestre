<?php
/**
 * API de Gerenciamento de Perfis
 */
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/perfis.php';

// Verificar autenticação
if (!isset($_SESSION['usuario_logado']) || !$_SESSION['usuario_logado']) {
    header('Content-Type: application/json');
    echo json_encode(['sucesso' => false, 'erro' => 'Não autenticado']);
    exit;
}

$acao = $_GET['acao'] ?? $_POST['acao'] ?? '';

// Para ação 'trocar' via GET, não definir Content-Type JSON ainda
if (!($acao === 'trocar' && $_SERVER['REQUEST_METHOD'] === 'GET')) {
    header('Content-Type: application/json');
}

try {
    switch ($acao) {
        case 'listar':
            $perfis = listarPerfis();
            $perfilAtual = obterPerfilAtual();
            echo json_encode([
                'sucesso' => true,
                'perfis' => $perfis,
                'perfil_atual_id' => $perfilAtual ? $perfilAtual['id'] : null,
                'modo_consolidado' => isModoConsolidado(),
                'perfis_consolidados' => $_SESSION['perfis_consolidados'] ?? []
            ]);
            break;

        case 'obter':
            $id = intval($_GET['id'] ?? 0);
            $perfil = obterPerfil($id);
            if ($perfil) {
                $perfil['estatisticas'] = obterEstatisticasPerfil($id);
                echo json_encode(['sucesso' => true, 'perfil' => $perfil]);
            } else {
                echo json_encode(['sucesso' => false, 'erro' => 'Perfil não encontrado']);
            }
            break;

        case 'salvar':
            $dados = [
                'id' => $_POST['id'] ?? null,
                'nome' => trim($_POST['nome'] ?? ''),
                'tipo' => $_POST['tipo'] ?? 'pessoal',
                'cor' => $_POST['cor'] ?? '#6366f1',
                'icone' => $_POST['icone'] ?? 'fa-user'
            ];

            if (empty($dados['nome'])) {
                echo json_encode(['sucesso' => false, 'erro' => 'Nome é obrigatório']);
                break;
            }

            $resultado = salvarPerfil($dados);
            if ($resultado === 'LIMITE_PERFIS') {
                echo json_encode(['sucesso' => false, 'erro' => 'Limite de perfis atingido! No plano Mensal você pode ter até 3 perfis. Faça upgrade para ter perfis ilimitados.']);
                break;
            }
            if ($resultado) {
                echo json_encode([
                    'sucesso' => true,
                    'mensagem' => $dados['id'] ? 'Perfil atualizado!' : 'Perfil criado!',
                    'perfil_id' => is_numeric($resultado) ? $resultado : $dados['id']
                ]);
            } else {
                echo json_encode(['sucesso' => false, 'erro' => 'Erro ao salvar perfil']);
            }
            break;

        case 'excluir':
            $id = intval($_POST['id'] ?? $_GET['id'] ?? 0);
            if (excluirPerfil($id)) {
                // Se excluiu o perfil ativo, mudar para outro
                if (isset($_SESSION['perfil_id']) && $_SESSION['perfil_id'] == $id) {
                    $perfis = listarPerfis();
                    if (!empty($perfis)) {
                        $_SESSION['perfil_id'] = $perfis[0]['id'];
                    }
                }
                echo json_encode(['sucesso' => true, 'mensagem' => 'Perfil excluído!']);
            } else {
                echo json_encode(['sucesso' => false, 'erro' => 'Não é possível excluir o único perfil']);
            }
            break;

        case 'trocar':
            $id = intval($_POST['id'] ?? $_GET['id'] ?? 0);
            if (setPerfilAtual($id)) {
                // Se veio via GET (link direto), redirecionar
                if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                    $referer = $_SERVER['HTTP_REFERER'] ?? '../index.php';
                    // Evitar redirect loop
                    if (strpos($referer, 'api/perfis.php') !== false) {
                        $referer = '../index.php';
                    }
                    header('Location: ' . $referer);
                    exit;
                }
                // Se veio via AJAX/POST, retornar JSON
                $perfil = obterPerfil($id);
                echo json_encode([
                    'sucesso' => true,
                    'mensagem' => "Perfil alterado para {$perfil['nome']}",
                    'perfil' => $perfil
                ]);
            } else {
                if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                    header('Location: ../index.php?erro=perfil_invalido');
                    exit;
                }
                echo json_encode(['sucesso' => false, 'erro' => 'Perfil inválido']);
            }
            break;

        case 'consolidar':
            $ids = $_POST['ids'] ?? $_GET['ids'] ?? '';
            if (is_string($ids)) {
                $ids = array_filter(array_map('intval', explode(',', $ids)));
            }

            if (ativarModoConsolidado($ids)) {
                // Se veio de formulário, redirecionar de volta
                $referer = $_SERVER['HTTP_REFERER'] ?? '../index.php';
                header('Location: ' . $referer);
                exit;
            } else {
                header('Location: ../perfis.php?erro=consolidado_falha');
                exit;
            }
            break;

        case 'desativar_consolidado':
            desativarModoConsolidado();
            // Redirecionar de volta à página anterior ou index
            $referer = $_SERVER['HTTP_REFERER'] ?? '../index.php';
            header('Location: ' . $referer);
            exit;

        default:
            echo json_encode(['sucesso' => false, 'erro' => 'Ação inválida']);
    }
} catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'erro' => 'Erro interno: ' . $e->getMessage()]);
}
