<?php
// Database connection
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'hkid_appointment');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Security headers (web server hardening)
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Content-Security-Policy: default-src \'self\'');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1); // Enforce HTTPS
session_start();

// Require Google Authenticator for MFA
require 'vendor/autoload.php';
$ga = new PHPGangsta_GoogleAuthenticator();
?>