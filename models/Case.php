<?php
/**
 * Case Model
 * Handles case management operations
 */

class CaseModel {
    private $conn;
    private $table_name = "cases";

    public $id;
    public $case_no;
    public $title;
    public $description;
    public $case_type;
    public $filing_date;
    public $status;
    public $priority;
    public $plaintiff;
    public $defendant;
    public $assigned_judge_id;
    public $confidential;
    public $tags;
    public $created_by;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Create new case
     */
    public function create() {
        // Generate case number if not provided
        if (empty($this->case_no)) {
            $this->case_no = generate_case_number();
        }

        $query = "INSERT INTO " . $this->table_name . " 
                  (case_no, title, description, case_type, filing_date, status, priority, 
                   plaintiff, defendant, assigned_judge_id, confidential, tags, created_by) 
                  VALUES (:case_no, :title, :description, :case_type, :filing_date, :status, 
                          :priority, :plaintiff, :defendant, :assigned_judge_id, :confidential, 
                          :tags, :created_by)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':case_no', $this->case_no);
        $stmt->bindParam(':title', $this->title);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':case_type', $this->case_type);
        $stmt->bindParam(':filing_date', $this->filing_date);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':priority', $this->priority);
        $stmt->bindParam(':plaintiff', $this->plaintiff);
        $stmt->bindParam(':defendant', $this->defendant);
        $stmt->bindParam(':assigned_judge_id', $this->assigned_judge_id);
        $stmt->bindParam(':confidential', $this->confidential);
        $stmt->bindParam(':tags', $this->tags);
        $stmt->bindParam(':created_by', $this->created_by);
        
        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            $this->logAudit('CASE_CREATED', $this->id);
            
            // Send notification to assigned judge if set
            if ($this->assigned_judge_id) {
                $this->sendCaseAssignmentNotification();
            }
            
            return true;
        }
        return false;
    }

    /**
     * Update case
     */
    public function update() {
        // Get old values for audit
        $old_values = $this->getById($this->id);
        
        $query = "UPDATE " . $this->table_name . " 
                  SET title = :title, description = :description, case_type = :case_type, 
                      status = :status, priority = :priority, plaintiff = :plaintiff, 
                      defendant = :defendant, assigned_judge_id = :assigned_judge_id, 
                      confidential = :confidential, tags = :tags 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':title', $this->title);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':case_type', $this->case_type);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':priority', $this->priority);
        $stmt->bindParam(':plaintiff', $this->plaintiff);
        $stmt->bindParam(':defendant', $this->defendant);
        $stmt->bindParam(':assigned_judge_id', $this->assigned_judge_id);
        $stmt->bindParam(':confidential', $this->confidential);
        $stmt->bindParam(':tags', $this->tags);
        $stmt->bindParam(':id', $this->id);
        
        if ($stmt->execute()) {
            $this->logAudit('CASE_UPDATED', $this->id, $old_values);
            
            // Send notification if judge assignment changed
            if ($old_values && $old_values['assigned_judge_id'] != $this->assigned_judge_id) {
                $this->sendCaseAssignmentNotification();
            }
            
            return true;
        }
        return false;
    }

    /**
     * Get case by ID
     */
    public function getById($id) {
        $query = "SELECT c.*, 
                         CONCAT(u.first_name, ' ', u.last_name) as judge_name,
                         CONCAT(creator.first_name, ' ', creator.last_name) as created_by_name
                  FROM " . $this->table_name . " c 
                  LEFT JOIN users u ON c.assigned_judge_id = u.id 
                  LEFT JOIN users creator ON c.created_by = creator.id 
                  WHERE c.id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        if ($stmt->rowCount() == 1) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Check access permissions
            if (!AuthMiddleware::canAccessCase($id)) {
                return false;
            }
            
            // Log case view
            $this->logAudit('CASE_VIEWED', $id);
            
            return $row;
        }
        return false;
    }

    /**
     * Get all cases with filters and pagination
     */
    public function getAll($page = 1, $limit = 20, $filters = []) {
        $offset = ($page - 1) * $limit;
        $where_conditions = [];
        $params = [];
        
        // Build WHERE clause based on filters
        if (!empty($filters['search'])) {
            $where_conditions[] = "(c.case_no LIKE :search OR c.title LIKE :search OR c.plaintiff LIKE :search OR c.defendant LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        if (!empty($filters['case_type'])) {
            $where_conditions[] = "c.case_type = :case_type";
            $params[':case_type'] = $filters['case_type'];
        }
        
        if (!empty($filters['status'])) {
            $where_conditions[] = "c.status = :status";
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['assigned_judge_id'])) {
            $where_conditions[] = "c.assigned_judge_id = :assigned_judge_id";
            $params[':assigned_judge_id'] = $filters['assigned_judge_id'];
        }
        
        if (!empty($filters['date_from'])) {
            $where_conditions[] = "c.filing_date >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where_conditions[] = "c.filing_date <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }
        
        // Handle confidential cases based on user role
        if (!check_permission('manager')) {
            if ($_SESSION['user_role'] === 'judge') {
                $where_conditions[] = "(c.confidential = 0 OR c.assigned_judge_id = :user_id)";
                $params[':user_id'] = $_SESSION['user_id'];
            } else {
                $where_conditions[] = "c.confidential = 0";
            }
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        $query = "SELECT c.*, 
                         CONCAT(u.first_name, ' ', u.last_name) as judge_name,
                         CONCAT(creator.first_name, ' ', creator.last_name) as created_by_name,
                         (SELECT COUNT(*) FROM documents WHERE case_id = c.id) as document_count,
                         (SELECT COUNT(*) FROM hearings WHERE case_id = c.id) as hearing_count
                  FROM " . $this->table_name . " c 
                  LEFT JOIN users u ON c.assigned_judge_id = u.id 
                  LEFT JOIN users creator ON c.created_by = creator.id 
                  $where_clause 
                  ORDER BY c.created_at DESC 
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get total count for pagination
     */
    public function getTotalCount($filters = []) {
        $where_conditions = [];
        $params = [];
        
        // Same filter logic as getAll()
        if (!empty($filters['search'])) {
            $where_conditions[] = "(case_no LIKE :search OR title LIKE :search OR plaintiff LIKE :search OR defendant LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        if (!empty($filters['case_type'])) {
            $where_conditions[] = "case_type = :case_type";
            $params[':case_type'] = $filters['case_type'];
        }
        
        if (!empty($filters['status'])) {
            $where_conditions[] = "status = :status";
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['assigned_judge_id'])) {
            $where_conditions[] = "assigned_judge_id = :assigned_judge_id";
            $params[':assigned_judge_id'] = $filters['assigned_judge_id'];
        }
        
        if (!empty($filters['date_from'])) {
            $where_conditions[] = "filing_date >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where_conditions[] = "filing_date <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }
        
        // Handle confidential cases
        if (!check_permission('manager')) {
            if ($_SESSION['user_role'] === 'judge') {
                $where_conditions[] = "(confidential = 0 OR assigned_judge_id = :user_id)";
                $params[':user_id'] = $_SESSION['user_id'];
            } else {
                $where_conditions[] = "confidential = 0";
            }
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " $where_clause";
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }

    /**
     * Get cases assigned to a judge
     */
    public function getByJudge($judge_id, $status = null) {
        $where_clause = "WHERE assigned_judge_id = :judge_id";
        $params = [':judge_id' => $judge_id];
        
        if ($status) {
            $where_clause .= " AND status = :status";
            $params[':status'] = $status;
        }
        
        $query = "SELECT c.*, 
                         (SELECT COUNT(*) FROM documents WHERE case_id = c.id) as document_count,
                         (SELECT MIN(hearing_date) FROM hearings WHERE case_id = c.id AND status = 'Scheduled') as next_hearing
                  FROM " . $this->table_name . " c 
                  $where_clause 
                  ORDER BY c.priority DESC, c.filing_date ASC";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get case statistics
     */
    public function getStatistics($date_from = null, $date_to = null) {
        $where_clause = "";
        $params = [];
        
        if ($date_from && $date_to) {
            $where_clause = "WHERE filing_date BETWEEN :date_from AND :date_to";
            $params[':date_from'] = $date_from;
            $params[':date_to'] = $date_to;
        }
        
        $query = "SELECT 
                    COUNT(*) as total_cases,
                    SUM(CASE WHEN status = 'Filed' THEN 1 ELSE 0 END) as filed_cases,
                    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_cases,
                    SUM(CASE WHEN status = 'In Hearing' THEN 1 ELSE 0 END) as hearing_cases,
                    SUM(CASE WHEN status = 'Judged' THEN 1 ELSE 0 END) as judged_cases,
                    SUM(CASE WHEN status = 'Closed' THEN 1 ELSE 0 END) as closed_cases,
                    SUM(CASE WHEN case_type = 'Criminal' THEN 1 ELSE 0 END) as criminal_cases,
                    SUM(CASE WHEN case_type = 'Civil' THEN 1 ELSE 0 END) as civil_cases,
                    SUM(CASE WHEN case_type = 'Family' THEN 1 ELSE 0 END) as family_cases,
                    SUM(CASE WHEN case_type = 'Appeal' THEN 1 ELSE 0 END) as appeal_cases
                  FROM " . $this->table_name . " $where_clause";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Add case note
     */
    public function addNote($case_id, $note_text, $is_private = 1) {
        $query = "INSERT INTO case_notes (case_id, user_id, note_text, is_private) 
                  VALUES (:case_id, :user_id, :note_text, :is_private)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':case_id', $case_id);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->bindParam(':note_text', $note_text);
        $stmt->bindParam(':is_private', $is_private);
        
        if ($stmt->execute()) {
            $this->logAudit('CASE_NOTE_ADDED', $case_id);
            return true;
        }
        return false;
    }

    /**
     * Get case notes
     */
    public function getNotes($case_id) {
        $query = "SELECT cn.*, CONCAT(u.first_name, ' ', u.last_name) as author_name 
                  FROM case_notes cn 
                  JOIN users u ON cn.user_id = u.id 
                  WHERE cn.case_id = :case_id 
                  AND (cn.is_private = 0 OR cn.user_id = :user_id OR :user_role IN ('admin', 'manager'))
                  ORDER BY cn.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':case_id', $case_id);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->bindParam(':user_role', $_SESSION['user_role']);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Send case assignment notification
     */
    private function sendCaseAssignmentNotification() {
        if (!$this->assigned_judge_id) return;
        
        $query = "INSERT INTO notifications (recipient_id, subject, message, case_id, type) 
                  VALUES (:recipient_id, :subject, :message, :case_id, 'info')";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':recipient_id', $this->assigned_judge_id);
        $stmt->bindParam(':subject', 'New Case Assigned: ' . $this->case_no);
        $stmt->bindParam(':message', 'You have been assigned to case: ' . $this->title);
        $stmt->bindParam(':case_id', $this->id);
        $stmt->execute();
    }

    /**
     * Log audit trail
     */
    private function logAudit($action, $record_id, $old_values = null) {
        AuthMiddleware::logActivity($action, $this->table_name, $record_id, $old_values, [
            'case_no' => $this->case_no,
            'title' => $this->title,
            'status' => $this->status
        ]);
    }
}
?>