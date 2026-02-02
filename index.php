<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$spreadsheets = get_all_spreadsheets();

include 'views/dashboard.php';
?>
