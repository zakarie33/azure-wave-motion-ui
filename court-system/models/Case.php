<?php
/**
 * Case Model
 * Handles all case-related database operations
 */

if (!defined('COURT_SYSTEM')) {
    define('COURT_SYSTEM', true);
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

class CaseModel {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Create a new case
     */
    public function create($data) {
        // Generate case number if not provided
        if (empty($data['case_no'])) {
            $data['case_no'] = generateCaseNumber();
        }
        
        // Validate required fields
        $required = ['case_no', 'title', 'description', 'case_type', 'filing_date', 'plaintiff', 'defendant'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Field '{$field}' is required");
            }
        }
        
        // Prepare data for insertion
        $insertData = [
            'case_no' => $data['case_no'],
            'title' => sanitize($data['title']),
            'description' => sanitize($data['description']),
            'case_type' => $data['case_type'],
            'filing_date' => $data['filing_date'],
            'status' => $data['status'] ?? 'Filed',
            'priority' => $data['priority'] ?? 'Normal',
            'assigned_judge_id' => $data['assigned_judge_id'] ?? null,
            'department_id' => $data['department_id'] ?? null,
            'plaintiff' => sanitize($data['plaintiff']),
            'defendant' => sanitize($data['defendant']),
            'confidential' => isset($data['confidential']) ? 1 : 0,
            'tags' => sanitize($data['tags'] ?? ''),
            'created_by' => $_SESSION['user_id']
        ];
        
        $caseId = $this->db->insert('cases', $insertData);
        
        // Add case participants if provided
        if (!empty($data['participants'])) {
            $this->addParticipants($caseId, $data['participants']);
        }
        
        // Send notification to assigned judge
        if (!empty($data['assigned_judge_id'])) {
            sendNotification(
                $data['assigned_judge_id'],
                'New Case Assigned',
                "You have been assigned to case {$data['case_no']}: {$data['title']}",
                'case_assignment',
                $caseId
            );
        }
        
        logAudit('CASE_CREATED', 'cases', $caseId, $caseId, null, $insertData);
        
        return $caseId;
    }
    
    /**
     * Update a case
     */
    public function update($caseId, $data) {
        // Get current case data
        $currentCase = $this->getById($caseId);
        if (!$currentCase) {
            throw new Exception('Case not found');
        }
        
        // Prepare update data
        $updateData = [];
        $oldValues = [];
        $newValues = [];
        
        $allowedFields = ['title', 'description', 'case_type', 'status', 'priority', 
                         'assigned_judge_id', 'department_id', 'plaintiff', 'defendant', 
                         'confidential', 'tags'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field]) && $data[$field] !== $currentCase[$field]) {
                $oldValues[$field] = $currentCase[$field];
                $newValues[$field] = is_string($data[$field]) ? sanitize($data[$field]) : $data[$field];
                $updateData[$field] = $newValues[$field];
            }
        }
        
        if (!empty($updateData)) {
            $this->db->update('cases', $updateData, 'id = ?', [$caseId]);
            
            // Send notification if judge assignment changed
            if (isset($newValues['assigned_judge_id']) && 
                $newValues['assigned_judge_id'] !== $oldValues['assigned_judge_id']) {
                
                if ($newValues['assigned_judge_id']) {
                    sendNotification(
                        $newValues['assigned_judge_id'],
                        'Case Assignment',
                        "You have been assigned to case {$currentCase['case_no']}: {$currentCase['title']}",
                        'case_assignment',
                        $caseId
                    );
                }
            }
            
            logAudit('CASE_UPDATED', 'cases', $caseId, $caseId, $oldValues, $newValues);
        }
        
        return true;
    }
    
    /**
     * Get case by ID
     */
    public function getById($caseId) {
        $sql = "
            SELECT c.*, 
                   CONCAT(u.first_name, ' ', u.last_name) as judge_name,
                   d.name as department_name,
                   CONCAT(creator.first_name, ' ', creator.last_name) as created_by_name
            FROM cases c
            LEFT JOIN users u ON c.assigned_judge_id = u.id
            LEFT JOIN departments d ON c.department_id = d.id
            LEFT JOIN users creator ON c.created_by = creator.id
            WHERE c.id = ?
        ";
        
        return $this->db->fetch($sql, [$caseId]);
    }
    
    /**
     * Get cases with filtering and pagination
     */
    public function getList($filters = [], $page = 1, $limit = 20) {
        $where = ['1=1'];
        $params = [];
        
        // Apply filters
        if (!empty($filters['search'])) {
            $where[] = "(c.case_no LIKE ? OR c.title LIKE ? OR c.plaintiff LIKE ? OR c.defendant LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }
        
        if (!empty($filters['case_type'])) {
            $where[] = "c.case_type = ?";
            $params[] = $filters['case_type'];
        }
        
        if (!empty($filters['status'])) {
            $where[] = "c.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['assigned_judge_id'])) {
            $where[] = "c.assigned_judge_id = ?";
            $params[] = $filters['assigned_judge_id'];
        }
        
        if (!empty($filters['priority'])) {
            $where[] = "c.priority = ?";
            $params[] = $filters['priority'];
        }
        
        if (!empty($filters['date_from'])) {
            $where[] = "c.filing_date >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = "c.filing_date <= ?";
            $params[] = $filters['date_to'];
        }
        
        // Role-based access control
        $userRole = $_SESSION['user_role'];
        $userId = $_SESSION['user_id'];
        
        if ($userRole === 'judge') {
            $where[] = "c.assigned_judge_id = ?";
            $params[] = $userId;
        } elseif ($userRole === 'prosecutor') {
            // Prosecutors can only see cases they're involved in
            $where[] = "EXISTS (SELECT 1 FROM case_participants cp WHERE cp.case_id = c.id AND cp.contact_email = ?)";
            $params[] = $_SESSION['user_email'];
        } elseif ($userRole === 'clerk') {
            // Clerks can see cases in their department or cases they created
            $where[] = "(c.created_by = ? OR c.department_id = ?)";
            $params[] = $userId;
            $params[] = $_SESSION['department_id'] ?? 0;
        }
        
        // Hide confidential cases for unauthorized users
        if (!in_array($userRole, ['admin', 'manager', 'judge'])) {
            $where[] = "c.confidential = 0";
        }
        
        $whereClause = implode(' AND ', $where);
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM cases c WHERE {$whereClause}";
        $totalRecords = $this->db->fetch($countSql, $params)['total'];
        
        // Get paginated results
        $offset = ($page - 1) * $limit;
        $sql = "
            SELECT c.*, 
                   CONCAT(u.first_name, ' ', u.last_name) as judge_name,
                   d.name as department_name,
                   (SELECT COUNT(*) FROM documents WHERE case_id = c.id AND is_active = 1) as document_count,
                   (SELECT COUNT(*) FROM hearings WHERE case_id = c.id) as hearing_count,
                   (SELECT MIN(hearing_date) FROM hearings WHERE case_id = c.id AND hearing_date >= NOW() AND status IN ('Scheduled', 'Rescheduled')) as next_hearing
            FROM cases c
            LEFT JOIN users u ON c.assigned_judge_id = u.id
            LEFT JOIN departments d ON c.department_id = d.id
            WHERE {$whereClause}
            ORDER BY c.created_at DESC
            LIMIT {$limit} OFFSET {$offset}
        ";
        
        $cases = $this->db->fetchAll($sql, $params);
        
        return [
            'cases' => $cases,
            'pagination' => paginate($totalRecords, $limit, $page)
        ];
    }
    
    /**
     * Get case statistics
     */
    public function getStatistics($filters = []) {
        $where = ['1=1'];
        $params = [];
        
        // Apply role-based filtering
        $userRole = $_SESSION['user_role'];
        $userId = $_SESSION['user_id'];
        
        if ($userRole === 'judge') {
            $where[] = "assigned_judge_id = ?";
            $params[] = $userId;
        } elseif ($userRole === 'clerk') {
            $where[] = "(created_by = ? OR department_id = ?)";
            $params[] = $userId;
            $params[] = $_SESSION['department_id'] ?? 0;
        }
        
        if (!in_array($userRole, ['admin', 'manager', 'judge'])) {
            $where[] = "confidential = 0";
        }
        
        $whereClause = implode(' AND ', $where);
        
        // Total cases
        $totalCases = $this->db->fetch("SELECT COUNT(*) as count FROM cases WHERE {$whereClause}", $params)['count'];
        
        // Cases by status
        $statusStats = $this->db->fetchAll("
            SELECT status, COUNT(*) as count 
            FROM cases 
            WHERE {$whereClause} 
            GROUP BY status
        ", $params);
        
        // Cases by type
        $typeStats = $this->db->fetchAll("
            SELECT case_type, COUNT(*) as count 
            FROM cases 
            WHERE {$whereClause} 
            GROUP BY case_type
        ", $params);
        
        // Recent activity
        $recentCases = $this->db->fetchAll("
            SELECT case_no, title, status, created_at
            FROM cases 
            WHERE {$whereClause}
            ORDER BY created_at DESC 
            LIMIT 5
        ", $params);
        
        return [
            'total_cases' => $totalCases,
            'status_stats' => $statusStats,
            'type_stats' => $typeStats,
            'recent_cases' => $recentCases
        ];
    }
    
    /**
     * Delete a case
     */
    public function delete($caseId) {
        // Check if case exists
        $case = $this->getById($caseId);
        if (!$case) {
            throw new Exception('Case not found');
        }
        
        // Check permissions (only admin/manager can delete)
        if (!hasPermission('delete_case')) {
            throw new Exception('You do not have permission to delete cases');
        }
        
        // Soft delete by updating status
        $this->db->update('cases', ['status' => 'Deleted'], 'id = ?', [$caseId]);
        
        logAudit('CASE_DELETED', 'cases', $caseId, $caseId, $case, ['status' => 'Deleted']);
        
        return true;
    }
    
    /**
     * Add case participants
     */
    public function addParticipants($caseId, $participants) {
        foreach ($participants as $participant) {
            $data = [
                'case_id' => $caseId,
                'name' => sanitize($participant['name']),
                'role' => sanitize($participant['role']),
                'contact_email' => sanitize($participant['contact_email'] ?? ''),
                'contact_phone' => sanitize($participant['contact_phone'] ?? ''),
                'address' => sanitize($participant['address'] ?? ''),
                'notes' => sanitize($participant['notes'] ?? '')
            ];
            
            $this->db->insert('case_participants', $data);
        }
    }
    
    /**
     * Get case participants
     */
    public function getParticipants($caseId) {
        return $this->db->fetchAll("SELECT * FROM case_participants WHERE case_id = ? ORDER BY role, name", [$caseId]);
    }
    
    /**
     * Add case note
     */
    public function addNote($caseId, $noteData) {
        $data = [
            'case_id' => $caseId,
            'user_id' => $_SESSION['user_id'],
            'note_type' => $noteData['note_type'] ?? 'general',
            'title' => sanitize($noteData['title'] ?? ''),
            'content' => sanitize($noteData['content']),
            'visibility' => $noteData['visibility'] ?? 'staff_only',
            'reminder_date' => $noteData['reminder_date'] ?? null
        ];
        
        $noteId = $this->db->insert('case_notes', $data);
        
        logAudit('CASE_NOTE_ADDED', 'case_notes', $noteId, $caseId, null, $data);
        
        return $noteId;
    }
    
    /**
     * Get case notes
     */
    public function getNotes($caseId) {
        $sql = "
            SELECT cn.*, CONCAT(u.first_name, ' ', u.last_name) as author_name
            FROM case_notes cn
            JOIN users u ON cn.user_id = u.id
            WHERE cn.case_id = ?
            ORDER BY cn.created_at DESC
        ";
        
        return $this->db->fetchAll($sql, [$caseId]);
    }
    
    /**
     * Get judges for assignment
     */
    public function getAvailableJudges() {
        return $this->db->fetchAll("
            SELECT id, CONCAT(first_name, ' ', last_name) as name 
            FROM users 
            WHERE role = 'judge' AND is_active = 1 
            ORDER BY first_name, last_name
        ");
    }
    
    /**
     * Get departments
     */
    public function getDepartments() {
        return $this->db->fetchAll("SELECT * FROM departments WHERE is_active = 1 ORDER BY name");
    }
}
?>