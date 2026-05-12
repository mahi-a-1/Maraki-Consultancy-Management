<?php
// functions/roles.php
// MNTHC-39: Create role logic - role-based access control functions

// Define roles and their permissions
define('ROLES', [
    'patient' => [
        'permissions' => ['view_own_records', 'book_appointments', 'send_messages']
    ],
    'doctor' => [
        'permissions' => ['view_patient_records', 'manage_appointments', 'send_messages', 'write_reports']
    ],
    'admin' => [
        'permissions' => ['manage_users', 'view_all_records', 'system_settings', 'manage_appointments', 'send_messages']
    ]
]);

/**
 * Check if a user has a specific role
 */
function hasRole($userRole, $requiredRole) {
    return $userRole === $requiredRole;
}

/**
 * Check if a user has a specific permission
 */
function hasPermission($userRole, $permission) {
    if (!isset(ROLES[$userRole])) {
        return false;
    }

    return in_array($permission, ROLES[$userRole]['permissions']);
}

/**
 * Check if user can access a resource based on role
 */
function canAccess($userRole, $resource, $action = 'view') {
    $permission = $action . '_' . $resource;

    return hasPermission($userRole, $permission);
}

/**
 * Get all permissions for a role
 */
function getRolePermissions($role) {
    return ROLES[$role]['permissions'] ?? [];
}

/**
 * Validate role exists
 */
function isValidRole($role) {
    return isset(ROLES[$role]);
}

/**
 * Get user role from session (placeholder - implement session logic)
 */
function getCurrentUserRole() {
    // This would check session data in a real app
    // For now, return default
    return 'patient';
}

/**
 * Require specific role for access
 */
function requireRole($requiredRole) {
    $userRole = getCurrentUserRole();

    if (!hasRole($userRole, $requiredRole)) {
        http_response_code(403);
        echo json_encode([
            "status" => "error",
            "message" => "Access denied. Required role: $requiredRole"
        ]);
        exit();
    }
}

/**
 * Require specific permission for access
 */
function requirePermission($permission) {
    $userRole = getCurrentUserRole();

    if (!hasPermission($userRole, $permission)) {
        http_response_code(403);
        echo json_encode([
            "status" => "error",
            "message" => "Access denied. Required permission: $permission"
        ]);
        exit();
    }
}
?>