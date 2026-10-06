<?php
/**
 * API: Exportar Relatórios
 */
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/feature_gate.php';

// Verificar se o plano permite exportação
$formato = $_GET['formato'] ?? 'csv';
$feature = ($formato === 'csv') ? 'exportacao' : 'relatorios_avancados';

if (featureBloqueada($feature)) {
    die("Acesso negado. Seu plano não permite esta funcionalidade.");
}
$dataInicio = $_GET['data_inicio'] ?? date('Y-m-01');
$dataFim = $_GET['data_fim'] ?? date('Y-m-t');
$tipo = $_GET['tipo'] ?? '';
$perfilFiltro = $_GET['perfil'] ?? 'atual';

// Determinar quais perfis usar no filtro
$todosPerfis = function_exists('listarPerfis') ? listarPerfis() : [];
$perfilAtual = function_exists('obterPerfilAtual') ? obterPerfilAtual() : null;
$perfilIdSessao = $_SESSION['perfil_id'] ?? null;

$perfisParaExportar = [];
if ($perfilFiltro === 'todos') {
    $perfisParaExportar = array_column($todosPerfis, 'id');
} elseif ($perfilFiltro === 'atual') {
    // Usar direto da sessão
    if ($perfilIdSessao) {
        $perfisParaExportar = [$perfilIdSessao];
    }
} elseif (is_numeric($perfilFiltro)) {
    $perfisParaExportar = [intval($perfilFiltro)];
}

$filtros = [
    'data_inicio' => $dataInicio,
    'data_fim' => $dataFim
];

// SEMPRE passar perfis explicitamente para garantir isolamento
if (!empty($perfisParaExportar)) {
    $filtros['perfil_ids'] = $perfisParaExportar;
} elseif ($perfilIdSessao) {
    // Fallback: usar perfil da sessão
    $filtros['perfil_ids'] = [$perfilIdSessao];
}

if ($tipo)
    $filtros['tipo'] = $tipo;

$transacoes = listarTransacoes($filtros);

if ($formato === 'csv') {
    // Exportar CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="FinnMestre_relatorio_' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');

    // BOM para UTF-8
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    // Cabeçalho
    fputcsv($output, ['Data', 'Descrição', 'Categoria', 'Conta', 'Tipo', 'Valor'], ';');

    // Dados
    foreach ($transacoes as $t) {
        fputcsv($output, [
            formatarData($t['data_transacao']),
            $t['descricao'],
            $t['categoria_nome'] ?? 'Sem categoria',
            $t['conta_nome'] ?? '-',
            ucfirst($t['tipo']),
            number_format($t['valor'], 2, ',', '.')
        ], ';');
    }

    // Totais
    $totalEntradas = array_sum(array_map(fn($t) => $t['tipo'] === 'entrada' ? $t['valor'] : 0, $transacoes));
    $totalSaidas = array_sum(array_map(fn($t) => $t['tipo'] === 'saida' ? $t['valor'] : 0, $transacoes));

    fputcsv($output, [], ';');
    fputcsv($output, ['', '', '', '', 'Total Entradas', number_format($totalEntradas, 2, ',', '.')], ';');
    fputcsv($output, ['', '', '', '', 'Total Saídas', number_format($totalSaidas, 2, ',', '.')], ';');
    fputcsv($output, ['', '', '', '', 'Balanço', number_format($totalEntradas - $totalSaidas, 2, ',', '.')], ';');

    fclose($output);
    exit;
}

