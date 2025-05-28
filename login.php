<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'C:/xampp/secure/encryption_key.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid CSRF token. Please try again.";
    } else {
        $username = sanitizeInput($_POST['username']);
        $password = $_POST['password'];
        $totp_code = $_POST['totp_code'];

        if (!validateTOTPCode($totp_code)) {
            $error = "Invalid TOTP code (must be a 6-digit number).";
        } else {
            $stmt = $pdo->prepare("SELECT id, username, CAST(AES_DECRYPT(FROM_BASE64(password_hash), UNHEX(?)) AS CHAR) AS password_hash_decrypted, role, totp_secret FROM users WHERE username = ?");
            $stmt->execute([bin2hex(ENCRYPTION_KEY), $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash_decrypted']) && verifyTOTP($user['totp_secret'], $totp_code)) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['mfa_setup'] = true;
                header('Location: dashboard.php');
                exit;
            } else {
                $error = "Invalid username, password, or TOTP code.";
            }
        }
    }
    // Regenerate CSRF token after form submission
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Set security headers for public-facing page
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Content-Security-Policy: default-src \'self\'');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
header('Referrer-Policy: no-referrer');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Login - HKID Card Management System</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <h2>Login</h2>
    <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
        <label>Username: <input type="text" name="username" required></label><br><br>
        <label>Password: <input type="password" name="password" required></label><br><br>
        <label>TOTP Code: <input type="text" name="totp_code" required placeholder="6-digit code"></label><br><br>
        <button type="submit">Login</button>
    </form>
    <p>Don't have a TOTP code? <a href="setup_mfa.php"><button>Setup MFA First</button></a></p>
    
    <p><a href="index.php">Back to Home</a></p>
</body>
</html>