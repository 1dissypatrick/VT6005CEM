<?php
require_once 'functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
</head>
<body>
    <h2>Welcome, <?php echo htmlspecialchars($_SESSION['role']); ?>!</h2>
    <ul>
        <?php if ($_SESSION['role'] === 'junior') { ?>
            <li><a href="book_appointment1.php">Book Appointment</a></li>
            <li><a href="change_password.php">Change Password</a></li>
        <?php } ?>
        <?php if ($_SESSION['role'] === 'approving') { ?>
            <li><a href="manage_appointments.php">Manage Appointments</a></li>
            <li><a href="change_password.php">Change Password</a></li>
        <?php } ?>
        <?php if ($_SESSION['role'] === 'admin') { ?>
            <li><a href="manage_accounts.php">Manage Accounts</a></li>
            <li><a href="register.php">Create Accounts</a></li>
            <li><a href="change_password.php">Change Password</a></li>
        <?php } ?>
        <li><a href="logout.php">Logout</a></li>
    </ul>
</body>
</html>