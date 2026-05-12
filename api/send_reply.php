<?php
// api/send_reply.php
// MNTHC-52: Implement reply feature - send reply endpoint
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

$data = json_decode(file_get_contents("php://input"), true);

$errors = [];

if (!isset($data['message_id']) || !is_numeric($data['message_id'])) {
    $errors['message_id'] = "Valid message ID is required";
}

if (!isset($data['sender_id']) || !is_numeric($data['sender_id'])) {
    $errors['sender_id'] = "Valid sender ID is required";
}

if (!isset($data['reply_text']) || empty(trim($data['reply_text']))) {
    $errors['reply_text'] = "Reply text is required";
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Validation failed",
        "errors" => $errors
    ]);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$query = "INSERT INTO message_replies (message_id, sender_id, reply_text)
          VALUES (:message_id, :sender_id, :reply_text)";

$stmt = $db->prepare($query);
$stmt->bindParam(":message_id", $data['message_id']);
$stmt->bindParam(":sender_id", $data['sender_id']);
$stmt->bindParam(":reply_text", $data['reply_text']);

if ($stmt->execute()) {
    http_response_code(201);
    echo json_encode([
        "status" => "success",
        "message" => "Reply sent successfully",
        "data" => [
            "reply_id" => $db->lastInsertId()
        ]
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Failed to send reply"
    ]);
}
?>