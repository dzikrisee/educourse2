<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'educourse2');

// Site configuration
define('SITE_URL', 'http://localhost/educourse');
define('UPLOAD_PATH', 'assets/uploads/');

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Database connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Check if user is logged in
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Redirect if not logged in
function requireLogin()
{
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/login.php');
        exit();
    }
}

// Redirect if not admin
function requireAdmin()
{
    requireLogin();
    if (!isAdmin()) {
        header('Location: ' . SITE_URL . '/index.php');
        exit();
    }
}
