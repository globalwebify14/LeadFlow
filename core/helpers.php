<?php
/**
 * Core Helpers — Shared utility functions for the CRM
 */

/**
 * Sanitize output for HTML
 */
function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Format date for display
 */
function formatDate($date, $format = 'M d, Y') {
    if (empty($date)) return 'N/A';
    return date($format, strtotime($date));
}

/**
 * Format date with time
 */
function formatDateTime($date) {
    if (empty($date)) return 'N/A';
    return date('M d, Y h:i A', strtotime($date));
}

/**
 * Format currency
 */
function formatCurrency($amount, $symbol = '₹') {
    return $symbol . number_format((float)$amount, 2);
}

/**
 * Get status badge CSS class
 */
function getStatusBadgeClass($status) {
    $map = [
        'New Lead'      => 'bg-new-lead',
        'Contacted'     => 'bg-contacted',
        'Working'       => 'bg-working',
        'Qualified'     => 'bg-qualified',
        'Processing'    => 'bg-working',
        'Proposal Sent' => 'bg-qualified',
        'Follow Up'     => 'bg-follow-up',
        'Negotiation'   => 'bg-negotiation',
        'Not Picked'    => 'bg-not-picked',
        'Closed Won'    => 'bg-closed-won',
        'Done'          => 'bg-done',
        'Closed Lost'   => 'bg-closed-lost',
        'Rejected'      => 'bg-rejected',
    ];
    return $map[$status] ?? 'bg-secondary';
}

/**
 * Get priority badge class
 */
function getPriorityBadgeClass($priority) {
    $map = [
        'Hot'  => 'bg-danger',
        'Warm' => 'bg-warning text-dark',
        'Cold' => 'bg-info',
    ];
    return $map[$priority] ?? 'bg-secondary';
}

/**
 * Get priority icon
 */
function getPriorityIcon($priority) {
    $map = [
        'Hot'  => 'bi-fire',
        'Warm' => 'bi-sun',
        'Cold' => 'bi-snow',
    ];
    return $map[$priority] ?? 'bi-circle';
}

/**
 * Send JSON response (for AJAX/API)
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Redirect with flash message
 */
function redirect($url, $message = '', $type = 'success') {
    if ($message) {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }
    header("Location: $url");
    exit;
}

/**
 * Get and clear flash message
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $msg = [
            'message' => $_SESSION['flash_message'],
            'type'    => $_SESSION['flash_type'] ?? 'success'
        ];
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return $msg;
    }
    return null;
}

/**
 * Check if current user has required role
 */
function checkRole($requiredRoles) {
    if (!is_array($requiredRoles)) {
        $requiredRoles = [$requiredRoles];
    }
    $userRole = $_SESSION['user_role'] ?? 'agent';
    return in_array($userRole, $requiredRoles);
}

/**
 * Require specific role or redirect
 */
function requireRole($requiredRoles) {
    if (!checkRole($requiredRoles)) {
        redirect(BASE_URL . 'modules/dashboard/', 'You do not have permission to access that page.', 'danger');
    }
}

/**
 * Get current organization ID from session
 */
function getOrgId() {
    return $_SESSION['organization_id'] ?? 1;
}

/**
 * Get current user ID from session
 */
function getUserId() {
    return $_SESSION['user_id'] ?? 0;
}

/**
 * Get current user role from session
 */
function getUserRole() {
    return $_SESSION['user_role'] ?? 'agent';
}

/**
 * Role hierarchy checks
 */
function isSuperAdmin() {
    return getUserRole() === 'super_admin';
}

function isOrgOwner() {
    return in_array(getUserRole(), ['super_admin', 'org_owner']);
}

function isOrgAdmin() {
    return in_array(getUserRole(), ['super_admin', 'org_owner', 'org_admin']);
}

function isTeamLead() {
    return in_array(getUserRole(), ['super_admin', 'org_owner', 'org_admin', 'team_lead']);
}

/**
 * Legacy wrappers
 */
function isAdmin() {
    return isOrgAdmin();
}

function isManager() {
    return isTeamLead();
}

/**
 * Generate a secure random token
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Get time-ago string
 */
function timeAgo($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    
    if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0) return $diff->i . ' min' . ($diff->i > 1 ? 's' : '') . ' ago';
    return 'Just now';
}

/**
 * Truncate string
 */
function truncate($string, $length = 50) {
    if (strlen($string) <= $length) return $string;
    return substr($string, 0, $length) . '...';
}

/**
 * Get user initials for avatar
 */
function getInitials($name) {
    $words = explode(' ', trim($name));
    $initials = '';
    foreach ($words as $w) {
        $initials .= strtoupper(substr($w, 0, 1));
        if (strlen($initials) >= 2) break;
    }
    return $initials ?: '?';
}
/**
 * Check if the current organization has access to a specific module.
 * Logic:
 * 1. Super Admins always have access to everything.
 * 2. If NO records exist in organization_modules for an org, treat as legacy (ALL ALLOWED).
 * 3. Otherwise, explicitly require an exact match in organization_modules.
 */
function hasModuleAccess($module_name) {
    global $pdo;
    
    // Super Admins bypass module restrictions
    if (getUserRole() === 'super_admin') {
        return true;
    }
    
    $org_id = getOrgId();
    if (!$org_id) return true;
    
    try {
        // FAST CACHING: Store checked modules in RAM for the request
        static $orgModulesCount = null;
        static $allowedModules = [];
        
        // 1. Check if org has ANY module restrictions defined
        if ($orgModulesCount === null) {
            $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM organization_modules WHERE organization_id = ?");
            $stmtCount->execute([$org_id]);
            $orgModulesCount = (int)$stmtCount->fetchColumn();
        }
        
        // Legacy fallback: If 0 rules exist, they get FULL access
        if ($orgModulesCount === 0) {
            return true;
        }
        
        // 2. Fetch specific module access
        if (!isset($allowedModules[$module_name])) {
            $stmt = $pdo->prepare("SELECT 1 FROM organization_modules WHERE organization_id = ? AND module_name = ?");
            $stmt->execute([$org_id, $module_name]);
            $allowedModules[$module_name] = (bool)$stmt->fetchColumn();
        }
        
        return $allowedModules[$module_name];
        
    } catch (Exception $e) {
        // Fail open or fail closed? Fail closed for security.
        return false;
    }
}
?>
