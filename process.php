<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\Controller\SpreadsheetController;
use Dotenv\Dotenv;

ini_set('memory_limit', '1024M');
ini_set('max_execution_time', '300');

session_start();

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/includes/config.php';

$action = $_GET['action'] ?? '';

$controller = new SpreadsheetController();
$response = $controller->handleRequest($action);

if ($response) {
    echo json_encode($response);
}