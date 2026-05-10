<?php
// api/register.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once "config/database.php";

// Validation functions
function validateFullName($name) {
    return !empty($name) && strlen($name) >= 2 && strlen($name) <= 100;
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validatePassword($password) {
    return strlen($password) >= 8;
}

function validatePhoneNumber($phone) {
    return preg_match('/^09[0-9]{8}$/', $phone);
}

// Get input data
$data = json_decode(file_get_contents("php://input"), true);

// Validate required fields
$errors = [];

if (!isset($data['full_name']) || !validateFullName($data['full_name'])) {
    $errors['full_name'] = "Full name is required (min 2 characters)";
}

if (!isset($data['email']) || !validateEmail($data['email'])) {
    $errors['email'] = "Valid email is required";
}

if (!isset($data['password']) || !validatePassword($data['password'])) {
    $errors['password'] = "Password must be at least 8 characters";
}

if (!isset($data['phone_number']) || !validatePhoneNumber($data['phone_number'])) {
    $errors['phone_number'] = "Valid phone number (09xxxxxxxx) is required";
}

// Return validation errors if any
if (!empty($errors)) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Validation failed",
        "errors" => $errors
    ]);
    exit();
}

// Connect to database
$database = new Database();
$db = $database->getConnection();

// Check if email already exists
$checkQuery = "SELECT user_id FROM users WHERE email = :email";
$checkStmt = $db->prepare($checkQuery);
$checkStmt->bindParam(":email", $data['email']);
$checkStmt->execute();

if ($checkStmt->rowCount() > 0) {
    http_response_code(409);
    echo json_encode([
        "status" => "error",
        "message" => "Existing email displays error message"
    ]);
    exit();
}

// Hash password
$password_hash = password_hash($data['password'], PASSWORD_BCRYPT);

// Insert user
$query = "INSERT INTO users (full_name, email, password_hash, phone_number) 
          VALUES (:full_name, :email, :password_hash, :phone_number)";

$stmt = $db->prepare($query);
$stmt->bindParam(":full_name", $data['full_name']);
$stmt->bindParam(":email", $data['email']);
$stmt->bindParam(":password_hash", $password_hash);
$stmt->bindParam(":phone_number", $data['phone_number']);

if ($stmt->execute()) {
    http_response_code(201);
    echo json_encode([
        "status" => "success",
        "message" => "User account created successfully",
        "data" => [
            "user_id" => $db->lastInsertId(),
            "full_name" => $data['full_name'],
            "email" => $data['email'],
            "role" => "patient"
        ]
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Registration failed, please try again"
    ]);
}
?>