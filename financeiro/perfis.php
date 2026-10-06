<?php
/**
 * =====================================================
 * FinnMestre - Gerenciamento de Perfis
 * =====================================================
 */
require_once 'includes/header.php';
require_once 'includes/perfis.php';

$mensagem = '';
$tipoMensagem = '';

// Processar formulário de perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['perfil_nome'])) {
    $dados = [
        'id' => !empty($_POST['perfil_id']) ? intval($_POST['perfil_id']) : null,
        'nome' => trim($_POST['perfil_nome']),
        'tipo' => $_POST['perfil_tipo'] ?? 'pessoal',
        'cor' => $_POST['perfil_cor'] ?? '#6366f1',
        'icone' => $_POST['perfil_icone'] ?? 'fa-user'
    ];

    $resultado = salvarPerfil($dados);
    if ($resultado === 'LIMITE_PERFIS') {
        $mensagem = 'Você atingiu o limite de perfis do seu plano. Faça upgrade para ter perfis ilimitados!';
        $tipoMensagem = 'danger';
    } elseif ($resultado) {
        $mensagem = $dados['id'] ? 'Perfil atualizado com sucesso!' : 'Perfil criado com sucesso!';
        $tipoMensagem = 'success';
    } else {
        $mensagem = 'Erro ao salvar perfil.';
        $tipoMensagem = 'danger';
    }
}

// Excluir perfil
if (isset($_GET['excluir'])) {
    if (excluirPerfil(intval($_GET['excluir']))) {
        $mensagem = 'Perfil excluído com sucesso!';
        $tipoMensagem = 'success';
    } else {
        $mensagem = 'Não é possível excluir o único perfil ativo.';
        $tipoMensagem = 'danger';
    }
}

// Trocar perfil ativo
if (isset($_GET['trocar'])) {
    if (setPerfilAtual(intval($_GET['trocar']))) {
        $perfil = obterPerfil(intval($_GET['trocar']));
        $mensagem = "Perfil alterado para: {$perfil['nome']}";
        $tipoMensagem = 'success';
    }
}

$perfis = listarPerfis();
$perfilAtual = obterPerfilAtual();
$icones = getIconesPerfil();
$tipos = getTiposPerfil();
$cores = getCoresPerfil();

// Limite de perfis
$limitePerfis = function_exists('obterLimitePerfis') ? obterLimitePerfis($_SESSION['usuario_id']) : 999;
$totalPerfis = count($perfis);
?>

<div class="page-header">
    <h1><i class="fas fa-users" style="color: var(--fm-primary);"></i> Perfis Financeiros</h1>
    <p>Gerencie múltiplos perfis para organizar suas finanças</p>
</div>

<?php if ($mensagem): ?>
    <div class="alert alert-<?= $tipoMensagem ?>">
        <i class="fas fa-<?= $tipoMensagem === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
        <?= $mensagem ?>
    </div>
<?php endif; ?>

<!-- Perfil Atual -->
<div class="form-card animate-in" style="margin-bottom: 20px;">
    <div style="display: flex; align-items: center; gap: 20px;">
        <div style="width: 60px; height: 60px; background: <?= $perfilAtual['cor'] ?? '#6366f1' ?>; 
             border-radius: 16px; display: flex; align-items: center; justify-content: center; 
             font-size: 1.5rem; color: white;">
            <i class="fas <?= $perfilAtual['icone'] ?? 'fa-user' ?>"></i>
        </div>
        <div style="flex: 1;">
            <h3 style="margin: 0; font-size: 1.3rem;"><?= htmlspecialchars($perfilAtual['nome'] ?? 'Sem Perfil') ?></h3>
            <span class="badge" style="background: <?= $perfilAtual['cor'] ?? '#6366f1' ?>20; 
                  color: <?= $perfilAtual['cor'] ?? '#6366f1' ?>;">
                <?= $tipos[$perfilAtual['tipo'] ?? 'pessoal'] ?? 'Pessoal' ?>
            </span>
        </div>
        <div>
            <span class="badge success">✓ Perfil Ativo</span>
        </div>
    </div>
</div>