// Formato HTML para impressão/PDF
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Relatório FinnMestre -
        <?= date('d/m/Y') ?>
    </title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            color: #f8fafc;
            background: #0f172a;
            padding: 40px;
            line-height: 1.5;
        }

        .no-print {
            text-align: right;
            margin-bottom: 30px;
        }

        button {
            background: #8b5cf6;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.2s;
        }

        button:hover {
            background: #7c3aed;
        }

        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 40px;
            border-bottom: 2px solid #1e293b;
            padding-bottom: 24px;
        }

        .logo-area h1 {
            font-size: 26px;
            color: #a78bfa;
            font-weight: 800;
            letter-spacing: -0.02em;
            margin-bottom: 4px;
        }

        .logo-area p {
            font-size: 14px;
            color: #94a3b8;
        }

        .report-info {
            text-align: right;
        }

        .report-info strong {
            display: block;
            font-size: 16px;
            color: #f1f5f9;
            margin-bottom: 4px;
        }

        .report-info p {
            font-size: 13px;
            color: #94a3b8;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        th {
            background: #1e293b;
            color: #cbd5e1;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.05em;
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid #334155;
        }

        td {
            padding: 14px 16px;
            border-bottom: 1px solid #1e293b;
            font-size: 13px;
        }

        tr:nth-child(even) {
            background: #162032;
        }

        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 700;
            font-size: 10px;
            text-transform: uppercase;
        }

        .badge-entrada {
            background: rgba(6, 214, 160, 0.15);
            color: #06d6a0;
        }

        .badge-saida {
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
        }

        .val-entrada {
            color: #06d6a0;
            font-weight: 600;
        }

        .val-saida {
            color: #ef4444;
            font-weight: 600;
        }

        .summary-card {
            background: #1e293b;
            border-radius: 12px;
            padding: 24px;
            margin-left: auto;
            max-width: 340px;
            border: 1px solid #334155;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 14px;
        }

        .summary-row span:first-child {
            color: #94a3b8;
        }

        .summary-row span:last-child {
            font-weight: 700;
            color: #f1f5f9;
        }

        .summary-row.total {
            margin-bottom: 0;
            padding-top: 12px;
            border-top: 1px solid #334155;
            margin-top: 12px;
            font-size: 18px;
        }

        .summary-row.total span:last-child {
            color: #a78bfa;
        }

        .footer {
            margin-top: 60px;
            text-align: center;
            font-size: 12px;
            color: #64748b;
            border-top: 1px solid #1e293b;
            padding-top: 24px;
        }

        @media print {
            body {
                padding: 0;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                background-color: #0f172a !important;
            }

            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div class="no-print">
        <button onclick="window.print()">
            <i class="fas fa-print"></i> Imprimir / Salvar PDF
        </button>
    </div>

    <div class="report-header">
        <div class="logo-area">
            <h1>FinnMestre</h1>
            <p>Sua vida financeira sob controle</p>
        </div>
        <div class="report-info">
            <strong>Relatório Financeiro</strong>
            <p>Período: <?= formatarData($dataInicio) ?> - <?= formatarData($dataFim) ?></p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Data</th>
                <th>Descrição</th>
                <th>Categoria</th>
                <th>Conta</th>
                <th>Tipo</th>
                <th style="text-align: right;">Valor</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($transacoes as $t): ?>
                <tr>
                    <td style="color: #64748b;"><?= formatarData($t['data_transacao']) ?></td>
                    <td style="font-weight: 500;"><?= htmlspecialchars($t['descricao']) ?></td>
                    <td><?= htmlspecialchars($t['categoria_nome'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($t['conta_nome'] ?? '-') ?></td>
                    <td>
                        <span class="badge badge-<?= $t['tipo'] ?>">
                            <?= $t['tipo'] === 'entrada' ? 'Receita' : 'Despesa' ?>
                        </span>
                    </td>
                    <td style="text-align: right;" class="val-<?= $t['tipo'] ?>">
                        <?= formatarMoeda($t['valor']) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php
    $totalEntradas = array_sum(array_map(fn($t) => $t['tipo'] === 'entrada' ? $t['valor'] : 0, $transacoes));
    $totalSaidas = array_sum(array_map(fn($t) => $t['tipo'] === 'saida' ? $t['valor'] : 0, $transacoes));
    $saldo = $totalEntradas - $totalSaidas;
    ?>

    <div class="summary-card">
        <div class="summary-row">
            <span>Total Receitas</span>
            <span style="color: #16a34a;"><?= formatarMoeda($totalEntradas) ?></span>
        </div>
        <div class="summary-row">
            <span>Total Despesas</span>
            <span style="color: #dc2626;"><?= formatarMoeda($totalSaidas) ?></span>
        </div>
        <div class="summary-row total">
            <span>Balanço Líquido</span>
            <span style="color: <?= $saldo >= 0 ? '#8b5cf6' : '#dc2626' ?>;">
                <?= formatarMoeda($saldo) ?>
            </span>
        </div>
    </div>

    <div class="footer">
        Gerado em <?= date('d/m/Y H:i') ?> • FinnMestre — Consciência Financeira
    </div>
</body>

</html>