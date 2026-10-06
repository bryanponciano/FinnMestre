<?php
/**
 * =====================================================
 * FinnMestre - Gestão de Contas
 * =====================================================
 */
require_once 'includes/header.php';

$mensagem = '';
$tipoMensagem = '';

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['conta_nome'])) {
    $dados = [
        'id' => !empty($_POST['conta_id']) ? intval($_POST['conta_id']) : null,
        'nome' => trim($_POST['conta_nome']),
        'tipo' => $_POST['conta_tipo'],
        'instituicao' => trim($_POST['conta_instituicao'] ?? ''),
        'saldo_inicial' => floatval(str_replace(',', '.', str_replace('.', '', $_POST['conta_saldo_inicial'] ?? '0'))),
        'limite_credito' => !empty($_POST['conta_limite']) ? floatval(str_replace(',', '.', str_replace('.', '', $_POST['conta_limite']))) : null,
        'dia_fechamento' => !empty($_POST['conta_dia_fechamento']) ? intval($_POST['conta_dia_fechamento']) : null,
        'dia_vencimento' => !empty($_POST['conta_dia_vencimento']) ? intval($_POST['conta_dia_vencimento']) : null,
        'cor' => $_POST['conta_cor'] ?? '#6366f1',
        'icone' => $_POST['conta_icone'] ?? 'fa-wallet'
    ];

    if (salvarConta($dados)) {
        $mensagem = $dados['id'] ? 'Conta atualizada com sucesso!' : 'Conta criada com sucesso!';
        $tipoMensagem = 'success';
    } else {
        $mensagem = 'Erro ao salvar conta.';
        $tipoMensagem = 'danger';
    }
}

// Excluir conta
if (isset($_GET['excluir'])) {
    if (excluirConta(intval($_GET['excluir']))) {
        $mensagem = 'Conta desativada com sucesso!';
        $tipoMensagem = 'success';
    } else {
        $mensagem = 'Erro ao desativar conta.';
        $tipoMensagem = 'danger';
    }
}

$contas = listarContas(true);
$resumoContas = obterResumoContas();

// Ícones disponíveis
$iconesDisponiveis = [
    // Tipos Gerais
    'fa-wallet' => 'Carteira',
    'fa-university' => 'Banco Físico',
    'fa-building' => 'Banco Digital',
    'fa-piggy-bank' => 'Poupança/Cofre',
    'fa-money-bill-wave' => 'Dinheiro',
    'fa-coins' => 'Moedas',
    'fa-landmark' => 'Instituição Financeira',
    
    // Bandeiras de Cartões (FontAwesome Brands)
    'fab fa-cc-visa' => 'Visa',
    'fab fa-cc-mastercard' => 'Mastercard',
    'fab fa-cc-amex' => 'American Express',
    'fab fa-cc-diners-club' => 'Diners Club',
    'fab fa-cc-discover' => 'Discover',
    'fab fa-cc-jcb' => 'JCB',
    
    // Pagamentos / Outros
    'fab fa-pix' => 'Pix',
    'fab fa-paypal' => 'PayPal',
    'fab fa-bitcoin' => 'Criptomoedas',
    'fas fa-credit-card' => 'Cartão Genérico'
];
?>

<div class="page-header">
    <h1><i class="fas fa-wallet" style="color: var(--fm-primary);"></i> Minhas Contas</h1>
    <p>Gerencie suas contas bancárias, cartões e carteiras</p>
</div>

<?php if ($mensagem): ?>
    <div class="alert alert-<?= $tipoMensagem ?>">
        <i class="fas fa-<?= $tipoMensagem === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
        <?= $mensagem ?>
    </div>
<?php endif; ?>