<!-- Modo de Visualização -->
<div class="form-card animate-in" style="margin-bottom: 20px; animation-delay: 0.1s;">
    <h3><i class="fas fa-eye"></i> Modo de Visualização</h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px; margin-top: 16px;">
        <label class="view-mode-card <?= !isModoConsolidado() ? 'active' : '' ?>" 
               onclick="setVisualizacao('individual')">
            <div class="view-mode-icon">
                <i class="fas fa-user"></i>
            </div>
            <div>
                <strong>Visualização Individual</strong>
                <p>Ver dados apenas do perfil selecionado</p>
            </div>
            <input type="radio" name="modo_view" <?= !isModoConsolidado() ? 'checked' : '' ?>>
        </label>
        
        <label class="view-mode-card <?= isModoConsolidado() ? 'active' : '' ?>" 
               onclick="openModal('modalConsolidado')">
            <div class="view-mode-icon consolidado">
                <i class="fas fa-layer-group"></i>
            </div>
            <div>
                <strong>Visualização Consolidada</strong>
                <p>Ver dados de múltiplos perfis combinados</p>
            </div>
            <input type="radio" name="modo_view" <?= isModoConsolidado() ? 'checked' : '' ?>>
        </label>
    </div>
    
    <?php if (isModoConsolidado()): ?>
        <div style="margin-top: 16px; padding: 12px; background: var(--bg-glass-light); border-radius: 8px;">
            <strong><i class="fas fa-info-circle"></i> Perfis consolidados:</strong>
            <?php 
            $consolidados = $_SESSION['perfis_consolidados'] ?? [];
            foreach ($perfis as $p): 
                if (in_array($p['id'], $consolidados)):
            ?>
                <span class="badge" style="background: <?= $p['cor'] ?>20; color: <?= $p['cor'] ?>; margin-left: 8px;">
                    <i class="fas <?= $p['icone'] ?>"></i> <?= htmlspecialchars($p['nome']) ?>
                </span>
            <?php 
                endif;
            endforeach; 
            ?>
            <a href="api/perfis.php?acao=desativar_consolidado" style="margin-left: 10px; color: var(--danger);">
                <i class="fas fa-times"></i> Desativar
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- Lista de Perfis -->
<div class="form-card animate-in" style="animation-delay: 0.2s;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h3 style="margin: 0;"><i class="fas fa-list"></i> Seus Perfis</h3>
        <?php if ($totalPerfis < $limitePerfis): ?>
            <button onclick="novoPerfil()" class="btn btn-primary">
                <i class="fas fa-plus"></i> Novo Perfil
            </button>
        <?php else: ?>
            <a href="assinatura.php" class="btn btn-secondary" title="Limite de perfis atingido">
                <i class="fas fa-lock"></i> Limite Atingido (<?= $limitePerfis ?>)
            </a>
        <?php endif; ?>
    </div>

    <div class="profiles-grid">
        <?php foreach ($perfis as $perfil): ?>
            <div class="profile-card <?= ($perfilAtual && $perfil['id'] == $perfilAtual['id']) ? 'active' : '' ?>">
                <div class="profile-card-header" style="background: <?= $perfil['cor'] ?>15; border-left: 4px solid <?= $perfil['cor'] ?>;">
                    <div class="profile-avatar" style="background: <?= $perfil['cor'] ?>;">
                        <i class="fas <?= $perfil['icone'] ?>"></i>
                    </div>
                    <div class="profile-info">
                        <h4><?= htmlspecialchars($perfil['nome']) ?></h4>
                        <span class="badge" style="background: <?= $perfil['cor'] ?>20; color: <?= $perfil['cor'] ?>;">
                            <?= $tipos[$perfil['tipo']] ?? 'Outro' ?>
                        </span>
                    </div>
                    <?php if ($perfilAtual && $perfil['id'] == $perfilAtual['id']): ?>
                        <span class="badge success" style="position: absolute; top: 10px; right: 10px;">
                            <i class="fas fa-check"></i> Ativo
                        </span>
                    <?php endif; ?>
                </div>
                
                <?php $stats = obterEstatisticasPerfil($perfil['id']); ?>
                <div class="profile-stats">
                    <div class="stat-item">
                        <span class="stat-label">Saldo</span>
                        <span class="stat-value <?= $stats['saldo'] >= 0 ? 'text-success' : 'text-danger' ?>">
                            <?= formatarMoeda($stats['saldo']) ?>
                        </span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Gastos do Mês</span>
                        <span class="stat-value text-danger"><?= formatarMoeda($stats['gastos_mes']) ?></span>
                    </div>
                </div>
                
                <div class="profile-actions">
                    <?php if (!$perfilAtual || $perfil['id'] != $perfilAtual['id']): ?>
                        <a href="?trocar=<?= $perfil['id'] ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-exchange-alt"></i> Usar Este
                        </a>
                    <?php endif; ?>
                    <button onclick='editarPerfil(<?= json_encode($perfil) ?>)' class="btn btn-secondary btn-sm">
                        <i class="fas fa-edit"></i>
                    </button>
                    <?php if (count($perfis) > 1): ?>
                        <a href="?excluir=<?= $perfil['id'] ?>" 
                           onclick="return confirm('Excluir este perfil? Os dados serão mantidos.')" 
                           class="btn btn-ghost btn-sm" style="color: var(--danger);">
                            <i class="fas fa-trash"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modal Novo/Editar Perfil -->
