<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\Controller\SpreadsheetController;
use Dotenv\Dotenv;

// Configurações de memória e tempo
ini_set('memory_limit', '1024M');
ini_set('max_execution_time', '300');

session_start();

// Carregar variáveis de ambiente
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Definir headers
header('Content-Type: application/json; charset=utf-8');

// Definir constantes de config
require_once __DIR__ . '/includes/config.php';

$action = $_GET['action'] ?? '';

$controller = new SpreadsheetController();
$response = $controller->handleRequest($action);

// Se handleRequest já não tiver dado exit (export binary), retorna JSON
if ($response) {
    echo json_encode($response);
}