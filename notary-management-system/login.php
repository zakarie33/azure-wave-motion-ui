<?php
require_once 'config/config.php';
require_once 'includes/Auth.php';

$auth = new Auth();
$error = '';
$success = '';

// If already logged in, redirect to appropriate dashboard
if ($auth->isLoggedIn()) {
    header('Location: index.php');
    exit();
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $result = $auth->login($username, $password);
        
        if ($result['success']) {
            header('Location: ' . $result['redirect']);
            exit();
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container-fluid vh-100">
        <div class="row h-100">
            <!-- Left side - Branding -->
            <div class="col-md-6 d-none d-md-flex bg-primary text-white align-items-center justify-content-center">
                <div class="text-center">
                    <i class="fas fa-stamp fa-5x mb-4 opacity-75"></i>
                    <h1 class="display-4 fw-bold"><?php echo APP_NAME; ?></h1>
                    <p class="lead">Secure • Professional • Efficient</p>
                    <div class="mt-5">
                        <h5>Features:</h5>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check me-2"></i> Client Management</li>
                            <li><i class="fas fa-check me-2"></i> Appointment Scheduling</li>
                            <li><i class="fas fa-check me-2"></i> Document Management</li>
                            <li><i class="fas fa-check me-2"></i> Notarization Logs</li>
                            <li><i class="fas fa-check me-2"></i> Audit Trails</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Right side - Login Form -->
            <div class="col-md-6 d-flex align-items-center justify-content-center">
                <div class="w-100" style="max-width: 400px;">
                    <div class="card shadow-lg border-0">
                        <div class="card-body p-5">
                            <div class="text-center mb-4">
                                <i class="fas fa-user-circle fa-3x text-primary mb-3"></i>
                                <h2 class="card-title">Welcome Back</h2>
                                <p class="text-muted">Sign in to your account</p>
                            </div>
                            
                            <?php if ($error): ?>
                                <div class="alert alert-danger" role="alert">
                                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($success): ?>
                                <div class="alert alert-success" role="alert">
                                    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" action="" id="loginForm">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username or Email</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        <input type="text" class="form-control" id="username" name="username" 
                                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                                               required autocomplete="username">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" id="password" name="password" 
                                               required autocomplete="current-password">
                                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="rememberMe" name="remember_me">
                                    <label class="form-check-label" for="rememberMe">Remember me</label>
                                </div>
                                
                                <button type="submit" class="btn btn-primary w-100 py-2">
                                    <i class="fas fa-sign-in-alt me-2"></i>Sign In
                                </button>
                            </form>
                            
                            <div class="text-center mt-4">
                                <a href="forgot-password.php" class="text-decoration-none">
                                    <i class="fas fa-key me-1"></i>Forgot Password?
                                </a>
                            </div>
                            
                            <div class="mt-4 pt-4 border-top">
                                <div class="text-center">
                                    <small class="text-muted">Demo Accounts:</small>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-4 text-center">
                                        <small class="d-block text-primary fw-bold">Admin</small>
                                        <small class="text-muted">admin</small>
                                    </div>
                                    <div class="col-4 text-center">
                                        <small class="d-block text-success fw-bold">Notary</small>
                                        <small class="text-muted">notary1</small>
                                    </div>
                                    <div class="col-4 text-center">
                                        <small class="d-block text-info fw-bold">Client</small>
                                        <small class="text-muted">client1</small>
                                    </div>
                                </div>
                                <div class="text-center mt-1">
                                    <small class="text-muted">Password for all: admin123, notary123, client123</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
        
        // Form validation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            
            if (!username || !password) {
                e.preventDefault();
                alert('Please fill in all fields.');
            }
        });
    </script>
</body>
</html>