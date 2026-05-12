<?php
// api/appointments.php
// MNTHC-43: Save appointments - completed - author: Abenezer Andualem
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT");
header("Access-Control-Allow-Headers: Content-Type");

require_once "config/database.php";
require_once "functions/helpers.php";

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $raw  = json_decode(file_get_contents("php://input"), true) ?? [];
    $data = sanitizeInput($raw);

    foreach (['patient_id', 'service_id', 'appointment_date', 'appointment_time'] as $field) {
        if (empty($data[$field])) respond(400, "error", "$field is required");
    }

    if (strtotime($data['appointment_date']) < strtotime(date('Y-m-d'))) {
        respond(400, "error", "Appointment date cannot be in the past");
    }

    $stmt = $db->prepare("INSERT INTO appointments (patient_id, doctor_id, service_id, appointment_date, appointment_time, notes)
                          VALUES (:patient_id, :doctor_id, :service_id, :appointment_date, :appointment_time, :notes)");
    $stmt->bindParam(":patient_id",       $data['patient_id']);
    $doctorId = $data['doctor_id'] ?? null;
    $stmt->bindParam(":doctor_id",        $doctorId);
    $stmt->bindParam(":service_id",       $data['service_id']);
    $stmt->bindParam(":appointment_date", $data['appointment_date']);
    $stmt->bindParam(":appointment_time", $data['appointment_time']);
    $notes = $data['notes'] ?? '';
    $stmt->bindParam(":notes",            $notes);

    if ($stmt->execute()) {
        $appointmentId = $db->lastInsertId();
        logActivity($db, (int)$data['patient_id'], "book_appointment", "Booked appointment ID $appointmentId");
        respond(201, "success", "Appointment saved successfully", ["appointment_id" => $appointmentId]);
    } else {
        respond(500, "error", "Failed to save appointment");
    }

} elseif ($method === 'GET') {
    $userId = $_GET['user_id'] ?? null;
    $role   = sanitize($_GET['role'] ?? 'patient');
    $status = isset($_GET['status']) ? sanitize($_GET['status']) : null;
    $from   = isset($_GET['from'])   ? sanitize($_GET['from'])   : null;
    $to     = isset($_GET['to'])     ? sanitize($_GET['to'])     : null;

    [$limit, $offset] = getPagination();

    if (!$userId) respond(400, "error", "user_id is required");

    $where  = $role === 'doctor' ? "a.doctor_id = :uid" : "a.patient_id = :uid";
    $params = [":uid" => $userId];

    if ($status) { $where .= " AND a.status = :status"; $params[":status"] = $status; }
    if ($from)   { $where .= " AND a.appointment_date >= :from"; $params[":from"] = $from; }
    if ($to)     { $where .= " AND a.appointment_date <= :to";   $params[":to"]   = $to; }

    $countStmt = $db->prepare("SELECT COUNT(*) FROM appointments a WHERE $where");
    foreach ($params as $k => $v) $countStmt->bindValue($k, $v);
    $countStmt->execute();
    $total = (int)$countStmt->fetchColumn();

    $query = "SELECT a.*, s.title as service_name, u.full_name as patient_name
              FROM appointments a
              JOIN services s ON a.service_id = s.service_id
              JOIN users u ON a.patient_id = u.user_id
              WHERE $where
              ORDER BY a.appointment_date DESC
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

} elseif ($method === 'PUT') {
    $raw  = json_decode(file_get_contents("php://input"), true) ?? [];
    $data = sanitizeInput($raw);

    if (empty($data['appointment_id']) || empty($data['status'])) {
        respond(400, "error", "appointment_id and status are required");
    }

    $allowed = ['pending', 'confirmed', 'cancelled', 'completed'];
    if (!in_array($data['status'], $allowed)) {
        respond(400, "error", "Invalid status value");
    }

    $stmt = $db->prepare("UPDATE appointments SET status = :status WHERE appointment_id = :id");
    $stmt->bindParam(":status", $data['status']);
    $stmt->bindParam(":id",     $data['appointment_id']);
    $stmt->execute();

    logActivity($db, null, "update_appointment", "Appointment {$data['appointment_id']} status set to {$data['status']}");
    respond(200, "success", "Appointment updated");

} else {
    respond(405, "error", "Method not allowed");
}
?>
