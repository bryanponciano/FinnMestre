-- --------------------------------------------------------
-- Atualização do Banco de Dados - FinMestre V2
-- Rode este código no seu phpMyAdmin da Hostinger para garantir que as novas funcionalidades funcionem!
-- --------------------------------------------------------

-- 1. Cria a tabela de assinaturas (se não existir)
CREATE TABLE IF NOT EXISTS assinaturas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    plano ENUM('mensal', 'semestral', 'anual') DEFAULT 'mensal',
    status ENUM('active', 'pending', 'pending_boleto', 'canceled', 'overdue') DEFAULT 'pending',
    forma_pagamento VARCHAR(50),
    valor DECIMAL(10,2),
    payment_id VARCHAR(255),
    expires_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_usuario (usuario_id),
    INDEX idx_status (status)
);

-- 2. Cria a tabela de eventos de pagamento do Mercado Pago (log do Webhook)
CREATE TABLE IF NOT EXISTS payment_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_id VARCHAR(255),
    usuario_id INT,
    status VARCHAR(50),
    tipo_evento VARCHAR(50),
    payload JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_payment (payment_id),
    INDEX idx_usuario (usuario_id)
);

-- 3. Adiciona a coluna apelido na tabela usuários (caso não exista)
-- Nota: se ela já existir, o MySQL irá ignorar ou você pode simplesmente pular essa linha.
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS apelido VARCHAR(100) DEFAULT NULL;
