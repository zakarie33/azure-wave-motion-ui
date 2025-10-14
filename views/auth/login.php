<?php
require_once 'config/config.php';
include 'views/layouts/header.php';
?>

<div class="min-vh-100 d-flex align-items-center justify-content-center">
    <div class="card shadow-lg" style="width: 100%; max-width: 400px;">
        <div class="card-header text-center">
            <h4 class="mb-0">
                <i class="bi bi-building"></i>
                Court Management System
            </h4>
            <p class="mb-0 mt-2 opacity-75">Sign in to your account</p>
        </div>
        
        <div class="card-body p-4">
            <form method="POST" action="login.php" id="loginForm" onsubmit="return validateForm('loginForm')">
                <input type="hidden" name="csrf_token" value="<?php echo AuthMiddleware::generateCSRF(); ?>">
                
                <div class="mb-3">
                    <label for="email" class="form-label">
                        <i class="bi bi-envelope"></i> Email Address
                    </label>
                    <input type="email" 
                           class="form-control" 
                           id="email" 
                           name="email" 
                           placeholder="you@court.example" 
                           required 
                           autocomplete="email"
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    <div class="invalid-feedback">
                        Please enter a valid email address.
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">
                        <i class="bi bi-lock"></i> Password
                    </label>
                    <div class="input-group">
                        <input type="password" 
                               class="form-control" 
                               id="password" 
                               name="password" 
                               placeholder="Enter your password" 
                               required 
                               autocomplete="current-password">
                        <button class="btn btn-outline-secondary" 
                                type="button" 
                                onclick="togglePassword('password')">
                            <i class="bi bi-eye" id="password-icon"></i>
                        </button>
                    </div>
                    <div class="invalid-feedback">
                        Please enter your password.
                    </div>
                </div>
                
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="remember_me" name="remember_me">
                    <label class="form-check-label" for="remember_me">
                        Remember me for 30 days
                    </label>
                </div>
                
                <div class="d-grid">
                    <button type="submit" 
                            class="btn btn-primary btn-lg" 
                            id="loginButton"
                            onclick="showLoading('loginButton')">
                        <i class="bi bi-box-arrow-in-right"></i>
                        Sign In
                    </button>
                </div>
            </form>
        </div>
        
        <div class="card-footer text-center text-muted">
            <small>
                <i class="bi bi-shield-lock"></i>
                Secure login protected by encryption
            </small>
            <br>
            <small class="mt-2 d-block">
                Forgot your password? Contact your system administrator.
            </small>
        </div>
    </div>
</div>

<!-- Login-specific styles -->
<style>
    body {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
    }
    
    .card {
        backdrop-filter: blur(10px);
        background-color: rgba(255, 255, 255, 0.95);
    }
    
    .form-control:focus {
        border-color: var(--court-secondary);
        box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25);
    }
    
    .btn-primary {
        background: linear-gradient(135deg, var(--court-primary), var(--court-secondary));
        border: none;
        transition: all 0.3s ease;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 15px rgba(0,0,0,0.2);
    }
    
    .card-header {
        background: linear-gradient(135deg, var(--court-primary), var(--court-secondary));
    }
    
    @media (max-width: 576px) {
        .card {
            margin: 1rem;
        }
    }
</style>

<script>
    // Toggle password visibility
    function togglePassword(inputId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(inputId + '-icon');
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'bi bi-eye-slash';
        } else {
            input.type = 'password';
            icon.className = 'bi bi-eye';
        }
    }
    
    // Enhanced form validation
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        
        // Basic validation
        if (!email || !password) {
            e.preventDefault();
            showToast('Please fill in all required fields', 'error');
            return false;
        }
        
        // Email validation
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            e.preventDefault();
            document.getElementById('email').classList.add('is-invalid');
            showToast('Please enter a valid email address', 'error');
            return false;
        }
        
        // Password length validation
        if (password.length < 6) {
            e.preventDefault();
            document.getElementById('password').classList.add('is-invalid');
            showToast('Password must be at least 6 characters long', 'error');
            return false;
        }
        
        // Show loading state
        showLoading('loginButton');
        return true;
    });
    
    // Remove validation errors on input
    document.getElementById('email').addEventListener('input', function() {
        this.classList.remove('is-invalid');
    });
    
    document.getElementById('password').addEventListener('input', function() {
        this.classList.remove('is-invalid');
    });
    
    // Auto-focus on email field
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('email').focus();
    });
    
    // Handle Enter key in form
    document.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            document.getElementById('loginForm').submit();
        }
    });
</script>

<?php include 'views/layouts/footer.php'; ?>