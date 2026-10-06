<?php
/**
 * =====================================================
 * FinnMestre - Configurações
 * =====================================================
 */
require_once 'includes/header.php';

$mensagem = '';
$tipoMensagem = '';

// Processar formulário de limite
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['limite_mensal'])) {
    // Remove pontos de milhares e troca virgula por ponto
    $limiteStr = $_POST['limite_mensal'];
    $limiteStr = str_replace('.', '', $limiteStr); // Remove milhares
    $limiteStr = str_replace(',', '.', $limiteStr); // Troca virgula por ponto
    $limite = floatval($limiteStr);
    if (salvarLimiteMensal($limite)) {
        $mensagem = 'Limite mensal atualizado com sucesso!';
        $tipoMensagem = 'success';
    } else {
        $mensagem = 'Erro ao atualizar limite mensal.';
        $tipoMensagem = 'danger';
    }
}

// Processar configurações gerais
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['config_finbot'])) {
    salvarConfiguracao('finbot_ativo', isset($_POST['finbot_ativo']), 'boolean');
    salvarConfiguracao('alertas_ativos', isset($_POST['alertas_ativos']), 'boolean');
    $mensagem = 'Configurações salvas com sucesso!';
    $tipoMensagem = 'success';
}

// Processar WhatsApp
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['config_whatsapp'])) {
    $telefone_whatsapp = trim($_POST['telefone_whatsapp'] ?? '');
    // Clean up to keep only numbers
    $telefone_whatsapp = preg_replace('/[^0-9]/', '', $telefone_whatsapp);
    
    global $pdo;
    $usuario_id = $_SESSION['usuario_id'] ?? null;
    if ($usuario_id && $pdo) {
        try {
            $stmt = $pdo->prepare("UPDATE usuarios SET telefone_whatsapp = ? WHERE id = ?");
            if ($stmt->execute([$telefone_whatsapp, $usuario_id])) {
                $mensagem = 'Número de WhatsApp salvo com sucesso! O FinBot agora pode receber suas mensagens.';
                $tipoMensagem = 'success';
            } else {
                $mensagem = 'Erro ao salvar o número do WhatsApp.';
                $tipoMensagem = 'danger';
            }
        } catch (PDOException $e) {
            $mensagem = 'A coluna do WhatsApp ainda não existe no banco. Execute a migração SQL primeiro.';
            $tipoMensagem = 'danger';
        }
    }
}
// Processar foto de perfil removido

// Processar formulário de categoria
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['categoria_nome'])) {
    $dados = [
        'id' => !empty($_POST['categoria_id']) ? intval($_POST['categoria_id']) : null,
        'nome' => trim($_POST['categoria_nome']),
        'tipo' => $_POST['categoria_tipo'],
        'cor' => $_POST['categoria_cor'],
        'icone' => $_POST['categoria_icone'] ?? 'fa-tag'
    ];

    if (salvarCategoria($dados)) {
        $mensagem = 'Categoria salva com sucesso!';
        $tipoMensagem = 'success';
    } else {
        $mensagem = 'Erro ao salvar categoria.';
        $tipoMensagem = 'danger';
    }
}

// Excluir categoria
if (isset($_GET['excluir_categoria'])) {
    if (excluirCategoria(intval($_GET['excluir_categoria']))) {
        $mensagem = 'Categoria excluída com sucesso!';
        $tipoMensagem = 'success';
    } else {
        $mensagem = 'Erro ao excluir categoria.';
        $tipoMensagem = 'danger';
    }
}

// Processar configurações comportamentais
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['config_behavioral'])) {
    // Remove pontos de milhares e troca virgula por ponto
    $salarioStr = $_POST['salario_mensal'] ?? '0';
    $salarioStr = str_replace('.', '', $salarioStr); // Remove milhares
    $salarioStr = str_replace(',', '.', $salarioStr); // Troca virgula por ponto
    $salario = floatval($salarioStr);
    $horasTrabalho = intval($_POST['horas_trabalho_mes'] ?? 176);
    if (salvarConfiguracoesBehavioral($salario, $horasTrabalho)) {
        $mensagem = 'Configurações financeiras pessoais salvas com sucesso!';
        $tipoMensagem = 'success';
    } else {
        $mensagem = 'Erro ao salvar configurações.';
        $tipoMensagem = 'danger';
    }
}

