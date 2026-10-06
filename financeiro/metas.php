<?php
/**
 * =====================================================
 * FinnMestre - Metas Financeiras
 * =====================================================
 */
require_once 'includes/header.php';

$mensagem = '';
$tipoMensagem = '';

// Processar formulário de meta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['meta_titulo'])) {
    $dados = [
        'id' => !empty($_POST['meta_id']) ? intval($_POST['meta_id']) : null,
        'titulo' => trim($_POST['meta_titulo']),
        'descricao' => trim($_POST['meta_descricao'] ?? ''),
        'valor_objetivo' => floatval(str_replace(',', '.', str_replace('.', '', $_POST['meta_valor']))),
        'valor_mensal' => floatval(str_replace(',', '.', str_replace('.', '', $_POST['meta_valor_mensal'] ?? '0'))),
        'data_inicio' => $_POST['meta_data_inicio'] ?: date('Y-m-d'),
        'data_limite' => $_POST['meta_data_limite'] ?: null,
        'cor' => $_POST['meta_cor'] ?? '#10b981',
        'icone' => $_POST['meta_icone'] ?? 'fa-bullseye',
        'prioridade' => $_POST['meta_prioridade'] ?? 'media'
    ];

    if (salvarMeta($dados)) {
        $mensagem = $dados['id'] ? 'Meta atualizada com sucesso!' : 'Meta criada com sucesso!';
        $tipoMensagem = 'success';
    } else {
        $mensagem = 'Erro ao salvar meta.';
        $tipoMensagem = 'danger';
    }
}

// Adicionar/Remover contribuição
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contribuicao_valor'])) {
    $metaId = intval($_POST['contribuicao_meta_id']);
    $valor = floatval(str_replace(',', '.', str_replace('.', '', $_POST['contribuicao_valor'])));
    $operacao = $_POST['contribuicao_operacao'] ?? 'adicionar';
    $obs = trim($_POST['contribuicao_obs'] ?? '');

    // Se for remover, inverte o sinal do valor
    if ($operacao === 'remover') {
        $valor = -abs($valor);
        $obs = $obs ?: 'Remoção de valor';
    }

    if (adicionarContribuicaoMeta($metaId, $valor, $obs)) {
        $mensagem = $operacao === 'remover' ? 'Valor removido com sucesso!' : 'Contribuição adicionada com sucesso!';
        $tipoMensagem = 'success';
    } else {
        $mensagem = 'Erro ao processar operação.';
        $tipoMensagem = 'danger';
    }
}

// Excluir meta
if (isset($_GET['excluir'])) {
    if (excluirMeta(intval($_GET['excluir']))) {
        $mensagem = 'Meta excluída com sucesso!';
        $tipoMensagem = 'success';
    } else {
        $mensagem = 'Erro ao excluir meta.';
        $tipoMensagem = 'danger';
    }
}

$metas = listarMetas(false);
$metasPendentes = array_filter($metas, fn($m) => !$m['concluida']);
$metasConcluidas = array_filter($metas, fn($m) => $m['concluida']);

// Estatísticas
$totalObjetivo = array_sum(array_column($metasPendentes, 'valor_objetivo'));
$totalAtual = array_sum(array_column($metasPendentes, 'valor_atual'));
$percentualGeral = $totalObjetivo > 0 ? ($totalAtual / $totalObjetivo) * 100 : 0;

// Ícones disponíveis
$iconesDisponiveis = [
    'fa-bullseye' => 'Alvo',
    'fa-home' => 'Casa',
    'fa-car' => 'Carro',
    'fa-plane' => 'Viagem',
    'fa-graduation-cap' => 'Educação',
    'fa-laptop' => 'Tecnologia',
    'fa-ring' => 'Casamento',
    'fa-baby' => 'Bebê',
    'fa-piggy-bank' => 'Reserva',
    'fa-heart' => 'Saúde',
    'fa-gift' => 'Presente',
    'fa-star' => 'Especial'
];
?>

<div class="page-header">
    <h1><i class="fas fa-bullseye" style="color: var(--fm-primary);"></i> <?= __('suas_metas') ?></h1>
    <p><?= __('gerencie_metas') ?></p>
