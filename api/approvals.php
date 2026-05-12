<?php
// api/approvals.php
// MNTHC-45: Create approval interface - completed - author: Abenezer Andualem
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT");
header("Access-Control-Allow-Headers: Content-Type");

require_once "config/database.php";
require_once "functions/helpers.php";

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // List appointments pending approval (admin/doctor)
    $status    = isset($_GET['status'])    ? sanitize($_GET['status'])    : 'pending';
    $doctorId  = isset($_GET['doctor_id']) ? sanitize($_GET['doctor_id']) : null;
    $from      = isset($_GET['from'])      ? sanitize($_GET['from'])      : null;
    $to        = isset($_GET['to'])        ? sanitize($_GET['to'])        : null;

    [$limit, $offset] = getPagination();

    $where  = "a.status = :status";
    $params = [":status" => $status];

    if ($doctorId) { $where .= " AND a.doctor_id = :doctor_id"; $params[":doctor_id"] = $doctorId; }
    if ($from)     { $where .= " AND a.appointment_date >= :from"; $params[":from"] = $from; }
    if ($to)       { $where .= " AND a.appointment_date <= :to";   $params[":to"]   = $to; }

    $countStmt = $db->prepare("SELECT COUNT(*) FROM appointments a WHERE $where");
    foreach ($params as $k => $v) $countStmt->bindValue($k, $v);
    $countStmt->execute();
    $total = (int)$countStmt->fetchColumn();

    $query = "SELECT a.*,
                     s.title AS service_name,
                     p.full_name AS patient_name,
                     p.phone_number AS patient_phone,
                     d.full_name AS doctor_name
              FROM appointments a
              JOIN services s ON a.service_id = s.service_id
              JOIN users p ON a.patient_id = p.user_id
              LEFT JOIN users d ON a.doctor_id = d.user_id
              WHERE $where
              ORDER BY a.appointment_date ASC, a.appointment_time ASC
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
    // Approve or reject an appointment
    $raw  = json_decode(file_get_contents("php://input"), true) ?? [];
    $data = sanitizeInput($raw);

    if (empty($data['appointment_id'])) respond(400, "error", "appointment_id is required");
    if (empty($data['action']))         respond(400, "error", "action is required (approve or reject)");
    if (empty($data['reviewed_by']))    respond(400, "error", "reviewed_by (user_id) is required");

    $allowed = ['approve', 'reject'];
    if (!in_array($data['action'], $allowed)) {
        respond(400, "error", "action must be 'approve' or 'reject'");
    }

    $newStatus = $data['action'] === 'approve' ? 'confirmed' : 'cancelled';
    $reason    = $data['reason'] ?? null;

    // Update appointment status
    $stmt = $db->prepare("UPDATE appointments SET status = :status WHERE appointment_id = :id");
    $stmt->bindParam(":status", $newStatus);
    $stmt->bindParam(":id",     $data['appointment_id']);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        respond(404, "error", "Appointment not found");
    }

    // Fetch patient_id for notification
    $apptStmt = $db->prepare("SELECT patient_id FROM appointments WHERE appointment_id = :id");
    $apptStmt->bindParam(":id", $data['appointment_id']);
    $apptStmt->execute();
    $appt = $apptStmt->fetch(PDO::FETCH_ASSOC);

    if ($appt) {
        $title   = $data['action'] === 'approve' ? 'Appointment Confirmed' : 'Appointment Rejected';
        $message = $data['action'] === 'approve'
            ? "Your appointment (ID: {$data['appointment_id']}) has been confirmed."
            : "Your appointment (ID: {$data['appointment_id']}) has been rejected." . ($reason ? " Reason: $reason" : "");

        $notifStmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (:uid, :title, :msg, 'appointment')");
        $notifStmt->bindParam(":uid",   $appt['patient_id']);
        $notifStmt->bindParam(":title", $title);
        $notifStmt->bindParam(":msg",   $message);
        $notifStmt->execute();
    }

    logActivity($db, (int)$data['reviewed_by'], "approval_{$data['action']}", "Appointment {$data['appointment_id']} {$data['action']}d. Status: $newStatus");

    respond(200, "success", "Appointment " . ($data['action'] === 'approve' ? 'approved' : 'rejected') . " successfully");

} elseif ($method === 'POST') {
    // Bulk approve/reject
    $raw  = json_decode(file_get_contents("php://input"), true) ?? [];
    $data = sanitizeInput($raw);

    if (empty($data['appointment_ids']) || !is_array($raw['appointment_ids'])) {
        respond(400, "error", "appointment_ids array is required");
    }
    if (empty($data['action']))      respond(400, "error", "action is required (approve or reject)");
    if (empty($data['reviewed_by'])) respond(400, "error", "reviewed_by (user_id) is required");

    $allowed = ['approve', 'reject'];
    if (!in_array($data['action'], $allowed)) {
        respond(400, "error", "action must be 'approve' or 'reject'");
    }

    $newStatus = $data['action'] === 'approve' ? 'confirmed' : 'cancelled';
    $ids       = array_map('intval', $raw['appointment_ids']);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $stmt = $db->prepare("UPDATE appointments SET status = ? WHERE appointment_id IN ($placeholders)");
    $stmt->execute(array_merge([$newStatus], $ids));

    logActivity($db, (int)$data['reviewed_by'], "bulk_approval_{$data['action']}", "Bulk {$data['action']}d " . count($ids) . " appointments");

    respond(200, "success", count($ids) . " appointments " . ($data['action'] === 'approve' ? 'approved' : 'rejected'));

} else {
    respond(405, "error", "Method not allowed");
}
?>
