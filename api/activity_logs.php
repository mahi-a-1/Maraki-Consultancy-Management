<?php
// api/activity_logs.php
// MNTHC-56: Create activity monitoring page
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once "config/database.php";

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Admin: get all logs; user: get own logs
    $userId = $_GET['user_id'] ?? null;
    $limit  = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

    if ($userId) {
        $query = "SELECT l.*, u.full_name, u.role
                  FROM activity_logs l
                  LEFT JOIN users u ON l.user_id = u.user_id
                  WHERE l.user_id = :uid
                  ORDER BY l.created_at DESC
                  LIMIT :limit OFFSET :offset";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":uid", $userId);
    } else {
        // All logs (admin use)
        $query = "SELECT l.*, u.full_name, u.role
                  FROM activity_logs l
                  LEFT JOIN users u ON l.user_id = u.user_id
                  ORDER BY l.created_at DESC
                  LIMIT :limit OFFSET :offset";
        $stmt = $db->prepare($query);
    }

    $stmt->bindParam(":limit",  $limit,  PDO::PARAM_INT);
    $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
    $stmt->execute();

    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Total count for pagination
    $countQuery = $userId
        ? "SELECT COUNT(*) FROM activity_logs WHERE user_id = :uid"
        : "SELECT COUNT(*) FROM activity_logs";
    $countStmt = $db->prepare($countQuery);
    if ($userId) $countStmt->bindParam(":uid", $userId);
    $countStmt->execute();
    $total = (int)$countStmt->fetchColumn();

    echo json_encode([
        "status" => "success",
        "total"  => $total,
        "limit"  => $limit,
        "offset" => $offset,
        "data"   => $logs
    ]);

} elseif ($method === 'POST') {
    // Log an activity
    $data = json_decode(file_get_contents("php://input"), true);

    if (empty($data['action'])) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "action is required"]);
        exit();
    }

    $userId      = $data['user_id'] ?? null;
    $description = $data['description'] ?? null;
    $ip          = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    $query = "INSERT INTO activity_logs (user_id, action, description, ip_address)
              VALUES (:uid, :action, :desc, :ip)";
    $stmt  = $db->prepare($query);
    $stmt->bindParam(":uid",    $userId);
    $stmt->bindParam(":action", $data['action']);
    $stmt->bindParam(":desc",   $description);
    $stmt->bindParam(":ip",     $ip);

    if ($stmt->execute()) {
        http_response_code(201);
        echo json_encode([
            "status"  => "success",
            "message" => "Activity logged",
            "data"    => ["log_id" => $db->lastInsertId()]
        ]);
    } else {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Failed to log activity"]);
    }

} else {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
}
?>
