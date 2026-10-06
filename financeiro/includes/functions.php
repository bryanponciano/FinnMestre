<?php
/**
 * =====================================================
 * FinnMestre - Sistema de Controle Financeiro Inteligente
 * Funções do Sistema
 * =====================================================
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/perfis.php';
require_once __DIR__ . '/i18n.php';
require_once __DIR__ . '/behavioral.php';

// ==========================================
// TRANSAÇÕES
// ==========================================

/**
 * Lista transações com filtros avançados
 */
function listarTransacoes($filtros = [])
{
    global $pdo;

    $sql = "SELECT t.*, 
            c.nome as categoria_nome, c.cor as categoria_cor, c.icone as categoria_icone,
            co.nome as conta_nome, co.tipo as conta_tipo, co.cor as conta_cor, co.icone as conta_icone,
            cd.nome as conta_destino_nome
            FROM transacoes t 
            LEFT JOIN categorias c ON t.categoria_id = c.id 
            LEFT JOIN contas co ON t.conta_id = co.id
            LEFT JOIN contas cd ON t.conta_destino_id = cd.id
            WHERE 1=1";
    $params = [];

    // Filtro por perfil
    if (!empty($filtros['perfil_ids'])) {
        $perfisIds = is_array($filtros['perfil_ids']) ? $filtros['perfil_ids'] : [$filtros['perfil_ids']];
        $placeholders = implode(',', array_fill(0, count($perfisIds), '?'));
        $sql .= " AND t.perfil_id IN ($placeholders)";
        $params = array_merge($params, $perfisIds);
    } elseif (function_exists('obterPerfisAtivos')) {
        $perfisAtivos = obterPerfisAtivos();
        if (!empty($perfisAtivos)) {
            $placeholders = implode(',', array_fill(0, count($perfisAtivos), '?'));
            $sql .= " AND t.perfil_id IN ($placeholders)";
            $params = array_merge($params, $perfisAtivos);
        }
    }

    if (!empty($filtros['mes'])) {
        $sql .= " AND MONTH(t.data_transacao) = ?";
        $params[] = $filtros['mes'];
    }

    if (!empty($filtros['ano'])) {
        $sql .= " AND YEAR(t.data_transacao) = ?";
        $params[] = $filtros['ano'];
    }

    if (!empty($filtros['tipo'])) {
        $sql .= " AND t.tipo = ?";
        $params[] = $filtros['tipo'];
    }

    if (!empty($filtros['categoria_id'])) {
        $sql .= " AND t.categoria_id = ?";
        $params[] = $filtros['categoria_id'];
    }

    if (!empty($filtros['conta_id'])) {
        $sql .= " AND (t.conta_id = ? OR t.conta_destino_id = ?)";
        $params[] = $filtros['conta_id'];
        $params[] = $filtros['conta_id'];
    }

    if (!empty($filtros['data_inicio'])) {
        $sql .= " AND t.data_transacao >= ?";
        $params[] = $filtros['data_inicio'];
    }

    if (!empty($filtros['data_fim'])) {
        $sql .= " AND t.data_transacao <= ?";
        $params[] = $filtros['data_fim'];
    }

    if (isset($filtros['parcelado']) && $filtros['parcelado']) {
        $sql .= " AND t.total_parcelas > 1";
    }

    $sql .= " ORDER BY t.data_transacao DESC, t.id DESC";

    if (!empty($filtros['limite'])) {
        $sql .= " LIMIT ?";
        $params[] = (int) $filtros['limite'];
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Obtém uma transação específica
 */
function obterTransacao($id)
{
    global $pdo;

    $sql = "
        SELECT t.*, 
        c.nome as categoria_nome, co.nome as conta_nome
        FROM transacoes t 
        LEFT JOIN categorias c ON t.categoria_id = c.id 
        LEFT JOIN contas co ON t.conta_id = co.id
        WHERE t.id = ?";
    $params = [$id];

    // Filtrar por perfis ativos do usuário
    if (function_exists('obterPerfisAtivos')) {
        $perfisAtivos = obterPerfisAtivos();
        if (!empty($perfisAtivos)) {
            $placeholders = implode(',', array_fill(0, count($perfisAtivos), '?'));
            $sql .= " AND t.perfil_id IN ($placeholders)";
            $params = array_merge($params, $perfisAtivos);
        }
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch();
}

/**
 * Salva transação (com suporte a parcelamento)
 */
function salvarTransacao($dados)
{
    global $pdo;

    try {
        $pdo->beginTransaction();

        if (!empty($dados['id'])) {
            // Atualizar
            $stmt = $pdo->prepare("UPDATE transacoes SET 
                descricao = ?, valor = ?, tipo = ?, categoria_id = ?, conta_id = ?,
                conta_destino_id = ?, data_transacao = ?, observacao = ? 
                WHERE id = ?");
            $result = $stmt->execute([
                $dados['descricao'],
                $dados['valor'],
                $dados['tipo'],
                $dados['categoria_id'] ?: null,
                $dados['conta_id'] ?: null,
                $dados['conta_destino_id'] ?? null,
                $dados['data_transacao'],
                $dados['observacao'] ?? '',
                $dados['id']
            ]);
        } else {
            $totalParcelas = isset($dados['total_parcelas']) ? (int) $dados['total_parcelas'] : 1;

            if ($totalParcelas > 1) {
                // Criar transações parceladas
                $perfilId = $dados['perfil_id'] ?? $_SESSION['perfil_id'] ?? null;
                $valorParcela = round($dados['valor'] / $totalParcelas, 2);
                $dataBase = new DateTime($dados['data_transacao']);

                // Primeira parcela (pai)
                $stmt = $pdo->prepare("INSERT INTO transacoes 
                    (perfil_id, descricao, valor, tipo, categoria_id, conta_id, data_transacao, 
                    parcela_atual, total_parcelas, observacao) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $perfilId,
                    $dados['descricao'] . " (1/{$totalParcelas})",
                    $valorParcela,
                    $dados['tipo'],
                    $dados['categoria_id'] ?: null,
                    $dados['conta_id'] ?: null,
                    $dados['data_transacao'],
                    1,
                    $totalParcelas,
                    $dados['observacao'] ?? ''
                ]);

                $transacaoPaiId = $pdo->lastInsertId();

                // Atualizar com ID pai
                $pdo->prepare("UPDATE transacoes SET transacao_pai_id = ? WHERE id = ?")
                    ->execute([$transacaoPaiId, $transacaoPaiId]);

                // Demais parcelas
                for ($i = 2; $i <= $totalParcelas; $i++) {
                    $dataBase->modify('+1 month');
                    $stmt = $pdo->prepare("INSERT INTO transacoes 
                        (perfil_id, descricao, valor, tipo, categoria_id, conta_id, data_transacao, 
                        parcela_atual, total_parcelas, transacao_pai_id, observacao) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $perfilId,
                        $dados['descricao'] . " ({$i}/{$totalParcelas})",
                        $valorParcela,
                        $dados['tipo'],
                        $dados['categoria_id'] ?: null,
                        $dados['conta_id'] ?: null,
                        $dataBase->format('Y-m-d'),
                        $i,
                        $totalParcelas,
                        $transacaoPaiId,
                        $dados['observacao'] ?? ''
                    ]);
                }
            } else {
                // Transação única
                $perfilId = $dados['perfil_id'] ?? $_SESSION['perfil_id'] ?? null;
                $stmt = $pdo->prepare("INSERT INTO transacoes 
                    (perfil_id, descricao, valor, tipo, categoria_id, conta_id, conta_destino_id, data_transacao, observacao) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $result = $stmt->execute([
                    $perfilId,
                    $dados['descricao'],
                    $dados['valor'],
                    $dados['tipo'],
                    $dados['categoria_id'] ?: null,
                    $dados['conta_id'] ?: null,
                    $dados['conta_destino_id'] ?? null,
                    $dados['data_transacao'],
                    $dados['observacao'] ?? ''
                ]);
            }
        }

        // Verificar e gerar alertas
        verificarEGerarAlertas();

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Erro ao salvar transação: " . $e->getMessage());
        return false;
    }
}

/**
 * Exclui transação (com verificação de perfil)
 */
function excluirTransacao($id)
{
    global $pdo;
    $perfisAtivos = obterPerfisAtivos();
    if (!empty($perfisAtivos)) {
        $placeholders = implode(',', array_fill(0, count($perfisAtivos), '?'));
        $stmt = $pdo->prepare("DELETE FROM transacoes WHERE id = ? AND perfil_id IN ($placeholders)");
        return $stmt->execute(array_merge([$id], $perfisAtivos));
    }
    $stmt = $pdo->prepare("DELETE FROM transacoes WHERE id = ?");
    return $stmt->execute([$id]);
}

// ==========================================
// CONTAS
// ==========================================

/**
 * Lista todas as contas
 */
function listarContas($apenasAtivas = true)
{
    global $pdo;

    $sql = "SELECT * FROM contas WHERE 1=1";
    $params = [];

    // Filtro por perfil
    if (function_exists('obterPerfisAtivos')) {
        $perfisAtivos = obterPerfisAtivos();
        if (!empty($perfisAtivos)) {
            $placeholders = implode(',', array_fill(0, count($perfisAtivos), '?'));
            $sql .= " AND perfil_id IN ($placeholders)";
            $params = array_merge($params, $perfisAtivos);
        }
    }

    if ($apenasAtivas) {
        $sql .= " AND ativo = 1";
    }
    $sql .= " ORDER BY tipo, nome";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Obtém uma conta específica
 */
function obterConta($id)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM contas WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Salva conta
 */
function salvarConta($dados)
{
    global $pdo;

    if (!empty($dados['id'])) {
        $stmt = $pdo->prepare("UPDATE contas SET 
            nome = ?, tipo = ?, instituicao = ?, saldo_inicial = ?,
            limite_credito = ?, dia_fechamento = ?, dia_vencimento = ?,
            cor = ?, icone = ?, ativo = ?
            WHERE id = ?");
        return $stmt->execute([
            $dados['nome'],
            $dados['tipo'],
            $dados['instituicao'] ?? null,
            $dados['saldo_inicial'] ?? 0,
            $dados['limite_credito'] ?? null,
            $dados['dia_fechamento'] ?? null,
            $dados['dia_vencimento'] ?? null,
            $dados['cor'] ?? '#6366f1',
            $dados['icone'] ?? 'fa-wallet',
            $dados['ativo'] ?? 1,
            $dados['id']
        ]);
    } else {
        $perfilId = $dados['perfil_id'] ?? $_SESSION['perfil_id'] ?? null;
        $stmt = $pdo->prepare("INSERT INTO contas 
            (perfil_id, nome, tipo, instituicao, saldo_inicial, limite_credito, 
            dia_fechamento, dia_vencimento, cor, icone) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([
            $perfilId,
            $dados['nome'],
            $dados['tipo'],
            $dados['instituicao'] ?? null,
            $dados['saldo_inicial'] ?? 0,
            $dados['limite_credito'] ?? null,
            $dados['dia_fechamento'] ?? null,
            $dados['dia_vencimento'] ?? null,
            $dados['cor'] ?? '#6366f1',
            $dados['icone'] ?? 'fa-wallet'
        ]);
    }
}

/**
 * Exclui conta (soft delete, com verificação de perfil)
 */
function excluirConta($id)
{
    global $pdo;
    $perfisAtivos = obterPerfisAtivos();
    if (!empty($perfisAtivos)) {
        $placeholders = implode(',', array_fill(0, count($perfisAtivos), '?'));
        $stmt = $pdo->prepare("UPDATE contas SET ativo = 0 WHERE id = ? AND perfil_id IN ($placeholders)");
        return $stmt->execute(array_merge([$id], $perfisAtivos));
    }
    $stmt = $pdo->prepare("UPDATE contas SET ativo = 0 WHERE id = ?");
    return $stmt->execute([$id]);
}

/**
 * Calcula saldo de uma conta (até o final do mês/ano informado, ou total se não informado)
 */
function calcularSaldoConta($contaId, $mes = null, $ano = null)
{
    global $pdo;

    $conta = obterConta($contaId);
    if (!$conta)
        return 0;

    $saldoInicial = (float) $conta['saldo_inicial'];

    // Se mês/ano informados, filtrar até o último dia desse mês
    $filtroData = '';
    $params = [$contaId];
    if ($mes && $ano) {
        $ultimoDia = date('Y-m-t', mktime(0, 0, 0, $mes, 1, $ano));
        $filtroData = " AND data_transacao <= ?";
        $params[] = $ultimoDia;
    }

    // Entradas na conta
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(valor), 0) as total 
        FROM transacoes 
        WHERE conta_id = ? AND tipo = 'entrada'" . $filtroData . "
    ");
    $stmt->execute($params);
    $entradas = (float) $stmt->fetch()['total'];

    // Saídas da conta
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(valor), 0) as total 
        FROM transacoes 
        WHERE conta_id = ? AND tipo = 'saida'" . $filtroData . "
    ");
    $stmt->execute($params);
    $saidas = (float) $stmt->fetch()['total'];

    // Transferências recebidas
    $paramsDestino = [$contaId];
    if ($mes && $ano) {
        $paramsDestino[] = $ultimoDia;
    }
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(valor), 0) as total 
        FROM transacoes 
        WHERE conta_destino_id = ? AND tipo = 'transferencia'" . $filtroData . "
    ");
    $stmt->execute($paramsDestino);
    $transfsRecebidas = (float) $stmt->fetch()['total'];

    // Transferências enviadas
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(valor), 0) as total 
        FROM transacoes 
        WHERE conta_id = ? AND tipo = 'transferencia'" . $filtroData . "
    ");
    $stmt->execute($params);
    $transfsEnviadas = (float) $stmt->fetch()['total'];

    return $saldoInicial + $entradas - $saidas + $transfsRecebidas - $transfsEnviadas;
}

/**
 * Calcula a fatura de um cartão de crédito para um mês específico
 */
function calcularFaturaCartao($contaId, $mes = null, $ano = null)
{
    global $pdo;
    $mes = $mes ?: date('m');
    $ano = $ano ?: date('Y');

    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(valor), 0) as total 
        FROM transacoes 
        WHERE conta_id = ? 
        AND tipo = 'saida' 
        AND MONTH(data_transacao) = ? 
        AND YEAR(data_transacao) = ?
    ");
    $stmt->execute([$contaId, $mes, $ano]);
    return (float) $stmt->fetch()['total'];
}

/**
 * Obtém resumo de todas as contas (filtrado por mês/ano se informado)
 */
function obterResumoContas($mes = null, $ano = null)
{
    $contas = listarContas(true);
    $resumo = [
        'corrente' => ['total' => 0, 'contas' => []],
        'credito' => ['total' => 0, 'limite_total' => 0, 'usado' => 0, 'contas' => []],
        'poupanca' => ['total' => 0, 'contas' => []],
        'carteira' => ['total' => 0, 'contas' => []],
        'patrimonio_total' => 0
    ];

    foreach ($contas as $conta) {
        if ($conta['tipo'] === 'credito') {
            // Para cartão de crédito, o que importa no resumo é a fatura do mês atual
            $saldo = calcularFaturaCartao($conta['id'], $mes, $ano);
            $conta['saldo_atual'] = -$saldo; // Exibimos como negativo (dívida)

            $resumo['credito']['contas'][] = $conta;
            $resumo['credito']['usado'] += $saldo;
            $resumo['credito']['limite_total'] += (float) $conta['limite_credito'];
            $resumo['credito']['total'] -= $saldo; 
        } else {
            $saldo = calcularSaldoConta($conta['id'], $mes, $ano);
            $conta['saldo_atual'] = $saldo;

            $resumo[$conta['tipo']]['contas'][] = $conta;
            $resumo[$conta['tipo']]['total'] += $saldo;
            $resumo['patrimonio_total'] += $saldo;
        }
    }

    // Patrimônio líquido (menos dívidas do cartão)
    $resumo['patrimonio_total'] += $resumo['credito']['total'];

    return $resumo;
}

// ==========================================
// METAS FINANCEIRAS
// ==========================================

/**
 * Lista todas as metas
 */
function listarMetas($apenasPendentes = false)
{
    global $pdo;

    $sql = "SELECT * FROM metas WHERE 1=1";
    $params = [];

    // Filtro por perfil
    if (function_exists('obterPerfisAtivos')) {
        $perfisAtivos = obterPerfisAtivos();
        if (!empty($perfisAtivos)) {
            $placeholders = implode(',', array_fill(0, count($perfisAtivos), '?'));
            $sql .= " AND perfil_id IN ($placeholders)";
            $params = array_merge($params, $perfisAtivos);
        }
    }

    if ($apenasPendentes) {
        $sql .= " AND concluida = 0";
    }
    $sql .= " ORDER BY prioridade DESC, data_limite ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $metas = $stmt->fetchAll();

    // Calcular percentual de cada meta
    foreach ($metas as &$meta) {
        $meta['percentual'] = $meta['valor_objetivo'] > 0
            ? min(100, ($meta['valor_atual'] / $meta['valor_objetivo']) * 100)
            : 0;
    }

    return $metas;
}

/**
 * Obtém uma meta específica
 */
function obterMeta($id)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM metas WHERE id = ?");
    $stmt->execute([$id]);
    $meta = $stmt->fetch();

    if ($meta) {
        $meta['percentual'] = $meta['valor_objetivo'] > 0
            ? min(100, ($meta['valor_atual'] / $meta['valor_objetivo']) * 100)
            : 0;

        // Buscar contribuições
        $stmt = $pdo->prepare("
            SELECT * FROM meta_contribuicoes 
            WHERE meta_id = ? 
            ORDER BY data_contribuicao DESC
        ");
        $stmt->execute([$id]);
        $meta['contribuicoes'] = $stmt->fetchAll();
    }

    return $meta;
}

/**
 * Salva meta (com perfil_id)
 */
function salvarMeta($dados)
{
    global $pdo;

    if (!empty($dados['id'])) {
        $stmt = $pdo->prepare("UPDATE metas SET 
            titulo = ?, descricao = ?, valor_objetivo = ?, valor_mensal = ?,
            data_inicio = ?, data_limite = ?, cor = ?, icone = ?, prioridade = ?
            WHERE id = ?");
        return $stmt->execute([
            $dados['titulo'],
            $dados['descricao'] ?? '',
            $dados['valor_objetivo'],
            $dados['valor_mensal'] ?? 0,
            $dados['data_inicio'] ?? null,
            $dados['data_limite'] ?? null,
            $dados['cor'] ?? '#10b981',
            $dados['icone'] ?? 'fa-bullseye',
            $dados['prioridade'] ?? 'media',
            $dados['id']
        ]);
    } else {
        $perfilId = $dados['perfil_id'] ?? $_SESSION['perfil_id'] ?? null;
        $stmt = $pdo->prepare("INSERT INTO metas 
            (perfil_id, titulo, descricao, valor_objetivo, valor_mensal, data_inicio, data_limite, cor, icone, prioridade) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([
            $perfilId,
            $dados['titulo'],
            $dados['descricao'] ?? '',
            $dados['valor_objetivo'],
            $dados['valor_mensal'] ?? 0,
            $dados['data_inicio'] ?? date('Y-m-d'),
            $dados['data_limite'] ?? null,
            $dados['cor'] ?? '#10b981',
            $dados['icone'] ?? 'fa-bullseye',
            $dados['prioridade'] ?? 'media'
        ]);
    }
}

/**
 * Adiciona contribuição a uma meta
 */
function adicionarContribuicaoMeta($metaId, $valor, $observacao = '', $data = null)
{
    global $pdo;

    try {
        $pdo->beginTransaction();

        // Inserir contribuição
        $stmt = $pdo->prepare("INSERT INTO meta_contribuicoes 
            (meta_id, valor, observacao, data_contribuicao) 
            VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $metaId,
            $valor,
            $observacao,
            $data ?? date('Y-m-d')
        ]);

        // Atualizar valor atual da meta
        $stmt = $pdo->prepare("UPDATE metas SET valor_atual = valor_atual + ? WHERE id = ?");
        $stmt->execute([$valor, $metaId]);

        // Verificar se atingiu a meta
        $meta = obterMeta($metaId);
        if ($meta && $meta['valor_atual'] >= $meta['valor_objetivo'] && !$meta['concluida']) {
            $pdo->prepare("UPDATE metas SET concluida = 1 WHERE id = ?")->execute([$metaId]);

            // Criar alerta de celebração
            criarAlerta(
                'meta_atingida',
                '🎉 Meta Alcançada!',
                "Parabéns! Você atingiu sua meta \"{$meta['titulo']}\" de " . formatarMoeda($meta['valor_objetivo']) . "!",
                'fa-trophy',
                '#10b981',
                'metas.php'
            );
        }

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

/**
 * Exclui meta
 */
function excluirMeta($id)
{
    global $pdo;
    $perfisAtivos = obterPerfisAtivos();
    if (!empty($perfisAtivos)) {
        $placeholders = implode(',', array_fill(0, count($perfisAtivos), '?'));
        $stmt = $pdo->prepare("DELETE FROM metas WHERE id = ? AND perfil_id IN ($placeholders)");
        return $stmt->execute(array_merge([$id], $perfisAtivos));
    }
    $stmt = $pdo->prepare("DELETE FROM metas WHERE id = ?");
    return $stmt->execute([$id]);
}

// ==========================================
// POUPANÇA
// ==========================================

/**
 * Obtém a conta poupança do usuário
 */
function obterContaPoupanca($perfilId = null)
{
    global $pdo;
    $perfilId = $perfilId ?: ($_SESSION['perfil_id'] ?? null);

    if (!$perfilId) return null;

    $stmt = $pdo->prepare("SELECT * FROM contas WHERE tipo = 'poupanca' AND perfil_id = ? LIMIT 1");
    $stmt->execute([$perfilId]);
    return $stmt->fetch();
}

/**
 * Obtém saldo da poupança
 */
function obterSaldoPoupanca($perfilId = null)
{
    $perfilId = $perfilId ?: ($_SESSION['perfil_id'] ?? null);
    $poupanca = obterContaPoupanca($perfilId);
    
    if (!$poupanca) return 0;
    
    return calcularSaldoConta($poupanca['id']);
}

/**
 * Deposita valor na poupança (transferência de outra conta)
 */
function depositarPoupanca($perfilId, $valor, $descricao, $data, $contaOrigemId)
{
    if ($valor <= 0) return false;
    
    global $pdo;
    // Valida se conta de origem pertence ao perfil
    $stmt = $pdo->prepare("SELECT id FROM contas WHERE id = ? AND perfil_id = ?");
    $stmt->execute([$contaOrigemId, $perfilId]);
    if (!$stmt->fetch()) return false;
    
    $poupanca = obterContaPoupanca($perfilId);
    if (!$poupanca) return false;
    
    return salvarTransacao(
        $perfilId, 
        'transferencia', 
        $valor, 
        $descricao, 
        $data, 
        $contaOrigemId, 
        null, 
        'pago', 
        $poupanca['id']
    );
}

/**
 * Retira valor da poupança (transferência para outra conta)
 */
function retirarPoupanca($perfilId, $valor, $descricao, $data, $contaDestinoId)
{
    if ($valor <= 0) return false;
    
    $saldoAtual = obterSaldoPoupanca($perfilId);
    if ($saldoAtual < $valor) return false; // Saldo insuficiente
    
    $poupanca = obterContaPoupanca($perfilId);
    if (!$poupanca) return false;
    
    return salvarTransacao(
        $perfilId, 
        'transferencia', 
        $valor, 
        $descricao, 
        $data, 
        $poupanca['id'], 
        null, 
        'pago', 
        $contaDestinoId
    );
}

/**
 * Lista o histórico de movimentos da poupança
 */
function listarHistoricoPoupanca($perfilId = null, $mes = null, $ano = null)
{
    global $pdo;
    $perfilId = $perfilId ?: ($_SESSION['perfil_id'] ?? null);
    
    $poupanca = obterContaPoupanca($perfilId);
    if (!$poupanca) return [];
    
    $poupancaId = $poupanca['id'];
    
    $sql = "SELECT t.*, 
            c1.nome as conta_origem, 
            c2.nome as conta_destino,
            CASE 
                WHEN t.conta_destino_id = ? THEN 'deposito'
                WHEN t.conta_id = ? THEN 'retirada'
            END as tipo_movimento,
            CASE 
                WHEN t.conta_destino_id = ? THEN c1.nome
                WHEN t.conta_id = ? THEN c2.nome
            END as conta_envolvida
            FROM transacoes t
            LEFT JOIN contas c1 ON t.conta_id = c1.id
            LEFT JOIN contas c2 ON t.conta_destino_id = c2.id
            WHERE (t.conta_id = ? OR t.conta_destino_id = ?)";
            
    $params = [$poupancaId, $poupancaId, $poupancaId, $poupancaId, $poupancaId, $poupancaId];
            
    if ($mes && $ano) {
        $sql .= " AND MONTH(t.data_transacao) = ? AND YEAR(t.data_transacao) = ?";
        $params[] = $mes;
        $params[] = $ano;
    }
    
    $sql .= " ORDER BY t.data_transacao ASC, t.id ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $transacoes = $stmt->fetchAll();
    
    // Calcula o saldo progressivo
    $saldo = $poupanca['saldo_inicial'] ?? 0;
    
    foreach ($transacoes as &$t) {
        if ($t['tipo_movimento'] == 'deposito') {
            $saldo += $t['valor'];
        } else if ($t['tipo_movimento'] == 'retirada') {
            $saldo -= $t['valor'];
        }
        $t['saldo_momento'] = $saldo;
    }
    
    return array_reverse($transacoes); // Mais recentes primeiro
}

/**
 * Obtém resumo geral da poupança
 */
function obterResumoPoupanca($perfilId = null)
{
    global $pdo;
    $perfilId = $perfilId ?: ($_SESSION['perfil_id'] ?? null);
    
    $poupanca = obterContaPoupanca($perfilId);
    if (!$poupanca) {
        return [
            'saldo_atual' => 0,
            'total_depositado' => 0,
            'total_retirado' => 0,
            'qtd_depositos' => 0,
            'qtd_retiradas' => 0
        ];
    }
    
    $poupancaId = $poupanca['id'];
    
    $sql = "SELECT 
            COALESCE(SUM(CASE WHEN conta_destino_id = ? THEN valor ELSE 0 END), 0) as total_depositado,
            COALESCE(SUM(CASE WHEN conta_id = ? THEN valor ELSE 0 END), 0) as total_retirado,
            SUM(CASE WHEN conta_destino_id = ? THEN 1 ELSE 0 END) as qtd_depositos,
            SUM(CASE WHEN conta_id = ? THEN 1 ELSE 0 END) as qtd_retiradas
            FROM transacoes 
            WHERE (conta_id = ? OR conta_destino_id = ?)";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$poupancaId, $poupancaId, $poupancaId, $poupancaId, $poupancaId, $poupancaId]);
    $resumo = $stmt->fetch();
    
    $resumo['saldo_atual'] = obterSaldoPoupanca($perfilId);
    
    return $resumo;
}

// ==========================================
// ALERTAS INTELIGENTES
// ==========================================

/**
 * Cria um novo alerta (associado ao perfil ativo)
 */
function criarAlerta($tipo, $titulo, $mensagem, $icone = 'fa-bell', $cor = '#f59e0b', $acaoUrl = null, $expiracao = null)
{
    global $pdo;
    $perfilId = $_SESSION['perfil_id'] ?? null;

    $stmt = $pdo->prepare("INSERT INTO alertas 
        (perfil_id, tipo, titulo, mensagem, icone, cor, acao_url, data_expiracao) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

    return $stmt->execute([
        $perfilId,
        $tipo,
        $titulo,
        $mensagem,
        $icone,
        $cor,
        $acaoUrl,
        $expiracao
    ]);
}

/**
 * Lista alertas não lidos (filtrado por perfil ativo)
 */
function listarAlertas($apenasNaoLidos = true, $limite = 10)
{
    global $pdo;

    $perfisAtivos = obterPerfisAtivos();
    $params = [];

    $sql = "SELECT * FROM alertas WHERE expirado = 0";

    if (!empty($perfisAtivos)) {
        $placeholders = implode(',', array_fill(0, count($perfisAtivos), '?'));
        $sql .= " AND (perfil_id IN ($placeholders) OR perfil_id IS NULL)";
        $params = array_merge($params, $perfisAtivos);
    }

    if ($apenasNaoLidos) {
        $sql .= " AND lido = 0";
    }
    $sql .= " ORDER BY created_at DESC LIMIT ?";
    $params[] = $limite;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Marca alerta como lido
 */
function marcarAlertaLido($id)
{
    global $pdo;
    $stmt = $pdo->prepare("UPDATE alertas SET lido = 1 WHERE id = ?");
    return $stmt->execute([$id]);
}

/**
 * Marca todos os alertas como lidos (filtrado por perfil ativo)
 */
function marcarTodosAlertasLidos()
{
    global $pdo;
    $perfisAtivos = obterPerfisAtivos();
    if (!empty($perfisAtivos)) {
        $placeholders = implode(',', array_fill(0, count($perfisAtivos), '?'));
        $stmt = $pdo->prepare("UPDATE alertas SET lido = 1 WHERE lido = 0 AND (perfil_id IN ($placeholders) OR perfil_id IS NULL)");
        return $stmt->execute($perfisAtivos);
    }
    return $pdo->exec("UPDATE alertas SET lido = 1 WHERE lido = 0");
}

/**
 * Conta alertas não lidos (filtrado por perfil ativo)
 */
function contarAlertasNaoLidos()
{
    global $pdo;
    $perfisAtivos = obterPerfisAtivos();
    if (!empty($perfisAtivos)) {
        $placeholders = implode(',', array_fill(0, count($perfisAtivos), '?'));
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM alertas WHERE lido = 0 AND expirado = 0 AND (perfil_id IN ($placeholders) OR perfil_id IS NULL)");
        $stmt->execute($perfisAtivos);
        return (int) $stmt->fetchColumn();
    }
    $stmt = $pdo->query("SELECT COUNT(*) FROM alertas WHERE lido = 0 AND expirado = 0");
    return (int) $stmt->fetchColumn();
}

/**
 * Verifica condições e gera alertas automaticamente
 */
function verificarEGerarAlertas()
{
    global $pdo;

    $mes = date('m');
    $ano = date('Y');
    $perfilId = $_SESSION['perfil_id'] ?? null;

    // 1. Verificar limite mensal de gastos
    $limiteMensal = obterLimiteMensal();
    if ($limiteMensal > 0) {
        $resumo = obterResumoMensal($mes, $ano);
        $percentual = ($resumo['total_saidas'] / $limiteMensal) * 100;

        // Alerta 80%
        if ($percentual >= 80 && $percentual < 100) {
            $sqlCheck = "SELECT COUNT(*) FROM alertas WHERE tipo = 'limite_gasto' AND DATE(created_at) = CURDATE()";
            $paramsCheck = [];
            if ($perfilId) {
                $sqlCheck .= " AND perfil_id = ?";
                $paramsCheck[] = $perfilId;
            }
            $stmt = $pdo->prepare($sqlCheck);
            $stmt->execute($paramsCheck);
            if ($stmt->fetchColumn() == 0) {
                criarAlerta(
                    'limite_gasto',
                    '⚠️ Atenção ao Limite!',
                    "Você já usou " . number_format($percentual, 1) . "% do seu limite mensal de gastos.",
                    'fa-exclamation-triangle',
                    '#f59e0b',
                    'index.php'
                );
            }
        }

        // Alerta 100%
        if ($percentual >= 100) {
            $sqlCheck = "SELECT COUNT(*) FROM alertas WHERE tipo = 'limite_gasto' AND mensagem LIKE '%ultrapassou%' AND DATE(created_at) = CURDATE()";
            $paramsCheck = [];
            if ($perfilId) {
                $sqlCheck .= " AND perfil_id = ?";
                $paramsCheck[] = $perfilId;
            }
            $stmt = $pdo->prepare($sqlCheck);
            $stmt->execute($paramsCheck);
            if ($stmt->fetchColumn() == 0) {
                criarAlerta(
                    'limite_gasto',
                    '🚨 Limite Ultrapassado!',
                    "Você ultrapassou o limite mensal! Total gasto: " . formatarMoeda($resumo['total_saidas']),
                    'fa-times-circle',
                    '#ef4444',
                    'relatorios.php'
                );
            }
        }
    }

    // 2. Verificar limite de cartão de crédito
    $resumoContas = obterResumoContas();
    if (!empty($resumoContas['credito']['contas'])) {
        foreach ($resumoContas['credito']['contas'] as $cartao) {
            if ($cartao['limite_credito'] > 0) {
                $usado = abs($cartao['saldo_atual']);
                $percentual = ($usado / $cartao['limite_credito']) * 100;

                if ($percentual >= 80) {
                    $sqlCheck = "SELECT COUNT(*) FROM alertas WHERE tipo = 'limite_credito' AND mensagem LIKE ? AND DATE(created_at) = CURDATE()";
                    $paramsCheck = ['%' . $cartao['nome'] . '%'];
                    if ($perfilId) {
                        $sqlCheck .= " AND perfil_id = ?";
                        $paramsCheck[] = $perfilId;
                    }
                    $stmt = $pdo->prepare($sqlCheck);
                    $stmt->execute($paramsCheck);
                    if ($stmt->fetchColumn() == 0) {
                        criarAlerta(
                            'limite_credito',
                            '💳 Limite do Cartão',
                            "O cartão \"{$cartao['nome']}\" está com " . number_format($percentual, 1) . "% do limite usado.",
                            'fa-credit-card',
                            '#f97316',
                            'contas.php'
                        );
                    }
                }
            }
        }
    }

    // 3. Verificar economia (fim do mês)
    if (date('d') >= 25) {
        $resumo = obterResumoMensal($mes, $ano);
        if ($resumo['saldo'] > 0) {
            $sqlCheck = "SELECT COUNT(*) FROM alertas WHERE tipo = 'economia' AND MONTH(created_at) = ? AND YEAR(created_at) = ?";
            $paramsCheck = [$mes, $ano];
            if ($perfilId) {
                $sqlCheck .= " AND perfil_id = ?";
                $paramsCheck[] = $perfilId;
            }
            $stmt = $pdo->prepare($sqlCheck);
            $stmt->execute($paramsCheck);
            if ($stmt->fetchColumn() == 0) {
                criarAlerta(
                    'economia',
                    '🎊 Parabéns pela Economia!',
                    "Este mês você economizou " . formatarMoeda($resumo['saldo']) . "! Continue assim!",
                    'fa-piggy-bank',
                    '#10b981',
                    'relatorios.php'
                );
            }
        }
    }

    // 4. Parabéns por controle (gastos abaixo de 50% do limite)
    if ($limiteMensal > 0) {
        $resumo = isset($resumo) ? $resumo : obterResumoMensal($mes, $ano);
        $percentual = ($resumo['total_saidas'] / $limiteMensal) * 100;

        if ($percentual <= 50 && $percentual > 0 && date('d') >= 15) {
            $sqlCheck = "SELECT COUNT(*) FROM alertas WHERE tipo = 'parabens_controle' AND MONTH(created_at) = ? AND YEAR(created_at) = ?";
            $paramsCheck = [$mes, $ano];
            if ($perfilId) {
                $sqlCheck .= " AND perfil_id = ?";
                $paramsCheck[] = $perfilId;
            }
            $stmt = $pdo->prepare($sqlCheck);
            $stmt->execute($paramsCheck);
            if ($stmt->fetchColumn() == 0) {
                criarAlerta(
                    'parabens_controle',
                    '👏 Mandou Bem!',
                    "Você só gastou " . number_format($percentual, 0) . "% do limite! Excelente controle financeiro!",
                    'fa-trophy',
                    '#10b981',
                    'index.php'
                );
            }
        }
    }

    // 5. Puxão de orelha (alerta se gastou muito em lazer - filtrado por perfil)
    $perfisAtivos = obterPerfisAtivos();
    $sqlPuxao = "
        SELECT c.nome, SUM(t.valor) as total 
        FROM transacoes t 
        JOIN categorias c ON t.categoria_id = c.id 
        WHERE t.tipo = 'saida' 
        AND MONTH(t.data_transacao) = ? AND YEAR(t.data_transacao) = ?
        AND c.nome IN ('Lazer', 'Assinaturas', 'Roupas', 'Restaurantes')
    ";
    $paramsPuxao = [$mes, $ano];
    if (!empty($perfisAtivos)) {
        $placeholders = implode(',', array_fill(0, count($perfisAtivos), '?'));
        $sqlPuxao .= " AND t.perfil_id IN ($placeholders)";
        $paramsPuxao = array_merge($paramsPuxao, $perfisAtivos);
    }
    $sqlPuxao .= " GROUP BY c.id ORDER BY total DESC LIMIT 1";
    $stmt = $pdo->prepare($sqlPuxao);
    $stmt->execute($paramsPuxao);
    $categoriaAlta = $stmt->fetch();

    if ($categoriaAlta && $limiteMensal > 0) {
        $percentualCategoria = ($categoriaAlta['total'] / $limiteMensal) * 100;
        if ($percentualCategoria >= 25) {
            $sqlCheck = "SELECT COUNT(*) FROM alertas WHERE tipo = 'gasto_superfluo' AND DATE(created_at) = CURDATE()";
            $paramsCheck = [];
            if ($perfilId) {
                $sqlCheck .= " AND perfil_id = ?";
                $paramsCheck[] = $perfilId;
            }
            $stmt = $pdo->prepare($sqlCheck);
            $stmt->execute($paramsCheck);
            if ($stmt->fetchColumn() == 0) {
                criarAlerta(
                    'gasto_superfluo',
                    '😬 Ei, Controlá-se!',
                    "Você gastou " . formatarMoeda($categoriaAlta['total']) . " em {$categoriaAlta['nome']} este mês. Bora economizar?",
                    'fa-hand-holding-usd',
                    '#f59e0b',
                    'relatorios.php'
                );
            }
        }
    }
}

// ==========================================
// CATEGORIAS
// ==========================================

/**
 * Lista categorias (filtradas por perfil ativo)
 */
function listarCategorias($tipo = null)
{
    global $pdo;

    $params = [];
    $sql = "SELECT * FROM categorias WHERE 1=1";

    // Filtro por perfil
    if (function_exists('obterPerfisAtivos')) {
        $perfisAtivos = obterPerfisAtivos();
        if (!empty($perfisAtivos)) {
            $placeholders = implode(',', array_fill(0, count($perfisAtivos), '?'));
            $sql .= " AND perfil_id IN ($placeholders)";
            $params = array_merge($params, $perfisAtivos);
        }
    }

    if ($tipo) {
        $sql .= " AND tipo = ?";
        $params[] = $tipo;
    }

    $sql .= " ORDER BY tipo, nome";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Salva categoria (com perfil_id)
 */
function salvarCategoria($dados)
{
    global $pdo;

    if (!empty($dados['id'])) {
        $stmt = $pdo->prepare("UPDATE categorias SET nome = ?, tipo = ?, cor = ?, icone = ? WHERE id = ?");
        return $stmt->execute([$dados['nome'], $dados['tipo'], $dados['cor'], $dados['icone'] ?? 'fa-tag', $dados['id']]);
    } else {
        $perfilId = $dados['perfil_id'] ?? $_SESSION['perfil_id'] ?? null;
        $stmt = $pdo->prepare("INSERT INTO categorias (perfil_id, nome, tipo, cor, icone) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$perfilId, $dados['nome'], $dados['tipo'], $dados['cor'], $dados['icone'] ?? 'fa-tag']);
    }
}

/**
 * Exclui categoria (com verificação de perfil)
 */
function excluirCategoria($id)
{
    global $pdo;
    $perfisAtivos = obterPerfisAtivos();
    if (!empty($perfisAtivos)) {
        $placeholders = implode(',', array_fill(0, count($perfisAtivos), '?'));
        $stmt = $pdo->prepare("DELETE FROM categorias WHERE id = ? AND perfil_id IN ($placeholders)");
        return $stmt->execute(array_merge([$id], $perfisAtivos));
    }
    $stmt = $pdo->prepare("DELETE FROM categorias WHERE id = ?");
    return $stmt->execute([$id]);
}

// ==========================================
// RESUMOS E ESTATÍSTICAS
// ==========================================

/**
 * Obtém resumo mensal
 * NOTA: Transferências (incluindo movimentações de poupança) são intencionalmente
 * excluídas deste cálculo para não afetar o saldo de receitas/despesas do mês.
 */
function obterResumoMensal($mes = null, $ano = null)
{
    global $pdo;

    $mes = $mes ?: date('m');
    $ano = $ano ?: date('Y');

    // Obter perfis ativos (consolidado ou individual)
    $perfisAtivos = obterPerfisAtivos();

    if (empty($perfisAtivos)) {
        return ['total_entradas' => 0, 'total_saidas' => 0, 'saldo' => 0, 'mes' => $mes, 'ano' => $ano];
    }

    $placeholders = implode(',', array_fill(0, count($perfisAtivos), '?'));
    $params = array_merge([$mes, $ano], $perfisAtivos);

    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(CASE WHEN tipo = 'entrada' THEN valor ELSE 0 END), 0) as total_entradas,
            COALESCE(SUM(CASE WHEN tipo = 'saida' THEN valor ELSE 0 END), 0) as total_saidas
        FROM transacoes 
        WHERE MONTH(data_transacao) = ? AND YEAR(data_transacao) = ?
        AND perfil_id IN ($placeholders)
    ");
    $stmt->execute($params);
    $resultado = $stmt->fetch();

    $resultado['saldo'] = $resultado['total_entradas'] - $resultado['total_saidas'];
    $resultado['mes'] = $mes;
    $resultado['ano'] = $ano;

    return $resultado;
}

/**
 * Obtém saldo total (patrimônio líquido, filtrado por mês/ano se informado)
 */
function obterSaldoTotal($mes = null, $ano = null)
{
    $resumo = obterResumoContas($mes, $ano);
    return $resumo['patrimonio_total'];
}

/**
 * Obtém gastos por categoria
 */
function obterGastosPorCategoria($mes = null, $ano = null)
{
    global $pdo;

    $mes = $mes ?: date('m');
    $ano = $ano ?: date('Y');

    // Obter perfis ativos (consolidado ou individual)
    $perfisAtivos = obterPerfisAtivos();

    if (empty($perfisAtivos)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($perfisAtivos), '?'));
    $params = array_merge([$mes, $ano], $perfisAtivos);

    $stmt = $pdo->prepare("
        SELECT c.nome, c.cor, c.icone, SUM(t.valor) as total
        FROM transacoes t
        JOIN categorias c ON t.categoria_id = c.id
        WHERE t.tipo = 'saida' 
        AND MONTH(t.data_transacao) = ? 
        AND YEAR(t.data_transacao) = ?
        AND t.perfil_id IN ($placeholders)
        GROUP BY c.id, c.nome, c.cor, c.icone
        ORDER BY total DESC
    ");
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Obtém gastos por dia do mês (para gráfico de linha)
 */
function obterGastosDiarios($mes = null, $ano = null)
{
    global $pdo;

    $mes = $mes ?: date('m');
    $ano = $ano ?: date('Y');

    // Número de dias no mês
    $diasNoMes = cal_days_in_month(CAL_GREGORIAN, intval($mes), intval($ano));

    // Inicializar array com todos os dias
    $gastosDiarios = [];
    for ($i = 1; $i <= $diasNoMes; $i++) {
        $gastosDiarios[$i] = 0;
    }

    // Obter perfis ativos (consolidado ou individual)
    $perfisAtivos = obterPerfisAtivos();

    $sql = "
        SELECT DAY(data_transacao) as dia, SUM(valor) as total
        FROM transacoes
        WHERE tipo = 'saida' 
        AND MONTH(data_transacao) = ? 
        AND YEAR(data_transacao) = ?
    ";
    $params = [$mes, $ano];

    if (!empty($perfisAtivos)) {
        $placeholders = implode(',', array_fill(0, count($perfisAtivos), '?'));
        $sql .= " AND perfil_id IN ($placeholders)";
        $params = array_merge($params, $perfisAtivos);
    }

    $sql .= " GROUP BY DAY(data_transacao) ORDER BY dia";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    foreach ($stmt->fetchAll() as $row) {
        $gastosDiarios[$row['dia']] = floatval($row['total']);
    }

    return $gastosDiarios;
}

/**
 * Obtém entradas vs saídas por mês (filtrada por perfil)
 */
function obterEntradasVsSaidas($ano = null)
{
    global $pdo;

    $ano = $ano ?: date('Y');

    $perfisAtivos = function_exists('obterPerfisAtivos') ? obterPerfisAtivos() : [];
    $filtroPerfilSql = '';
    $params = [$ano];

    if (!empty($perfisAtivos)) {
        $placeholders = implode(',', array_fill(0, count($perfisAtivos), '?'));
        $filtroPerfilSql = " AND perfil_id IN ($placeholders)";
        $params = array_merge($params, $perfisAtivos);
    }

    $stmt = $pdo->prepare("
        SELECT 
            MONTH(data_transacao) as mes,
            SUM(CASE WHEN tipo = 'entrada' THEN valor ELSE 0 END) as entradas,
            SUM(CASE WHEN tipo = 'saida' THEN valor ELSE 0 END) as saidas
        FROM transacoes
        WHERE YEAR(data_transacao) = ?
        $filtroPerfilSql
        GROUP BY MONTH(data_transacao)
        ORDER BY mes
    ");
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Obtém evolução patrimonial (filtrada por perfil)
 */
function obterEvolucaoPatrimonial($meses = 12)
{
    global $pdo;

    $perfisAtivos = function_exists('obterPerfisAtivos') ? obterPerfisAtivos() : [];

    $filtroPerfilSql = '';
    $params = [$meses];

    if (!empty($perfisAtivos)) {
        $placeholders = implode(',', array_fill(0, count($perfisAtivos), '?'));
        $filtroPerfilSql = " AND perfil_id IN ($placeholders)";
        $params = array_merge($params, $perfisAtivos);
    }

    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(data_transacao, '%Y-%m') as periodo,
            SUM(CASE 
                WHEN tipo = 'entrada' THEN valor 
                WHEN tipo = 'saida' THEN -valor 
                ELSE 0 
            END) as variacao
        FROM transacoes
        WHERE data_transacao >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
        $filtroPerfilSql
        GROUP BY DATE_FORMAT(data_transacao, '%Y-%m')
        ORDER BY periodo
    ");
    $stmt->execute($params);

    $dados = $stmt->fetchAll();
    $saldoAcumulado = 0;
    $evolucao = [];

    foreach ($dados as $d) {
        $saldoAcumulado += $d['variacao'];
        $evolucao[] = [
            'periodo' => $d['periodo'],
            'saldo' => $saldoAcumulado
        ];
    }

    return $evolucao;
}

// ==========================================
// CONFIGURAÇÕES
// ==========================================

/**
 * Obtém limite mensal (do perfil ativo)
 */
function obterLimiteMensal()
{
    global $pdo;

    // Tentar do perfil ativo
    $perfilId = $_SESSION['perfil_id'] ?? null;
    if ($perfilId) {
        $stmt = $pdo->prepare("SELECT limite_mensal FROM perfis WHERE id = ?");
        $stmt->execute([$perfilId]);
        $resultado = $stmt->fetch();
        if ($resultado && floatval($resultado['limite_mensal']) > 0) {
            return floatval($resultado['limite_mensal']);
        }
    }

    // Fallback para config global (compatibilidade)
    $stmt = $pdo->query("SELECT valor FROM configuracoes WHERE chave = 'limite_mensal'");
    $resultado = $stmt->fetch();
    return $resultado ? floatval($resultado['valor']) : 0;
}

/**
 * Salva limite mensal (no perfil ativo)
 */
function salvarLimiteMensal($valor)
{
    global $pdo;

    $perfilId = $_SESSION['perfil_id'] ?? null;
    if ($perfilId) {
        $stmt = $pdo->prepare("UPDATE perfis SET limite_mensal = ? WHERE id = ?");
        return $stmt->execute([$valor, $perfilId]);
    }

    // Fallback para config global
    $stmt = $pdo->prepare("INSERT INTO configuracoes (chave, valor, tipo) VALUES ('limite_mensal', ?, 'number') ON DUPLICATE KEY UPDATE valor = ?");
    return $stmt->execute([$valor, $valor]);
}

/**
 * Obtém percentual de gasto do limite
 */
function obterPercentualGasto($mes = null, $ano = null)
{
    $limite = obterLimiteMensal();
    if ($limite <= 0)
        return 0;

    $resumo = obterResumoMensal($mes, $ano);
    return min(100, ($resumo['total_saidas'] / $limite) * 100);
}

/**
 * Obtém configuração genérica
 */
function obterConfiguracao($chave, $padrao = null)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT valor, tipo FROM configuracoes WHERE chave = ?");
    $stmt->execute([$chave]);
    $resultado = $stmt->fetch();

    if (!$resultado)
        return $padrao;

    switch ($resultado['tipo']) {
        case 'number':
            return floatval($resultado['valor']);
        case 'boolean':
            return $resultado['valor'] === 'true';
        case 'json':
            return json_decode($resultado['valor'], true);
        default:
            return $resultado['valor'];
    }
}

/**
 * Salva configuração genérica
 */
function salvarConfiguracao($chave, $valor, $tipo = 'string')
{
    global $pdo;

    if ($tipo === 'boolean') {
        $valor = $valor ? 'true' : 'false';
    } elseif ($tipo === 'json') {
        $valor = json_encode($valor);
    }

    $stmt = $pdo->prepare("INSERT INTO configuracoes (chave, valor, tipo) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE valor = ?, tipo = ?");
    return $stmt->execute([$chave, $valor, $tipo, $valor, $tipo]);
}

// ==========================================
// FINBOT - MENSAGENS INTELIGENTES
// ==========================================

/**
 * Obtém mensagem contextual do FinBot
 */
function obterMensagemFinBot()
{
    // Definir fuso horário de Brasília
    date_default_timezone_set('America/Sao_Paulo');

    $mes = date('m');
    $ano = date('Y');
    $hora = (int) date('H');

    $resumo = obterResumoMensal($mes, $ano);
    $limite = obterLimiteMensal();
    $percentualGasto = $limite > 0 ? ($resumo['total_saidas'] / $limite) * 100 : 0;
    $metas = listarMetas(true);

    $mensagens = [];

    // Saudação por horário (Brasília)
    if ($hora >= 5 && $hora < 12) {
        $saudacao = "Bom dia! ☀️";
    } elseif ($hora >= 12 && $hora < 18) {
        $saudacao = "Boa tarde! 🌤️";
    } else {
        $saudacao = "Boa noite! 🌙";
    }

    // Mensagens contextuais
    if ($percentualGasto >= 90) {
        $mensagens[] = [
            'tipo' => 'alerta',
            'texto' => "Cuidado! Você já usou " . number_format($percentualGasto, 0) . "% do seu limite mensal.",
            'icone' => 'fa-exclamation-triangle',
            'cor' => '#ef4444'
        ];
    } elseif ($percentualGasto >= 70) {
        $mensagens[] = [
            'tipo' => 'aviso',
            'texto' => "Atenção! " . number_format($percentualGasto, 0) . "% do limite foi usado. Controle os gastos!",
            'icone' => 'fa-info-circle',
            'cor' => '#f59e0b'
        ];
    } elseif ($resumo['saldo'] > 0) {
        $mensagens[] = [
            'tipo' => 'positivo',
            'texto' => "Ótimo! Você economizou " . formatarMoeda($resumo['saldo']) . " este mês!",
            'icone' => 'fa-thumbs-up',
            'cor' => '#10b981'
        ];
    }

    // Metas próximas de vencer
    foreach ($metas as $meta) {
        if ($meta['data_limite']) {
            $diasRestantes = (strtotime($meta['data_limite']) - time()) / 86400;
            if ($diasRestantes > 0 && $diasRestantes <= 7 && $meta['percentual'] < 100) {
                $mensagens[] = [
                    'tipo' => 'lembrete',
                    'texto' => "Meta \"{$meta['titulo']}\" vence em " . ceil($diasRestantes) . " dias!",
                    'icone' => 'fa-clock',
                    'cor' => '#6366f1'
                ];
                break;
            }
        }
    }

    // Dicas aleatórias
    $dicas = [
        "💡 Dica: Anote todos os gastos, até os pequenos!",
        "💡 Dica: Reserve 20% da renda para emergências.",
        "💡 Dica: Revise suas assinaturas mensalmente.",
        "💡 Dica: Defina metas realistas e acompanhe o progresso.",
        "💡 Dica: Evite compras por impulso, espere 24h.",
    ];

    if (empty($mensagens) || rand(0, 10) > 7) {
        $mensagens[] = [
            'tipo' => 'dica',
            'texto' => $dicas[array_rand($dicas)],
            'icone' => 'fa-lightbulb',
            'cor' => '#8b5cf6'
        ];
    }

    return [
        'saudacao' => $saudacao,
        'mensagens' => $mensagens
    ];
}

/**
 * Retorna ícone do banco ou tipo de conta com base no nome/tipo
 */
function getIconeBanco($nomeBanco, $tipoConta)
{
    $nome = strtolower($nomeBanco);

    // Bancos Brasileiros e Bandeiras
    if (strpos($nome, 'nubank') !== false || strpos($nome, 'nu ') !== false)
        return 'fa-solid fa-n';
    if (strpos($nome, 'inter') !== false)
        return 'fa-solid fa-i';
    if (strpos($nome, 'itau') !== false || strpos($nome, 'itaú') !== false)
        return 'fa-solid fa-u';
    if (strpos($nome, 'bradesco') !== false)
        return 'fa-solid fa-b';
    if (strpos($nome, 'santander') !== false)
        return 'fa-solid fa-s';
    if (strpos($nome, 'caixa') !== false)
        return 'fa-solid fa-c';
    if (strpos($nome, 'banco do brasil') !== false || strpos($nome, 'bb') !== false)
        return 'fa-solid fa-university';
    if (strpos($nome, 'c6') !== false)
        return 'fa-solid fa-6';
    if (strpos($nome, 'xp') !== false)
        return 'fa-solid fa-x';
    if (strpos($nome, 'visa') !== false)
        return 'fa-brands fa-cc-visa';
    if (strpos($nome, 'master') !== false || strpos($nome, 'mastercard') !== false)
        return 'fa-brands fa-cc-mastercard';
    if (strpos($nome, 'amex') !== false || strpos($nome, 'american') !== false)
        return 'fa-brands fa-cc-amex';
    if (strpos($nome, 'elo') !== false)
        return 'fa-solid fa-credit-card';

    // Fallback por tipo de conta
    switch ($tipoConta) {
        case 'corrente':
            return 'fa-solid fa-university';
        case 'poupanca':
            return 'fa-solid fa-piggy-bank';
        case 'credito':
            return 'fa-solid fa-credit-card';
        case 'investimento':
            return 'fa-solid fa-chart-line';
        default:
            return 'fa-solid fa-wallet';
    }
}

// ==========================================
// UTILITÁRIOS
// ==========================================

// formatarMoeda está definida em i18n.php (suporta múltiplas moedas)

/**
 * Formata data para exibição
 */
function formatarData($data)
{
    return date('d/m/Y', strtotime($data));
}

/**
 * Retorna array com nomes dos meses
 */
function getMeses()
{
    return [
        1 => 'Janeiro',
        2 => 'Fevereiro',
        3 => 'Março',
        4 => 'Abril',
        5 => 'Maio',
        6 => 'Junho',
        7 => 'Julho',
        8 => 'Agosto',
        9 => 'Setembro',
        10 => 'Outubro',
        11 => 'Novembro',
        12 => 'Dezembro'
    ];
}

/**
 * Retorna cores por tipo de conta
 */
function getCorTipoConta($tipo)
{
    $cores = [
        'corrente' => '#3b82f6',
        'credito' => '#f59e0b',
        'poupanca' => '#8b5cf6',
        'carteira' => '#10b981'
    ];
    return $cores[$tipo] ?? '#6366f1';
}

/**
 * Retorna ícone por tipo de conta
 */
function getIconeTipoConta($tipo)
{
    $icones = [
        'corrente' => 'fa-university',
        'credito' => 'fa-credit-card',
        'poupanca' => 'fa-piggy-bank',
        'carteira' => 'fa-wallet'
    ];
    return $icones[$tipo] ?? 'fa-wallet';
}

/**
 * Retorna nome amigável do tipo de conta
 */
function getNomeTipoConta($tipo)
{
    $nomes = [
        'corrente' => 'Conta Corrente',
        'credito' => 'Cartão de Crédito',
        'poupanca' => 'Poupança',
        'carteira' => 'Carteira/Dinheiro'
    ];
    return $nomes[$tipo] ?? $tipo;
}
