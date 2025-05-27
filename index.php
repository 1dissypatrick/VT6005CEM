<?php
// C:\xampp\htdocs\VT6005CEM Coursework 2 Wong Pak Kwan\session_config.php
// Configure session settings before starting session
ini_set('session.cookie_httponly', '1'); // Prevent JavaScript access to session cookies
ini_set('session.cookie_secure', '1');   // Ensure cookies are sent over HTTPS only
ini_set('session.cookie_samesite', 'Strict'); // Mitigate CSRF attacks
ini_set('session.use_strict_mode', '1'); // Prevent session fixation

// Start session only if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize CSRF token if not set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>HKID Card Booking and Management System</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f9;
            color: #333;
            text-align: center;
        }
        h1 {
            color: #005a87;
            margin-bottom: 30px;
        }
        .nav-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
            max-width: 400px;
            margin: 0 auto;
        }
        .nav-button {
            display: inline-block;
            padding: 12px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        .nav-button:hover {
            background-color: #0056b3;
        }
        @media (max-width: 600px) {
            .nav-container {
                gap: 10px;
            }
            .nav-button {
                padding: 10px 15px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <h1>Welcome to HKID Card Booking and Management System</h1>
    <div class="nav-container">
        <a href="public_book_appointment.php" class="nav-button">Book an Appointment (Public)</a>
        <a href="login.php" class="nav-button">Staff Login</a>
    </div>
</body>
</html>