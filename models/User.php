<?php
/**
 * User Model
 * Handles user authentication and management
 */

class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $first_name;
    public $last_name;
    public $email;
    public $password_hash;
    public $role;
    public $department;
    public $phone;
    public $is_active;
    public $last_login;
    public $failed_login_attempts;
    public $lockout_until;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Authenticate user login
     */
    public function login($email, $password) {
        // Check if user is locked out
        if ($this->isLockedOut($email)) {
            return ['success' => false, 'message' => 'Account is temporarily locked due to multiple failed attempts'];
        }

        $query = "SELECT id, first_name, last_name, email, password_hash, role, department, is_active, failed_login_attempts 
                  FROM " . $this->table_name . " 
                  WHERE email = :email AND is_active = 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() == 1) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (password_verify($password, $row['password_hash'])) {
                // Reset failed attempts on successful login
                $this->resetFailedAttempts($email);
                $this->updateLastLogin($row['id']);
                
                // Set session variables
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['user_name'] = $row['first_name'] . ' ' . $row['last_name'];
                $_SESSION['user_email'] = $row['email'];
                $_SESSION['user_role'] = $row['role'];
                $_SESSION['user_department'] = $row['department'];
                $_SESSION['login_time'] = time();
                
                // Log successful login
                $this->logAudit($row['id'], 'LOGIN_SUCCESS', 'users', $row['id']);
                
                return ['success' => true, 'user' => $row];
            } else {
                // Increment failed attempts
                $this->incrementFailedAttempts($email);
                $this->logAudit(null, 'LOGIN_FAILED', 'users', null, ['email' => $email]);
                return ['success' => false, 'message' => 'Invalid email or password'];
            }
        } else {
            $this->logAudit(null, 'LOGIN_FAILED', 'users', null, ['email' => $email]);
            return ['success' => false, 'message' => 'Invalid email or password'];
        }
    }

    /**
     * Create new user
     */
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (first_name, last_name, email, password_hash, role, department, phone) 
                  VALUES (:first_name, :last_name, :email, :password_hash, :role, :department, :phone)";
        
        $stmt = $this->conn->prepare($query);
        
        // Hash password
        $hashed_password = password_hash($this->password_hash, PASSWORD_DEFAULT);
        
        $stmt->bindParam(':first_name', $this->first_name);
        $stmt->bindParam(':last_name', $this->last_name);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password_hash', $hashed_password);
        $stmt->bindParam(':role', $this->role);
        $stmt->bindParam(':department', $this->department);
        $stmt->bindParam(':phone', $this->phone);
        
        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            $this->logAudit($_SESSION['user_id'] ?? null, 'USER_CREATED', 'users', $this->id);
            return true;
        }
        return false;
    }

    /**
     * Update user profile
     */
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET first_name = :first_name, last_name = :last_name, 
                      email = :email, department = :department, phone = :phone 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':first_name', $this->first_name);
        $stmt->bindParam(':last_name', $this->last_name);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':department', $this->department);
        $stmt->bindParam(':phone', $this->phone);
        $stmt->bindParam(':id', $this->id);
        
        if ($stmt->execute()) {
            $this->logAudit($_SESSION['user_id'], 'USER_UPDATED', 'users', $this->id);
            return true;
        }
        return false;
    }

    /**
     * Change password
     */
    public function changePassword($current_password, $new_password) {
        // Verify current password
        $query = "SELECT password_hash FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();
        
        if ($stmt->rowCount() == 1) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (password_verify($current_password, $row['password_hash'])) {
                // Update password
                $query = "UPDATE " . $this->table_name . " SET password_hash = :password_hash WHERE id = :id";
                $stmt = $this->conn->prepare($query);
                
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt->bindParam(':password_hash', $hashed_password);
                $stmt->bindParam(':id', $this->id);
                
                if ($stmt->execute()) {
                    $this->logAudit($this->id, 'PASSWORD_CHANGED', 'users', $this->id);
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Get all users with pagination
     */
    public function getAll($page = 1, $limit = 20, $role = null) {
        $offset = ($page - 1) * $limit;
        
        $where_clause = "";
        if ($role) {
            $where_clause = "WHERE role = :role";
        }
        
        $query = "SELECT id, first_name, last_name, email, role, department, phone, is_active, last_login, created_at 
                  FROM " . $this->table_name . " 
                  $where_clause 
                  ORDER BY created_at DESC 
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        
        if ($role) {
            $stmt->bindParam(':role', $role);
        }
        
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get user by ID
     */
    public function getById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        if ($stmt->rowCount() == 1) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->first_name = $row['first_name'];
            $this->last_name = $row['last_name'];
            $this->email = $row['email'];
            $this->role = $row['role'];
            $this->department = $row['department'];
            $this->phone = $row['phone'];
            $this->is_active = $row['is_active'];
            return true;
        }
        return false;
    }

    /**
     * Get judges for assignment
     */
    public function getJudges() {
        $query = "SELECT id, CONCAT(first_name, ' ', last_name) as name 
                  FROM " . $this->table_name . " 
                  WHERE role = 'judge' AND is_active = 1 
                  ORDER BY first_name, last_name";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Check if user is locked out
     */
    private function isLockedOut($email) {
        $query = "SELECT lockout_until FROM " . $this->table_name . " WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() == 1) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row['lockout_until'] && strtotime($row['lockout_until']) > time()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Increment failed login attempts
     */
    private function incrementFailedAttempts($email) {
        $query = "UPDATE " . $this->table_name . " 
                  SET failed_login_attempts = failed_login_attempts + 1,
                      lockout_until = CASE 
                          WHEN failed_login_attempts + 1 >= :max_attempts 
                          THEN DATE_ADD(NOW(), INTERVAL :lockout_time SECOND)
                          ELSE lockout_until 
                      END
                  WHERE email = :email";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':max_attempts', MAX_LOGIN_ATTEMPTS);
        $stmt->bindParam(':lockout_time', LOGIN_LOCKOUT_TIME);
        $stmt->execute();
    }

    /**
     * Reset failed login attempts
     */
    private function resetFailedAttempts($email) {
        $query = "UPDATE " . $this->table_name . " 
                  SET failed_login_attempts = 0, lockout_until = NULL 
                  WHERE email = :email";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
    }

    /**
     * Update last login timestamp
     */
    private function updateLastLogin($user_id) {
        $query = "UPDATE " . $this->table_name . " SET last_login = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $user_id);
        $stmt->execute();
    }

    /**
     * Log audit trail
     */
    private function logAudit($user_id, $action, $table_name, $record_id, $data = null) {
        $query = "INSERT INTO audit_logs (user_id, action, table_name, record_id, new_values, ip_address, user_agent) 
                  VALUES (:user_id, :action, :table_name, :record_id, :new_values, :ip_address, :user_agent)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':action', $action);
        $stmt->bindParam(':table_name', $table_name);
        $stmt->bindParam(':record_id', $record_id);
        $stmt->bindParam(':new_values', json_encode($data));
        $stmt->bindParam(':ip_address', $_SERVER['REMOTE_ADDR'] ?? '');
        $stmt->bindParam(':user_agent', $_SERVER['HTTP_USER_AGENT'] ?? '');
        $stmt->execute();
    }

    /**
     * Logout user
     */
    public function logout() {
        $this->logAudit($_SESSION['user_id'] ?? null, 'LOGOUT', 'users', $_SESSION['user_id'] ?? null);
        session_destroy();
    }
}
?>