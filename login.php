<?php
/**
 * Login Page
 * Entry point for user authentication
 */

require_once 'config/config.php';

$auth = new AuthController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth->processLogin();
} else {
    $auth->showLogin();
}
?>