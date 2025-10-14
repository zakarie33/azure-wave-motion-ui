<?php
/**
 * Authentication and Authorization Functions
 */

if (!defined('COURT_SYSTEM')) {
    define('COURT_SYSTEM', true);
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

/**
 * Require login - redirect to login page if not authenticated
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /court-system/views/auth/login.php');
        exit();
    }
}

/**
 * Check if user has specific permission
 */
function hasPermission($permission) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $userRole = $_SESSION['user_role'];
    $roles = USER_ROLES;
    
    return isset($roles[$userRole]['permissions']) && 
           in_array($permission, $roles[$userRole]['permissions']);
}

/**
 * Require specific permission
 */
function requirePermission($permission) {
    if (!hasPermission($permission)) {
        http_response_code(403);
        die('Access denied. You do not have permission to access this resource.');
    }
}

/**
 * Check if user can access case (based on role and case assignment)
 */
function canAccessCase($caseId) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $db = getDB();
    $userId = $_SESSION['user_id'];
    $userRole = $_SESSION['user_role'];
    
    // Admin and Manager can access all cases
    if (in_array($userRole, ['admin', 'manager'])) {
        return true;
    }
    
    // Get case details
    $case = $db->fetch("SELECT * FROM cases WHERE id = ?", [$caseId]);
    if (!$case) {
        return false;
    }
    
    // Check confidential flag
    if ($case['confidential'] && !in_array($userRole, ['admin', 'manager', 'judge'])) {
        return false;
    }
    
    // Judge can access assigned cases
    if ($userRole === 'judge') {
        return $case['assigned_judge_id'] == $userId;
    }
    
    // Clerk can access cases in their department or cases they created
    if ($userRole === 'clerk') {
        return $case['created_by'] == $userId || 
               ($case['department_id'] && $case['department_id'] == $_SESSION['department_id']);
    }
    
    // Prosecutor can access cases they're involved in
    if ($userRole === 'prosecutor') {
        // Check if user is listed as a participant in the case
        $participant = $db->fetch(
            "SELECT id FROM case_participants WHERE case_id = ? AND contact_email = ?",
            [$caseId, $_SESSION['user_email']]
        );
        return $participant !== false;
    }
    
    return false;
}

/**
 * Authenticate user login
 */
function authenticateUser($email, $password) {
    $db = getDB();
    
    // Get user by email
    $user = $db->fetch("SELECT * FROM users WHERE email = ? AND is_active = 1", [$email]);
    
    if (!$user) {
        logAudit('LOGIN_FAILED', null, null, null, null, ['email' => $email, 'reason' => 'user_not_found']);
        return false;
    }
    
    // Check if account is locked
    if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
        logAudit('LOGIN_FAILED', null, null, null, null, ['email' => $email, 'reason' => 'account_locked']);
        return false;
    }
    
    // Verify password
    if (!verifyPassword($password, $user['password_hash'])) {
        // Increment failed attempts
        $failedAttempts = $user['failed_login_attempts'] + 1;
        $updateData = ['failed_login_attempts' => $failedAttempts];
        
        // Lock account if too many failed attempts
        if ($failedAttempts >= MAX_LOGIN_ATTEMPTS) {
            $updateData['locked_until'] = date('Y-m-d H:i:s', time() + LOGIN_LOCKOUT_TIME);
        }
        
        $db->update('users', $updateData, 'id = ?', [$user['id']]);
        
        logAudit('LOGIN_FAILED', null, null, null, null, ['email' => $email, 'reason' => 'invalid_password']);
        return false;
    }
    
    // Reset failed attempts and update last login
    $db->update('users', [
        'failed_login_attempts' => 0,
        'locked_until' => null,
        'last_login' => date('Y-m-d H:i:s')
    ], 'id = ?', [$user['id']]);
    
    // Set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
    $_SESSION['department_id'] = $user['department_id'];
    $_SESSION['login_time'] = time();
    
    // Regenerate session ID for security
    session_regenerate_id(true);
    
    logAudit('LOGIN_SUCCESS', null, null, null, null, ['user_id' => $user['id']]);
    
    return $user;
}

/**
 * Logout user
 */
function logoutUser() {
    if (isLoggedIn()) {
        logAudit('LOGOUT', null, null, null, null, ['user_id' => $_SESSION['user_id']]);
    }
    
    // Clear all session data
    $_SESSION = array();
    
    // Delete session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Destroy session
    session_destroy();
    
    // Start new session for flash messages
    session_start();
}

/**
 * Check session timeout
 */
function checkSessionTimeout() {
    if (isLoggedIn() && isset($_SESSION['login_time'])) {
        if (time() - $_SESSION['login_time'] > SESSION_TIMEOUT) {
            logoutUser();
            return false;
        }
        // Update last activity time
        $_SESSION['login_time'] = time();
    }
    return true;
}

/**
 * Create new user account
 */
