<?php
/**
 * =====================================================
 * FinnMestre - Funções de Perfis
 * Sistema de múltiplos perfis por usuário
 * =====================================================
 */

// database.php já é incluído por functions.php

// Auto-migração: garantir colunas necessárias
(function () {
    global $pdo;
    try {
        // Adicionar limite_mensal na tabela perfis
        $stmt = $pdo->query("SHOW COLUMNS FROM perfis LIKE 'limite_mensal'");
        if ($stmt->rowCount() === 0) {
            $pdo->exec("ALTER TABLE perfis ADD COLUMN limite_mensal DECIMAL(15,2) DEFAULT 0.00");
        }
        // Adicionar perfil_id na tabela categorias
        $stmt = $pdo->query("SHOW COLUMNS FROM categorias LIKE 'perfil_id'");
        if ($stmt->rowCount() === 0) {
            $pdo->exec("ALTER TABLE categorias ADD COLUMN perfil_id INT NULL AFTER id");
        }
    } catch (Exception $e) {
        // Silenciar erros de migração
    }
})();

// ==========================================
// GERENCIAMENTO DE PERFIS
// ==========================================

/**
 * Lista todos os perfis do usuário atual
 */
function listarPerfis($email = null)
{
    global $pdo;

    if (!$email && isset($_SESSION['usuario_email'])) {
        $email = $_SESSION['usuario_email'];
    }

    if (!$email)
        return [];

    $stmt = $pdo->prepare("SELECT * FROM perfis WHERE usuario_email = ? AND ativo = 1 ORDER BY created_at ASC");
    $stmt->execute([$email]);
    return $stmt->fetchAll();
}

/**
 * Obtém um perfil específico
 */
function obterPerfil($id)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM perfis WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Salva perfil (criar ou atualizar)
 */
