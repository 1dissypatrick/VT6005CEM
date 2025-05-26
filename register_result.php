<?php
session_start();

// Check if the session data exists
if (!isset($_SESSION['registration_success'])) {
    header('Location: register.php');
    exit;
}

$success = $_SESSION['registration_success'];

// Clear session data after displaying
unset($_SESSION['registration_success']);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Registration Result</title>
</head>
<body>
    <h2>Registration Result</h2>
    <p style="color:green;"><?php echo $success; ?></p>
    <br>
    <a href="register.php"><button>Return to Register</button></a>
</body>
</html>