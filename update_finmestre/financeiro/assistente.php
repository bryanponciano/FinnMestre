<?php
require_once 'includes/header.php';
?>
<link rel="stylesheet" href="assets/css/assistente.css?v=<?= time() ?>">

<?php
$mes = date('m');
$ano = date('Y');
require_once 'includes/ia_financeiro.php';
$analise = gerarAnaliseAutomatica($_SESSION['perfil_id'] ?? $_SESSION['usuario_id'] ?? 0);
$alertas = gerarAlertasInteligentes($_SESSION['perfil_id'] ?? $_SESSION['usuario_id'] ?? 0);
$resumo = obterResumoMensal($mes, $ano);
$resumoPoupanca = function_exists('obterResumoPoupanca') ? obterResumoPoupanca() : ['saldo_atual' => 0];
$iaAtivo = obterConfiguracao('ia_ativo', true);
$temApiKey = !empty(obterConfiguracao('ia_api_key', ''));

$mensagemProativa = '';
try {
    if (function_exists('gerarMensagemProativaDashboard')) {
        $dadosProativos = gerarMensagemProativaDashboard();
        $mensagemProativa = $dadosProativos['mensagem'] ?? '';
    }
} catch (Exception $e) {
    $mensagemProativa = '';
}
if (empty($mensagemProativa)) {
    // Fallback: generate from analysis
    $mensagemProativa = $analise['mensagem_proativa'] ?? $analise['resumo'] ?? 'Olá! Sou seu assistente financeiro. Posso analisar seus gastos e te ajudar a economizar.';
}
?>

