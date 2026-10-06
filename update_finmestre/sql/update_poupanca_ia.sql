-- Phase 2: Poupança & IA
-- Criação das tabelas de chat IA
CREATE TABLE IF NOT EXISTS ia_conversas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    perfil_id INT NOT NULL,
    titulo VARCHAR(255) DEFAULT 'Nova conversa',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (perfil_id) REFERENCES perfis(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS ia_mensagens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    conversa_id INT NOT NULL,
    role ENUM('user', 'assistant') NOT NULL,
    mensagem TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (conversa_id) REFERENCES ia_conversas(id) ON DELETE CASCADE
);

-- Correção de transações antigas de poupança (que eram entrada/saída em vez de transferência)

-- Rollback information:
-- To rollback, you would need to change tipo='transferencia' back to 'saida'/'entrada' based on the conta_destino_id and conta_id, but the original categorization might be lost.

-- Update saida (withdrawal) pointing to poupança -> transfer
UPDATE transacoes t
JOIN contas c ON c.id = t.conta_destino_id OR c.id = t.conta_id
SET 
    t.tipo = 'transferencia',
    t.conta_destino_id = c.id
WHERE 
    c.tipo = 'poupanca' 
    AND t.tipo = 'saida';

-- Update entrada (deposit) from poupança -> transfer
UPDATE transacoes t
JOIN contas c ON c.id = t.conta_id
SET 
    t.tipo = 'transferencia',
    t.conta_id = c.id
WHERE 
    c.tipo = 'poupanca' 
    AND t.tipo = 'entrada';
