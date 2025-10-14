<?php
/**
 * Authentication Middleware
 * Handles session validation and access control
 */

class AuthMiddleware {
    
    /**
     * Check if user is authenticated
     */
    public static function requireAuth() {
        if (!is_logged_in()) {
            flash_message('error', 'Please log in to access this page');
            redirect('login.php');
        }
        
        // Check session timeout
        if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > SESSION_TIMEOUT) {
            session_destroy();
            flash_message('error', 'Your session has expired. Please log in again.');
            redirect('login.php');
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
    }
    
    /**
     * Check if user has required role/permission
     */
    public static function requireRole($required_role) {
        self::requireAuth();
        
        if (!check_permission($required_role)) {
            flash_message('error', 'You do not have permission to access this page');
            redirect('dashboard.php');
        }
    }
    
    /**
     * Check if user can access case (considering confidential flag)
     */
    public static function canAccessCase($case_id, $user_id = null, $user_role = null) {
        if (!$user_id) $user_id = $_SESSION['user_id'];
        if (!$user_role) $user_role = $_SESSION['user_role'];
        
        $database = new Database();
        $conn = $database->getConnection();
        
        $query = "SELECT confidential, assigned_judge_id, created_by FROM cases WHERE id = :case_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':case_id', $case_id);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            return false; // Case doesn't exist
        }
        
        $case = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Admin and manager can access all cases
        if (in_array($user_role, ['admin', 'manager'])) {
            return true;
        }
        
        // If case is not confidential, all authenticated users can access
        if (!$case['confidential']) {
            return true;
        }
        
        // For confidential cases, check specific permissions
        if ($case['assigned_judge_id'] == $user_id) {
            return true; // Assigned judge
        }
        
        if ($case['created_by'] == $user_id) {
            return true; // Case creator
        }
        
        // Check if user is involved in the case (prosecutor, clerk who uploaded documents, etc.)
        $query = "SELECT COUNT(*) as count FROM documents WHERE case_id = :case_id AND uploaded_by = :user_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':case_id', $case_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            return true; // User has uploaded documents to this case
        }
        
        return false;
    }
    
    /**
     * Check if user can access document
     */
    public static function canAccessDocument($document_id, $user_id = null, $user_role = null) {
        if (!$user_id) $user_id = $_SESSION['user_id'];
        if (!$user_role) $user_role = $_SESSION['user_role'];
        
        $database = new Database();
        $conn = $database->getConnection();
        
        $query = "SELECT d.visibility, d.case_id, d.uploaded_by, c.assigned_judge_id, c.confidential 
                  FROM documents d 
                  JOIN cases c ON d.case_id = c.id 
                  WHERE d.id = :document_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':document_id', $document_id);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            return false; // Document doesn't exist
        }
        
        $doc = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Admin and manager can access all documents
        if (in_array($user_role, ['admin', 'manager'])) {
            return true;
        }
        
        // Check case access first
        if (!self::canAccessCase($doc['case_id'], $user_id, $user_role)) {
            return false;
        }
        
        // Check document visibility
        switch ($doc['visibility']) {
            case 'public':
                return true;
                
            case 'case_staff':
                // Users involved in the case can access
                return true; // Already checked case access above
                
            case 'judge_only':
                // Only assigned judge can access
                return ($doc['assigned_judge_id'] == $user_id) || ($user_role == 'admin');
                
            default:
                return false;
        }
    }
    
    /**
     * Log user activity for audit trail
     */
    public static function logActivity($action, $table_name = null, $record_id = null, $old_values = null, $new_values = null) {
        if (!is_logged_in()) return;
        
        $database = new Database();
        $conn = $database->getConnection();
        
        $query = "INSERT INTO audit_logs (user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent) 
                  VALUES (:user_id, :action, :table_name, :record_id, :old_values, :new_values, :ip_address, :user_agent)";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->bindParam(':action', $action);
        $stmt->bindParam(':table_name', $table_name);
        $stmt->bindParam(':record_id', $record_id);
        $stmt->bindParam(':old_values', json_encode($old_values));
        $stmt->bindParam(':new_values', json_encode($new_values));
        $stmt->bindParam(':ip_address', $_SERVER['REMOTE_ADDR'] ?? '');
        $stmt->bindParam(':user_agent', $_SERVER['HTTP_USER_AGENT'] ?? '');
        $stmt->execute();
    }
    
    /**
     * Check CSRF token
     */
    public static function validateCSRF($token = null) {
        if (!$token) {
            $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
        }
        
        if (empty($token) || !isset($_SESSION['csrf_token'])) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Generate CSRF token
     */
    public static function generateCSRF() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Rate limiting for sensitive operations
     */
    public static function checkRateLimit($action, $limit = 5, $window = 300) { // 5 attempts per 5 minutes
        $key = $action . '_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . '_' . ($_SESSION['user_id'] ?? 'anonymous');
        
        if (!isset($_SESSION['rate_limits'])) {
            $_SESSION['rate_limits'] = [];
        }
        
        $now = time();
        
        // Clean old entries
        foreach ($_SESSION['rate_limits'] as $k => $v) {
            if ($v['expires'] < $now) {
                unset($_SESSION['rate_limits'][$k]);
            }
        }
        
        if (!isset($_SESSION['rate_limits'][$key])) {
            $_SESSION['rate_limits'][$key] = [
                'count' => 1,
                'expires' => $now + $window
            ];
            return true;
        }
        
        if ($_SESSION['rate_limits'][$key]['count'] >= $limit) {
            return false; // Rate limit exceeded
        }
        
        $_SESSION['rate_limits'][$key]['count']++;
        return true;
    }
}
?>