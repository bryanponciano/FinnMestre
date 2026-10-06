<?php
/**
 * =====================================================
 * FinnMestre - Gestão de Transações
 * =====================================================
 */
require_once 'includes/header.php';

$mes = isset($_GET['mes']) ? intval($_GET['mes']) : date('m');
$ano = isset($_GET['ano']) ? intval($_GET['ano']) : date('Y');
$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
$categoria = isset($_GET['categoria']) ? intval($_GET['categoria']) : '';
$conta = isset($_GET['conta']) ? intval($_GET['conta']) : '';

$filtros = ['mes' => $mes, 'ano' => $ano];
if ($tipo)
    $filtros['tipo'] = $tipo;
if ($categoria)
    $filtros['categoria_id'] = $categoria;
if ($conta)
    $filtros['conta_id'] = $conta;

$transacoes = listarTransacoes($filtros);
$categorias = listarCategorias();
$contas = listarContas(true);
$meses = getMeses();
$mesInt = intval($mes);

// Calcular totais (transferências são neutras - não contam como entrada nem saída)
$totalEntradas = 0;
$totalSaidas = 0;
$totalTransferencias = 0;
foreach ($transacoes as $t) {
    if ($t['tipo'] === 'entrada')
        $totalEntradas += $t['valor'];
    elseif ($t['tipo'] === 'saida')
        $totalSaidas += $t['valor'];
    elseif ($t['tipo'] === 'transferencia')
        $totalTransferencias += $t['valor'];
}
?>

<div class="page-header">
    <h1><i class="fas fa-exchange-alt" style="color: var(--fm-primary);"></i> <?= __('transacoes') ?></h1>
    <p><?= __('transacoes') ?></p>
</div>

<!-- Botão Nova Transação -->
<div style="margin-bottom: 20px;">
    <button onclick="novaTransacao()" class="btn btn-primary">
        <i class="fas fa-plus"></i> <?= __('nova_transacao') ?>
    </button>
</div>