<!-- Resumo das Contas -->
<div class="summary-cards">
    <!-- Patrimônio Total -->
    <div class="card animate-in">
        <div class="card-header">
            <span class="card-title">Patrimônio Líquido</span>
            <div class="card-icon primary">
                <i class="fas fa-coins"></i>
            </div>
        </div>
        <div class="card-value <?= $resumoContas['patrimonio_total'] >= 0 ? 'primary' : 'danger' ?>">
            <?= formatarMoeda($resumoContas['patrimonio_total']) ?>
        </div>
        <div class="card-subtitle">Soma de todas as contas</div>
    </div>

    <!-- Disponível em Contas -->
    <div class="card account-card corrente animate-in" style="animation-delay: 0.1s">
        <div class="card-header">
            <span class="card-title">Contas Correntes</span>
            <div class="card-icon secondary">
                <i class="fas fa-university"></i>
            </div>
        </div>
        <div class="card-value" style="color: var(--fm-secondary);">
            <?= formatarMoeda($resumoContas['corrente']['total']) ?>
        </div>
        <div class="card-subtitle">
            <?= count($resumoContas['corrente']['contas']) ?> conta(s) ativa(s)
        </div>
    </div>

    <!-- Limite Usado -->
    <div class="card account-card credito animate-in" style="animation-delay: 0.2s">
        <div class="card-header">
            <span class="card-title">Fatura Cartões</span>
            <div class="card-icon warning">
                <i class="fas fa-credit-card"></i>
            </div>
        </div>
        <div class="card-value" style="color: var(--warning);">
            <?= formatarMoeda($resumoContas['credito']['usado']) ?>
        </div>
        <div class="card-subtitle">
            <?php if ($resumoContas['credito']['limite_total'] > 0): ?>
                <?= number_format(($resumoContas['credito']['usado'] / $resumoContas['credito']['limite_total']) * 100, 1) ?>%
                do limite
            <?php else: ?>
                em uso
            <?php endif; ?>
        </div>
    </div>

    <!-- Reservas -->
    <div class="card account-card poupanca animate-in" style="animation-delay: 0.3s">
        <div class="card-header">
            <span class="card-title">Reservas / Poupança</span>
            <div class="card-icon accent">
                <i class="fas fa-piggy-bank"></i>
            </div>
        </div>
        <div class="card-value" style="color: var(--fm-accent);">
            <?= formatarMoeda($resumoContas['poupanca']['total']) ?>
        </div>
        <div class="card-subtitle">Foco no seu futuro</div>
    </div>
</div>

<!-- Botão Nova Conta -->
<div style="margin-bottom: 24px;">
    <button onclick="novaConta()" class="btn btn-primary">
        <i class="fas fa-plus"></i> Nova Conta
    </button>
</div>

