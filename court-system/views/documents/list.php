<?php
/**
 * Documents List Page
 */

define('COURT_SYSTEM', true);
require_once '../../includes/auth.php';
require_once '../../models/Document.php';

// Check permissions
requirePermission('upload_document');

$documentModel = new DocumentModel();

// Get filters from request
$filters = [
    'search' => sanitize($_GET['search'] ?? ''),
    'document_type' => sanitize($_GET['document_type'] ?? ''),
    'case_id' => intval($_GET['case_id'] ?? 0)
];

$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;

// Get documents
$result = $documentModel->getList($filters, $page, $limit);
$documents = $result['documents'];
$pagination = $result['pagination'];

$pageTitle = 'Documents';
include '../layouts/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="bi bi-file-earmark-text me-2"></i>Documents</h1>
    <a href="upload.php" class="btn btn-court-primary">
        <i class="bi bi-upload me-2"></i>Upload Document
    </a>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-funnel me-2"></i>Filters</h5>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control" id="search" name="search" 
                       placeholder="Document title, description, case number..." 
                       value="<?= htmlspecialchars($filters['search']) ?>">
            </div>
            
            <div class="col-md-3">
                <label for="document_type" class="form-label">Document Type</label>
                <select class="form-select" id="document_type" name="document_type">
                    <option value="">All Types</option>
                    <?php foreach (DOCUMENT_TYPES as $type): ?>
                    <option value="<?= $type ?>" <?= $filters['document_type'] === $type ? 'selected' : '' ?>>
                        <?= $type ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-3">
                <label for="case_id" class="form-label">Case ID</label>
                <input type="number" class="form-control" id="case_id" name="case_id" 
                       placeholder="Enter case ID" 
                       value="<?= $filters['case_id'] ?: '' ?>">
            </div>
            
            <div class="col-md-2 d-flex align-items-end">
                <div class="btn-group w-100">
                    <button type="submit" class="btn btn-court-primary">
                        <i class="bi bi-search"></i>
                    </button>
                    <a href="list.php" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Documents Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            Documents (<?= number_format($pagination['total_records']) ?> total)
        </h5>
        <div class="btn-group btn-group-sm">
            <button type="button" class="btn btn-outline-secondary" onclick="exportDocuments('excel')">
                <i class="bi bi-file-earmark-excel me-1"></i>Excel
            </button>
            <button type="button" class="btn btn-outline-secondary" onclick="exportDocuments('pdf')">
                <i class="bi bi-file-earmark-pdf me-1"></i>PDF
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if (empty($documents)): ?>
        <div class="text-center py-5">
            <i class="bi bi-file-earmark-x display-1 text-muted"></i>
            <p class="text-muted mt-3">No documents found matching your criteria.</p>
            <a href="upload.php" class="btn btn-court-primary">Upload First Document</a>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Document</th>
                        <th>Case</th>
                        <th>Type</th>
                        <th>Size</th>
                        <th>Uploaded By</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($documents as $doc): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <?php
                                $iconClass = 'bi-file-earmark-text';
                                if (strpos($doc['mime_type'], 'pdf') !== false) {
                                    $iconClass = 'bi-file-earmark-pdf text-danger';
                                } elseif (strpos($doc['mime_type'], 'word') !== false || strpos($doc['mime_type'], 'document') !== false) {
                                    $iconClass = 'bi-file-earmark-word text-primary';
                                } elseif (strpos($doc['mime_type'], 'image') !== false) {
                                    $iconClass = 'bi-file-earmark-image text-info';
                                }
                                ?>
                                <i class="bi <?= $iconClass ?> me-2 fs-5"></i>
                                <div>
                                    <div class="fw-bold"><?= htmlspecialchars($doc['title']) ?></div>
                                    <?php if ($doc['description']): ?>
                                    <small class="text-muted"><?= htmlspecialchars(substr($doc['description'], 0, 50)) ?>...</small>
                                    <?php endif; ?>
                                    <?php if ($doc['visibility'] !== 'public'): ?>
                                    <div>
                                        <i class="bi bi-lock text-warning me-1"></i>
                                        <small class="text-muted"><?= ucfirst(str_replace('_', ' ', $doc['visibility'])) ?></small>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <a href="../cases/view.php?id=<?= $doc['case_id'] ?>" class="text-decoration-none">
                                <strong><?= htmlspecialchars($doc['case_no']) ?></strong><br>
                                <small class="text-muted"><?= htmlspecialchars(substr($doc['case_title'], 0, 30)) ?>...</small>
                            </a>
                        </td>
                        <td>
                            <span class="badge bg-info"><?= htmlspecialchars($doc['document_type']) ?></span>
                        </td>
                        <td><?= formatFileSize($doc['file_size']) ?></td>
                        <td><?= htmlspecialchars($doc['uploaded_by_name']) ?></td>
                        <td>
                            <div><?= formatDate($doc['uploaded_at']) ?></div>
                            <small class="text-muted"><?= timeAgo($doc['uploaded_at']) ?></small>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="view.php?id=<?= $doc['id'] ?>" 
                                   class="btn btn-outline-primary" 
                                   title="View Document">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="download.php?id=<?= $doc['id'] ?>" 
                                   class="btn btn-outline-success" 
                                   title="Download">
                                    <i class="bi bi-download"></i>
                                </a>
                                <?php if (hasPermission('edit_case') || $doc['uploaded_by'] == $_SESSION['user_id']): ?>
                                <a href="edit.php?id=<?= $doc['id'] ?>" 
                                   class="btn btn-outline-secondary" 
                                   title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php endif; ?>
                                <?php if (hasPermission('delete_case') || $doc['uploaded_by'] == $_SESSION['user_id']): ?>
                                <button type="button" 
                                        class="btn btn-outline-danger confirm-delete" 
                                        title="Delete"
                                        onclick="deleteDocument(<?= $doc['id'] ?>)">
                                    <i class="bi bi-trash"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
    
    <?php if ($pagination['total_pages'] > 1): ?>
    <div class="card-footer">
        <?= generatePaginationHTML($pagination, 'list.php?' . http_build_query(array_filter($filters))) ?>
    </div>
    <?php endif; ?>
</div>

<script>
function exportDocuments(format) {
    const params = new URLSearchParams(window.location.search);
    params.set('export', format);
    window.open('export.php?' + params.toString(), '_blank');
}

function deleteDocument(documentId) {
    if (confirm('Are you sure you want to delete this document? This action cannot be undone.')) {
        fetch('delete.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${documentId}&csrf_token=<?= generateCSRF() ?>`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
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