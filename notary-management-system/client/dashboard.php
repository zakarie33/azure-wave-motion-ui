<?php
require_once '../config/config.php';
require_once '../includes/Auth.php';
require_once '../includes/models/User.php';

$auth = new Auth();
$auth->requireRole('client');

$currentUser = $auth->getCurrentUser();
$userModel = new User();

// Get client-specific data
$db = new Database();
$db->connect();

// Get appointment statistics
$upcomingAppointments = $db->fetchAll(
    "SELECT a.*, u.first_name as notary_first, u.last_name as notary_last, u.phone as notary_phone
     FROM appointments a 
     JOIN users u ON a.notary_id = u.id 
     WHERE a.client_id = :client_id AND a.appointment_date >= CURDATE()
     ORDER BY a.appointment_date ASC, a.appointment_time ASC 
     LIMIT 5",
    ['client_id' => $currentUser['id']]
);

$totalAppointments = $db->fetchOne(
    "SELECT COUNT(*) as count FROM appointments WHERE client_id = :client_id",
    ['client_id' => $currentUser['id']]
)['count'];

$completedAppointments = $db->fetchOne(
    "SELECT COUNT(*) as count FROM appointments WHERE client_id = :client_id AND status = 'completed'",
    ['client_id' => $currentUser['id']]
)['count'];

// Get recent documents
$recentDocuments = $db->fetchAll(
    "SELECT d.*, u.first_name, u.last_name 
     FROM documents d 
     LEFT JOIN users u ON d.notary_id = u.id 
     WHERE d.client_id = :client_id 
     ORDER BY d.upload_date DESC 
     LIMIT 5",
    ['client_id' => $currentUser['id']]
);

