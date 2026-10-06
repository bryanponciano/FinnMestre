<?php
/**
 * =====================================================
 * FinnMestre - Painel Administrativo
 * =====================================================
 * Acesso exclusivo para: poncianobryan988@gmail.com
 */
require_once 'includes/auth.php';

// Verificar se é o admin
if (!isset($_SESSION['usuario_email']) || $_SESSION['usuario_email'] !== 'poncianobryan988@gmail.com') {
    header('Location: index.php');
    exit;
}

require_once 'config/database.php';
require_once 'includes/assinaturas.php';

// Gerar Token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$mensagem = '';
$tipoMensagem = '';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('Ação não permitida (Token CSRF inválido).');
    }

    $acao = $_POST['acao'] ?? '';

    try {
        switch ($acao) {
            case 'atualizar_whatsapp':
                $numero = preg_replace('/\D/', '', $_POST['whatsapp_numero']);
                $stmt = $pdo->prepare("INSERT INTO configuracoes (chave, valor, tipo, descricao) 
                    VALUES ('whatsapp_suporte', ?, 'string', 'Número do WhatsApp de suporte') 
                    ON DUPLICATE KEY UPDATE valor = ?");
                $stmt->execute([$numero, $numero]);
                $mensagem = 'WhatsApp atualizado com sucesso!';
                $tipoMensagem = 'success';
                break;

            case 'excluir_usuario':
                $userId = intval($_POST['user_id']);
                if ($userId > 0) {
                    // Não permitir excluir a si mesmo
                    if ($userId != $_SESSION['usuario_id']) {
                        $pdo->prepare("DELETE FROM usuarios WHERE id = ?")->execute([$userId]);
                        $mensagem = 'Usuário excluído!';
                        $tipoMensagem = 'success';
                    } else {
                        $mensagem = 'Você não pode excluir seu próprio usuário!';
                        $tipoMensagem = 'danger';
                    }
                }
                break;

            case 'resetar_senha':
                $userId = intval($_POST['user_id']);
                $novaSenha = $_POST['nova_senha'];
                if ($userId > 0 && !empty($novaSenha)) {
                    $hash = password_hash($novaSenha, PASSWORD_DEFAULT);
                    $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?")->execute([$hash, $userId]);
                    $mensagem = 'Senha resetada com sucesso!';
                    $tipoMensagem = 'success';
                }
                break;

            case 'limpar_transacoes':
                $pdo->exec("DELETE FROM transacoes");
                $mensagem = 'Todas as transações foram excluídas!';
                $tipoMensagem = 'success';
                break;

            case 'limpar_alertas':
                $pdo->exec("DELETE FROM alertas");
                $mensagem = 'Todos os alertas foram excluídos!';
                $tipoMensagem = 'success';
                break;

            case 'criar_usuario':
                $nome = trim($_POST['novo_nome'] ?? '');
                $email = trim($_POST['novo_email'] ?? '');
                $senha = $_POST['novo_senha'] ?? '';
                $plano = $_POST['novo_plano'] ?? 'mensal';

                if (empty($nome) || empty($email) || empty($senha)) {
                    $mensagem = 'Preencha todos os campos!';
                    $tipoMensagem = 'danger';
                } else {
                    // Verificar se email já existe
                    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
                    $stmt->execute([$email]);
                    if ($stmt->fetch()) {
                        $mensagem = 'Este e-mail já está cadastrado!';
                        $tipoMensagem = 'danger';
                    } else {
                        $hash = password_hash($senha, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)");
                        $stmt->execute([$nome, $email, $hash]);
                        $novoUserId = $pdo->lastInsertId();

                        // Criar assinatura manual
                        criarAssinaturaManual($novoUserId, $plano);

                        $mensagem = "Usuário '$nome' criado com plano " . getNomePlano($plano) . '!';
                        $tipoMensagem = 'success';
                    }
                }
                break;

            case 'atribuir_plano':
                $userId = intval($_POST['user_id']);
                $plano = $_POST['plano'] ?? 'mensal';
                if ($userId > 0 && in_array($plano, ['mensal', 'semestral', 'anual'])) {
                    criarAssinaturaManual($userId, $plano);
                    $mensagem = 'Plano atualizado para ' . getNomePlano($plano) . '!';
                    $tipoMensagem = 'success';
                }
                break;
        }
    } catch (PDOException $e) {
        $mensagem = 'Erro: ' . $e->getMessage();
        $tipoMensagem = 'danger';
    }
}

