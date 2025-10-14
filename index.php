<?php
/**
 * Index Page
 * Redirects to appropriate page based on authentication status
 */

require_once 'config/config.php';

if (is_logged_in()) {
    redirect('dashboard.php');
} else {
    redirect('login.php');
}
?>