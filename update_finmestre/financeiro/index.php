<?php
/**
 * =====================================================
 * FinnMestre - Dashboard Principal
 * =====================================================
 */
require_once 'includes/header.php';
require_once 'includes/quotes.php';

$mes = isset($_GET['mes']) ? intval($_GET['mes']) : date('m');
$ano = isset($_GET['ano']) ? intval($_GET['ano']) : date('Y');

// Dados do Assistente IA (análise local, sem depender de API)
$analiseIA = null;
$alertasIA = [];
$mensagemProativaIA = '';
try {
    if (file_exists(__DIR__ . '/includes/ia_financeiro.php')) {
        require_once 'includes/ia_financeiro.php';
        $analiseIA = gerarAnaliseAutomatica($_SESSION['perfil_id'] ?? $_SESSION['usuario_id'] ?? 0);
        $alertasIA = $analiseIA['alertas'] ?? [];
        $mensagemProativaIA = $analiseIA['mensagem_proativa'] ?? $analiseIA['resumo'] ?? '';
    }
} catch (Exception $e) {
    // Graceful degradation - dashboard funciona sem IA
}

// Dados do dashboard
$meses = getMeses();
$mesInt = is_numeric($mes) ? intval($mes) : 1;
$resumo = obterResumoMensal($mes, $ano);
$saldoTotal = obterSaldoTotal($mes, $ano);
$limiteMensal = obterLimiteMensal();
$percentualGasto = obterPercentualGasto($mes, $ano);
$gastosPorCategoria = obterGastosPorCategoria($mes, $ano);
$entradasVsSaidas = obterEntradasVsSaidas($ano);
$ultimasTransacoes = listarTransacoes(['mes' => $mes, 'ano' => $ano, 'limite' => 8]);
$resumoContas = obterResumoContas($mes, $ano);
$metas = listarMetas(true);
$gastosDiarios = obterGastosDiarios($mes, $ano);

// Dados comportamentais
$behavSalario = calcularPercentualSalarioRestante($mes, $ano);
$behavMicroPoupanca = calcularMicroPoupanca(15); // R$ 15/dia como padrão

// Dados do usuário para saudação
$apelido = $_SESSION['nome'] ?? 'Usuário';
if (isset($_SESSION['usuario_id'])) {
    try {
        $stmtUser = $pdo->prepare("SELECT nome FROM usuarios WHERE id = ?");
        $stmtUser->execute([$_SESSION['usuario_id']]);
        $dadosUser = $stmtUser->fetch();
        $apelido = $dadosUser['nome'] ?? 'Usuário';
        // Tentar pegar apelido se existir
        $firstName = explode(' ', $apelido)[0];
        $apelido = $firstName;
    } catch (Exception $e) {
        // Manter valor padrão
    }
}

// Determinar classe da barra de limite
if ($percentualGasto >= 90) {
    $barraClasse = 'danger';
} elseif ($percentualGasto >= 70) {
    $barraClasse = 'warning';
} else {
    $barraClasse = 'safe';
}
?>

<!-- Dashboard Header Integrado -->
<div class="dashboard-header animate-in" style="margin-bottom: 10px;">
    <div>
        <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
            <p style="color: var(--fm-text-secondary); margin: 0; font-size: 0.9rem; width: 100%;">
                <i class="fas fa-chart-pie" style="color: var(--fm-primary); margin-right: 4px;"></i>
                Suas finanças em <strong><?= $meses[$mesInt] ?></strong> de <strong><?= $ano ?></strong>
            </p>
            <!-- Filtros de Período -->
            <form method="GET" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                <select name="mes" class="form-control" style="min-width: 100px; padding: 6px 10px; font-size: 0.85rem;"
                    onchange="this.form.submit()">
                    <?php foreach ($meses as $num => $nome): ?>
                        <option value="<?= $num ?>" <?= $num == $mes ? 'selected' : '' ?>><?= $nome ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="ano" class="form-control" style="min-width: 75px; padding: 6px 10px; font-size: 0.85rem;"
                    onchange="this.form.submit()">
                    <?php for ($a = date('Y') + 1; $a >= date('Y') - 5; $a--): ?>
                        <option value="<?= $a ?>" <?= $a == $ano ? 'selected' : '' ?>><?= $a ?></option>
                    <?php endfor; ?>
                </select>
            </form>
        </div>
    </div>
