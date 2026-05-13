<?php
require_once "../backend/config/database.php";

header("Content-Type: application/json");

$conn = Database::connect();

$action = $_GET['action'] ?? '';

// ============================
// UPDATE DIET PLAN (MNTHC-17)
// ============================
if ($action === "update") {

    $id = $_POST['id'] ?? null;
    $name = $_POST['name'] ?? null;
    $description = $_POST['description'] ?? null;
    $calories = $_POST['calories'] ?? null;

    if (!$id || !$name || !$description || !$calories) {
        echo json_encode([
            "success" => false,
            "message" => "All fields are required"
        ]);
        exit;
    }

    $sql = "UPDATE diet_plans 
            SET name = ?, description = ?, calories = ? 
            WHERE id = ?";

    $stmt = $conn->prepare($sql);
    $result = $stmt->execute([$name, $description, $calories, $id]);

    echo json_encode([
        "success" => $result,
        "message" => $result ? "Diet plan updated successfully" : "Update failed"
    ]);
    exit;
}

// ============================
// INVALID ACTION
// ============================
echo json_encode([
    "success" => false,
    "message" => "Invalid action"
]);
?>
