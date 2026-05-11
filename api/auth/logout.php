<?php
// Destroy session — MNTHC-63
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

require_once "../config/database.php";

// Extract token from Authorization header
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';

if (!str_starts_with($authHeader, 'Bearer ')) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "No token provided"]);
    exit();
}

$token = substr($authHeader, 7);

// Invalidate token in DB (blacklist)
$database = new Database();
$db = $database->getConnection();

$stmt = $db->prepare("INSERT INTO token_blacklist (token, invalidated_at) VALUES (:token, NOW())");
$stmt->bindParam(':token', $token);

if ($stmt->execute()) {
    http_response_code(200);
    echo json_encode([
        "status"  => "success",
        "message" => "Session destroyed successfully"
    ]);
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Failed to destroy session"]);
}
?>