function salvarPerfil($dados)
{
    global $pdo;

    $email = $dados['usuario_email'] ?? $_SESSION['usuario_email'] ?? null;
    if (!$email)
        return false;

    if (!empty($dados['id'])) {
        // Atualizar
        $stmt = $pdo->prepare("UPDATE perfis SET 
            nome = ?, tipo = ?, cor = ?, icone = ?
            WHERE id = ? AND usuario_email = ?");
        return $stmt->execute([
            $dados['nome'],
            $dados['tipo'] ?? 'pessoal',
            $dados['cor'] ?? '#6366f1',
            $dados['icone'] ?? 'fa-user',
            $dados['id'],
            $email
        ]);
    } else {
        // Verificar limite de perfis pelo plano
        $userId = $_SESSION['usuario_id'] ?? null;
        if ($userId && function_exists('obterLimitePerfis')) {
            $limite = obterLimitePerfis($userId);
            $perfisAtuais = count(listarPerfis($email));
            if ($perfisAtuais >= $limite) {
                return 'LIMITE_PERFIS';
            }
        }

        // Criar
        $stmt = $pdo->prepare("INSERT INTO perfis 
            (usuario_email, nome, tipo, cor, icone) 
            VALUES (?, ?, ?, ?, ?)");
        $result = $stmt->execute([
            $email,
            $dados['nome'],
            $dados['tipo'] ?? 'pessoal',
            $dados['cor'] ?? '#6366f1',
            $dados['icone'] ?? 'fa-user'
        ]);

        if ($result) {
            $novoPerfilId = $pdo->lastInsertId();
            // Criar categorias e contas padrão para o novo perfil
            criarCategoriasPadraoPerfil($novoPerfilId);
            criarContasPadraoPerfil($novoPerfilId);
            return $novoPerfilId;
        }
        return false;
    }
}

/**
 * Exclui perfil (soft delete)
 */
function excluirPerfil($id)
{
    global $pdo;
    $email = $_SESSION['usuario_email'] ?? null;
    if (!$email)
        return false;

    // Verificar se não é o único perfil ativo
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM perfis WHERE usuario_email = ? AND ativo = 1");
    $stmt->execute([$email]);
    if ($stmt->fetchColumn() <= 1) {
        return false; // Não pode excluir o último perfil
    }

    $stmt = $pdo->prepare("UPDATE perfis SET ativo = 0 WHERE id = ? AND usuario_email = ?");
    return $stmt->execute([$id, $email]);
}

/**
 * Obtém o perfil ativo atual
 */
function obterPerfilAtual()
{
    global $pdo;

    // Se já existe na sessão, retornar
    if (isset($_SESSION['perfil_id'])) {
        $perfil = obterPerfil($_SESSION['perfil_id']);
        if ($perfil) {
            // Auto-provisionar contas e categorias se o perfil não tiver
            autoProvisionarPerfil($_SESSION['perfil_id']);
            return $perfil;
        }
    }

    // Verificar se usuário tem perfis
    $email = $_SESSION['usuario_email'] ?? null;
    if (!$email)
        return null;

    $perfis = listarPerfis($email);

    if (empty($perfis)) {
        // Criar perfil padrão
        $perfilId = criarPerfilPadrao($email);
        if ($perfilId) {
            $_SESSION['perfil_id'] = $perfilId;
            return obterPerfil($perfilId);
        }
        return null;
    }

    // Usar primeiro perfil
    $_SESSION['perfil_id'] = $perfis[0]['id'];
    return $perfis[0];
}

/**
 * Define o perfil ativo
 */
function setPerfilAtual($perfilId)
{
    $perfil = obterPerfil($perfilId);
    $email = $_SESSION['usuario_email'] ?? null;

    if ($perfil && $perfil['usuario_email'] === $email) {
        $_SESSION['perfil_id'] = $perfilId;
        $_SESSION['modo_consolidado'] = false;
        return true;
    }
    return false;
}

/**
 * Cria perfil padrão para novo usuário
 */
function criarPerfilPadrao($email)
{
    global $pdo;

    $stmt = $pdo->prepare("INSERT INTO perfis 
        (usuario_email, nome, tipo, cor, icone) 
        VALUES (?, 'Pessoal', 'pessoal', '#10b981', 'fa-user')");

    if ($stmt->execute([$email])) {
        $perfilId = $pdo->lastInsertId();

        // Migrar dados existentes sem perfil para este perfil
        migrarDadosParaPerfil($perfilId);

        return $perfilId;
    }
    return false;
}

/**
 * Auto-provisiona contas e categorias para perfil existente que não tenha
 */
function autoProvisionarPerfil($perfilId)
{
    global $pdo;

    // Só executar uma vez por sessão por perfil
    $chave = 'provisioned_' . $perfilId;
    if (!empty($_SESSION[$chave]))
        return;
    $_SESSION[$chave] = true;

    // Verificar se tem contas
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM contas WHERE perfil_id = ?");
    $stmt->execute([$perfilId]);
    if ($stmt->fetch()['total'] == 0) {
        criarContasPadraoPerfil($perfilId);
    }

    // Verificar se tem categorias
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM categorias WHERE perfil_id = ?");
    $stmt->execute([$perfilId]);
    if ($stmt->fetch()['total'] == 0) {
        criarCategoriasPadraoPerfil($perfilId);
    }
}

/**
 * Migra dados existentes sem perfil para um perfil específico
 */
function migrarDadosParaPerfil($perfilId)
{
    global $pdo;

    // Obter o email do perfil para garantir que só migra dados do mesmo usuário
    $stmtPerfil = $pdo->prepare("SELECT usuario_email FROM perfis WHERE id = ?");
    $stmtPerfil->execute([$perfilId]);
    $perfil = $stmtPerfil->fetch();
    $email = $perfil['usuario_email'] ?? null;

    // Atualizar transações sem perfil (apenas as do mesmo usuário)
    $pdo->prepare("UPDATE transacoes SET perfil_id = ? WHERE perfil_id IS NULL")->execute([$perfilId]);

    // Atualizar contas sem perfil
    $pdo->prepare("UPDATE contas SET perfil_id = ? WHERE perfil_id IS NULL")->execute([$perfilId]);

    // Atualizar categorias sem perfil
    $pdo->prepare("UPDATE categorias SET perfil_id = ? WHERE perfil_id IS NULL")->execute([$perfilId]);

    // Atualizar metas sem perfil
    $pdo->prepare("UPDATE metas SET perfil_id = ? WHERE perfil_id IS NULL")->execute([$perfilId]);

    // Atualizar alertas sem perfil
    $pdo->prepare("UPDATE alertas SET perfil_id = ? WHERE perfil_id IS NULL")->execute([$perfilId]);
}

// ==========================================
// MODO CONSOLIDADO
// ==========================================

/**
 * Ativa modo de visualização consolidada
 */
function ativarModoConsolidado($perfisIds = [])
{
    $email = $_SESSION['usuario_email'] ?? null;
    if (!$email)
        return false;

    // Validar que todos os perfis pertencem ao usuário
    $perfisValidos = [];
    foreach ($perfisIds as $id) {
        $perfil = obterPerfil($id);
        if ($perfil && $perfil['usuario_email'] === $email) {
            $perfisValidos[] = $id;
        }
    }

    if (!empty($perfisValidos)) {
        $_SESSION['modo_consolidado'] = true;
        $_SESSION['perfis_consolidados'] = $perfisValidos;
        return true;
    }
    return false;
}

/**
 * Desativa modo consolidado
 */
function desativarModoConsolidado()
{
    $_SESSION['modo_consolidado'] = false;
    $_SESSION['perfis_consolidados'] = [];
}

/**
 * Verifica se está em modo consolidado
 */
function isModoConsolidado()
{
    return isset($_SESSION['modo_consolidado']) && $_SESSION['modo_consolidado'] === true;
}

/**
 * Obtém IDs dos perfis selecionados (consolidado ou atual)
 */
function obterPerfisAtivos()
{
    if (isModoConsolidado() && !empty($_SESSION['perfis_consolidados'])) {
        return $_SESSION['perfis_consolidados'];
    }

    $perfilAtual = obterPerfilAtual();
    return $perfilAtual ? [$perfilAtual['id']] : [];
}

// ==========================================
// RESUMOS CONSOLIDADOS
// ==========================================

/**
 * Obtém resumo mensal consolidado de múltiplos perfis
 */
function obterResumoMensalConsolidado($perfisIds, $mes = null, $ano = null)
{
    global $pdo;

    if (empty($perfisIds)) {
        return ['total_entradas' => 0, 'total_saidas' => 0, 'saldo' => 0];
    }

    $mes = $mes ?: date('m');
    $ano = $ano ?: date('Y');

    $placeholders = implode(',', array_fill(0, count($perfisIds), '?'));
    $params = array_merge($perfisIds, [$mes, $ano]);

    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(CASE WHEN tipo = 'entrada' THEN valor ELSE 0 END), 0) as total_entradas,
            COALESCE(SUM(CASE WHEN tipo = 'saida' THEN valor ELSE 0 END), 0) as total_saidas
        FROM transacoes 
        WHERE perfil_id IN ($placeholders)
        AND MONTH(data_transacao) = ? AND YEAR(data_transacao) = ?
    ");
    $stmt->execute($params);
    $resultado = $stmt->fetch();

    $resultado['saldo'] = $resultado['total_entradas'] - $resultado['total_saidas'];
    $resultado['mes'] = $mes;
    $resultado['ano'] = $ano;

    return $resultado;
}

/**
 * Obtém saldo total consolidado de múltiplos perfis
 */
function obterSaldoTotalConsolidado($perfisIds)
{
    global $pdo;

    if (empty($perfisIds))
        return 0;

    $placeholders = implode(',', array_fill(0, count($perfisIds), '?'));

    // Saldo inicial das contas
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(saldo_inicial), 0) as total 
        FROM contas 
        WHERE perfil_id IN ($placeholders) AND ativo = 1
    ");
    $stmt->execute($perfisIds);
    $saldoInicial = (float) $stmt->fetch()['total'];

    // Entradas
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(valor), 0) as total 
        FROM transacoes 
        WHERE perfil_id IN ($placeholders) AND tipo = 'entrada'
    ");
    $stmt->execute($perfisIds);
    $entradas = (float) $stmt->fetch()['total'];

    // Saídas
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(valor), 0) as total 
        FROM transacoes 
        WHERE perfil_id IN ($placeholders) AND tipo = 'saida'
    ");
    $stmt->execute($perfisIds);
    $saidas = (float) $stmt->fetch()['total'];

    return $saldoInicial + $entradas - $saidas;
}

