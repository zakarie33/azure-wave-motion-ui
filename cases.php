<?php
/**
 * Cases List Page
 * Shows all cases with filtering and pagination
 */

require_once 'config/config.php';

$controller = new CaseController();
$controller->index();
?>