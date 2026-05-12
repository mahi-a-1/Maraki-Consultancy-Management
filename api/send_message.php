<?php
// api/send_message.php
// MNTHC-52: Implement reply feature - send message endpoint
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

if (!isset($data['sender_id']) || !is_numeric($data['sender_id'])) {
    $errors['sender_id'] = "Valid sender ID is required";
}

if (!isset($data['recipient_id']) || !is_numeric($data['recipient_id'])) {
    $errors['recipient_id'] = "Valid recipient ID is required";
}

if (!isset($data['subject']) || empty(trim($data['subject']))) {
    $errors['subject'] = "Subject is required";
}

if (!isset($data['message_text']) || empty(trim($data['message_text']))) {
    $errors['message_text'] = "Message text is required";
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

$query = "INSERT INTO messages (sender_id, recipient_id, subject, message_text)
          VALUES (:sender_id, :recipient_id, :subject, :message_text)";

$stmt = $db->prepare($query);
$stmt->bindParam(":sender_id", $data['sender_id']);
$stmt->bindParam(":recipient_id", $data['recipient_id']);
$stmt->bindParam(":subject", $data['subject']);
$stmt->bindParam(":message_text", $data['message_text']);

if ($stmt->execute()) {
    http_response_code(201);
    echo json_encode([
        "status" => "success",
        "message" => "Message sent successfully",
        "data" => [
            "message_id" => $db->lastInsertId()
        ]
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Failed to send message"
    ]);
}
?>