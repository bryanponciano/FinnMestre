<?php
/**
 * =====================================================
 * FinnMestre - Contas Programadas
 * Gerenciamento de contas com vencimento
 * =====================================================
 */
require_once 'includes/header.php';
require_once 'includes/feature_gate.php';
require_once 'includes/contas-programadas.php';

// Verificar acesso à feature
if (exibirFeatureGate('contas_programadas', 'Contas Programadas', 'Gerencie suas contas fixas e recorrentes com vencimento automático e alertas.')): ?>
    </main>
    <script src="assets/js/app.js"></script>
    </body>

    </html>
    <?php exit; endif; ?>

<?php
$contas = listarContasProgramadas();
$contasProximas = obterContasProximasVencimento(7);
$totalMes = calcularTotalContasMes();
$categorias = listarCategorias('saida');
$contasBancarias = listarContas(true);
?>

<div class="page-header">
    <h1><i class="fas fa-calendar-alt" style="color: var(--fm-primary);"></i> Contas Programadas</h1>
    <p>Gerencie suas contas fixas e recorrentes</p>
</div>

<!-- Botão Nova Conta -->
<div style="margin-bottom: 20px; display: flex; gap: 12px; align-items: center;">
    <button onclick="novaContaProgramada()" class="btn btn-primary">
        <i class="fas fa-plus"></i> Nova Conta Programada
    </button>
    <div
        style="margin-left: auto; background: var(--bg-glass-light); padding: 12px 20px; border-radius: var(--radius-md);">
        <span style="color: var(--text-muted);">Total do mês:</span>
        <strong class="text-danger" style="font-size: 1.2rem; margin-left: 8px;">
            <?= formatarMoeda($totalMes) ?>
        </strong>
    </div>
</div>

