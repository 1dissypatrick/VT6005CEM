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
// 透過禁止頁面嵌入 iframe 來防止點擊劫持
header('X-Frame-Options: DENY'); 
header('X-Content-Type-Options: nosniff');
// prevent script
header('Content-Security-Policy: default-src \'self\'');
//強制使用 HTTPS，防止中間人 (MITM) 攻擊。
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
header('Referrer-Policy: no-referrer');
// Disables access to sensitive features like geolocation, camera, and microphone.
header('Feature-Policy: geolocation \'none\'; camera \'none\'; microphone \'none\'');

// Session configuration
//阻止客戶端腳本存取會話 cookie，降低 XSS 風險。
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1); // close HTTPS
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