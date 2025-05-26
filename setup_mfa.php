<?php
require_once 'functions.php';
require_once 'C:/xampp/secure/encryption_key.php'; // Windows path for encryption key

// Clear any existing session data to ensure fresh setup
if (isset($_SESSION['mfa_setup'])) {
    unset($_SESSION['mfa_setup']);
}
if (isset($_SESSION['user_id'])) {
    unset($_SESSION['user_id']);
}

$step = isset($_SESSION['mfa_step']) ? $_SESSION['mfa_step'] : 1;
$qrCodeUrl = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step == 1) {
        $username = sanitizeInput($_POST['username']);
        $password = $_POST['password'];

        if (!validateUsername($username)) {
            $error = "Username must start with a letter, be alphanumeric, and be 6-20 characters long.";
        } else {
            $stmt = $pdo->prepare("SELECT id, username, CAST(AES_DECRYPT(FROM_BASE64(password_hash), UNHEX(?)) AS CHAR) AS password_hash_decrypted, totp_secret FROM users WHERE username = ?");
            $stmt->execute([bin2hex(ENCRYPTION_KEY), $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash_decrypted'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['totp_secret'] = $user['totp_secret'];
                $_SESSION['mfa_step'] = 2;
                header('Location: setup_mfa.php');
                exit;
            } else {
                $error = "Invalid username or password.";
            }
        }
    } elseif ($step == 2) {
        $username = sanitizeInput($_POST['username']);
        $password = $_POST['password'];
        $totp_code = $_POST['totp_code'];

        if (!validateUsername($username)) {
            $error = "Username must start with a letter, be alphanumeric, and be 6-20 characters long.";
        } else {
            $stmt = $pdo->prepare("SELECT id, username, CAST(AES_DECRYPT(FROM_BASE64(password_hash), UNHEX(?)) AS CHAR) AS password_hash_decrypted, totp_secret, role FROM users WHERE username = ?");
            $stmt->execute([bin2hex(ENCRYPTION_KEY), $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash_decrypted']) && verifyTOTP($user['totp_secret'], $totp_code)) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['mfa_setup'] = true;
                unset($_SESSION['mfa_step']);
                unset($_SESSION['totp_secret']);
                header('Location: dashboard.php');
                exit;
            } else {
                $error = "Invalid username, password, or TOTP code.";
            }
        }
    }
}

if ($step == 2 && isset($_SESSION['totp_secret'])) {
    $qrCodeUrl = $ga->getQRCodeGoogleUrl($_SESSION['username'], $_SESSION['totp_secret'], 'HKID Appointment');
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>MFA Setup</title>
</head>
<body>
    <h2>MFA Setup</h2>
    <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
    <?php if ($step == 1): ?>
        <p>Please enter your username and password to set up MFA:</p>
        <form method="POST">
            <label>Username: <input type="text" name="username" required placeholder="e.g., user123"></label><br>
            <label>Password: <input type="password" name="password" required></label><br>
            <button type="submit">Submit</button>
        </form>
    <?php elseif ($step == 2): ?>
        <p>Please scan this QR code with Google Authenticator:</p>
        <img src="<?php echo htmlspecialchars($qrCodeUrl); ?>" alt="MFA QR Code">
        <p>Then, re-enter your username, password, and TOTP code to complete setup:</p>
        <form method="POST">
            <label>Username: <input type="text" name="username" required placeholder="e.g., user123"></label><br>
            <label>Password: <input type="password" name="password" required></label><br>
            <label>TOTP Code: <input type="text" name="totp_code" required></label><br>
            <button type="submit">Verify and Complete</button>
        </form>
        <p>Debug QR Code URL: <a href="<?php echo htmlspecialchars($qrCodeUrl); ?>" target="_blank"><?php echo htmlspecialchars($qrCodeUrl); ?></a></p>
    <?php endif; ?>
</body>
</html>