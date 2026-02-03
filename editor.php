<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/config.php';

use App\Repository\SpreadsheetRepository;

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: index.php");
    exit;
}

$repo = new SpreadsheetRepository();
$meta = $repo->find($id);

if (!$meta) {
    die("Planilha não encontrada.");
}

include 'views/editor.php';
?>