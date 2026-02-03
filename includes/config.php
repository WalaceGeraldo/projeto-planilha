<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

try {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
} catch (Exception $e) {
}

function get_env_var($key, $default = null) {
    if (!empty($_ENV[$key])) return $_ENV[$key];
    
    $val = getenv($key);
    if ($val !== false && $val !== '') return $val;
    
    if (!empty($_SERVER[$key])) return $_SERVER[$key];
    
    return $default;
}

$databaseUrl = get_env_var('DATABASE_URL');

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
    $parts = parse_url($databaseUrl);
    
    define('DB_HOST', $parts['host'] ?? 'localhost');
    define('DB_PORT', $parts['port'] ?? '5432');
    define('DB_NAME', ltrim($parts['path'] ?? 'projeto_planilha', '/'));
    define('DB_USER', $parts['user'] ?? 'postgres');
    define('DB_PASS', $parts['pass'] ?? '');
} elseif (get_env_var('PGHOST')) {
    define('DB_HOST', get_env_var('PGHOST'));
    define('DB_PORT', get_env_var('PGPORT', '5432'));
    define('DB_NAME', get_env_var('PGDATABASE', 'railway'));
    define('DB_USER', get_env_var('PGUSER', 'postgres'));
    define('DB_PASS', get_env_var('PGPASSWORD', ''));
} else {
    define('DB_HOST', get_env_var('DB_HOST', 'localhost'));
    define('DB_PORT', get_env_var('DB_PORT', '5432'));
    define('DB_NAME', get_env_var('DB_NAME', 'projeto_planilha'));
    define('DB_USER', get_env_var('DB_USER', 'postgres'));
    define('DB_PASS', get_env_var('DB_PASS', ''));
}
?>
