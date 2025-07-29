<?php
require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../ActivityLogger.php';

class User {
    private $db;
    private $logger;
    
    public function __construct() {
        $this->db = new Database();
        $this->db->connect();
        $this->logger = new ActivityLogger($this->db);
    }
    
    public function getAllUsers($role = null, $status = 'active') {
        $sql = "SELECT u.*, 
                       CASE 
                           WHEN u.role = 'notary' THEN n.license_number
                           WHEN u.role = 'client' THEN c.address
                           ELSE NULL 
                       END as additional_info
                FROM users u
                LEFT JOIN notaries n ON u.id = n.user_id AND u.role = 'notary'
                LEFT JOIN clients c ON u.id = c.user_id AND u.role = 'client'
                WHERE 1=1";
        
        $params = [];
        
        if ($role) {
            $sql .= " AND u.role = :role";
            $params['role'] = $role;
        }
        
        if ($status) {
            $sql .= " AND u.status = :status";
            $params['status'] = $status;
        }
        
        $sql .= " ORDER BY u.created_at DESC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    public function getUserById($id) {
        $sql = "SELECT u.*, 
                       CASE 
                           WHEN u.role = 'notary' THEN n.license_number
                           WHEN u.role = 'client' THEN c.address
                           ELSE NULL 
                       END as additional_info,
                       CASE 
                           WHEN u.role = 'notary' THEN n.license_expiry
                           ELSE NULL 
                       END as license_expiry
                FROM users u
                LEFT JOIN notaries n ON u.id = n.user_id AND u.role = 'notary'
                LEFT JOIN clients c ON u.id = c.user_id AND u.role = 'client'
                WHERE u.id = :id";
        
        return $this->db->fetchOne($sql, ['id' => $id]);
    }
    
