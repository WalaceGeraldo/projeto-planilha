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

// Função auxiliar para buscar variáveis de ambiente em várias fontes
function get_env_var($key, $default = null) {
    // 1. Prioridade: $_ENV (carregado pelo phpdotenv ou sistema)
    if (!empty($_ENV[$key])) return $_ENV[$key];
    
    // 2. getenv() (padrão do sistema)
    $val = getenv($key);
    if ($val !== false && $val !== '') return $val;
    
    // 3. $_SERVER (alguns servidores injetam aqui)
    if (!empty($_SERVER[$key])) return $_SERVER[$key];
    
    return $default;
}

// Tentar detectar DATABASE_URL (ou qualquer variável que pareça uma URL de banco)
$databaseUrl = get_env_var('DATABASE_URL');

// Se não achou pelo nome padrão, procura por qualquer variável que pareça uma conexão Postgres
if (!$databaseUrl) {
    $allVars = array_merge($_ENV, $_SERVER, getenv());
    foreach ($allVars as $key => $value) {
        if (is_string($value) && strpos($value, 'postgres://') === 0) {
            $databaseUrl = $value;
            break;
        }
    }
}

if ($databaseUrl) {
    // Parser da URL de conexão: postgres://user:pass@host:port/dbname
    $parts = parse_url($databaseUrl);
    
    define('DB_HOST', $parts['host'] ?? 'localhost');
    define('DB_PORT', $parts['port'] ?? '5432');
    define('DB_NAME', ltrim($parts['path'] ?? 'projeto_planilha', '/'));
    define('DB_USER', $parts['user'] ?? 'postgres');
    define('DB_PASS', $parts['pass'] ?? '');
} else {
    // Fallback para variáveis individuais
    define('DB_HOST', get_env_var('DB_HOST', 'localhost'));
    define('DB_PORT', get_env_var('DB_PORT', '5432'));
    define('DB_NAME', get_env_var('DB_NAME', 'projeto_planilha'));
    define('DB_USER', get_env_var('DB_USER', 'postgres'));
    define('DB_PASS', get_env_var('DB_PASS', ''));
}

?>
