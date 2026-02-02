<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\Controller\AuthController;
use Dotenv\Dotenv;

session_start();

// Carregar variáveis de ambiente
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Definir headers e output charset
header('Content-Type: application/json; charset=utf-8');

// Definir constantes de config se ainda não estiverem (fallback)
require_once __DIR__ . '/includes/config.php';

$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true) ?? [];

$controller = new AuthController();
$response = $controller->handleRequest($action, $input);

echo json_encode($response);
