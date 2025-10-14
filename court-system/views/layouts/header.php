<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Court Management System' ?> - <?= SITE_NAME ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom CSS -->
    <link href="/court-system/assets/css/style.css" rel="stylesheet">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/court-system/assets/images/favicon.ico">
    
    <style>
        :root {
            --court-primary: #2c3e50;
            --court-secondary: #34495e;
            --court-success: #27ae60;
            --court-danger: #e74c3c;
            --court-warning: #f39c12;
            --court-info: #3498db;
            --court-light: #ecf0f1;
            --court-dark: #2c3e50;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        
        .navbar-brand {
            font-weight: bold;
            color: var(--court-primary) !important;
        }
        
        .sidebar {
            min-height: calc(100vh - 56px);
            background-color: var(--court-primary);
            color: white;
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 0.75rem 1rem;
            border-radius: 0.375rem;
            margin: 0.125rem 0;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border-radius: 0.5rem;
        }
        
        .card-header {
            background-color: var(--court-light);
            border-bottom: 1px solid rgba(0, 0, 0, 0.125);
            font-weight: 600;
        }
        
        .btn-court-primary {
            background-color: var(--court-primary);
            border-color: var(--court-primary);
            color: white;
        }
        
        .btn-court-primary:hover {
            background-color: var(--court-secondary);
            border-color: var(--court-secondary);
            color: white;
        }
        
        .table th {
            background-color: var(--court-light);
            border-top: none;
            font-weight: 600;
        }
        
        .badge {
            font-size: 0.75em;
        }
        
        .alert {
            border: none;
            border-radius: 0.5rem;
        }
        
        .form-control:focus,
        .form-select:focus {
            border-color: var(--court-primary);
            box-shadow: 0 0 0 0.2rem rgba(44, 62, 80, 0.25);
        }
        
        .login-container {
            min-height: 100vh;
            background: linear-gradient(135deg, var(--court-primary) 0%, var(--court-secondary) 100%);
        }
        
        .login-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.175);
        }
        
        .stats-card {
            background: linear-gradient(135deg, var(--court-primary) 0%, var(--court-secondary) 100%);
            color: white;
            border-radius: 1rem;
        }
        
        .stats-card .card-body {
            padding: 2rem;
        }
        
        .stats-card h3 {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0;
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: var(--court-danger);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .case-priority-high {
            border-left: 4px solid var(--court-danger);
        }
        
        .case-priority-normal {
            border-left: 4px solid var(--court-info);
        }
        
        .case-priority-low {
            border-left: 4px solid var(--court-success);
        }
        
        .hearing-today {
            background-color: #fff3cd;
            border-color: #ffeaa7;
        }
        
        .hearing-upcoming {
            background-color: #d1ecf1;
            border-color: #bee5eb;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                min-height: auto;
            }
        }
    </style>
