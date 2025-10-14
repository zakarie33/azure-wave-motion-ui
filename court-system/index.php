<?php
/**
 * Digital Court Case Management and Automation System
 * Main Entry Point
 * 
 * @author Court System Development Team
 * @version 1.0.0
 */

session_start();

// Include configuration and core files
require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: views/auth/login.php');
    exit();
}

// Get user role and redirect to appropriate dashboard
$userRole = $_SESSION['user_role'];

switch ($userRole) {
    case 'admin':
    case 'manager':
        header('Location: views/dashboard/admin.php');
        break;
    case 'judge':
        header('Location: views/dashboard/judge.php');
        break;
    case 'clerk':
        header('Location: views/dashboard/clerk.php');
        break;
    case 'prosecutor':
        header('Location: views/dashboard/prosecutor.php');
        break;
    default:
        header('Location: views/auth/login.php');
        break;
}
exit();
?>