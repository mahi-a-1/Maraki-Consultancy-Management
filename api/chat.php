<?php
require_once "../backend/config/database.php";

header("Content-Type: application/json");

$conn = Database::connect();

$action = $_GET['action'] ?? '';

// ============================
// SEND MESSAGE
// ============================
if ($action === "send") {

    $sender_id = $_POST['sender_id'] ?? null;
    $receiver_id = $_POST['receiver_id'] ?? null;
    $message = $_POST['message'] ?? null;

    if (!$sender_id || !$receiver_id || !$message) {
        echo json_encode([
            "success" => false,
            "message" => "All fields required"
        ]);
        exit;
    }

    $stmt = $conn->prepare("
        INSERT INTO chat_messages (sender_id, receiver_id, message)
        VALUES (?, ?, ?)
    ");

    $result = $stmt->execute([$sender_id, $receiver_id, $message]);

    echo json_encode([
        "success" => $result,
        "message" => "Message sent"
    ]);

    exit;
}

// ============================
// GET MESSAGES BETWEEN USERS
// ============================
if ($action === "get") {

    $user1 = $_GET['user1'] ?? null;
    $user2 = $_GET['user2'] ?? null;

    if (!$user1 || !$user2) {
        echo json_encode([
            "success" => false,
            "message" => "Users required"
        ]);
        exit;
    }

    $stmt = $conn->prepare("
        SELECT * FROM chat_messages 
        WHERE (sender_id=? AND receiver_id=?)
        OR (sender_id=? AND receiver_id=?)
        ORDER BY created_at ASC
    ");

    $stmt->execute([$user1, $user2, $user2, $user1]);

    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "data" => $messages
    ]);

    exit;
}

// DEFAULT
echo json_encode([
    "success" => false,
    "message" => "Invalid action"
]);
?>