<div id="modalPerfil" class="modal-overlay">
    <div class="modal" style="max-width: 500px;">
        <div class="modal-header">
            <h3><i class="fas fa-user-plus"></i> <span id="modalPerfilTitulo">Novo Perfil</span></h3>
            <button class="modal-close" onclick="closeModal('modalPerfil')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" id="perfil_id" name="perfil_id">

                <div class="form-group">
                    <label class="required">Nome do Perfil</label>
                    <input type="text" id="perfil_nome" name="perfil_nome" class="form-control" 
                           required placeholder="Ex: Pessoal, Empresa, Família...">
                </div>

                <div class="form-group">
                    <label>Tipo</label>
                    <select id="perfil_tipo" name="perfil_tipo" class="form-control">
                        <?php foreach ($tipos as $valor => $nome): ?>
                            <option value="<?= $valor ?>"><?= $nome ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-grid" style="grid-template-columns: 1fr 1fr;">
                    <div class="form-group">
                        <label>Cor</label>
                        <div class="color-picker-grid">
                            <?php foreach ($cores as $hex => $nome): ?>
                                <label class="color-option" title="<?= $nome ?>">
                                    <input type="radio" name="perfil_cor" value="<?= $hex ?>" 
                                           <?= $hex === '#10b981' ? 'checked' : '' ?>>
                                    <span style="background: <?= $hex ?>;"></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Ícone</label>
                        <div class="icon-picker-grid">
                            <?php foreach ($icones as $classe => $nome): ?>
                                <label class="icon-option" title="<?= $nome ?>">
                                    <input type="radio" name="perfil_icone" value="<?= $classe ?>" 
                                           <?= $classe === 'fa-user' ? 'checked' : '' ?>>
                                    <i class="fas <?= $classe ?>"></i>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalPerfil')">Cancelar</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Salvar Perfil
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Consolidar Perfis -->
<div id="modalConsolidado" class="modal-overlay">
    <div class="modal" style="max-width: 450px;">
        <div class="modal-header">
            <h3><i class="fas fa-layer-group"></i> Visão Consolidada</h3>
            <button class="modal-close" onclick="closeModal('modalConsolidado')">&times;</button>
        </div>
        <form action="api/perfis.php" method="GET">
            <input type="hidden" name="acao" value="consolidar">
            <div class="modal-body">
                <p style="color: var(--text-secondary); margin-bottom: 16px;">
                    Selecione os perfis para ver os dados combinados:
                </p>
                
                <div class="consolidate-list">
                    <?php foreach ($perfis as $perfil): ?>
                        <label class="consolidate-item">
                            <input type="checkbox" name="ids[]" value="<?= $perfil['id'] ?>" 
                                   <?= in_array($perfil['id'], $_SESSION['perfis_consolidados'] ?? []) ? 'checked' : '' ?>>
                            <div class="consolidate-avatar" style="background: <?= $perfil['cor'] ?>;">
                                <i class="fas <?= $perfil['icone'] ?>"></i>
                            </div>
                            <span><?= htmlspecialchars($perfil['nome']) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalConsolidado')">Cancelar</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-layer-group"></i> Ativar Consolidado
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.profiles-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 20px;
}