</div>

<style>
/* Dashboard XRii Layout */
.xr-grid {
    display: grid;
    grid-template-columns: 2.2fr 1fr;
    gap: 14px;
    margin-bottom: 14px;
}
@media (max-width: 1024px) {
    .xr-grid { grid-template-columns: 1fr; }
}
.xr-col {
    display: flex;
    flex-direction: column;
    gap: 14px;
}
.xr-row {
    display: flex;
    gap: 14px;
}
@media (max-width: 768px) {
    .xr-row { flex-direction: column; }
}
.xr-card {
    background: #1a1d24;
    border-radius: 14px;
    padding: 16px;
    border: 1px solid rgba(255,255,255,0.05);
}
.xr-card h3 {
    margin: 0 0 12px 0;
    color: white;
    font-size: 1rem;
    font-weight: 600;
}
.xr-io-card {
    flex: 1;
    background: #1a1d24;
    border-radius: 14px;
    padding: 14px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    border: 1px solid rgba(255,255,255,0.05);
}
.xr-io-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
}
.xr-io-icon {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1rem;
}
.xr-io-icon.income { background: #0084ff; }
.xr-io-icon.outcome { background: #0084ff; }
.xr-io-label { color: #8b95a5; font-size: 0.85rem; font-weight: 500; }
.xr-io-value { font-size: 1.5rem; font-weight: 700; color: white; margin-bottom: 4px; display: flex; align-items: center; gap: 10px; }
.xr-io-badge { display: inline-block; padding: 4px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; }
.xr-io-badge.positive { background: rgba(6, 214, 160, 0.15); color: #06d6a0; }
.xr-io-badge.negative { background: rgba(239, 68, 68, 0.15); color: #ef4444; }

/* My Card styling */
.xr-my-card {
    background: linear-gradient(135deg, #0062ff, #00a3ff);
    border-radius: 16px;
    padding: 24px;
    color: white;
    position: relative;
    overflow: hidden;
    margin-bottom: 20px;
    box-shadow: 0 10px 30px rgba(0, 98, 255, 0.3);
}
.xr-my-card::before {
    content: '';
    position: absolute;
    top: -50px;
    right: -50px;
    width: 150px;
    height: 150px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
}
.xr-my-card::after {
    content: '';
    position: absolute;
    bottom: -30px;
    left: -30px;
    width: 100px;
    height: 100px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
}
.xr-my-card p {
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.9rem;
    margin: 0 0 4px 0;
}
.xr-my-card h2 {
    font-size: 2.2rem;
    font-weight: 700;
    margin: 0 0 24px 0;
}
.xr-my-card .card-details {
    display: flex;
    justify-content: space-between;
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.9rem;
    color: rgba(255, 255, 255, 0.9);
}

.xr-card-actions {
    display: flex;
    gap: 12px;
}
.xr-card-actions button, .xr-card-actions a {
    flex: 1;
    padding: 12px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s;
    text-align: center;
    text-decoration: none;
}
.xr-btn-primary {
    background: #0084ff;
    color: white;
    border: none;
}
.xr-btn-primary:hover { background: #0062ff; }
.xr-btn-secondary {
    background: transparent;
    color: white;
    border: 1px solid rgba(255,255,255,0.2);
}
.xr-btn-secondary:hover { background: rgba(255,255,255,0.05); }

/* Payment List */
.xr-payment-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
    height: 250px;
    overflow-y: auto;
    padding-right: 8px;
}
.xr-payment-item {
    display: flex;
    align-items: center;
    gap: 12px;
}
.xr-payment-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: rgba(255,255,255,0.05);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}
.xr-payment-details {
    flex: 1;
}
.xr-payment-title {
    color: white;
    font-weight: 500;
    font-size: 0.95rem;
    margin-bottom: 4px;
}
.xr-payment-subtitle {
    color: #8b95a5;
    font-size: 0.8rem;
}
.xr-payment-amount {
    color: white;
    font-weight: 600;
    font-size: 0.95rem;
    text-align: right;
}
.xr-payment-amount.positive { color: #06d6a0; }
.xr-payment-amount.negative { color: #ef4444; }
</style>

<div class="xr-grid">
    <!-- Bloco Esquerdo -->
    <div class="xr-col">
        <!-- Linha Topo Esquerdo: Income e Outcome -->
        <div class="xr-row">
            <div class="xr-io-card">
                <div class="xr-io-header">
                    <div class="xr-io-icon income" style="background: rgba(6, 214, 160, 0.1); color: #06d6a0;"><i class="fas fa-arrow-up"></i></div>
                    <span class="xr-io-label">Total Receitas</span>
                </div>
                <div class="xr-io-value">
                    <?= formatarMoeda($resumo['total_entradas']) ?>
                </div>
            </div>
            
            <div class="xr-io-card">
                <div class="xr-io-header">
                    <div class="xr-io-icon outcome" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;"><i class="fas fa-arrow-down"></i></div>
                    <span class="xr-io-label">Total Despesas</span>
                </div>
                <div class="xr-io-value">
                    <?= formatarMoeda($resumo['total_saidas']) ?>
                </div>
            </div>
        </div>
        
        <!-- Gráfico Central Analytics -->
        <div class="xr-card">
            <h3>Estatísticas <span style="font-size: 12px; float: right; font-weight: normal; color: #8b95a5;"><?= $ano ?></span></h3>
            <div style="height: 200px; position: relative;">
                <canvas id="barChart"></canvas>
            </div>
        </div>

        <!-- Linha Base Esquerda: Activity e Payment -->
        <div class="xr-row">
            <div class="xr-card" style="flex: 1;">
                <h3>Atividades <span style="font-size: 12px; float: right; font-weight: normal; color: #8b95a5;">Diárias</span></h3>
                <div style="height: 180px; position: relative;">
                    <?php if (array_sum($gastosDiarios) == 0): ?>
                        <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: #8b95a5;">Sem gastos</div>
                    <?php else: ?>
                        <canvas id="lineChart"></canvas>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="xr-card" style="flex: 1;">
                <h3>Transações <a href="transacoes.php" style="font-size: 12px; float: right; font-weight: normal; color: #0084ff; text-decoration: none;">Ver Todos</a></h3>
                <div class="xr-payment-list">
                    <?php if (empty($ultimasTransacoes)): ?>
                        <div style="text-align: center; color: #8b95a5; padding-top: 40px;">Nenhuma transação</div>
                    <?php else: ?>
                        <?php foreach ($ultimasTransacoes as $t): ?>
                            <div class="xr-payment-item">
                                <div class="xr-payment-icon">
                                    <i class="fas <?= getIconeTipoConta($t['conta_tipo']) ?>"></i>
                                </div>
                                <div class="xr-payment-details">
                                    <div class="xr-payment-title"><?= htmlspecialchars($t['descricao']) ?></div>
                                    <div class="xr-payment-subtitle"><?= htmlspecialchars($t['conta_nome'] ?? 'Conta') ?></div>
                                </div>
                                <div class="xr-payment-amount <?= $t['tipo'] === 'entrada' ? 'positive' : ($t['tipo'] === 'transferencia' ? '' : 'negative') ?>">
                                    <?= $t['tipo'] === 'entrada' ? '+' : ($t['tipo'] === 'transferencia' ? '↔' : '-') ?> <?= formatarMoeda($t['valor']) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Nova Linha: Contas e Metas -->
        <div class="xr-row">
            <!-- Contas Organizadas -->
            <?php if (!empty($resumoContas)): ?>
                <div class="xr-card" style="flex: 1; display: flex; flex-direction: column; gap: 12px;">
                    <h3>Minhas Contas</h3>
                    
                    <div style="display: flex; gap: 10px;">
                        <div style="flex: 1; background: rgba(255,255,255,0.02); padding: 12px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                                <div style="background: #0084ff; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; border-radius: 6px; flex-shrink: 0; color: white; font-size: 0.8rem;"><i class="fas fa-university"></i></div>
                                <span style="color: #8b95a5; font-size: 0.8rem; line-height: 1.2;">C. Corrente</span>
                            </div>
                            <div style="font-size: 1rem; font-weight: bold; color: white;">
                                <?= formatarMoeda($resumoContas['corrente']['total']) ?>
                            </div>
                        </div>

                        <div style="flex: 1; background: rgba(255,255,255,0.02); padding: 12px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                                <div style="background: #8b5cf6; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; border-radius: 6px; flex-shrink: 0; color: white; font-size: 0.8rem;"><i class="fas fa-piggy-bank"></i></div>
                                <span style="color: #8b95a5; font-size: 0.8rem; line-height: 1.2;">Poupança</span>
                            </div>
                            <div style="font-size: 1rem; font-weight: bold; color: white;">
                                <?= formatarMoeda($resumoContas['poupanca']['total']) ?>
                            </div>
                        </div>
                    </div>
                    
                    <div style="background: rgba(255,255,255,0.02); padding: 12px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 4px;">
                            <div style="background: #f59e0b; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; border-radius: 6px; flex-shrink: 0; color: white; font-size: 0.8rem;"><i class="fas fa-credit-card"></i></div>
                            <span style="color: #8b95a5; font-size: 0.85rem;">Cartão de Crédito</span>
                            <div style="margin-left: auto; font-size: 1rem; font-weight: bold; color: #f59e0b;">
                                <?= formatarMoeda(abs($resumoContas['credito']['usado'])) ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Metas em Destaque -->
            <?php if (!empty($metas)): ?>
                <div class="xr-card" style="flex: 1; display: flex; flex-direction: column; gap: 12px;">
                    <h3>Suas Metas <a href="metas.php" style="font-size: 12px; float: right; font-weight: normal; color: #0084ff; text-decoration: none;">Ver Todas</a></h3>
                    
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <?php foreach (array_slice($metas, 0, 2) as $meta): ?>
                            <div style="background: rgba(255,255,255,0.02); border-radius: 12px; padding: 12px; border: 1px solid rgba(255,255,255,0.05);">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                                    <span style="color: white; font-weight: 500; font-size: 0.85rem;"><?= htmlspecialchars($meta['titulo']) ?></span>
                                    <span style="color: <?= $meta['cor'] ?>; font-weight: bold; font-size: 0.85rem;"><?= number_format($meta['percentual'], 0) ?>%</span>
                                </div>
                                <div style="height: 4px; background: rgba(255,255,255,0.1); border-radius: 2px; margin-bottom: 4px; overflow: hidden;">
                                    <div style="height: 100%; width: <?= $meta['percentual'] ?>%; background: <?= $meta['cor'] ?>; border-radius: 2px;"></div>
                                </div>
                                <div style="display: flex; justify-content: space-between; font-size: 0.75rem; color: #8b95a5;">
                                    <span><?= formatarMoeda($meta['valor_atual']) ?></span>
                                    <span><?= formatarMoeda($meta['valor_objetivo']) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bloco Direito -->
    <div class="xr-col">
        <!-- My Card -->
        <div class="xr-card" style="position: relative; overflow: hidden; display: flex; flex-direction: column; justify-content: center; min-height: 200px;">
            <div style="position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: radial-gradient(circle, rgba(0,132,255,0.15) 0%, transparent 60%); pointer-events: none;"></div>
            <h3 style="margin-bottom: 20px; z-index: 1;">Meu Patrimônio</h3>
            
            <?php 
                $saldoPoupanca = $resumoContas['poupanca']['total'] ?? 0;
                $saldoPrincipal = $saldoTotal - $saldoPoupanca;
            ?>
            <div style="z-index: 1; margin-bottom: 15px;">
                <p style="color: #8b95a5; font-size: 0.95rem; margin-bottom: 4px;">Saldo Principal</p>
                <h2 style="font-size: 2.2rem; font-weight: 800; background: linear-gradient(135deg, #0084ff, #06d6a0); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin: 0; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));"><?= formatarMoeda($saldoPrincipal) ?></h2>
            </div>
            
            <div style="z-index: 1; margin-bottom: 30px;">
                <p style="color: #8b95a5; font-size: 0.85rem; margin-bottom: 4px;"><i class="fas fa-piggy-bank"></i> Poupança</p>
                <h3 style="font-size: 1.4rem; font-weight: 700; color: #10b981; margin: 0; filter: drop-shadow(0 1px 2px rgba(0,0,0,0.2));"><?= formatarMoeda($saldoPoupanca) ?></h3>
            </div>
            
            <div class="xr-card-actions" style="z-index: 1;">
                <a href="contas.php" class="xr-btn-primary">Gerenciar</a>
                <a href="transacoes.php" class="xr-btn-secondary">Transferir</a>
            </div>
        </div>

        <!-- Activity Donut Chart -->
        <div class="xr-card">
            <h3>Atividades <span style="font-size: 12px; float: right; font-weight: normal; color: #8b95a5;">por Categoria</span></h3>
            <div style="height: 180px; position: relative;">
                <?php if (empty($gastosPorCategoria)): ?>
                    <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: #8b95a5;">Sem dados</div>
                <?php else: ?>
                    <canvas id="pieChart"></canvas>
                <?php endif; ?>
            </div>
        </div>

        <!-- O Poder da Economia Mensal -->
        <div class="xr-card">
            <h3>Poder da Economia Mensal</h3>
            <div style="background: rgba(255,255,255,0.02); border-radius: 12px; padding: 14px; border: 1px solid rgba(255,255,255,0.05);">
                <label style="color: #8b95a5; font-size: 0.8rem; display: block; margin-bottom: 6px;">Se você poupar por mês:</label>
                <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 16px; border-bottom: 2px solid #0084ff; padding-bottom: 4px; width: fit-content;">
                    <span style="color: white; font-weight: bold; font-size: 0.9rem;">R$</span>
                    <input type="number" id="microValorMensal" value="450" min="10" max="10000" step="50" style="background: transparent; border: none; color: white; font-size: 1.1rem; font-weight: bold; width: 70px; outline: none;" onchange="atualizarMicroPoupancaMensal(this.value)">
                </div>
                
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="color: #8b95a5; font-size: 0.75rem;">6 meses</span>
                        <strong style="color: white; font-size: 0.95rem;" id="micro6meses">R$ 2.700,00</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 6px; border-top: 1px solid rgba(255,255,255,0.05);">
                        <span style="color: #8b95a5; font-size: 0.75rem;">1 ano</span>
                        <strong style="color: #06d6a0; font-size: 1.05rem;" id="micro1anoMensal">R$ 5.400,00</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 6px; border-top: 1px solid rgba(255,255,255,0.05);">
                        <span style="color: #8b95a5; font-size: 0.75rem;">5 anos</span>
                        <strong style="color: white; font-size: 0.95rem;" id="micro5anos">R$ 27.000,00</strong>
                    </div>
                </div>
            </div>
            
            <button style="width: 100%; margin-top: 12px; background: #0084ff; color: white; border: none; border-radius: 8px; padding: 10px; font-weight: bold; font-size: 0.85rem; cursor: pointer; transition: 0.2s;" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'" onclick="window.location='metas.php'">
                Iniciar Plano
            </button>
        </div>

    </div>
</div>

<script>
function atualizarMicroPoupancaMensal(valor) {
    const v = parseFloat(valor);
    if(isNaN(v) || v <= 0) return;
    
    const m6 = v * 6;
    const m12 = v * 12;
    const m60 = v * 60;
    
    const el6 = document.getElementById('micro6meses');
    const el12 = document.getElementById('micro1anoMensal');
    const el60 = document.getElementById('micro5anos');
    
    if(el6) el6.innerText = 'R$ ' + m6.toLocaleString('pt-BR', {minimumFractionDigits: 2});
    if(el12) el12.innerText = 'R$ ' + m12.toLocaleString('pt-BR', {minimumFractionDigits: 2});
    if(el60) el60.innerText = 'R$ ' + m60.toLocaleString('pt-BR', {minimumFractionDigits: 2});
}
document.addEventListener('DOMContentLoaded', function() {
    const el = document.getElementById('microValorMensal');
    if (el) atualizarMicroPoupancaMensal(el.value);
});
</script>

<!-- Insights do Assistente IA -->
<?php if ($analiseIA): ?>
<div class="form-card" style="margin-bottom: 24px; background: linear-gradient(135deg, rgba(99,102,241,0.08) 0%, rgba(139,92,246,0.05) 100%); border: 1px solid rgba(139,92,246,0.15);">
    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 16px;">
        <div style="width: 40px; height: 40px; border-radius: 12px; background: linear-gradient(135deg, #8b5cf6, #6366f1); display: flex; align-items: center; justify-content: center;">
            <i class="fas fa-robot" style="color: white; font-size: 1rem;"></i>
        </div>
        <div>
            <h3 style="margin: 0; font-size: 1.05rem; font-weight: 600; color: white;">Assistente Financeiro</h3>
            <p style="margin: 0; font-size: 0.75rem; color: #8b95a5;">Análise proativa do seu mês</p>
        </div>
    </div>

    <?php if (!empty($mensagemProativaIA)): ?>
    <p style="margin: 0 0 16px; font-size: 0.92rem; color: #c8d0da; line-height: 1.6;">
        <?= nl2br(htmlspecialchars($mensagemProativaIA)) ?>
    </p>
    <?php endif; ?>

    <?php if (!empty($analiseIA['top_gastos_redutiveis'])): ?>
    <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 14px;">
        <?php foreach (array_slice($analiseIA['top_gastos_redutiveis'], 0, 3) as $gasto): ?>
        <div style="flex: 1; min-width: 120px; padding: 10px 14px; background: rgba(0,0,0,0.25); border-radius: 10px; text-align: center;">
            <span style="display: block; font-size: 0.75rem; color: #8b95a5; margin-bottom: 4px;"><?= htmlspecialchars($gasto['categoria']) ?></span>
            <strong style="color: #ef4444; font-size: 1rem;">R$ <?= number_format($gasto['valor_atual'], 2, ',', '.') ?></strong>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (($analiseIA['economia_potencial'] ?? 0) > 0): ?>
    <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; background: rgba(139,92,246,0.12); border-radius: 10px; margin-bottom: 14px;">
        <div>
            <span style="font-size: 0.8rem; color: #8b95a5;">Economia potencial estimada</span>
            <strong style="display: block; color: #8b5cf6; font-size: 1.2rem;">R$ <?= number_format($analiseIA['economia_potencial'], 2, ',', '.') ?><span style="font-size: 0.75rem; color: #8b95a5;">/mês</span></strong>
        </div>
        <a href="assistente.php" class="btn btn-primary btn-sm" style="background: #8b5cf6; border-color: #8b5cf6; white-space: nowrap;">
            <i class="fas fa-robot"></i> Monte um plano
        </a>
    </div>
    <?php else: ?>
    <div style="text-align: right; margin-bottom: 4px;">
        <a href="assistente.php" class="btn btn-ghost btn-sm" style="font-size: 0.8rem; color: #8b5cf6;">
            Conversar com o Assistente <i class="fas fa-arrow-right" style="margin-left: 4px;"></i>
        </a>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if (!empty($gastosPorCategoria) || !empty($entradasVsSaidas)): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Configuração global do Chart.js
            Chart.defaults.color = '#94a3b8';
            Chart.defaults.font.family = 'Inter, sans-serif';

            <?php if (!empty($gastosPorCategoria)): ?>
                // Gráfico de Pizza - Gastos por Categoria
                const pieCtx = document.getElementById('pieChart');
                if (pieCtx) {
                    new Chart(pieCtx, {
                        type: 'doughnut',
                        data: {
                            labels: <?= json_encode(array_column($gastosPorCategoria, 'nome')) ?>,
                            datasets: [{
                                data: <?= json_encode(array_map('floatval', array_column($gastosPorCategoria, 'total'))) ?>,
                                backgroundColor: <?= json_encode(array_column($gastosPorCategoria, 'cor')) ?>,
                                borderWidth: 0,
                                hoverOffset: 15
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '65%',
                            plugins: {
                                legend: {
                                    position: 'right',
                                    labels: {
                                        padding: 15,
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
            <?php endif; ?>

            // Gráfico de Barras - Entradas vs Saídas
            const barCtx = document.getElementById('barChart');
            if (barCtx) {
                const meses = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
                const dadosAno = <?= json_encode($entradasVsSaidas) ?>;

                const entradas = new Array(12).fill(0);
                const saidas = new Array(12).fill(0);

                dadosAno.forEach(d => {
                    entradas[d.mes - 1] = parseFloat(d.entradas);
                    saidas[d.mes - 1] = parseFloat(d.saidas);
                });

                new Chart(barCtx, {
                    type: 'bar',
                    data: {
                        labels: meses,
                        datasets: [
                            {
                                label: 'Entradas',
                                data: entradas,
                                backgroundColor: '#06d6a0cc',
                                borderColor: '#06d6a0',
                                borderWidth: 0,
                                borderRadius: 6,
                                borderSkipped: false
                            },
                            {
                                label: 'Saídas',
                                data: saidas,
                                backgroundColor: '#8b5cf6cc',
                                borderColor: '#8b5cf6',
                                borderWidth: 0,
                                borderRadius: 6,
                                borderSkipped: false
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            intersect: false,
                            mode: 'index'
                        },
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    usePointStyle: true,
                                    pointStyle: 'circle'
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function (context) {
                                        return context.dataset.label + ': R$ ' + context.raw.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: {
                                    display: false
                                }
                            },
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(148, 163, 184, 0.1)'
                                },
                                ticks: {
                                    callback: function (value) {
                                        return 'R$ ' + value.toLocaleString('pt-BR');
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // Gráfico de Linha - Gastos Diários
            const lineCtx = document.getElementById('lineChart');
            if (lineCtx) {
                const gastosDiarios = <?= json_encode(array_values($gastosDiarios)) ?>;
                const diasDoMes = <?= json_encode(array_keys($gastosDiarios)) ?>;

                new Chart(lineCtx, {
                    type: 'line',
                    data: {
                        labels: diasDoMes,
                        datasets: [{
                            label: 'Gastos do Dia',
                            data: gastosDiarios,
                            borderColor: '#8b5cf6',
                            backgroundColor: 'rgba(139, 92, 246, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 5,
                            pointHoverRadius: 10,
                            pointBackgroundColor: '#8b5cf6',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointHoverBackgroundColor: '#fff',
                            pointHoverBorderColor: '#8b5cf6',
                            pointHoverBorderWidth: 3
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            intersect: false,
                            mode: 'index'
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: 'rgba(15, 23, 42, 0.95)',
                                titleColor: '#fff',
                                bodyColor: '#fff',
                                borderColor: '#ef4444',
                                borderWidth: 1,
                                cornerRadius: 8,
                                padding: 12,
                                displayColors: false,
                                callbacks: {
                                    title: function (context) {
                                        return 'Dia ' + context[0].label;
                                    },
                                    label: function (context) {
                                        return 'Gasto: R$ ' + context.raw.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                title: {
                                    display: true,
                                    text: 'Dia do Mês',
                                    color: '#94a3b8'
                                },
                                grid: {
                                    display: false
                                }
                            },
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Valor (R$)',
                                    color: '#94a3b8'
                                },
                                grid: {
                                    color: 'rgba(148, 163, 184, 0.1)'
                                },
                                ticks: {
                                    callback: function (value) {
                                        return 'R$ ' + value.toLocaleString('pt-BR');
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