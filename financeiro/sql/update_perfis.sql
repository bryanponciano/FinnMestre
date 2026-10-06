-- =====================================================
-- FINMESTRE - Atualização para Múltiplos Perfis
-- Execute este script para adicionar suporte a perfis
-- =====================================================

-- Tabela de perfis
CREATE TABLE IF NOT EXISTS perfis (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_email VARCHAR(255) NOT NULL,
    nome VARCHAR(100) NOT NULL,
    tipo ENUM('pessoal', 'empresa', 'familia', 'outro') DEFAULT 'pessoal',
    cor VARCHAR(7) DEFAULT '#6366f1',
    icone VARCHAR(50) DEFAULT 'fa-user',
    ativo BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usuario_email (usuario_email)
);

-- Adicionar coluna perfil_id nas tabelas existentes (se não existirem)
-- Usando IGNORE para evitar erro se coluna já existe

-- Para MySQL 5.7+, verificar se coluna existe antes de adicionar
SET @dbname = DATABASE();

-- Transacoes
SET @tablename = 'transacoes';
SET @columnname = 'perfil_id';
SET @preparedStatement = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0,
    'SELECT 1',
    CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' INT NULL AFTER id')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Contas
SET @tablename = 'contas';
SET @preparedStatement = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0,
    'SELECT 1',
    CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' INT NULL AFTER id')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Metas
SET @tablename = 'metas';
SET @preparedStatement = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0,
    'SELECT 1',
    CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' INT NULL AFTER id')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Alertas
SET @tablename = 'alertas';
SET @preparedStatement = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0,
    'SELECT 1',
    CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' INT NULL AFTER id')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Tabela de configuração dos robôs
CREATE TABLE IF NOT EXISTS config_robos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_email VARCHAR(255) NOT NULL,
    robot_alert_ativo BOOLEAN DEFAULT 1,
    robot_merit_ativo BOOLEAN DEFAULT 1,
    categorias_superfluas TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_usuario (usuario_email)
);

-- Inserir configuração padrão de categorias supérfluas (Lazer e Assinaturas)
-- Será processado na aplicação PHP
