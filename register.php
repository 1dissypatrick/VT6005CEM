<?php
require_once 'functions.php';
require_once 'C:/xampp/secure/encryption_key.php'; // Windows path
checkRole('admin');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password']; // Don't sanitize password to preserve integrity
    $role = sanitizeInput($_POST['role']);

    // Validate inputs
    if (empty($username) || !validateUsername($username)) {
        $error = "Username must start with a letter, be alphanumeric, and be 6-20 characters long.";
    } elseif (empty($password) || !validatePassword($password)) {
        $error = "Password must be at least 8 characters long, with 1 uppercase, 1 lowercase, 1 number, and 1 special character.";
    } elseif (!in_array($role, ['junior', 'approving', 'admin'])) {
        $error = "Invalid role.";
    } else {
        // Check if username already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            $error = "Username '$username' is already taken. Please choose a different username.";
        } else {
            // Generate Argon2 hash
            $password_hash = password_hash($password, PASSWORD_ARGON2ID, [
                'memory_cost' => 65536,
                'time_cost' => 4,
                'threads' => 2
            ]);
            // Encrypt password_hash
            $password_hash_encrypted = base64_encode($pdo->query("SELECT AES_ENCRYPT('$password_hash', UNHEX('" . bin2hex(ENCRYPTION_KEY) . "'))")->fetchColumn());

            // Generate TOTP secret
            $totp_secret = $ga->createSecret();

            // Store user in database
            $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role, totp_secret) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$username, $password_hash_encrypted, $role, $totp_secret])) {
                $_SESSION['registration_success'] = "User $username registered successfully. They can log in to set up MFA.";
                header('Location: register_result.php');
                exit;
            } else {
                $error = "Registration failed due to a database error.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
</head>
<body>
    <h2>Register New User</h2>
    <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
    <form method="POST">
        <label>Username: <input type="text" name="username" required placeholder="e.g. user123"></label><br><br>
        <label>Password: <input type="password" name="password" required placeholder="e.g. Password123!"></label><br><br>
        <label>Role: 
            <select name="role">
                <option value="junior">Junior Staff</option>
                <option value="approving">Approving Staff</option>
                <option value="admin">Admin</option>
            </select>
        </label><br><br>
        <button type="submit">Register</button>
    </form>
    <br>
    <a href="dashboard.php">Back to dashboard</a>
</body>
</html>