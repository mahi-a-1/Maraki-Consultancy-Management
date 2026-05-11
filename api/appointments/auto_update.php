<?php
// Update automation logic — MNTHC-60
// Auto-marks past pending/confirmed appointments as 'completed'
// Run this via a cron job: * * * * * php /path/to/auto_update.php

require_once "../config/database.php";

$database = new Database();
$db = $database->getConnection();

// Mark appointments as completed if date+time has passed
$stmt = $db->prepare("
    UPDATE appointments
    SET status = 'completed', updated_at = NOW()
    WHERE status IN ('pending', 'confirmed')
      AND CONCAT(appointment_date, ' ', appointment_time) < NOW()
");

$stmt->execute();
$updated = $stmt->rowCount();

// Log the result
$log = date('Y-m-d H:i:s') . " — Auto-completed {$updated} appointment(s)\n";
file_put_contents(__DIR__ . '/../../logs/auto_update.log', $log, FILE_APPEND);

echo json_encode([
    "status"  => "success",
    "updated" => $updated,
    "message" => "Auto-completed {$updated} appointment(s)"
]);
?>