// Processar configurações do Assistente IA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['config_ia'])) {
    salvarConfiguracao('ia_ativo', isset($_POST['ia_ativo']), 'boolean');
    
    if (!empty($_POST['ia_provedor'])) {
        salvarConfiguracao('ia_provedor', $_POST['ia_provedor'], 'string');
    }
    if (!empty($_POST['ia_modelo'])) {
        salvarConfiguracao('ia_modelo', trim($_POST['ia_modelo']), 'string');
    }
    // Só atualizar API key se o campo não estiver vazio (permite manter a key atual)
    if (!empty($_POST['ia_api_key']) && $_POST['ia_api_key'] !== '••••••••••••••••') {
        // Armazenar a key (em produção, usar criptografia)
        salvarConfiguracao('ia_api_key', trim($_POST['ia_api_key']), 'string');
    }
    $mensagem = 'Configurações do Assistente IA salvas com sucesso!';
    $tipoMensagem = 'success';
}

$limiteMensal = obterLimiteMensal();
$categoriasEntrada = listarCategorias('entrada');
$categoriasSaida = listarCategorias('saida');
$finbotAtivo = obterConfiguracao('finbot_ativo', true);
$alertasAtivos = obterConfiguracao('alertas_ativos', true);
$salarioAtual = obterSalarioUsuario();
$horasTrabalhoAtual = obterHorasTrabalhoUsuario();

// Obter configurações de idioma e moeda
$idiomaAtual = getIdiomaAtual();
$moedaAtualCodigo = getCodigoMoeda();
$idiomasDisponiveis = getIdiomasDisponiveis();
$moedasDisponiveis = getMoedasDisponiveis();

// Processar mudança de idioma/moeda
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['config_regional'])) {
    if (!empty($_POST['idioma'])) {
        setIdioma($_POST['idioma']);
        $idiomaAtual = $_POST['idioma'];
    }
    if (!empty($_POST['moeda'])) {
        setMoeda($_POST['moeda']);
        $moedaAtualCodigo = $_POST['moeda'];
    }
    $mensagem = 'Configurações regionais atualizadas!';
    $tipoMensagem = 'success';
}

// Ícones disponíveis para categorias
$iconesDisponiveis = [
    'fa-tag' => 'Tag',
    'fa-home' => 'Casa',
    'fa-car' => 'Carro',
    'fa-utensils' => 'Alimentação',
    'fa-shopping-cart' => 'Compras',
    'fa-heartbeat' => 'Saúde',
    'fa-graduation-cap' => 'Educação',
    'fa-gamepad' => 'Lazer',
    'fa-tshirt' => 'Roupas',
    'fa-file-invoice-dollar' => 'Contas',
    'fa-tv' => 'Assinaturas',
    'fa-paw' => 'Pets',
    'fa-gift' => 'Presentes',
    'fa-money-bill-wave' => 'Dinheiro',
    'fa-chart-line' => 'Investimentos',
    'fa-laptop-code' => 'Freelance',
    'fa-plane' => 'Viagem',
    'fa-spa' => 'Beleza'
];

// Get current WhatsApp number
$telefone_whatsapp_atual = '';
if (isset($_SESSION['usuario_id'])) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT telefone_whatsapp FROM usuarios WHERE id = ?");
        $stmt->execute([$_SESSION['usuario_id']]);
        $telefone_whatsapp_atual = $stmt->fetchColumn() ?: '';
    } catch (PDOException $e) {
        // Coluna telefone_whatsapp pode não existir ainda - ignorar
        $telefone_whatsapp_atual = '';
    }
}
?>

<div class="page-header">
    <h1><i class="fas fa-cog" style="color: var(--fm-primary);"></i> <?= __('config_gerais') ?></h1>
    <p><?= __('personalize_sistema') ?></p>
</div>

<?php if ($mensagem): ?>
    <div class="alert alert-<?= $tipoMensagem ?>">
        <i class="fas fa-<?= $tipoMensagem === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
        <?= $mensagem ?>
    </div>
<?php endif; ?>