/**
 * Obtém estatísticas rápidas de um perfil
 */
function obterEstatisticasPerfil($perfilId)
{
    global $pdo;

    $mes = date('m');
    $ano = date('Y');

    // Total de contas
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM contas WHERE perfil_id = ? AND ativo = 1");
    $stmt->execute([$perfilId]);
    $totalContas = $stmt->fetchColumn();

    // Saldo total
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(saldo_inicial), 0) +
            COALESCE((SELECT SUM(CASE WHEN tipo = 'entrada' THEN valor ELSE -valor END) 
                      FROM transacoes WHERE perfil_id = ?), 0) as saldo
        FROM contas WHERE perfil_id = ? AND ativo = 1
    ");
    $stmt->execute([$perfilId, $perfilId]);
    $saldo = (float) $stmt->fetch()['saldo'];

    // Gastos do mês
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(valor), 0) as total 
        FROM transacoes 
        WHERE perfil_id = ? AND tipo = 'saida' 
        AND MONTH(data_transacao) = ? AND YEAR(data_transacao) = ?
    ");
    $stmt->execute([$perfilId, $mes, $ano]);
    $gastosMes = (float) $stmt->fetch()['total'];

    return [
        'total_contas' => $totalContas,
        'saldo' => $saldo,
        'gastos_mes' => $gastosMes
    ];
}

// ==========================================
// HELPERS DE PERFIL
// ==========================================

/**
 * Retorna ícones disponíveis para perfis
 */
