<?php
/**
 * New Case Registration Page
 * Handles case creation form and processing
 */

require_once 'config/config.php';

$controller = new CaseController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller->store();
} else {
    $controller->create();
}
?>