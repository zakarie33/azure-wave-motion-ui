<?php
/**
 * Admin Dashboard
 */

define('COURT_SYSTEM', true);
require_once '../../includes/auth.php';
require_once '../../models/Case.php';
require_once '../../models/Document.php';

// Check permissions
if (!in_array($_SESSION['user_role'], ['admin', 'manager'])) {
    http_response_code(403);
    die('Access denied');
}

$caseModel = new CaseModel();
$documentModel = new DocumentModel();
$db = getDB();

// Get statistics
$caseStats = $caseModel->getStatistics();
$docStats = $documentModel->getStatistics();

// System statistics
$userStats = $db->fetchAll("
    SELECT role, COUNT(*) as count 
    FROM users 
    WHERE is_active = 1 
    GROUP BY role
");

$hearingStats = $db->fetchAll("
    SELECT status, COUNT(*) as count 
    FROM hearings 
    WHERE hearing_date >= CURDATE() 
    GROUP BY status
");

// Recent activity
$recentActivity = $db->fetchAll("
    SELECT al.*, CONCAT(u.first_name, ' ', u.last_name) as user_name
    FROM audit_logs al
    LEFT JOIN users u ON al.user_id = u.id
    ORDER BY al.created_at DESC
    LIMIT 10
");

// Upcoming hearings
$upcomingHearings = $db->fetchAll("
    SELECT h.*, c.case_no, c.title as case_title, 
           CONCAT(j.first_name, ' ', j.last_name) as judge_name
    FROM hearings h
    JOIN cases c ON h.case_id = c.id
    JOIN users j ON h.judge_id = j.id
    WHERE h.hearing_date >= NOW() AND h.status IN ('Scheduled', 'Rescheduled')
    ORDER BY h.hearing_date ASC
    LIMIT 5
");

// System alerts
$alerts = [];

// Check for overdue cases
$overdueCases = $db->fetch("
    SELECT COUNT(*) as count 
    FROM cases 
    WHERE status NOT IN ('Closed', 'Dismissed') 
    AND filing_date < DATE_SUB(NOW(), INTERVAL 6 MONTH)
")['count'];

if ($overdueCases > 0) {
    $alerts[] = [
        'type' => 'warning',
        'message' => "{$overdueCases} cases are older than 6 months and still open"
    ];
}

// Check for unassigned cases
$unassignedCases = $db->fetch("
    SELECT COUNT(*) as count 
    FROM cases 
    WHERE assigned_judge_id IS NULL 
    AND status NOT IN ('Closed', 'Dismissed')
")['count'];

if ($unassignedCases > 0) {
    $alerts[] = [
        'type' => 'info',
        'message' => "{$unassignedCases} cases are not assigned to any judge"
    ];
}

$pageTitle = 'Admin Dashboard';
include '../layouts/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="bi bi-speedometer2 me-2"></i>Admin Dashboard</h1>
    <div class="text-muted">
        Welcome back, <?= htmlspecialchars($_SESSION['user_name']) ?>
    </div>
</div>

<!-- System Alerts -->
<?php if (!empty($alerts)): ?>
<div class="row mb-4">
    <div class="col-12">
        <?php foreach ($alerts as $alert): ?>
        <div class="alert alert-<?= $alert['type'] ?> alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <?= htmlspecialchars($alert['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
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
        <div class="card bg-info text-white mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <?php
                        $totalUsers = array_sum(array_column($userStats, 'count'));
                        ?>
                        <h3 class="mb-0"><?= number_format($totalUsers) ?></h3>
                        <p class="mb-0">Active Users</p>
                    </div>
                    <i class="bi bi-people fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="card bg-warning text-white mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <?php
                        $totalHearings = array_sum(array_column($hearingStats, 'count'));
                        ?>
                        <h3 class="mb-0"><?= number_format($totalHearings) ?></h3>
                        <p class="mb-0">Upcoming Hearings</p>
                    </div>
                    <i class="bi bi-calendar-event fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-8">
        <!-- Case Status Chart -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-bar-chart me-2"></i>Cases by Status</h5>
            </div>
            <div class="card-body">
                <canvas id="caseStatusChart" width="400" height="200"></canvas>
            </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-activity me-2"></i>Recent Activity</h5>
                <a href="../admin/audit.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($recentActivity)): ?>
                <div class="text-center py-3">
                    <i class="bi bi-activity text-muted"></i>
                    <p class="text-muted mt-2 mb-0">No recent activity</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Action</th>
                                <th>User</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentActivity as $activity): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($activity['action']) ?></span>
                                    <?php if ($activity['table_name']): ?>
                                    <small class="text-muted">on <?= htmlspecialchars($activity['table_name']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($activity['user_name'] ?? 'System') ?></td>
                                <td><?= timeAgo($activity['created_at']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
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
                        <i class="bi bi-plus-circle me-2"></i>New Case
                    </a>
                    <a href="../admin/users.php" class="btn btn-outline-primary">
                        <i class="bi bi-person-plus me-2"></i>Add User
                    </a>
                    <a href="../reports/dashboard.php" class="btn btn-outline-success">
                        <i class="bi bi-graph-up me-2"></i>Generate Report
                    </a>
                    <a href="../admin/settings.php" class="btn btn-outline-secondary">
                        <i class="bi bi-gear me-2"></i>System Settings
                    </a>
                </div>
            </div>
        </div>
        
        <!-- User Distribution -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-people me-2"></i>User Distribution</h5>
            </div>
            <div class="card-body">
                <?php foreach ($userStats as $stat): ?>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span><?= getRoleDisplayName($stat['role']) ?></span>
                    <span class="badge bg-primary"><?= $stat['count'] ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Upcoming Hearings -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-calendar-event me-2"></i>Upcoming Hearings</h5>
                <a href="../hearings/list.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($upcomingHearings)): ?>
                <div class="text-center py-3">
                    <i class="bi bi-calendar-x text-muted"></i>
                    <p class="text-muted mt-2 mb-0">No upcoming hearings</p>
                </div>
                <?php else: ?>
                <?php foreach ($upcomingHearings as $hearing): ?>
                <div class="border-bottom pb-2 mb-2">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="mb-1"><?= htmlspecialchars($hearing['case_no']) ?></h6>
                            <small class="text-muted"><?= htmlspecialchars($hearing['judge_name']) ?></small>
                        </div>
                        <small class="text-muted"><?= formatDateTime($hearing['hearing_date'], 'M j, g:i A') ?></small>
                    </div>
                    <p class="mb-1 small"><?= htmlspecialchars(substr($hearing['case_title'], 0, 50)) ?>...</p>
                    <span class="badge bg-info"><?= htmlspecialchars($hearing['court_room']) ?></span>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
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