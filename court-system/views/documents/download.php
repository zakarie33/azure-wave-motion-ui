<?php
/**
 * Document Download Handler
 */

define('COURT_SYSTEM', true);
require_once '../../includes/auth.php';
require_once '../../models/Document.php';

// Check if user is logged in
requireLogin();

$documentId = intval($_GET['id'] ?? 0);
if (!$documentId) {
    http_response_code(400);
    die('Invalid document ID');
}

try {
    $documentModel = new DocumentModel();
    $fileInfo = $documentModel->getFilePath($documentId);
    
    // Set headers for file download
    header('Content-Type: ' . $fileInfo['mime_type']);
    header('Content-Disposition: attachment; filename="' . $fileInfo['filename'] . '"');
    header('Content-Length: ' . filesize($fileInfo['path']));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    
    // Output file
    readfile($fileInfo['path']);
    
} catch (Exception $e) {
    http_response_code(403);
    die('Error: ' . $e->getMessage());
}
?>