<?php
// Validation rules — MNTHC-57

function validateFullName($name) {
    if (empty($name)) return "Full name is required";
    if (strlen($name) < 2) return "Full name must be at least 2 characters";
    if (strlen($name) > 100) return "Full name must be under 100 characters";
    return null;
}

function validateEmail($email) {
    if (empty($email)) return "Email is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return "Enter a valid email address";
    return null;
}

function validatePassword($password) {
    if (empty($password)) return "Password is required";
    if (strlen($password) < 8) return "Password must be at least 8 characters";
    if (!preg_match('/[A-Z]/', $password)) return "Password must contain at least one uppercase letter";
    if (!preg_match('/[0-9]/', $password)) return "Password must contain at least one number";
    return null;
}

function validatePhoneNumber($phone) {
    if (empty($phone)) return null; // optional
    if (!preg_match('/^09[0-9]{8}$/', $phone)) return "Phone must be in format 09xxxxxxxx";
    return null;
}

function validateDate($date) {
    if (empty($date)) return "Date is required";
    if (strtotime($date) < strtotime('today')) return "Date cannot be in the past";
    return null;
}

function validateTime($time) {
    if (empty($time)) return "Time is required";
    $hour = (int) date('H', strtotime($time));
    if ($hour < 8 || $hour >= 18) return "Appointment time must be between 08:00 and 18:00";
    return null;
}

/**
 * Run multiple validators and collect errors.
 * $schema: ['field' => fn($value)]
 * Returns array of errors (empty if all pass)
 */
function validateSchema($data, $schema) {
    $errors = [];
    foreach ($schema as $field => $fn) {
        $error = $fn($data[$field] ?? null);
        if ($error !== null) {
            $errors[$field] = $error;
        }
    }
    return $errors;
}
?>