</div>

<?php if ($mensagem): ?>
    <div class="alert alert-<?= $tipoMensagem ?>">
        <i class="fas fa-<?= $tipoMensagem === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
        <?= $mensagem ?>
    </div>
<?php endif; ?>

<!-- Resumo das Metas -->
<div class="summary-cards">
    <div class="card animate-in">
        <div class="card-header">
            <span class="card-title">Metas Ativas</span>
            <div class="card-icon primary">
                <i class="fas fa-flag"></i>
            </div>
        </div>
        <div class="card-value primary">
            <?= count($metasPendentes) ?>
        </div>
        <div class="card-subtitle">objetivos em andamento</div>
    </div>

    <div class="card animate-in" style="animation-delay: 0.1s">
        <div class="card-header">
            <span class="card-title">Total a Alcançar</span>
            <div class="card-icon accent">
                <i class="fas fa-mountain"></i>
            </div>
        </div>
        <div class="card-value accent">
            <?= formatarMoeda($totalObjetivo) ?>
        </div>
        <div class="card-subtitle">soma de todas as metas</div>
    </div>

    <div class="card animate-in" style="animation-delay: 0.2s">
        <div class="card-header">
            <span class="card-title">Já Conquistado</span>
            <div class="card-icon success">
                <i class="fas fa-trophy"></i>
            </div>
        </div>
        <div class="card-value success">
            <?= formatarMoeda($totalAtual) ?>
        </div>
        <div class="card-subtitle">
            <?= number_format($percentualGeral, 1) ?>% do total
        </div>
    </div>

    <div class="card animate-in" style="animation-delay: 0.3s">
        <div class="card-header">
            <span class="card-title">Metas Concluídas</span>
            <div class="card-icon warning">
                <i class="fas fa-medal"></i>
            </div>
        </div>
        <div class="card-value warning">
            <?= count($metasConcluidas) ?>
        </div>
        <div class="card-subtitle">objetivos alcançados 🎉</div>
    </div>
</div>

<!-- Botão Nova Meta -->
<div style="margin-bottom: 24px;">
    <button onclick="novaMeta()" class="btn btn-primary">
        <i class="fas fa-plus"></i> <?= __('nova_meta') ?>
    </button>
</div>

<!-- Grid de Metas -->
<?php if (empty($metasPendentes)): ?>
    <div class="form-card">
        <div class="empty-state">
            <i class="fas fa-bullseye"></i>
            <h4><?= __('nenhuma_meta') ?></h4>
            <p><?= __('crie_primeira_meta') ?></p>
            <button onclick="novaMeta()" class="btn btn-primary" style="margin-top: 20px;">
                <i class="fas fa-plus"></i> <?= __('nova_meta') ?>
            </button>
        </div>
    </div>