// Obter dados com plano
$usuarios = $pdo->query("
    SELECT u.*, 
           (SELECT a.plano FROM assinaturas a WHERE a.usuario_id = u.id AND a.status = 'active' ORDER BY a.created_at DESC LIMIT 1) as plano_ativo,
           (SELECT a.status FROM assinaturas a WHERE a.usuario_id = u.id ORDER BY a.created_at DESC LIMIT 1) as status_assinatura
    FROM usuarios u ORDER BY u.id
")->fetchAll();
$totalTransacoes = $pdo->query("SELECT COUNT(*) FROM transacoes")->fetchColumn();
$totalContas = $pdo->query("SELECT COUNT(*) FROM contas")->fetchColumn();
$totalMetas = $pdo->query("SELECT COUNT(*) FROM metas")->fetchColumn();
$totalAlertas = $pdo->query("SELECT COUNT(*) FROM alertas")->fetchColumn();

// Configurações
try {
    $whatsappNumero = $pdo->query("SELECT valor FROM configuracoes WHERE chave = 'whatsapp_suporte'")->fetchColumn() ?: '';
} catch (Exception $e) {
    $whatsappNumero = '';
}

require_once 'includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-shield-alt" style="color: #f59e0b;"></i> Painel Administrativo</h1>
    <p>Gerenciamento completo do sistema FinnMestre</p>
</div>

<?php if ($mensagem): ?>
    <div class="alert alert-<?= $tipoMensagem ?>">
        <i class="fas fa-<?= $tipoMensagem === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
        <?= $mensagem ?>
    </div>
<?php endif; ?>

<!-- Estatísticas -->
<div class="summary-cards">
    <div class="card animate-in">
        <div class="card-header">
            <span class="card-title">Usuários</span>
            <div class="card-icon primary">
                <i class="fas fa-users"></i>
            </div>
        </div>
        <div class="card-value primary">
            <?= count($usuarios) ?>
        </div>
        <div class="card-subtitle">cadastrados</div>
    </div>
    <div class="card animate-in" style="animation-delay: 0.1s">
        <div class="card-header">
            <span class="card-title">Transações</span>
            <div class="card-icon success">
                <i class="fas fa-exchange-alt"></i>
            </div>
        </div>
        <div class="card-value success">
            <?= $totalTransacoes ?>
        </div>
        <div class="card-subtitle">registradas</div>
    </div>
    <div class="card animate-in" style="animation-delay: 0.2s">
        <div class="card-header">
            <span class="card-title">Contas</span>
            <div class="card-icon accent">
                <i class="fas fa-wallet"></i>
            </div>
        </div>
        <div class="card-value accent">
            <?= $totalContas ?>
        </div>
        <div class="card-subtitle">ativas</div>
    </div>
    <div class="card animate-in" style="animation-delay: 0.3s">
        <div class="card-header">
            <span class="card-title">Metas</span>
            <div class="card-icon warning">
                <i class="fas fa-bullseye"></i>
            </div>
        </div>
        <div class="card-value warning">
            <?= $totalMetas ?>
        </div>
        <div class="card-subtitle">definidas</div>
    </div>
</div>

<!-- Configurações Rápidas -->
<div class="form-card animate-in">
    <h3><i class="fas fa-cogs"></i> Configurações Rápidas</h3>

    <div class="form-grid" style="margin-top: 20px;">
        <!-- WhatsApp -->
        <form method="POST" style="display: flex; gap: 12px; align-items: flex-end;">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="acao" value="atualizar_whatsapp">
            <div class="form-group" style="flex: 1; margin-bottom: 0;">
                <label><i class="fab fa-whatsapp" style="color: #25d366;"></i> WhatsApp Suporte</label>
                <input type="text" name="whatsapp_numero" class="form-control"
                    value="<?= htmlspecialchars($whatsappNumero) ?>" placeholder="5511999999999">
            </div>
            <button type="submit" class="btn btn-success">
                <i class="fas fa-save"></i> Salvar
            </button>
        </form>
    </div>
</div>

<!-- Criar Usuário -->
<div class="form-card animate-in" style="margin-bottom: 24px;">
    <h3><i class="fas fa-user-plus"></i> Criar Novo Usuário</h3>
    <form method="POST" style="margin-top: 20px;">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="acao" value="criar_usuario">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div class="form-group" style="margin-bottom: 0;">
                <label><i class="fas fa-user"></i> Nome</label>
                <input type="text" name="novo_nome" class="form-control" placeholder="Nome completo" required>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label><i class="fas fa-envelope"></i> E-mail</label>
                <input type="email" name="novo_email" class="form-control" placeholder="email@exemplo.com" required>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label><i class="fas fa-lock"></i> Senha</label>
                <input type="text" name="novo_senha" class="form-control" placeholder="Senha do usuário" required>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label><i class="fas fa-crown"></i> Plano</label>
                <select name="novo_plano" class="form-control">
                    <option value="mensal">Mensal</option>
                    <option value="semestral">Semestral</option>
                    <option value="anual" selected>Anual</option>
                </select>
            </div>
        </div>
        <button type="submit" class="btn btn-primary" style="margin-top: 16px;">
            <i class="fas fa-plus"></i> Criar Usuário
        </button>
    </form>
</div>

<!-- Gestão de Usuários -->
<div class="table-card animate-in">
    <div class="table-header">
        <h3><i class="fas fa-users-cog"></i> Gestão de Usuários</h3>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Plano</th>
                    <th>Cadastro</th>
                    <th>Último Acesso</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $u): ?>
                    <tr>
                        <td>#<?= $u['id'] ?></td>
                        <td>
                            <strong><?= htmlspecialchars($u['nome']) ?></strong>
                            <?php if ($u['email'] === ADMIN_EMAIL): ?>
                                <span class="badge" style="background: #f59e0b; color: white; font-size: 0.7rem;">ADMIN</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td>
                            <?php if ($u['email'] === ADMIN_EMAIL): ?>
                                <span class="badge" style="background: #f59e0b; color: white; font-size: 0.75rem;">Acesso Total</span>
                            <?php elseif ($u['plano_ativo']): ?>
                                <?php
                                $corPlano = ['mensal' => '#3b82f6', 'semestral' => '#8b5cf6', 'anual' => '#06d6a0'];
                                $cor = $corPlano[$u['plano_ativo']] ?? '#6b7280';
                                ?>
                                <span class="badge" style="background: <?= $cor ?>; color: white; font-size: 0.75rem;">
                                    <?= getNomePlano($u['plano_ativo']) ?>
                                </span>
                            <?php else: ?>
                                <span class="badge" style="background: var(--danger); color: white; font-size: 0.75rem;">Sem plano</span>
                            <?php endif; ?>
                        </td>
                        <td><?= formatarData($u['created_at']) ?></td>
                        <td><?= $u['ultimo_acesso'] ? formatarData($u['ultimo_acesso']) : '-' ?></td>
                        <td>
                            <?php if ($u['email'] !== ADMIN_EMAIL): ?>
                                <button onclick="atribuirPlano(<?= $u['id'] ?>, '<?= htmlspecialchars($u['nome']) ?>', '<?= $u['plano_ativo'] ?? 'mensal' ?>')"
                                    class="btn btn-icon btn-ghost btn-sm" title="Alterar Plano" style="color: var(--fm-primary);">
                                    <i class="fas fa-crown"></i>
                                </button>
                                <button onclick="resetarSenha(<?= $u['id'] ?>, '<?= htmlspecialchars($u['nome']) ?>')"
                                    class="btn btn-icon btn-ghost btn-sm" title="Resetar Senha">
                                    <i class="fas fa-key"></i>
                                </button>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Excluir este usuário?')">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="acao" value="excluir_usuario">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <button type="submit" class="btn btn-icon btn-ghost btn-sm" style="color: var(--danger);" title="Excluir">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            <?php else: ?>
                                <span style="color: var(--text-muted);">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Ações de Manutenção -->
