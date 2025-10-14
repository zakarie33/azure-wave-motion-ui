<?php
/**
 * Application Configuration
 * Digital Court Case Management System
 */

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Application Settings
define('APP_NAME', 'Digital Court Management System');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://localhost/court-management/');

// File Upload Settings
define('MAX_FILE_SIZE', 20 * 1024 * 1024); // 20MB
define('ALLOWED_FILE_TYPES', ['pdf', 'docx', 'doc', 'jpg', 'jpeg', 'png']);
define('UPLOAD_PATH', 'uploads/documents/');

// Security Settings
define('SESSION_TIMEOUT', 3600); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// Email Settings
define('SMTP_HOST', 'localhost');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('FROM_EMAIL', 'noreply@court.gov');
define('FROM_NAME', 'Court Management System');

// Timezone
date_default_timezone_set('UTC');

// Error Reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require_once 'database.php';

// Autoloader for classes
spl_autoload_register(function ($class_name) {
    $directories = [
        'models/',
        'controllers/',
        'utils/',
        'middleware/'
    ];
    
    foreach ($directories as $directory) {
        $file = $directory . $class_name . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Helper Functions
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function generate_case_number() {
    $year = date('Y');
    $prefix = 'COURT-' . $year . '-';
    
    // Get last case number for this year
    $db = new Database();
    $conn = $db->getConnection();
    
    $query = "SELECT case_no FROM cases WHERE case_no LIKE :prefix ORDER BY id DESC LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':prefix', $prefix . '%');
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $last_case = $stmt->fetch();
        $last_number = intval(substr($last_case['case_no'], -4));
        $new_number = $last_number + 1;
    } else {
        $new_number = 1;
    }
    
    return $prefix . str_pad($new_number, 4, '0', STR_PAD_LEFT);
}

function is_logged_in() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

function check_permission($required_role) {
    if (!is_logged_in()) {
        return false;
    }
    
    $user_role = $_SESSION['user_role'];
    $role_hierarchy = [
        'admin' => 5,
        'manager' => 4,
        'judge' => 3,
        'clerk' => 2,
        'prosecutor' => 1
    ];
    
    return isset($role_hierarchy[$user_role]) && 
           isset($role_hierarchy[$required_role]) && 
           $role_hierarchy[$user_role] >= $role_hierarchy[$required_role];
}

function redirect($url) {
    header("Location: " . $url);
    exit();
}

function flash_message($type, $message) {
    $_SESSION['flash'][$type] = $message;
}

function get_flash_messages() {
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}
?>