<!-- Limite Mensal -->
<?php $perfilAtual = function_exists('obterPerfilAtual') ? obterPerfilAtual() : null; ?>
<div class="form-card animate-in">
    <h3><i class="fas fa-flag"></i> <?= __('limite_mensal') ?>
        <?php if ($perfilAtual): ?>
            <span style="font-size: 0.8rem; font-weight: 400; color: var(--text-muted); margin-left: 8px;">
                (Perfil: <strong
                    style="color: <?= $perfilAtual['cor'] ?? 'var(--fm-primary)' ?>;"><?= htmlspecialchars($perfilAtual['nome']) ?></strong>)
            </span>
        <?php endif; ?>
    </h3>
    <p style="color: var(--text-secondary); margin-bottom: 20px;">
        Cada perfil pode ter seu próprio limite mensal de gastos
    </p>
    <form method="POST">
        <div class="form-grid" style="grid-template-columns: 300px auto;">
            <div class="form-group" style="margin-bottom: 0;">
                <label for="limite_mensal"><?= __('valor') ?></label>
                <input type="text" id="limite_mensal" name="limite_mensal" class="form-control input-moeda"
                    value="<?= $limiteMensal > 0 ? number_format($limiteMensal, 2, ',', '.') : '' ?>"
                    placeholder="Ex: 3.000,00">
            </div>
            <div class="form-group" style="margin-bottom: 0; display: flex; align-items: flex-end;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?= __('salvar') ?>
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Dados Financeiros Pessoais (Comportamental) -->
<div class="form-card animate-in" style="animation-delay: 0.05s;">
    <h3><i class="fas fa-brain"></i> Dados Financeiros Pessoais</h3>
    <p style="color: var(--text-secondary); margin-bottom: 20px;">
        Usamos esses dados para calcular o impacto real dos seus gastos em tempo de vida.
    </p>
    <form method="POST">
        <input type="hidden" name="config_behavioral" value="1">
        <div class="form-grid" style="grid-template-columns: 1fr 1fr;">
            <div class="form-group">
                <label for="salario_mensal">
                    <i class="fas fa-money-bill-wave"></i> Salário Mensal Líquido
                </label>
                <input type="text" id="salario_mensal" name="salario_mensal" class="form-control input-moeda"
                    value="<?= $salarioAtual > 0 ? number_format($salarioAtual, 2, ',', '.') : '' ?>"
                    placeholder="Ex: 3.000,00">
                <small style="color: var(--text-muted);">Valor que você recebe por mês</small>
            </div>
            <div class="form-group">
                <label for="horas_trabalho_mes">
                    <i class="fas fa-clock"></i> Horas Trabalhadas por Mês
                </label>
                <input type="number" id="horas_trabalho_mes" name="horas_trabalho_mes" class="form-control"
                    value="<?= $horasTrabalhoAtual ?>" step="1" min="1" max="400" placeholder="Ex: 176">
                <small style="color: var(--text-muted);">Padrão: 176h (44h/semana)</small>
            </div>
        </div>
        <div
            style="background: var(--bg-glass-light); padding: 16px; border-radius: var(--radius-md); margin-top: 16px; margin-bottom: 16px;">
            <p style="color: var(--text-secondary); font-size: 0.9rem; margin: 0;">
                <i class="fas fa-info-circle" style="color: var(--fm-primary);"></i>
                <strong>Por que isso importa?</strong> Com esses dados, mostramos quanto tempo de trabalho cada compra
                representa.
                Assim você pensa duas vezes antes de gastar.
            </p>
        </div>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Salvar Dados Pessoais
        </button>
    </form>
</div>

<!-- Configurações Gerais -->
<div class="form-card animate-in" style="animation-delay: 0.1s;">
    <h3><i class="fas fa-sliders-h"></i> <?= __('preferencias') ?></h3>
    <form method="POST">
        <input type="hidden" name="config_finbot" value="1">

        <div style="display: grid; gap: 16px; margin-top: 20px;">
            <label
                style="display: flex; align-items: center; gap: 14px; padding: 16px; background: var(--bg-glass-light); border-radius: var(--radius-md); cursor: pointer;">
                <input type="checkbox" name="finbot_ativo" <?= $finbotAtivo ? 'checked' : '' ?>
                    style="width: 20px; height: 20px; accent-color: var(--fm-primary);">
                <div>
                    <strong>🤖 FinBot Ativo</strong>
                    <p style="color: var(--text-secondary); font-size: 0.9rem; margin-top: 4px;">
                        Mostrar mascote com dicas e mensagens inteligentes
                    </p>
                </div>
            </label>

            <label
                style="display: flex; align-items: center; gap: 14px; padding: 16px; background: var(--bg-glass-light); border-radius: var(--radius-md); cursor: pointer;">
                <input type="checkbox" name="alertas_ativos" <?= $alertasAtivos ? 'checked' : '' ?>
                    style="width: 20px; height: 20px; accent-color: var(--fm-primary);">
                <div>
                    <strong>🔔 Alertas Inteligentes</strong>
                    <p style="color: var(--text-secondary); font-size: 0.9rem; margin-top: 4px;">
                        Receber notificações sobre limite de gastos, metas e economia
                    </p>
                </div>
            </label>
        </div>

        <button type="submit" class="btn btn-primary" style="margin-top: 20px;">
            <i class="fas fa-save"></i> <?= __('salvar') ?>
        </button>
    </form>