    public function createUser($userData) {
        try {
            $this->db->beginTransaction();
            
            // Validate required fields
            $required = ['username', 'email', 'password', 'first_name', 'last_name', 'role'];
            foreach ($required as $field) {
                if (empty($userData[$field])) {
                    throw new Exception("$field is required");
                }
            }
            
            // Check if username or email already exists
            if ($this->usernameExists($userData['username'])) {
                throw new Exception("Username already exists");
            }
            
            if ($this->emailExists($userData['email'])) {
                throw new Exception("Email already exists");
            }
            
            // Hash password
            $userData['password'] = password_hash($userData['password'], PASSWORD_DEFAULT);
            
            // Insert user
            $userId = $this->db->insert('users', [
                'username' => $userData['username'],
                'email' => $userData['email'],
                'password' => $userData['password'],
                'first_name' => $userData['first_name'],
                'last_name' => $userData['last_name'],
                'phone' => $userData['phone'] ?? null,
                'role' => $userData['role'],
                'status' => $userData['status'] ?? 'active'
            ]);
            
            // Create role-specific record
            if ($userData['role'] === 'notary') {
                $this->createNotaryRecord($userId, $userData);
            } elseif ($userData['role'] === 'client') {
                $this->createClientRecord($userId, $userData);
            }
            
            $this->db->commit();
            
            // Log activity
            $this->logger->log($_SESSION['user_id'] ?? null, 'user_created', 'users', $userId, null, $userData);
            
            return ['success' => true, 'user_id' => $userId, 'message' => 'User created successfully'];
            
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function updateUser($id, $userData) {
        try {
            $this->db->beginTransaction();
            
            // Get original user data for logging
            $originalUser = $this->getUserById($id);
            if (!$originalUser) {
                throw new Exception("User not found");
            }
            
            // Check if username or email already exists (excluding current user)
            if (isset($userData['username']) && $userData['username'] !== $originalUser['username']) {
                if ($this->usernameExists($userData['username'], $id)) {
                    throw new Exception("Username already exists");
                }
            }
            
            if (isset($userData['email']) && $userData['email'] !== $originalUser['email']) {
                if ($this->emailExists($userData['email'], $id)) {
                    throw new Exception("Email already exists");
                }
            }
            
            // Hash password if provided
            if (!empty($userData['password'])) {
                $userData['password'] = password_hash($userData['password'], PASSWORD_DEFAULT);
            } else {
                unset($userData['password']);
            }
            
            // Update user
            $updated = $this->db->update('users', $userData, 'id = :id', ['id' => $id]);
            
            // Update role-specific record
            if ($originalUser['role'] === 'notary' && isset($userData['notary_data'])) {
                $this->updateNotaryRecord($id, $userData['notary_data']);
            } elseif ($originalUser['role'] === 'client' && isset($userData['client_data'])) {
                $this->updateClientRecord($id, $userData['client_data']);
            }
            
            $this->db->commit();
            
            // Log activity
            $this->logger->log($_SESSION['user_id'] ?? null, 'user_updated', 'users', $id, $originalUser, $userData);
            
            return ['success' => true, 'message' => 'User updated successfully'];
            
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function deleteUser($id) {
        try {
            $user = $this->getUserById($id);
            if (!$user) {
                throw new Exception("User not found");
            }
            
            // Don't allow deletion of admin users if it's the last one
            if ($user['role'] === 'admin') {
                $adminCount = $this->db->fetchOne("SELECT COUNT(*) as count FROM users WHERE role = 'admin' AND status = 'active'")['count'];
                if ($adminCount <= 1) {
                    throw new Exception("Cannot delete the last admin user");
                }
            }
            
            // Soft delete by updating status
            $updated = $this->db->update('users', ['status' => 'inactive'], 'id = :id', ['id' => $id]);
            
            // Log activity
            $this->logger->log($_SESSION['user_id'] ?? null, 'user_deleted', 'users', $id, $user);
            
            return ['success' => true, 'message' => 'User deleted successfully'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function searchUsers($query, $role = null) {
        $sql = "SELECT u.*, 
                       CASE 
                           WHEN u.role = 'notary' THEN n.license_number
                           WHEN u.role = 'client' THEN c.address
                           ELSE NULL 
                       END as additional_info
                FROM users u
                LEFT JOIN notaries n ON u.id = n.user_id AND u.role = 'notary'
                LEFT JOIN clients c ON u.id = c.user_id AND u.role = 'client'
                WHERE (u.first_name LIKE :query 
                       OR u.last_name LIKE :query 
                       OR u.username LIKE :query 
                       OR u.email LIKE :query)
                AND u.status = 'active'";
        
        $params = ['query' => "%$query%"];
        
        if ($role) {
            $sql .= " AND u.role = :role";
            $params['role'] = $role;
        }
        
        $sql .= " ORDER BY u.first_name, u.last_name";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    public function getUserStats() {
        $stats = [];
        
        // Total users by role
        $roleStats = $this->db->fetchAll(
            "SELECT role, COUNT(*) as count FROM users WHERE status = 'active' GROUP BY role"
        );
        
        foreach ($roleStats as $stat) {
            $stats[$stat['role']] = $stat['count'];
        }
        
        // Recent registrations
        $stats['recent'] = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
        )['count'];
        
        // Active sessions today
        $stats['active_today'] = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM users WHERE last_login >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
        )['count'];
        
        return $stats;
    }
    
    private function usernameExists($username, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM users WHERE username = :username";
        $params = ['username' => $username];
        
        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }
        
        return $this->db->fetchOne($sql, $params)['count'] > 0;
    }
    
    private function emailExists($email, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM users WHERE email = :email";
        $params = ['email' => $email];
        
        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }
        
        return $this->db->fetchOne($sql, $params)['count'] > 0;
    }
    
    private function createNotaryRecord($userId, $userData) {
        if (isset($userData['notary_data'])) {
            $notaryData = $userData['notary_data'];
            $notaryData['user_id'] = $userId;
            $this->db->insert('notaries', $notaryData);
        }
    }
    
    private function createClientRecord($userId, $userData) {
        if (isset($userData['client_data'])) {
            $clientData = $userData['client_data'];
            $clientData['user_id'] = $userId;
            $this->db->insert('clients', $clientData);
        }
    }
    
    private function updateNotaryRecord($userId, $notaryData) {
        $existing = $this->db->fetchOne("SELECT id FROM notaries WHERE user_id = :user_id", ['user_id' => $userId]);
        
        if ($existing) {
            $this->db->update('notaries', $notaryData, 'user_id = :user_id', ['user_id' => $userId]);
        } else {
            $notaryData['user_id'] = $userId;
            $this->db->insert('notaries', $notaryData);
        }
    }
    
    private function updateClientRecord($userId, $clientData) {
        $existing = $this->db->fetchOne("SELECT id FROM clients WHERE user_id = :user_id", ['user_id' => $userId]);
        
        if ($existing) {
            $this->db->update('clients', $clientData, 'user_id = :user_id', ['user_id' => $userId]);
        } else {
            $clientData['user_id'] = $userId;
            $this->db->insert('clients', $clientData);
        }
    }
}
?>