<?php
// debug_env.php
// Este arquivo serve para diagnosticar se as variáveis de ambiente estão chegando no PHP.

require_once __DIR__ . '/vendor/autoload.php';

// Tenta carregar .env (se existir)
try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->safeLoad();
} catch (Exception $e) {
    // Ignora erro de .env
}

// Carrega config (que define as constantes)
require_once __DIR__ . '/includes/config.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== DIAGNÓSTICO DE AMBIENTE ===\n\n";

echo "1. Valores das Constantes (usadas pelo sistema):\n";
echo "DB_HOST: " . (defined('DB_HOST') ? DB_HOST : 'NÃO DEFINIDA') . "\n";
echo "DB_PORT: " . (defined('DB_PORT') ? DB_PORT : 'NÃO DEFINIDA') . "\n";
echo "DB_NAME: " . (defined('DB_NAME') ? DB_NAME : 'NÃO DEFINIDA') . "\n";
echo "DB_USER: " . (defined('DB_USER') ? DB_USER : 'NÃO DEFINIDA') . "\n";
echo "DB_PASS: " . (defined('DB_PASS') ? (empty(DB_PASS) ? '(Vazio)' : '****** (Definido)') : 'NÃO DEFINIDA') . "\n";

echo "\n2. Teste direto das Variáveis (Origem):\n";
echo "getenv('DB_HOST'): " . (getenv('DB_HOST') ?: 'False/Empty') . "\n";
echo "\$_ENV['DB_HOST']: " . ($_ENV['DB_HOST'] ?? 'Null/Empty') . "\n";
echo "\$_SERVER['DB_HOST']: " . ($_SERVER['DB_HOST'] ?? 'Null/Empty') . "\n";

echo "\n3. Informações do Sistema:\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "\n";

echo "\n=== CONCLUSÃO ===\n";
if (defined('DB_HOST') && DB_HOST === 'localhost') {
    echo "❌ PROBLEMA DETECTADO: O sistema está tentando conectar em 'localhost'.\n";
    echo "Isso significa que as variáveis de ambiente NÃO estão configuradas no Railway ou o PHP não consegue lê-las.\n";
    echo "SOLUÇÃO: Vá em Settings > Variables no Railway e adicione DB_HOST, DB_USER, etc.\n";
} elseif (defined('DB_HOST') && DB_HOST !== 'localhost') {
    echo "✅ CONFIGURAÇÃO OK: O Host parece estar apontando para um servidor externo (" . DB_HOST . ").\n";
    echo "Se ainda der erro, verifique se o Host está correto e se o banco permite conexões externas.\n";
} else {
    echo "⚠️ SITUAÇÃO INDEFINIDA.\n";
}
