<?php
/**
 * =====================================================
 * FinnMestre - Funções de Contas Programadas
 * Gerenciamento de contas com vencimento
 * =====================================================
 */

/**
 * Auto-migração: cria tabela se não existir
 */
function executarMigracaoContasProgramadas()
{
    global $pdo;

    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS contas_programadas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT NOT NULL,
            perfil_id INT DEFAULT NULL,
            descricao VARCHAR(255) NOT NULL,
            valor DECIMAL(12,2) NOT NULL,
            dia_vencimento INT NOT NULL,
            categoria_id INT DEFAULT NULL,
            conta_id INT DEFAULT NULL,
            recorrente TINYINT(1) DEFAULT 1,
            tipo_recorrencia ENUM('mensal', 'semanal', 'anual') DEFAULT 'mensal',
            ativo TINYINT(1) DEFAULT 1,
            proximo_vencimento DATE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_usuario (usuario_id),
            INDEX idx_vencimento (proximo_vencimento)
        )");

        // Tabela para histórico de pagamentos
        $pdo->exec("CREATE TABLE IF NOT EXISTS contas_programadas_pagamentos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            conta_programada_id INT NOT NULL,
            data_pagamento DATE NOT NULL,
            valor_pago DECIMAL(12,2) NOT NULL,
            transacao_id INT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_conta (conta_programada_id)
        )");
    } catch (Exception $e) {
        // Silenciosamente ignora se falhar
    }
}

// Executar migração
executarMigracaoContasProgramadas();

/**
 * Lista contas programadas do usuário
 */
