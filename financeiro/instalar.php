<?php
/**
 * =====================================================
 * FinnMestre - Sistema de Controle Financeiro Inteligente
 * Script de Instalação do Banco de Dados
 * =====================================================
 * 
 * Execute este arquivo UMA VEZ para criar as tabelas.
 * IMPORTANTE: Delete este arquivo após a instalação!
 */

require_once 'config/database.php';

// Desabilitar limite de tempo para instalação
set_time_limit(0);

$erros = [];
$sucessos = [];

try {
    // ==========================================
    // TABELA DE CATEGORIAS
    // ==========================================
    $pdo->exec("CREATE TABLE IF NOT EXISTS categorias (
        id INT PRIMARY KEY AUTO_INCREMENT,
        nome VARCHAR(100) NOT NULL,
        tipo ENUM('entrada', 'saida') NOT NULL,
        cor VARCHAR(7) DEFAULT '#6366f1',
        icone VARCHAR(50) DEFAULT 'fa-tag',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $sucessos[] = "Tabela 'categorias' criada";

    // ==========================================
    // TABELA DE CONTAS (NOVA!)
    // Suporta: Conta Corrente, Cartão de Crédito, 
    // Poupança, Conta de Parcelas
    // ==========================================
    $pdo->exec("CREATE TABLE IF NOT EXISTS contas (
        id INT PRIMARY KEY AUTO_INCREMENT,
        nome VARCHAR(100) NOT NULL,
        tipo ENUM('corrente', 'credito', 'poupanca', 'carteira') NOT NULL DEFAULT 'corrente',
        instituicao VARCHAR(100) DEFAULT NULL,
        saldo_inicial DECIMAL(15,2) DEFAULT 0.00,
        limite_credito DECIMAL(15,2) DEFAULT NULL,
        dia_fechamento INT DEFAULT NULL,
        dia_vencimento INT DEFAULT NULL,
        cor VARCHAR(7) DEFAULT '#6366f1',
        icone VARCHAR(50) DEFAULT 'fa-wallet',
        ativo BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    $sucessos[] = "Tabela 'contas' criada";

    // ==========================================
    // TABELA DE TRANSAÇÕES (ATUALIZADA!)
    // Agora suporta parcelamento e vínculo com conta
    // ==========================================
    $pdo->exec("CREATE TABLE IF NOT EXISTS transacoes (
        id INT PRIMARY KEY AUTO_INCREMENT,
        descricao VARCHAR(255) NOT NULL,
        valor DECIMAL(15,2) NOT NULL,
        tipo ENUM('entrada', 'saida', 'transferencia') NOT NULL,
        categoria_id INT DEFAULT NULL,
        conta_id INT DEFAULT NULL,
        conta_destino_id INT DEFAULT NULL,
        data_transacao DATE NOT NULL,
        data_efetivacao DATE DEFAULT NULL,
        parcela_atual INT DEFAULT NULL,
        total_parcelas INT DEFAULT NULL,
        transacao_pai_id INT DEFAULT NULL,
        recorrente BOOLEAN DEFAULT FALSE,
        observacao TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL,
        FOREIGN KEY (conta_id) REFERENCES contas(id) ON DELETE SET NULL,
        FOREIGN KEY (conta_destino_id) REFERENCES contas(id) ON DELETE SET NULL,
        FOREIGN KEY (transacao_pai_id) REFERENCES transacoes(id) ON DELETE CASCADE,
        INDEX idx_data (data_transacao),
        INDEX idx_tipo (tipo),
        INDEX idx_conta (conta_id)
    )");
    $sucessos[] = "Tabela 'transacoes' criada";

    // ==========================================
    // TABELA DE METAS FINANCEIRAS (NOVA!)
    // ==========================================
    $pdo->exec("CREATE TABLE IF NOT EXISTS metas (
        id INT PRIMARY KEY AUTO_INCREMENT,
        titulo VARCHAR(150) NOT NULL,
        descricao TEXT,
        valor_objetivo DECIMAL(15,2) NOT NULL,
        valor_atual DECIMAL(15,2) DEFAULT 0.00,
        data_inicio DATE DEFAULT NULL,
        data_limite DATE DEFAULT NULL,
        cor VARCHAR(7) DEFAULT '#10b981',
        icone VARCHAR(50) DEFAULT 'fa-bullseye',
        prioridade ENUM('baixa', 'media', 'alta') DEFAULT 'media',
        concluida BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    $sucessos[] = "Tabela 'metas' criada";

    // ==========================================
    // TABELA DE CONTRIBUIÇÕES PARA METAS (NOVA!)
    // ==========================================
    $pdo->exec("CREATE TABLE IF NOT EXISTS meta_contribuicoes (
        id INT PRIMARY KEY AUTO_INCREMENT,
        meta_id INT NOT NULL,
        valor DECIMAL(15,2) NOT NULL,
        observacao VARCHAR(255),
        data_contribuicao DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (meta_id) REFERENCES metas(id) ON DELETE CASCADE
    )");
    $sucessos[] = "Tabela 'meta_contribuicoes' criada";

    // ==========================================
    // TABELA DE ALERTAS INTELIGENTES (NOVA!)
    // ==========================================
    $pdo->exec("CREATE TABLE IF NOT EXISTS alertas (
        id INT PRIMARY KEY AUTO_INCREMENT,
        tipo ENUM('limite_credito', 'limite_gasto', 'meta_atingida', 'economia', 'lembrete', 'dica') NOT NULL,
        titulo VARCHAR(150) NOT NULL,
        mensagem TEXT NOT NULL,
        icone VARCHAR(50) DEFAULT 'fa-bell',
        cor VARCHAR(7) DEFAULT '#f59e0b',
        acao_url VARCHAR(255) DEFAULT NULL,
        lido BOOLEAN DEFAULT FALSE,
        expirado BOOLEAN DEFAULT FALSE,
        data_expiracao DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_lido (lido),
        INDEX idx_tipo (tipo)
    )");
    $sucessos[] = "Tabela 'alertas' criada";

    // ==========================================
    // TABELA DE CONFIGURAÇÕES
    // ==========================================
    $pdo->exec("CREATE TABLE IF NOT EXISTS configuracoes (
        id INT PRIMARY KEY AUTO_INCREMENT,
        chave VARCHAR(50) UNIQUE NOT NULL,
        valor TEXT,
        tipo ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
        descricao VARCHAR(255) DEFAULT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    $sucessos[] = "Tabela 'configuracoes' criada";

    // ==========================================
    // TABELA DE USUÁRIOS (para multi-usuário futuro)
    // ==========================================
    $pdo->exec("CREATE TABLE IF NOT EXISTS usuarios (
        id INT PRIMARY KEY AUTO_INCREMENT,
        nome VARCHAR(100) NOT NULL,
        email VARCHAR(150) UNIQUE NOT NULL,
        senha VARCHAR(255) NOT NULL,
        avatar VARCHAR(255) DEFAULT NULL,
        tema ENUM('dark', 'light', 'auto') DEFAULT 'dark',
        idioma VARCHAR(5) DEFAULT 'pt-BR',
        ativo BOOLEAN DEFAULT TRUE,
        ultimo_acesso DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    $sucessos[] = "Tabela 'usuarios' criada";

    // ==========================================
    // DADOS INICIAIS - CATEGORIAS DE ENTRADA
    // ==========================================
    $stmt = $pdo->query("SELECT COUNT(*) FROM categorias");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO categorias (nome, tipo, cor, icone) VALUES
            ('Salário', 'entrada', '#10b981', 'fa-money-bill-wave'),
            ('Freelance', 'entrada', '#06b6d4', 'fa-laptop-code'),
            ('Investimentos', 'entrada', '#8b5cf6', 'fa-chart-line'),
            ('Vendas', 'entrada', '#f59e0b', 'fa-shopping-cart'),
            ('Dividendos', 'entrada', '#14b8a6', 'fa-hand-holding-usd'),
            ('Bonificações', 'entrada', '#22c55e', 'fa-gift'),
            ('Reembolsos', 'entrada', '#3b82f6', 'fa-undo'),
            ('Outros Ganhos', 'entrada', '#64748b', 'fa-plus-circle')
        ");
        $sucessos[] = "Categorias de ENTRADA inseridas";

        // ==========================================
        // DADOS INICIAIS - CATEGORIAS DE SAÍDA
        // ==========================================
        $pdo->exec("INSERT INTO categorias (nome, tipo, cor, icone) VALUES
            ('Alimentação', 'saida', '#ef4444', 'fa-utensils'),
            ('Transporte', 'saida', '#f97316', 'fa-car'),
            ('Moradia', 'saida', '#eab308', 'fa-home'),
            ('Saúde', 'saida', '#22c55e', 'fa-heartbeat'),
            ('Educação', 'saida', '#3b82f6', 'fa-graduation-cap'),
            ('Lazer', 'saida', '#a855f7', 'fa-gamepad'),
            ('Roupas', 'saida', '#ec4899', 'fa-tshirt'),
            ('Contas Fixas', 'saida', '#14b8a6', 'fa-file-invoice-dollar'),
            ('Assinaturas', 'saida', '#6366f1', 'fa-tv'),
            ('Compras', 'saida', '#f43f5e', 'fa-shopping-bag'),
            ('Pets', 'saida', '#fb923c', 'fa-paw'),
            ('Beleza', 'saida', '#e879f9', 'fa-spa'),
            ('Presentes', 'saida', '#fbbf24', 'fa-gifts'),
            ('Impostos', 'saida', '#78716c', 'fa-landmark'),
            ('Outros Gastos', 'saida', '#64748b', 'fa-minus-circle')
        ");
        $sucessos[] = "Categorias de SAÍDA inseridas";
    }

    // ==========================================
    // DADOS INICIAIS - CONTAS PADRÃO
    // ==========================================
    $stmt = $pdo->query("SELECT COUNT(*) FROM contas");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO contas (nome, tipo, instituicao, saldo_inicial, cor, icone) VALUES
            ('Carteira', 'carteira', NULL, 0.00, '#10b981', 'fa-wallet'),
            ('Conta Corrente', 'corrente', 'Banco Principal', 0.00, '#3b82f6', 'fa-university'),
            ('Poupança', 'poupanca', 'Banco Principal', 0.00, '#8b5cf6', 'fa-piggy-bank')
        ");
        $sucessos[] = "Contas padrão inseridas";
    }

    // ==========================================
    // DADOS INICIAIS - CONFIGURAÇÕES
    // ==========================================
    $stmt = $pdo->query("SELECT COUNT(*) FROM configuracoes");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO configuracoes (chave, valor, tipo, descricao) VALUES
            ('limite_mensal', '3000.00', 'number', 'Limite mensal de gastos'),
            ('moeda', 'BRL', 'string', 'Moeda principal do sistema'),
            ('primeiro_dia_mes', '1', 'number', 'Dia que considera início do mês financeiro'),
            ('alertas_ativos', 'true', 'boolean', 'Habilitar alertas inteligentes'),
            ('finbot_ativo', 'true', 'boolean', 'Habilitar mascote FinBot'),
            ('tema', 'dark', 'string', 'Tema do sistema (dark/light)'),
            ('notificacoes_email', 'false', 'boolean', 'Enviar notificações por email')
        ");
        $sucessos[] = "Configurações padrão inseridas";
    }

    // ==========================================
    // DADOS INICIAIS - USUÁRIO ADMIN
    // ==========================================
    $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios");
    if ($stmt->fetchColumn() == 0) {
        $senhaHash = password_hash('BRYAN2005', PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)")
            ->execute(['Bryan (Admin)', 'poncianobryan988@gmail.com', $senhaHash]);
        $sucessos[] = "Usuário admin criado (email: poncianobryan988@gmail.com)";
    }

} catch (PDOException $e) {
    $erros[] = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalação - FinnMestre</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: #e2e8f0;
        }

        .container {
            background: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 40px;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #10b981, #059669);
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: white;
            margin-bottom: 15px;
            box-shadow: 0 10px 30px rgba(16, 185, 129, 0.3);
        }

        .logo h1 {
            font-size: 2rem;
            background: linear-gradient(135deg, #10b981, #3b82f6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .logo p {
            color: #94a3b8;
            margin-top: 5px;
        }

        .results {
            margin-top: 20px;
        }

        .result-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 12px;
            margin-bottom: 8px;
            border-left: 3px solid #10b981;
        }

        .result-item.error {
            border-left-color: #ef4444;
            background: rgba(239, 68, 68, 0.1);
        }

        .result-item i {
            font-size: 1.2rem;
        }

        .result-item.success i {
            color: #10b981;
        }

        .result-item.error i {
            color: #ef4444;
        }

        .final-status {
            margin-top: 30px;
            padding: 25px;
            border-radius: 16px;
            text-align: center;
        }

        .final-status.success {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.2), rgba(16, 185, 129, 0.05));
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .final-status.error {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.2), rgba(239, 68, 68, 0.05));
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .final-status h2 {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }

        .final-status.success h2 {
            color: #10b981;
        }

        .final-status.error h2 {
            color: #ef4444;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 14px 28px;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            margin-top: 15px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
        }

        .warning {
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.3);
            border-radius: 12px;
            padding: 15px;
            margin-top: 20px;
            color: #fbbf24;
            font-size: 0.9rem;
        }

        .warning i {
            margin-right: 8px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="logo">
            <div class="logo-icon">
                <i class="fas fa-robot"></i>
            </div>
            <h1>FinnMestre</h1>
            <p>Instalação do Sistema</p>
        </div>

        <div class="results">
            <?php foreach ($sucessos as $msg): ?>
                <div class="result-item success">
                    <i class="fas fa-check-circle"></i>
                    <span><?= htmlspecialchars($msg) ?></span>
                </div>
            <?php endforeach; ?>

            <?php foreach ($erros as $msg): ?>
                <div class="result-item error">
                    <i class="fas fa-times-circle"></i>
                    <span><?= htmlspecialchars($msg) ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($erros)): ?>
            <div class="final-status success">
                <h2><i class="fas fa-rocket"></i> Instalação Concluída!</h2>
                <p>Seu sistema FinnMestre está pronto para uso.</p>
                <a href="index.php" class="btn">
                    <i class="fas fa-arrow-right"></i> Acessar o Sistema
                </a>
            </div>
            <div class="warning">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>IMPORTANTE:</strong> Por segurança, delete este arquivo (instalar.php) após a instalação!
            </div>
        <?php else: ?>
            <div class="final-status error">
                <h2><i class="fas fa-exclamation-circle"></i> Erro na Instalação</h2>
                <p>Verifique os erros acima e tente novamente.</p>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>