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
echo "getenv('DATABASE_URL'): " . (getenv('DATABASE_URL') ? '✅ DEFINIDA (Começa com ' . substr(getenv('DATABASE_URL'), 0, 10) . '...)' : '❌ NÃO DEFINIDA') . "\n";
echo "\$_ENV['DB_HOST']: " . ($_ENV['DB_HOST'] ?? 'Null/Empty') . "\n";
echo "\$_SERVER['DB_HOST']: " . ($_SERVER['DB_HOST'] ?? 'Null/Empty') . "\n";

echo "\n3. Informações do Sistema:\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "\n";

echo "\n4. Lista Completa de Chaves de Ambiente (Valores Ocultos):\n";
$all_keys = array_merge(array_keys($_ENV), array_keys(getenv()), array_keys($_SERVER));
$all_keys = array_unique($all_keys);
sort($all_keys);

foreach ($all_keys as $key) {
    // Filtro mais amplo para encontrar qualquer variável de banco
    if (preg_match('/(DB|POSTGRES|PG|RAILWAY|URL|HOST|USER|PASS|NAME)/i', $key)) {
        echo "[$key] => (Presente)\n";
    }
}

echo "\n=== CONCLUSÃO ===\n";
if (defined('DB_HOST') && DB_HOST === 'localhost') {
    echo "❌ FALHA: O sistema ainda acha que é localhost.\n";
    echo "Se você adicionou a variável, o Railway pode não ter feito o Redeploy ainda.\n";
    echo "Tente clicar em 'Deployments' > 'Trigger Redeploy' no Railway.\n";
} else {
    echo "✅ SUCESSO: Conectado em " . DB_HOST . "\n";
}
