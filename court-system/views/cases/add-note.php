<?php
/**
 * Add Case Note Handler
 */

define('COURT_SYSTEM', true);
require_once '../../includes/auth.php';
require_once '../../models/Case.php';

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('list.php', 'Invalid request method', 'error');
}

$caseId = intval($_POST['case_id'] ?? 0);
if (!$caseId) {
    redirect('list.php', 'Invalid case ID', 'error');
}

// Check if user can access this case
if (!canAccessCase($caseId)) {
    http_response_code(403);
    die('Access denied');
}

// Validate CSRF token
if (!validateCSRF($_POST['csrf_token'] ?? '')) {
    redirect("view.php?id={$caseId}", 'Invalid security token. Please try again.', 'error');
}

try {
    $caseModel = new CaseModel();
    
    $noteData = [
        'title' => sanitize($_POST['title'] ?? ''),
        'content' => sanitize($_POST['content'] ?? ''),
        'note_type' => sanitize($_POST['note_type'] ?? 'general'),
        'visibility' => sanitize($_POST['visibility'] ?? 'staff_only'),
        'reminder_date' => !empty($_POST['reminder_date']) ? sanitize($_POST['reminder_date']) : null
    ];
    
    if (empty($noteData['content'])) {
        throw new Exception('Note content is required');
    }
    
    $noteId = $caseModel->addNote($caseId, $noteData);
    
    redirect("view.php?id={$caseId}", 'Note added successfully!', 'success');
    
} catch (Exception $e) {
    redirect("view.php?id={$caseId}", $e->getMessage(), 'error');
}
?>