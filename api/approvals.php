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

/**
 * Check if a doctor already has an appointment at the same date/time
 */
function hasConflict($db, int $doctorId, string $date, string $time, ?int $excludeId = null): bool {
    $query = "SELECT appointment_id FROM appointments
              WHERE doctor_id = :doctor_id
              AND appointment_date = :date
              AND appointment_time = :time
              AND status NOT IN ('cancelled')";
    if ($excludeId) $query .= " AND appointment_id != :exclude_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":doctor_id", $doctorId);
    $stmt->bindParam(":date",      $date);
    $stmt->bindParam(":time",      $time);
    if ($excludeId) $stmt->bindParam(":exclude_id", $excludeId);
    $stmt->execute();
    return $stmt->rowCount() > 0;
}

/**
 * Get all available time slots for a doctor on a given date
 * Slots are every 30 minutes from 08:00 to 17:00
 */
function getAvailableSlots($db, int $doctorId, string $date): array {
    $allSlots = [];
    $start = strtotime("08:00");
    $end   = strtotime("17:00");
    for ($t = $start; $t <= $end; $t += 1800) {
        $allSlots[] = date("H:i:s", $t);
    }

    $stmt = $db->prepare(
        "SELECT appointment_time FROM appointments
         WHERE doctor_id = :doctor_id
         AND appointment_date = :date
         AND status NOT IN ('cancelled')"
    );
    $stmt->bindParam(":doctor_id", $doctorId);
    $stmt->bindParam(":date",      $date);
    $stmt->execute();
    $booked = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'appointment_time');

    return array_values(array_filter($allSlots, fn($slot) => !in_array($slot, $booked)));
}

/**
 * Send notification to a user
 */
function sendNotification($db, int $userId, string $title, string $message, string $type = 'appointment'): void {
    $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (:uid, :title, :msg, :type)");
    $stmt->bindParam(":uid",   $userId);
    $stmt->bindParam(":title", $title);
    $stmt->bindParam(":msg",   $message);
    $stmt->bindParam(":type",  $type);
    $stmt->execute();
}

/**
 * Fetch full appointment details with joins
 */
