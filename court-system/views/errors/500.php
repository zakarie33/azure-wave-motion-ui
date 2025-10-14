<?php
/**
 * 500 Error Page - Internal Server Error
 * Author: Court System Development Team
 * Purpose: User-friendly 500 server error page
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Error - Court Management System</title>
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
                        <i class="bi bi-exclamation-octagon error-icon mb-3"></i>
                        <h1 class="display-4 mb-3">500</h1>
                        <h2 class="h4 mb-3">Internal Server Error</h2>
                        <p class="text-muted mb-4">
                            Something went wrong on our end. We're working to fix the issue. 
                            Please try again later or contact support if the problem persists.
                        </p>
                        <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                            <a href="/court-system/" class="btn btn-primary">
                                <i class="bi bi-house me-2"></i>Go Home
                            </a>
                            <button onclick="location.reload()" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-clockwise me-2"></i>Try Again
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>