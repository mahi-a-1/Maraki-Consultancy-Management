<?php
require_once "../backend/config/database.php";

header("Content-Type: application/json");

$conn = Database::connect();

$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? '';

if (!$id) {
    echo json_encode([
        "success" => false,
        "message" => "Appointment ID required"
    ]);
    exit;
}

if ($action == "approve") {
    $sql = "UPDATE appointments SET status='approved' WHERE appointment_id=?";
}
elseif ($action == "reject") {
    $sql = "UPDATE appointments SET status='rejected' WHERE appointment_id=?";
}
else {
    echo json_encode([
        "success" => false,
        "message" => "Invalid action"
    ]);
    exit;
}

$stmt = $conn->prepare($sql);
$result = $stmt->execute([$id]);

echo json_encode([
    "success" => $result,
    "message" => "Action completed"
]);
?>
