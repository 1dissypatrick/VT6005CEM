<?php
require_once 'functions.php';
require_once 'config.php';
require_once 'C:/xampp/secure/encryption_key.php';
require_once 'vendor/autoload.php'; // Include Composer autoloader
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate CSRF token for form security if not set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Generate CAPTCHA code if not set
if (!isset($_SESSION['captcha_code'])) {
    $_SESSION['captcha_code'] = substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 6);
}

// Fetch available venues
$stmt = $pdo->query("SELECT id, name FROM venues");
$venues = $stmt->fetchAll();

// Sample available dates and timeslots
$available_dates = ['2025-06-01', '2025-06-02', '2025-06-03'];
$available_timeslots = ['09:00', '10:00', '11:00', '14:00', '15:00', '16:00'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CAPTCHA
    $user_captcha = isset($_POST['captcha']) ? trim($_POST['captcha']) : '';
    if (strtoupper($user_captcha) !== strtoupper($_SESSION['captcha_code'])) {
        $error = "Invalid CAPTCHA code. Please try again.";
    } else {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $error = "Invalid CSRF token. Please try again.";
        } else {
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

            // Validate inputs
            if (empty($english_name) || !preg_match('/^[A-Za-z\s]+$/', $english_name)) {
                $error = "Invalid English name.";
            } elseif (empty($chinese_name) || !validateChineseName($chinese_name)) {
                $error = "Invalid Chinese name.";
            } elseif (!in_array($gender, ['M', 'F', 'Other'])) {
                $error = "Invalid gender.";
            } elseif (!validateDateOfBirth($date_of_birth)) {
                $error = "Invalid date of birth or applicant is under 16 years old.";
            } elseif (empty($address) || !validateAddress($address)) {
                $error = "Invalid address (must be 10-100 characters, alphanumeric with spaces, commas, periods, hashes, or hyphens).";
            } elseif (empty($place_of_birth) || !validatePlaceOfBirth($place_of_birth)) {
                $error = "Invalid place of birth (must be 2-50 characters, letters with spaces, commas, or periods).";
            } elseif (empty($occupation) || !validateOccupation($occupation)) {
                $error = "Invalid occupation (must be 2-50 characters, letters with spaces, commas, or periods).";
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
                // Securely validate venue_id
                $stmt = $pdo->prepare("SELECT id FROM venues WHERE id = ?");
                $stmt->execute([$venue_id]);
                if (!$stmt->fetch()) {
                    $error = "Invalid venue.";
                } else {
                    // Encrypt sensitive data
                    $hkid_encrypted = base64_encode($pdo->query("SELECT AES_ENCRYPT('$hkid', UNHEX('" . bin2hex(ENCRYPTION_KEY) . "'))")->fetchColumn());
                    $email_encrypted = base64_encode($pdo->query("SELECT AES_ENCRYPT('$email', UNHEX('" . bin2hex(ENCRYPTION_KEY) . "'))")->fetchColumn());

                    // Set status to pending for all public submissions
                    $status = 'pending';

                    // Insert appointment into the database with NULL user_id
                    $stmt = $pdo->prepare("INSERT INTO appointments (user_id, english_name, chinese_name, gender, date_of_birth, address, place_of_birth, occupation, hkid, purpose, appointment_date, appointment_time, venue_id, email, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    if ($stmt->execute([null, $english_name, $chinese_name, $gender, $date_of_birth, $address, $place_of_birth, $occupation, $hkid_encrypted, $purpose, $appointment_date, $appointment_time, $venue_id, $email_encrypted, $status])) {
                        $success = "Appointment submitted for review. You will receive a confirmation email once approved.";
                    } else {
                        $error = "Failed to submit appointment.";
                    }
                }
            }
        }
    }
    // Regenerate CSRF token and CAPTCHA code after form submission
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['captcha_code'] = substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 6);
}

// Security headers for public-facing page
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Content-Security-Policy: default-src \'self\'');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
header('Referrer-Policy: no-referrer');
?>

<!DOCTYPE html>
<html>
<head>
    <title>Book HKID Appointment (Public)</title>
    <meta charset="UTF-8">
    <style>
        .captcha-link {
            margin: 10px 0;
            text-decoration: underline;
            color: blue;
            cursor: pointer;
            display: inline-block;
        }
        .captcha-link:hover {
            color: darkblue;
        }
    </style>
</head>
<body>
    <h2>Book HKID Appointment</h2>
    <p>Please fill out the form below to submit an appointment request. Your request will be reviewed, and you will receive a confirmation email once approved.</p>
    <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
    <?php if (isset($success)) echo "<p style='color:green;'>$success</p>"; ?>
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
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
        <!-- CAPTCHA Section -->
        <label>
            Verification Code (驗證碼): 
            <br>
            <a href="captcha_image.php" target="_blank" class="captcha-link">Get Verification Code</a>
            <p>
            <input type="text" name="captcha" required placeholder="Enter the code from the link">
        </label><p>
        <button type="submit" name="book_appointment">Submit Appointment Request</button>
        <p><a href="index.php">Back to Home</a></p>
    </form>
</body>
</html>