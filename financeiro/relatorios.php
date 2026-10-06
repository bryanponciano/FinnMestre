<?php
/**
 * =====================================================
 * FinnMestre - Relatórios
 * =====================================================
 */
require_once 'includes/header.php';
require_once 'includes/feature_gate.php';

// Verificar acesso à feature
if (exibirFeatureGate('relatorios_avancados', 'Relatórios Avançados', 'Análise detalhada com gráficos, distribuição por categoria e tendências.')): ?>
    </main>
    <script src="assets/js/app.js"></script>
    </body>

    </html>
    <?php exit; endif; ?>

<?php
$dataInicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : date('Y-m-01');
$dataFim = isset($_GET['data_fim']) ? $_GET['data_fim'] : date('Y-m-t');
$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
$categoria = isset($_GET['categoria']) ? intval($_GET['categoria']) : '';
$conta = isset($_GET['conta']) ? intval($_GET['conta']) : '';
$perfilFiltro = isset($_GET['perfil']) ? $_GET['perfil'] : 'atual';

// Obter lista de perfis do usuário para o seletor
$todosPerfis = function_exists('listarPerfis') ? listarPerfis() : [];
$perfilAtual = function_exists('obterPerfilAtual') ? obterPerfilAtual() : null;
$perfilIdSessao = $_SESSION['perfil_id'] ?? null;

// Determinar quais perfis usar no filtro
$perfisParaRelatorio = [];
if ($perfilFiltro === 'todos') {
    // Consolidado: todos os perfis
    $perfisParaRelatorio = array_column($todosPerfis, 'id');
} elseif ($perfilFiltro === 'atual') {
    // Perfil ativo - usar direto da sessão
    if ($perfilIdSessao) {
        $perfisParaRelatorio = [$perfilIdSessao];
    }
} elseif (is_numeric($perfilFiltro)) {
    // Perfil específico selecionado
    $perfisParaRelatorio = [intval($perfilFiltro)];
}

$filtros = [
    'data_inicio' => $dataInicio,
    'data_fim' => $dataFim
];

// SEMPRE passar perfis explicitamente para garantir isolamento
if (!empty($perfisParaRelatorio)) {
    $filtros['perfil_ids'] = $perfisParaRelatorio;
} elseif ($perfilIdSessao) {
    // Fallback: usar perfil da sessão
    $filtros['perfil_ids'] = [$perfilIdSessao];
}

if ($tipo)
    $filtros['tipo'] = $tipo;
if ($categoria)
    $filtros['categoria_id'] = $categoria;
if ($conta)
    $filtros['conta_id'] = $conta;

$transacoes = listarTransacoes($filtros);
$categorias = listarCategorias();
$contas = listarContas(true);

// Calcular totais
$totalEntradas = 0;
$totalSaidas = 0;
$porCategoria = [];
$porConta = [];

foreach ($transacoes as $t) {
    if ($t['tipo'] === 'entrada') {
        $totalEntradas += $t['valor'];
    } else {
        $totalSaidas += $t['valor'];
    }

    // Por categoria
    $catNome = $t['categoria_nome'] ?: 'Sem categoria';
    $catCor = $t['categoria_cor'] ?: '#64748b';
    if (!isset($porCategoria[$catNome])) {
        $porCategoria[$catNome] = ['total' => 0, 'cor' => $catCor, 'tipo' => $t['tipo']];
    }
    $porCategoria[$catNome]['total'] += $t['valor'];

    // Por conta
    if ($t['conta_nome']) {
        $contaNome = $t['conta_nome'];
        if (!isset($porConta[$contaNome])) {
            $porConta[$contaNome] = ['entradas' => 0, 'saidas' => 0, 'tipo' => $t['conta_tipo'], 'cor' => $t['conta_cor']];
        }
        if ($t['tipo'] === 'entrada') {
            $porConta[$contaNome]['entradas'] += $t['valor'];
        } else {
            $porConta[$contaNome]['saidas'] += $t['valor'];
        }
    }
}

// Ordenar por valor
uasort($porCategoria, fn($a, $b) => $b['total'] <=> $a['total']);
?>