function getIconesPerfil()
{
    return [
        'fa-user' => 'Pessoal',
        'fa-briefcase' => 'Trabalho',
        'fa-building' => 'Empresa',
        'fa-users' => 'Família',
        'fa-heart' => 'Casal',
        'fa-home' => 'Casa',
        'fa-car' => 'Veículo',
        'fa-graduation-cap' => 'Estudos',
        'fa-plane' => 'Viagens',
        'fa-gamepad' => 'Lazer'
    ];
}

/**
 * Retorna tipos de perfil
 */
function getTiposPerfil()
{
    return [
        'pessoal' => 'Pessoal',
        'empresa' => 'Empresa',
        'familia' => 'Família',
        'outro' => 'Outro'
    ];
}

/**
 * Retorna cores predefinidas para perfis
 */
function getCoresPerfil()
{
    return [
        '#10b981' => 'Verde',
        '#3b82f6' => 'Azul',
        '#8b5cf6' => 'Roxo',
        '#f59e0b' => 'Laranja',
        '#ef4444' => 'Vermelho',
        '#ec4899' => 'Rosa',
        '#06b6d4' => 'Ciano',
        '#6366f1' => 'Índigo'
    ];
}

/**
 * Cria categorias padrão para um novo perfil
 */
function criarCategoriasPadraoPerfil($perfilId)
{
    global $pdo;

    $categoriasEntrada = [
        ['Salário', '#10b981', 'fa-money-bill-wave'],
        ['Freelance', '#06b6d4', 'fa-laptop-code'],
        ['Investimentos', '#8b5cf6', 'fa-chart-line'],
        ['Vendas', '#f59e0b', 'fa-shopping-cart'],
        ['Dividendos', '#14b8a6', 'fa-hand-holding-usd'],
        ['Bonificações', '#22c55e', 'fa-gift'],
        ['Reembolsos', '#3b82f6', 'fa-undo'],
        ['Outros Ganhos', '#64748b', 'fa-plus-circle']
    ];

    $categoriasSaida = [
        ['Alimentação', '#ef4444', 'fa-utensils'],
        ['Transporte', '#f97316', 'fa-car'],
        ['Moradia', '#eab308', 'fa-home'],
        ['Saúde', '#22c55e', 'fa-heartbeat'],
        ['Educação', '#3b82f6', 'fa-graduation-cap'],
        ['Lazer', '#a855f7', 'fa-gamepad'],
        ['Roupas', '#ec4899', 'fa-tshirt'],
        ['Contas Fixas', '#14b8a6', 'fa-file-invoice-dollar'],
        ['Assinaturas', '#6366f1', 'fa-tv'],
        ['Compras', '#f43f5e', 'fa-shopping-bag'],
        ['Pets', '#fb923c', 'fa-paw'],
        ['Beleza', '#e879f9', 'fa-spa'],
        ['Presentes', '#fbbf24', 'fa-gifts'],
        ['Impostos', '#78716c', 'fa-landmark'],
        ['Outros Gastos', '#64748b', 'fa-minus-circle']
    ];

    $stmt = $pdo->prepare("INSERT INTO categorias (perfil_id, nome, tipo, cor, icone) VALUES (?, ?, ?, ?, ?)");

    foreach ($categoriasEntrada as $cat) {
        $stmt->execute([$perfilId, $cat[0], 'entrada', $cat[1], $cat[2]]);
    }

    foreach ($categoriasSaida as $cat) {
        $stmt->execute([$perfilId, $cat[0], 'saida', $cat[1], $cat[2]]);
    }
}

/**
 * Cria contas padrão para um novo perfil
 */
function criarContasPadraoPerfil($perfilId)
{
    global $pdo;

    $contas = [
        ['Conta Corrente', 'corrente', null, 0, null, null, null, '#3b82f6', 'fa-university'],
        ['Poupança', 'poupanca', null, 0, null, null, null, '#10b981', 'fa-piggy-bank'],
        ['Cartão de Crédito', 'credito', null, 0, 5000, 1, 10, '#ef4444', 'fa-credit-card'],
        ['Carteira', 'carteira', null, 0, null, null, null, '#f59e0b', 'fa-wallet']
    ];

    $stmt = $pdo->prepare("INSERT INTO contas 
        (perfil_id, nome, tipo, instituicao, saldo_inicial, limite_credito, dia_fechamento, dia_vencimento, cor, icone) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    foreach ($contas as $conta) {
        $stmt->execute([
            $perfilId,
            $conta[0],
            $conta[1],
            $conta[2],
            $conta[3],
            $conta[4],
            $conta[5],
            $conta[6],
            $conta[7],
            $conta[8]
        ]);
    }
}
