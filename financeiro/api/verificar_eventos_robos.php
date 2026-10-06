<?php
/**
 * API de Verificação de Eventos para FinBots 3D
 * Detecta situações que disparam os robôs de alerta e mérito
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

header('Content-Type: application/json');

$alertas = [];
$meritos = [];

try {
    // Verificar se robôs estão ativos
    $robotAlertAtivo = obterConfiguracao('robot_alert_ativo', true);
    $robotMeritAtivo = obterConfiguracao('robot_merit_ativo', true);

    if (!$robotAlertAtivo && !$robotMeritAtivo) {
        echo json_encode(['sucesso' => true, 'alertas' => [], 'meritos' => []]);
        exit;
    }

    $mes = date('m');
    $ano = date('Y');
    $limiteGastos = obterLimiteMensal();

    // Obter resumo do mês
    $resumo = obterResumoMensal($mes, $ano);
    $gastosMes = $resumo['total_saidas'] ?? 0;
    $entradasMes = $resumo['total_entradas'] ?? 0;

    // Categorias consideradas supérfluas
    $categoriasSuperfluasConfig = obterConfiguracao('categorias_superfluas', 'Lazer,Assinaturas');
    $categoriasSupérfluas = array_map('trim', explode(',', $categoriasSuperfluasConfig));

    // ==========================================
    // VERIFICAR ALERTAS (ROBÔ BRONCA)
    // ==========================================
    if ($robotAlertAtivo) {
        // 1. Gastos acima de 80% do limite
        if ($limiteGastos > 0) {
            $percentualGasto = ($gastosMes / $limiteGastos) * 100;

            if ($percentualGasto >= 100) {
                $alertas[] = [
                    'tipo' => 'limite_estourado',
                    'mensagem' => "Ei! Você já estourou o limite mensal! 😤 Gasto: " . formatarMoeda($gastosMes),
                    'prioridade' => 3
                ];
            } elseif ($percentualGasto >= 80) {
                $alertas[] = [
                    'tipo' => 'limite_proximo',
                    'mensagem' => "Cuidado! Você já gastou " . round($percentualGasto) . "% do limite mensal! 🚨",
                    'prioridade' => 2
                ];
            }
        }

        // 2. Gastos em categorias supérfluas acima de 30% do total
        $gastosSupérfluos = obterGastosPorCategoriasSuperfulas($categoriasSupérfluas, $mes, $ano);
        if ($gastosMes > 0) {
            $percentualSupérfluo = ($gastosSupérfluos / $gastosMes) * 100;
            if ($percentualSupérfluo >= 30) {
                $alertas[] = [
                    'tipo' => 'superfluo_alto',
                    'mensagem' => "Atenção! " . round($percentualSupérfluo) . "% dos gastos são em diversão e lazer!",
                    'prioridade' => 1
                ];
            }
        }

        // 3. Saldo negativo em alguma conta
        $contas = listarContas(true);
        foreach ($contas as $conta) {
            if ($conta['tipo'] !== 'credito') {
                $saldo = calcularSaldoConta($conta['id']);
                if ($saldo < 0) {
                    $alertas[] = [
                        'tipo' => 'saldo_negativo',
                        'mensagem' => "A conta {$conta['nome']} está no vermelho! Saldo: " . formatarMoeda($saldo),
                        'prioridade' => 3
                    ];
                    break; // Só mostra um alerta de saldo
                }
            }
        }
    }

    // ==========================================
    // VERIFICAR MÉRITOS (ROBÔ RECOMPENSA)
    // ==========================================
    if ($robotMeritAtivo) {
        // 1. Gastos abaixo de 50% do limite
        if ($limiteGastos > 0 && $gastosMes > 0) {
            $percentualGasto = ($gastosMes / $limiteGastos) * 100;

            if ($percentualGasto <= 50) {
                $meritos[] = [
                    'tipo' => 'controle_excelente',
                    'mensagem' => "Parabéns! 🎉 Você só usou " . round($percentualGasto) . "% do limite! Continue assim!",
                    'prioridade' => 2
                ];
            }
        }

        // 2. Metas atingidas recentemente
        $metas = listarMetas(false);
        foreach ($metas as $meta) {
            if ($meta['percentual'] >= 100 && !isset($_SESSION['meta_celebrada_' . $meta['id']])) {
                $meritos[] = [
                    'tipo' => 'meta_atingida',
                    'mensagem' => "Incrível! ⭐ Você atingiu a meta '{$meta['titulo']}'!",
                    'prioridade' => 3
                ];
                $_SESSION['meta_celebrada_' . $meta['id']] = true;
                break;
            }
        }

        // 3. Mais entradas que saídas no mês
        if ($entradasMes > $gastosMes && $gastosMes > 0) {
            $economia = $entradasMes - $gastosMes;
            $meritos[] = [
                'tipo' => 'saldo_positivo',
                'mensagem' => "Fantástico! 🏆 Você está economizando " . formatarMoeda($economia) . " este mês!",
                'prioridade' => 1
            ];
        }

        // 4. Sem gastos supérfluos em 7 dias
        $gastosRecentes = obterGastosPorCategoriasSuperfulas($categoriasSupérfluas, $mes, $ano, 7);
        if ($gastosRecentes == 0 && $gastosMes > 0) {
            $meritos[] = [
                'tipo' => 'disciplina',
                'mensagem' => "Mandou bem! 👏 Nenhum gasto supérfluo nos últimos 7 dias!",
                'prioridade' => 1
            ];
        }
    }

    // Ordenar por prioridade
    usort($alertas, fn($a, $b) => $b['prioridade'] <=> $a['prioridade']);
    usort($meritos, fn($a, $b) => $b['prioridade'] <=> $a['prioridade']);

    // Não mostrar mérito se houver alerta de alta prioridade
    if (!empty($alertas) && $alertas[0]['prioridade'] >= 2) {
        $meritos = [];
    }

    echo json_encode([
        'sucesso' => true,
        'alertas' => $alertas,
        'meritos' => $meritos,
        'debug' => [
            'gastos_mes' => $gastosMes,
            'limite' => $limiteGastos,
            'percentual' => $limiteGastos > 0 ? round(($gastosMes / $limiteGastos) * 100) : 0
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'sucesso' => false,
        'erro' => 'Erro ao verificar eventos: ' . $e->getMessage()
    ]);
}

/**
 * Obtém total de gastos em categorias supérfluas
 */
function obterGastosPorCategoriasSuperfulas($categorias, $mes, $ano, $diasRecentes = null)
{
    global $pdo;

    if (empty($categorias))
        return 0;

    $placeholders = implode(',', array_fill(0, count($categorias), '?'));

    $sql = "SELECT COALESCE(SUM(t.valor), 0) as total 
            FROM transacoes t 
            JOIN categorias c ON t.categoria_id = c.id 
            WHERE t.tipo = 'saida' 
            AND c.nome IN ($placeholders)
            AND MONTH(t.data_transacao) = ? 
            AND YEAR(t.data_transacao) = ?";

    $params = array_merge($categorias, [$mes, $ano]);

    if ($diasRecentes) {
        $sql .= " AND t.data_transacao >= DATE_SUB(CURDATE(), INTERVAL ? DAY)";
        $params[] = $diasRecentes;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return (float) $stmt->fetch()['total'];
}
