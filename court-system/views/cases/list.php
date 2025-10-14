<?php
/**
 * Cases List Page
 */

define('COURT_SYSTEM', true);
require_once '../../includes/auth.php';
require_once '../../models/Case.php';

// Check permissions
if (!hasPermission('view_cases') && !hasPermission('view_assigned_cases')) {
    http_response_code(403);
    die('Access denied');
}

$caseModel = new CaseModel();

// Get filters from request
$filters = [
    'search' => sanitize($_GET['search'] ?? ''),
    'case_type' => sanitize($_GET['case_type'] ?? ''),
    'status' => sanitize($_GET['status'] ?? ''),
    'assigned_judge_id' => sanitize($_GET['assigned_judge_id'] ?? ''),
    'priority' => sanitize($_GET['priority'] ?? ''),
    'date_from' => sanitize($_GET['date_from'] ?? ''),
    'date_to' => sanitize($_GET['date_to'] ?? '')
];

$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;

// Get cases
$result = $caseModel->getList($filters, $page, $limit);
$cases = $result['cases'];
$pagination = $result['pagination'];

// Get filter options
$judges = $caseModel->getAvailableJudges();

$pageTitle = 'Cases';
include '../layouts/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="bi bi-folder me-2"></i>Cases</h1>
    <?php if (hasPermission('create_case')): ?>
    <a href="create.php" class="btn btn-court-primary">
        <i class="bi bi-plus-circle me-2"></i>New Case
    </a>
    <?php endif; ?>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-funnel me-2"></i>Filters</h5>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control" id="search" name="search" 
                       placeholder="Case number, title, parties..." 
                       value="<?= htmlspecialchars($filters['search']) ?>">
            </div>
            
            <div class="col-md-2">
                <label for="case_type" class="form-label">Case Type</label>
                <select class="form-select" id="case_type" name="case_type">
                    <option value="">All Types</option>
                    <?php foreach (CASE_TYPES as $type): ?>
                    <option value="<?= $type ?>" <?= $filters['case_type'] === $type ? 'selected' : '' ?>>
                        <?= $type ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Statuses</option>
                    <?php foreach (CASE_STATUSES as $status): ?>
                    <option value="<?= $status ?>" <?= $filters['status'] === $status ? 'selected' : '' ?>>
                        <?= $status ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <label for="assigned_judge_id" class="form-label">Judge</label>
                <select class="form-select" id="assigned_judge_id" name="assigned_judge_id">
                    <option value="">All Judges</option>
                    <?php foreach ($judges as $judge): ?>
                    <option value="<?= $judge['id'] ?>" <?= $filters['assigned_judge_id'] == $judge['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($judge['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-1">
                <label for="priority" class="form-label">Priority</label>
                <select class="form-select" id="priority" name="priority">
                    <option value="">All</option>
                    <?php foreach (PRIORITY_LEVELS as $priority): ?>
                    <option value="<?= $priority ?>" <?= $filters['priority'] === $priority ? 'selected' : '' ?>>
                        <?= $priority ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <label for="date_from" class="form-label">Date From</label>
                <input type="date" class="form-control" id="date_from" name="date_from" 
                       value="<?= htmlspecialchars($filters['date_from']) ?>">
            </div>
            
            <div class="col-md-2">
                <label for="date_to" class="form-label">Date To</label>
                <input type="date" class="form-control" id="date_to" name="date_to" 
                       value="<?= htmlspecialchars($filters['date_to']) ?>">
            </div>
            
            <div class="col-md-12">
                <button type="submit" class="btn btn-court-primary">
                    <i class="bi bi-search me-2"></i>Search
                </button>
                <a href="list.php" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle me-2"></i>Clear
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Cases Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            Cases (<?= number_format($pagination['total_records']) ?> total)
        </h5>
        <div class="btn-group btn-group-sm">
            <button type="button" class="btn btn-outline-secondary" onclick="exportCases('excel')">
                <i class="bi bi-file-earmark-excel me-1"></i>Excel
            </button>
            <button type="button" class="btn btn-outline-secondary" onclick="exportCases('pdf')">
                <i class="bi bi-file-earmark-pdf me-1"></i>PDF
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if (empty($cases)): ?>
        <div class="text-center py-5">
            <i class="bi bi-folder-x display-1 text-muted"></i>
            <p class="text-muted mt-3">No cases found matching your criteria.</p>
            <?php if (hasPermission('create_case')): ?>
            <a href="create.php" class="btn btn-court-primary">Create First Case</a>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Case No.</th>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Priority</th>
                        <th>Judge</th>
                        <th>Filed Date</th>
                        <th>Next Hearing</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cases as $case): ?>
                    <tr class="case-priority-<?= strtolower($case['priority']) ?>">
                        <td>
                            <strong><?= htmlspecialchars($case['case_no']) ?></strong>
                            <?php if ($case['confidential']): ?>
                            <i class="bi bi-lock-fill text-warning ms-1" title="Confidential"></i>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div>
                                <strong><?= htmlspecialchars($case['title']) ?></strong>
                                <br>
                                <small class="text-muted">
                                    <?= htmlspecialchars($case['plaintiff']) ?> vs <?= htmlspecialchars($case['defendant']) ?>
                                </small>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-info"><?= htmlspecialchars($case['case_type']) ?></span>
                        </td>
                        <td><?= getCaseStatusBadge($case['status']) ?></td>
                        <td><?= getPriorityBadge($case['priority']) ?></td>
                        <td>
                            <?php if ($case['judge_name']): ?>
                                <?= htmlspecialchars($case['judge_name']) ?>
                            <?php else: ?>
                                <span class="text-muted">Unassigned</span>
                            <?php endif; ?>
                        </td>
                        <td><?= formatDate($case['filing_date']) ?></td>
                        <td>
                            <?php if ($case['next_hearing']): ?>
                                <span class="badge bg-warning">
                                    <?= formatDateTime($case['next_hearing'], 'M j, g:i A') ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">None scheduled</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="view.php?id=<?= $case['id'] ?>" 
                                   class="btn btn-outline-primary" 
                                   title="View Case">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php if (hasPermission('edit_case') || ($case['assigned_judge_id'] == $_SESSION['user_id'])): ?>
                                <a href="edit.php?id=<?= $case['id'] ?>" 
                                   class="btn btn-outline-secondary" 
                                   title="Edit Case">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php endif; ?>
                                <?php if (hasPermission('upload_document')): ?>
                                <a href="../documents/upload.php?case_id=<?= $case['id'] ?>" 
                                   class="btn btn-outline-success" 
                                   title="Upload Document">
                                    <i class="bi bi-upload"></i>
                                </a>
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
function exportCases(format) {
    const params = new URLSearchParams(window.location.search);
    params.set('export', format);
    window.open('export.php?' + params.toString(), '_blank');
}
</script>

<?php include '../layouts/footer.php'; ?>