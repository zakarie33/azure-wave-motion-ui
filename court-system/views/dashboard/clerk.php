<?php
/**
 * Clerk Dashboard
 */

define('COURT_SYSTEM', true);
require_once '../../includes/auth.php';
require_once '../../models/Case.php';
require_once '../../models/Document.php';

// Check permissions
if ($_SESSION['user_role'] !== 'clerk') {
    http_response_code(403);
    die('Access denied');
}

$caseModel = new CaseModel();
$documentModel = new DocumentModel();
$db = getDB();
$userId = $_SESSION['user_id'];

// Get clerk's statistics
$caseStats = $caseModel->getStatistics();
$docStats = $documentModel->getStatistics();

// Recent cases created by clerk
$recentCases = $db->fetchAll("
    SELECT c.*, CONCAT(u.first_name, ' ', u.last_name) as judge_name
    FROM cases c
    LEFT JOIN users u ON c.assigned_judge_id = u.id
    WHERE c.created_by = ?
    ORDER BY c.created_at DESC
    LIMIT 5
", [$userId]);

// Unassigned cases
$unassignedCases = $db->fetchAll("
    SELECT c.*
    FROM cases c
    WHERE c.assigned_judge_id IS NULL 
    AND c.status NOT IN ('Closed', 'Dismissed')
    AND (c.created_by = ? OR c.department_id = ?)
    ORDER BY c.filing_date ASC
    LIMIT 10
", [$userId, $_SESSION['department_id'] ?? 0]);

// Today's hearings in department
$todayHearings = $db->fetchAll("
    SELECT h.*, c.case_no, c.title as case_title, 
           CONCAT(j.first_name, ' ', j.last_name) as judge_name
    FROM hearings h
    JOIN cases c ON h.case_id = c.id
    JOIN users j ON h.judge_id = j.id
    WHERE DATE(h.hearing_date) = CURDATE()
    AND h.status IN ('Scheduled', 'Rescheduled', 'In Progress')
    AND (c.created_by = ? OR c.department_id = ?)
    ORDER BY h.hearing_date ASC
", [$userId, $_SESSION['department_id'] ?? 0]);

// Document requests pending approval
$pendingRequests = $db->fetchAll("
    SELECT dr.*, c.case_no, c.title as case_title,
           CONCAT(u.first_name, ' ', u.last_name) as requestor_name
    FROM document_requests dr
    JOIN cases c ON dr.case_id = c.id
    JOIN users u ON dr.requestor_id = u.id
    WHERE dr.status = 'pending'
    ORDER BY dr.created_at ASC
    LIMIT 5
");

// Recent document uploads
$recentUploads = $db->fetchAll("
    SELECT d.*, c.case_no
    FROM documents d
    JOIN cases c ON d.case_id = c.id
    WHERE d.uploaded_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    AND (c.created_by = ? OR c.department_id = ?)
    ORDER BY d.uploaded_at DESC
    LIMIT 5
", [$userId, $_SESSION['department_id'] ?? 0]);

$pageTitle = 'Clerk Dashboard';
include '../layouts/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="bi bi-person-badge me-2"></i>Clerk Dashboard</h1>
    <div class="text-muted">
        Welcome back, <?= htmlspecialchars($_SESSION['user_name']) ?>
    </div>
</div>

<!-- Today's Hearings Alert -->
<?php if (!empty($todayHearings)): ?>
<div class="alert alert-info alert-permanent mb-4">
    <h5><i class="bi bi-calendar-check me-2"></i>Today's Court Schedule</h5>
    <p class="mb-2"><?= count($todayHearings) ?> hearing(s) scheduled for today:</p>
    <div class="row">
        <?php foreach ($todayHearings as $hearing): ?>
        <div class="col-md-6 mb-2">
            <div class="card border-info">
                <div class="card-body py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong><?= formatDateTime($hearing['hearing_date'], 'g:i A') ?></strong> - 
                            <?= htmlspecialchars($hearing['case_no']) ?>
                            <br>
                            <small><?= htmlspecialchars($hearing['judge_name']) ?> - <?= htmlspecialchars($hearing['court_room']) ?></small>
                        </div>
                        <a href="../hearings/view.php?id=<?= $hearing['id'] ?>" class="btn btn-sm btn-outline-info">
                            View
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Unassigned Cases Alert -->
<?php if (!empty($unassignedCases)): ?>
<div class="alert alert-warning mb-4">
    <h5><i class="bi bi-exclamation-triangle me-2"></i>Cases Requiring Judge Assignment</h5>
    <p class="mb-2"><?= count($unassignedCases) ?> case(s) need to be assigned to a judge:</p>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>Case No.</th>
                    <th>Title</th>
                    <th>Filed Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (array_slice($unassignedCases, 0, 3) as $case): ?>
                <tr>
                    <td><?= htmlspecialchars($case['case_no']) ?></td>
                    <td><?= htmlspecialchars(substr($case['title'], 0, 30)) ?>...</td>
                    <td><?= formatDate($case['filing_date']) ?></td>
                    <td>
                        <a href="../cases/edit.php?id=<?= $case['id'] ?>" class="btn btn-sm btn-warning">
                            Assign Judge
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if (count($unassignedCases) > 3): ?>
    <p class="mt-2 mb-0">
        <a href="../cases/list.php?assigned_judge_id=0" class="btn btn-sm btn-outline-warning">
            View All <?= count($unassignedCases) ?> Unassigned Cases
        </a>
    </p>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card stats-card text-white mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0"><?= number_format($caseStats['total_cases']) ?></h3>
                        <p class="mb-0">Total Cases</p>
                    </div>
                    <i class="bi bi-folder fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="card bg-success text-white mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0"><?= number_format($docStats['total_documents']) ?></h3>
                        <p class="mb-0">Documents</p>
                    </div>
                    <i class="bi bi-file-earmark-text fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="card bg-warning text-white mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0"><?= count($unassignedCases) ?></h3>
                        <p class="mb-0">Unassigned Cases</p>
                    </div>
                    <i class="bi bi-person-x fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="card bg-info text-white mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0"><?= count($pendingRequests) ?></h3>
                        <p class="mb-0">Pending Requests</p>
                    </div>
                    <i class="bi bi-clock-history fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-8">
        <!-- Recent Cases -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-folder-plus me-2"></i>Recently Created Cases</h5>
                <a href="../cases/create.php" class="btn btn-sm btn-court-primary">
                    <i class="bi bi-plus me-1"></i>New Case
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($recentCases)): ?>
                <div class="text-center py-3">
                    <i class="bi bi-folder-x text-muted"></i>
                    <p class="text-muted mt-2 mb-0">No cases created yet</p>
                    <a href="../cases/create.php" class="btn btn-court-primary mt-2">Create First Case</a>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Case</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Assigned Judge</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentCases as $case): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($case['case_no']) ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars(substr($case['title'], 0, 30)) ?>...</small>
                                </td>
                                <td><span class="badge bg-info"><?= htmlspecialchars($case['case_type']) ?></span></td>
                                <td><?= getCaseStatusBadge($case['status']) ?></td>
                                <td>
                                    <?php if ($case['judge_name']): ?>
                                        <?= htmlspecialchars($case['judge_name']) ?>
                                    <?php else: ?>
                                        <span class="text-warning">Unassigned</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= timeAgo($case['created_at']) ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="../cases/view.php?id=<?= $case['id'] ?>" class="btn btn-outline-primary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="../cases/edit.php?id=<?= $case['id'] ?>" class="btn btn-outline-secondary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Document Requests -->
        <?php if (!empty($pendingRequests)): ?>
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-file-earmark-arrow-up me-2"></i>Pending Document Requests</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Case</th>
                                <th>Requested By</th>
                                <th>Reason</th>
                                <th>Requested</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingRequests as $request): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($request['case_no']) ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars(substr($request['case_title'], 0, 30)) ?>...</small>
                                </td>
                                <td><?= htmlspecialchars($request['requestor_name']) ?></td>
                                <td><?= htmlspecialchars(substr($request['reason'], 0, 50)) ?>...</td>
                                <td><?= timeAgo($request['created_at']) ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-success" onclick="approveRequest(<?= $request['id'] ?>)">
                                            <i class="bi bi-check"></i> Approve
                                        </button>
                                        <button class="btn btn-danger" onclick="denyRequest(<?= $request['id'] ?>)">
                                            <i class="bi bi-x"></i> Deny
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Case Type Distribution -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-pie-chart me-2"></i>Cases by Type</h5>
            </div>
            <div class="card-body">
                <canvas id="caseTypeChart" width="400" height="200"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-xl-4">
        <!-- Quick Actions -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-lightning me-2"></i>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="../cases/create.php" class="btn btn-court-primary">
                        <i class="bi bi-plus-circle me-2"></i>Register New Case
                    </a>
                    <a href="../documents/upload.php" class="btn btn-outline-success">
                        <i class="bi bi-upload me-2"></i>Upload Document
                    </a>
                    <a href="../hearings/create.php" class="btn btn-outline-primary">
                        <i class="bi bi-calendar-plus me-2"></i>Schedule Hearing
                    </a>
                    <a href="../cases/list.php?assigned_judge_id=0" class="btn btn-outline-warning">
                        <i class="bi bi-person-x me-2"></i>Unassigned Cases
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Recent Document Uploads -->
        <?php if (!empty($recentUploads)): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-file-earmark-plus me-2"></i>Recent Uploads</h5>
            </div>
            <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                <?php foreach ($recentUploads as $doc): ?>
                <div class="border-bottom pb-2 mb-2">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="mb-1"><?= htmlspecialchars($doc['title']) ?></h6>
                            <small class="text-muted"><?= htmlspecialchars($doc['case_no']) ?></small>
                        </div>
                        <small class="text-muted"><?= timeAgo($doc['uploaded_at']) ?></small>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="badge bg-info"><?= htmlspecialchars($doc['document_type']) ?></span>
                        <a href="../documents/view.php?id=<?= $doc['id'] ?>" class="btn btn-sm btn-outline-primary">
                            View
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Daily Tasks -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-list-check me-2"></i>Daily Tasks</h5>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Review new cases</span>
                        <span class="badge bg-primary rounded-pill"><?= count($recentCases) ?></span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Assign judges</span>
                        <span class="badge bg-warning rounded-pill"><?= count($unassignedCases) ?></span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Process requests</span>
                        <span class="badge bg-info rounded-pill"><?= count($pendingRequests) ?></span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Today's hearings</span>
                        <span class="badge bg-success rounded-pill"><?= count($todayHearings) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Case Type Chart
const ctx = document.getElementById('caseTypeChart').getContext('2d');
const caseTypeChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: [<?= implode(',', array_map(function($s) { return '"' . $s['case_type'] . '"'; }, $caseStats['type_stats'])) ?>],
        datasets: [{
            label: 'Cases',
            data: [<?= implode(',', array_column($caseStats['type_stats'], 'count')) ?>],
            backgroundColor: '#2c3e50',
            borderColor: '#34495e',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Document request actions
function approveRequest(requestId) {
    if (confirm('Approve this document request?')) {
        fetch('../documents/approve-request.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${requestId}&action=approve&csrf_token=<?= generateCSRF() ?>`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    }
}

function denyRequest(requestId) {
    const reason = prompt('Reason for denial (optional):');
    if (reason !== null) {
        fetch('../documents/approve-request.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${requestId}&action=deny&reason=${encodeURIComponent(reason)}&csrf_token=<?= generateCSRF() ?>`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    }
}
</script>

<?php include '../layouts/footer.php'; ?>