<div class="form-card animate-in" style="border: 1px solid rgba(239, 68, 68, 0.3);">
    <h3 style="color: var(--danger);"><i class="fas fa-exclamation-triangle"></i> Zona de Perigo</h3>
    <p style="color: var(--text-secondary); margin: 16px 0;">
        Ações irreversíveis. Use com cuidado!
    </p>

    <div style="display: flex; gap: 12px; flex-wrap: wrap;">
        <form method="POST" onsubmit="return confirm('ATENÇÃO: Isso vai excluir TODAS as transações. Continuar?')">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="acao" value="limpar_transacoes">
            <button type="submit" class="btn" style="background: var(--danger); color: white;">
                <i class="fas fa-trash"></i> Limpar Transações (
                <?= $totalTransacoes ?>)
            </button>
        </form>

        <form method="POST" onsubmit="return confirm('Excluir todos os alertas?')">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="acao" value="limpar_alertas">
            <button type="submit" class="btn" style="background: var(--danger); color: white;">
                <i class="fas fa-bell-slash"></i> Limpar Alertas (
                <?= $totalAlertas ?>)
            </button>
        </form>
    </div>
</div>

<!-- Modal Resetar Senha -->
<div id="modalResetSenha" class="modal-overlay">
    <div class="modal" style="max-width: 400px;">
        <div class="modal-header">
            <h3><i class="fas fa-key"></i> Resetar Senha</h3>
            <button class="modal-close" onclick="closeModal('modalResetSenha')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="acao" value="resetar_senha">
            <input type="hidden" name="user_id" id="reset_user_id">
            <div class="modal-body">
                <p style="margin-bottom: 20px;">Resetando senha de: <strong id="reset_user_nome"></strong></p>
                <div class="form-group">
                    <label class="required">Nova Senha</label>
                    <input type="text" name="nova_senha" id="nova_senha" class="form-control" required
                        placeholder="Digite a nova senha">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary"
                    onclick="closeModal('modalResetSenha')">Cancelar</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Resetar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Atribuir Plano -->