<main class="assistente-container">
    <div class="assistente-grid">
        <!-- Left Column: Insights & Alerts -->
        <div class="assistente-left-col">
            
            <!-- AI Status Card -->
            <div class="assistente-card ai-status-card">
                <div class="status-header">
                    <h3>Status do Assistente IA</h3>
                    <?php if ($iaAtivo && $temApiKey): ?>
                        <div class="status-badge success">
                            <span class="status-dot green"></span> IA Ativa
                        </div>
                    <?php else: ?>
                        <div class="status-badge warning">
                            <span class="status-dot yellow"></span> API não configurada
                        </div>
                        <a href="configuracoes.php" class="btn-config">Configure a API</a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="assistente-summary">
                <div class="summary-card">
                    <div class="card-icon text-success"><i class="fas fa-arrow-up"></i></div>
                    <div class="card-info">
                        <span class="card-label">Receitas</span>
                        <h4 class="card-value text-success">R$ <?= number_format($resumo['total_entradas'] ?? 0, 2, ',', '.') ?></h4>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="card-icon text-danger"><i class="fas fa-arrow-down"></i></div>
                    <div class="card-info">
                        <span class="card-label">Despesas</span>
                        <h4 class="card-value text-danger">R$ <?= number_format($resumo['total_saidas'] ?? 0, 2, ',', '.') ?></h4>
                    </div>
                </div>
                <div class="summary-card">
                    <?php $saldoClass = ($resumo['saldo'] ?? 0) >= 0 ? 'text-success' : 'text-danger'; ?>
                    <div class="card-icon <?= $saldoClass ?>"><i class="fas fa-calculator"></i></div>
                    <div class="card-info">
                        <span class="card-label">Saldo</span>
                        <h4 class="card-value <?= $saldoClass ?>">R$ <?= number_format($resumo['saldo'] ?? 0, 2, ',', '.') ?></h4>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="card-icon text-purple"><i class="fas fa-piggy-bank"></i></div>
                    <div class="card-info">
                        <span class="card-label">Poupança</span>
                        <h4 class="card-value text-purple">R$ <?= number_format($resumoPoupanca['saldo_atual'] ?? 0, 2, ',', '.') ?></h4>
                    </div>
                </div>
            </div>

            <!-- Quick Analysis Card -->
            <div class="assistente-card analysis-card">
                <h3><i class="fas fa-lightbulb text-warning"></i> Diagnóstico Financeiro</h3>
                <div class="analysis-content">
                    <p class="analysis-resumo"><?= nl2br(htmlspecialchars($analise['mensagem_proativa'] ?? $analise['resumo'] ?? 'Análise não disponível.')) ?></p>
                    
                    <?php if (!empty($analise['top_gastos_redutiveis'])): ?>
                    <div style="margin-top: 12px;">
                        <strong style="color: #f59e0b;">📊 Maiores oportunidades de economia:</strong>
                        <div style="margin-top: 8px; display: flex; flex-direction: column; gap: 6px;">
                            <?php foreach (array_slice($analise['top_gastos_redutiveis'], 0, 3) as $gasto): ?>
                            <div style="display: flex; justify-content: space-between; padding: 8px 12px; background: rgba(0,0,0,0.2); border-radius: 8px;">
                                <span><?= htmlspecialchars($gasto['categoria']) ?></span>
                                <span style="color: #ef4444; font-weight: 600;">R$ <?= number_format($gasto['valor_atual'], 2, ',', '.') ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($analise['dica_principal'])): ?>
                    <div class="analysis-dica">
                        <strong>💡 Dica:</strong> <?= htmlspecialchars($analise['dica_principal']) ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (($analise['economia_potencial'] ?? 0) > 0): ?>
                    <div class="analysis-economia">
                        <strong>Economia potencial:</strong> R$ <?= number_format($analise['economia_potencial'], 2, ',', '.') ?>/mês
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Smart Alerts Section -->
            <div class="assistente-card alerts-section">
                <h3><i class="fas fa-bell"></i> Alertas Inteligentes</h3>
                <div class="alerts-list">
                    <?php if (!empty($alertas) && is_array($alertas)): ?>
                        <?php 
                        $maxAlertas = 5;
                        $count = 0;
                        foreach ($alertas as $alerta): 
                            if ($count >= $maxAlertas) break;
                            $count++;
                        ?>
                            <div class="alert-card alert-<?= htmlspecialchars($alerta['tipo'] ?? 'info') ?>">
                                <div class="alert-icon">
                                    <i class="<?= htmlspecialchars($alerta['icone'] ?? 'fas fa-info-circle') ?>"></i>
                                </div>
                                <div class="alert-content">
                                    <h5 class="alert-title"><?= htmlspecialchars($alerta['titulo'] ?? '') ?></h5>
                                    <p class="alert-message"><?= htmlspecialchars($alerta['mensagem'] ?? '') ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php if (count($alertas) > $maxAlertas): ?>
                            <a href="#" class="btn-ver-mais">Ver mais alertas</a>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="text-muted">Nenhum alerta no momento.</p>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <!-- Right Column: Chat -->
        <div class="assistente-right-col">
            <div class="chat-container">
                
                <div class="chat-header">
                    <div class="chat-title">
                        <i class="fas fa-robot"></i> 
                        <h2>Assistente Financeiro</h2>
                        <span class="status-dot <?= ($iaAtivo && $temApiKey) ? 'green' : 'red' ?>"></span>
                    </div>
                    <div class="chat-controls">
                        <select id="conversationSelector" class="form-select chat-select">
                            <option value="">Nova Conversa</option>
                        </select>
                        <button id="btnNovaConversa" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Nova
                        </button>
                    </div>
                </div>

                <div class="chat-messages" id="chatMessages">
                    <div class="chat-bubble assistant initial-message">
                        <div class="bubble-content" data-original-message="<?= htmlspecialchars($mensagemProativa) ?>">
                            <?= nl2br(htmlspecialchars($mensagemProativa)) ?>
                        </div>
                        <div class="bubble-time"><?= date('H:i') ?></div>
                    </div>
                </div>

                <div class="chat-bottom-area">
                    <div class="quick-suggestions">
                        <button class="btn-sugestao" onclick="enviarSugestao('Sim, monte um plano para eu voltar ao positivo')">Monte um plano para mim</button>
                        <button class="btn-sugestao" onclick="enviarSugestao('Analise meus gastos e encontre onde posso economizar')">Onde posso economizar?</button>
                        <button class="btn-sugestao" onclick="enviarSugestao('Quais são meus gastos não essenciais?')">Gastos não essenciais</button>
                        <button class="btn-sugestao" onclick="enviarSugestao('Analise meu cartão de crédito')">Analise meu cartão</button>
                        <button class="btn-sugestao" onclick="enviarSugestao('Quanto preciso guardar por mês para atingir minhas metas?')">Plano para minhas metas</button>
                        <button class="btn-sugestao" onclick="enviarSugestao('Como está minha Poupança e quanto devo guardar?')">Minha Poupança</button>
                    </div>

                    <div class="chat-input-area">
                        <textarea id="chatInput" placeholder="Digite sua mensagem..." 
                                 rows="1" <?= (!$iaAtivo || !$temApiKey) ? 'disabled' : '' ?>></textarea>
                        <button id="btnSend" class="btn-send" <?= (!$iaAtivo || !$temApiKey) ? 'disabled' : '' ?>>
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>
</main>

<script src="assets/js/app.js"></script>
<script src="assets/js/assistente.js?v=<?= time() ?>"></script>
</body>
</html>
