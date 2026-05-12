<?php
// api/functions/helpers.php
// Shared helper functions: response, sanitization, pagination, error logging

/**
 * Send a consistent JSON response
 */
function respond(int $code, string $status, string $message, array $data = [], array $extras = []): void {
    http_response_code($code);
    $response = array_merge(["status" => $status, "message" => $message], $extras);
    if (!empty($data)) $response["data"] = $data;
    echo json_encode($response);
    exit();
}

/**
 * Sanitize a single string value
 */
function sanitize(string $value): string {
    return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize all string values in an associative array
 */
function sanitizeInput(array $data): array {
    foreach ($data as $key => $value) {
        if (is_string($value)) {
            $data[$key] = sanitize($value);
        }
    }
    return $data;
}

/**
 * Get pagination params from GET request
 * Returns [limit, offset]
 */
function getPagination(int $defaultLimit = 20): array {
    $limit  = isset($_GET['limit'])  ? max(1, min((int)$_GET['limit'],  100)) : $defaultLimit;
    $offset = isset($_GET['offset']) ? max(0, (int)$_GET['offset']) : 0;
    return [$limit, $offset];
}

/**
 * Build a paginated response envelope
 */
function paginatedResponse(array $rows, int $total, int $limit, int $offset): array {
    return [
        "total"   => $total,
        "limit"   => $limit,
        "offset"  => $offset,
        "pages"   => $limit > 0 ? (int)ceil($total / $limit) : 1,
        "data"    => $rows
    ];
}

/**
 * Log an error or activity to activity_logs table
 */
function logActivity($db, ?int $userId, string $action, string $description): void {
    try {
        $ip    = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $query = "INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (:uid, :action, :desc, :ip)";
        $stmt  = $db->prepare($query);
        $stmt->bindParam(":uid",    $userId);
        $stmt->bindParam(":action", $action);
        $stmt->bindParam(":desc",   $description);
        $stmt->bindParam(":ip",     $ip);
        $stmt->execute();
    } catch (Exception $e) {
        // Silently fail — logging should never break the main flow
    }
}
?>
