<?php
/**
 * File Verification Script
 * Author: Court System Development Team
 * Purpose: Verify integrity and completeness of all system files
 * Version: 1.0.0
 */

echo "🏛️ Digital Court Case Management System - File Verification\n";
echo "===========================================================\n\n";

$files = [
    // Core System Files
    'index.php' => [
        'type' => 'Core Entry Point',
        'description' => 'Main system entry point with role-based routing',
        'required' => true
    ],
    'README.md' => [
        'type' => 'Documentation',
        'description' => 'Comprehensive system documentation',
        'required' => true
    ],
    'INSTALLATION.md' => [
        'type' => 'Documentation',
        'description' => 'Complete installation guide',
        'required' => true
    ],
    'FILE_MANIFEST.md' => [
        'type' => 'Documentation',
        'description' => 'Complete file listing and descriptions',
        'required' => true
    ],
    'LICENSE' => [
        'type' => 'Legal',
        'description' => 'MIT License file',
        'required' => true
    ],
    'composer.json' => [
        'type' => 'Configuration',
        'description' => 'Composer package configuration',
        'required' => false
    ],
    '.htaccess' => [
        'type' => 'Configuration',
        'description' => 'Apache security and rewrite rules',
        'required' => false
    ],

    // Configuration Files
    'config/config.php' => [
        'type' => 'Configuration',
        'description' => 'Main system configuration',
        'required' => true
    ],
    'config/database.php' => [
        'type' => 'Configuration',
        'description' => 'Database connection management',
        'required' => true
    ],

    // Database Files
    'database/schema.sql' => [
        'type' => 'Database',
        'description' => 'Complete database schema',
        'required' => true
    ],
    'database/install.php' => [
        'type' => 'Database',
        'description' => 'Database installation script',
        'required' => true
    ],

    // Core Includes
    'includes/auth.php' => [
        'type' => 'Core Library',
        'description' => 'Authentication and authorization system',
        'required' => true
    ],
    'includes/functions.php' => [
        'type' => 'Core Library',
        'description' => 'Common utility functions',
        'required' => true
    ],

    // Models
    'models/Case.php' => [
        'type' => 'Data Model',
        'description' => 'Case management model',
        'required' => true
    ],
    'models/Document.php' => [
        'type' => 'Data Model',
        'description' => 'Document management model',
        'required' => true
    ],

    // Authentication Views
    'views/auth/login.php' => [
        'type' => 'View',
        'description' => 'User login interface',
        'required' => true
    ],
    'views/auth/logout.php' => [
        'type' => 'View',
        'description' => 'Logout handler',
        'required' => true
    ],

    // Case Management Views
    'views/cases/list.php' => [
        'type' => 'View',
        'description' => 'Cases listing with search and filters',
        'required' => true
    ],
    'views/cases/create.php' => [
        'type' => 'View',
        'description' => 'New case registration form',
        'required' => true
    ],
    'views/cases/view.php' => [
        'type' => 'View',
        'description' => 'Complete case details view',
        'required' => true
    ],
    'views/cases/add-note.php' => [
        'type' => 'View',
        'description' => 'Case notes handler',
        'required' => true
    ],

    // Document Management Views
    'views/documents/list.php' => [
        'type' => 'View',
        'description' => 'Documents listing interface',
        'required' => true
    ],
    'views/documents/upload.php' => [
        'type' => 'View',
        'description' => 'Document upload interface',
        'required' => true
    ],
    'views/documents/view.php' => [
        'type' => 'View',
        'description' => 'Document viewer with preview',
        'required' => true
    ],
    'views/documents/download.php' => [
        'type' => 'Handler',
        'description' => 'Secure document download handler',
        'required' => true
    ],
    'views/documents/delete.php' => [
        'type' => 'Handler',
        'description' => 'Document deletion handler',
        'required' => true
    ],

    // Dashboard Views
    'views/dashboard/admin.php' => [
        'type' => 'Dashboard',
        'description' => 'Administrator dashboard',
        'required' => true
    ],
    'views/dashboard/judge.php' => [
        'type' => 'Dashboard',
        'description' => 'Judge dashboard',
        'required' => true
    ],
    'views/dashboard/clerk.php' => [
        'type' => 'Dashboard',
        'description' => 'Court clerk dashboard',
        'required' => true
    ],
    'views/dashboard/prosecutor.php' => [
        'type' => 'Dashboard',
        'description' => 'Prosecutor dashboard',
        'required' => true
    ],

    // Layout Templates
    'views/layouts/header.php' => [
        'type' => 'Layout',
        'description' => 'Common page header template',
        'required' => true
    ],
    'views/layouts/footer.php' => [
        'type' => 'Layout',
        'description' => 'Common page footer template',
        'required' => true
    ],

    // Error Pages
    'views/errors/404.php' => [
        'type' => 'Error Page',
        'description' => 'Page not found error',
        'required' => false
    ],
    'views/errors/403.php' => [
        'type' => 'Error Page',
        'description' => 'Access forbidden error',
        'required' => false
    ],
    'views/errors/500.php' => [
        'type' => 'Error Page',
        'description' => 'Internal server error',
        'required' => false
    ],

    // Assets
    'assets/css/style.css' => [
        'type' => 'Stylesheet',
        'description' => 'Main system stylesheet',
        'required' => true
    ],
    'assets/js/app.js' => [
        'type' => 'JavaScript',
        'description' => 'Main application JavaScript',
        'required' => true
    ]
];

