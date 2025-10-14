<?php
/**
 * Main Dashboard
 * Shows overview of court activities
 */

require_once 'config/config.php';

AuthMiddleware::requireAuth();

// Get dashboard statistics
$database = new Database();
$conn = $database->getConnection();

// Get case statistics
$case_model = new CaseModel($conn);
$today = date('Y-m-d');
$week_start = date('Y-m-d', strtotime('monday this week'));
$month_start = date('Y-m-01');

$stats = [
    'today' => $case_model->getStatistics($today, $today),
    'week' => $case_model->getStatistics($week_start, $today),
    'month' => $case_model->getStatistics($month_start, $today),
    'total' => $case_model->getStatistics()
];

// Get recent cases
$recent_cases = $case_model->getAll(1, 5);

// Get upcoming hearings
$hearing_query = "SELECT h.*, c.case_no, c.title 
                  FROM hearings h 
                  JOIN cases c ON h.case_id = c.id 
                  WHERE h.hearing_date >= NOW() 
                  AND h.status = 'Scheduled' 
                  ORDER BY h.hearing_date ASC 
                  LIMIT 5";
$stmt = $conn->prepare($hearing_query);
$stmt->execute();
$upcoming_hearings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user-specific data based on role
$user_data = [];
switch ($_SESSION['user_role']) {
    case 'judge':
        $user_data['my_cases'] = $case_model->getByJudge($_SESSION['user_id']);
        break;
    case 'clerk':
        $user_data['my_cases'] = $case_model->getAll(1, 5, ['created_by' => $_SESSION['user_id']]);
        break;
}

// Get notifications
$notification_query = "SELECT * FROM notifications 
                       WHERE recipient_id = :user_id 
                       AND is_read = 0 
                       ORDER BY created_at DESC 
                       LIMIT 5";
