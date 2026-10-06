-- =====================================================
-- FINMESTRE - Atualização para Sistema Comportamental
-- =====================================================

-- Adicionar campos de configuração comportamental na tabela usuarios
ALTER TABLE usuarios 
ADD COLUMN IF NOT EXISTS salario_mensal DECIMAL(12,2) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS horas_trabalho_mes INT DEFAULT 176;

-- Criar índice para otimização
CREATE INDEX IF NOT EXISTS idx_transacoes_categoria ON transacoes(categoria_id);

-- Atualizar categorias com flag de "validação social"
ALTER TABLE categorias 
ADD COLUMN IF NOT EXISTS validacao_social TINYINT(1) DEFAULT 0;

-- Marcar categorias típicas de validação social
UPDATE categorias SET validacao_social = 1 
WHERE nome IN ('Lazer', 'Roupas', 'Restaurantes', 'Delivery', 'Assinaturas', 'Beleza', 'Presentes');

-- Tabela para metas automáticas de micro-poupança
CREATE TABLE IF NOT EXISTS micro_poupanca (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    valor_diario DECIMAL(10,2) NOT NULL,
    ativo TINYINT(1) DEFAULT 1,
    data_inicio DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);
