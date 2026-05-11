<?php
// api/login.php
// MNTHC-32: Design form - login endpoint
// MNTHC-64: Redirect user - role-based redirect on login - completed
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
    exit();
}

require_once "config/database.php";
require_once "functions/validation.php";

$data = json_decode(file_get_contents("php://input"), true);

$errors = [];

if (!isset($data['email']) || !validateEmail($data['email'])) {
    $errors['email'] = "Valid email is required";
}

if (!isset($data['password']) || empty($data['password'])) {
    $errors['password'] = "Password is required";
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode([
        "status"  => "error",
        "message" => "Validation failed",
        "errors"  => $errors
    ]);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$query = "SELECT user_id, full_name, email, password_hash, role FROM users WHERE email = :email";
$stmt  = $db->prepare($query);
$stmt->bindParam(":email", $data['email']);
$stmt->execute();

if ($stmt->rowCount() === 0) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Invalid email or password"]);
    exit();
}

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!password_verify($data['password'], $user['password_hash'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Invalid email or password"]);
    exit();
}

http_response_code(200);

// Role-based redirect mapping
$redirectMap = [
    'patient' => 'dashboard/patient',
    'doctor'  => 'dashboard/doctor',
    'admin'   => 'dashboard/admin',
];

echo json_encode([
    "status"  => "success",
    "message" => "Login successful",
    "data"    => [
        "user_id"   => $user['user_id'],
        "full_name" => $user['full_name'],
        "email"     => $user['email'],
        "role"      => $user['role'],
        "redirect"  => $redirectMap[$user['role']]
    ]
]);
?>