<!-- Grid de Contas por Tipo -->
<div class="charts-grid" style="grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));">

    <?php
    $tiposConta = [
        'corrente' => ['titulo' => 'Contas Correntes', 'icone' => 'fa-university', 'cor' => 'var(--fm-secondary)'],
        'credito' => ['titulo' => 'Cartões de Crédito', 'icone' => 'fa-credit-card', 'cor' => 'var(--warning)'],
        'poupanca' => ['titulo' => 'Poupança / Reservas', 'icone' => 'fa-piggy-bank', 'cor' => 'var(--fm-accent)']
    ];

    foreach ($tiposConta as $tipo => $config):
        $contasTipo = $resumoContas[$tipo]['contas'] ?? [];
        ?>
        <div class="table-card animate-in">
            <div class="table-header"
                style="border-left: 4px solid <?= $config['cor'] ?>; margin-left: -1px; padding-left: 20px;">
                <h3 style="color: <?= $config['cor'] ?>;">
                    <i class="fas <?= $config['icone'] ?>"></i>
                    <?= $config['titulo'] ?>
                </h3>
                <span class="badge" style="background: <?= $config['cor'] ?>20; color: <?= $config['cor'] ?>;">
                    <?= count($contasTipo) ?>
                </span>
            </div>
            <div class="table-wrapper">
                <?php if (empty($contasTipo)): ?>
                    <div class="empty-state" style="padding: 40px;">
                        <i class="fas <?= $config['icone'] ?>" style="color: <?= $config['cor'] ?>; opacity: 0.3;"></i>
                        <p style="margin-top: 10px;">Nenhuma conta deste tipo</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($contasTipo as $conta): ?>
                        <div
                            style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                            <div style="display: flex; align-items: center; gap: 14px;">
                                <div
                                    style="width: 45px; height: 45px; border-radius: var(--radius-md); background: <?= $conta['cor'] ?>20; color: <?= $conta['cor'] ?>; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                                    <i class="<?= getIconeBanco($conta['nome'], $conta['tipo']) ?>"></i>
                                </div>
                                <div>
                                    <div style="font-weight: 600;">
                                        <?= htmlspecialchars($conta['nome']) ?>
                                    </div>
                                    <?php if ($conta['instituicao']): ?>
                                        <div style="font-size: 0.85rem; color: var(--text-muted);">
                                            <?= htmlspecialchars($conta['instituicao']) ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($tipo === 'credito' && $conta['limite_credito']): ?>
                                        <?php $percentUsado = abs($conta['saldo_atual']) / $conta['limite_credito'] * 100; ?>
                                        <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px;">
                                            Limite:
                                            <?= formatarMoeda($conta['limite_credito']) ?>
                                            <span style="color: <?= $percentUsado > 80 ? 'var(--danger)' : 'var(--success)' ?>;">
                                                (
                                                <?= number_format($percentUsado, 1) ?>% usado)
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 16px;">
                                <div style="text-align: right;">
                                    <div
                                        style="font-size: 1.2rem; font-weight: 700; font-family: 'JetBrains Mono', monospace; color: <?= $tipo === 'credito' ? 'var(--danger)' : ($conta['saldo_atual'] >= 0 ? 'var(--success)' : 'var(--danger)') ?>;">
                                        <?= formatarMoeda(abs($conta['saldo_atual'])) ?>
                                    </div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">
                                        <?= $tipo === 'credito' ? 'fatura atual' : 'saldo atual' ?>
                                    </div>
                                </div>
                                <div class="btn-group">
                                    <button onclick='editarConta(<?= json_encode($conta) ?>)' class="btn btn-icon btn-ghost"
                                        title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="?excluir=<?= $conta['id'] ?>" onclick="return confirm('Desativar esta conta?')"
                                        class="btn btn-icon btn-ghost" title="Desativar" style="color: var(--danger);">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Modal Nova/Editar Conta -->