<!-- Filtros -->
<div class="form-card">
    <form method="GET" class="filters-bar">
        <div class="form-group">
            <label><?= __('mes') ?></label>
            <select name="mes" class="form-control">
                <?php foreach ($meses as $num => $nome): ?>
                    <option value="<?= $num ?>" <?= $num == $mes ? 'selected' : '' ?>><?= $nome ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label><?= __('ano') ?></label>
            <select name="ano" class="form-control">
                <?php for ($a = date('Y') + 1; $a >= date('Y') - 5; $a--): ?>
                    <option value="<?= $a ?>" <?= $a == $ano ? 'selected' : '' ?>><?= $a ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="form-group">
            <label><?= __('tipo') ?></label>
            <select name="tipo" class="form-control">
                <option value=""><?= __('todos') ?></option>
                <option value="entrada" <?= $tipo === 'entrada' ? 'selected' : '' ?>><?= __('entradas') ?></option>
                <option value="saida" <?= $tipo === 'saida' ? 'selected' : '' ?>><?= __('saidas') ?></option>
                <option value="transferencia" <?= $tipo === 'transferencia' ? 'selected' : '' ?>>
                    <?= __('transferencias') ?></option>
            </select>
        </div>
        <div class="form-group">
            <label><?= __('categoria') ?></label>
            <select name="categoria" class="form-control">
                <option value=""><?= __('todas') ?></option>
                <?php foreach ($categorias as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $categoria == $cat['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label><?= __('conta') ?></label>
            <select name="conta" class="form-control">
                <option value=""><?= __('todas') ?></option>
                <?php foreach ($contas as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $conta == $c['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <button type="submit" class="btn btn-secondary">
                <i class="fas fa-filter"></i> <?= __('filtrar') ?>
            </button>
        </div>
    </form>
</div>

<!-- Resumo dos filtros -->
<div class="summary-cards" style="margin-bottom: 24px;">
    <div class="card">
        <div class="card-header">
            <span class="card-title">Total Entradas</span>
            <div class="card-icon success">
                <i class="fas fa-arrow-up"></i>
            </div>
        </div>
        <div class="card-value success"><?= formatarMoeda($totalEntradas) ?></div>
    </div>
    <div class="card">
        <div class="card-header">
            <span class="card-title">Total Saídas</span>
            <div class="card-icon danger">
                <i class="fas fa-arrow-down"></i>
            </div>
        </div>
        <div class="card-value danger"><?= formatarMoeda($totalSaidas) ?></div>
    </div>
    <div class="card">
        <div class="card-header">
            <span class="card-title">Balanço</span>
            <div class="card-icon <?= ($totalEntradas - $totalSaidas) >= 0 ? 'success' : 'danger' ?>">
                <i class="fas fa-calculator"></i>
            </div>
        </div>
        <div class="card-value <?= ($totalEntradas - $totalSaidas) >= 0 ? 'success' : 'danger' ?>">
            <?= formatarMoeda($totalEntradas - $totalSaidas) ?>
        </div>
    </div>
</div>

<!-- Tabela de Transações -->
<div class="table-card">
    <div class="table-header">
        <h3><i class="fas fa-list"></i> Lista de Transações (<?= count($transacoes) ?>)</h3>
    </div>
    <div class="table-wrapper">
        <?php if (empty($transacoes)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h4>Nenhuma transação encontrada</h4>
                <p>Adicione uma nova transação ou ajuste os filtros</p>
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
                        <th style="width: 100px;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transacoes as $t): ?>
                        <tr>
                            <td><?= formatarData($t['data_transacao']) ?></td>
                            <td>
                                <strong><?= htmlspecialchars($t['descricao']) ?></strong>
                                <?php if ($t['total_parcelas'] > 1): ?>
                                    <span class="badge"
                                        style="background: var(--info-light); color: var(--fm-secondary); font-size: 0.7rem; padding: 3px 8px;">
                                        <?= $t['parcela_atual'] ?>/<?= $t['total_parcelas'] ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($t['observacao']): ?>
                                    <div style="font-size: 0.8rem; color: var(--text-muted);">
                                        <?= htmlspecialchars($t['observacao']) ?>
                                    </div>
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
                                    <?php if ($t['tipo'] === 'transferencia' && $t['conta_destino_nome']): ?>
                                        <i class="fas fa-arrow-right" style="margin: 0 5px; color: var(--text-muted);"></i>
                                        <span class="conta-badge"><?= htmlspecialchars($t['conta_destino_nome']) ?></span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color: var(--text-muted);">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?= $t['tipo'] ?>">
                                    <i
                                        class="fas fa-arrow-<?= $t['tipo'] === 'entrada' ? 'up' : ($t['tipo'] === 'transferencia' ? 'right' : 'down') ?>"></i>
                                    <?= ucfirst($t['tipo']) ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <strong
                                    class="<?= $t['tipo'] === 'entrada' ? 'text-success' : ($t['tipo'] === 'saida' ? 'text-danger' : 'text-primary') ?>">
                                    <?= $t['tipo'] === 'entrada' ? '+' : ($t['tipo'] === 'saida' ? '-' : '') ?>
                                    <?= formatarMoeda($t['valor']) ?>
                                </strong>
                                <?php if ($t['tipo'] === 'saida'):
                                    $horasCusto = converterValorEmHoras($t['valor']);
                                    if ($horasCusto['configurado'] && $horasCusto['horas_total'] >= 0.5): ?>
                                        <span class="hours-cost hours-cost-tooltip"
                                            data-tooltip="<?= $horasCusto['mensagem'] ?? 'Custo em tempo de trabalho' ?>">
                                            <i class="fas fa-clock"></i>
                                            <?= $horasCusto['texto_curto'] ?>
                                        </span>
                                    <?php endif; endif; ?>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <button onclick="editarTransacao(<?= $t['id'] ?>)" class="btn btn-icon btn-ghost btn-sm"
                                        title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="excluirTransacao(<?= $t['id'] ?>)" class="btn btn-icon btn-ghost btn-sm"
                                        style="color: var(--danger);" title="Excluir">
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

<!-- Modal Nova/Editar Transação -->
<div id="modalTransacao" class="modal-overlay">
    <div class="modal" style="max-width: 600px;">
        <div class="modal-header">
            <h3><i class="fas fa-exchange-alt"></i> <span id="modalTitulo">Nova Transação</span></h3>
            <button class="modal-close" onclick="closeModal('modalTransacao')">&times;</button>
        </div>
        <form id="formTransacao" onsubmit="salvarTransacao(event)">
            <div class="modal-body">
                <input type="hidden" id="transacao_id">

                <!-- Tipo de Transação -->
                <div class="form-group">
                    <label class="required">Tipo</label>
                    <div class="tipo-toggle">
                        <button type="button" class="tipo-btn entrada" onclick="setTipo('entrada')">
                            <i class="fas fa-arrow-up"></i> Entrada
                        </button>
                        <button type="button" class="tipo-btn saida active" onclick="setTipo('saida')">
                            <i class="fas fa-arrow-down"></i> Saída
                        </button>
                        <button type="button" class="tipo-btn transferencia" onclick="setTipo('transferencia')">
                            <i class="fas fa-exchange-alt"></i> Transferência
                        </button>
                    </div>
                    <input type="hidden" id="tipo" value="saida">
                </div>

                <div class="form-grid" style="grid-template-columns: 2fr 1fr;">
                    <div class="form-group">
                        <label class="required">Descrição</label>
                        <input type="text" id="descricao" class="form-control" required
                            placeholder="Ex: Supermercado, Salário...">
                    </div>
                    <div class="form-group">
                        <label class="required">Valor</label>
                        <input type="text" id="valor" class="form-control input-moeda" required placeholder="0,00">
                    </div>
                </div>

                <div class="form-grid" style="grid-template-columns: 1fr 1fr;">
                    <div class="form-group">
                        <label class="required">Data</label>
                        <input type="date" id="data_transacao" class="form-control" required
                            value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group" id="grupoParcelas">
                        <label>Parcelas</label>
                        <select id="total_parcelas" class="form-control">
                            <option value="1">À vista</option>
                            <?php for ($i = 2; $i <= 48; $i++): ?>
                                <option value="<?= $i ?>"><?= $i ?>x</option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>

                <div class="form-grid" style="grid-template-columns: 1fr 1fr;" id="grupoContas">
                    <div class="form-group">
                        <label>Conta</label>
                        <select id="conta_id" class="form-control">
                            <option value="">Selecione...</option>
                            
                            <?php foreach ($contas as $c): 
                                $tipoNome = getNomeTipoConta($c['tipo']);
                                $emoji = ($c['tipo'] === 'credito') ? '💳 ' : '🏦 ';
                            ?>
                                <option value="<?= $c['id'] ?>"><?= $emoji . $tipoNome ?></option>
                            <?php endforeach; ?>

                            <?php 
                            // Caso não existam contas de cartão, mostrar opção para criar ou aviso
                            $temCredito = false;
                            foreach($contas as $c) if($c['tipo'] === 'credito') $temCredito = true;
                            if (!$temCredito): ?>
                                <option value="" disabled>💳 Cartão de Crédito (Crie um em 'Contas')</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="form-group" id="grupoContaDestino" style="display: none;">
                        <label>Conta Destino</label>
                        <select id="conta_destino_id" class="form-control">
                            <option value="">Selecione...</option>
                            <?php foreach ($contas as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" id="grupoCategoria">
                        <label>Categoria</label>
                        <select id="categoria_id" class="form-control">
                            <option value="">Sem categoria</option>
                            <optgroup label="📈 Entradas" id="catEntradas">
                                <?php foreach ($categorias as $cat): ?>
                                    <?php if ($cat['tipo'] === 'entrada'): ?>
                                        <option value="<?= $cat['id'] ?>" data-tipo="entrada">
                                            <?= htmlspecialchars($cat['nome']) ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </optgroup>
                            <optgroup label="📉 Saídas" id="catSaidas">
                                <?php foreach ($categorias as $cat): ?>
                                    <?php if ($cat['tipo'] === 'saida'): ?>
                                        <option value="<?= $cat['id'] ?>" data-tipo="saida">
                                            <?= htmlspecialchars($cat['nome']) ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </optgroup>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Observação</label>
                    <textarea id="observacao" class="form-control" rows="2"
                        placeholder="Notas adicionais (opcional)"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalTransacao')">Cancelar</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Salvar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    let tipoAtual = 'saida';

    // Inicializar input monetário
    document.addEventListener('DOMContentLoaded', function () {
        const inputValor = document.getElementById('valor');
        if (inputValor && typeof inicializarInputMonetario === 'function') {
            inicializarInputMonetario(inputValor);
        }
    });

    function setTipo(tipo) {
        tipoAtual = tipo;
        document.getElementById('tipo').value = tipo;

        document.querySelectorAll('.tipo-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector('.tipo-btn.' + tipo).classList.add('active');

        // Mostrar/esconder campos relevantes
        const grupoContaDestino = document.getElementById('grupoContaDestino');
        const grupoCategoria = document.getElementById('grupoCategoria');
        const grupoParcelas = document.getElementById('grupoParcelas');

        if (tipo === 'transferencia') {
            grupoContaDestino.style.display = 'block';
            grupoCategoria.style.display = 'none';
            grupoParcelas.style.display = 'none';
        } else {
            grupoContaDestino.style.display = 'none';
            grupoCategoria.style.display = 'block';
            grupoParcelas.style.display = tipo === 'saida' ? 'block' : 'none';
        }

        // Filtrar categorias pelo tipo
        filtrarCategorias(tipo);
    }

    function filtrarCategorias(tipo) {
        const select = document.getElementById('categoria_id');
        const options = select.querySelectorAll('option[data-tipo]');

        options.forEach(opt => {
            const optTipo = opt.getAttribute('data-tipo');
            const optgroup = opt.parentElement;
            if (tipo === 'transferencia') {
                opt.style.display = 'none';
            } else if (optTipo === tipo) {
                opt.style.display = '';
            } else {
                opt.style.display = 'none';
            }
        });
    }

    function novaTransacao() {
        document.getElementById('modalTitulo').textContent = 'Nova Transação';
        document.getElementById('transacao_id').value = '';
        document.getElementById('descricao').value = '';
        const inputValor = document.getElementById('valor');
        if (typeof definirValorMonetario === 'function') {
            definirValorMonetario(inputValor, 0);
        } else {
            inputValor.value = '0,00';
        }
        document.getElementById('data_transacao').value = new Date().toISOString().split('T')[0];
        document.getElementById('total_parcelas').value = '1';
        document.getElementById('conta_id').value = '';
        document.getElementById('conta_destino_id').value = '';
        document.getElementById('categoria_id').value = '';
        document.getElementById('observacao').value = '';
        setTipo('saida');
        openModal('modalTransacao');
    }

    function editarTransacao(id) {
        fetch('api/obter_transacao.php?id=' + id)
            .then(r => r.text())
            .then(text => {
                text = text.trim().replace(/^\uFEFF/, '');
                try {
                    return JSON.parse(text);
                } catch(e) {
                    console.error('Resposta inválida do servidor:', text);
                    throw new Error('Resposta inválida ao obter transação');
                }
            })
            .then(data => {
                if (data.sucesso) {
                    const t = data.transacao;
                    document.getElementById('modalTitulo').textContent = 'Editar Transação';
                    document.getElementById('transacao_id').value = t.id;
                    document.getElementById('descricao').value = t.descricao;
                    if (typeof definirValorMonetario === 'function') {
                        definirValorMonetario(document.getElementById('valor'), parseFloat(t.valor));
                    } else {
                        document.getElementById('valor').value = t.valor;
                    }
                    document.getElementById('data_transacao').value = t.data_transacao;
                    document.getElementById('conta_id').value = t.conta_id || '';
                    document.getElementById('conta_destino_id').value = t.conta_destino_id || '';
                    document.getElementById('categoria_id').value = t.categoria_id || '';
                    document.getElementById('observacao').value = t.observacao || '';
                    setTipo(t.tipo);
                    openModal('modalTransacao');
                } else {
                    alert('Erro ao carregar transação: ' + (data.erro || 'Desconhecido'));
                }
            })
            .catch(err => {
                console.error('Erro ao editar transação:', err);
                alert('Erro ao editar transação: ' + err.message);
            });
    }

    function salvarTransacao(e) {
        e.preventDefault();

        const btn = e.target.querySelector('button[type="submit"]');
        if (btn.disabled) return;

        const elValor = document.getElementById('valor');
        const valorNumerico = typeof obterValorNumerico === 'function' ? obterValorNumerico(elValor) : (parseFloat(elValor.value.replace(/\./g, '').replace(',', '.')) || 0);

        const dados = {
            id: document.getElementById('transacao_id').value,
            descricao: document.getElementById('descricao').value,
            valor: valorNumerico,
            tipo: document.getElementById('tipo').value,
            data_transacao: document.getElementById('data_transacao').value,
            total_parcelas: document.getElementById('total_parcelas').value,
            conta_id: document.getElementById('conta_id').value,
            conta_destino_id: document.getElementById('conta_destino_id').value,
            categoria_id: document.getElementById('categoria_id').value,
            observacao: document.getElementById('observacao').value
        };

        if (!dados.descricao || !dados.descricao.trim()) {
            alert('Por favor, informe a descrição.');
            return;
        }

        if (dados.valor <= 0) {
            alert('Por favor, informe um valor maior que zero.');
            return;
        }

        // Validação para transferências: conta origem E destino são obrigatórias
        if (dados.tipo === 'transferencia') {
            if (!dados.conta_id) {
                alert('Selecione a conta de ORIGEM da transferência.');
                return;
            }
            if (!dados.conta_destino_id) {
                alert('Selecione a conta de DESTINO da transferência.');
                return;
            }
            if (dados.conta_id === dados.conta_destino_id) {
                alert('A conta de origem e destino não podem ser a mesma.');
                return;
            }
        }

        // Desabilitar botão e mostrar loading
        btn.disabled = true;
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';

        fetch('api/salvar_transacao.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(dados)
        })
            .then(r => {
                if (!r.ok) {
                    return r.text().then(text => {
                        throw new Error('Servidor retornou erro ' + r.status + ': ' + text.substring(0, 200));
                    });
                }
                return r.text().then(text => {
                    // Remover BOM e espaços invisíveis antes de parsear
                    text = text.trim().replace(/^\uFEFF/, '');
                    try {
                        return JSON.parse(text);
                    } catch(e) {
                        console.error('Resposta inválida do servidor:', text);
                        throw new Error('Resposta inválida: ' + text.substring(0, 100));
                    }
                });
            })
            .then(data => {
                if (data.sucesso) {
                    if (dados.data_transacao) {
                        const parts = dados.data_transacao.split('-');
                        if (parts.length === 3) {
                            const anoTx = parts[0];
                            const mesTx = parseInt(parts[1], 10);
                            const urlParams = new URLSearchParams(window.location.search);
                            urlParams.set('mes', mesTx);
                            urlParams.set('ano', anoTx);
                            window.location.href = window.location.pathname + '?' + urlParams.toString();
                            return;
                        }
                    }
                    window.location.reload();
                } else {
                    alert('Erro: ' + data.erro);
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            })
            .catch(err => {
                console.error('Erro ao salvar transação:', err);
                alert('Erro ao salvar transação: ' + err.message);
                btn.disabled = false;
                btn.innerHTML = originalText;
            });
    }

    let idTransacaoExcluir = null;

    function excluirTransacao(id) {
        idTransacaoExcluir = id;
        document.getElementById('modalExcluir').classList.add('active');
    }

    function fecharModalExcluir() {
        idTransacaoExcluir = null;
        document.getElementById('modalExcluir').classList.remove('active');
    }

    function confirmarExclusao() {
        if (!idTransacaoExcluir) return;
        
        const btn = document.getElementById('btnConfirmarExclusao');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Excluindo...';
        btn.disabled = true;

        fetch('api/excluir_transacao.php?id=' + idTransacaoExcluir)
                .then(r => r.text())
                .then(text => {
                    text = text.trim().replace(/^\uFEFF/, '');
                    try {
                        return JSON.parse(text);
                    } catch(e) {
                        console.error('Resposta inválida do servidor:', text);
                        throw new Error('Resposta inválida do servidor');
                    }
                })
                .then(data => {
                    if (data.sucesso) {
                        location.reload();
                    } else {
                        alert('Erro ao excluir: ' + (data.erro || 'Desconhecido'));
                        fecharModalExcluir();
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                    }
                })
                .catch(err => {
                    console.error('Erro ao excluir:', err);
                    alert('Erro ao excluir transação: ' + err.message);
                    fecharModalExcluir();
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                });
    }
</script>

<!-- Modal de Confirmação de Exclusão -->
<div id="modalExcluir" class="modal-overlay">
    <div class="modal-content" style="max-width: 400px; text-align: center; padding: 30px;">
        <div style="font-size: 3rem; color: var(--danger); margin-bottom: 15px;">
            <i class="fas fa-trash-alt"></i>
        </div>
        <h3 style="margin-bottom: 10px;">Excluir Transação</h3>
        <p style="color: var(--text-secondary); margin-bottom: 25px;">
            Tem certeza que deseja excluir esta transação? Esta ação não pode ser desfeita.
        </p>
        <div style="display: flex; gap: 10px; justify-content: center;">
            <button onclick="fecharModalExcluir()" class="btn btn-ghost" style="flex: 1;">Cancelar</button>
            <button id="btnConfirmarExclusao" onclick="confirmarExclusao()" class="btn btn-danger" style="flex: 1;">Excluir</button>
        </div>
    </div>
</div>

</main>
<script src="assets/js/app.js"></script>
</body>

</html>