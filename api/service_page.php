<?php
// api/service_page.php
// MNTHC-41: Create service page - completed - author: Abenezer Andualem
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
    exit();
}

require_once "config/database.php";

$database = new Database();
$db = $database->getConnection();

$serviceId = $_GET['service_id'] ?? null;

if ($serviceId) {
    // Single service detail
    $query = "SELECT s.*,
                     COUNT(a.appointment_id) AS total_bookings
              FROM services s
              LEFT JOIN appointments a ON s.service_id = a.service_id
              WHERE s.service_id = :id AND s.is_active = 1
              GROUP BY s.service_id";
    $stmt = $db->prepare($query);
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
    // All active services with booking count
    $query = "SELECT s.*,
                     COUNT(a.appointment_id) AS total_bookings
              FROM services s
              LEFT JOIN appointments a ON s.service_id = a.service_id
              WHERE s.is_active = 1
              GROUP BY s.service_id
              ORDER BY s.title ASC";
    $stmt = $db->prepare($query);
    $stmt->execute();

    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "total"  => count($services),
        "data"   => $services
    ]);
}
?>