<div id="modalConta" class="modal-overlay">
    <div class="modal" style="max-width: 550px;">
        <div class="modal-header">
            <h3><i class="fas fa-wallet"></i> <span id="modalContaTitulo">Nova Conta</span></h3>
            <button class="modal-close" onclick="closeModal('modalConta')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" id="conta_id" name="conta_id">

                <div class="form-group">
                    <label class="required">Nome da Conta</label>
                    <input type="text" id="conta_nome" name="conta_nome" class="form-control" required
                        placeholder="Ex: Nubank, Itaú, Carteira...">
                </div>

                <div class="form-grid" style="grid-template-columns: 1fr 1fr;">
                    <div class="form-group">
                        <label class="required">Tipo</label>
                        <select id="conta_tipo" name="conta_tipo" class="form-control" required
                            onchange="toggleCamposCredito()">
                            <option value="corrente">Conta Corrente</option>
                            <option value="credito">Cartão de Crédito</option>
                            <option value="poupanca">Poupança / Reserva</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Instituição</label>
                        <input type="text" id="conta_instituicao" name="conta_instituicao" class="form-control"
                            placeholder="Ex: Banco do Brasil">
                    </div>
                </div>

                <div class="form-group">
                    <label>Saldo Inicial</label>
                    <input type="text" id="conta_saldo_inicial" name="conta_saldo_inicial"
                        class="form-control input-moeda" placeholder="0,00">
                    <span class="form-hint">Saldo atual da conta (para cartão de crédito, deixe 0)</span>
                </div>

                <!-- Campos específicos para Cartão de Crédito -->
                <div id="camposCredito" style="display: none;">
                    <div class="form-group">
                        <label>Limite do Cartão</label>
                        <input type="text" id="conta_limite" name="conta_limite" class="form-control input-moeda"
                            placeholder="0,00">
                    </div>
                    <div class="form-grid" style="grid-template-columns: 1fr 1fr;">
                        <div class="form-group">
                            <label>Dia do Fechamento</label>
                            <input type="number" id="conta_dia_fechamento" name="conta_dia_fechamento"
                                class="form-control" min="1" max="31" placeholder="Ex: 15">
                        </div>
                        <div class="form-group">
                            <label>Dia do Vencimento</label>
                            <input type="number" id="conta_dia_vencimento" name="conta_dia_vencimento"
                                class="form-control" min="1" max="31" placeholder="Ex: 22">
                        </div>
                    </div>
                </div>

                <div class="form-grid" style="grid-template-columns: 1fr 1fr;">
                    <div class="form-group">
                        <label>Cor</label>
                        <input type="color" id="conta_cor" name="conta_cor" class="form-control" value="#6366f1"
                            style="height: 50px; padding: 5px;">
                    </div>
                    <div class="form-group">
                        <label>Ícone</label>
                        <select id="conta_icone" name="conta_icone" class="form-control">
                            <?php foreach ($iconesDisponiveis as $classe => $nome): ?>
                                <option value="<?= $classe ?>">
                                    <?= $nome ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalConta')">Cancelar</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Salvar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function novaConta() {
        document.getElementById('modalContaTitulo').textContent = 'Nova Conta';
        document.getElementById('conta_id').value = '';
        document.getElementById('conta_nome').value = '';
        document.getElementById('conta_tipo').value = 'corrente';
        document.getElementById('conta_instituicao').value = '';
        document.getElementById('conta_saldo_inicial').value = '';
        document.getElementById('conta_limite').value = '';
        document.getElementById('conta_dia_fechamento').value = '';
        document.getElementById('conta_dia_vencimento').value = '';
        document.getElementById('conta_cor').value = '#6366f1';
        document.getElementById('conta_icone').value = 'fa-wallet';
        toggleCamposCredito();
        openModal('modalConta');
    }

    function editarConta(conta) {
        document.getElementById('modalContaTitulo').textContent = 'Editar Conta';
        document.getElementById('conta_id').value = conta.id;
        document.getElementById('conta_nome').value = conta.nome;
        document.getElementById('conta_tipo').value = conta.tipo;
        document.getElementById('conta_instituicao').value = conta.instituicao || '';

        // Formatar valores monetários no formato BR
        const inputSaldo = document.getElementById('conta_saldo_inicial');
        const inputLimite = document.getElementById('conta_limite');
        if (typeof definirValorMonetario === 'function') {
            definirValorMonetario(inputSaldo, parseFloat(conta.saldo_inicial) || 0);
            definirValorMonetario(inputLimite, parseFloat(conta.limite_credito) || 0);
        } else {
            inputSaldo.value = conta.saldo_inicial;
            inputLimite.value = conta.limite_credito || '';
        }

        document.getElementById('conta_dia_fechamento').value = conta.dia_fechamento || '';
        document.getElementById('conta_dia_vencimento').value = conta.dia_vencimento || '';
        document.getElementById('conta_cor').value = conta.cor || '#6366f1';
        document.getElementById('conta_icone').value = conta.icone || 'fa-wallet';
        toggleCamposCredito();
        openModal('modalConta');
    }

    function toggleCamposCredito() {
        const tipo = document.getElementById('conta_tipo').value;
        document.getElementById('camposCredito').style.display = tipo === 'credito' ? 'block' : 'none';
    }
</script>

</main>
<script src="assets/js/app.js"></script>
</body>

</html>