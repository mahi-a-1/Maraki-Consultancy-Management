<?php
// api/dashboard_stats.php
// Summary statistics for admin and doctor dashboards
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

require_once "config/database.php";
require_once "functions/helpers.php";

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    respond(405, "error", "Method not allowed");
}

$role   = isset($_GET['role'])    ? sanitize($_GET['role'])    : null;
$userId = isset($_GET['user_id']) ? sanitize($_GET['user_id']) : null;

if (!$role || !$userId) {
    respond(400, "error", "role and user_id are required");
}

$database = new Database();
$db = $database->getConnection();

$stats = [];

if ($role === 'admin') {
    // Total users by role
    $stmt = $db->prepare("SELECT role, COUNT(*) as count FROM users GROUP BY role");
    $stmt->execute();
    $stats['users'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Appointments by status
    $stmt = $db->prepare("SELECT status, COUNT(*) as count FROM appointments GROUP BY status");
    $stmt->execute();
    $stats['appointments_by_status'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Total appointments today
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM appointments WHERE appointment_date = CURDATE()");
    $stmt->execute();
    $stats['appointments_today'] = (int)$stmt->fetchColumn();

    // Total active services
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM services WHERE is_active = 1");
    $stmt->execute();
    $stats['active_services'] = (int)$stmt->fetchColumn();

    // Recent activity (last 5 logs)
    $stmt = $db->prepare(
        "SELECT l.action, l.description, l.created_at, u.full_name
         FROM activity_logs l
         LEFT JOIN users u ON l.user_id = u.user_id
         ORDER BY l.created_at DESC LIMIT 5"
    );
    $stmt->execute();
    $stats['recent_activity'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Unread notifications count
    $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE is_read = 0");
    $stmt->execute();
    $stats['unread_notifications'] = (int)$stmt->fetchColumn();

} elseif ($role === 'doctor') {
    // Doctor's appointments by status
    $stmt = $db->prepare("SELECT status, COUNT(*) as count FROM appointments WHERE doctor_id = :uid GROUP BY status");
    $stmt->bindParam(":uid", $userId);
    $stmt->execute();
    $stats['appointments_by_status'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Today's appointments
    $stmt = $db->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = :uid AND appointment_date = CURDATE()");
    $stmt->bindParam(":uid", $userId);
    $stmt->execute();
    $stats['appointments_today'] = (int)$stmt->fetchColumn();

    // Pending approvals
    $stmt = $db->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = :uid AND status = 'pending'");
    $stmt->bindParam(":uid", $userId);
    $stmt->execute();
    $stats['pending_approvals'] = (int)$stmt->fetchColumn();

    // Upcoming appointments (next 7 days)
    $stmt = $db->prepare(
        "SELECT a.appointment_date, a.appointment_time, a.status,
                p.full_name AS patient_name, s.title AS service_name
         FROM appointments a
         JOIN users p ON a.patient_id = p.user_id
         JOIN services s ON a.service_id = s.service_id
         WHERE a.doctor_id = :uid
         AND a.appointment_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
         AND a.status = 'confirmed'
         ORDER BY a.appointment_date ASC, a.appointment_time ASC
         LIMIT 10"
    );
    $stmt->bindParam(":uid", $userId);
    $stmt->execute();
    $stats['upcoming_appointments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Unread notifications
    $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0");
    $stmt->bindParam(":uid", $userId);
    $stmt->execute();
    $stats['unread_notifications'] = (int)$stmt->fetchColumn();

} elseif ($role === 'patient') {
    // Patient's appointments by status
    $stmt = $db->prepare("SELECT status, COUNT(*) as count FROM appointments WHERE patient_id = :uid GROUP BY status");
    $stmt->bindParam(":uid", $userId);
    $stmt->execute();
    $stats['appointments_by_status'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Next upcoming appointment
    $stmt = $db->prepare(
        "SELECT a.appointment_date, a.appointment_time, a.status,
                s.title AS service_name, d.full_name AS doctor_name
         FROM appointments a
         JOIN services s ON a.service_id = s.service_id
         LEFT JOIN users d ON a.doctor_id = d.user_id
         WHERE a.patient_id = :uid
         AND a.appointment_date >= CURDATE()
         AND a.status IN ('pending', 'confirmed')
         ORDER BY a.appointment_date ASC, a.appointment_time ASC
         LIMIT 1"
    );
    $stmt->bindParam(":uid", $userId);
    $stmt->execute();
    $stats['next_appointment'] = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

    // Unread notifications
    $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0");
    $stmt->bindParam(":uid", $userId);
    $stmt->execute();
    $stats['unread_notifications'] = (int)$stmt->fetchColumn();

} else {
    respond(400, "error", "Invalid role. Must be admin, doctor, or patient");
}

respond(200, "success", "Dashboard stats retrieved", $stats);
?>
