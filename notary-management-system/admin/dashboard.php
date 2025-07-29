<?php
require_once '../config/config.php';
require_once '../includes/Auth.php';
require_once '../includes/models/User.php';
require_once '../includes/ActivityLogger.php';

$auth = new Auth();
$auth->requireRole('admin');

$userModel = new User();
$logger = new ActivityLogger((new Database())->connect() ? new Database() : null);

// Get dashboard statistics
$userStats = $userModel->getUserStats();
$activityStats = $logger->getSystemStats();
$recentActivity = $logger->getRecentActivity(10);

// Get counts for dashboard cards
$totalUsers = array_sum(array_filter($userStats, 'is_numeric'));
$totalClients = $userStats['client'] ?? 0;
$totalNotaries = $userStats['notary'] ?? 0;
$totalAdmins = $userStats['admin'] ?? 0;

$currentUser = $auth->getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <i class="fas fa-stamp fa-2x text-white mb-2"></i>
                        <h5 class="text-white"><?php echo APP_NAME; ?></h5>
                        <small class="text-white-50">Admin Panel</small>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="users.php">
                                <i class="fas fa-users"></i> User Management
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="clients.php">
                                <i class="fas fa-user-friends"></i> Clients
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="notaries.php">
                                <i class="fas fa-certificate"></i> Notaries
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="appointments.php">
                                <i class="fas fa-calendar-alt"></i> Appointments
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="documents.php">
                                <i class="fas fa-file-alt"></i> Documents
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="reports.php">
                                <i class="fas fa-chart-bar"></i> Reports
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="activity-logs.php">
                                <i class="fas fa-history"></i> Activity Logs
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="settings.php">
                                <i class="fas fa-cog"></i> System Settings
                            </a>
                        </li>
                    </ul>
                    
                    <hr class="text-white-50">
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="profile.php">
                                <i class="fas fa-user"></i> Profile
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../logout.php">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>
            
            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <!-- Top Bar -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-tachometer-alt me-2"></i>Admin Dashboard
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="location.reload()">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </button>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                                <li><a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card primary">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="ms-3">
                                    <div class="text-muted small">Total Users</div>
                                    <div class="h4 mb-0"><?php echo number_format($totalUsers); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card success">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon">
                                    <i class="fas fa-user-friends"></i>
                                </div>
                                <div class="ms-3">
                                    <div class="text-muted small">Clients</div>
                                    <div class="h4 mb-0"><?php echo number_format($totalClients); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card warning">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon">
                                    <i class="fas fa-certificate"></i>
                                </div>
                                <div class="ms-3">
                                    <div class="text-muted small">Notaries</div>
                                    <div class="h4 mb-0"><?php echo number_format($totalNotaries); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card danger">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon">
                                    <i class="fas fa-history"></i>
                                </div>
                                <div class="ms-3">
                                    <div class="text-muted small">Today's Activity</div>
                                    <div class="h4 mb-0"><?php echo number_format($activityStats['today'] ?? 0); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Activity Statistics -->
                <div class="row mb-4">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-chart-line me-2"></i>System Activity Overview
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 text-center">
                                        <h4 class="text-primary"><?php echo number_format($activityStats['today'] ?? 0); ?></h4>
                                        <small class="text-muted">Today</small>
                                    </div>
                                    <div class="col-md-4 text-center">
                                        <h4 class="text-success"><?php echo number_format($activityStats['week'] ?? 0); ?></h4>
                                        <small class="text-muted">This Week</small>
                                    </div>
                                    <div class="col-md-4 text-center">
                                        <h4 class="text-info"><?php echo number_format($activityStats['month'] ?? 0); ?></h4>
                                        <small class="text-muted">This Month</small>
                                    </div>
                                </div>
                                
                                <?php if (!empty($activityStats['common_actions'])): ?>
                                <hr>
                                <h6>Most Common Actions</h6>
                                <div class="row">
                                    <?php foreach (array_slice($activityStats['common_actions'], 0, 6) as $action): ?>
                                    <div class="col-md-4 mb-2">
                                        <div class="d-flex justify-content-between">
                                            <span class="text-capitalize"><?php echo str_replace('_', ' ', $action['action']); ?></span>
                                            <span class="badge bg-secondary"><?php echo $action['count']; ?></span>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-users me-2"></i>User Distribution
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span>Clients</span>
                                        <span class="text-success"><?php echo $totalClients; ?></span>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-success" style="width: <?php echo $totalUsers > 0 ? ($totalClients / $totalUsers) * 100 : 0; ?>%"></div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span>Notaries</span>
                                        <span class="text-warning"><?php echo $totalNotaries; ?></span>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-warning" style="width: <?php echo $totalUsers > 0 ? ($totalNotaries / $totalUsers) * 100 : 0; ?>%"></div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span>Admins</span>
                                        <span class="text-danger"><?php echo $totalAdmins; ?></span>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-danger" style="width: <?php echo $totalUsers > 0 ? ($totalAdmins / $totalUsers) * 100 : 0; ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-clock me-2"></i>Recent Activity
                                </h5>
                                <a href="activity-logs.php" class="btn btn-sm btn-outline-primary">View All</a>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($recentActivity)): ?>
                                <div class="activity-feed">
                                    <?php foreach ($recentActivity as $activity): ?>
                                    <div class="activity-item d-flex mb-3">
                                        <div class="activity-icon me-3">
                                            <i class="fas fa-circle text-primary" style="font-size: 8px;"></i>
                                        </div>
                                        <div class="activity-content flex-grow-1">
                                            <div class="activity-description">
                                                <strong><?php echo htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']); ?></strong>
                                                <?php echo ucfirst(str_replace('_', ' ', $activity['action'])); ?>
                                                <?php if ($activity['table_name']): ?>
                                                    in <?php echo ucfirst($activity['table_name']); ?>
                                                <?php endif; ?>
                                            </div>
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i>
                                                <?php echo date('M j, Y g:i A', strtotime($activity['created_at'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php else: ?>
                                <div class="text-center text-muted py-3">
                                    <i class="fas fa-history fa-2x mb-2"></i>
                                    <p>No recent activity</p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-tools me-2"></i>Quick Actions
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="users.php?action=create" class="btn btn-primary">
                                        <i class="fas fa-user-plus me-2"></i>Add New User
                                    </a>
                                    <a href="reports.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-chart-bar me-2"></i>Generate Report
                                    </a>
                                    <a href="settings.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-cog me-2"></i>System Settings
                                    </a>
                                    <a href="activity-logs.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-history me-2"></i>View Activity Logs
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mt-3">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-info-circle me-2"></i>System Info
                                </h5>
                            </div>
                            <div class="card-body">
                                <small class="text-muted">
                                    <div class="mb-1"><strong>Version:</strong> <?php echo APP_VERSION; ?></div>
                                    <div class="mb-1"><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></div>
                                    <div class="mb-1"><strong>Server:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></div>
                                    <div><strong>Uptime:</strong> <?php echo date('Y-m-d H:i:s'); ?></div>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-refresh dashboard every 5 minutes
        setTimeout(function() {
            location.reload();
        }, 300000);
        
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
</body>
</html>