.profile-card {
    background: var(--bg-glass);
    border-radius: var(--radius-lg);
    overflow: hidden;
    border: 1px solid var(--border-subtle);
    position: relative;
    transition: all 0.3s ease;
}

.profile-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
}

.profile-card.active {
    border-color: var(--fm-primary);
}

.profile-card-header {
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    position: relative;
}

.profile-avatar {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.3rem;
}

.profile-info h4 {
    margin: 0 0 6px 0;
    font-size: 1.1rem;
}

.profile-stats {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    padding: 16px 20px;
    background: var(--bg-glass-light);
}

.stat-item {
    display: flex;
    flex-direction: column;
}

.stat-label {
    font-size: 0.8rem;
    color: var(--text-muted);
}

.stat-value {
    font-size: 1.1rem;
    font-weight: 600;
}

.profile-actions {
    padding: 16px 20px;
    display: flex;
    gap: 8px;
}

.view-mode-card {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 20px;
    background: var(--bg-glass-light);
    border-radius: var(--radius-md);
    cursor: pointer;
    border: 2px solid transparent;
    transition: all 0.3s ease;
}

.view-mode-card:hover {
    background: var(--bg-glass);
}

.view-mode-card.active {
    border-color: var(--fm-primary);
    background: var(--fm-primary-alpha);
}

.view-mode-card input[type="radio"] {
    display: none;
}

.view-mode-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--fm-gradient-primary);
    color: white;
    font-size: 1.3rem;
}

.view-mode-icon.consolidado {
    background: linear-gradient(135deg, #8b5cf6, #6366f1);
}

.view-mode-card p {
    font-size: 0.85rem;
    color: var(--text-muted);
    margin: 4px 0 0 0;
}

.color-picker-grid, .icon-picker-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.color-option, .icon-option {
    cursor: pointer;
}

.color-option input, .icon-option input {
    display: none;
}

.color-option span {
    display: block;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: 2px solid transparent;
    transition: all 0.2s;
}

.color-option input:checked + span {
    border-color: white;
    box-shadow: 0 0 0 2px var(--fm-primary);
}

.icon-option i {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--bg-glass-light);
    border-radius: 8px;
    border: 2px solid transparent;
    transition: all 0.2s;
}

.icon-option input:checked + i {
    background: var(--fm-primary);
    color: white;
    border-color: var(--fm-primary);
}

.consolidate-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.consolidate-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: var(--bg-glass-light);
    border-radius: var(--radius-md);
    cursor: pointer;
    transition: all 0.2s;
}

.consolidate-item:hover {
    background: var(--bg-glass);
}

.consolidate-item input[type="checkbox"] {
    width: 20px;
    height: 20px;
    accent-color: var(--fm-primary);
}

.consolidate-avatar {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}
</style>

<script>
function novoPerfil() {
    document.getElementById('modalPerfilTitulo').textContent = 'Novo Perfil';
    document.getElementById('perfil_id').value = '';
    document.getElementById('perfil_nome').value = '';
    document.getElementById('perfil_tipo').value = 'pessoal';
    document.querySelector('input[name="perfil_cor"][value="#10b981"]').checked = true;
    document.querySelector('input[name="perfil_icone"][value="fa-user"]').checked = true;
    openModal('modalPerfil');
}

function editarPerfil(perfil) {
    document.getElementById('modalPerfilTitulo').textContent = 'Editar Perfil';
    document.getElementById('perfil_id').value = perfil.id;
    document.getElementById('perfil_nome').value = perfil.nome;
    document.getElementById('perfil_tipo').value = perfil.tipo;
    
    const corRadio = document.querySelector(`input[name="perfil_cor"][value="${perfil.cor}"]`);
    if (corRadio) corRadio.checked = true;
    
    const iconeRadio = document.querySelector(`input[name="perfil_icone"][value="${perfil.icone}"]`);
    if (iconeRadio) iconeRadio.checked = true;
    
    openModal('modalPerfil');
}

function setVisualizacao(modo) {
    if (modo === 'individual') {
        window.location.href = 'api/perfis.php?acao=desativar_consolidado';
    }
}
</script>

</main>
<script src="assets/js/app.js"></script>
</body>
</html>
