<?php
/**
 * Main Configuration File
 * Contains all system-wide configuration settings
 */

// Prevent direct access
if (!defined('COURT_SYSTEM')) {
    define('COURT_SYSTEM', true);
}

// System Configuration
define('SITE_NAME', 'Digital Court Case Management System');
define('SITE_VERSION', '1.0.0');
define('TIMEZONE', 'UTC');

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'court_management');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// File Upload Configuration
define('MAX_UPLOAD_SIZE', 20 * 1024 * 1024); // 20MB
define('ALLOWED_FILE_TYPES', ['pdf', 'docx', 'doc', 'jpg', 'jpeg', 'png']);
define('UPLOAD_PATH', __DIR__ . '/../uploads/documents/');
define('TEMP_PATH', __DIR__ . '/../uploads/temp/');

// Security Configuration
define('SESSION_TIMEOUT', 3600); // 1 hour
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// Email Configuration
define('SMTP_HOST', 'localhost');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('FROM_EMAIL', 'noreply@court.example.com');
define('FROM_NAME', 'Court Management System');

// SMS Configuration
define('SMS_PROVIDER', 'twilio'); // or 'nexmo', 'custom'
define('SMS_API_KEY', '');
define('SMS_API_SECRET', '');

// Notification Settings
define('NOTIFICATION_LEAD_TIMES', [24, 2]); // hours before hearing

// Case Number Format
define('CASE_NUMBER_PREFIX', 'COURT');
define('CASE_NUMBER_FORMAT', 'COURT-YYYY-####');

// Court Rooms
define('COURT_ROOMS', [
    'Room 1 - Criminal Court',
    'Room 2 - Civil Court',
    'Room 3 - Family Court',
    'Room 4 - Appeals Court',
    'Virtual Court Room A',
    'Virtual Court Room B'
]);

// Case Types
define('CASE_TYPES', [
    'Criminal',
    'Civil',
    'Family',
    'Appeal',
    'Administrative',
    'Traffic',
    'Juvenile'
]);

// Case Status Options
define('CASE_STATUSES', [
    'Filed',
    'Pending',
    'In Hearing',
    'Under Review',
    'Judged',
    'Closed',
    'Dismissed',
    'Settled'
]);

// Priority Levels
define('PRIORITY_LEVELS', [
    'High',
    'Normal',
    'Low'
]);

// Document Types
define('DOCUMENT_TYPES', [
    'Filing',
    'Evidence',
    'Motion',
    'Judgment',
    'Witness Statement',
    'Legal Brief',
    'Court Order',
    'Other'
]);

// Hearing Types
define('HEARING_TYPES', [
    'Pre-trial',
    'Trial',
    'Sentencing',
    'Motion Hearing',
    'Status Conference',
    'Plea Hearing',
    'Appeal Hearing'
]);

// User Roles and Permissions
define('USER_ROLES', [
    'admin' => [
        'name' => 'System Administrator',
        'permissions' => [
            'view_all_cases',
            'create_case',
            'edit_case',
            'delete_case',
            'upload_document',
            'view_confidential',
            'schedule_hearing',
            'create_user',
            'edit_user',
            'delete_user',
            'generate_report',
            'view_audit_log',
            'system_config'
        ]
    ],
    'manager' => [
        'name' => 'Court Manager',
        'permissions' => [
            'view_all_cases',
            'create_case',
            'edit_case',
            'upload_document',
            'view_confidential',
            'schedule_hearing',
            'create_user',
            'edit_user',
            'generate_report',
            'view_audit_log'
        ]
    ],
    'judge' => [
        'name' => 'Judge',
        'permissions' => [
            'view_assigned_cases',
            'view_case_details',
            'upload_document',
            'view_confidential',
            'add_judgment',
            'update_case_status',
            'add_case_notes',
            'request_document'
        ]
    ],
    'clerk' => [
        'name' => 'Court Clerk',
        'permissions' => [
            'view_cases',
            'create_case',
            'edit_case',
            'upload_document',
            'schedule_hearing',
            'assign_judge',
            'send_notification'
        ]
    ],
    'prosecutor' => [
        'name' => 'Prosecutor/Defender',
        'permissions' => [
            'view_assigned_cases',
            'upload_document',
            'add_case_notes'
        ]
    ]
]);

// Set timezone
date_default_timezone_set(TIMEZONE);

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set upload limits
ini_set('upload_max_filesize', '20M');
ini_set('post_max_size', '25M');
ini_set('max_execution_time', 300);
?>