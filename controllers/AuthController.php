<?php
/**
 * Authentication Controller
 * Handles login, logout, and user registration
 */

class AuthController {
    private $db;
    private $user;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->user = new User($this->db);
    }

    /**
     * Show login form
     */
    public function showLogin() {
        if (is_logged_in()) {
            $this->redirectToDashboard();
        }
        
        $page_title = "Login";
        include 'views/auth/login.php';
    }

    /**
     * Process login
     */
    public function processLogin() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('login.php');
        }

        $email = sanitize_input($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember_me = isset($_POST['remember_me']);

        // Validate input
        if (empty($email) || empty($password)) {
            flash_message('error', 'Please fill in all required fields');
            redirect('login.php');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash_message('error', 'Please enter a valid email address');
            redirect('login.php');
        }

        // Attempt login
        $result = $this->user->login($email, $password);

        if ($result['success']) {
            // Set remember me cookie if requested
            if ($remember_me) {
                $token = bin2hex(random_bytes(32));
                setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', true, true); // 30 days
                // Store token in database (implement remember_tokens table if needed)
            }

            flash_message('success', 'Welcome back, ' . $_SESSION['user_name']);
            $this->redirectToDashboard();
        } else {
            flash_message('error', $result['message']);
            redirect('login.php');
        }
    }

    /**
     * Process logout
     */
    public function logout() {
        if (is_logged_in()) {
            $this->user->logout();
        }
        
        // Clear remember me cookie
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/', '', true, true);
        }
        
        flash_message('success', 'You have been logged out successfully');
        redirect('login.php');
    }

    /**
     * Show user registration form (Admin only)
     */
    public function showRegister() {
        if (!is_logged_in() || !check_permission('admin')) {
            flash_message('error', 'Access denied');
            redirect('login.php');
        }

        $page_title = "Add New User";
        include 'views/auth/register.php';
    }

    /**
     * Process user registration
     */
    public function processRegister() {
        if (!is_logged_in() || !check_permission('admin')) {
            flash_message('error', 'Access denied');
            redirect('login.php');
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('register.php');
        }

        // Sanitize and validate input
        $first_name = sanitize_input($_POST['first_name'] ?? '');
        $last_name = sanitize_input($_POST['last_name'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $role = sanitize_input($_POST['role'] ?? '');
        $department = sanitize_input($_POST['department'] ?? '');
        $phone = sanitize_input($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $send_welcome_email = isset($_POST['send_welcome_email']);

        // Validation
        $errors = [];

        if (empty($first_name)) $errors[] = "First name is required";
        if (empty($last_name)) $errors[] = "Last name is required";
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Valid email is required";
        }
        if (empty($role) || !in_array($role, ['admin', 'manager', 'judge', 'clerk', 'prosecutor'])) {
            $errors[] = "Valid role is required";
        }
        if (empty($password) || strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters long";
        }

        if (!empty($errors)) {
            flash_message('error', implode('<br>', $errors));
            redirect('register.php');
        }

        // Create user
        $this->user->first_name = $first_name;
        $this->user->last_name = $last_name;
        $this->user->email = $email;
        $this->user->password_hash = $password; // Will be hashed in the model
        $this->user->role = $role;
        $this->user->department = $department;
        $this->user->phone = $phone;

        if ($this->user->create()) {
            // Send welcome email if requested
            if ($send_welcome_email) {
                $this->sendWelcomeEmail($email, $first_name . ' ' . $last_name, $password);
            }

            flash_message('success', 'User created successfully');
            redirect('users.php');
        } else {
            flash_message('error', 'Failed to create user. Email may already exist.');
            redirect('register.php');
        }
    }

    /**
     * Show profile edit form
     */
    public function showProfile() {
        if (!is_logged_in()) {
            redirect('login.php');
        }

        $this->user->getById($_SESSION['user_id']);
        $page_title = "My Profile";
        include 'views/auth/profile.php';
    }

    /**
     * Process profile update
     */
    public function updateProfile() {
        if (!is_logged_in()) {
            redirect('login.php');
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('profile.php');
        }

        $first_name = sanitize_input($_POST['first_name'] ?? '');
        $last_name = sanitize_input($_POST['last_name'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $department = sanitize_input($_POST['department'] ?? '');
        $phone = sanitize_input($_POST['phone'] ?? '');

        // Validation
        if (empty($first_name) || empty($last_name) || empty($email)) {
            flash_message('error', 'Please fill in all required fields');
            redirect('profile.php');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash_message('error', 'Please enter a valid email address');
            redirect('profile.php');
        }

        // Update profile
        $this->user->id = $_SESSION['user_id'];
        $this->user->first_name = $first_name;
        $this->user->last_name = $last_name;
        $this->user->email = $email;
        $this->user->department = $department;
        $this->user->phone = $phone;

        if ($this->user->update()) {
            // Update session variables
            $_SESSION['user_name'] = $first_name . ' ' . $last_name;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_department'] = $department;

            flash_message('success', 'Profile updated successfully');
        } else {
            flash_message('error', 'Failed to update profile');
        }

        redirect('profile.php');
    }

    /**
     * Change password
     */
    public function changePassword() {
        if (!is_logged_in()) {
            redirect('login.php');
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('profile.php');
        }

        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Validation
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            flash_message('error', 'Please fill in all password fields');
            redirect('profile.php');
        }

        if ($new_password !== $confirm_password) {
            flash_message('error', 'New passwords do not match');
            redirect('profile.php');
        }

        if (strlen($new_password) < 8) {
            flash_message('error', 'New password must be at least 8 characters long');
            redirect('profile.php');
        }

        // Change password
        $this->user->id = $_SESSION['user_id'];
        if ($this->user->changePassword($current_password, $new_password)) {
            flash_message('success', 'Password changed successfully');
        } else {
            flash_message('error', 'Current password is incorrect');
        }

        redirect('profile.php');
    }

    /**
     * Redirect to appropriate dashboard based on user role
     */
    private function redirectToDashboard() {
        switch ($_SESSION['user_role']) {
            case 'admin':
            case 'manager':
                redirect('dashboard.php');
                break;
            case 'judge':
                redirect('judge-dashboard.php');
                break;
            case 'clerk':
                redirect('clerk-dashboard.php');
                break;
            case 'prosecutor':
                redirect('prosecutor-dashboard.php');
                break;
            default:
                redirect('dashboard.php');
        }
    }

    /**
     * Send welcome email to new user
     */
    private function sendWelcomeEmail($email, $name, $password) {
        // This is a placeholder for email functionality
        // In production, use a proper email library like PHPMailer or SwiftMailer
        
        $subject = "Welcome to " . APP_NAME;
        $message = "Dear $name,\n\n";
        $message .= "Your account has been created successfully.\n";
        $message .= "Email: $email\n";
        $message .= "Temporary Password: $password\n\n";
        $message .= "Please log in and change your password immediately.\n\n";
        $message .= "Best regards,\n" . APP_NAME . " Team";
        
        $headers = "From: " . FROM_EMAIL . "\r\n";
        $headers .= "Reply-To: " . FROM_EMAIL . "\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();
        
        // mail($email, $subject, $message, $headers);
        
        // For development, log the email instead of sending
        error_log("Welcome email would be sent to: $email with password: $password");
    }
}
?>