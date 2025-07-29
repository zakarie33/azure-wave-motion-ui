<?php
require_once 'config/config.php';
require_once 'includes/Auth.php';

$auth = new Auth();

// Check if user is already logged in
if ($auth->isLoggedIn()) {
    $sessionData = $auth->getSessionData();
    $redirectUrl = '';
    
    switch ($sessionData['role']) {
        case 'admin':
            $redirectUrl = 'admin/dashboard.php';
            break;
        case 'notary':
            $redirectUrl = 'notary/dashboard.php';
            break;
        case 'client':
            $redirectUrl = 'client/dashboard.php';
            break;
        default:
            $redirectUrl = 'login.php';
    }
    
    header("Location: $redirectUrl");
    exit();
} else {
    // Redirect to login page
    header('Location: login.php');
    exit();
}
?>