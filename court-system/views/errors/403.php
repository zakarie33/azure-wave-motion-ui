<?php
/**
 * 403 Error Page - Access Forbidden
 * Author: Court System Development Team
 * Purpose: User-friendly 403 access denied page
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied - Court Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%); min-height: 100vh; }
        .error-container { min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .error-card { background: white; border-radius: 1rem; box-shadow: 0 1rem 3rem rgba(0,0,0,0.2); max-width: 500px; }
        .error-icon { font-size: 4rem; color: #e74c3c; }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="error-card p-5 text-center">
                        <i class="bi bi-shield-exclamation error-icon mb-3"></i>
                        <h1 class="display-4 mb-3">403</h1>
                        <h2 class="h4 mb-3">Access Denied</h2>
                        <p class="text-muted mb-4">
                            You don't have permission to access this resource. 
                            Please contact your system administrator if you believe this is an error.
                        </p>
                        <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                            <a href="/court-system/" class="btn btn-primary">
                                <i class="bi bi-house me-2"></i>Go Home
                            </a>
                            <a href="/court-system/views/auth/login.php" class="btn btn-outline-secondary">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Login
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>