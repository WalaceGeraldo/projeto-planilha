<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/config.php';

use App\Repository\SpreadsheetRepository;

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$repo = new SpreadsheetRepository();
$spreadsheets = $repo->getAll();

include 'views/dashboard.php';
?>
