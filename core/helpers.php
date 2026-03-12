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
?>
