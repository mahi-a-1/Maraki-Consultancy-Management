<?php
// api/services.php
// MNTHC-55: Create service update forms - completed - author: Abenezer Andualem
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type");

require_once "config/database.php";
require_once "functions/helpers.php";

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $serviceId = $_GET['service_id'] ?? null;
    $search    = isset($_GET['search']) ? sanitize($_GET['search']) : null;

    [$limit, $offset] = getPagination();

    if ($serviceId) {
        $stmt = $db->prepare("SELECT * FROM services WHERE service_id = :id AND is_active = 1");
        $stmt->bindParam(":id", $serviceId);
        $stmt->execute();
        $service = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$service) respond(404, "error", "Service not found");
        respond(200, "success", "Service retrieved", $service);
    }

    $where  = "is_active = 1";
    $params = [];

    if ($search) { $where .= " AND (title LIKE :search OR description LIKE :search)"; $params[":search"] = "%$search%"; }

    $countStmt = $db->prepare("SELECT COUNT(*) FROM services WHERE $where");
    foreach ($params as $k => $v) $countStmt->bindValue($k, $v);
    $countStmt->execute();
    $total = (int)$countStmt->fetchColumn();

    $stmt = $db->prepare("SELECT * FROM services WHERE $where ORDER BY title ASC LIMIT :limit OFFSET :offset");
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

    if (empty($data['title'])) respond(400, "error", "title is required");

    $stmt = $db->prepare("INSERT INTO services (title, description, price, duration_minutes) VALUES (:title, :desc, :price, :duration)");
    $stmt->bindParam(":title",    $data['title']);
    $desc = $data['description'] ?? null;
    $stmt->bindParam(":desc",     $desc);
    $price = $data['price'] ?? 0.00;
    $stmt->bindParam(":price",    $price);
    $duration = $data['duration_minutes'] ?? 60;
    $stmt->bindParam(":duration", $duration);

    if ($stmt->execute()) {
        logActivity($db, null, "create_service", "Created service: {$data['title']}");
        respond(201, "success", "Service created", ["service_id" => $db->lastInsertId()]);
    } else {
        respond(500, "error", "Failed to create service");
    }

} elseif ($method === 'PUT') {
    $raw  = json_decode(file_get_contents("php://input"), true) ?? [];
    $data = sanitizeInput($raw);

    if (empty($data['service_id'])) respond(400, "error", "service_id is required");

    $fields = [];
    $params = [":id" => $data['service_id']];

    if (isset($data['title']))            { $fields[] = "title = :title";          $params[":title"]    = $data['title']; }
    if (isset($data['description']))      { $fields[] = "description = :desc";     $params[":desc"]     = $data['description']; }
    if (isset($data['price']))            { $fields[] = "price = :price";          $params[":price"]    = $data['price']; }
    if (isset($data['duration_minutes'])) { $fields[] = "duration_minutes = :dur"; $params[":dur"]      = $data['duration_minutes']; }
    if (isset($data['is_active']))        { $fields[] = "is_active = :active";     $params[":active"]   = $data['is_active']; }

    if (empty($fields)) respond(400, "error", "No fields to update");

    $stmt = $db->prepare("UPDATE services SET " . implode(", ", $fields) . " WHERE service_id = :id");
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->execute();

    logActivity($db, null, "update_service", "Updated service ID: {$data['service_id']}");
    respond(200, "success", "Service updated");

} elseif ($method === 'DELETE') {
    $serviceId = $_GET['service_id'] ?? null;
    if (!$serviceId) respond(400, "error", "service_id is required");

    $stmt = $db->prepare("UPDATE services SET is_active = 0 WHERE service_id = :id");
    $stmt->bindParam(":id", $serviceId);
    $stmt->execute();

    logActivity($db, null, "delete_service", "Deactivated service ID: $serviceId");
    respond(200, "success", "Service deactivated");

} else {
    respond(405, "error", "Method not allowed");
}
?>