</head>
<body>
    <?php if (isLoggedIn()): ?>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand" href="/court-system/">
                <i class="bi bi-bank2 me-2"></i>
                <?= SITE_NAME ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <!-- Notifications -->
                    <li class="nav-item dropdown">
                        <a class="nav-link position-relative" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-bell"></i>
                            <?php
                            $db = getDB();
                            $unreadCount = $db->fetch("SELECT COUNT(*) as count FROM notifications WHERE recipient_id = ? AND read_at IS NULL", [$_SESSION['user_id']])['count'];
                            if ($unreadCount > 0):
                            ?>
                            <span class="notification-badge"><?= $unreadCount > 9 ? '9+' : $unreadCount ?></span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" style="width: 300px;">
                            <li><h6 class="dropdown-header">Notifications</h6></li>
                            <?php
                            $notifications = $db->fetchAll("SELECT * FROM notifications WHERE recipient_id = ? ORDER BY created_at DESC LIMIT 5", [$_SESSION['user_id']]);
                            foreach ($notifications as $notification):
                            ?>
                            <li>
                                <a class="dropdown-item <?= $notification['read_at'] ? '' : 'fw-bold' ?>" href="#">
                                    <div class="d-flex justify-content-between">
                                        <span><?= htmlspecialchars($notification['title']) ?></span>
                                        <small class="text-muted"><?= timeAgo($notification['created_at']) ?></small>
                                    </div>
                                    <small class="text-muted"><?= htmlspecialchars(substr($notification['message'], 0, 50)) ?>...</small>
                                </a>
                            </li>
                            <?php endforeach; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-center" href="/court-system/views/notifications.php">View All</a></li>
                        </ul>
                    </li>
                    
                    <!-- User Menu -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i>
                            <?= htmlspecialchars($_SESSION['user_name']) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><h6 class="dropdown-header"><?= getRoleDisplayName($_SESSION['user_role']) ?></h6></li>
                            <li><a class="dropdown-item" href="/court-system/views/profile.php"><i class="bi bi-person me-2"></i>Profile</a></li>
                            <li><a class="dropdown-item" href="/court-system/views/settings.php"><i class="bi bi-gear me-2"></i>Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/court-system/views/auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <div class="sidebar">
                    <div class="p-3">
                        <nav class="nav flex-column">
                            <!-- Dashboard -->
                            <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], 'dashboard') !== false ? 'active' : '' ?>" href="/court-system/views/dashboard/<?= $_SESSION['user_role'] ?>.php">
                                <i class="bi bi-speedometer2 me-2"></i>Dashboard
                            </a>
                            
                            <!-- Cases -->
                            <?php if (hasPermission('view_cases') || hasPermission('view_assigned_cases')): ?>
                            <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], 'cases') !== false ? 'active' : '' ?>" href="/court-system/views/cases/list.php">
                                <i class="bi bi-folder me-2"></i>Cases
                            </a>
                            <?php endif; ?>
                            
                            <!-- Hearings -->
                            <?php if (hasPermission('schedule_hearing') || hasPermission('view_assigned_cases')): ?>
                            <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], 'hearings') !== false ? 'active' : '' ?>" href="/court-system/views/hearings/list.php">
                                <i class="bi bi-calendar-event me-2"></i>Hearings
                            </a>
                            <?php endif; ?>
                            
                            <!-- Documents -->
                            <?php if (hasPermission('upload_document')): ?>
                            <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], 'documents') !== false ? 'active' : '' ?>" href="/court-system/views/documents/list.php">
                                <i class="bi bi-file-earmark-text me-2"></i>Documents
                            </a>
                            <?php endif; ?>
                            
                            <!-- Reports -->
                            <?php if (hasPermission('generate_report')): ?>
                            <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], 'reports') !== false ? 'active' : '' ?>" href="/court-system/views/reports/dashboard.php">
                                <i class="bi bi-graph-up me-2"></i>Reports
                            </a>
                            <?php endif; ?>
                            
                            <!-- Administration -->
                            <?php if (hasPermission('create_user') || hasPermission('system_config')): ?>
                            <hr class="my-3">
                            <h6 class="text-muted px-3 mb-2">Administration</h6>
                            
                            <?php if (hasPermission('create_user')): ?>
                            <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], 'admin/users') !== false ? 'active' : '' ?>" href="/court-system/views/admin/users.php">
                                <i class="bi bi-people me-2"></i>Users
                            </a>
                            <?php endif; ?>
                            
                            <?php if (hasPermission('view_audit_log')): ?>
                            <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], 'admin/audit') !== false ? 'active' : '' ?>" href="/court-system/views/admin/audit.php">
                                <i class="bi bi-shield-check me-2"></i>Audit Log
                            </a>
                            <?php endif; ?>
                            
                            <?php if (hasPermission('system_config')): ?>
                            <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], 'admin/settings') !== false ? 'active' : '' ?>" href="/court-system/views/admin/settings.php">
                                <i class="bi bi-gear me-2"></i>System Settings
                            </a>
                            <?php endif; ?>
                            <?php endif; ?>
                        </nav>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="p-4">
                    <?php displayFlashMessage(); ?>
    <?php endif; ?>