<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . APP_NAME : APP_NAME; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
    
    <style>
        :root {
            --court-primary: #1e3a8a;
            --court-secondary: #3b82f6;
            --court-success: #10b981;
            --court-warning: #f59e0b;
            --court-danger: #ef4444;
            --court-dark: #1f2937;
            --court-light: #f8fafc;
        }
        
        body {
            background-color: var(--court-light);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .navbar-brand {
            font-weight: bold;
            color: var(--court-primary) !important;
        }
        
        .navbar {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .card {
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-radius: 10px;
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--court-primary), var(--court-secondary));
            color: white;
            border-radius: 10px 10px 0 0 !important;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--court-primary), var(--court-secondary));
            border: none;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--court-secondary), var(--court-primary));
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .table th {
            background-color: var(--court-primary);
            color: white;
            border: none;
        }
        
        .badge-status {
            padding: 0.5em 0.75em;
            border-radius: 20px;
        }
        
        .status-filed { background-color: var(--court-warning); }
        .status-pending { background-color: #6c757d; }
        .status-hearing { background-color: var(--court-secondary); }
        .status-judged { background-color: var(--court-success); }
        .status-closed { background-color: var(--court-dark); }
        
        .sidebar {
            background: linear-gradient(180deg, var(--court-primary), var(--court-dark));
            min-height: 100vh;
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 0.75rem 1rem;
            border-radius: 5px;
            margin: 0.25rem 0;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background-color: rgba(255,255,255,0.1);
            color: white;
        }
        
        .dashboard-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .dashboard-card h3 {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .form-control:focus {
            border-color: var(--court-secondary);
            box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25);
        }
        
        .btn-outline-primary {
            color: var(--court-primary);
            border-color: var(--court-primary);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--court-primary);
            border-color: var(--court-primary);
        }
    </style>
</head>
<body>
    <?php if (is_logged_in()): ?>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-building"></i>
                <?php echo APP_NAME; ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    
                    <?php if (check_permission('clerk')): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="casesDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-folder"></i> Cases
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="cases.php">All Cases</a></li>
                            <li><a class="dropdown-item" href="new-case.php">New Case</a></li>
                            <li><a class="dropdown-item" href="case-search.php">Search Cases</a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (check_permission('clerk')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="hearings.php">
                            <i class="bi bi-calendar-event"></i> Hearings
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (check_permission('judge')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="my-cases.php">
                            <i class="bi bi-briefcase"></i> My Cases
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (check_permission('manager')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">
                            <i class="bi bi-graph-up"></i> Reports
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (check_permission('admin')): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-gear"></i> Admin
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="users.php">Users</a></li>
                            <li><a class="dropdown-item" href="settings.php">Settings</a></li>
                            <li><a class="dropdown-item" href="audit-logs.php">Audit Logs</a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-bell"></i>
                            <span class="badge bg-danger" id="notification-count">0</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" style="width: 300px;">
                            <li><h6 class="dropdown-header">Notifications</h6></li>
                            <li><div id="notifications-list">Loading...</div></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-center" href="notifications.php">View All</a></li>
                        </ul>
                    </li>
                    
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i>
                            <?php echo $_SESSION['user_name']; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php">
                                <i class="bi bi-person"></i> My Profile
                            </a></li>
                            <li><a class="dropdown-item" href="change-password.php">
                                <i class="bi bi-key"></i> Change Password
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <?php endif; ?>
    
    <!-- Flash Messages -->
    <?php 
    $flash_messages = get_flash_messages();
    foreach ($flash_messages as $type => $message): 
        $alert_class = $type === 'error' ? 'danger' : $type;
    ?>
    <div class="alert alert-<?php echo $alert_class; ?> alert-dismissible fade show m-3" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endforeach; ?>
    
    <div class="container-fluid">
        <?php if (is_logged_in()): ?>
        <div class="row">
            <!-- Sidebar for larger screens -->
            <div class="col-md-2 d-none d-md-block p-0">
                <div class="sidebar">
                    <div class="p-3">
                        <h6 class="text-white-50 text-uppercase">Navigation</h6>
                        <nav class="nav flex-column">
                            <a class="nav-link" href="dashboard.php">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                            
                            <?php if (check_permission('clerk')): ?>
                            <a class="nav-link" href="cases.php">
                                <i class="bi bi-folder"></i> Cases
                            </a>
                            <a class="nav-link" href="new-case.php">
                                <i class="bi bi-plus-circle"></i> New Case
                            </a>
                            <a class="nav-link" href="hearings.php">
                                <i class="bi bi-calendar-event"></i> Hearings
                            </a>
                            <?php endif; ?>
                            
                            <?php if (check_permission('judge')): ?>
                            <a class="nav-link" href="my-cases.php">
                                <i class="bi bi-briefcase"></i> My Cases
                            </a>
                            <a class="nav-link" href="my-agenda.php">
                                <i class="bi bi-calendar-check"></i> My Agenda
                            </a>
                            <?php endif; ?>
                            
                            <?php if (check_permission('manager')): ?>
                            <a class="nav-link" href="reports.php">
                                <i class="bi bi-graph-up"></i> Reports
                            </a>
                            <?php endif; ?>
                            
                            <?php if (check_permission('admin')): ?>
                            <hr class="text-white-50">
                            <h6 class="text-white-50 text-uppercase">Administration</h6>
                            <a class="nav-link" href="users.php">
                                <i class="bi bi-people"></i> Users
                            </a>
                            <a class="nav-link" href="settings.php">
                                <i class="bi bi-gear"></i> Settings
                            </a>
                            <a class="nav-link" href="audit-logs.php">
                                <i class="bi bi-list-check"></i> Audit Logs
                            </a>
                            <?php endif; ?>
                        </nav>
                    </div>
                </div>
            </div>
            
            <!-- Main content -->
            <div class="col-md-10">
                <div class="p-4">
        <?php else: ?>
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
        <?php endif; ?>