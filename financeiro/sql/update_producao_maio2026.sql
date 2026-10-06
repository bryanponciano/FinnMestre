-- --------------------------------------------------------
-- Migração de Produção - Maio 2026
-- Execute este SQL no phpMyAdmin da Hostinger
-- para corrigir colunas faltando no banco de dados
-- --------------------------------------------------------

-- 1. Adicionar coluna valor_mensal na tabela metas (para planejamento mensal)
ALTER TABLE `metas` ADD COLUMN IF NOT EXISTS `valor_mensal` DECIMAL(15,2) DEFAULT 0.00 AFTER `valor_atual`;

-- 2. Adicionar coluna telefone_whatsapp na tabela usuarios (para integração IA WhatsApp)
ALTER TABLE `usuarios` ADD COLUMN IF NOT EXISTS `telefone_whatsapp` VARCHAR(20) NULL DEFAULT NULL AFTER `email`;
