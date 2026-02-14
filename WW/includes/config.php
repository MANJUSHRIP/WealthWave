<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'financial_literacy');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Create database connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Set default timezone
date_default_timezone_set('Asia/Kolkata');

// Include functions
require_once 'functions.php';
?>