<div id="modalAtribuirPlano" class="modal-overlay">
    <div class="modal" style="max-width: 400px;">
        <div class="modal-header">
            <h3><i class="fas fa-crown" style="color: var(--fm-primary);"></i> Atribuir Plano</h3>
            <button class="modal-close" onclick="closeModal('modalAtribuirPlano')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="acao" value="atribuir_plano">
            <input type="hidden" name="user_id" id="plano_user_id">
            <div class="modal-body">
                <p style="margin-bottom: 20px;">Alterando plano de: <strong id="plano_user_nome"></strong></p>
                <div class="form-group">
                    <label class="required">Plano</label>
                    <select name="plano" id="plano_select" class="form-control">
                        <option value="mensal">Mensal — Dashboard, até 3 perfis</option>
                        <option value="semestral">Semestral — + Perfis ilimitados, relatórios avançados</option>
                        <option value="anual">Anual — + Exportação, contas programadas</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary"
                    onclick="closeModal('modalAtribuirPlano')">Cancelar</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Salvar Plano
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function resetarSenha(userId, nome) {
        document.getElementById('reset_user_id').value = userId;
        document.getElementById('reset_user_nome').textContent = nome;
        document.getElementById('nova_senha').value = '';
        openModal('modalResetSenha');
    }

    function atribuirPlano(userId, nome, planoAtual) {
        document.getElementById('plano_user_id').value = userId;
        document.getElementById('plano_user_nome').textContent = nome;
        document.getElementById('plano_select').value = planoAtual || 'mensal';
        openModal('modalAtribuirPlano');
    }
</script>

</main>
<script src="assets/js/app.js"></script>
</body>

</html>