<?php else: ?>
    <div
        style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <?php foreach ($metasPendentes as $meta): ?>
            <?php
            $diasRestantes = $meta['data_limite'] ? max(0, (strtotime($meta['data_limite']) - time()) / 86400) : null;
            $urgente = $diasRestantes !== null && $diasRestantes <= 7;
            ?>
            <div class="goal-card <?= $meta['prioridade'] ?> animate-in" style="animation-delay: 0.1s;">
                <div class="goal-header">
                    <div style="display: flex; gap: 14px; align-items: flex-start;">
                        <div class="goal-icon" style="background: <?= $meta['cor'] ?>20; color: <?= $meta['cor'] ?>;">
                            <i class="fas <?= $meta['icone'] ?>"></i>
                        </div>
                        <div>
                            <div class="goal-title">
                                <?= htmlspecialchars($meta['titulo']) ?>
                            </div>
                            <?php if ($meta['descricao']): ?>
                                <div class="goal-desc">
                                    <?= htmlspecialchars($meta['descricao']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="btn-group" style="flex-shrink: 0;">
                        <button onclick='editarMeta(<?= json_encode($meta) ?>)' class="btn btn-icon btn-ghost btn-sm"
                            title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <a href="?excluir=<?= $meta['id'] ?>" onclick="return confirm('Excluir esta meta?')"
                            class="btn btn-icon btn-ghost btn-sm" title="Excluir" style="color: var(--danger);">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </div>

                <div class="goal-progress">
                    <div class="goal-progress-bar">
                        <div class="goal-progress-fill"
                            style="width: <?= $meta['percentual'] ?>%; background: <?= $meta['cor'] ?>;"></div>
                    </div>
                    <div class="goal-stats">
                        <span class="goal-current" style="color: <?= $meta['cor'] ?>;">
                            <?= formatarMoeda($meta['valor_atual']) ?>
                        </span>
                        <span class="goal-target">de
                            <?= formatarMoeda($meta['valor_objetivo']) ?>
                        </span>
                    </div>
                    <?php if (($meta['valor_mensal'] ?? 0) > 0): ?>
                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">
                            <i class="fas fa-calendar-check"></i> Planejado: <strong><?= formatarMoeda($meta['valor_mensal']) ?>/mês</strong>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Calculadora de meses para bater a meta -->
                <?php
                $falta = $meta['valor_objetivo'] - $meta['valor_atual'];
                $diasCriacao = max(1, (time() - strtotime($meta['data_inicio'])) / 86400);
                $mediaDiaria = $meta['valor_atual'] / $diasCriacao;
                $mediaMensalHistorica = $mediaDiaria * 30;
                
                // Priorizar o valor planejado se existir, senão usar a média histórica
                $valorBaseCalculo = (($meta['valor_mensal'] ?? 0) > 0) ? $meta['valor_mensal'] : $mediaMensalHistorica;
                $mesesParaBater = $valorBaseCalculo > 0 ? ceil($falta / $valorBaseCalculo) : 0;
                $origemMedia = (($meta['valor_mensal'] ?? 0) > 0) ? 'planejado' : 'média';
                ?>
                <?php if ($falta > 0 && $valorBaseCalculo > 0): ?>
                    <div style="background: var(--bg-glass-light); padding: 12px 14px; border-radius: var(--radius-md); margin-top: 12px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-calculator" style="color: var(--fm-primary);"></i>
                        <div style="font-size: 0.85rem; color: var(--text-secondary);">
                            <strong style="color: <?= $meta['cor'] ?>;">~<?= $mesesParaBater ?> <?= $mesesParaBater == 1 ? 'mês' : 'meses' ?></strong> para atingir
                            <div style="font-size: 0.75rem; opacity: 0.8;">(baseado no valor <?= $origemMedia ?> de <?= formatarMoeda($valorBaseCalculo) ?>/mês)</div>
                        </div>
                        <button onclick='abrirSimulador(<?= json_encode($meta) ?>)' class="btn btn-icon btn-ghost btn-sm" style="margin-left: auto;" title="Simular outros valores">
                            <i class="fas fa-magic"></i>
                        </button>
                    </div>
                <?php elseif ($falta > 0): ?>
                    <div style="background: var(--bg-glass-light); padding: 12px 14px; border-radius: var(--radius-md); margin-top: 12px; display: flex; align-items: center; gap: 10px; cursor: pointer;" onclick='abrirSimulador(<?= json_encode($meta) ?>)'>
                        <i class="fas fa-calculator" style="color: var(--fm-primary);"></i>
                        <div style="font-size: 0.85rem; color: var(--text-secondary);">
                            Clique para <strong>simular quanto tempo</strong> levará para bater essa meta.
                        </div>
                    </div>
                <?php endif; ?>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 16px;">
                    <?php if ($meta['data_limite']): ?>
                        <div class="goal-deadline <?= $urgente ? 'urgent' : '' ?>">
                            <i class="fas fa-clock"></i>
                            <?php if ($diasRestantes <= 0): ?>
                                Vencida!
                            <?php else: ?>
                                <?= ceil($diasRestantes) ?> dias restantes
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="goal-deadline">
                            <i class="fas fa-infinity"></i> Sem prazo
                        </div>
                    <?php endif; ?>

                    <div style="display: flex; gap: 8px;">
                        <button
                            onclick="abrirContribuicao(<?= $meta['id'] ?>, '<?= htmlspecialchars($meta['titulo']) ?>', 'adicionar')"
                            class="btn btn-sm" style="background: <?= $meta['cor'] ?>; color: white;">
                            <i class="fas fa-plus"></i> Adicionar
                        </button>
                        <?php if ($meta['valor_atual'] > 0): ?>
                            <button
                                onclick="abrirContribuicao(<?= $meta['id'] ?>, '<?= htmlspecialchars($meta['titulo']) ?>', 'remover')"
                                class="btn btn-sm btn-ghost" style="color: var(--danger);">
                                <i class="fas fa-minus"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Barra de prioridade -->
                <div
                    style="position: absolute; top: 0; left: 0; right: 0; height: 4px; border-radius: var(--radius-lg) var(--radius-lg) 0 0; overflow: hidden;">
                    <?php
                    $corPrioridade = match ($meta['prioridade']) {
                        'alta' => 'var(--danger)',
                        'media' => 'var(--warning)',
                        default => 'var(--success)'
                    };
                    ?>
                    <div style="height: 100%; background: <?= $corPrioridade ?>;"></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Metas Concluídas -->
<?php if (!empty($metasConcluidas)): ?>
    <div class="form-card" style="margin-top: 30px;">
        <h3><i class="fas fa-trophy" style="color: var(--warning);"></i> Metas Concluídas 🎉</h3>
        <div
            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; margin-top: 20px;">
            <?php foreach ($metasConcluidas as $meta): ?>
                <div
                    style="padding: 16px; background: var(--success-light); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: var(--radius-md); display: flex; align-items: center; gap: 14px;">
                    <div
                        style="width: 45px; height: 45px; border-radius: 50%; background: var(--success); color: white; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-check"></i>
                    </div>
                    <div style="flex: 1;">
                        <div style="font-weight: 600;">
                            <?= htmlspecialchars($meta['titulo']) ?>
                        </div>
                        <div style="font-size: 0.85rem; color: var(--text-muted);">
                            Alcançado:
                            <?= formatarMoeda($meta['valor_objetivo']) ?>
                        </div>
                    </div>
                    <i class="fas fa-medal" style="font-size: 1.5rem; color: var(--warning);"></i>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Modal Nova/Editar Meta -->
<div id="modalMeta" class="modal-overlay">
    <div class="modal" style="max-width: 550px;">
        <div class="modal-header">
            <h3><i class="fas fa-bullseye"></i> <span id="modalMetaTitulo">Nova Meta</span></h3>
            <button class="modal-close" onclick="closeModal('modalMeta')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" id="meta_id" name="meta_id">

                <div class="form-group">
                    <label class="required">Título da Meta</label>
                    <input type="text" id="meta_titulo" name="meta_titulo" class="form-control" required
                        placeholder="Ex: Reserva de Emergência, Viagem, etc.">
                </div>

                <div class="form-group">
                    <label>Descrição</label>
                    <textarea id="meta_descricao" name="meta_descricao" class="form-control" rows="2"
                        placeholder="Detalhes sobre sua meta (opcional)"></textarea>
                </div>

                <div class="form-grid" style="grid-template-columns: 1fr 1fr;">
                    <div class="form-group">
                        <label class="required">Valor Objetivo</label>
                        <input type="text" id="meta_valor" name="meta_valor" class="form-control input-moeda" required
                            placeholder="0,00">
                    </div>
                    <div class="form-group">
                        <label>Planejamento Mensal</label>
                        <input type="text" id="meta_valor_mensal" name="meta_valor_mensal" class="form-control input-moeda"
                            placeholder="Quanto guardar por mês?">
                    </div>
                </div>

                <div class="form-grid" style="grid-template-columns: 1fr 1fr;">
                    <div class="form-group">
                        <label>Data Início</label>
                        <input type="date" id="meta_data_inicio" name="meta_data_inicio" class="form-control"
                            value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group">
                        <label>Data Limite</label>
                        <input type="date" id="meta_data_limite" name="meta_data_limite" class="form-control">
                        <span class="form-hint">Deixe vazio se não tiver prazo</span>
                    </div>
                </div>

                <div class="form-grid" style="grid-template-columns: 1fr 1fr 1fr;">
                    <div class="form-group">
                        <label>Prioridade</label>
                        <select id="meta_prioridade" name="meta_prioridade" class="form-control">
                            <option value="baixa">🟢 Baixa</option>
                            <option value="media" selected>🟡 Média</option>
                            <option value="alta">🔴 Alta</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Cor</label>
                        <input type="color" id="meta_cor" name="meta_cor" class="form-control" value="#10b981"
                            style="height: 46px; padding: 5px;">
                    </div>
                    <div class="form-group">
                        <label>Ícone</label>
                        <select id="meta_icone" name="meta_icone" class="form-control">
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
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalMeta')">Cancelar</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Salvar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Contribuição -->
<div id="modalContribuicao" class="modal-overlay">
    <div class="modal" style="max-width: 400px;">
        <div class="modal-header">
            <h3><i class="fas fa-plus-circle"></i> Adicionar Contribuição</h3>
            <button class="modal-close" onclick="closeModal('modalContribuicao')">&times;</button>
        </div>
        <form method="POST" id="formContribuicao">
            <div class="modal-body">
                <input type="hidden" id="contribuicao_meta_id" name="contribuicao_meta_id">
                <input type="hidden" id="contribuicao_operacao" name="contribuicao_operacao" value="adicionar">

                <p style="margin-bottom: 20px; color: var(--text-secondary);">
                    Contribuindo para: <strong id="contribuicaoMetaNome"></strong>
                </p>

                <div class="form-group">
                    <label class="required">Valor</label>
                    <input type="text" id="contribuicao_valor" name="contribuicao_valor"
                        class="form-control input-moeda" required placeholder="0,00">
                </div>

                <div class="form-group">
                    <label>Observação</label>
                    <input type="text" id="contribuicao_obs" name="contribuicao_obs" class="form-control"
                        placeholder="Ex: Depósito mensal (opcional)">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary"
                    onclick="closeModal('modalContribuicao')">Cancelar</button>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-plus"></i> Adicionar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Inicializar inputs monetários
    document.addEventListener('DOMContentLoaded', function () {
        const inputMetaValor = document.getElementById('meta_valor');
        const inputContribValor = document.getElementById('contribuicao_valor');

        if (inputMetaValor && typeof inicializarInputMonetario === 'function') {
            inicializarInputMonetario(inputMetaValor);
        }
        if (inputContribValor && typeof inicializarInputMonetario === 'function') {
            inicializarInputMonetario(inputContribValor);
        }
    });

    function novaMeta() {
        document.getElementById('modalMetaTitulo').textContent = 'Nova Meta';
        document.getElementById('meta_id').value = '';
        document.getElementById('meta_titulo').value = '';
        document.getElementById('meta_descricao').value = '';
        definirValorMonetario(document.getElementById('meta_valor'), 0);
        definirValorMonetario(document.getElementById('meta_valor_mensal'), 0);
        document.getElementById('meta_data_inicio').value = new Date().toISOString().split('T')[0];
        document.getElementById('meta_data_limite').value = '';
        document.getElementById('meta_prioridade').value = 'media';
        document.getElementById('meta_cor').value = '#10b981';
        document.getElementById('meta_icone').value = 'fa-bullseye';
        openModal('modalMeta');
    }

    function editarMeta(meta) {
        document.getElementById('modalMetaTitulo').textContent = 'Editar Meta';
        document.getElementById('meta_id').value = meta.id;
        document.getElementById('meta_titulo').value = meta.titulo;
        document.getElementById('meta_descricao').value = meta.descricao || '';
        definirValorMonetario(document.getElementById('meta_valor'), parseFloat(meta.valor_objetivo));
        definirValorMonetario(document.getElementById('meta_valor_mensal'), parseFloat(meta.valor_mensal || 0));
        document.getElementById('meta_data_inicio').value = meta.data_inicio || '';
        document.getElementById('meta_data_limite').value = meta.data_limite || '';
        document.getElementById('meta_prioridade').value = meta.prioridade || 'media';
        document.getElementById('meta_cor').value = meta.cor || '#10b981';
        document.getElementById('meta_icone').value = meta.icone || 'fa-bullseye';
        openModal('modalMeta');
    }

    function abrirContribuicao(metaId, metaNome, tipo = 'adicionar') {
        document.getElementById('contribuicao_meta_id').value = metaId;
        document.getElementById('contribuicaoMetaNome').textContent = metaNome;
        document.getElementById('contribuicao_valor').value = '';
        document.getElementById('contribuicao_obs').value = '';

        const titulo = document.querySelector('#modalContribuicao .modal-header h3');
        const btnSubmit = document.querySelector('#modalContribuicao .btn-success');
        const inputValor = document.getElementById('contribuicao_valor');

        if (tipo === 'remover') {
            titulo.innerHTML = '<i class="fas fa-minus-circle"></i> Remover Valor';
            btnSubmit.innerHTML = '<i class="fas fa-minus"></i> Remover';
            btnSubmit.classList.remove('btn-success');
            btnSubmit.classList.add('btn-danger');
            inputValor.setAttribute('data-operacao', 'remover');
            document.getElementById('contribuicao_operacao').value = 'remover';
        } else {
            titulo.innerHTML = '<i class="fas fa-plus-circle"></i> Adicionar Contribuição';
            btnSubmit.innerHTML = '<i class="fas fa-plus"></i> Adicionar';
            btnSubmit.classList.remove('btn-danger');
            btnSubmit.classList.add('btn-success');
            inputValor.setAttribute('data-operacao', 'adicionar');
            document.getElementById('contribuicao_operacao').value = 'adicionar';
        }

        openModal('modalContribuicao');
    }

    let metaSimulando = null;

    function abrirSimulador(meta) {
        metaSimulando = meta;
        const falta = meta.valor_objetivo - meta.valor_atual;
        document.getElementById('simuMetaNome').textContent = meta.titulo;
        document.getElementById('simuMetaFalta').textContent = formatarMoeda(falta);
        
        const valorPlanejado = parseFloat(meta.valor_mensal || 0);
        const inputSimu = document.getElementById('simuValorMensal');
        
        if (valorPlanejado > 0) {
            definirValorMonetario(inputSimu, valorPlanejado);
        } else {
            inputSimu.value = '';
        }
        
        document.getElementById('resultadoSimulacao').style.display = 'none';
        
        openModal('modalSimulador');
        
        setTimeout(() => {
            inputSimu.focus();
            if (typeof inicializarInputMonetario === 'function') inicializarInputMonetario(inputSimu);
            if (valorPlanejado > 0) calcularSimulacao();
        }, 100);
    }

    function salvarValorSimulado() {
        if (!metaSimulando) return;
        
        const input = document.getElementById('simuValorMensal');
        const valor = input.value;
        
        if (!valor || valor === '0,00') {
            alert('Digite um valor válido para salvar.');
            return;
        }

        // Preencher o formulário principal de edição e submeter
        document.getElementById('meta_id').value = metaSimulando.id;
        document.getElementById('meta_titulo').value = metaSimulando.titulo;
        document.getElementById('meta_descricao').value = metaSimulando.descricao || '';
        definirValorMonetario(document.getElementById('meta_valor'), parseFloat(metaSimulando.valor_objetivo));
        document.getElementById('meta_valor_mensal').value = valor;
        document.getElementById('meta_data_inicio').value = metaSimulando.data_inicio || '';
        document.getElementById('meta_data_limite').value = metaSimulando.data_limite || '';
        document.getElementById('meta_prioridade').value = metaSimulando.prioridade || 'media';
        document.getElementById('meta_cor').value = metaSimulando.cor || '#10b981';
        document.getElementById('meta_icone').value = metaSimulando.icone || 'fa-bullseye';
        
        // Submeter o formulário
        document.querySelector('#modalMeta form').submit();
    }

    function calcularSimulacao() {
        const input = document.getElementById('simuValorMensal');
        const valorMensal = parseFloat(input.value.replace(/[^\d,]/g, '').replace(',', '.')) || 0;
        
        if (valorMensal <= 0) {
            document.getElementById('resultadoSimulacao').style.display = 'none';
            return;
        }

        const falta = metaSimulando.valor_objetivo - metaSimulando.valor_atual;
        const meses = Math.ceil(falta / valorMensal);
        
        const resDiv = document.getElementById('resultadoSimulacao');
        const tempoDiv = document.getElementById('simuTempo');
        const dataDiv = document.getElementById('simuDataFim');

        resDiv.style.display = 'block';
        
        if (meses > 12) {
            const anos = Math.floor(meses / 12);
            const mesesRestantes = meses % 12;
            tempoDiv.textContent = `${anos} ${anos == 1 ? 'ano' : 'anos'}${mesesRestantes > 0 ? ' e ' + mesesRestantes + ' ' + (mesesRestantes == 1 ? 'mês' : 'meses') : ''}`;
        } else {
            tempoDiv.textContent = `${meses} ${meses == 1 ? 'mês' : 'meses'}`;
        }

        const dataFim = new Date();
        dataFim.setMonth(dataFim.getMonth() + meses);
        dataDiv.innerHTML = `<i class="fas fa-check-circle" style="color: #06d6a0;"></i> Previsão de conclusão: <strong>${dataFim.toLocaleDateString('pt-BR', {month: 'long', year: 'numeric'})}</strong>`;
        
        if (metaSimulando.data_limite) {
            const limite = new Date(metaSimulando.data_limite);
            if (dataFim > limite) {
                dataDiv.innerHTML += `<br><span style="color: var(--danger); font-size: 0.75rem;"><i class="fas fa-exclamation-triangle"></i> Atenção: Ultrapassa o prazo de ${limite.toLocaleDateString('pt-BR')}</span>`;
            } else {
                dataDiv.innerHTML += `<br><span style="color: #06d6a0; font-size: 0.75rem;"><i class="fas fa-thumbs-up"></i> Dentro do prazo estipulado!</span>`;
            }
        }
    }

    function formatarMoeda(valor) {
        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(valor);
    }
</script>

<!-- Modal Simulador de Meta -->
<div id="modalSimulador" class="modal-overlay">
    <div class="modal" style="max-width: 450px;">
        <div class="modal-header">
            <h3><i class="fas fa-calculator"></i> Simulador de Meta</h3>
            <button class="modal-close" onclick="closeModal('modalSimulador')">&times;</button>
        </div>
        <div class="modal-body">
            <p style="margin-bottom: 20px; color: var(--text-secondary);">
                Meta: <strong id="simuMetaNome"></strong><br>
                Faltam: <strong id="simuMetaFalta" style="color: var(--fm-primary);"></strong>
            </p>

            <div class="form-group">
                <label class="required">Quanto você consegue guardar por mês?</label>
                <div style="display: flex; gap: 8px;">
                    <input type="text" id="simuValorMensal" class="form-control input-moeda" onkeyup="calcularSimulacao()" placeholder="0,00" style="flex: 1;">
                    <button type="button" class="btn btn-primary btn-sm" onclick="salvarValorSimulado()" title="Salvar este valor na meta">
                        <i class="fas fa-save"></i>
                    </button>
                </div>
            </div>

            <div id="resultadoSimulacao" style="display: none; margin-top: 20px; padding: 16px; background: rgba(0, 132, 255, 0.1); border-radius: 12px; border: 1px solid rgba(0, 132, 255, 0.2);">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 40px; height: 40px; background: var(--fm-primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white;">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div>
                        <div style="font-size: 0.8rem; color: #8b95a5;">Tempo estimado:</div>
                        <div id="simuTempo" style="font-size: 1.1rem; font-weight: 700; color: white;"></div>
                    </div>
                </div>
                <div id="simuDataFim" style="margin-top: 12px; font-size: 0.85rem; color: #8b95a5; padding-top: 12px; border-top: 1px solid rgba(255,255,255,0.1);">
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('modalSimulador')">Fechar</button>
        </div>
    </div>
</div>

</main>
<script src="assets/js/app.js"></script>
</body>

</html>