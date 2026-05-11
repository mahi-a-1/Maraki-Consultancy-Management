<?php
// api/notifications.php
// MNTHC-59: Implement notification service
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT");
header("Access-Control-Allow-Headers: Content-Type");

require_once "config/database.php";

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Fetch notifications for a user
    $userId = $_GET['user_id'] ?? null;

    if (!$userId) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "user_id is required"]);
        exit();
    }

    $query = "SELECT * FROM notifications WHERE user_id = :uid ORDER BY created_at DESC LIMIT 50";
    $stmt  = $db->prepare($query);
    $stmt->bindParam(":uid", $userId);
    $stmt->execute();

    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $unreadCount   = 0;
    foreach ($notifications as $n) {
        if ($n['is_read'] == 0) $unreadCount++;
    }

    echo json_encode([
        "status"       => "success",
        "unread_count" => $unreadCount,
        "data"         => $notifications
    ]);

} elseif ($method === 'POST') {
    // Send a notification
    $data = json_decode(file_get_contents("php://input"), true);

    if (empty($data['user_id']) || empty($data['title']) || empty($data['message'])) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "user_id, title, and message are required"]);
        exit();
    }

    $type  = $data['type'] ?? 'system';
    $query = "INSERT INTO notifications (user_id, title, message, type) VALUES (:uid, :title, :msg, :type)";
    $stmt  = $db->prepare($query);
    $stmt->bindParam(":uid",   $data['user_id']);
    $stmt->bindParam(":title", $data['title']);
    $stmt->bindParam(":msg",   $data['message']);
    $stmt->bindParam(":type",  $type);

    if ($stmt->execute()) {
        http_response_code(201);
        echo json_encode([
            "status"  => "success",
            "message" => "Notification sent",
            "data"    => ["notification_id" => $db->lastInsertId()]
        ]);
    } else {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Failed to send notification"]);
    }

} elseif ($method === 'PUT') {
    // Mark notification(s) as read
    $data = json_decode(file_get_contents("php://input"), true);

    if (!empty($data['notification_id'])) {
        $query = "UPDATE notifications SET is_read = 1 WHERE notification_id = :id";
        $stmt  = $db->prepare($query);
        $stmt->bindParam(":id", $data['notification_id']);
    } elseif (!empty($data['user_id'])) {
        // Mark all as read for a user
        $query = "UPDATE notifications SET is_read = 1 WHERE user_id = :id";
        $stmt  = $db->prepare($query);
        $stmt->bindParam(":id", $data['user_id']);
    } else {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "notification_id or user_id is required"]);
        exit();
    }

    $stmt->execute();
    echo json_encode(["status" => "success", "message" => "Notifications marked as read"]);

} else {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
}
?>
