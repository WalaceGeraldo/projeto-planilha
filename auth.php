<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\Controller\AuthController;
use Dotenv\Dotenv;

session_start();

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/includes/config.php';

$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true) ?? [];

try {
    $controller = new AuthController();
    $response = $controller->handleRequest($action, $input);
    echo json_encode($response);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
