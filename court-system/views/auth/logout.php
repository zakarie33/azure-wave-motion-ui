<?php
/**
 * Logout Page
 */

define('COURT_SYSTEM', true);
require_once '../../includes/auth.php';

// Logout user
logoutUser();

// Redirect to login page with message
redirect('/court-system/views/auth/login.php', 'You have been successfully logged out.', 'success');
?>