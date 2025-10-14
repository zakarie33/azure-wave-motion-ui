<?php
/**
 * Core utility functions for the Court Management System
 */

if (!defined('COURT_SYSTEM')) {
    define('COURT_SYSTEM', true);
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Sanitize input data
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email address
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Generate secure random token
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Hash password securely
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_ARGON2ID);
}

/**
 * Verify password against hash
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Generate unique case number
 */
function generateCaseNumber() {
    $db = getDB();
    
    // Get current year
    $year = date('Y');
    
    // Get next case number for this year
    $sql = "SELECT setting_value FROM system_settings WHERE setting_key = 'case_number_counter'";
    $counter = $db->fetch($sql)['setting_value'] ?? 1;
    
    // Format: COURT-YYYY-0001
    $caseNumber = CASE_NUMBER_PREFIX . '-' . $year . '-' . str_pad($counter, 4, '0', STR_PAD_LEFT);
    
    // Check if case number already exists (shouldn't happen, but safety check)
    $sql = "SELECT id FROM cases WHERE case_no = ?";
    $exists = $db->fetch($sql, [$caseNumber]);
    
    if ($exists) {
        // Increment counter and try again
        $counter++;
        $db->update('system_settings', 
            ['setting_value' => $counter], 
            'setting_key = ?', 
            ['case_number_counter']
        );
        return generateCaseNumber();
    }
    
    // Update counter for next case
    $db->update('system_settings', 
        ['setting_value' => $counter + 1], 
        'setting_key = ?', 
        ['case_number_counter']
    );
    
    return $caseNumber;
}

/**
 * Log audit trail
 */
function logAudit($action, $tableName = null, $recordId = null, $caseId = null, $oldValues = null, $newValues = null) {
    $db = getDB();
    
    $data = [
        'user_id' => $_SESSION['user_id'] ?? null,
        'action' => $action,
        'table_name' => $tableName,
        'record_id' => $recordId,
        'case_id' => $caseId,
        'old_values' => $oldValues ? json_encode($oldValues) : null,
        'new_values' => $newValues ? json_encode($newValues) : null,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        'session_id' => session_id()
    ];
    
    $db->insert('audit_logs', $data);
}

/**
 * Send notification to user
 */
function sendNotification($recipientId, $title, $message, $type = 'general', $caseId = null, $hearingId = null, $priority = 'Normal', $sendEmail = true, $sendSms = false) {
    $db = getDB();
    
    $data = [
        'recipient_id' => $recipientId,
        'sender_id' => $_SESSION['user_id'] ?? null,
        'case_id' => $caseId,
        'hearing_id' => $hearingId,
        'type' => $type,
        'title' => $title,
        'message' => $message,
        'priority' => $priority,
        'send_email' => $sendEmail ? 1 : 0,
        'send_sms' => $sendSms ? 1 : 0
    ];
    
    $notificationId = $db->insert('notifications', $data);
    
    // Queue for actual sending (email/SMS)
    if ($sendEmail) {
        queueEmail($recipientId, $title, $message);
    }
    
    if ($sendSms) {
        queueSms($recipientId, $message);
    }
    
    return $notificationId;
}

/**
 * Queue email for sending
 */
function queueEmail($userId, $subject, $message) {
    // Get user email
    $db = getDB();
    $user = $db->fetch("SELECT email, first_name, last_name FROM users WHERE id = ?", [$userId]);
    
    if ($user) {
        // In a real implementation, you would queue this for background processing
        // For now, we'll send immediately
        sendEmail($user['email'], $subject, $message, $user['first_name'] . ' ' . $user['last_name']);
    }
}

/**
 * Send email (basic implementation)
 */
function sendEmail($to, $subject, $message, $toName = '') {
    // Basic email headers
    $headers = [
        'From: ' . FROM_NAME . ' <' . FROM_EMAIL . '>',
        'Reply-To: ' . FROM_EMAIL,
        'X-Mailer: Court Management System',
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8'
    ];
    
    $htmlMessage = "
    <html>
    <head>
        <title>{$subject}</title>
    </head>
    <body>
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <h2 style='color: #2c3e50;'>" . SITE_NAME . "</h2>
            <div style='background: #f8f9fa; padding: 20px; border-radius: 5px;'>
                {$message}
            </div>
            <p style='color: #7f8c8d; font-size: 12px; margin-top: 20px;'>
                This is an automated message from the Court Management System.
            </p>
        </div>
    </body>
    </html>";
    
    return mail($to, $subject, $htmlMessage, implode("\r\n", $headers));
}

/**
 * Queue SMS for sending
 */
function queueSms($userId, $message) {
    // Get user phone
    $db = getDB();
    $user = $db->fetch("SELECT phone FROM users WHERE id = ? AND phone IS NOT NULL", [$userId]);
    
    if ($user && !empty($user['phone'])) {
        sendSms($user['phone'], $message);
    }
}

/**
 * Send SMS (placeholder implementation)
 */
function sendSms($phone, $message) {
    // Implement SMS sending based on your provider (Twilio, Nexmo, etc.)
    // For now, just log it
    error_log("SMS to {$phone}: {$message}");
    return true;
}

/**
 * Format file size
 */
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, 2) . ' ' . $units[$pow];
}

/**
 * Check if file type is allowed
 */
function isAllowedFileType($filename) {
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($extension, ALLOWED_FILE_TYPES);
}

/**
 * Generate secure filename
 */
function generateSecureFilename($originalFilename) {
    $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    return $filename;
}

/**
 * Calculate file hash
 */
