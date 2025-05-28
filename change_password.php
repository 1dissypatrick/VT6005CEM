<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'C:/xampp/secure/encryption_key.php';

//why need to change password?
//since admin know the account
//Following a data breach notification
//After sharing your password
//When a password is simple or commonly used

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Generate CSRF token if not set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid CSRF token. Please try again.";
    } else {
        $username = sanitizeInput($_POST['username']);
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        $totp_code = $_POST['totp_code'];

        // Validate inputs
        if (empty($username) || empty($current_password) || empty($new_password) || empty($confirm_password) || empty($totp_code)) {
            $error = "All fields are required.";
        } elseif ($new_password !== $confirm_password) {
            $error = "New password and confirmation do not match.";
        } elseif (!validatePassword($new_password)) {
            $error = "New password must be at least 8 characters long, with 1 uppercase, 1 lowercase, 1 number, and 1 special character.";
        } elseif (!validateTOTPCode($totp_code)) {
            $error = "Invalid TOTP code (must be a 6-digit number).";
        } else {
            // Fetch current user's data
            $stmt = $pdo->prepare("SELECT id, CAST(AES_DECRYPT(FROM_BASE64(password_hash), UNHEX(?)) AS CHAR) AS password_hash_decrypted, totp_secret FROM users WHERE username = ?");
            $stmt->execute([bin2hex(ENCRYPTION_KEY), $username]);
            $user = $stmt->fetch();

            if ($user && $user['id'] == $_SESSION['user_id'] && password_verify($current_password, $user['password_hash_decrypted']) && verifyTOTP($user['totp_secret'], $totp_code)) {
                // Generate new Argon2 hash
                $new_password_hash = password_hash($new_password, PASSWORD_ARGON2ID, [
                    'memory_cost' => 65536,
                    'time_cost' => 4,
                    'threads' => 2
                ]);
                // Encrypt new password hash
                $new_password_hash_encrypted = base64_encode($pdo->query("SELECT AES_ENCRYPT('$new_password_hash', UNHEX('" . bin2hex(ENCRYPTION_KEY) . "'))")->fetchColumn());

                // Update password in database
                $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                if ($stmt->execute([$new_password_hash_encrypted, $_SESSION['user_id']])) {
                    $success = "Password updated successfully.";
                    // Regenerate CSRF token
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                } else {
                    $error = "Failed to update password due to a database error.";
                }
            } else {
                $error = "Invalid username, current password, or TOTP code.";
            }
        }
    }
    // Regenerate CSRF token after form submission
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Set security headers
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Content-Security-Policy: default-src \'self\'');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
header('Referrer-Policy: no-referrer');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Change Password - HKID Card Management System</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <h2>Change Password</h2>
    <?php
    if (isset($error)) echo "<p style='color:red;'>$error</p>";
    if (isset($success)) echo "<p style='color:green;'>$success</p>";
    ?>
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
        <label>Username: <input type="text" name="username" required placeholder="e.g. user123"></label><br><br>
        <label>Current Password: <input type="password" name="current_password" required></label><br><br>
        <label>New Password: <input type="password" name="new_password" required placeholder="e.g. Password123!"></label><br><br>
        <label>Confirm New Password: <input type="password" name="confirm_password" required placeholder="e.g. Password123!"></label><br><br>
        <label>TOTP Code: <input type="text" name="totp_code" required placeholder="6-digit code"></label><br><br>
        <button type="submit">Change Password</button>
    </form>
    <p><a href="dashboard.php">Back to Dashboard</a></p>
</body>
</html>