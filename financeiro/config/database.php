<?php
/**
 * =====================================================
 * FinnMestre - Configuração do Banco de Dados
 * =====================================================
 * Configurado para MySQL Local (XAMPP/WAMP)
 */

// ==========================================
// CONFIGURAÇÃO LOCAL
// ==========================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'FinMestre');
define('DB_USER', 'root');
define('DB_PASS', '');

// ==========================================
// CONEXÃO PDO
// ==========================================

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    error_log("Erro de conexão com banco: " . $e->getMessage());
    die("Erro de conexão com o banco de dados. Tente novamente mais tarde.");
}