<?php if (!empty($contasProximas)): ?>
    <!-- Alertas de Vencimento -->
    <div class="behavioral-card warning" style="margin-bottom: 24px;">
        <div class="behavioral-header">
            <div class="behavioral-icon warning">
                <i class="fas fa-bell"></i>
            </div>
            <div class="behavioral-title">
                <h3>Contas Próximas do Vencimento</h3>
                <span class="behavioral-subtitle">
                    <?= count($contasProximas) ?> conta(s) vencem nos próximos 7 dias
                </span>
            </div>
        </div>
        <div class="behavioral-body">
            <div style="display: grid; gap: 12px;">
                <?php foreach ($contasProximas as $cp):
                    $status = getStatusVencimento($cp['proximo_vencimento']);
                    ?>
                    <div
                        style="display: flex; align-items: center; gap: 16px; padding: 12px; background: var(--bg-glass-light); border-radius: var(--radius-md); border-left: 4px solid var(--<?= $status['classe'] === 'danger' ? 'danger' : ($status['classe'] === 'warning' ? 'warning' : 'fm-primary') ?>);">
                        <div style="flex: 1;">
                            <strong>
                                <?= htmlspecialchars($cp['descricao']) ?>
                            </strong>
                            <div style="font-size: 0.85rem; color: var(--text-muted);">
                                <i class="fas <?= $status['icone'] ?>"></i>
                                <?= $status['texto'] ?>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <strong class="text-danger">
                                <?= formatarMoeda($cp['valor']) ?>
                            </strong>
                        </div>
                        <button onclick="pagarConta(<?= $cp['id'] ?>, <?= $cp['valor'] ?>)" class="btn btn-success btn-sm">
                            <i class="fas fa-check"></i> Pagar
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Lista de Contas Programadas -->
<div class="table-card">
    <div class="table-header">
        <h3><i class="fas fa-list"></i> Todas as Contas Programadas (
            <?= count($contas) ?>)
        </h3>
    </div>
    <div class="table-wrapper">
        <?php if (empty($contas)): ?>
            <div class="empty-state">
                <i class="fas fa-calendar-plus"></i>
                <h4>Nenhuma conta programada</h4>
                <p>Adicione suas contas fixas como aluguel, internet, luz, etc.</p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Descrição</th>
                        <th>Categoria</th>
                        <th style="text-align: center;">Vencimento</th>
                        <th style="text-align: center;">Recorrência</th>
                        <th style="text-align: right;">Valor</th>
                        <th style="width: 150px;">Ações</th>
                        <th style="text-align: center;">Recorrência</th>
                        <th style="text-align: right;">Valor</th>
                        <th style="width: 150px;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($contas as $cp):
                        $status = getStatusVencimento($cp['proximo_vencimento']);
                        ?>
                        <tr>
                            <td>
                                <strong>
                                    <?= htmlspecialchars($cp['descricao']) ?>
                                </strong>
                                <?php if ($cp['conta_nome']): ?>
                                    <div style="font-size: 0.8rem; color: var(--text-muted);">
                                        <i class="fas fa-wallet"></i>
                                        <?= htmlspecialchars($cp['conta_nome']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($cp['categoria_nome']): ?>
                                    <span class="categoria-badge">
                                        <span class="categoria-dot" style="background: <?= $cp['categoria_cor'] ?>"></span>
                                        <?= htmlspecialchars($cp['categoria_nome']) ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: var(--text-muted);">-</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <span class="badge <?= $status['classe'] ?>">
                                    <i class="fas <?= $status['icone'] ?>"></i>
                                    <?= formatarData($cp['proximo_vencimento']) ?>
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <?php if ($cp['recorrente']): ?>
                                    <span class="badge" style="background: var(--success-light); color: var(--success);">
                                        <i class="fas fa-sync-alt"></i>
                                        <?= ucfirst($cp['tipo_recorrencia']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge" style="background: var(--bg-glass-light);">
                                        Única
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;">
                                <strong class="text-danger">
                                    <?= formatarMoeda($cp['valor']) ?>
                                </strong>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <button onclick="pagarConta(<?= $cp['id'] ?>, <?= $cp['valor'] ?>)"
                                        class="btn btn-icon btn-success btn-sm" title="Marcar como Paga">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button onclick="editarContaProgramada(<?= htmlspecialchars(json_encode($cp)) ?>)"
                                        class="btn btn-icon btn-ghost btn-sm" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="excluirContaProgramada(<?= $cp['id'] ?>)"
                                        class="btn btn-icon btn-ghost btn-sm" style="color: var(--danger);" title="Excluir">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Nova/Editar Conta Programada -->
<div id="modalContaProgramada" class="modal-overlay">
    <div class="modal" style="max-width: 550px;">
        <div class="modal-header">
            <h3><i class="fas fa-calendar-alt"></i> <span id="modalTitulo">Nova Conta Programada</span></h3>
            <button class="modal-close" onclick="closeModal('modalContaProgramada')">&times;</button>
        </div>
        <form id="formContaProgramada" onsubmit="salvarContaProgramadaForm(event)">
            <div class="modal-body">
                <input type="hidden" id="conta_id">

                <div class="form-group">
                    <label class="required">Descrição</label>
                    <input type="text" id="conta_descricao" class="form-control" required
                        placeholder="Ex: Aluguel, Internet, Luz...">
                </div>

                <div class="form-grid" style="grid-template-columns: 1fr 1fr;">
                    <div class="form-group">
                        <label class="required">Valor</label>
                        <input type="text" id="conta_valor" class="form-control input-moeda" required
                            placeholder="0,00">
                    </div>
                    <div class="form-group">
                        <label class="required">Data do Vencimento</label>
                        <input type="date" id="conta_data_vencimento" class="form-control" required
                            value="<?= date('Y-m-d') ?>">
                        <small style="color: var(--text-muted);">A data do primeiro (ou próximo) pagamento</small>
                    </div>
                </div>

                <div class="form-grid" style="grid-template-columns: 1fr 1fr;">
                    <div class="form-group">
                        <label>Categoria</label>
                        <select id="conta_categoria_id" class="form-control">
                            <option value="">Selecione...</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= $cat['id'] ?>">
                                    <?= htmlspecialchars($cat['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Conta de Pagamento</label>
                        <select id="conta_conta_id" class="form-control">
                            <option value="">Selecione...</option>
                            <?php foreach ($contasBancarias as $c): ?>
                                <option value="<?= $c['id'] ?>">
                                    <?= htmlspecialchars($c['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-grid" style="grid-template-columns: 1fr 1fr;">
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                            <input type="checkbox" id="conta_recorrente" checked
                                style="width: 20px; height: 20px; accent-color: var(--fm-primary);">
                            <span>Conta Recorrente</span>
                        </label>
                    </div>
                    <div class="form-group" id="grupoRecorrencia">
                        <label>Tipo de Recorrência</label>
                        <select id="conta_tipo_recorrencia" class="form-control">
                            <option value="mensal">Mensal</option>
                            <option value="semanal">Semanal</option>
                            <option value="anual">Anual</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary"
                    onclick="closeModal('modalContaProgramada')">Cancelar</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Salvar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Confirmar Pagamento -->
<div id="modalPagamento" class="modal-overlay">
    <div class="modal" style="max-width: 400px;">
        <div class="modal-header">
            <h3><i class="fas fa-check-circle" style="color: var(--success);"></i> Confirmar Pagamento</h3>
            <button class="modal-close" onclick="closeModal('modalPagamento')">&times;</button>
        </div>
        <form id="formPagamento" onsubmit="confirmarPagamento(event)">
            <div class="modal-body">
                <input type="hidden" id="pagamento_conta_id">

                <div class="form-group">
                    <label>Valor Pago</label>
                    <input type="text" id="pagamento_valor" class="form-control input-moeda" required>
                </div>

                <div class="form-group">
                    <label>Data do Pagamento</label>
                    <input type="date" id="pagamento_data" class="form-control" value="<?= date('Y-m-d') ?>">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalPagamento')">Cancelar</button>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-check"></i> Confirmar Pagamento
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Inicializar inputs monetários
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.input-moeda').forEach(input => {
            if (typeof inicializarInputMonetario === 'function') {
                inicializarInputMonetario(input);
            }
        });

        // Toggle recorrência
        document.getElementById('conta_recorrente').addEventListener('change', function () {
            document.getElementById('grupoRecorrencia').style.display = this.checked ? 'block' : 'none';
        });
    });

    function novaContaProgramada() {
        document.getElementById('modalTitulo').textContent = 'Nova Conta Programada';
        document.getElementById('conta_id').value = '';
        document.getElementById('conta_descricao').value = '';
        document.getElementById('conta_valor').value = '';
        document.getElementById('conta_data_vencimento').value = new Date().toISOString().split('T')[0];
        document.getElementById('conta_categoria_id').value = '';
        document.getElementById('conta_conta_id').value = '';
        document.getElementById('conta_recorrente').checked = true;
        document.getElementById('conta_tipo_recorrencia').value = 'mensal';
        document.getElementById('grupoRecorrencia').style.display = 'block';
        openModal('modalContaProgramada');
    }

    function editarContaProgramada(cp) {
        document.getElementById('modalTitulo').textContent = 'Editar Conta Programada';
        document.getElementById('conta_id').value = cp.id;
        document.getElementById('conta_descricao').value = cp.descricao;
        definirValorMonetario(document.getElementById('conta_valor'), parseFloat(cp.valor));
        document.getElementById('conta_data_vencimento').value = cp.proximo_vencimento;
        document.getElementById('conta_categoria_id').value = cp.categoria_id || '';
        document.getElementById('conta_conta_id').value = cp.conta_id || '';
        document.getElementById('conta_recorrente').checked = cp.recorrente == 1;
        document.getElementById('conta_tipo_recorrencia').value = cp.tipo_recorrencia || 'mensal';
        document.getElementById('grupoRecorrencia').style.display = cp.recorrente == 1 ? 'block' : 'none';
        openModal('modalContaProgramada');
    }

    function salvarContaProgramadaForm(e) {
        e.preventDefault();

        const dados = {
            id: document.getElementById('conta_id').value,
            descricao: document.getElementById('conta_descricao').value,
            valor: obterValorNumerico(document.getElementById('conta_valor')),
            data_vencimento: document.getElementById('conta_data_vencimento').value,
            categoria_id: document.getElementById('conta_categoria_id').value,
            conta_id: document.getElementById('conta_conta_id').value,
            recorrente: document.getElementById('conta_recorrente').checked ? 1 : 0,
            tipo_recorrencia: document.getElementById('conta_tipo_recorrencia').value
        };

        fetch('api/contas-programadas.php?acao=salvar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(dados)
        })
            .then(r => r.json())
            .then(data => {
                if (data.sucesso) {
                    location.reload();
                } else {
                    alert('Erro: ' + (data.erro || 'Tente novamente'));
                }
            });
    }

    function excluirContaProgramada(id) {
        if (confirm('Excluir esta conta programada?')) {
            fetch('api/contas-programadas.php?acao=excluir&id=' + id)
                .then(r => r.json())
                .then(data => {
                    if (data.sucesso) {
                        location.reload();
                    } else {
                        alert('Erro ao excluir');
                    }
                });
        }
    }

    function pagarConta(id, valor) {
        document.getElementById('pagamento_conta_id').value = id;
        definirValorMonetario(document.getElementById('pagamento_valor'), valor);
        document.getElementById('pagamento_data').value = new Date().toISOString().split('T')[0];
        openModal('modalPagamento');
    }

    function confirmarPagamento(e) {
        e.preventDefault();

        const dados = {
            conta_id: document.getElementById('pagamento_conta_id').value,
            valor: obterValorNumerico(document.getElementById('pagamento_valor')),
            data: document.getElementById('pagamento_data').value
        };

        fetch('api/contas-programadas.php?acao=pagar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(dados)
        })
            .then(r => r.json())
            .then(data => {
                if (data.sucesso) {
                    location.reload();
                } else {
                    alert('Erro: ' + (data.erro || 'Tente novamente'));
                }
            });
    }
</script>

</main>
<script src="assets/js/app.js"></script>
</body>

</html>