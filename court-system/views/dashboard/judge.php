<?php
/**
 * Judge Dashboard
 */

define('COURT_SYSTEM', true);
require_once '../../includes/auth.php';
require_once '../../models/Case.php';

// Check permissions
if ($_SESSION['user_role'] !== 'judge') {
    http_response_code(403);
    die('Access denied');
}

$caseModel = new CaseModel();
$db = getDB();
$userId = $_SESSION['user_id'];

// Get judge's statistics
$caseStats = $caseModel->getStatistics();

// Judge's upcoming hearings
$upcomingHearings = $db->fetchAll("
    SELECT h.*, c.case_no, c.title as case_title, c.case_type
    FROM hearings h
    JOIN cases c ON h.case_id = c.id
    WHERE h.judge_id = ? 
    AND h.hearing_date >= NOW() 
    AND h.status IN ('Scheduled', 'Rescheduled')
    ORDER BY h.hearing_date ASC
    LIMIT 10
", [$userId]);

// Today's hearings
$todayHearings = $db->fetchAll("
    SELECT h.*, c.case_no, c.title as case_title, c.case_type
    FROM hearings h
    JOIN cases c ON h.case_id = c.id
    WHERE h.judge_id = ? 
    AND DATE(h.hearing_date) = CURDATE()
    AND h.status IN ('Scheduled', 'Rescheduled', 'In Progress')
    ORDER BY h.hearing_date ASC
", [$userId]);

// Recent cases assigned
$recentCases = $db->fetchAll("
    SELECT c.*, 
           (SELECT COUNT(*) FROM documents WHERE case_id = c.id AND is_active = 1) as document_count
    FROM cases c
    WHERE c.assigned_judge_id = ?
    ORDER BY c.updated_at DESC
    LIMIT 5
", [$userId]);

// Pending judgments
$pendingJudgments = $db->fetchAll("
    SELECT c.*, h.hearing_date
    FROM cases c
    LEFT JOIN hearings h ON c.id = h.case_id AND h.status = 'Completed'
    WHERE c.assigned_judge_id = ?
    AND c.status IN ('In Hearing', 'Under Review')
    AND NOT EXISTS (SELECT 1 FROM judgments j WHERE j.case_id = c.id)
    ORDER BY h.hearing_date ASC
", [$userId]);

// New document notifications
$newDocuments = $db->fetchAll("
    SELECT d.*, c.case_no, c.title as case_title
    FROM documents d
    JOIN cases c ON d.case_id = c.id
    WHERE c.assigned_judge_id = ?
    AND d.uploaded_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    AND (d.visibility = 'judge_only' OR d.document_type IN ('Motion', 'Evidence'))
    ORDER BY d.uploaded_at DESC
    LIMIT 5
", [$userId]);

$pageTitle = 'Judge Dashboard';
include '../layouts/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="bi bi-gavel me-2"></i>Judge Dashboard</h1>
    <div class="text-muted">
        Welcome back, Your Honor <?= htmlspecialchars($_SESSION['user_name']) ?>
    </div>
</div>

<!-- Today's Schedule Alert -->
<?php if (!empty($todayHearings)): ?>
<div class="alert alert-info alert-permanent mb-4">
    <h5><i class="bi bi-calendar-check me-2"></i>Today's Schedule</h5>
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
                            <small><?= htmlspecialchars($hearing['court_room']) ?></small>
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
                        <p class="mb-0">Assigned Cases</p>
                    </div>
                    <i class="bi bi-folder fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="card bg-warning text-white mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0"><?= count($pendingJudgments) ?></h3>
                        <p class="mb-0">Pending Judgments</p>
                    </div>
                    <i class="bi bi-hourglass-split fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="card bg-info text-white mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0"><?= count($upcomingHearings) ?></h3>
                        <p class="mb-0">Upcoming Hearings</p>
                    </div>
                    <i class="bi bi-calendar-event fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="card bg-success text-white mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0"><?= count($newDocuments) ?></h3>
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
        <!-- Pending Judgments -->
        <?php if (!empty($pendingJudgments)): ?>
        <div class="card mb-4">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Cases Requiring Judgment</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Case</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Last Hearing</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingJudgments as $case): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($case['case_no']) ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars(substr($case['title'], 0, 40)) ?>...</small>
                                </td>
                                <td><span class="badge bg-info"><?= htmlspecialchars($case['case_type']) ?></span></td>
                                <td><?= getCaseStatusBadge($case['status']) ?></td>
                                <td><?= $case['hearing_date'] ? formatDateTime($case['hearing_date']) : 'N/A' ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="../cases/view.php?id=<?= $case['id'] ?>" class="btn btn-outline-primary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="../judgments/create.php?case_id=<?= $case['id'] ?>" class="btn btn-warning">
                                            <i class="bi bi-gavel"></i> Judge
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
        
        <!-- Recent Cases -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-folder-open me-2"></i>Recent Cases</h5>
                <a href="../cases/list.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($recentCases)): ?>
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
                                <th>Documents</th>
                                <th>Updated</th>
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
                                    <span class="badge bg-secondary"><?= $case['document_count'] ?></span>
                                </td>
                                <td><?= timeAgo($case['updated_at']) ?></td>
                                <td>
                                    <a href="../cases/view.php?id=<?= $case['id'] ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Case Status Distribution -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-pie-chart me-2"></i>My Cases by Status</h5>
            </div>
            <div class="card-body">
                <canvas id="caseStatusChart" width="400" height="200"></canvas>
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
                    <a href="../cases/list.php" class="btn btn-court-primary">
                        <i class="bi bi-folder me-2"></i>View My Cases
                    </a>
                    <a href="../hearings/list.php" class="btn btn-outline-primary">
                        <i class="bi bi-calendar-event me-2"></i>My Schedule
                    </a>
                    <a href="../documents/list.php" class="btn btn-outline-success">
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
                            <small class="text-muted"><?= htmlspecialchars($hearing['court_room']) ?></small>
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
        
        <!-- New Documents -->
        <?php if (!empty($newDocuments)): ?>
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-file-earmark-plus me-2"></i>New Documents</h5>
            </div>
            <div class="card-body">
                <?php foreach ($newDocuments as $doc): ?>
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
    </div>
</div>

<script>
// Case Status Chart
const ctx = document.getElementById('caseStatusChart').getContext('2d');
const caseStatusChart = new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: [<?= implode(',', array_map(function($s) { return '"' . $s['status'] . '"'; }, $caseStats['status_stats'])) ?>],
        datasets: [{
            data: [<?= implode(',', array_column($caseStats['status_stats'], 'count')) ?>],
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

<?php include '../layouts/footer.php'; ?>