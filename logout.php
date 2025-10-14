<?php
/**
 * Logout Page
 * Handles user logout
 */

require_once 'config/config.php';

$auth = new AuthController();
$auth->logout();
?>