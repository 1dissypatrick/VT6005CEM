<?php
require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];
    $totp_code = $_POST['totp_code'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash']) && verifyTOTP($user['totp_secret'], $totp_code)) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['mfa_setup'] = true;
        header('Location: dashboard.php');
        exit;
    } else {
        $error = "Invalid username, password, or TOTP code.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
</head>
<body>
    <h2>Login</h2>
    <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
    <form method="POST">
        <label>Username: <input type="text" name="username" required></label><br>
        <label>Password: <input type="password" name="password" required></label><br>
        <label>TOTP Code: <input type="text" name="totp_code" required></label><br>
        <button type="submit">Login</button>
    </form>
    <p>Don't have a TOTP code? <a href="setup_mfa.php"><button>Setup MFA First</button></a></p>
</body>
</html>