<?php
// api/logout.php
// MNTHC-35: Create login API - logout endpoint
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
    exit();
}

session_start();

// Destroy the session
session_unset();
session_destroy();

http_response_code(200);
echo json_encode([
    "status" => "success",
    "message" => "Logout successful"
]);
?>