<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'notary_management');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application Configuration
define('APP_NAME', 'Notary Management System');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://localhost/notary-management-system/');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 10485760); // 10MB in bytes
define('ALLOWED_FILE_TYPES', ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png']);

// Security Configuration
define('SESSION_TIMEOUT', 3600); // 1 hour
define('PASSWORD_MIN_LENGTH', 8);
define('ENCRYPTION_KEY', 'your-secret-encryption-key-change-this');

// Email Configuration (for notifications)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
define('FROM_EMAIL', 'noreply@notary.com');
define('FROM_NAME', 'Notary Management System');

// Time Zone
date_default_timezone_set('America/Los_Angeles');

// Error Reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_lifetime', SESSION_TIMEOUT);
?>