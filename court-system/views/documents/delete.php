<?php
/**
 * Delete Document Handler
 */

define('COURT_SYSTEM', true);
require_once '../../includes/auth.php';
require_once '../../models/Document.php';

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
}

// Validate CSRF token
if (!validateCSRF($_POST['csrf_token'] ?? '')) {
    jsonResponse(['success' => false, 'message' => 'Invalid security token'], 403);
}

$documentId = intval($_POST['id'] ?? 0);
if (!$documentId) {
    jsonResponse(['success' => false, 'message' => 'Invalid document ID'], 400);
}

try {
    $documentModel = new DocumentModel();
    $documentModel->delete($documentId);
    
    jsonResponse(['success' => true, 'message' => 'Document deleted successfully']);
    
} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
}
?>