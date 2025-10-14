<?php
/**
 * Prosecutor Dashboard
 */

define('COURT_SYSTEM', true);
require_once '../../includes/auth.php';
require_once '../../models/Case.php';
require_once '../../models/Document.php';

// Check permissions
if ($_SESSION['user_role'] !== 'prosecutor') {
    http_response_code(403);
    die('Access denied');
}

$caseModel = new CaseModel();
$documentModel = new DocumentModel();
$db = getDB();
$userEmail = $_SESSION['user_email'];

// Get prosecutor's cases (cases where they are listed as participants)
$myCases = $db->fetchAll("
    SELECT DISTINCT c.*, 
           CONCAT(u.first_name, ' ', u.last_name) as judge_name,
           cp.role as my_role
    FROM cases c
    JOIN case_participants cp ON c.id = cp.case_id
    LEFT JOIN users u ON c.assigned_judge_id = u.id
    WHERE cp.contact_email = ?
    AND c.status NOT IN ('Closed', 'Dismissed')
    ORDER BY c.updated_at DESC
", [$userEmail]);

// Upcoming hearings for my cases
$upcomingHearings = $db->fetchAll("
    SELECT h.*, c.case_no, c.title as case_title, 
           CONCAT(j.first_name, ' ', j.last_name) as judge_name
    FROM hearings h
    JOIN cases c ON h.case_id = c.id
    JOIN case_participants cp ON c.id = cp.case_id
    JOIN users j ON h.judge_id = j.id
    WHERE cp.contact_email = ?
    AND h.hearing_date >= NOW() 
    AND h.status IN ('Scheduled', 'Rescheduled')
    ORDER BY h.hearing_date ASC
    LIMIT 10
", [$userEmail]);

// Today's hearings
$todayHearings = $db->fetchAll("
    SELECT h.*, c.case_no, c.title as case_title, 
           CONCAT(j.first_name, ' ', j.last_name) as judge_name
    FROM hearings h
    JOIN cases c ON h.case_id = c.id
    JOIN case_participants cp ON c.id = cp.case_id
    JOIN users j ON h.judge_id = j.id
    WHERE cp.contact_email = ?
    AND DATE(h.hearing_date) = CURDATE()
    AND h.status IN ('Scheduled', 'Rescheduled', 'In Progress')
    ORDER BY h.hearing_date ASC
", [$userEmail]);

// Recent document uploads to my cases
$recentDocuments = $db->fetchAll("
    SELECT d.*, c.case_no, c.title as case_title,
           CONCAT(u.first_name, ' ', u.last_name) as uploaded_by_name
    FROM documents d
    JOIN cases c ON d.case_id = c.id
    JOIN case_participants cp ON c.id = cp.case_id
    JOIN users u ON d.uploaded_by = u.id
    WHERE cp.contact_email = ?
    AND d.uploaded_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    AND d.visibility IN ('public', 'case_staff')
    ORDER BY d.uploaded_at DESC
    LIMIT 5
", [$userEmail]);

// Case statistics
$caseStats = [
    'total_cases' => count($myCases),
    'active_cases' => count(array_filter($myCases, function($c) { return !in_array($c['status'], ['Closed', 'Dismissed']); })),
    'upcoming_hearings' => count($upcomingHearings),
    'today_hearings' => count($todayHearings)
];

// Case status distribution
$statusStats = [];
foreach ($myCases as $case) {
    $status = $case['status'];
    if (!isset($statusStats[$status])) {
        $statusStats[$status] = 0;
    }
    $statusStats[$status]++;
}

$pageTitle = 'Prosecutor Dashboard';
include '../layouts/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="bi bi-briefcase me-2"></i>Prosecutor Dashboard</h1>
    <div class="text-muted">
        Welcome back, <?= htmlspecialchars($_SESSION['user_name']) ?>
    </div>
</div>

<!-- Today's Hearings Alert -->
<?php if (!empty($todayHearings)): ?>
<div class="alert alert-info alert-permanent mb-4">
    <h5><i class="bi bi-calendar-check me-2"></i>Today's Hearings</h5>
    <p class="mb-2">You have <?= count($todayHearings) ?> hearing(s) scheduled for today:</p>
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

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card stats-card text-white mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0"><?= number_format($caseStats['total_cases']) ?></h3>
                        <p class="mb-0">My Cases</p>
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
                        <h3 class="mb-0"><?= number_format($caseStats['active_cases']) ?></h3>
                        <p class="mb-0">Active Cases</p>
                    </div>
                    <i class="bi bi-folder-check fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="card bg-info text-white mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0"><?= number_format($caseStats['upcoming_hearings']) ?></h3>
                        <p class="mb-0">Upcoming Hearings</p>
                    </div>
                    <i class="bi bi-calendar-event fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="card bg-warning text-white mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0"><?= count($recentDocuments) ?></h3>
                        <p class="mb-0">New Documents</p>
                    </div>
                    <i class="bi bi-file-earmark-plus fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-8">
        <!-- My Cases -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-folder-open me-2"></i>My Cases</h5>
                <a href="../cases/list.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($myCases)): ?>
                <div class="text-center py-3">
                    <i class="bi bi-folder-x text-muted"></i>
                    <p class="text-muted mt-2 mb-0">No cases assigned yet</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Case</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Judge</th>
                                <th>My Role</th>
                                <th>Updated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($myCases, 0, 10) as $case): ?>
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
                                        <span class="text-muted">Unassigned</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($case['my_role']) ?></span></td>
                                <td><?= timeAgo($case['updated_at']) ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="../cases/view.php?id=<?= $case['id'] ?>" class="btn btn-outline-primary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="../documents/upload.php?case_id=<?= $case['id'] ?>" class="btn btn-outline-success">
                                            <i class="bi bi-upload"></i>
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
        
        <!-- Recent Documents -->
        <?php if (!empty($recentDocuments)): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-file-earmark-plus me-2"></i>Recent Document Updates</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Document</th>
                                <th>Case</th>
                                <th>Type</th>
                                <th>Uploaded By</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentDocuments as $doc): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($doc['title']) ?></strong><br>
                                    <?php if ($doc['description']): ?>
                                    <small class="text-muted"><?= htmlspecialchars(substr($doc['description'], 0, 40)) ?>...</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="../cases/view.php?id=<?= $doc['case_id'] ?>" class="text-decoration-none">
                                        <?= htmlspecialchars($doc['case_no']) ?>
                                    </a>
                                </td>
                                <td><span class="badge bg-info"><?= htmlspecialchars($doc['document_type']) ?></span></td>
                                <td><?= htmlspecialchars($doc['uploaded_by_name']) ?></td>
                                <td><?= timeAgo($doc['uploaded_at']) ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="../documents/view.php?id=<?= $doc['id'] ?>" class="btn btn-outline-primary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="../documents/download.php?id=<?= $doc['id'] ?>" class="btn btn-outline-success">
                                            <i class="bi bi-download"></i>
                                        </a>
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
        
        <!-- Case Status Distribution -->
        <?php if (!empty($statusStats)): ?>
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-pie-chart me-2"></i>My Cases by Status</h5>
            </div>
            <div class="card-body">
                <canvas id="caseStatusChart" width="400" height="200"></canvas>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="col-xl-4">
        <!-- Quick Actions -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-lightning me-2"></i>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="../cases/list.php" class="btn btn-court-primary">
                        <i class="bi bi-folder me-2"></i>View My Cases
                    </a>
                    <a href="../documents/upload.php" class="btn btn-outline-success">
                        <i class="bi bi-upload me-2"></i>Upload Document
                    </a>
                    <a href="../hearings/list.php" class="btn btn-outline-primary">
                        <i class="bi bi-calendar-event me-2"></i>My Schedule
                    </a>
                    <a href="../documents/list.php" class="btn btn-outline-info">
                        <i class="bi bi-file-earmark-text me-2"></i>Recent Documents
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Upcoming Hearings -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-calendar-event me-2"></i>Upcoming Hearings</h5>
                <a href="../hearings/list.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                <?php if (empty($upcomingHearings)): ?>
                <div class="text-center py-3">
                    <i class="bi bi-calendar-x text-muted"></i>
                    <p class="text-muted mt-2 mb-0">No upcoming hearings</p>
                </div>
                <?php else: ?>
                <?php foreach ($upcomingHearings as $hearing): ?>
                <div class="border-bottom pb-2 mb-2 <?= date('Y-m-d') === date('Y-m-d', strtotime($hearing['hearing_date'])) ? 'hearing-today' : (strtotime($hearing['hearing_date']) <= strtotime('+3 days') ? 'hearing-upcoming' : '') ?>">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="mb-1"><?= htmlspecialchars($hearing['case_no']) ?></h6>
                            <small class="text-muted"><?= htmlspecialchars($hearing['judge_name']) ?> - <?= htmlspecialchars($hearing['court_room']) ?></small>
                        </div>
                        <small class="text-muted"><?= formatDateTime($hearing['hearing_date'], 'M j, g:i A') ?></small>
                    </div>
                    <p class="mb-1 small"><?= htmlspecialchars(substr($hearing['case_title'], 0, 40)) ?>...</p>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="badge bg-info"><?= htmlspecialchars($hearing['hearing_type']) ?></span>
                        <a href="../hearings/view.php?id=<?= $hearing['id'] ?>" class="btn btn-sm btn-outline-primary">
                            View
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Case Preparation Checklist -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-list-check me-2"></i>Case Preparation</h5>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Review case files</span>
                        <span class="badge bg-primary rounded-pill"><?= count($myCases) ?></span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Prepare for hearings</span>
                        <span class="badge bg-warning rounded-pill"><?= count($upcomingHearings) ?></span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Review new documents</span>
                        <span class="badge bg-info rounded-pill"><?= count($recentDocuments) ?></span>
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

<?php if (!empty($statusStats)): ?>
<script>
// Case Status Chart
const ctx = document.getElementById('caseStatusChart').getContext('2d');
const caseStatusChart = new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: [<?= implode(',', array_map(function($s) { return '"' . $s . '"'; }, array_keys($statusStats))) ?>],
        datasets: [{
            data: [<?= implode(',', array_values($statusStats)) ?>],
            backgroundColor: [
                '#2c3e50', '#3498db', '#e74c3c', '#f39c12', '#27ae60', '#9b59b6', '#1abc9c', '#34495e'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
</script>
<?php endif; ?>

<?php include '../layouts/footer.php'; ?>