</div>

<!-- Assistente IA -->
<?php
$iaAtivo = obterConfiguracao('ia_ativo', true);
$iaProvedor = obterConfiguracao('ia_provedor', 'gemini');
$iaModelo = obterConfiguracao('ia_modelo', 'gemini-2.0-flash');
$iaTemKey = !empty(obterConfiguracao('ia_api_key', ''));
?>
<div class="form-card animate-in" style="animation-delay: 0.12s;">
    <h3><i class="fas fa-robot" style="color: #8b5cf6;"></i> Assistente IA</h3>
    <p style="color: var(--text-secondary); margin-bottom: 20px;">
        Configure o assistente financeiro com inteligência artificial para análises personalizadas, sugestões de economia e planos financeiros.
    </p>
    <form method="POST">
        <input type="hidden" name="config_ia" value="1">

        <div style="display: grid; gap: 16px; margin-bottom: 20px;">
            <label style="display: flex; align-items: center; gap: 14px; padding: 16px; background: var(--bg-glass-light); border-radius: var(--radius-md); cursor: pointer;">
                <input type="checkbox" name="ia_ativo" <?= $iaAtivo ? 'checked' : '' ?>
                    style="width: 20px; height: 20px; accent-color: #8b5cf6;">
                <div>
                    <strong>🤖 Ativar Assistente IA</strong>
                    <p style="color: var(--text-secondary); font-size: 0.9rem; margin-top: 4px;">
                        Habilitar chat com IA, análises automáticas e sugestões inteligentes
                    </p>
                </div>
            </label>
        </div>

        <div class="form-grid" style="grid-template-columns: 1fr 1fr;">
            <div class="form-group">
                <label><i class="fas fa-cloud"></i> Provedor de IA</label>
                <select name="ia_provedor" class="form-control">
                    <option value="gemini" <?= $iaProvedor === 'gemini' ? 'selected' : '' ?>>Google Gemini</option>
                    <option value="openai" <?= $iaProvedor === 'openai' ? 'selected' : '' ?>>OpenAI (ChatGPT)</option>
                </select>
                <small style="color: var(--text-muted);">Gemini tem plano gratuito generoso</small>
            </div>
            <div class="form-group">
                <label><i class="fas fa-microchip"></i> Modelo</label>
                <input type="text" name="ia_modelo" class="form-control"
                    value="<?= htmlspecialchars($iaModelo) ?>"
                    placeholder="Ex: gemini-2.0-flash">
                <small style="color: var(--text-muted);">Modelo padrão recomendado: gemini-2.0-flash</small>
            </div>
        </div>

        <div class="form-group">
            <label><i class="fas fa-key"></i> Chave da API</label>
            <div style="position: relative;">
                <input type="password" name="ia_api_key" id="ia_api_key" class="form-control"
                    value="<?= $iaTemKey ? '••••••••••••••••' : '' ?>"
                    placeholder="Cole aqui sua API Key"
                    style="padding-right: 40px;">
                <button type="button" onclick="toggleApiKeyVisibility()" 
                    style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-muted); cursor: pointer;">
                    <i class="fas fa-eye" id="toggleKeyIcon"></i>
                </button>
            </div>
            <small style="color: var(--text-muted);">
                <?php if ($iaTemKey): ?>
                    <i class="fas fa-check-circle" style="color: #10b981;"></i> Chave configurada. Deixe em branco para manter a atual.
                <?php else: ?>
                    <i class="fas fa-info-circle" style="color: #f59e0b;"></i> 
                    Obtenha uma chave gratuita em <a href="https://aistudio.google.com/apikey" target="_blank" style="color: #8b5cf6;">Google AI Studio</a>
                <?php endif; ?>
            </small>
        </div>

        <div style="background: rgba(139,92,246,0.08); border: 1px solid rgba(139,92,246,0.15); border-radius: var(--radius-md); padding: 14px; margin-bottom: 20px;">
            <p style="color: var(--text-secondary); font-size: 0.85rem; margin: 0;">
                <i class="fas fa-shield-alt" style="color: #8b5cf6;"></i>
                <strong>Segurança:</strong> Sua chave de API é armazenada no servidor e nunca é enviada ao navegador. A IA analisa apenas dados resumidos e nunca acessa seu banco de dados diretamente.
            </p>
        </div>

        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Salvar Configurações IA
        </button>
    </form>