function listarContasProgramadas($apenasAtivas = true)
{
    global $pdo;
    $userId = $_SESSION['usuario_id'] ?? null;
    $perfilId = $_SESSION['perfil_id'] ?? null;

    if (!$userId)
        return [];

    $sql = "SELECT cp.*, c.nome as categoria_nome, c.cor as categoria_cor, c.icone as categoria_icone,
                   ct.nome as conta_nome, ct.tipo as conta_tipo
            FROM contas_programadas cp
            LEFT JOIN categorias c ON cp.categoria_id = c.id
            LEFT JOIN contas ct ON cp.conta_id = ct.id
            WHERE cp.usuario_id = ?";

    $params = [$userId];

    if ($perfilId) {
        $sql .= " AND cp.perfil_id = ?";
        $params[] = $perfilId;
    }

    if ($apenasAtivas) {
        $sql .= " AND cp.ativo = 1";
    }

    $sql .= " ORDER BY cp.dia_vencimento ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Obtém uma conta programada por ID
 */
function obterContaProgramada($id)
{
    global $pdo;
    $userId = $_SESSION['usuario_id'] ?? null;

    $stmt = $pdo->prepare("SELECT * FROM contas_programadas WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$id, $userId]);
    return $stmt->fetch();
}

/**
 * Salva conta programada (cria ou atualiza)
 */
function salvarContaProgramada($dados)
{
    global $pdo;
    $userId = $_SESSION['usuario_id'] ?? null;
    $perfilId = $_SESSION['perfil_id'] ?? null;

    if (!$userId)
        return false;

    // Usar data de vencimento informada ou calcular a partir do dia
    if (!empty($dados['data_vencimento'])) {
        $proximoVenc = new DateTime($dados['data_vencimento']);
        $diaVenc = (int) $proximoVenc->format('d');
    } else {
        $diaVenc = intval($dados['dia_vencimento'] ?? 5);
        $hoje = new DateTime();
        $proximoVenc = new DateTime($hoje->format('Y-m-') . str_pad($diaVenc, 2, '0', STR_PAD_LEFT));
        if ($proximoVenc < $hoje) {
            $proximoVenc->modify('+1 month');
        }
    }

    try {
        if (!empty($dados['id'])) {
            // Atualizar
            $stmt = $pdo->prepare("UPDATE contas_programadas SET 
                descricao = ?, valor = ?, dia_vencimento = ?, categoria_id = ?, conta_id = ?,
                recorrente = ?, tipo_recorrencia = ?, proximo_vencimento = ?
                WHERE id = ? AND usuario_id = ?");
            return $stmt->execute([
                $dados['descricao'],
                $dados['valor'],
                $diaVenc,
                $dados['categoria_id'] ?: null,
                $dados['conta_id'] ?: null,
                $dados['recorrente'] ?? 1,
                $dados['tipo_recorrencia'] ?? 'mensal',
                $proximoVenc->format('Y-m-d'),
                $dados['id'],
                $userId
            ]);
        } else {
            // Criar
            $stmt = $pdo->prepare("INSERT INTO contas_programadas 
                (usuario_id, perfil_id, descricao, valor, dia_vencimento, categoria_id, conta_id, 
                 recorrente, tipo_recorrencia, proximo_vencimento)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            return $stmt->execute([
                $userId,
                $perfilId,
                $dados['descricao'],
                $dados['valor'],
                $diaVenc,
                $dados['categoria_id'] ?: null,
                $dados['conta_id'] ?: null,
                $dados['recorrente'] ?? 1,
                $dados['tipo_recorrencia'] ?? 'mensal',
                $proximoVenc->format('Y-m-d')
            ]);
        }
    } catch (Exception $e) {
        error_log("Erro ao salvar conta programada: " . $e->getMessage());
        return false;
    }
}

/**
 * Exclui conta programada
 */
function excluirContaProgramada($id)
{
    global $pdo;
    $userId = $_SESSION['usuario_id'] ?? null;

    $stmt = $pdo->prepare("DELETE FROM contas_programadas WHERE id = ? AND usuario_id = ?");
    return $stmt->execute([$id, $userId]);
}

/**
 * Marca conta como paga e cria transação
 */
function pagarContaProgramada($contaId, $valorPago = null, $dataPagamento = null)
{
    global $pdo;
    $userId = $_SESSION['usuario_id'] ?? null;

    $conta = obterContaProgramada($contaId);
    if (!$conta)
        return false;

    $valor = $valorPago ?? $conta['valor'];
    $data = $dataPagamento ?? date('Y-m-d');

    try {
        $pdo->beginTransaction();

        // Criar transação
        $perfilId = $conta['perfil_id'] ?? $_SESSION['perfil_id'] ?? null;
        $stmt = $pdo->prepare("INSERT INTO transacoes 
            (perfil_id, tipo, descricao, valor, data_transacao, categoria_id, conta_id, observacao)
            VALUES (?, 'saida', ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $perfilId,
            $conta['descricao'],
            $valor,
            $data,
            $conta['categoria_id'],
            $conta['conta_id'],
            'Pagamento de conta programada'
        ]);
        $transacaoId = $pdo->lastInsertId();

        // Registrar pagamento
        $stmt = $pdo->prepare("INSERT INTO contas_programadas_pagamentos 
            (conta_programada_id, data_pagamento, valor_pago, transacao_id)
            VALUES (?, ?, ?, ?)");
        $stmt->execute([$contaId, $data, $valor, $transacaoId]);

        // Atualizar próximo vencimento se recorrente
        if ($conta['recorrente']) {
            $novoVenc = new DateTime($conta['proximo_vencimento']);
            switch ($conta['tipo_recorrencia']) {
                case 'semanal':
                    $novoVenc->modify('+1 week');
                    break;
                case 'anual':
                    $novoVenc->modify('+1 year');
                    break;
                default: // mensal
                    $novoVenc->modify('+1 month');
            }
            $stmt = $pdo->prepare("UPDATE contas_programadas SET proximo_vencimento = ? WHERE id = ?");
            $stmt->execute([$novoVenc->format('Y-m-d'), $contaId]);
        } else {
            // Desativar se não recorrente
            $stmt = $pdo->prepare("UPDATE contas_programadas SET ativo = 0 WHERE id = ?");
            $stmt->execute([$contaId]);
        }

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Erro ao pagar conta: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtém contas próximas do vencimento
 */
function obterContasProximasVencimento($dias = 7)
{
    global $pdo;
    $userId = $_SESSION['usuario_id'] ?? null;

    if (!$userId)
        return [];

    $dataLimite = date('Y-m-d', strtotime("+{$dias} days"));

    $stmt = $pdo->prepare("SELECT cp.*, c.nome as categoria_nome, c.cor as categoria_cor
            FROM contas_programadas cp
            LEFT JOIN categorias c ON cp.categoria_id = c.id
            WHERE cp.usuario_id = ? AND cp.ativo = 1 
            AND cp.proximo_vencimento <= ?
            ORDER BY cp.proximo_vencimento ASC");
    $stmt->execute([$userId, $dataLimite]);
    return $stmt->fetchAll();
}

/**
 * Calcula total de contas a vencer no mês
 */
function calcularTotalContasMes($mes = null, $ano = null)
{
    global $pdo;
    $userId = $_SESSION['usuario_id'] ?? null;

    if (!$userId)
        return 0;

    $mes = $mes ?? date('m');
    $ano = $ano ?? date('Y');

    $stmt = $pdo->prepare("SELECT SUM(valor) as total FROM contas_programadas 
            WHERE usuario_id = ? AND ativo = 1 
            AND MONTH(proximo_vencimento) = ? AND YEAR(proximo_vencimento) = ?");
    $stmt->execute([$userId, $mes, $ano]);
    $result = $stmt->fetch();
    return $result ? (float) $result['total'] : 0;
}

/**
 * Verifica status de vencimento
 */
function getStatusVencimento($dataVencimento)
{
    $hoje = new DateTime();
    $venc = new DateTime($dataVencimento);
    $diff = $hoje->diff($venc);
    $dias = (int) $diff->format('%r%a');

    if ($dias < 0) {
        return ['classe' => 'danger', 'texto' => 'Vencida há ' . abs($dias) . ' dia(s)', 'icone' => 'fa-exclamation-triangle'];
    } elseif ($dias === 0) {
        return ['classe' => 'warning', 'texto' => 'Vence hoje!', 'icone' => 'fa-clock'];
    } elseif ($dias <= 3) {
        return ['classe' => 'warning', 'texto' => "Vence em {$dias} dia(s)", 'icone' => 'fa-clock'];
    } elseif ($dias <= 7) {
        return ['classe' => 'info', 'texto' => "Vence em {$dias} dias", 'icone' => 'fa-calendar'];
    } else {
        return ['classe' => 'safe', 'texto' => "Vence em {$dias} dias", 'icone' => 'fa-calendar-check'];
    }
}
