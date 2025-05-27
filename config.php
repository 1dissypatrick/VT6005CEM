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

// Security headers (web server hardening) preventing phishing
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Content-Security-Policy: default-src \'self\'');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
header('Referrer-Policy: no-referrer');
header('Feature-Policy: geolocation \'none\'; camera \'none\'; microphone \'none\'');

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1); // Enforce HTTPS
ini_set('session.cookie_samesite', 'Strict'); 
// The SameSite attribute controls whether cookies are sent with cross-site requests, 
// mitigating Cross-Site Request Forgery (CSRF) attacks.
ini_set('session.use_strict_mode', '1');

// Start session only if not active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Require Google Authenticator for MFA
require 'vendor/autoload.php';
$ga = new PHPGangsta_GoogleAuthenticator();
?>