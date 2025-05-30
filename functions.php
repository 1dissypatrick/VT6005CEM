<?php
require_once 'config.php';

// Input sanitization
function sanitizeInput($input) {
    $input = trim($input); // Remove whitespace
    $input = strip_tags($input); // Remove HTML tags to prevent XSS
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8'); // Escape special characters
    return $input;
}

// Validate username (starts with a letter, alphanumeric, 6-20 characters)
function validateUsername($username) {
    return preg_match('/^[a-zA-Z][a-zA-Z0-9]{5,19}$/', $username);
}

// Validate password (at least 8 characters, 1 uppercase, 1 lowercase, 1 number, 1 special character)
function validatePassword($password) {
    return preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[!@#$%^&*()])[A-Za-z0-9!@#$%^&*()]{8,}$/', $password);
}

// Validate HKID format (e.g., A123456(7))
function validateHKID($hkid) {
    return preg_match('/^[A-Z]{1,2}[0-9]{6}\([0-9A]\)$/', $hkid);
}

// Validate Chinese name (basic check for Chinese characters)
function validateChineseName($name) {
    return preg_match('/^[\p{Han}]+$/u', $name);
}

// Validate date of birth (must be a valid date and user must be at least 16 years old)
function validateDateOfBirth($dob) {
    $date = DateTime::createFromFormat('Y-m-d', $dob);
    if (!$date || $date->format('Y-m-d') !== $dob) {
        return false;
    }
    $today = new DateTime();
    $age = $today->diff($date)->y;
    return $age >= 16;
}

// Validate address (alphanumeric, spaces, and common punctuation, 10-100 characters)
function validateAddress($address) {
    return preg_match('/^[A-Za-z0-9\s,.#-]{10,300}$/', $address);
}

// Validate place of birth (alphanumeric, spaces, and common punctuation, 2-50 characters)
function validatePlaceOfBirth($place) {
    return preg_match('/^[A-Za-z\s,.]{2,50}$/', $place);
}

// Validate occupation (alphanumeric, spaces, and common punctuation, 2-50 characters)
function validateOccupation($occupation) {
    return preg_match('/^[A-Za-z\s,.]{2,50}$/', $occupation);
}

function validateTOTPCode($code) {
    return preg_match('/^\d{6}$/', $code);
}

// Check user role for RBAC
function checkRole($requiredRole) {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== $requiredRole) {
        header('Location: login.php');
        exit;
    }
}

// Verify TOTP code for MFA
function verifyTOTP($secret, $code) {
    global $ga;
    return $ga->verifyCode($secret, $code, 2); // 2 = 60-second window
}
?>