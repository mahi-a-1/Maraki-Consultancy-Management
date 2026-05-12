<?php
// api/test_db.php
// MNTHC-47: Connect database - test database connection
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

require_once "config/database.php";

$database = new Database();
$db = $database->getConnection();

if ($db) {
    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "message" => "Database connection successful"
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Database connection failed"
    ]);
}
?>