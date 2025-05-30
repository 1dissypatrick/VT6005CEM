<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Generate CAPTCHA code if not set
if (!isset($_SESSION['captcha_code'])) {
    $_SESSION['captcha_code'] = substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 6);
}

// Display the CAPTCHA code as plain text
?>
<!DOCTYPE html>
<html>
<head>
    <title>CAPTCHA Code</title>
    <meta charset="UTF-8">
    <style>
        .captcha-display {
            font-family: monospace;
            font-size: 24px;
            background-color: #f0f0f0;
            padding: 15px;
            display: inline-block;
            border: 2px solid #ccc;
            border-radius: 5px;
            margin: 20px;
        }
    </style>
</head>
<body>
    <h2>Your Verification Code</h2>
    <div class="captcha-display"><?php echo htmlspecialchars($_SESSION['captcha_code']); ?></div>
    <p>Please copy this code and return to the appointment form to enter it.</p>
    <p><a href="public_book_appointment.php">Back to Appointment Form</a></p>
</body>
</html>