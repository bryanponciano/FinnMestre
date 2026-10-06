<?php
/**
 * Verificação de Autenticação e Assinatura
 * Inclua este arquivo no início de cada página protegida
 */

session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header('Location: vendas.php');
    exit;
}

// Verificar assinatura ativa (exceto admin)
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/assinaturas.php';

$userId = $_SESSION['usuario_id'] ?? null;
$isAdmin = (isset($_SESSION['usuario_email']) && $_SESSION['usuario_email'] === ADMIN_EMAIL) || $userId == 1;

if (!$isAdmin && $userId) {
    if (!usuarioTemAcesso($userId)) {
        // Sem assinatura ativa - redirecionar para vendas
        header('Location: vendas.php?expired=1#planos');
        exit;
    }
}