function getAppointmentById($db, int $id): ?array {
    $stmt = $db->prepare(
        "SELECT a.*,
                s.title AS service_name, s.duration_minutes,
                p.full_name AS patient_name, p.phone_number AS patient_phone, p.email AS patient_email,
                d.full_name AS doctor_name
         FROM appointments a
         JOIN services s ON a.service_id = s.service_id
         JOIN users p ON a.patient_id = p.user_id
         LEFT JOIN users d ON a.doctor_id = d.user_id
         WHERE a.appointment_id = :id"
    );
    $stmt->bindParam(":id", $id);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

if ($method === 'GET') {
    $action = sanitize($_GET['action'] ?? '');

    // Approval history for a specific appointment
    if ($action === 'history') {
        $appointmentId = $_GET['appointment_id'] ?? null;
        if (!$appointmentId) respond(400, "error", "appointment_id is required");

        $stmt = $db->prepare(
            "SELECT l.*, u.full_name AS reviewed_by_name
             FROM activity_logs l
             LEFT JOIN users u ON l.user_id = u.user_id
             WHERE l.action LIKE 'approval_%'
             AND l.description LIKE :pattern
             ORDER BY l.created_at DESC"
        );
        $pattern = "%Appointment $appointmentId%";
        $stmt->bindParam(":pattern", $pattern);
        $stmt->execute();
        respond(200, "success", "Approval history retrieved", $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    // Doctor availability slots for a given date
    if ($action === 'availability') {
        $doctorId = $_GET['doctor_id'] ?? null;
        $date     = $_GET['date']      ?? null;
        if (!$doctorId || !$date) respond(400, "error", "doctor_id and date are required");

        $slots = getAvailableSlots($db, (int)$doctorId, sanitize($date));
        respond(200, "success", "Available slots retrieved", ["date" => $date, "available_slots" => $slots]);
    }

    // Approval summary report (admin)
    if ($action === 'summary') {
        $from = isset($_GET['from']) ? sanitize($_GET['from']) : date('Y-m-01');
        $to   = isset($_GET['to'])   ? sanitize($_GET['to'])   : date('Y-m-d');

        $stmt = $db->prepare(
            "SELECT
                COUNT(*) AS total,
                SUM(status = 'confirmed') AS approved,
                SUM(status = 'cancelled') AS rejected,
                SUM(status = 'pending')   AS pending,
                SUM(status = 'completed') AS completed
             FROM appointments
             WHERE appointment_date BETWEEN :from AND :to"
        );
        $stmt->bindParam(":from", $from);
        $stmt->bindParam(":to",   $to);
        $stmt->execute();
        $summary = $stmt->fetch(PDO::FETCH_ASSOC);

        // Per-service breakdown
        $stmt = $db->prepare(
            "SELECT s.title, COUNT(*) AS total,
                    SUM(a.status = 'confirmed') AS approved,
                    SUM(a.status = 'cancelled') AS rejected
             FROM appointments a
             JOIN services s ON a.service_id = s.service_id
             WHERE a.appointment_date BETWEEN :from AND :to
             GROUP BY s.service_id
             ORDER BY total DESC"
        );
        $stmt->bindParam(":from", $from);
        $stmt->bindParam(":to",   $to);
        $stmt->execute();
        $summary['by_service'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Per-doctor breakdown
        $stmt = $db->prepare(
            "SELECT u.full_name AS doctor_name, COUNT(*) AS total,
                    SUM(a.status = 'confirmed') AS approved,
                    SUM(a.status = 'cancelled') AS rejected
             FROM appointments a
             JOIN users u ON a.doctor_id = u.user_id
             WHERE a.appointment_date BETWEEN :from AND :to
             GROUP BY a.doctor_id
             ORDER BY total DESC"
        );
        $stmt->bindParam(":from", $from);
        $stmt->bindParam(":to",   $to);
        $stmt->execute();
        $summary['by_doctor'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        respond(200, "success", "Approval summary retrieved", $summary);
    }

    // Default: list appointments pending approval
    $status   = isset($_GET['status'])    ? sanitize($_GET['status'])    : 'pending';
    $doctorId = isset($_GET['doctor_id']) ? sanitize($_GET['doctor_id']) : null;
    $from     = isset($_GET['from'])      ? sanitize($_GET['from'])      : null;
    $to       = isset($_GET['to'])        ? sanitize($_GET['to'])        : null;

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
                     s.title AS service_name, s.duration_minutes,
                     p.full_name AS patient_name, p.phone_number AS patient_phone,
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
    $raw  = json_decode(file_get_contents("php://input"), true) ?? [];
    $data = sanitizeInput($raw);

    if (empty($data['appointment_id'])) respond(400, "error", "appointment_id is required");
    if (empty($data['action']))         respond(400, "error", "action is required (approve, reject, or reschedule)");
    if (empty($data['reviewed_by']))    respond(400, "error", "reviewed_by (user_id) is required");

    $validActions = ['approve', 'reject', 'reschedule'];
    if (!in_array($data['action'], $validActions)) {
        respond(400, "error", "action must be approve, reject, or reschedule");
    }

    $appt = getAppointmentById($db, (int)$data['appointment_id']);
    if (!$appt) respond(404, "error", "Appointment not found");

    // Reschedule
    if ($data['action'] === 'reschedule') {
        if (empty($data['new_date']) || empty($data['new_time'])) {
            respond(400, "error", "new_date and new_time are required for reschedule");
        }
        if (strtotime($data['new_date']) < strtotime(date('Y-m-d'))) {
            respond(400, "error", "Reschedule date cannot be in the past");
        }
        $doctorId = $appt['doctor_id'] ?? null;
        if ($doctorId && hasConflict($db, (int)$doctorId, $data['new_date'], $data['new_time'], (int)$data['appointment_id'])) {
            respond(409, "error", "Doctor has a conflicting appointment at the new date and time");
        }

        $stmt = $db->prepare("UPDATE appointments SET appointment_date = :date, appointment_time = :time, status = 'pending' WHERE appointment_id = :id");
        $stmt->bindParam(":date", $data['new_date']);
        $stmt->bindParam(":time", $data['new_time']);
        $stmt->bindParam(":id",   $data['appointment_id']);
        $stmt->execute();

        sendNotification($db, (int)$appt['patient_id'],
            'Appointment Rescheduled',
            "Your appointment for {$appt['service_name']} has been rescheduled to {$data['new_date']} at {$data['new_time']}."
        );

        logActivity($db, (int)$data['reviewed_by'], "approval_reschedule",
            "Appointment {$data['appointment_id']} rescheduled to {$data['new_date']} {$data['new_time']}"
        );

        respond(200, "success", "Appointment rescheduled successfully");
    }

    // Approve
    if ($data['action'] === 'approve') {
        if (!empty($data['doctor_id'])) {
            if (hasConflict($db, (int)$data['doctor_id'], $appt['appointment_date'], $appt['appointment_time'], (int)$data['appointment_id'])) {
                respond(409, "error", "Doctor has a conflicting appointment at this date and time");
            }
            $assignStmt = $db->prepare("UPDATE appointments SET doctor_id = :doctor_id WHERE appointment_id = :id");
            $assignStmt->bindParam(":doctor_id", $data['doctor_id']);
            $assignStmt->bindParam(":id",        $data['appointment_id']);
            $assignStmt->execute();
        }

        $stmt = $db->prepare("UPDATE appointments SET status = 'confirmed' WHERE appointment_id = :id");
        $stmt->bindParam(":id", $data['appointment_id']);
        $stmt->execute();

        sendNotification($db, (int)$appt['patient_id'],
            'Appointment Confirmed',
            "Your appointment for {$appt['service_name']} on {$appt['appointment_date']} at {$appt['appointment_time']} has been confirmed."
        );

        logActivity($db, (int)$data['reviewed_by'], "approval_approve",
            "Appointment {$data['appointment_id']} approved. Status: confirmed"
        );

        respond(200, "success", "Appointment approved successfully");
    }

    // Reject
    $reason = $data['reason'] ?? null;
    $stmt   = $db->prepare("UPDATE appointments SET status = 'cancelled' WHERE appointment_id = :id");
    $stmt->bindParam(":id", $data['appointment_id']);
    $stmt->execute();

    sendNotification($db, (int)$appt['patient_id'],
        'Appointment Rejected',
        "Your appointment for {$appt['service_name']} on {$appt['appointment_date']} has been rejected." . ($reason ? " Reason: $reason" : "")
    );

    logActivity($db, (int)$data['reviewed_by'], "approval_reject",
        "Appointment {$data['appointment_id']} rejected." . ($reason ? " Reason: $reason" : "")
    );

    respond(200, "success", "Appointment rejected successfully");

} elseif ($method === 'POST') {
    // Bulk approve/reject
    $raw  = json_decode(file_get_contents("php://input"), true) ?? [];
    $data = sanitizeInput($raw);

    if (empty($raw['appointment_ids']) || !is_array($raw['appointment_ids'])) {
        respond(400, "error", "appointment_ids array is required");
    }
    if (empty($data['action']))      respond(400, "error", "action is required (approve or reject)");
    if (empty($data['reviewed_by'])) respond(400, "error", "reviewed_by (user_id) is required");

    if (!in_array($data['action'], ['approve', 'reject'])) {
        respond(400, "error", "action must be approve or reject");
    }

    $newStatus    = $data['action'] === 'approve' ? 'confirmed' : 'cancelled';
    $ids          = array_map('intval', $raw['appointment_ids']);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $stmt = $db->prepare("UPDATE appointments SET status = ? WHERE appointment_id IN ($placeholders)");
    $stmt->execute(array_merge([$newStatus], $ids));
    $affected = $stmt->rowCount();

    // Notify each patient
    $patientStmt = $db->prepare(
        "SELECT a.patient_id, a.appointment_date, s.title AS service_name
         FROM appointments a
         JOIN services s ON a.service_id = s.service_id
         WHERE a.appointment_id IN ($placeholders)"
    );
    $patientStmt->execute($ids);

    foreach ($patientStmt->fetchAll(PDO::FETCH_ASSOC) as $a) {
        $title   = $data['action'] === 'approve' ? 'Appointment Confirmed' : 'Appointment Rejected';
        $message = $data['action'] === 'approve'
            ? "Your appointment for {$a['service_name']} on {$a['appointment_date']} has been confirmed."
            : "Your appointment for {$a['service_name']} on {$a['appointment_date']} has been rejected.";
        sendNotification($db, (int)$a['patient_id'], $title, $message);
    }

    logActivity($db, (int)$data['reviewed_by'], "bulk_approval_{$data['action']}",
        "Bulk {$data['action']}d $affected appointments"
    );

    respond(200, "success", "$affected appointments " . ($data['action'] === 'approve' ? 'approved' : 'rejected'));

} else {
    respond(405, "error", "Method not allowed");
}
?>
