<?php
/**
 * View Document Page
 */

define('COURT_SYSTEM', true);
require_once '../../includes/auth.php';
require_once '../../models/Document.php';

$documentId = intval($_GET['id'] ?? 0);
if (!$documentId) {
    redirect('list.php', 'Invalid document ID', 'error');
}

$documentModel = new DocumentModel();
$document = $documentModel->getById($documentId);

if (!$document) {
    redirect('list.php', 'Document not found', 'error');
}

// Check if user can access this document
if (!canAccessCase($document['case_id'])) {
    http_response_code(403);
    die('Access denied. You do not have permission to view this document.');
}

// Check visibility restrictions
$userRole = $_SESSION['user_role'];
if (!in_array($userRole, ['admin', 'manager'])) {
    if ($document['visibility'] === 'judge_only' && $userRole !== 'judge') {
        http_response_code(403);
        die('This document is restricted to judges only.');
    }
    if ($document['visibility'] === 'admin_only') {
        http_response_code(403);
        die('This document is restricted to administrators only.');
    }
}

$pageTitle = 'Document: ' . $document['title'];
include '../layouts/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1>
            <?php
            $iconClass = 'bi-file-earmark-text';
            if (strpos($document['mime_type'], 'pdf') !== false) {
                $iconClass = 'bi-file-earmark-pdf text-danger';
            } elseif (strpos($document['mime_type'], 'word') !== false || strpos($document['mime_type'], 'document') !== false) {
                $iconClass = 'bi-file-earmark-word text-primary';
            } elseif (strpos($document['mime_type'], 'image') !== false) {
                $iconClass = 'bi-file-earmark-image text-info';
            }
            ?>
            <i class="bi <?= $iconClass ?> me-2"></i>
            <?= htmlspecialchars($document['title']) ?>
        </h1>
        <p class="text-muted mb-0">
            Case: <a href="../cases/view.php?id=<?= $document['case_id'] ?>" class="text-decoration-none">
                <?= htmlspecialchars($document['case_no']) ?> - <?= htmlspecialchars($document['case_title']) ?>
            </a>
        </p>
    </div>
    <div class="btn-group">
        <a href="list.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to Documents
        </a>
        <a href="download.php?id=<?= $document['id'] ?>" class="btn btn-success">
            <i class="bi bi-download me-2"></i>Download
        </a>
        <?php if (hasPermission('edit_case') || $document['uploaded_by'] == $_SESSION['user_id']): ?>
        <a href="edit.php?id=<?= $document['id'] ?>" class="btn btn-outline-primary">
            <i class="bi bi-pencil me-2"></i>Edit
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <!-- Document Preview -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-eye me-2"></i>Document Preview</h5>
            </div>
            <div class="card-body">
                <?php if (strpos($document['mime_type'], 'pdf') !== false): ?>
                <!-- PDF Preview -->
                <div class="text-center">
                    <embed src="download.php?id=<?= $document['id'] ?>" 
                           type="application/pdf" 
                           width="100%" 
                           height="600px">
                    <p class="mt-3">
                        <a href="download.php?id=<?= $document['id'] ?>" class="btn btn-primary">
                            <i class="bi bi-download me-2"></i>Download PDF
                        </a>
                    </p>
                </div>
                
                <?php elseif (strpos($document['mime_type'], 'image') !== false): ?>
                <!-- Image Preview -->
                <div class="text-center">
                    <img src="download.php?id=<?= $document['id'] ?>" 
                         class="img-fluid" 
                         alt="<?= htmlspecialchars($document['title']) ?>"
                         style="max-height: 600px;">
                </div>
                
                <?php else: ?>
                <!-- Other file types -->
                <div class="text-center py-5">
                    <i class="bi <?= $iconClass ?> display-1 text-muted"></i>
                    <h4 class="mt-3"><?= htmlspecialchars($document['original_filename']) ?></h4>
                    <p class="text-muted">Preview not available for this file type.</p>
                    <a href="download.php?id=<?= $document['id'] ?>" class="btn btn-primary">
                        <i class="bi bi-download me-2"></i>Download File
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Document History -->
        <?php
        $history = $documentModel->db->fetchAll("
            SELECT al.*, CONCAT(u.first_name, ' ', u.last_name) as user_name
            FROM audit_logs al
            LEFT JOIN users u ON al.user_id = u.id
            WHERE al.table_name = 'documents' AND al.record_id = ?
            ORDER BY al.created_at DESC
        ", [$document['id']]);
        
        if (!empty($history)):
        ?>
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Document History</h5>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <?php foreach ($history as $entry): ?>
                    <div class="timeline-item mb-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong><?= htmlspecialchars($entry['action']) ?></strong>
                                <?php if ($entry['user_name']): ?>
                                by <?= htmlspecialchars($entry['user_name']) ?>
                                <?php endif; ?>
                            </div>
                            <small class="text-muted"><?= formatDateTime($entry['created_at']) ?></small>
                        </div>
                        <?php if ($entry['new_values']): ?>
                        <small class="text-muted">
                            Changes: <?= htmlspecialchars(json_encode(json_decode($entry['new_values']), JSON_PRETTY_PRINT)) ?>
                        </small>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="col-lg-4">
        <!-- Document Details -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Document Details</h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless table-sm">
                    <tr>
                        <th width="100">Type:</th>
                        <td><span class="badge bg-info"><?= htmlspecialchars($document['document_type']) ?></span></td>
                    </tr>
                    <tr>
                        <th>Visibility:</th>
                        <td>
                            <i class="bi bi-<?= $document['visibility'] === 'public' ? 'globe' : 'lock' ?> me-1"></i>
                            <?= ucfirst(str_replace('_', ' ', $document['visibility'])) ?>
                        </td>
                    </tr>
                    <tr>
                        <th>File Size:</th>
                        <td><?= formatFileSize($document['file_size']) ?></td>
                    </tr>
                    <tr>
                        <th>File Type:</th>
                        <td><?= htmlspecialchars($document['mime_type']) ?></td>
                    </tr>
                    <tr>
                        <th>Original Name:</th>
                        <td><?= htmlspecialchars($document['original_filename']) ?></td>
                    </tr>
                    <tr>
                        <th>Uploaded By:</th>
                        <td><?= htmlspecialchars($document['uploaded_by_name']) ?></td>
                    </tr>
                    <tr>
                        <th>Upload Date:</th>
                        <td><?= formatDateTime($document['uploaded_at']) ?></td>
                    </tr>
                    <?php if ($document['signed_by']): ?>
                    <tr>
                        <th>Signed By:</th>
                        <td><?= htmlspecialchars($document['signed_by']) ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <th>Version:</th>
                        <td><?= $document['version'] ?></td>
                    </tr>
                </table>
                
                <?php if ($document['description']): ?>
                <hr>
                <h6><i class="bi bi-file-text me-2"></i>Description</h6>
                <p class="small"><?= nl2br(htmlspecialchars($document['description'])) ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Related Case -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-folder me-2"></i>Related Case</h5>
            </div>
            <div class="card-body">
                <h6>
                    <a href="../cases/view.php?id=<?= $document['case_id'] ?>" class="text-decoration-none">
                        <?= htmlspecialchars($document['case_no']) ?>
                    </a>
                </h6>
                <p class="mb-2"><?= htmlspecialchars($document['case_title']) ?></p>
                <a href="../cases/view.php?id=<?= $document['case_id'] ?>" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-eye me-1"></i>View Case
                </a>
            </div>
        </div>
        
        <!-- Actions -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-lightning me-2"></i>Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="download.php?id=<?= $document['id'] ?>" class="btn btn-success">
                        <i class="bi bi-download me-2"></i>Download
                    </a>
                    
                    <?php if (hasPermission('edit_case') || $document['uploaded_by'] == $_SESSION['user_id']): ?>
                    <a href="edit.php?id=<?= $document['id'] ?>" class="btn btn-outline-primary">
                        <i class="bi bi-pencil me-2"></i>Edit Details
                    </a>
                    <?php endif; ?>
                    
                    <a href="upload.php?case_id=<?= $document['case_id'] ?>" class="btn btn-outline-success">
                        <i class="bi bi-upload me-2"></i>Upload New Version
                    </a>
                    
                    <?php if (hasPermission('delete_case') || $document['uploaded_by'] == $_SESSION['user_id']): ?>
                    <button type="button" class="btn btn-outline-danger" onclick="deleteDocument()">
                        <i class="bi bi-trash me-2"></i>Delete Document
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function deleteDocument() {
    if (confirm('Are you sure you want to delete this document? This action cannot be undone.')) {
        fetch('delete.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=<?= $document['id'] ?>&csrf_token=<?= generateCSRF() ?>`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = 'list.php';
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting the document.');
        });
    }
}
</script>

<?php include '../layouts/footer.php'; ?>