<div class="page-header">
    <h1><i class="fas fa-file-alt" style="color: var(--fm-primary);"></i> Relatórios</h1>
    <p>Análise detalhada das suas finanças
        <?php if ($perfilFiltro === 'todos'): ?>
            <span
                style="background: var(--fm-gradient-primary); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-weight: 600;">—
                Visão Consolidada</span>
        <?php elseif (is_numeric($perfilFiltro) && $perfilFiltro != ($perfilAtual['id'] ?? 0)): ?>
            <?php
            $perfilSelecionado = array_filter($todosPerfis, fn($p) => $p['id'] == $perfilFiltro);
            $perfilSelecionado = reset($perfilSelecionado);
            ?>
            <?php if ($perfilSelecionado): ?>
                <span style="color: var(--fm-primary); font-weight: 600;">—
                    <?= htmlspecialchars($perfilSelecionado['nome']) ?></span>
            <?php endif; ?>
        <?php endif; ?>
    </p>
</div>

<!-- Filtros -->
<div class="form-card">
    <h3><i class="fas fa-filter"></i> Filtros</h3>
    <form method="GET" class="filters-bar">
        <div class="form-group">
            <label>Data Início</label>
            <input type="date" name="data_inicio" class="form-control" value="<?= $dataInicio ?>">
        </div>
        <div class="form-group">
            <label>Data Fim</label>
            <input type="date" name="data_fim" class="form-control" value="<?= $dataFim ?>">
        </div>
        <?php if (count($todosPerfis) > 1): ?>
            <div class="form-group">
                <label><i class="fas fa-user-circle" style="margin-right: 4px;"></i>Perfil</label>
                <select name="perfil" class="form-control">
                    <option value="atual" <?= $perfilFiltro === 'atual' ? 'selected' : '' ?>>
                        <?= $perfilAtual ? htmlspecialchars($perfilAtual['nome']) . ' (atual)' : 'Perfil Atual' ?>
                    </option>
                    <option value="todos" <?= $perfilFiltro === 'todos' ? 'selected' : '' ?>>
                        📊 Todos os Perfis (Consolidado)
                    </option>
                    <?php foreach ($todosPerfis as $p): ?>
                        <?php if ($perfilAtual && $p['id'] == $perfilAtual['id'])
                            continue; ?>
                        <option value="<?= $p['id'] ?>" <?= $perfilFiltro == $p['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
        <div class="form-group">
            <label>Tipo</label>
            <select name="tipo" class="form-control">
                <option value="">Todos</option>
                <option value="entrada" <?= $tipo === 'entrada' ? 'selected' : '' ?>>Entradas</option>
                <option value="saida" <?= $tipo === 'saida' ? 'selected' : '' ?>>Saídas</option>
            </select>
        </div>
        <div class="form-group">
            <label>Categoria</label>
            <select name="categoria" class="form-control">
                <option value="">Todas</option>
                <?php foreach ($categorias as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $categoria == $cat['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Conta</label>
            <select name="conta" class="form-control">
                <option value="">Todas</option>
                <?php foreach ($contas as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $conta == $c['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> Gerar Relatório
            </button>
        </div>
    </form>
</div>

<!-- Botões de Exportação (somente plano Anual) -->
<?php if (!featureBloqueada('exportacao')): ?>
    <div style="margin-bottom: 24px; display: flex; gap: 12px;">
        <a href="api/exportar.php?formato=csv&data_inicio=<?= $dataInicio ?>&data_fim=<?= $dataFim ?>&tipo=<?= $tipo ?>&perfil=<?= $perfilFiltro ?>"
            class="btn btn-secondary">
            <i class="fas fa-file-csv"></i> Exportar CSV
        </a>
        <a href="api/exportar.php?formato=pdf&data_inicio=<?= $dataInicio ?>&data_fim=<?= $dataFim ?>&tipo=<?= $tipo ?>&perfil=<?= $perfilFiltro ?>"
            target="_blank" class="btn btn-secondary">
            <i class="fas fa-file-pdf"></i> Exportar PDF
        </a>
    </div>
<?php else: ?>
    <div style="margin-bottom: 24px; display: flex; gap: 12px; align-items: center;">
        <button class="btn btn-secondary" disabled style="opacity: 0.5; cursor: not-allowed;">
            <i class="fas fa-lock"></i> Exportar CSV
        </button>
        <button class="btn btn-secondary" disabled style="opacity: 0.5; cursor: not-allowed;">
            <i class="fas fa-lock"></i> Exportar PDF
        </button>
        <span style="color: var(--fm-primary); font-size: 0.85rem;">
            <i class="fas fa-crown"></i> Disponível no plano Anual
        </span>
    </div>
<?php endif; ?>

<!-- Resumo do Período -->
<div class="summary-cards">
    <div class="card animate-in">
        <div class="card-header">
            <span class="card-title">Total Entradas</span>
            <div class="card-icon success">
                <i class="fas fa-arrow-up"></i>
            </div>
        </div>
        <div class="card-value success">
            <?= formatarMoeda($totalEntradas) ?>
        </div>
        <div class="card-subtitle">
            <?= count(array_filter($transacoes, fn($t) => $t['tipo'] === 'entrada')) ?> transações
        </div>
    </div>
    <div class="card animate-in" style="animation-delay: 0.1s">
        <div class="card-header">
            <span class="card-title">Total Saídas</span>
            <div class="card-icon danger">
                <i class="fas fa-arrow-down"></i>
            </div>
        </div>
        <div class="card-value danger">
            <?= formatarMoeda($totalSaidas) ?>
        </div>
        <div class="card-subtitle">
            <?= count(array_filter($transacoes, fn($t) => $t['tipo'] === 'saida')) ?> transações
        </div>
    </div>
    <div class="card animate-in" style="animation-delay: 0.2s">
        <div class="card-header">
            <span class="card-title">Balanço do Período</span>
            <div class="card-icon <?= ($totalEntradas - $totalSaidas) >= 0 ? 'success' : 'danger' ?>">
                <i class="fas fa-calculator"></i>
            </div>
        </div>
        <div class="card-value <?= ($totalEntradas - $totalSaidas) >= 0 ? 'success' : 'danger' ?>">
            <?= formatarMoeda($totalEntradas - $totalSaidas) ?>
        </div>
        <div class="card-subtitle">De <?= formatarData($dataInicio) ?> até <?= formatarData($dataFim) ?></div>
    </div>
</div>

<div class="charts-grid">
    <!-- Resumo por Categoria -->
    <div class="chart-card animate-in">
        <h3><i class="fas fa-tags"></i> Resumo por Categoria</h3>
        <?php if (empty($porCategoria)): ?>
            <div class="empty-state">
                <i class="fas fa-folder-open"></i>
                <h4>Sem dados</h4>
                <p>Nenhuma transação no período</p>
            </div>
        <?php else: ?>
            <div style="max-height: 400px; overflow-y: auto;">
                <?php foreach ($porCategoria as $nome => $dados): ?>
                    <?php
                    $total = $dados['tipo'] === 'entrada' ? $totalEntradas : $totalSaidas;
                    $percentual = $total > 0 ? ($dados['total'] / $total) * 100 : 0;
                    ?>
                    <div style="margin-bottom: 16px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                            <span style="display: flex; align-items: center; gap: 10px;">
                                <span class="categoria-dot"
                                    style="background: <?= $dados['cor'] ?>; width: 12px; height: 12px;"></span>
                                <?= htmlspecialchars($nome) ?>
                            </span>
                            <span
                                style="font-weight: 600; color: var(--<?= $dados['tipo'] === 'entrada' ? 'success' : 'danger' ?>); font-family: 'JetBrains Mono', monospace;">
                                <?= formatarMoeda($dados['total']) ?>
                            </span>
                        </div>
                        <div class="limit-bar" style="height: 8px;">
                            <div
                                style="height: 100%; width: <?= $percentual ?>%; background: <?= $dados['cor'] ?>; border-radius: 4px;">
                            </div>
                        </div>
                        <small style="color: var(--text-muted);">
                            <?= number_format($percentual, 1) ?>%
                        </small>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Gráfico de Pizza -->
    <div class="chart-card animate-in" style="animation-delay: 0.1s">
        <h3><i class="fas fa-chart-pie"></i> Distribuição de Gastos</h3>
        <div class="chart-container">
            <?php if (empty($porCategoria)): ?>
                <div class="empty-state">
                    <i class="fas fa-chart-pie"></i>
                    <h4>Sem dados</h4>
                </div>
            <?php else: ?>
                <canvas id="relatorioPieChart"></canvas>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Resumo por Conta -->
<?php if (!empty($porConta)): ?>
    <div class="form-card animate-in">
        <h3><i class="fas fa-wallet"></i> Movimentação por Conta</h3>
        <div
            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; margin-top: 20px;">
            <?php foreach ($porConta as $nome => $dados): ?>
                <div
                    style="padding: 16px; background: var(--bg-glass-light); border-radius: var(--radius-md); border-left: 4px solid <?= getCorTipoConta($dados['tipo']) ?>;">
                    <div style="font-weight: 600; margin-bottom: 8px;"><?= htmlspecialchars($nome) ?></div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; font-size: 0.9rem;">
                        <div>
                            <span style="color: var(--text-muted);">Entradas:</span>
                            <span
                                style="color: var(--success); font-weight: 600;"><?= formatarMoeda($dados['entradas']) ?></span>
                        </div>
                        <div>
                            <span style="color: var(--text-muted);">Saídas:</span>
                            <span style="color: var(--danger); font-weight: 600;"><?= formatarMoeda($dados['saidas']) ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Tabela Detalhada -->
<div class="table-card animate-in">
    <div class="table-header">
        <h3><i class="fas fa-list-alt"></i> Detalhamento (<?= count($transacoes) ?> transações)</h3>
    </div>
    <div class="table-wrapper">
        <?php if (empty($transacoes)): ?>
            <div class="empty-state">
                <i class="fas fa-search"></i>
                <h4>Nenhuma transação encontrada</h4>
                <p>Ajuste os filtros para ver resultados</p>
            </div>
        <?php else: ?>
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
                            <td><?= formatarData($t['data_transacao']) ?></td>
                            <td>
                                <?= htmlspecialchars($t['descricao']) ?>
                                <?php if ($t['total_parcelas'] > 1): ?>
                                    <small
                                        style="color: var(--text-muted);">(<?= $t['parcela_atual'] ?>/<?= $t['total_parcelas'] ?>)</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($t['categoria_nome']): ?>
                                    <span class="categoria-badge">
                                        <span class="categoria-dot" style="background: <?= $t['categoria_cor'] ?>"></span>
                                        <?= htmlspecialchars($t['categoria_nome']) ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: var(--text-muted);">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($t['conta_nome']): ?>
                                    <span class="conta-badge <?= $t['conta_tipo'] ?>">
                                        <i class="fas <?= getIconeTipoConta($t['conta_tipo']) ?>"></i>
                                        <?= htmlspecialchars($t['conta_nome']) ?>
                                    </span>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?= $t['tipo'] ?>"><?= ucfirst($t['tipo']) ?></span>
                            </td>
                            <td
                                style="text-align: right; font-weight: 600; color: var(--<?= $t['tipo'] === 'entrada' ? 'success' : 'danger' ?>);">
                                <?= $t['tipo'] === 'entrada' ? '+' : '-' ?>         <?= formatarMoeda($t['valor']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot style="background: var(--bg-glass-light);">
                    <tr>
                        <td colspan="5" style="font-weight: 600;">Total Entradas</td>
                        <td style="text-align: right; font-weight: 700; color: var(--success);">
                            +<?= formatarMoeda($totalEntradas) ?>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="5" style="font-weight: 600;">Total Saídas</td>
                        <td style="text-align: right; font-weight: 700; color: var(--danger);">
                            -<?= formatarMoeda($totalSaidas) ?>
                        </td>
                    </tr>
                    <tr style="background: rgba(16, 185, 129, 0.1);">
                        <td colspan="5" style="font-weight: 700;">Balanço</td>
                        <td
                            style="text-align: right; font-weight: 700; color: var(--<?= ($totalEntradas - $totalSaidas) >= 0 ? 'success' : 'danger' ?>);">
                            <?= formatarMoeda($totalEntradas - $totalSaidas) ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($porCategoria)): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const dadosCategoria = <?= json_encode(array_map(function ($nome, $dados) {
                return ['nome' => $nome, 'total' => $dados['total'], 'cor' => $dados['cor']];
            }, array_keys($porCategoria), array_values($porCategoria))) ?>;

            const ctx = document.getElementById('relatorioPieChart');
            if (ctx && dadosCategoria.length > 0) {
                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: dadosCategoria.map(item => item.nome),
                        datasets: [{
                            data: dadosCategoria.map(item => item.total),
                            backgroundColor: dadosCategoria.map(item => item.cor),
                            borderWidth: 0,
                            hoverOffset: 15
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '60%',
                        plugins: {
                            legend: {
                                position: 'right',
                                labels: {
                                    color: '#94a3b8',
                                    padding: 12,
                                    usePointStyle: true,
                                    pointStyle: 'circle'
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function (context) {
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = ((context.raw / total) * 100).toFixed(1);
                                        return context.label + ': R$ ' + context.raw.toLocaleString('pt-BR', { minimumFractionDigits: 2 }) + ' (' + percentage + '%)';
                                    }
                                }
                            }
                        }
                    }
                });
            }
        });
    </script>
<?php endif; ?>

</main>
<script src="assets/js/app.js"></script>
</body>

</html>