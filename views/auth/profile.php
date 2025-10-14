<?php
require_once 'config/config.php';
include 'views/layouts/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>
        <i class="bi bi-person-circle"></i>
        My Profile
    </h2>
    <a href="dashboard.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to Dashboard
    </a>
</div>

<div class="row">
    <!-- Profile Information -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-person"></i>
                    Profile Information
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="profile.php" id="profileForm">
                    <input type="hidden" name="action" value="update_profile">
                    <input type="hidden" name="csrf_token" value="<?php echo AuthMiddleware::generateCSRF(); ?>">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control" 
                                   id="first_name" 
                                   name="first_name" 
                                   value="<?php echo htmlspecialchars($this->user->first_name); ?>" 
                                   required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control" 
                                   id="last_name" 
                                   name="last_name" 
                                   value="<?php echo htmlspecialchars($this->user->last_name); ?>" 
                                   required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                        <input type="email" 
                               class="form-control" 
                               id="email" 
                               name="email" 
                               value="<?php echo htmlspecialchars($this->user->email); ?>" 
                               required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="department" class="form-label">Department</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="department" 
                                   name="department" 
                                   value="<?php echo htmlspecialchars($this->user->department ?? ''); ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" 
                                   class="form-control" 
                                   id="phone" 
                                   name="phone" 
                                   value="<?php echo htmlspecialchars($this->user->phone ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Update Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Change Password -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-key"></i>
                    Change Password
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="profile.php" id="passwordForm">
                    <input type="hidden" name="action" value="change_password">
                    <input type="hidden" name="csrf_token" value="<?php echo AuthMiddleware::generateCSRF(); ?>">
                    
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" 
                                   class="form-control" 
                                   id="current_password" 
                                   name="current_password" 
                                   required>
                            <button class="btn btn-outline-secondary" 
                                    type="button" 
                                    onclick="togglePassword('current_password')">
                                <i class="bi bi-eye" id="current_password-icon"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" 
                                   class="form-control" 
                                   id="new_password" 
                                   name="new_password" 
                                   minlength="8" 
                                   required>
                            <button class="btn btn-outline-secondary" 
                                    type="button" 
                                    onclick="togglePassword('new_password')">
                                <i class="bi bi-eye" id="new_password-icon"></i>
                            </button>
                        </div>
                        <div class="form-text">Password must be at least 8 characters long</div>
                        <div class="progress mt-2" style="height: 5px;">
                            <div class="progress-bar" id="password-strength" role="progressbar" style="width: 0%"></div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" 
                                   class="form-control" 
                                   id="confirm_password" 
                                   name="confirm_password" 
                                   required>
                            <button class="btn btn-outline-secondary" 
                                    type="button" 
                                    onclick="togglePassword('confirm_password')">
                                <i class="bi bi-eye" id="confirm_password-icon"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback" id="password-mismatch">
                            Passwords do not match
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-warning" id="changePasswordBtn">
                            <i class="bi bi-key"></i> Change Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Profile Summary -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-info-circle"></i>
                    Account Information
                </h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <strong>Role:</strong>
                    <span class="badge bg-primary ms-2">
                        <?php echo ucfirst($_SESSION['user_role']); ?>
                    </span>
                </div>
                
                <div class="mb-3">
                    <strong>Account Status:</strong>
                    <span class="badge bg-success ms-2">Active</span>
                </div>
                
                <div class="mb-3">
                    <strong>Last Login:</strong><br>
                    <small class="text-muted">
                        <?php 
                        if ($this->user->last_login) {
                            echo date('M j, Y g:i A', strtotime($this->user->last_login));
                        } else {
                            echo 'Never';
                        }
                        ?>
                    </small>
                </div>
                
                <div class="mb-3">
                    <strong>Member Since:</strong><br>
                    <small class="text-muted">
                        <?php echo date('M j, Y', strtotime($this->user->created_at)); ?>
                    </small>
                </div>
                
                <hr>
                
                <h6>Security Tips:</h6>
                <ul class="small">
                    <li>Use a strong, unique password</li>
                    <li>Log out when finished</li>
                    <li>Don't share your credentials</li>
                    <li>Report suspicious activity</li>
                </ul>
            </div>
        </div>
        
        <!-- Activity Summary -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-activity"></i>
                    Recent Activity
                </h6>
            </div>
            <div class="card-body">
                <?php
                // Get recent user activity
                $activity_query = "SELECT action, created_at 
                                   FROM audit_logs 
                                   WHERE user_id = :user_id 
                                   ORDER BY created_at DESC 
                                   LIMIT 5";
                $stmt = $conn->prepare($activity_query);
                $stmt->bindParam(':user_id', $_SESSION['user_id']);
                $stmt->execute();
                $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>
                
                <?php if (!empty($activities)): ?>
                    <?php foreach ($activities as $activity): ?>
                    <div class="mb-2">
                        <small class="text-muted">
                            <?php echo date('M j, g:i A', strtotime($activity['created_at'])); ?>
                        </small><br>
                        <small><?php echo htmlspecialchars($activity['action']); ?></small>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                <p class="text-muted small">No recent activity</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

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

// Password strength checker
document.getElementById('new_password').addEventListener('input', function() {
    const password = this.value;
    const strengthBar = document.getElementById('password-strength');
    let strength = 0;
    
    // Length check
    if (password.length >= 8) strength += 25;
    
    // Uppercase check
    if (/[A-Z]/.test(password)) strength += 25;
    
    // Lowercase check
    if (/[a-z]/.test(password)) strength += 25;
    
    // Number or special character check
    if (/[\d\W]/.test(password)) strength += 25;
    
    // Update progress bar
    strengthBar.style.width = strength + '%';
    
    if (strength < 50) {
        strengthBar.className = 'progress-bar bg-danger';
    } else if (strength < 75) {
        strengthBar.className = 'progress-bar bg-warning';
    } else {
        strengthBar.className = 'progress-bar bg-success';
    }
});

// Password confirmation check
document.getElementById('confirm_password').addEventListener('input', function() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = this.value;
    const mismatchFeedback = document.getElementById('password-mismatch');
    
    if (confirmPassword && newPassword !== confirmPassword) {
        this.classList.add('is-invalid');
        mismatchFeedback.style.display = 'block';
    } else {
        this.classList.remove('is-invalid');
        mismatchFeedback.style.display = 'none';
    }
});

// Form validation
document.getElementById('passwordForm').addEventListener('submit', function(e) {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (newPassword !== confirmPassword) {
        e.preventDefault();
        showToast('Passwords do not match', 'error');
        return false;
    }
    
    if (newPassword.length < 8) {
        e.preventDefault();
        showToast('Password must be at least 8 characters long', 'error');
        return false;
    }
    
    // Show loading state
    const btn = document.getElementById('changePasswordBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="loading-spinner"></span> Changing...';
});

// Profile form validation
document.getElementById('profileForm').addEventListener('submit', function(e) {
    const email = document.getElementById('email').value;
    
    if (!email || !email.includes('@')) {
        e.preventDefault();
        showToast('Please enter a valid email address', 'error');
        return false;
    }
});
</script>

<?php include 'views/layouts/footer.php'; ?>