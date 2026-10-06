<?php
require_once 'includes/header.php';

$mes = isset($_GET['mes']) ? intval($_GET['mes']) : date('m');
$ano = isset($_GET['ano']) ? intval($_GET['ano']) : date('Y');
$meses = getMeses();
$contas = listarContas(true);
$contasNaoPoupanca = array_filter($contas, fn($c) => $c['tipo'] !== 'poupanca');

$resumoPoupanca = obterResumoPoupanca();
$historico = listarHistoricoPoupanca(null, $mes, $ano);
?>

<div class="page-header">
    <h1 class="page-title"><i class="fas fa-piggy-bank text-purple"></i> Poupança</h1>
    <div class="page-actions">
        <button class="btn btn-primary" onclick="openModal('modalDepositar')">
            <i class="fas fa-plus"></i> Depositar
        </button>
        <button class="btn btn-secondary" onclick="openModal('modalRetirar')">
            <i class="fas fa-minus"></i> Retirar
        </button>
    </div>
</div>

<div class="summary-cards">
    <div class="card bg-purple-gradient text-white" style="background: linear-gradient(135deg, #8b5cf6, #10b981);">
        <div class="card-header">
            <h3 class="card-title text-white">Saldo da Poupança</h3>
            <div class="card-icon"><i class="fas fa-piggy-bank"></i></div>
        </div>
        <div class="card-value"><?= formatarMoeda($resumoPoupanca['saldo_atual'] ?? 0) ?></div>
    </div>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Total Depositado</h3>
            <div class="card-icon text-success"><i class="fas fa-arrow-up"></i></div>
        </div>
        <div class="card-value text-success"><?= formatarMoeda($resumoPoupanca['total_depositado'] ?? 0) ?></div>
    </div>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Total Retirado</h3>
            <div class="card-icon text-danger"><i class="fas fa-arrow-down"></i></div>
        </div>
        <div class="card-value text-danger"><?= formatarMoeda($resumoPoupanca['total_retirado'] ?? 0) ?></div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h3 class="card-title">Evolução da Poupança</h3>
    </div>
    <div class="card-body">
        <canvas id="poupancaChart" height="250"></canvas>
    </div>
</div>

