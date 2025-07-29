<?php
require_once '../config/config.php';
require_once '../includes/Auth.php';
require_once '../includes/models/User.php';

$auth = new Auth();
$auth->requireRole('notary');

$currentUser = $auth->getCurrentUser();
$userModel = new User();

// Get notary-specific data
$db = new Database();
$db->connect();

// Get appointment statistics
$todayAppointments = $db->fetchOne(
    "SELECT COUNT(*) as count FROM appointments WHERE notary_id = :notary_id AND appointment_date = CURDATE()",
    ['notary_id' => $currentUser['id']]
)['count'];

$weekAppointments = $db->fetchOne(
    "SELECT COUNT(*) as count FROM appointments WHERE notary_id = :notary_id AND appointment_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)",
    ['notary_id' => $currentUser['id']]
)['count'];

$totalClients = $db->fetchOne(
    "SELECT COUNT(DISTINCT client_id) as count FROM appointments WHERE notary_id = :notary_id",
    ['notary_id' => $currentUser['id']]
)['count'];

// Get recent appointments
$recentAppointments = $db->fetchAll(
    "SELECT a.*, u.first_name, u.last_name, u.email, u.phone 
     FROM appointments a 
     JOIN users u ON a.client_id = u.id 
     WHERE a.notary_id = :notary_id 
     ORDER BY a.appointment_date DESC, a.appointment_time DESC 
     LIMIT 5",
    ['notary_id' => $currentUser['id']]
);

// Get pending documents
$pendingDocuments = $db->fetchAll(
    "SELECT d.*, u.first_name, u.last_name 
     FROM documents d 
     JOIN users u ON d.client_id = u.id 
     WHERE d.notary_id = :notary_id AND d.status = 'pending_notarization'
     ORDER BY d.upload_date DESC 
     LIMIT 5",
    ['notary_id' => $currentUser['id']]
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notary Dashboard - <?php echo APP_NAME; ?></title>
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
                        <i class="fas fa-certificate fa-2x text-white mb-2"></i>
                        <h5 class="text-white"><?php echo APP_NAME; ?></h5>
                        <small class="text-white-50">Notary Portal</small>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="appointments.php">
                                <i class="fas fa-calendar-alt"></i> My Appointments
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="clients.php">
                                <i class="fas fa-user-friends"></i> My Clients
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="documents.php">
                                <i class="fas fa-file-alt"></i> Documents
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="notarizations.php">
                                <i class="fas fa-stamp"></i> Notarization Log
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="calendar.php">
                                <i class="fas fa-calendar"></i> Calendar
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="earnings.php">
                                <i class="fas fa-dollar-sign"></i> Earnings
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
                        <i class="fas fa-tachometer-alt me-2"></i>Welcome, <?php echo htmlspecialchars($currentUser['first_name']); ?>
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="appointments.php?action=create" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus"></i> New Appointment
                            </a>
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
                                    <i class="fas fa-calendar-day"></i>
                                </div>
                                <div class="ms-3">
                                    <div class="text-muted small">Today's Appointments</div>
                                    <div class="h4 mb-0"><?php echo number_format($todayAppointments); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card success">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon">
                                    <i class="fas fa-calendar-week"></i>
                                </div>
                                <div class="ms-3">
                                    <div class="text-muted small">This Week</div>
                                    <div class="h4 mb-0"><?php echo number_format($weekAppointments); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card warning">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="ms-3">
                                    <div class="text-muted small">Total Clients</div>
                                    <div class="h4 mb-0"><?php echo number_format($totalClients); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card danger">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <div class="ms-3">
                                    <div class="text-muted small">Pending Documents</div>
                                    <div class="h4 mb-0"><?php echo number_format(count($pendingDocuments)); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Main Content Row -->
                <div class="row">
                    <!-- Recent Appointments -->
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-calendar-alt me-2"></i>Recent Appointments
                                </h5>
                                <a href="appointments.php" class="btn btn-sm btn-outline-primary">View All</a>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($recentAppointments)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Client</th>
                                                <th>Date & Time</th>
                                                <th>Type</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentAppointments as $appointment): ?>
                                            <tr>
                                                <td>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']); ?></strong>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars($appointment['email']); ?></small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div>
                                                        <?php echo date('M j, Y', strtotime($appointment['appointment_date'])); ?>
                                                        <br><small class="text-muted"><?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?></small>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($appointment['appointment_type']); ?></td>
                                                <td>
                                                    <span class="badge status-<?php echo $appointment['status']; ?>">
                                                        <?php echo ucfirst($appointment['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href="appointments.php?id=<?php echo $appointment['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="appointments.php?action=edit&id=<?php echo $appointment['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php else: ?>
                                <div class="text-center text-muted py-3">
                                    <i class="fas fa-calendar fa-2x mb-2"></i>
                                    <p>No recent appointments</p>
                                    <a href="appointments.php?action=create" class="btn btn-primary">Schedule First Appointment</a>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions & Pending Documents -->
                    <div class="col-lg-4">
                        <div class="card mb-3">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-tools me-2"></i>Quick Actions
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="appointments.php?action=create" class="btn btn-primary">
                                        <i class="fas fa-calendar-plus me-2"></i>Schedule Appointment
                                    </a>
                                    <a href="clients.php?action=create" class="btn btn-outline-secondary">
                                        <i class="fas fa-user-plus me-2"></i>Add New Client
                                    </a>
                                    <a href="documents.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-upload me-2"></i>Upload Document
                                    </a>
                                    <a href="notarizations.php?action=create" class="btn btn-outline-secondary">
                                        <i class="fas fa-stamp me-2"></i>Record Notarization
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-file-alt me-2"></i>Pending Documents
                                </h5>
                                <a href="documents.php" class="btn btn-sm btn-outline-primary">View All</a>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($pendingDocuments)): ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($pendingDocuments as $document): ?>
                                    <div class="list-group-item px-0">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($document['document_name']); ?></h6>
                                                <p class="mb-1 text-muted small">
                                                    <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($document['first_name'] . ' ' . $document['last_name']); ?>
                                                </p>
                                                <small class="text-muted">
                                                    <i class="fas fa-clock me-1"></i><?php echo date('M j, Y', strtotime($document['upload_date'])); ?>
                                                </small>
                                            </div>
                                            <a href="documents.php?id=<?php echo $document['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php else: ?>
                                <div class="text-center text-muted py-3">
                                    <i class="fas fa-file fa-2x mb-2"></i>
                                    <p>No pending documents</p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- License Information -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-certificate me-2"></i>License Information
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <strong>License Number:</strong><br>
                                        <span class="text-muted"><?php echo htmlspecialchars($currentUser['additional_info'] ?? 'Not Set'); ?></span>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Expiry Date:</strong><br>
                                        <span class="text-muted"><?php echo $currentUser['license_expiry'] ? date('M j, Y', strtotime($currentUser['license_expiry'])) : 'Not Set'; ?></span>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Commission State:</strong><br>
                                        <span class="text-muted">California</span>
                                    </div>
                                    <div class="col-md-3">
                                        <a href="profile.php" class="btn btn-outline-primary">
                                            <i class="fas fa-edit me-2"></i>Update License Info
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
</body>
</html>