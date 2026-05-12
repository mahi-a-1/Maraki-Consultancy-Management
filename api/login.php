<?php
// api/login.php
// MNTHC-32: Design form - login endpoint
// MNTHC-64: Redirect user - role-based redirect on login - completed - author: Abenezer Andualem
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

if (!isset($data['email']) || !validateEmail($data['email'])) {
    respond(400, "error", "Valid email is required");
}
if (!isset($raw['password']) || empty($raw['password'])) {
    respond(400, "error", "Password is required");
}

$database = new Database();
$db = $database->getConnection();

$stmt = $db->prepare("SELECT user_id, full_name, email, password_hash, role FROM users WHERE email = :email");
$stmt->bindParam(":email", $data['email']);
$stmt->execute();

if ($stmt->rowCount() === 0) {
    logActivity($db, null, "login_failed", "No account found for: {$data['email']}");
    respond(401, "error", "Invalid email or password");
}

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!password_verify($raw['password'], $user['password_hash'])) {
    logActivity($db, (int)$user['user_id'], "login_failed", "Wrong password for: {$data['email']}");
    respond(401, "error", "Invalid email or password");
}

logActivity($db, (int)$user['user_id'], "login", "User logged in: {$user['email']}");

$redirectMap = [
    'patient' => 'dashboard/patient',
    'doctor'  => 'dashboard/doctor',
    'admin'   => 'dashboard/admin',
];

respond(200, "success", "Login successful", [
    "user_id"   => $user['user_id'],
    "full_name" => $user['full_name'],
    "email"     => $user['email'],
    "role"      => $user['role'],
    "redirect"  => $redirectMap[$user['role']]
]);
?>
