<?php
// api/activity_logs.php
// MNTHC-56: Create activity monitoring page - completed - author: Abenezer Andualem
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once "config/database.php";
require_once "functions/helpers.php";

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $userId = $_GET['user_id'] ?? null;
    $action = isset($_GET['action']) ? sanitize($_GET['action']) : null;
    $from   = isset($_GET['from'])   ? sanitize($_GET['from'])   : null;
    $to     = isset($_GET['to'])     ? sanitize($_GET['to'])     : null;

    [$limit, $offset] = getPagination();

    $where  = "1=1";
    $params = [];

    if ($userId) { $where .= " AND l.user_id = :uid";           $params[":uid"]    = $userId; }
    if ($action) { $where .= " AND l.action LIKE :action";      $params[":action"] = "%$action%"; }
    if ($from)   { $where .= " AND l.created_at >= :from";      $params[":from"]   = $from; }
    if ($to)     { $where .= " AND l.created_at <= :to";        $params[":to"]     = $to; }

    $countStmt = $db->prepare("SELECT COUNT(*) FROM activity_logs l WHERE $where");
    foreach ($params as $k => $v) $countStmt->bindValue($k, $v);
    $countStmt->execute();
    $total = (int)$countStmt->fetchColumn();

    $query = "SELECT l.*, u.full_name, u.role
              FROM activity_logs l
              LEFT JOIN users u ON l.user_id = u.user_id
              WHERE $where
              ORDER BY l.created_at DESC
              LIMIT :limit OFFSET :offset";

    $stmt = $db->prepare($query);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(":limit",  $limit,  PDO::PARAM_INT);
    $stmt->bindValue(":offset", $offset, PDO::PARAM_INT);
    $stmt->execute();

    http_response_code(200);
    echo json_encode(array_merge(
        ["status" => "success"],
        paginatedResponse($stmt->fetchAll(PDO::FETCH_ASSOC), $total, $limit, $offset)
    ));

} elseif ($method === 'POST') {
    $raw  = json_decode(file_get_contents("php://input"), true) ?? [];
    $data = sanitizeInput($raw);

    if (empty($data['action'])) respond(400, "error", "action is required");

    $userId      = $data['user_id'] ?? null;
    $description = $data['description'] ?? null;
    $ip          = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (:uid, :action, :desc, :ip)");
    $stmt->bindParam(":uid",    $userId);
    $stmt->bindParam(":action", $data['action']);
    $stmt->bindParam(":desc",   $description);
    $stmt->bindParam(":ip",     $ip);

    if ($stmt->execute()) {
        respond(201, "success", "Activity logged", ["log_id" => $db->lastInsertId()]);
    } else {
        respond(500, "error", "Failed to log activity");
    }

} else {
    respond(405, "error", "Method not allowed");
}
?>
