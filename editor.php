<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: index.php");
    exit;
}

$meta = find_spreadsheet_meta($id);
if (!$meta) {
    die("Planilha não encontrada.");
}

include 'views/editor.php';
?>