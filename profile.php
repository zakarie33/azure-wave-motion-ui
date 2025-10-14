<?php
/**
 * User Profile Page
 * Allows users to view and edit their profile
 */

require_once 'config/config.php';

$auth = new AuthController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_profile':
                $auth->updateProfile();
                break;
            case 'change_password':
                $auth->changePassword();
                break;
        }
    }
} else {
    $auth->showProfile();
}
?>