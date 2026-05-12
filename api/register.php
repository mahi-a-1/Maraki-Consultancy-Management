<?php
// api/register.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once "config/database.php";
require_once "functions/validation.php";
require_once "functions/helpers.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, "error", "Method not allowed");
}

$raw  = json_decode(file_get_contents("php://input"), true) ?? [];
$data = sanitizeInput($raw);

$errors = [];

if (!isset($data['full_name']) || !validateFullName($data['full_name'])) {
    $errors['full_name'] = "Full name is required (min 2 characters)";
}
if (!isset($data['email']) || !validateEmail($data['email'])) {
    $errors['email'] = "Valid email is required";
}
if (!isset($raw['password']) || !validatePassword($raw['password'])) {
    $errors['password'] = "Password must be at least 8 characters";
}
if (!isset($data['phone_number']) || !validatePhoneNumber($data['phone_number'])) {
    $errors['phone_number'] = "Valid phone number (09xxxxxxxx) is required";
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Validation failed", "errors" => $errors]);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$checkStmt = $db->prepare("SELECT user_id FROM users WHERE email = :email");
$checkStmt->bindParam(":email", $data['email']);
$checkStmt->execute();

if ($checkStmt->rowCount() > 0) {
    logActivity($db, null, "register_failed", "Duplicate email: {$data['email']}");
    respond(409, "error", "Email already registered");
}

$password_hash = password_hash($raw['password'], PASSWORD_BCRYPT);

$stmt = $db->prepare("INSERT INTO users (full_name, email, password_hash, phone_number) VALUES (:full_name, :email, :password_hash, :phone_number)");
$stmt->bindParam(":full_name",     $data['full_name']);
$stmt->bindParam(":email",         $data['email']);
$stmt->bindParam(":password_hash", $password_hash);
$stmt->bindParam(":phone_number",  $data['phone_number']);

if ($stmt->execute()) {
    $userId = $db->lastInsertId();
    logActivity($db, (int)$userId, "register", "New user registered: {$data['email']}");
    respond(201, "success", "User account created successfully", [
        "user_id"   => $userId,
        "full_name" => $data['full_name'],
        "email"     => $data['email'],
        "role"      => "patient"
    ]);
} else {
    respond(500, "error", "Registration failed, please try again");
}
?>
