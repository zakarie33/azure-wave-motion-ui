<?php
require_once 'Database.php';
require_once 'ActivityLogger.php';

class Auth {
    private $db;
    private $logger;
    
    public function __construct() {
        $this->db = new Database();
        $this->db->connect();
        $this->logger = new ActivityLogger($this->db);
        $this->startSession();
    }
    
    private function startSession() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check session timeout
        if (isset($_SESSION['last_activity']) && 
            time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
            $this->logout();
        }
        $_SESSION['last_activity'] = time();
    }
    
    public function login($username, $password) {
        try {
            $sql = "SELECT * FROM users WHERE (username = :username OR email = :username) AND status = 'active'";
            $user = $this->db->fetchOne($sql, ['username' => $username]);
            
            if ($user && password_verify($password, $user['password'])) {
                // Update last login
                $this->db->update('users', 
                    ['last_login' => date('Y-m-d H:i:s')], 
                    'id = :id', 
                    ['id' => $user['id']]
                );
                
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['logged_in'] = true;
                
                // Log successful login
                $this->logger->log($user['id'], 'login', 'users', $user['id']);
                
                return [
                    'success' => true,
                    'user' => $user,
                    'redirect' => $this->getRedirectUrl($user['role'])
                ];
            } else {
                // Log failed login attempt
                if ($user) {
                    $this->logger->log($user['id'], 'login_failed', 'users', $user['id']);
                }
                
                return [
                    'success' => false,
                    'message' => 'Invalid username or password'
                ];
            }
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Login failed. Please try again.'
            ];
        }
    }
    
    public function logout() {
        if ($this->isLoggedIn()) {
            $this->logger->log($_SESSION['user_id'], 'logout', 'users', $_SESSION['user_id']);
        }
        
        session_unset();
        session_destroy();
        
        // Clear session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: login.php');
            exit();
        }
    }
    
    public function requireRole($allowedRoles) {
        $this->requireLogin();
        
        if (!is_array($allowedRoles)) {
            $allowedRoles = [$allowedRoles];
        }
        
        if (!in_array($_SESSION['role'], $allowedRoles)) {
            header('Location: unauthorized.php');
            exit();
        }
    }
    
    public function hasRole($role) {
        return $this->isLoggedIn() && $_SESSION['role'] === $role;
    }
    
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        $sql = "SELECT u.*, 
                       CASE 
                           WHEN u.role = 'notary' THEN n.license_number
                           WHEN u.role = 'client' THEN c.address
                           ELSE NULL 
                       END as additional_info
                FROM users u
                LEFT JOIN notaries n ON u.id = n.user_id
                LEFT JOIN clients c ON u.id = c.user_id
                WHERE u.id = :id";
        
        return $this->db->fetchOne($sql, ['id' => $_SESSION['user_id']]);
    }
    
    public function changePassword($userId, $oldPassword, $newPassword) {
        // Validate password strength
        if (!$this->validatePassword($newPassword)) {
            return [
                'success' => false,
                'message' => 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long'
            ];
        }
        
        // Verify old password
        $user = $this->db->fetchOne("SELECT password FROM users WHERE id = :id", ['id' => $userId]);
        
        if (!$user || !password_verify($oldPassword, $user['password'])) {
            return [
                'success' => false,
                'message' => 'Current password is incorrect'
            ];
        }
        
        // Update password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $updated = $this->db->update('users', 
            ['password' => $hashedPassword], 
            'id = :id', 
            ['id' => $userId]
        );
        
        if ($updated) {
            $this->logger->log($userId, 'password_changed', 'users', $userId);
            return ['success' => true, 'message' => 'Password updated successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to update password'];
        }
    }
    
    public function resetPassword($email) {
        $user = $this->db->fetchOne("SELECT * FROM users WHERE email = :email", ['email' => $email]);
        
        if (!$user) {
            return ['success' => false, 'message' => 'Email not found'];
        }
        
        // Generate temporary password
        $tempPassword = $this->generateTempPassword();
        $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);
        
        // Update password
        $updated = $this->db->update('users', 
            ['password' => $hashedPassword], 
            'id = :id', 
            ['id' => $user['id']]
        );
        
        if ($updated) {
            $this->logger->log($user['id'], 'password_reset', 'users', $user['id']);
            
            // TODO: Send email with temporary password
            // For now, return the temporary password (remove in production)
            return [
                'success' => true, 
                'message' => 'Password reset successful',
                'temp_password' => $tempPassword // Remove in production
            ];
        } else {
            return ['success' => false, 'message' => 'Failed to reset password'];
        }
    }
    
    private function getRedirectUrl($role) {
        switch ($role) {
            case 'admin':
                return 'admin/dashboard.php';
            case 'notary':
                return 'notary/dashboard.php';
            case 'client':
                return 'client/dashboard.php';
            default:
                return 'dashboard.php';
        }
    }
    
    private function validatePassword($password) {
        return strlen($password) >= PASSWORD_MIN_LENGTH;
    }
    
    private function generateTempPassword($length = 12) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        return substr(str_shuffle($chars), 0, $length);
    }
    
    public function getSessionData() {
        return [
            'user_id' => $_SESSION['user_id'] ?? null,
            'username' => $_SESSION['username'] ?? null,
            'role' => $_SESSION['role'] ?? null,
            'first_name' => $_SESSION['first_name'] ?? null,
            'last_name' => $_SESSION['last_name'] ?? null,
            'email' => $_SESSION['email'] ?? null,
            'logged_in' => $_SESSION['logged_in'] ?? false
        ];
    }
}
?>