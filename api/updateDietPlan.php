<?php
require_once "../backend/config/database.php";

header("Content-Type: application/json");

$conn = Database::connect();

// get data from request
$id = $_POST['id'] ?? null;
$name = $_POST['name'] ?? null;
$description = $_POST['description'] ?? null;
$calories = $_POST['calories'] ?? null;

// validation
if (!$id || !$name || !$description || !$calories) {
    echo json_encode([
        "success" => false,
        "message" => "All fields are required"
    ]);
    exit;
}

try {
    $stmt = $conn->prepare("
        UPDATE diet_plans 
        SET name = ?, description = ?, calories = ?
        WHERE id = ?
    ");

    $result = $stmt->execute([$name, $description, $calories, $id]);

    echo json_encode([
        "success" => $result,
        "message" => $result ? "Diet plan updated successfully" : "Update failed"
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>
