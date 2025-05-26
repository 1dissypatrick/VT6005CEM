<?php
require_once 'functions.php';
require_once 'C:/xampp/secure/encryption_key.php'; // Windows path
checkRole('junior'); // Also allowed for admin

// Fetch available venues
$stmt = $pdo->query("SELECT id, name FROM venues");
$venues = $stmt->fetchAll();

// Sample available dates and timeslots
$available_dates = ['2025-06-01', '2025-06-02', '2025-06-03'];
$available_timeslots = ['09:00', '10:00', '11:00', '14:00', '15:00', '16:00'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $english_name = sanitizeInput($_POST['english_name']);
    $chinese_name = sanitizeInput($_POST['chinese_name']);
    $gender = sanitizeInput($_POST['gender']);
    $date_of_birth = sanitizeInput($_POST['date_of_birth']);
    $address = sanitizeInput($_POST['address']);
    $place_of_birth = sanitizeInput($_POST['place_of_birth']);
    $occupation = sanitizeInput($_POST['occupation']);
    $hkid = sanitizeInput($_POST['hkid']);
    $purpose = sanitizeInput($_POST['purpose']);
    $appointment_date = sanitizeInput($_POST['appointment_date']);
    $appointment_time = sanitizeInput($_POST['appointment_time']);
    $venue_id = (int)$_POST['venue_id'];
    $email = sanitizeInput($_POST['email']);
    $is_special_case = isset($_POST['special_case']); // Check if special case button was clicked

    // Validate inputs
    if (empty($english_name) || !preg_match('/^[A-Za-z\s]+$/', $english_name)) {
        $error = "Invalid English name.";
    } elseif (empty($chinese_name) || !validateChineseName($chinese_name)) {
        $error = "Invalid Chinese name.";
    } elseif (!in_array($gender, ['M', 'F', 'Other'])) {
        $error = "Invalid gender.";
    } elseif (!validateDateOfBirth($date_of_birth)) {
        $error = "Invalid date of birth or applicant is under 16 years old.";
    } elseif (empty($address)) {
        $error = "Address is required.";
    } elseif (empty($place_of_birth)) {
        $error = "Place of birth is required.";
    } elseif (empty($occupation)) {
        $error = "Occupation is required.";
    } elseif (!validateHKID($hkid)) {
        $error = "Invalid HKID format.";
    } elseif (!in_array($purpose, ['application', 'replacement'])) {
        $error = "Invalid purpose.";
    } elseif (!in_array($appointment_date, $available_dates)) {
        $error = "Invalid appointment date.";
    } elseif (!in_array($appointment_time, $available_timeslots)) {
        $error = "Invalid appointment time.";
    } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address.";
    } else {
        // Securely validate venue_id using a prepared statement
        $stmt = $pdo->prepare("SELECT id FROM venues WHERE id = ?");
        $stmt->execute([$venue_id]);
        if (!$stmt->fetch()) {
            $error = "Invalid venue.";
        } else {
            // Encrypt sensitive data
            $hkid_encrypted = base64_encode($pdo->query("SELECT AES_ENCRYPT('$hkid', UNHEX('" . bin2hex(ENCRYPTION_KEY) . "'))")->fetchColumn());
            $email_encrypted = base64_encode($pdo->query("SELECT AES_ENCRYPT('$email', UNHEX('" . bin2hex(ENCRYPTION_KEY) . "'))")->fetchColumn());

            // Determine status based on button clicked
            $status = $is_special_case ? 'pending' : 'approved';

            // Insert appointment into the database (using parameterized query)
            $stmt = $pdo->prepare("INSERT INTO appointments (user_id, english_name, chinese_name, gender, date_of_birth, address, place_of_birth, occupation, hkid, purpose, appointment_date, appointment_time, venue_id, email, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$_SESSION['user_id'], $english_name, $chinese_name, $gender, $date_of_birth, $address, $place_of_birth, $occupation, $hkid_encrypted, $purpose, $appointment_date, $appointment_time, $venue_id, $email_encrypted, $status])) {
                // If approved, send confirmation email
                if ($status === 'approved') {
                    $venue_stmt = $pdo->prepare("SELECT name FROM venues WHERE id = ?");
                    $venue_stmt->execute([$venue_id]);
                    $venue = $venue_stmt->fetchColumn();

                    $email_subject = "HKID Appointment Confirmation";
                    $email_body = "Dear $english_name,\n\nYour HKID appointment has been approved:\nDate: $appointment_date\nTime: $appointment_time\nVenue: $venue\nPurpose: $purpose\n\nPlease arrive 10 minutes early with all required documents.\n\nBest regards,\nHKID Appointment System";

                    // Log the email (simulating sending)
                    $log_message = "Email Sent (immediate):\nTo: $email\nSubject: $email_subject\nBody:\n$email_body\n\n";
                    file_put_contents('email_reminders.log', $log_message, FILE_APPEND);

                    $success = "Appointment booked and confirmed! A confirmation email has been sent.";
                } else {
                    $success = "Special case appointment submitted for review!";
                }
            } else {
                $error = "Failed to book appointment.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Book HKID Appointment</title>
    <meta charset="UTF-8">
</head>
<body>
    <h2>Book HKID Appointment</h2>
    <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
    <?php if (isset($success)) echo "<p style='color:green;'>$success</p>"; ?>
    <form method="POST">
        <label>Purpose: 
            <select name="purpose" required>
                <option value="application">New Application</option>
                <option value="replacement">Replacement</option>
            </select>
        </label><br>
        <label>English Name: <input type="text" name="english_name" required></label><br>
        <label>Chinese Name: <input type="text" name="chinese_name" required></label><br>
        <label>Gender: 
            <select name="gender" required>
                <option value="M">Male</option>
                <option value="F">Female</option>
                <option value="Other">Other</option>
            </select>
        </label><br>
        <label>Date of Birth: <input type="date" name="date_of_birth" required></label><br>
        <label>Address: <textarea name="address" required></textarea></label><br>
        <label>Place of Birth: <input type="text" name="place_of_birth" required></label><br>
        <label>Occupation: <input type="text" name="occupation" required></label><br>
        <label>HKID: <input type="text" name="hkid" required placeholder="e.g., A123456(7)"></label><br>
        <label>Email: <input type="email" name="email" required placeholder="e.g., user@example.com"></label><br>
        <label>Appointment Date: 
            <select name="appointment_date" required>
                <?php foreach ($available_dates as $date) { ?>
                    <option value="<?php echo $date; ?>"><?php echo $date; ?></option>
                <?php } ?>
            </select>
        </label><br>
        <label>Appointment Time: 
            <select name="appointment_time" required>
                <?php foreach ($available_timeslots as $time) { ?>
                    <option value="<?php echo $time; ?>"><?php echo $time; ?></option>
                <?php } ?>
            </select>
        </label><br>
        <label>Venue: 
            <select name="venue_id" required>
                <?php foreach ($venues as $venue) { ?>
                    <option value="<?php echo $venue['id']; ?>"><?php echo htmlspecialchars($venue['name']); ?></option>
                <?php } ?>
            </select>
        </label><br>
        <button type="submit" name="book_appointment">Book Appointment</button>
        <button type="submit" name="special_case">Submit as Special Case</button>
    </form>
    <a href="dashboard.php">Back to Dashboard</a>
</body>
</html>