$totalFiles = count($files);
$existingFiles = 0;
$missingFiles = 0;
$requiredMissing = 0;

echo "📁 Checking System Files...\n";
echo "Total files to verify: {$totalFiles}\n\n";

foreach ($files as $file => $info) {
    $exists = file_exists($file);
    $status = $exists ? '✅' : '❌';
    $required = $info['required'] ? '[REQUIRED]' : '[OPTIONAL]';
    
    echo sprintf("%-50s %s %s %s\n", 
        $file, 
        $status, 
        $required,
        $info['type']
    );
    
    if ($exists) {
        $existingFiles++;
        $size = filesize($file);
        $sizeFormatted = formatBytes($size);
        echo sprintf("   └─ %s - %s\n", $info['description'], $sizeFormatted);
    } else {
        $missingFiles++;
        if ($info['required']) {
            $requiredMissing++;
        }
        echo sprintf("   └─ MISSING: %s\n", $info['description']);
    }
    echo "\n";
}

// Check directories
echo "📂 Checking Required Directories...\n";
$directories = [
    'uploads/' => 'Document upload directory',
    'uploads/documents/' => 'Document storage directory',
    'uploads/temp/' => 'Temporary file directory'
];

foreach ($directories as $dir => $desc) {
    $exists = is_dir($dir);
    $status = $exists ? '✅' : '❌';
    $writable = $exists && is_writable($dir) ? '(Writable)' : '(Not Writable)';
    
    echo sprintf("%-30s %s %s %s\n", $dir, $status, $writable, $desc);
}

echo "\n";

// Summary
echo "📊 VERIFICATION SUMMARY\n";
echo "======================\n";
echo "Total Files: {$totalFiles}\n";
echo "Existing Files: {$existingFiles}\n";
echo "Missing Files: {$missingFiles}\n";
echo "Missing Required Files: {$requiredMissing}\n";

$completeness = round(($existingFiles / $totalFiles) * 100, 1);
echo "System Completeness: {$completeness}%\n\n";

if ($requiredMissing > 0) {
    echo "⚠️  WARNING: {$requiredMissing} required files are missing!\n";
    echo "The system may not function properly.\n\n";
} elseif ($missingFiles > 0) {
    echo "ℹ️  INFO: {$missingFiles} optional files are missing.\n";
    echo "The system should function normally.\n\n";
} else {
    echo "🎉 SUCCESS: All files are present!\n";
    echo "The system is ready for deployment.\n\n";
}

// File signatures
echo "🔐 FILE SIGNATURES\n";
echo "==================\n";
foreach ($files as $file => $info) {
    if (file_exists($file)) {
        $hash = hash_file('sha256', $file);
        $size = filesize($file);
        echo sprintf("%-40s %s (%d bytes)\n", $file, substr($hash, 0, 16) . '...', $size);
    }
}

echo "\n";
echo "📝 Verification completed at: " . date('Y-m-d H:i:s') . "\n";
echo "🏛️ Digital Court Case Management System v1.0.0\n";
echo "👨‍💻 Author: Court System Development Team\n";

function formatBytes($size, $precision = 2) {
    $base = log($size, 1024);
    $suffixes = array('B', 'KB', 'MB', 'GB', 'TB');
    return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
}
?>