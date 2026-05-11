<?php
// api/appointments.php
// MNTHC-43: Save appointments - completed
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT");
header("Access-Control-Allow-Headers: Content-Type");

require_once "config/database.php";

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    $required = ['patient_id', 'service_id', 'appointment_date', 'appointment_time'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "$field is required"]);
            exit();
        }
    }

    if (strtotime($data['appointment_date']) < strtotime(date('Y-m-d'))) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Appointment date cannot be in the past"]);
        exit();
    }

    $query = "INSERT INTO appointments (patient_id, doctor_id, service_id, appointment_date, appointment_time, notes)
              VALUES (:patient_id, :doctor_id, :service_id, :appointment_date, :appointment_time, :notes)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":patient_id", $data['patient_id']);
    $doctorId = $data['doctor_id'] ?? null;
    $stmt->bindParam(":doctor_id", $doctorId);
    $stmt->bindParam(":service_id", $data['service_id']);
    $stmt->bindParam(":appointment_date", $data['appointment_date']);
    $stmt->bindParam(":appointment_time", $data['appointment_time']);
    $notes = $data['notes'] ?? '';
    $stmt->bindParam(":notes", $notes);

    if ($stmt->execute()) {
        http_response_code(201);
        echo json_encode([
            "status"  => "success",
            "message" => "Appointment saved successfully",
            "data"    => ["appointment_id" => $db->lastInsertId()]
        ]);
    } else {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Failed to save appointment"]);
    }

} elseif ($method === 'GET') {
    $userId = $_GET['user_id'] ?? null;
    $role   = $_GET['role'] ?? 'patient';

    if (!$userId) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "user_id is required"]);
        exit();
    }

    if ($role === 'doctor') {
        $query = "SELECT a.*, s.title as service_name, u.full_name as patient_name
                  FROM appointments a
                  JOIN services s ON a.service_id = s.service_id
                  JOIN users u ON a.patient_id = u.user_id
                  WHERE a.doctor_id = :uid ORDER BY a.appointment_date DESC";
    } else {
        $query = "SELECT a.*, s.title as service_name
                  FROM appointments a
                  JOIN services s ON a.service_id = s.service_id
                  WHERE a.patient_id = :uid ORDER BY a.appointment_date DESC";
    }

    $stmt = $db->prepare($query);
    $stmt->bindParam(":uid", $userId);
    $stmt->execute();

    echo json_encode([
        "status" => "success",
        "data"   => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);

} elseif ($method === 'PUT') {
    $data = json_decode(file_get_contents("php://input"), true);

    if (empty($data['appointment_id']) || empty($data['status'])) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "appointment_id and status are required"]);
        exit();
    }

    $allowed = ['pending', 'confirmed', 'cancelled', 'completed'];
    if (!in_array($data['status'], $allowed)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Invalid status value"]);
        exit();
    }

    $query = "UPDATE appointments SET status = :status WHERE appointment_id = :id";
    $stmt  = $db->prepare($query);
    $stmt->bindParam(":status", $data['status']);
    $stmt->bindParam(":id", $data['appointment_id']);
    $stmt->execute();

    echo json_encode(["status" => "success", "message" => "Appointment updated"]);

} else {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
}
?>
