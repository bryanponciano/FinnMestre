-- Sistema de Controle Financeiro
-- Script de criação do banco de dados

CREATE DATABASE IF NOT EXISTS finmestre CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE finmestre;

-- Tabela de categorias
CREATE TABLE IF NOT EXISTS categorias (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    tipo ENUM('entrada', 'saida') NOT NULL,
    cor VARCHAR(7) DEFAULT '#6366f1',
    icone VARCHAR(50) DEFAULT 'fa-tag',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de transações
CREATE TABLE IF NOT EXISTS transacoes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    descricao VARCHAR(255) NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    tipo ENUM('entrada', 'saida') NOT NULL,
    categoria_id INT,
    data_transacao DATE NOT NULL,
    observacao TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL
);

-- Tabela de configurações
CREATE TABLE IF NOT EXISTS configuracoes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    chave VARCHAR(50) UNIQUE NOT NULL,
    valor TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Inserir categorias padrão de ENTRADA
INSERT INTO categorias (nome, tipo, cor, icone) VALUES
('Salário', 'entrada', '#10b981', 'fa-money-bill-wave'),
('Freelance', 'entrada', '#06b6d4', 'fa-laptop'),
('Investimentos', 'entrada', '#8b5cf6', 'fa-chart-line'),
('Vendas', 'entrada', '#f59e0b', 'fa-shopping-cart'),
('Outros', 'entrada', '#64748b', 'fa-plus-circle');

-- Inserir categorias padrão de SAÍDA
INSERT INTO categorias (nome, tipo, cor, icone) VALUES
('Alimentação', 'saida', '#ef4444', 'fa-utensils'),
('Transporte', 'saida', '#f97316', 'fa-car'),
('Moradia', 'saida', '#eab308', 'fa-home'),
('Saúde', 'saida', '#22c55e', 'fa-heartbeat'),
('Educação', 'saida', '#3b82f6', 'fa-graduation-cap'),
('Lazer', 'saida', '#a855f7', 'fa-gamepad'),
('Roupas', 'saida', '#ec4899', 'fa-tshirt'),
('Contas', 'saida', '#14b8a6', 'fa-file-invoice'),
('Outros', 'saida', '#64748b', 'fa-minus-circle');

-- Inserir configuração padrão de limite mensal
INSERT INTO configuracoes (chave, valor) VALUES ('limite_mensal', '3000.00');

-- Inserir algumas transações de exemplo
INSERT INTO transacoes (descricao, valor, tipo, categoria_id, data_transacao) VALUES
('Salário Janeiro', 5000.00, 'entrada', 1, CURDATE()),
('Freelance Website', 1500.00, 'entrada', 2, CURDATE()),
('Supermercado', 450.00, 'saida', 6, CURDATE()),
('Uber', 85.00, 'saida', 7, CURDATE()),
('Aluguel', 1200.00, 'saida', 8, CURDATE()),
('Farmácia', 120.00, 'saida', 9, CURDATE()),
('Netflix', 45.90, 'saida', 11, CURDATE()),
('Conta de Luz', 180.00, 'saida', 13, CURDATE());
