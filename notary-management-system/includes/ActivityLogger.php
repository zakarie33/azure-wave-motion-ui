<?php
class ActivityLogger {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    public function log($userId, $action, $tableName = null, $recordId = null, $oldValues = null, $newValues = null) {
        try {
            $data = [
                'user_id' => $userId,
                'action' => $action,
                'table_name' => $tableName,
                'record_id' => $recordId,
                'old_values' => $oldValues ? json_encode($oldValues) : null,
                'new_values' => $newValues ? json_encode($newValues) : null,
                'ip_address' => $this->getClientIP(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            return $this->db->insert('activity_logs', $data);
        } catch (Exception $e) {
            error_log("Activity logging failed: " . $e->getMessage());
            return false;
        }
    }
    
    public function getRecentActivity($limit = 50, $userId = null) {
        $sql = "SELECT al.*, u.username, u.first_name, u.last_name 
                FROM activity_logs al 
                LEFT JOIN users u ON al.user_id = u.id";
        $params = [];
        
        if ($userId) {
            $sql .= " WHERE al.user_id = :user_id";
            $params['user_id'] = $userId;
        }
        
        $sql .= " ORDER BY al.created_at DESC LIMIT :limit";
        $params['limit'] = $limit;
        
        return $this->db->fetchAll($sql, $params);
    }
    
    public function getActivityByDateRange($startDate, $endDate, $userId = null) {
        $sql = "SELECT al.*, u.username, u.first_name, u.last_name 
                FROM activity_logs al 
                LEFT JOIN users u ON al.user_id = u.id 
                WHERE al.created_at BETWEEN :start_date AND :end_date";
        $params = [
            'start_date' => $startDate,
            'end_date' => $endDate
        ];
        
        if ($userId) {
            $sql .= " AND al.user_id = :user_id";
            $params['user_id'] = $userId;
        }
        
        $sql .= " ORDER BY al.created_at DESC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    public function getActionCount($action, $userId = null, $days = 30) {
        $sql = "SELECT COUNT(*) as count 
                FROM activity_logs 
                WHERE action = :action 
                AND created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)";
        $params = ['action' => $action, 'days' => $days];
        
        if ($userId) {
            $sql .= " AND user_id = :user_id";
            $params['user_id'] = $userId;
        }
        
        $result = $this->db->fetchOne($sql, $params);
        return $result['count'] ?? 0;
    }
    
    public function getUserActivity($userId, $limit = 20) {
        $sql = "SELECT * FROM activity_logs 
                WHERE user_id = :user_id 
                ORDER BY created_at DESC 
                LIMIT :limit";
        
        return $this->db->fetchAll($sql, ['user_id' => $userId, 'limit' => $limit]);
    }
    
    public function getSystemStats() {
        $stats = [];
        
        // Total activities today
        $stats['today'] = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM activity_logs WHERE DATE(created_at) = CURDATE()"
        )['count'];
        
        // Total activities this week
        $stats['week'] = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM activity_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
        )['count'];
        
        // Total activities this month
        $stats['month'] = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM activity_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
        )['count'];
        
        // Most active users
        $stats['active_users'] = $this->db->fetchAll(
            "SELECT u.username, u.first_name, u.last_name, COUNT(al.id) as activity_count 
             FROM activity_logs al 
             JOIN users u ON al.user_id = u.id 
             WHERE al.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
             GROUP BY al.user_id 
             ORDER BY activity_count DESC 
             LIMIT 5"
        );
        
        // Most common actions
        $stats['common_actions'] = $this->db->fetchAll(
            "SELECT action, COUNT(*) as count 
             FROM activity_logs 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
             GROUP BY action 
             ORDER BY count DESC 
             LIMIT 10"
        );
        
        return $stats;
    }
    
    public function cleanOldLogs($days = 90) {
        $sql = "DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)";
        return $this->db->query($sql, ['days' => $days])->rowCount();
    }
    
    private function getClientIP() {
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, 
                        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}
?>