<div class="card table-card mb-4">
    <div class="table-header">
        <h3 class="card-title">Histórico de Transações</h3>
        <form method="GET" class="form-inline" style="display: flex; gap: 8px;">
            <select name="mes" class="form-control form-control-sm" onchange="this.form.submit()">
                <?php foreach ($meses as $num => $nome): ?>
                    <option value="<?= $num ?>" <?= $mes == $num ? 'selected' : '' ?>><?= $nome ?></option>
                <?php endforeach; ?>
            </select>
            <select name="ano" class="form-control form-control-sm" onchange="this.form.submit()">
                <?php for ($i = date('Y') - 5; $i <= date('Y') + 1; $i++): ?>
                    <option value="<?= $i ?>" <?= $ano == $i ? 'selected' : '' ?>><?= $i ?></option>
                <?php endfor; ?>
            </select>
        </form>
    </div>
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Tipo</th>
                    <th>Descrição</th>
                    <th>Conta</th>
                    <th style="text-align: right;">Valor</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($historico)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: #9ca3af;">Nenhum registro encontrado neste período.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($historico as $item): ?>
                        <tr>
                            <td><?= formatarData($item['data']) ?></td>
                            <td>
                                <?php if ($item['tipo'] === 'deposito'): ?>
                                    <span class="badge badge-success" style="background-color: rgba(16, 185, 129, 0.1); color: #10b981; padding: 4px 8px; border-radius: 4px;">Depósito</span>
                                <?php else: ?>
                                    <span class="badge badge-danger" style="background-color: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 4px 8px; border-radius: 4px;">Retirada</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($item['descricao']) ?></td>
                            <td><?= htmlspecialchars($item['conta_nome'] ?? '--') ?></td>
                            <td style="text-align: right;" class="<?= $item['tipo'] === 'deposito' ? 'text-success' : 'text-danger' ?>">
                                <?= $item['tipo'] === 'deposito' ? '+' : '-' ?><?= formatarMoeda($item['valor']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Depositar -->
<div id="modalDepositar" class="modal-overlay">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Depositar na Poupança</h3>
            <button type="button" class="btn-close" onclick="closeModal('modalDepositar')">&times;</button>
        </div>
        <form id="formDepositar" onsubmit="depositarPoupanca(event)">
            <div class="modal-body">
                <div class="form-group mb-3">
                    <label>Conta de Origem</label>
                    <select name="conta_id" class="form-control" required>
                        <option value="">Selecione uma conta</option>
                        <?php foreach ($contasNaoPoupanca as $conta): ?>
                            <option value="<?= $conta['id'] ?>"><?= htmlspecialchars($conta['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group mb-3">
                    <label>Valor</label>
                    <input type="text" name="valor" class="form-control input-moeda" required>
                </div>
                <div class="form-group mb-3">
                    <label>Descrição</label>
                    <input type="text" name="descricao" class="form-control" value="Depósito na Poupança" required>
                </div>
                <div class="form-group mb-3">
                    <label>Data</label>
                    <input type="date" name="data" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('modalDepositar')">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="btnSalvarDeposito">Confirmar Depósito</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Retirar -->
<div id="modalRetirar" class="modal-overlay">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Retirar da Poupança</h3>
            <button type="button" class="btn-close" onclick="closeModal('modalRetirar')">&times;</button>
        </div>
        <form id="formRetirar" onsubmit="retirarPoupanca(event)">
            <div class="modal-body">
                <div class="form-group mb-3">
                    <label>Conta de Destino</label>
                    <select name="conta_id" class="form-control" required>
                        <option value="">Selecione uma conta</option>
                        <?php foreach ($contasNaoPoupanca as $conta): ?>
                            <option value="<?= $conta['id'] ?>"><?= htmlspecialchars($conta['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group mb-3">
                    <label>Valor</label>
                    <input type="text" name="valor" class="form-control input-moeda" required>
                </div>
                <div class="form-group mb-3">
                    <label>Descrição</label>
                    <input type="text" name="descricao" class="form-control" value="Retirada da Poupança" required>
                </div>
                <div class="form-group mb-3">
                    <label>Data</label>
                    <input type="date" name="data" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('modalRetirar')">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="btnSalvarRetirada">Confirmar Retirada</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar inputs monetários
    document.querySelectorAll('.input-moeda').forEach(el => {
        if (typeof inicializarInputMonetario === 'function') {
            inicializarInputMonetario(el);
        }
    });

    const historico = <?= json_encode(array_reverse($historico)) ?>;
    let saldoAtual = <?= isset($resumoPoupanca['saldo_atual']) ? floatval($resumoPoupanca['saldo_atual']) : 0 ?>;
    
    let saldosAcumulados = [];
    let labels = [];
    
    if (historico.length === 0) {
        saldosAcumulados.push(saldoAtual);
        labels.push('Hoje');
    } else {
        let variacaoNoPeriodo = historico.reduce((acc, curr) => {
            return acc + (curr.tipo === 'deposito' ? parseFloat(curr.valor) : -parseFloat(curr.valor));
        }, 0);
        
        let saldoAcumulado = saldoAtual - variacaoNoPeriodo;
        
        labels.push('Início');
        saldosAcumulados.push(saldoAcumulado);
        
        historico.forEach(item => {
            let val = parseFloat(item.valor);
            if (item.tipo === 'deposito') {
                saldoAcumulado += val;
            } else {
                saldoAcumulado -= val;
            }
            labels.push(item.data.split('-').reverse().join('/'));
            saldosAcumulados.push(saldoAcumulado);
        });
    }

    const ctx = document.getElementById('poupancaChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Saldo da Poupança',
                    data: saldosAcumulados,
                    borderColor: '#8b5cf6',
                    backgroundColor: 'rgba(139, 92, 246, 0.15)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#8b5cf6',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: '#8b5cf6'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        grid: { color: 'rgba(255,255,255,0.05)' },
                        ticks: { color: 'rgba(255,255,255,0.5)' }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: 'rgba(255,255,255,0.5)' }
                    }
                }
            }
        });
    }
});

async function depositarPoupanca(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSalvarDeposito');
    const txtOriginal = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
    btn.disabled = true;

    try {
        const form = e.target;
        const inputValor = form.querySelector('input[name="valor"]');
        const valorReal = typeof obterValorMonetario === 'function' ? obterValorMonetario(inputValor) : parseFloat(inputValor.value.replace(/[^0-9,-]+/g,"").replace(",", "."));

        const formData = new FormData(form);
        formData.set('valor', valorReal);

        const response = await fetch('api/poupanca.php?acao=depositar', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.sucesso) {
            closeModal('modalDepositar');
            window.location.reload();
        } else {
            alert(data.mensagem || 'Erro ao realizar depósito.');
            btn.innerHTML = txtOriginal;
            btn.disabled = false;
        }
    } catch (error) {
        console.error(error);
        alert('Erro de conexão ao salvar.');
        btn.innerHTML = txtOriginal;
        btn.disabled = false;
    }
}

async function retirarPoupanca(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSalvarRetirada');
    const txtOriginal = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
    btn.disabled = true;

    try {
        const form = e.target;
        const inputValor = form.querySelector('input[name="valor"]');
        const valorReal = typeof obterValorMonetario === 'function' ? obterValorMonetario(inputValor) : parseFloat(inputValor.value.replace(/[^0-9,-]+/g,"").replace(",", "."));

        const formData = new FormData(form);
        formData.set('valor', valorReal);

        const response = await fetch('api/poupanca.php?acao=retirar', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.sucesso) {
            closeModal('modalRetirar');
            window.location.reload();
        } else {
            alert(data.mensagem || 'Erro ao realizar retirada.');
            btn.innerHTML = txtOriginal;
            btn.disabled = false;
        }
    } catch (error) {
        console.error(error);
        alert('Erro de conexão ao salvar.');
        btn.innerHTML = txtOriginal;
        btn.disabled = false;
    }
}
</script>

</main>
<script src="assets/js/app.js"></script>
</body>
</html>
