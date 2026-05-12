<?php
// api/notifications.php
// MNTHC-59: Implement notification service - completed - author: Abenezer Andualem
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT");
header("Access-Control-Allow-Headers: Content-Type");

require_once "config/database.php";
require_once "functions/helpers.php";

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $userId = $_GET['user_id'] ?? null;
    $type   = isset($_GET['type']) ? sanitize($_GET['type']) : null;
    $unread = isset($_GET['unread']) ? (bool)$_GET['unread'] : false;

    if (!$userId) respond(400, "error", "user_id is required");

    [$limit, $offset] = getPagination();

    $where  = "user_id = :uid";
    $params = [":uid" => $userId];

    if ($type)   { $where .= " AND type = :type";       $params[":type"]   = $type; }
    if ($unread) { $where .= " AND is_read = 0"; }

    $countStmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE $where");
    foreach ($params as $k => $v) $countStmt->bindValue($k, $v);
    $countStmt->execute();
    $total = (int)$countStmt->fetchColumn();

    $stmt = $db->prepare("SELECT * FROM notifications WHERE $where ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(":limit",  $limit,  PDO::PARAM_INT);
    $stmt->bindValue(":offset", $offset, PDO::PARAM_INT);
    $stmt->execute();

    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $unreadCount   = 0;
    foreach ($notifications as $n) { if ($n['is_read'] == 0) $unreadCount++; }

    http_response_code(200);
    echo json_encode(array_merge(
        ["status" => "success", "unread_count" => $unreadCount],
        paginatedResponse($notifications, $total, $limit, $offset)
    ));

} elseif ($method === 'POST') {
    $raw  = json_decode(file_get_contents("php://input"), true) ?? [];
    $data = sanitizeInput($raw);

    if (empty($data['user_id']) || empty($data['title']) || empty($data['message'])) {
        respond(400, "error", "user_id, title, and message are required");
    }

    $type  = $data['type'] ?? 'system';
    $stmt  = $db->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (:uid, :title, :msg, :type)");
    $stmt->bindParam(":uid",   $data['user_id']);
    $stmt->bindParam(":title", $data['title']);
    $stmt->bindParam(":msg",   $data['message']);
    $stmt->bindParam(":type",  $type);

    if ($stmt->execute()) {
        respond(201, "success", "Notification sent", ["notification_id" => $db->lastInsertId()]);
    } else {
        respond(500, "error", "Failed to send notification");
    }

} elseif ($method === 'PUT') {
    $raw  = json_decode(file_get_contents("php://input"), true) ?? [];
    $data = sanitizeInput($raw);

    if (!empty($data['notification_id'])) {
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE notification_id = :id");
        $stmt->bindParam(":id", $data['notification_id']);
    } elseif (!empty($data['user_id'])) {
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = :id");
        $stmt->bindParam(":id", $data['user_id']);
    } else {
        respond(400, "error", "notification_id or user_id is required");
    }

    $stmt->execute();
    respond(200, "success", "Notifications marked as read");

} else {
    respond(405, "error", "Method not allowed");
}
?>