</div>

<script>
function toggleApiKeyVisibility() {
    const input = document.getElementById('ia_api_key');
    const icon = document.getElementById('toggleKeyIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fas fa-eye';
    }
}
</script>

<!-- Configurações Regionais -->
<div class="form-card animate-in" style="animation-delay: 0.15s;">
    <h3><i class="fab fa-whatsapp" style="color: #25D366;"></i> Assistente IA do WhatsApp</h3>
    <p style="color: var(--text-secondary); margin-bottom: 20px;">
        Cadastre seu número de WhatsApp com DDD para que a Inteligência Artificial identifique sua conta quando você enviar áudios, textos ou fotos de gastos.
    </p>
    <form method="POST">
        <input type="hidden" name="config_whatsapp" value="1">
        
        <div class="form-grid" style="grid-template-columns: 300px auto;">
            <div class="form-group" style="margin-bottom: 0;">
                <label for="telefone_whatsapp">Seu Número do WhatsApp</label>
                <input type="text" id="telefone_whatsapp" name="telefone_whatsapp" class="form-control"
                    value="<?= htmlspecialchars($telefone_whatsapp_atual) ?>"
                    placeholder="Ex: 5511999999999" required>
                <small style="color: var(--text-muted);">Apenas números (Ex: 55 DDD NÚMERO)</small>
            </div>
            <div class="form-group" style="margin-bottom: 0; display: flex; align-items: flex-end;">
                <button type="submit" class="btn btn-primary" style="background-color: #25D366; border-color: #25D366; color: #fff;">
                    <i class="fas fa-save"></i> Ativar Integração
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Configurações Regionais -->
<div class="form-card animate-in" style="animation-delay: 0.15s;">
    <h3><i class="fas fa-globe"></i> Idioma e Moeda</h3>
    <p style="color: var(--text-secondary); margin-bottom: 20px;">
        Personalize o idioma e a moeda do sistema
    </p>
    <form method="POST">
        <input type="hidden" name="config_regional" value="1">

        <div class="form-grid" style="grid-template-columns: 1fr 1fr;">
            <!-- Idioma -->
            <div class="form-group">
                <label for="idioma"><i class="fas fa-language"></i> Idioma</label>
                <select name="idioma" id="idioma" class="form-control">
                    <?php foreach ($idiomasDisponiveis as $codigo => $info): ?>
                        <option value="<?= $codigo ?>" <?= $idiomaAtual === $codigo ? 'selected' : '' ?>>
                            <?= $info['bandeira'] ?>     <?= $info['nome'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Moeda -->
            <div class="form-group">
                <label for="moeda"><i class="fas fa-coins"></i> Moeda</label>
                <select name="moeda" id="moeda" class="form-control">
                    <?php foreach ($moedasDisponiveis as $codigo => $info): ?>
                        <option value="<?= $codigo ?>" <?= $moedaAtualCodigo === $codigo ? 'selected' : '' ?>>
                            <?= $info['simbolo'] ?> - <?= $info['nome'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <button type="submit" class="btn btn-primary" style="margin-top: 10px;">
            <i class="fas fa-save"></i> Salvar Configurações Regionais
        </button>
    </form>
</div>

<!-- Categorias -->
<div class="form-card animate-in" style="animation-delay: 0.2s;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h3><i class="fas fa-tags"></i> Categorias
            <?php if ($perfilAtual): ?>
                <span style="font-size: 0.8rem; font-weight: 400; color: var(--text-muted); margin-left: 8px;">
                    (Perfil: <strong style="color: <?= $perfilAtual['cor'] ?? 'var(--fm-primary)' ?>;"><?= htmlspecialchars($perfilAtual['nome']) ?></strong>)
                </span>
            <?php endif; ?>
        </h3>
        <button onclick="novaCategoria()" class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i> Nova Categoria
        </button>
    </div>

    <div class="charts-grid" style="margin-bottom: 0;">
        <!-- Categorias de Entrada -->
        <div class="table-card" style="margin-bottom: 0;">
            <div class="table-header"
                style="border-left: 4px solid var(--success); margin-left: -1px; padding-left: 20px;">
                <h3 style="color: var(--success);"><i class="fas fa-arrow-up"></i> Categorias de Entrada</h3>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 50px;">Cor</th>
                            <th>Nome</th>
                            <th style="width: 80px;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categoriasEntrada as $cat): ?>
                            <tr>
                                <td>
                                    <div
                                        style="width: 24px; height: 24px; border-radius: 6px; background: <?= $cat['cor'] ?>; display: flex; align-items: center; justify-content: center; color: white; font-size: 0.7rem;">
                                        <i class="fas <?= $cat['icone'] ?>"></i>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($cat['nome']) ?></td>
                                <td>
                                    <div class="btn-group">
                                        <button onclick='editarCategoria(<?= htmlspecialchars(json_encode($cat)) ?>)'
                                            class="btn btn-icon btn-ghost btn-sm" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="excluirCategoria(<?= $cat['id'] ?>)"
                                            class="btn btn-icon btn-ghost btn-sm" style="color: var(--danger);"
                                            title="Excluir">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Categorias de Saída -->
        <div class="table-card" style="margin-bottom: 0;">
            <div class="table-header"
                style="border-left: 4px solid var(--danger); margin-left: -1px; padding-left: 20px;">
                <h3 style="color: var(--danger);"><i class="fas fa-arrow-down"></i> Categorias de Saída</h3>
            </div>
            <div class="table-wrapper" style="max-height: 400px; overflow-y: auto;">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 50px;">Cor</th>
                            <th>Nome</th>
                            <th style="width: 80px;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categoriasSaida as $cat): ?>
                            <tr>
                                <td>
                                    <div
                                        style="width: 24px; height: 24px; border-radius: 6px; background: <?= $cat['cor'] ?>; display: flex; align-items: center; justify-content: center; color: white; font-size: 0.7rem;">
                                        <i class="fas <?= $cat['icone'] ?>"></i>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($cat['nome']) ?></td>
                                <td>
                                    <div class="btn-group">
                                        <button onclick='editarCategoria(<?= htmlspecialchars(json_encode($cat)) ?>)'
                                            class="btn btn-icon btn-ghost btn-sm" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="excluirCategoria(<?= $cat['id'] ?>)"
                                            class="btn btn-icon btn-ghost btn-sm" style="color: var(--danger);"
                                            title="Excluir">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Sobre o Sistema -->
<div class="form-card animate-in" style="animation-delay: 0.3s;">
    <h3><i class="fas fa-info-circle"></i> Sobre o FinnMestre</h3>
    <div
        style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 20px;">
        <div style="text-align: center; padding: 20px;">
            <div
                style="width: 60px; height: 60px; background: var(--fm-gradient-primary); border-radius: 16px; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px; font-size: 1.5rem;">
                <i class="fas fa-robot"></i>
            </div>
            <strong>Versão 2.0</strong>
            <p style="color: var(--text-muted); font-size: 0.85rem;">FinnMestre Pro</p>
        </div>
        <div style="text-align: center; padding: 20px;">
            <div
                style="width: 60px; height: 60px; background: rgba(59, 130, 246, 0.2); color: var(--fm-secondary); border-radius: 16px; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px; font-size: 1.5rem;">
                <i class="fas fa-database"></i>
            </div>
            <strong>MySQL</strong>
            <p style="color: var(--text-muted); font-size: 0.85rem;">Banco de Dados</p>
        </div>
        <div style="text-align: center; padding: 20px;">
            <div
                style="width: 60px; height: 60px; background: rgba(139, 92, 246, 0.2); color: var(--fm-accent); border-radius: 16px; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px; font-size: 1.5rem;">
                <i class="fas fa-code"></i>
            </div>
            <strong>PHP 8+</strong>
            <p style="color: var(--text-muted); font-size: 0.85rem;">Backend</p>
        </div>
    </div>
</div>

<!-- Modal Categoria -->
<div id="modalCategoria" class="modal-overlay">
    <div class="modal" style="max-width: 450px;">
        <div class="modal-header">
            <h3><i class="fas fa-tag"></i> <span id="modalCategoriaTitulo">Nova Categoria</span></h3>
            <button class="modal-close" onclick="closeModal('modalCategoria')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" id="categoria_id" name="categoria_id">

                <div class="form-group">
                    <label class="required">Nome</label>
                    <input type="text" id="categoria_nome" name="categoria_nome" class="form-control" required
                        placeholder="Nome da categoria">
                </div>

                <div class="form-group">
                    <label class="required">Tipo</label>
                    <select id="categoria_tipo" name="categoria_tipo" class="form-control" required>
                        <option value="entrada">📈 Entrada</option>
                        <option value="saida">📉 Saída</option>
                    </select>
                </div>

                <div class="form-grid" style="grid-template-columns: 1fr 1fr;">
                    <div class="form-group">
                        <label>Cor</label>
                        <input type="color" id="categoria_cor" name="categoria_cor" class="form-control" value="#6366f1"
                            style="height: 50px; padding: 5px;">
                    </div>
                    <div class="form-group">
                        <label>Ícone</label>
                        <select id="categoria_icone" name="categoria_icone" class="form-control">
                            <?php foreach ($iconesDisponiveis as $classe => $nome): ?>
                                <option value="<?= $classe ?>"><?= $nome ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalCategoria')">Cancelar</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Salvar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function novaCategoria() {
        document.getElementById('modalCategoriaTitulo').textContent = 'Nova Categoria';
        document.getElementById('categoria_id').value = '';
        document.getElementById('categoria_nome').value = '';
        document.getElementById('categoria_tipo').value = 'saida';
        document.getElementById('categoria_cor').value = '#6366f1';
        document.getElementById('categoria_icone').value = 'fa-tag';
        openModal('modalCategoria');
    }

    function editarCategoria(cat) {
        document.getElementById('modalCategoriaTitulo').textContent = 'Editar Categoria';
        document.getElementById('categoria_id').value = cat.id;
        document.getElementById('categoria_nome').value = cat.nome;
        document.getElementById('categoria_tipo').value = cat.tipo;
        document.getElementById('categoria_cor').value = cat.cor || '#6366f1';
        document.getElementById('categoria_icone').value = cat.icone || 'fa-tag';
        openModal('modalCategoria');
    }

    // Inicializar input monetário no salário
    document.addEventListener('DOMContentLoaded', function () {
        const inputSalario = document.getElementById('salario_mensal');
        if (inputSalario && typeof inicializarInputMonetario === 'function') {
            inicializarInputMonetario(inputSalario);
        }
    });

    let idCategoriaExcluir = null;

    function excluirCategoria(id) {
        idCategoriaExcluir = id;
        document.getElementById('modalExcluirCategoria').classList.add('active');
    }

    function fecharModalExcluirCategoria() {
        idCategoriaExcluir = null;
        document.getElementById('modalExcluirCategoria').classList.remove('active');
    }

    function confirmarExclusaoCategoria() {
        if (!idCategoriaExcluir) return;
        
        const btn = document.getElementById('btnConfirmarExclusaoCategoria');
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Excluindo...';
        btn.disabled = true;

        window.location.href = '?excluir_categoria=' + idCategoriaExcluir;
    }
</script>

<!-- Modal de Confirmação de Exclusão -->
<div id="modalExcluirCategoria" class="modal-overlay">
    <div class="modal-content" style="max-width: 400px; text-align: center; padding: 30px;">
        <div style="font-size: 3rem; color: var(--danger); margin-bottom: 15px;">
            <i class="fas fa-trash-alt"></i>
        </div>
        <h3 style="margin-bottom: 10px;">Excluir Categoria</h3>
        <p style="color: var(--text-secondary); margin-bottom: 25px;">
            Tem certeza que deseja excluir esta categoria? Esta ação não pode ser desfeita e pode afetar transações existentes.
        </p>
        <div style="display: flex; gap: 10px; justify-content: center;">
            <button onclick="fecharModalExcluirCategoria()" class="btn btn-ghost" style="flex: 1;">Cancelar</button>
            <button id="btnConfirmarExclusaoCategoria" onclick="confirmarExclusaoCategoria()" class="btn btn-danger" style="flex: 1;">Excluir</button>
        </div>
    </div>
</div>


</main>
<script src="assets/js/app.js"></script>
</body>

</html>