function calculateFileHash($filePath) {
    return hash_file('sha256', $filePath);
}

/**
 * Get user's full name
 */
function getUserFullName($userId) {
    $db = getDB();
    $user = $db->fetch("SELECT first_name, last_name FROM users WHERE id = ?", [$userId]);
    return $user ? $user['first_name'] . ' ' . $user['last_name'] : 'Unknown User';
}

/**
 * Get case status badge HTML
 */
function getCaseStatusBadge($status) {
    $badges = [
        'Filed' => 'badge-primary',
        'Pending' => 'badge-warning',
        'In Hearing' => 'badge-info',
        'Under Review' => 'badge-secondary',
        'Judged' => 'badge-success',
        'Closed' => 'badge-dark',
        'Dismissed' => 'badge-danger',
        'Settled' => 'badge-success'
    ];
    
    $class = $badges[$status] ?? 'badge-secondary';
    return "<span class='badge {$class}'>{$status}</span>";
}

/**
 * Get priority badge HTML
 */
function getPriorityBadge($priority) {
    $badges = [
        'High' => 'badge-danger',
        'Normal' => 'badge-primary',
        'Low' => 'badge-secondary'
    ];
    
    $class = $badges[$priority] ?? 'badge-secondary';
    return "<span class='badge {$class}'>{$priority}</span>";
}

/**
 * Format date for display
 */
function formatDate($date, $format = 'M j, Y') {
    if (!$date) return '-';
    return date($format, strtotime($date));
}

/**
 * Format datetime for display
 */
function formatDateTime($datetime, $format = 'M j, Y g:i A') {
    if (!$datetime) return '-';
    return date($format, strtotime($datetime));
}

/**
 * Get time ago string
 */
function timeAgo($datetime) {
    if (!$datetime) return '-';
    
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' months ago';
    return floor($time/31536000) . ' years ago';
}

/**
 * Paginate results
 */
function paginate($totalRecords, $recordsPerPage = 20, $currentPage = 1) {
    $totalPages = ceil($totalRecords / $recordsPerPage);
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $recordsPerPage;
    
    return [
        'total_records' => $totalRecords,
        'total_pages' => $totalPages,
        'current_page' => $currentPage,
        'records_per_page' => $recordsPerPage,
        'offset' => $offset,
        'has_previous' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages
    ];
}

/**
 * Generate pagination HTML
 */
function generatePaginationHTML($pagination, $baseUrl) {
    if ($pagination['total_pages'] <= 1) return '';
    
    $html = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
    
    // Previous button
    if ($pagination['has_previous']) {
        $prevPage = $pagination['current_page'] - 1;
        $html .= "<li class='page-item'><a class='page-link' href='{$baseUrl}&page={$prevPage}'>Previous</a></li>";
    } else {
        $html .= "<li class='page-item disabled'><span class='page-link'>Previous</span></li>";
    }
    
    // Page numbers
    $start = max(1, $pagination['current_page'] - 2);
    $end = min($pagination['total_pages'], $pagination['current_page'] + 2);
    
    if ($start > 1) {
        $html .= "<li class='page-item'><a class='page-link' href='{$baseUrl}&page=1'>1</a></li>";
        if ($start > 2) {
            $html .= "<li class='page-item disabled'><span class='page-link'>...</span></li>";
        }
    }
    
    for ($i = $start; $i <= $end; $i++) {
        if ($i == $pagination['current_page']) {
            $html .= "<li class='page-item active'><span class='page-link'>{$i}</span></li>";
        } else {
            $html .= "<li class='page-item'><a class='page-link' href='{$baseUrl}&page={$i}'>{$i}</a></li>";
        }
    }
    
    if ($end < $pagination['total_pages']) {
        if ($end < $pagination['total_pages'] - 1) {
            $html .= "<li class='page-item disabled'><span class='page-link'>...</span></li>";
        }
        $html .= "<li class='page-item'><a class='page-link' href='{$baseUrl}&page={$pagination['total_pages']}'>{$pagination['total_pages']}</a></li>";
    }
    
    // Next button
    if ($pagination['has_next']) {
        $nextPage = $pagination['current_page'] + 1;
        $html .= "<li class='page-item'><a class='page-link' href='{$baseUrl}&page={$nextPage}'>Next</a></li>";
    } else {
        $html .= "<li class='page-item disabled'><span class='page-link'>Next</span></li>";
    }
    
    $html .= '</ul></nav>';
    
    return $html;
}

/**
 * Redirect with message
 */
function redirect($url, $message = null, $type = 'success') {
    if ($message) {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }
    header("Location: {$url}");
    exit();
}

/**
 * Display flash message
 */
function displayFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        
        $alertClass = [
            'success' => 'alert-success',
            'error' => 'alert-danger',
            'warning' => 'alert-warning',
            'info' => 'alert-info'
        ][$type] ?? 'alert-info';
        
        echo "<div class='alert {$alertClass} alert-dismissible fade show' role='alert'>";
        echo htmlspecialchars($message);
        echo "<button type='button' class='btn-close' data-bs-dismiss='alert'></button>";
        echo "</div>";
    }
}

/**
 * JSON response helper
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

/**
 * Validate CSRF token
 */
function validateCSRF($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Generate CSRF token
 */
function generateCSRF() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = generateToken(16);
    }
    return $_SESSION['csrf_token'];
}

/**
 * Get CSRF hidden input
 */
function csrfInput() {
    $token = generateCSRF();
    return "<input type='hidden' name='csrf_token' value='{$token}'>";
}
?>