function createUser($userData) {
    $db = getDB();
    
    // Validate required fields
    $required = ['first_name', 'last_name', 'email', 'role', 'password'];
    foreach ($required as $field) {
        if (empty($userData[$field])) {
            throw new Exception("Field '{$field}' is required");
        }
    }
    
    // Validate email format
    if (!isValidEmail($userData['email'])) {
        throw new Exception("Invalid email format");
    }
    
    // Check if email already exists
    $existing = $db->fetch("SELECT id FROM users WHERE email = ?", [$userData['email']]);
    if ($existing) {
        throw new Exception("Email address already exists");
    }
    
    // Validate role
    if (!array_key_exists($userData['role'], USER_ROLES)) {
        throw new Exception("Invalid user role");
    }
    
    // Validate password strength
    if (strlen($userData['password']) < PASSWORD_MIN_LENGTH) {
        throw new Exception("Password must be at least " . PASSWORD_MIN_LENGTH . " characters long");
    }
    
    // Prepare user data
    $insertData = [
        'first_name' => sanitize($userData['first_name']),
        'last_name' => sanitize($userData['last_name']),
        'email' => strtolower(trim($userData['email'])),
        'password_hash' => hashPassword($userData['password']),
        'role' => $userData['role'],
        'department_id' => $userData['department_id'] ?? null,
        'phone' => sanitize($userData['phone'] ?? null),
        'is_active' => 1,
        'email_verified' => 0
    ];
    
    $userId = $db->insert('users', $insertData);
    
    logAudit('USER_CREATED', 'users', $userId, null, null, [
        'created_user_id' => $userId,
        'role' => $userData['role']
    ]);
    
    // Send welcome email if requested
    if (!empty($userData['send_welcome_email'])) {
        $subject = "Welcome to " . SITE_NAME;
        $message = "
            <p>Hello {$userData['first_name']},</p>
            <p>Your account has been created in the Court Management System.</p>
            <p><strong>Login Details:</strong><br>
            Email: {$userData['email']}<br>
            Role: " . USER_ROLES[$userData['role']]['name'] . "</p>
            <p>Please contact your administrator to set up your password.</p>
        ";
        sendEmail($userData['email'], $subject, $message, $userData['first_name'] . ' ' . $userData['last_name']);
    }
    
    return $userId;
}

/**
 * Update user profile
 */
function updateUserProfile($userId, $userData) {
    $db = getDB();
    
    // Get current user data
    $currentUser = $db->fetch("SELECT * FROM users WHERE id = ?", [$userId]);
    if (!$currentUser) {
        throw new Exception("User not found");
    }
    
    $updateData = [];
    $oldValues = [];
    $newValues = [];
    
    // Update allowed fields
    $allowedFields = ['first_name', 'last_name', 'phone'];
    foreach ($allowedFields as $field) {
        if (isset($userData[$field]) && $userData[$field] !== $currentUser[$field]) {
            $oldValues[$field] = $currentUser[$field];
            $newValues[$field] = sanitize($userData[$field]);
            $updateData[$field] = $newValues[$field];
        }
    }
    
    // Handle email change (requires verification)
    if (isset($userData['email']) && $userData['email'] !== $currentUser['email']) {
        if (!isValidEmail($userData['email'])) {
            throw new Exception("Invalid email format");
        }
        
        // Check if email already exists
        $existing = $db->fetch("SELECT id FROM users WHERE email = ? AND id != ?", [$userData['email'], $userId]);
        if ($existing) {
            throw new Exception("Email address already exists");
        }
        
        $oldValues['email'] = $currentUser['email'];
        $newValues['email'] = strtolower(trim($userData['email']));
        $updateData['email'] = $newValues['email'];
        $updateData['email_verified'] = 0; // Require re-verification
    }
    
    if (!empty($updateData)) {
        $db->update('users', $updateData, 'id = ?', [$userId]);
        
        logAudit('PROFILE_UPDATED', 'users', $userId, null, $oldValues, $newValues);
        
        // Update session if current user
        if ($_SESSION['user_id'] == $userId) {
            if (isset($newValues['email'])) {
                $_SESSION['user_email'] = $newValues['email'];
            }
            if (isset($newValues['first_name']) || isset($newValues['last_name'])) {
                $user = $db->fetch("SELECT first_name, last_name FROM users WHERE id = ?", [$userId]);
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            }
        }
    }
    
    return true;
}

/**
 * Change user password
 */
function changePassword($userId, $currentPassword, $newPassword) {
    $db = getDB();
    
    // Get current user
    $user = $db->fetch("SELECT password_hash FROM users WHERE id = ?", [$userId]);
    if (!$user) {
        throw new Exception("User not found");
    }
    
    // Verify current password
    if (!verifyPassword($currentPassword, $user['password_hash'])) {
        throw new Exception("Current password is incorrect");
    }
    
    // Validate new password
    if (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
        throw new Exception("Password must be at least " . PASSWORD_MIN_LENGTH . " characters long");
    }
    
    // Update password
    $newHash = hashPassword($newPassword);
    $db->update('users', ['password_hash' => $newHash], 'id = ?', [$userId]);
    
    logAudit('PASSWORD_CHANGED', 'users', $userId);
    
    return true;
}

/**
 * Get user permissions
 */
function getUserPermissions($role = null) {
    $role = $role ?? $_SESSION['user_role'];
    return USER_ROLES[$role]['permissions'] ?? [];
}

/**
 * Get role display name
 */
function getRoleDisplayName($role) {
    return USER_ROLES[$role]['name'] ?? $role;
}

/**
 * Initialize session security
 */
function initializeSession() {
    // Set secure session parameters
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
    ini_set('session.use_strict_mode', 1);
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check session timeout
    checkSessionTimeout();
}

// Initialize session when this file is included
initializeSession();
?>