<?php

// Carrega o autoloader do Composer para ter acesso ao PHP Dotenv
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Carrega as variáveis do arquivo .env na raiz do projeto
try {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
} catch (Exception $e) {
    // Se não tiver .env, segue a vida (fallback ou erro mais tarde)
    // Em produção, as variáveis podem vir do ambiente do servidor
}

define('DB_HOST', $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: '5432');
define('DB_NAME', $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'projeto_planilha');
define('DB_USER', $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'postgres');
define('DB_PASS', $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: '');

?>