// Get available notaries
$availableNotaries = $db->fetchAll(
    "SELECT u.id, u.first_name, u.last_name, u.email, n.license_number, n.hourly_rate
     FROM users u 
     JOIN notaries n ON u.id = n.user_id 
     WHERE u.status = 'active' AND u.role = 'notary'
     ORDER BY u.first_name, u.last_name 
     LIMIT 3"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Dashboard - <?php echo APP_NAME; ?></title>
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
                        <i class="fas fa-user-circle fa-2x text-white mb-2"></i>
                        <h5 class="text-white"><?php echo APP_NAME; ?></h5>
                        <small class="text-white-50">Client Portal</small>
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
                            <a class="nav-link" href="book-appointment.php">
                                <i class="fas fa-calendar-plus"></i> Book Appointment
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="documents.php">
                                <i class="fas fa-file-alt"></i> My Documents
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="notaries.php">
                                <i class="fas fa-search"></i> Find Notaries
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="history.php">
                                <i class="fas fa-history"></i> History
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
                            <a href="book-appointment.php" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus"></i> Book Appointment
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
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <div class="ms-3">
                                    <div class="text-muted small">Total Appointments</div>
                                    <div class="h4 mb-0"><?php echo number_format($totalAppointments); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card success">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="ms-3">
                                    <div class="text-muted small">Completed</div>
                                    <div class="h4 mb-0"><?php echo number_format($completedAppointments); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card warning">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="ms-3">
                                    <div class="text-muted small">Upcoming</div>
                                    <div class="h4 mb-0"><?php echo number_format(count($upcomingAppointments)); ?></div>
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
                                    <div class="text-muted small">Documents</div>
                                    <div class="h4 mb-0"><?php echo number_format(count($recentDocuments)); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Main Content Row -->
                <div class="row">
                    <!-- Upcoming Appointments -->
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-calendar-alt me-2"></i>Upcoming Appointments
                                </h5>
                                <a href="appointments.php" class="btn btn-sm btn-outline-primary">View All</a>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($upcomingAppointments)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Notary</th>
                                                <th>Date & Time</th>
                                                <th>Type</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($upcomingAppointments as $appointment): ?>
                                            <tr>
                                                <td>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($appointment['notary_first'] . ' ' . $appointment['notary_last']); ?></strong>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars($appointment['notary_phone']); ?></small>
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
                                                        <?php if (in_array($appointment['status'], ['scheduled', 'confirmed'])): ?>
                                                        <a href="appointments.php?action=edit&id=<?php echo $appointment['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <?php endif; ?>
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
                                    <p>No upcoming appointments</p>
                                    <a href="book-appointment.php" class="btn btn-primary">Book Your First Appointment</a>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Services Available -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-concierge-bell me-2"></i>Available Services
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <div class="card border-primary h-100">
                                            <div class="card-body text-center">
                                                <i class="fas fa-file-signature fa-2x text-primary mb-2"></i>
                                                <h6>Document Notarization</h6>
                                                <p class="small text-muted">Legal document notarization and verification</p>
                                                <a href="book-appointment.php?service=document" class="btn btn-sm btn-primary">Book Now</a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="card border-success h-100">
                                            <div class="card-body text-center">
                                                <i class="fas fa-home fa-2x text-success mb-2"></i>
                                                <h6>Real Estate Documents</h6>
                                                <p class="small text-muted">Property transfers, deeds, and mortgages</p>
                                                <a href="book-appointment.php?service=realestate" class="btn btn-sm btn-success">Book Now</a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="card border-warning h-100">
                                            <div class="card-body text-center">
                                                <i class="fas fa-handshake fa-2x text-warning mb-2"></i>
                                                <h6>Business Documents</h6>
                                                <p class="small text-muted">Contracts, agreements, and business forms</p>
                                                <a href="book-appointment.php?service=business" class="btn btn-sm btn-warning">Book Now</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sidebar Content -->
                    <div class="col-lg-4">
                        <!-- Quick Actions -->
                        <div class="card mb-3">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-tools me-2"></i>Quick Actions
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="book-appointment.php" class="btn btn-primary">
                                        <i class="fas fa-calendar-plus me-2"></i>Book Appointment
                                    </a>
                                    <a href="documents.php?action=upload" class="btn btn-outline-secondary">
                                        <i class="fas fa-upload me-2"></i>Upload Document
                                    </a>
                                    <a href="notaries.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-search me-2"></i>Find Notaries
                                    </a>
                                    <a href="history.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-history me-2"></i>View History
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Recent Documents -->
                        <div class="card mb-3">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-file-alt me-2"></i>Recent Documents
                                </h5>
                                <a href="documents.php" class="btn btn-sm btn-outline-primary">View All</a>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($recentDocuments)): ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($recentDocuments as $document): ?>
                                    <div class="list-group-item px-0">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($document['document_name']); ?></h6>
                                                <p class="mb-1 text-muted small">
                                                    Status: <span class="badge status-<?php echo $document['status']; ?>"><?php echo ucfirst($document['status']); ?></span>
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
                                    <p>No documents yet</p>
                                    <a href="documents.php?action=upload" class="btn btn-sm btn-primary">Upload First Document</a>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Available Notaries -->
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-users me-2"></i>Featured Notaries
                                </h5>
                                <a href="notaries.php" class="btn btn-sm btn-outline-primary">View All</a>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($availableNotaries)): ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($availableNotaries as $notary): ?>
                                    <div class="list-group-item px-0">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($notary['first_name'] . ' ' . $notary['last_name']); ?></h6>
                                                <p class="mb-1 text-muted small">License: <?php echo htmlspecialchars($notary['license_number']); ?></p>
                                                <?php if ($notary['hourly_rate']): ?>
                                                <small class="text-success">$<?php echo number_format($notary['hourly_rate'], 2); ?>/hour</small>
                                                <?php endif; ?>
                                            </div>
                                            <a href="book-appointment.php?notary=<?php echo $notary['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                Book
                                            </a>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php else: ?>
                                <div class="text-center text-muted py-3">
                                    <i class="fas fa-users fa-2x mb-2"></i>
                                    <p>No notaries available</p>
                                </div>
                                <?php endif; ?>
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