<?php
require_once 'functions.php';
require_once 'C:/xampp/secure/encryption_key.php';
require_once 'vendor/autoload.php'; // Include Composer autoloader
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

checkRole('approving'); // Also allowed for admin

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointment_id = (int)$_POST['appointment_id'];
    $status = sanitizeInput($_POST['status']);

    if (in_array($status, ['approved', 'rejected'])) {
        // Update appointment status
        $stmt = $pdo->prepare("UPDATE appointments SET status = ? WHERE id = ?");
        $stmt->execute([$status, $appointment_id]);

        // Fetch appointment details for email reminder
        $stmt = $pdo->prepare("SELECT a.*, v.name AS venue_name, 
            CAST(AES_DECRYPT(FROM_BASE64(a.hkid), UNHEX(?)) AS CHAR) AS hkid_decrypted,
            CAST(AES_DECRYPT(FROM_BASE64(a.email), UNHEX(?)) AS CHAR) AS email_decrypted
            FROM appointments a JOIN venues v ON a.venue_id = v.id WHERE a.id = ?");
        $stmt->execute([bin2hex(ENCRYPTION_KEY), bin2hex(ENCRYPTION_KEY), $appointment_id]);
        $appt = $stmt->fetch();

        if ($appt) {
            // Prepare email reminder based on status
            $email = $appt['email_decrypted'];
            $english_name = $appt['english_name'];
            $appointment_date = $appt['appointment_date'];
            $appointment_time = $appt['appointment_time'];
            $venue = $appt['venue_name'];
            $purpose = $appt['purpose'];
            $reminder_date = date('Y-m-d', strtotime($appointment_date . ' -2 days'));

            if ($status === 'approved') {
                $email_subject = "HKID Appointment Confirmation";
                $email_body = "Dear $english_name,\n\nYour HKID special case appointment has been approved:\nDate: $appointment_date\nTime: $appointment_time\nVenue: $venue\nPurpose: $purpose\n\nPlease arrive 10 minutes early with all required documents.\n\nBest regards,\nHKID Appointment System";
            } else {
                $email_subject = "HKID Appointment Rejection Notice";
                $email_body = "Dear $english_name,\n\nWe regret to inform you that your HKID special case appointment (Purpose: $purpose, Date: $appointment_date, Time: $appointment_time, Venue: $venue) has been rejected.\nPlease book a new appointment if needed.\n\nBest regards,\nHKID Appointment System";
            }

            // Send email using PHPMailer
            $config = include('C:/xampp/secure/config.php');
            $mail = new PHPMailer(true);
            try {
                // SMTP configuration
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = $config['smtp']['username'];
                $mail->Password = $config['smtp']['password'];  
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                // Email settings
                $mail->setFrom('no-reply@hkid-system.com', 'HKID Appointment System');
                $mail->addAddress($email, $english_name);
                $mail->Subject = $email_subject;
                $mail->Body = $email_body;
                $mail->AltBody = $email_body; // Plain text version

                $mail->send();
                $success = "Appointment updated and email reminder sent!";
            } catch (Exception $e) {
                $error = "Failed to send email: {$mail->ErrorInfo}";
            }
        } else {
            $error = "Failed to fetch appointment details.";
        }
    } else {
        $error = "Invalid status.";
    }
}

// Fetch only pending appointments with decrypted fields
$stmt = $pdo->query("SELECT a.*, v.name AS venue_name, 
    CAST(AES_DECRYPT(FROM_BASE64(a.hkid), UNHEX('" . bin2hex(ENCRYPTION_KEY) . "')) AS CHAR) AS hkid_decrypted,
    CAST(AES_DECRYPT(FROM_BASE64(a.email), UNHEX('" . bin2hex(ENCRYPTION_KEY) . "')) AS CHAR) AS email_decrypted
    FROM appointments a JOIN venues v ON a.venue_id = v.id WHERE a.status = 'pending'");
$appointments = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Special Case Appointments</title>
    <meta charset="UTF-8">
</head>
<body>
    <h2>Manage Special Case Appointments</h2>
    <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
    <?php if (isset($success)) echo "<p style='color:green;'>$success</p>"; ?>
    <table border="1">
        <tr>
            <th>ID</th>
            <th>English Name</th>
            <th>Chinese Name</th>
            <th>HKID</th>
            <th>Email</th>
            <th>Purpose</th>
            <th>Date</th>
            <th>Time</th>
            <th>Venue</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
        <?php foreach ($appointments as $appt) { ?>
            <tr>
                <td><?php echo $appt['id']; ?></td>
                <td><?php echo htmlspecialchars($appt['english_name']); ?></td>
                <td><?php echo htmlspecialchars($appt['chinese_name']); ?></td>
                <td><?php echo htmlspecialchars($appt['hkid_decrypted']); ?></td>
                <td><?php echo htmlspecialchars($appt['email_decrypted']); ?></td>
                <td><?php echo htmlspecialchars($appt['purpose']); ?></td>
                <td><?php echo $appt['appointment_date']; ?></td>
                <td><?php echo $appt['appointment_time']; ?></td>
                <td><?php echo htmlspecialchars($appt['venue_name']); ?></td>
                <td><?php echo $appt['status']; ?></td>
                <td>
                    <form method="POST">
                        <input type="hidden" name="appointment_id" value="<?php echo $appt['id']; ?>">
                        <select name="status">
                            <option value="approved">Approve</option>
                            <option value="rejected">Reject</option>
                        </select>
                        <button type="submit">Update</button>
                    </form>
                </td>
            </tr>
        <?php } ?>
    </table>
    <br>
    <a href="dashboard.php">Back to Dashboard</a>
</body>
</html>