<?php
// api/services.php
// MNTHC-55: Create service update forms
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type");

require_once "config/database.php";

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // MNTHC-41: Create service page - list all active services
    $serviceId = $_GET['service_id'] ?? null;

    if ($serviceId) {
        $query = "SELECT * FROM services WHERE service_id = :id";
        $stmt  = $db->prepare($query);
        $stmt->bindParam(":id", $serviceId);
        $stmt->execute();

        $service = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$service) {
            http_response_code(404);
            echo json_encode(["status" => "error", "message" => "Service not found"]);
            exit();
        }

        echo json_encode(["status" => "success", "data" => $service]);
    } else {
        $query = "SELECT * FROM services WHERE is_active = 1 ORDER BY created_at DESC";
        $stmt  = $db->prepare($query);
        $stmt->execute();

        echo json_encode([
            "status" => "success",
            "data"   => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ]);
    }

} elseif ($method === 'POST') {
    // Create a new service (admin)
    $data = json_decode(file_get_contents("php://input"), true);

    if (empty($data['title'])) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "title is required"]);
        exit();
    }

    $query = "INSERT INTO services (title, description, price, duration_minutes)
              VALUES (:title, :desc, :price, :duration)";
    $stmt  = $db->prepare($query);
    $stmt->bindParam(":title",    $data['title']);
    $desc = $data['description'] ?? null;
    $stmt->bindParam(":desc",     $desc);
    $price = $data['price'] ?? 0.00;
    $stmt->bindParam(":price",    $price);
    $duration = $data['duration_minutes'] ?? 60;
    $stmt->bindParam(":duration", $duration);

    if ($stmt->execute()) {
        http_response_code(201);
        echo json_encode([
            "status"  => "success",
            "message" => "Service created",
            "data"    => ["service_id" => $db->lastInsertId()]
        ]);
    } else {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Failed to create service"]);
    }

} elseif ($method === 'PUT') {
    // Update an existing service (admin)
    $data = json_decode(file_get_contents("php://input"), true);

    if (empty($data['service_id'])) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "service_id is required"]);
        exit();
    }

    $fields = [];
    $params = [":id" => $data['service_id']];

    if (isset($data['title']))            { $fields[] = "title = :title";            $params[":title"]    = $data['title']; }
    if (isset($data['description']))      { $fields[] = "description = :desc";       $params[":desc"]     = $data['description']; }
    if (isset($data['price']))            { $fields[] = "price = :price";            $params[":price"]    = $data['price']; }
    if (isset($data['duration_minutes'])) { $fields[] = "duration_minutes = :dur";   $params[":dur"]      = $data['duration_minutes']; }
    if (isset($data['is_active']))        { $fields[] = "is_active = :active";       $params[":active"]   = $data['is_active']; }

    if (empty($fields)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "No fields to update"]);
        exit();
    }

    $query = "UPDATE services SET " . implode(", ", $fields) . " WHERE service_id = :id";
    $stmt  = $db->prepare($query);

    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    $stmt->execute();

    echo json_encode(["status" => "success", "message" => "Service updated"]);

} elseif ($method === 'DELETE') {
    // Soft delete - deactivate service (admin)
    $serviceId = $_GET['service_id'] ?? null;

    if (!$serviceId) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "service_id is required"]);
        exit();
    }

    $query = "UPDATE services SET is_active = 0 WHERE service_id = :id";
    $stmt  = $db->prepare($query);
    $stmt->bindParam(":id", $serviceId);
    $stmt->execute();

    echo json_encode(["status" => "success", "message" => "Service deactivated"]);

} else {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
}
?>
