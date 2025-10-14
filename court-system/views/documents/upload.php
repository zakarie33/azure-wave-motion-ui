<?php
/**
 * Document Upload Page
 */

define('COURT_SYSTEM', true);
require_once '../../includes/auth.php';
require_once '../../models/Document.php';
require_once '../../models/Case.php';

// Check permissions
requirePermission('upload_document');

$caseId = intval($_GET['case_id'] ?? 0);
$documentModel = new DocumentModel();
$caseModel = new CaseModel();

$error = '';
$success = '';
$case = null;

// Get case information if case_id is provided
if ($caseId) {
    if (!canAccessCase($caseId)) {
        http_response_code(403);
        die('Access denied. You do not have permission to upload documents to this case.');
    }
    
    $case = $caseModel->getById($caseId);
    if (!$case) {
        redirect('list.php', 'Case not found', 'error');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate CSRF token
        if (!validateCSRF($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid security token. Please try again.');
        }
        
        $uploadCaseId = intval($_POST['case_id'] ?? 0);
        if (!$uploadCaseId) {
            throw new Exception('Please select a case');
        }
        
        // Prepare document data
        $documentData = [
            'title' => sanitize($_POST['title'] ?? ''),
            'description' => sanitize($_POST['description'] ?? ''),
            'document_type' => sanitize($_POST['document_type'] ?? ''),
            'visibility' => sanitize($_POST['visibility'] ?? 'case_staff'),
            'signed_by' => sanitize($_POST['signed_by'] ?? '')
        ];
        
        // Validate file upload
        if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Please select a file to upload');
        }
        
        // Upload document
        $documentId = $documentModel->upload($uploadCaseId, $documentData, $_FILES['document']);
        
        $redirectUrl = $case ? "../cases/view.php?id={$uploadCaseId}" : "view.php?id={$documentId}";
        redirect($redirectUrl, 'Document uploaded successfully!', 'success');
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get available cases for dropdown (if not pre-selected)
$availableCases = [];
if (!$case) {
    $userRole = $_SESSION['user_role'];
    $userId = $_SESSION['user_id'];
    
    if (in_array($userRole, ['admin', 'manager'])) {
        $availableCases = $caseModel->getList(['status' => '!Closed'])['cases'];
    } elseif ($userRole === 'judge') {
        $availableCases = $caseModel->getList(['assigned_judge_id' => $userId])['cases'];
    } else {
        $availableCases = $caseModel->getList([])['cases'];
    }
}

$pageTitle = 'Upload Document';
include '../layouts/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="bi bi-upload me-2"></i>Upload Document</h1>
    <div class="btn-group">
        <?php if ($case): ?>
        <a href="../cases/view.php?id=<?= $case['id'] ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to Case
        </a>
        <?php else: ?>
        <a href="list.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to Documents
        </a>
        <?php endif; ?>
    </div>
</div>

<?php if ($case): ?>
<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>
    Uploading document to case: <strong><?= htmlspecialchars($case['case_no']) ?> - <?= htmlspecialchars($case['title']) ?></strong>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-danger">
    <i class="bi bi-exclamation-triangle me-2"></i>
    <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-file-earmark-plus me-2"></i>Document Information</h5>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data" class="needs-validation loading-form" novalidate>
                    <?= csrfInput() ?>
                    
                    <?php if ($case): ?>
                    <input type="hidden" name="case_id" value="<?= $case['id'] ?>">
                    <?php else: ?>
                    <div class="mb-4">
                        <label for="case_id" class="form-label">Select Case *</label>
                        <select class="form-select" id="case_id" name="case_id" required>
                            <option value="">Choose a case...</option>
                            <?php foreach ($availableCases as $availableCase): ?>
                            <option value="<?= $availableCase['id'] ?>" <?= ($_POST['case_id'] ?? '') == $availableCase['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($availableCase['case_no']) ?> - <?= htmlspecialchars($availableCase['title']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Please select a case.</div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label for="document" class="form-label">Select File *</label>
                        <input type="file" class="form-control" id="document" name="document" 
                               accept=".pdf,.docx,.doc,.jpg,.jpeg,.png" required
                               onchange="previewFile(this, 'file-preview')">
                        <div class="form-text">
                            Allowed file types: <?= implode(', ', ALLOWED_FILE_TYPES) ?>. 
                            Maximum size: <?= formatFileSize(MAX_UPLOAD_SIZE) ?>
                        </div>
                        <div class="invalid-feedback">Please select a file to upload.</div>
                        <div id="file-preview" class="mt-2"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="title" class="form-label">Document Title *</label>
                        <input type="text" class="form-control" id="title" name="title" 
                               placeholder="e.g., Witness Statement - John Doe" 
                               value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required>
                        <div class="invalid-feedback">Please provide a document title.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3" 
                                  placeholder="Optional description of the document..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="document_type" class="form-label">Document Type *</label>
                                <select class="form-select" id="document_type" name="document_type" required>
                                    <option value="">Select Type</option>
                                    <?php foreach (DOCUMENT_TYPES as $type): ?>
                                    <option value="<?= $type ?>" <?= ($_POST['document_type'] ?? '') === $type ? 'selected' : '' ?>>
                                        <?= $type ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Please select a document type.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="visibility" class="form-label">Visibility</label>
                                <select class="form-select" id="visibility" name="visibility">
                                    <option value="case_staff" <?= ($_POST['visibility'] ?? 'case_staff') === 'case_staff' ? 'selected' : '' ?>>
                                        Case Staff Only
                                    </option>
                                    <option value="public" <?= ($_POST['visibility'] ?? '') === 'public' ? 'selected' : '' ?>>
                                        Public
                                    </option>
                                    <option value="judge_only" <?= ($_POST['visibility'] ?? '') === 'judge_only' ? 'selected' : '' ?>>
                                        Judge Only
                                    </option>
                                    <?php if (hasPermission('system_config')): ?>
                                    <option value="admin_only" <?= ($_POST['visibility'] ?? '') === 'admin_only' ? 'selected' : '' ?>>
                                        Admin Only
                                    </option>
                                    <?php endif; ?>
                                </select>
                                <div class="form-text">Controls who can view this document</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="signed_by" class="form-label">Signed By</label>
                        <input type="text" class="form-control" id="signed_by" name="signed_by" 
                               placeholder="Name of person who signed this document (if applicable)" 
                               value="<?= htmlspecialchars($_POST['signed_by'] ?? '') ?>">
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <?php if ($case): ?>
                        <a href="../cases/view.php?id=<?= $case['id'] ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle me-2"></i>Cancel
                        </a>
                        <?php else: ?>
                        <a href="list.php" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle me-2"></i>Cancel
                        </a>
                        <?php endif; ?>
                        <button type="submit" class="btn btn-court-primary">
                            <i class="bi bi-upload me-2"></i>Upload Document
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Upload Guidelines -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Upload Guidelines</h5>
            </div>
            <div class="card-body">
                <h6><i class="bi bi-check-circle text-success me-2"></i>Allowed File Types</h6>
                <ul class="list-unstyled mb-3">
                    <li><i class="bi bi-file-earmark-pdf text-danger me-2"></i>PDF Documents</li>
                    <li><i class="bi bi-file-earmark-word text-primary me-2"></i>Word Documents (.docx, .doc)</li>
                    <li><i class="bi bi-file-earmark-image text-info me-2"></i>Images (.jpg, .jpeg, .png)</li>
                </ul>
                
                <h6><i class="bi bi-exclamation-triangle text-warning me-2"></i>Important Notes</h6>
                <ul class="small">
                    <li>Maximum file size: <?= formatFileSize(MAX_UPLOAD_SIZE) ?></li>
                    <li>Files are scanned for security</li>
                    <li>All uploads are logged for audit purposes</li>
                    <li>Choose appropriate visibility settings</li>
                    <li>Use descriptive titles for easy identification</li>
                </ul>
                
                <hr>
                
                <h6><i class="bi bi-shield-lock text-primary me-2"></i>Security</h6>
                <p class="small text-muted">
                    All uploaded documents are stored securely and access is controlled based on user roles and document visibility settings.
                </p>
            </div>
        </div>
        
        <!-- Recent Uploads -->
        <?php
        $recentDocs = $documentModel->getStatistics()['recent_uploads'];
        if (!empty($recentDocs)):
        ?>
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Recent Uploads</h5>
            </div>
            <div class="card-body">
                <?php foreach ($recentDocs as $doc): ?>
                <div class="d-flex justify-content-between align-items-start mb-2 pb-2 border-bottom">
                    <div>
                        <div class="fw-bold small"><?= htmlspecialchars($doc['title']) ?></div>
                        <small class="text-muted"><?= htmlspecialchars($doc['case_no']) ?></small>
                    </div>
                    <small class="text-muted"><?= timeAgo($doc['uploaded_at']) ?></small>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Auto-populate title from filename
document.getElementById('document').addEventListener('change', function() {
    const titleField = document.getElementById('title');
    if (!titleField.value && this.files.length > 0) {
        const filename = this.files[0].name;
        const nameWithoutExt = filename.substring(0, filename.lastIndexOf('.')) || filename;
        titleField.value = nameWithoutExt.replace(/[_-]/g, ' ');
    }
});

// File size validation
document.getElementById('document').addEventListener('change', function() {
    const maxSize = <?= MAX_UPLOAD_SIZE ?>;
    if (this.files.length > 0 && this.files[0].size > maxSize) {
        alert('File size exceeds maximum allowed size of <?= formatFileSize(MAX_UPLOAD_SIZE) ?>');
        this.value = '';
        document.getElementById('file-preview').innerHTML = '';
    }
});
</script>

<?php include '../layouts/footer.php'; ?>