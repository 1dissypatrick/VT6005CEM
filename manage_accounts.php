<?php
require_once 'functions.php';
checkRole('admin');

// Admin can view all users
$stmt = $pdo->query("SELECT id, username, role FROM users");
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Accounts</title>
</head>
<body>
    <h2>Manage Accounts</h2>
    <h3>Manage Users</h3>
    <table border="1">
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Role</th>
        </tr>
        <?php foreach ($users as $user) { ?>
            <tr>
                <td><?php echo $user['id']; ?></td>
                <td><?php echo htmlspecialchars($user['username']); ?></td>
                <td><?php echo $user['role']; ?></td>
            </tr>
        <?php } ?>
    </table>
    <a href="dashboard.php">Back to Dashboard</a>
</body>
</html>