$stmt = $conn->prepare($notification_query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Dashboard";
include 'views/layouts/header.php';
?>

<div class="row">
    <!-- Statistics Cards -->
    <div class="col-md-3 mb-4">
        <div class="dashboard-card bg-primary">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3><?php echo $stats['today']['total_cases']; ?></h3>
                    <p class="mb-0">Cases Today</p>
                </div>
                <i class="bi bi-folder-plus fs-1 opacity-75"></i>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="dashboard-card bg-success">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3><?php echo $stats['week']['total_cases']; ?></h3>
                    <p class="mb-0">Cases This Week</p>
                </div>
                <i class="bi bi-calendar-week fs-1 opacity-75"></i>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="dashboard-card bg-warning">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3><?php echo $stats['month']['total_cases']; ?></h3>
                    <p class="mb-0">Cases This Month</p>
                </div>
                <i class="bi bi-calendar-month fs-1 opacity-75"></i>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="dashboard-card bg-info">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3><?php echo count($upcoming_hearings); ?></h3>
                    <p class="mb-0">Upcoming Hearings</p>
                </div>
                <i class="bi bi-calendar-event fs-1 opacity-75"></i>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Case Status Chart -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-pie-chart"></i>
                    Case Status Distribution
                </h5>
            </div>
            <div class="card-body">
                <canvas id="caseStatusChart" width="400" height="200"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Case Type Chart -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-bar-chart"></i>
                    Case Types
                </h5>
            </div>
            <div class="card-body">
                <canvas id="caseTypeChart" width="400" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Cases -->
    <div class="col-md-8 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-clock-history"></i>
                    Recent Cases
                </h5>
                <a href="cases.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($recent_cases)): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Case No.</th>
                                <th>Title</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Filed Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_cases as $case): ?>
                            <tr>
                                <td>
                                    <a href="case-details.php?id=<?php echo $case['id']; ?>" class="text-decoration-none">
                                        <?php echo htmlspecialchars($case['case_no']); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($case['title']); ?></td>
                                <td>
                                    <span class="badge bg-secondary"><?php echo $case['case_type']; ?></span>
                                </td>
                                <td>
                                    <?php
                                    $status_class = 'status-' . strtolower(str_replace(' ', '-', $case['status']));
                                    ?>
                                    <span class="badge badge-status <?php echo $status_class; ?>">
                                        <?php echo $case['status']; ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($case['filing_date'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center p-4">
                    <i class="bi bi-folder-x fs-1 text-muted"></i>
                    <p class="text-muted mt-2">No recent cases found</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Notifications & Quick Actions -->
    <div class="col-md-4 mb-4">
        <!-- Notifications -->
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-bell"></i>
                    Recent Notifications
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($notifications)): ?>
                    <?php foreach ($notifications as $notification): ?>
                    <div class="d-flex mb-3">
                        <div class="flex-shrink-0">
                            <i class="bi bi-info-circle text-primary"></i>
                        </div>
                        <div class="flex-grow-1 ms-2">
                            <h6 class="mb-1"><?php echo htmlspecialchars($notification['subject']); ?></h6>
                            <p class="mb-1 small"><?php echo htmlspecialchars($notification['message']); ?></p>
                            <small class="text-muted"><?php echo date('M j, g:i A', strtotime($notification['created_at'])); ?></small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <div class="text-center">
                        <a href="notifications.php" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                <?php else: ?>
                <div class="text-center">
                    <i class="bi bi-bell-slash fs-3 text-muted"></i>
                    <p class="text-muted mt-2">No new notifications</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-lightning"></i>
                    Quick Actions
                </h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <?php if (check_permission('clerk')): ?>
                    <a href="new-case.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> New Case
                    </a>
                    <a href="schedule-hearing.php" class="btn btn-outline-primary">
                        <i class="bi bi-calendar-plus"></i> Schedule Hearing
                    </a>
                    <?php endif; ?>
                    
                    <?php if (check_permission('judge')): ?>
                    <a href="my-cases.php" class="btn btn-primary">
                        <i class="bi bi-briefcase"></i> My Cases
                    </a>
                    <a href="my-agenda.php" class="btn btn-outline-primary">
                        <i class="bi bi-calendar-check"></i> My Agenda
                    </a>
                    <?php endif; ?>
                    
                    <?php if (check_permission('manager')): ?>
                    <a href="reports.php" class="btn btn-primary">
                        <i class="bi bi-graph-up"></i> Generate Report
                    </a>
                    <?php endif; ?>
                    
                    <a href="case-search.php" class="btn btn-outline-secondary">
                        <i class="bi bi-search"></i> Search Cases
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Upcoming Hearings -->
<?php if (!empty($upcoming_hearings)): ?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-calendar-event"></i>
                    Upcoming Hearings
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Case No.</th>
                                <th>Case Title</th>
                                <th>Court Room</th>
                                <th>Type</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($upcoming_hearings as $hearing): ?>
                            <tr>
                                <td>
                                    <strong><?php echo date('M j, Y', strtotime($hearing['hearing_date'])); ?></strong><br>
                                    <small class="text-muted"><?php echo date('g:i A', strtotime($hearing['hearing_date'])); ?></small>
                                </td>
                                <td>
                                    <a href="case-details.php?id=<?php echo $hearing['case_id']; ?>">
                                        <?php echo htmlspecialchars($hearing['case_no']); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($hearing['title']); ?></td>
                                <td><?php echo htmlspecialchars($hearing['court_room']); ?></td>
                                <td>
                                    <span class="badge bg-info"><?php echo $hearing['hearing_type']; ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-success"><?php echo $hearing['status']; ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// Chart.js configurations
document.addEventListener('DOMContentLoaded', function() {
    // Case Status Chart
    const statusCtx = document.getElementById('caseStatusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['Filed', 'Pending', 'In Hearing', 'Judged', 'Closed'],
            datasets: [{
                data: [
                    <?php echo $stats['total']['filed_cases']; ?>,
                    <?php echo $stats['total']['pending_cases']; ?>,
                    <?php echo $stats['total']['hearing_cases']; ?>,
                    <?php echo $stats['total']['judged_cases']; ?>,
                    <?php echo $stats['total']['closed_cases']; ?>
                ],
                backgroundColor: [
                    '#f59e0b',
                    '#6b7280',
                    '#3b82f6',
                    '#10b981',
                    '#1f2937'
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
    
    // Case Type Chart
    const typeCtx = document.getElementById('caseTypeChart').getContext('2d');
    new Chart(typeCtx, {
        type: 'bar',
        data: {
            labels: ['Criminal', 'Civil', 'Family', 'Appeal'],
            datasets: [{
                label: 'Number of Cases',
                data: [
                    <?php echo $stats['total']['criminal_cases']; ?>,
                    <?php echo $stats['total']['civil_cases']; ?>,
                    <?php echo $stats['total']['family_cases']; ?>,
                    <?php echo $stats['total']['appeal_cases']; ?>
                ],
                backgroundColor: [
                    '#ef4444',
                    '#3b82f6',
                    '#10b981',
                    '#f59e0b'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
});
</script>

<?php include 'views/layouts/footer.php'; ?>