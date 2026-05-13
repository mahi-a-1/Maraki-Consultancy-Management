<?php
require_once "../backend/config/database.php";

header("Content-Type: application/json");

$conn = Database::connect();

try {
    $stmt = $conn->prepare("SELECT * FROM diet_plans ORDER BY id DESC");
    $stmt->execute();

    $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "data" => $plans
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>
