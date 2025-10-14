<?php
/**
 * Document Model
 * Handles all document-related database operations
 */

if (!defined('COURT_SYSTEM')) {
    define('COURT_SYSTEM', true);
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

class DocumentModel {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Upload and store a document
     */
    public function upload($caseId, $documentData, $fileData) {
        // Validate case exists and user has access
        if (!canAccessCase($caseId)) {
            throw new Exception('You do not have permission to upload documents to this case');
        }
        
        // Validate required fields
        $required = ['title', 'document_type'];
        foreach ($required as $field) {
            if (empty($documentData[$field])) {
                throw new Exception("Field '{$field}' is required");
            }
        }
        
        // Validate file
        if (!isset($fileData['tmp_name']) || !is_uploaded_file($fileData['tmp_name'])) {
            throw new Exception('No file uploaded or upload error occurred');
        }
        
        if ($fileData['size'] > MAX_UPLOAD_SIZE) {
            throw new Exception('File size exceeds maximum allowed size of ' . formatFileSize(MAX_UPLOAD_SIZE));
        }
        
        if (!isAllowedFileType($fileData['name'])) {
            throw new Exception('File type not allowed. Allowed types: ' . implode(', ', ALLOWED_FILE_TYPES));
        }
        
        // Generate secure filename and path
        $filename = generateSecureFilename($fileData['name']);
        $filePath = UPLOAD_PATH . $filename;
        
        // Ensure upload directory exists
        if (!is_dir(UPLOAD_PATH)) {
            if (!mkdir(UPLOAD_PATH, 0755, true)) {
                throw new Exception('Failed to create upload directory');
            }
        }
        
        // Move uploaded file
        if (!move_uploaded_file($fileData['tmp_name'], $filePath)) {
            throw new Exception('Failed to move uploaded file');
        }
        
        // Calculate file hash for integrity
        $fileHash = calculateFileHash($filePath);
        
        // Check for duplicate files
        $existingDoc = $this->db->fetch(
            "SELECT id FROM documents WHERE file_hash = ? AND case_id = ? AND is_active = 1",
            [$fileHash, $caseId]
        );
        
        if ($existingDoc) {
            unlink($filePath); // Remove duplicate file
            throw new Exception('This file has already been uploaded to this case');
        }
        
        // Prepare document data
        $insertData = [
            'case_id' => $caseId,
            'title' => sanitize($documentData['title']),
            'description' => sanitize($documentData['description'] ?? ''),
            'document_type' => $documentData['document_type'],
            'file_path' => $filename, // Store relative path
            'original_filename' => $fileData['name'],
            'file_size' => $fileData['size'],
            'mime_type' => $fileData['type'],
            'file_hash' => $fileHash,
            'visibility' => $documentData['visibility'] ?? 'case_staff',
            'signed_by' => sanitize($documentData['signed_by'] ?? ''),
            'uploaded_by' => $_SESSION['user_id']
        ];
        
        $documentId = $this->db->insert('documents', $insertData);
        
        // Log the upload
        logAudit('DOCUMENT_UPLOADED', 'documents', $documentId, $caseId, null, $insertData);
        
        // Send notification to assigned judge if document is for judge review
        if ($documentData['visibility'] === 'judge_only' || $documentData['document_type'] === 'Motion') {
            $case = $this->db->fetch("SELECT assigned_judge_id, case_no, title FROM cases WHERE id = ?", [$caseId]);
            if ($case && $case['assigned_judge_id']) {
                sendNotification(
                    $case['assigned_judge_id'],
                    'New Document Uploaded',
                    "A new document '{$documentData['title']}' has been uploaded to case {$case['case_no']}: {$case['title']}",
                    'document_upload',
                    $caseId
                );
            }
        }
        
        return $documentId;
    }
    
    /**
     * Get document by ID
     */
    public function getById($documentId) {
        $sql = "
            SELECT d.*, 
                   CONCAT(u.first_name, ' ', u.last_name) as uploaded_by_name,
                   c.case_no, c.title as case_title
            FROM documents d
            JOIN users u ON d.uploaded_by = u.id
            JOIN cases c ON d.case_id = c.id
            WHERE d.id = ? AND d.is_active = 1
        ";
        
        return $this->db->fetch($sql, [$documentId]);
    }
    
    /**
     * Get documents for a case
     */
    public function getByCaseId($caseId, $filters = []) {
        // Check access to case
        if (!canAccessCase($caseId)) {
            throw new Exception('You do not have permission to view documents for this case');
        }
        
        $where = ['d.case_id = ?', 'd.is_active = 1'];
        $params = [$caseId];
        
        // Apply visibility restrictions
        $userRole = $_SESSION['user_role'];
        if (!in_array($userRole, ['admin', 'manager'])) {
            if ($userRole === 'judge') {
                // Judges can see all documents for their assigned cases
                $case = $this->db->fetch("SELECT assigned_judge_id FROM cases WHERE id = ?", [$caseId]);
                if (!$case || $case['assigned_judge_id'] != $_SESSION['user_id']) {
                    $where[] = "d.visibility IN ('public', 'case_staff')";
                }
            } else {
                // Other roles see based on visibility
                $where[] = "d.visibility IN ('public', 'case_staff')";
            }
        }
        
        // Apply filters
        if (!empty($filters['document_type'])) {
            $where[] = "d.document_type = ?";
            $params[] = $filters['document_type'];
        }
        
        if (!empty($filters['search'])) {
            $where[] = "(d.title LIKE ? OR d.description LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $whereClause = implode(' AND ', $where);
        
        $sql = "
            SELECT d.*, 
                   CONCAT(u.first_name, ' ', u.last_name) as uploaded_by_name
            FROM documents d
            JOIN users u ON d.uploaded_by = u.id
            WHERE {$whereClause}
            ORDER BY d.uploaded_at DESC
        ";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Get documents list with pagination
     */
    public function getList($filters = [], $page = 1, $limit = 20) {
        $where = ['d.is_active = 1'];
        $params = [];
        
        // Role-based access control
        $userRole = $_SESSION['user_role'];
        $userId = $_SESSION['user_id'];
        
        if ($userRole === 'judge') {
            // Judges can only see documents from their assigned cases
            $where[] = "EXISTS (SELECT 1 FROM cases c WHERE c.id = d.case_id AND c.assigned_judge_id = ?)";
            $params[] = $userId;
        } elseif ($userRole === 'prosecutor') {
            // Prosecutors can only see documents from cases they're involved in
            $where[] = "EXISTS (SELECT 1 FROM case_participants cp JOIN cases c ON cp.case_id = c.id WHERE cp.case_id = d.case_id AND cp.contact_email = ?)";
            $params[] = $_SESSION['user_email'];
        } elseif ($userRole === 'clerk') {
            // Clerks can see documents from cases in their department or cases they created
            $where[] = "EXISTS (SELECT 1 FROM cases c WHERE c.id = d.case_id AND (c.created_by = ? OR c.department_id = ?))";
            $params[] = $userId;
            $params[] = $_SESSION['department_id'] ?? 0;
        }
        
        // Apply visibility restrictions for non-admin users
        if (!in_array($userRole, ['admin', 'manager'])) {
            if ($userRole !== 'judge') {
                $where[] = "d.visibility IN ('public', 'case_staff')";
            }
        }
        
        // Apply filters
        if (!empty($filters['case_id'])) {
            $where[] = "d.case_id = ?";
            $params[] = $filters['case_id'];
        }
        
        if (!empty($filters['document_type'])) {
            $where[] = "d.document_type = ?";
            $params[] = $filters['document_type'];
        }
        
        if (!empty($filters['search'])) {
            $where[] = "(d.title LIKE ? OR d.description LIKE ? OR c.case_no LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $whereClause = implode(' AND ', $where);
        
        // Get total count
        $countSql = "
            SELECT COUNT(*) as total 
            FROM documents d 
            JOIN cases c ON d.case_id = c.id 
            WHERE {$whereClause}
        ";
        $totalRecords = $this->db->fetch($countSql, $params)['total'];
        
        // Get paginated results
        $offset = ($page - 1) * $limit;
        $sql = "
            SELECT d.*, 
                   CONCAT(u.first_name, ' ', u.last_name) as uploaded_by_name,
                   c.case_no, c.title as case_title
            FROM documents d
            JOIN users u ON d.uploaded_by = u.id
            JOIN cases c ON d.case_id = c.id
            WHERE {$whereClause}
            ORDER BY d.uploaded_at DESC
            LIMIT {$limit} OFFSET {$offset}
        ";
        
        $documents = $this->db->fetchAll($sql, $params);
        
        return [
            'documents' => $documents,
            'pagination' => paginate($totalRecords, $limit, $page)
        ];
    }
    
    /**
     * Update document metadata
     */
    public function update($documentId, $data) {
        $document = $this->getById($documentId);
        if (!$document) {
            throw new Exception('Document not found');
        }
        
        // Check permissions
        if (!canAccessCase($document['case_id'])) {
            throw new Exception('You do not have permission to edit this document');
        }
        
        // Only allow certain users to edit documents
        if (!in_array($_SESSION['user_role'], ['admin', 'manager']) && 
            $document['uploaded_by'] != $_SESSION['user_id']) {
            throw new Exception('You can only edit documents you uploaded');
        }
        
        $updateData = [];
        $oldValues = [];
        $newValues = [];
        
        $allowedFields = ['title', 'description', 'document_type', 'visibility', 'signed_by'];
        foreach ($allowedFields as $field) {
            if (isset($data[$field]) && $data[$field] !== $document[$field]) {
                $oldValues[$field] = $document[$field];
                $newValues[$field] = sanitize($data[$field]);
                $updateData[$field] = $newValues[$field];
            }
        }
        
        if (!empty($updateData)) {
            $this->db->update('documents', $updateData, 'id = ?', [$documentId]);
            logAudit('DOCUMENT_UPDATED', 'documents', $documentId, $document['case_id'], $oldValues, $newValues);
        }
        
        return true;
    }
    
    /**
     * Delete document (soft delete)
     */
    public function delete($documentId) {
        $document = $this->getById($documentId);
        if (!$document) {
            throw new Exception('Document not found');
        }
        
        // Check permissions
        if (!hasPermission('delete_case') && $document['uploaded_by'] != $_SESSION['user_id']) {
            throw new Exception('You do not have permission to delete this document');
        }
        
        // Soft delete
        $this->db->update('documents', ['is_active' => 0], 'id = ?', [$documentId]);
        
        logAudit('DOCUMENT_DELETED', 'documents', $documentId, $document['case_id'], $document, ['is_active' => 0]);
        
        return true;
    }
    
    /**
     * Get file path for download
     */
    public function getFilePath($documentId) {
        $document = $this->getById($documentId);
        if (!$document) {
            throw new Exception('Document not found');
        }
        
        // Check permissions
        if (!canAccessCase($document['case_id'])) {
            throw new Exception('You do not have permission to access this document');
        }
        
        // Check visibility restrictions
        $userRole = $_SESSION['user_role'];
        if (!in_array($userRole, ['admin', 'manager'])) {
            if ($document['visibility'] === 'judge_only' && $userRole !== 'judge') {
                throw new Exception('This document is restricted to judges only');
            }
            if ($document['visibility'] === 'admin_only') {
                throw new Exception('This document is restricted to administrators only');
            }
        }
        
        $filePath = UPLOAD_PATH . $document['file_path'];
        if (!file_exists($filePath)) {
            throw new Exception('File not found on server');
        }
        
        // Log document access
        logAudit('DOCUMENT_ACCESSED', 'documents', $documentId, $document['case_id']);
        
        return [
            'path' => $filePath,
            'filename' => $document['original_filename'],
            'mime_type' => $document['mime_type']
        ];
    }
    
    /**
     * Create document request
     */
    public function createRequest($caseId, $requestData) {
        // Check if user can access case
        if (!canAccessCase($caseId)) {
            throw new Exception('You do not have permission to request documents for this case');
        }
        
        // Validate required fields
        if (empty($requestData['document_ids']) || empty($requestData['reason'])) {
            throw new Exception('Document IDs and reason are required');
        }
        
        $data = [
            'case_id' => $caseId,
            'requestor_id' => $_SESSION['user_id'],
            'document_ids' => json_encode($requestData['document_ids']),
            'reason' => sanitize($requestData['reason']),
            'due_by' => !empty($requestData['due_by']) ? $requestData['due_by'] : null,
            'status' => 'pending'
        ];
        
        $requestId = $this->db->insert('document_requests', $data);
        
        // Notify clerks/admins about the request
        $clerks = $this->db->fetchAll("SELECT id FROM users WHERE role IN ('clerk', 'admin', 'manager') AND is_active = 1");
        foreach ($clerks as $clerk) {
            sendNotification(
                $clerk['id'],
                'Document Access Request',
                "A document access request has been submitted for case ID {$caseId}",
                'document_request',
                $caseId
            );
        }
        
        logAudit('DOCUMENT_REQUEST_CREATED', 'document_requests', $requestId, $caseId, null, $data);
        
        return $requestId;
    }
    
    /**
     * Get document statistics
     */
    public function getStatistics() {
        $userRole = $_SESSION['user_role'];
        $userId = $_SESSION['user_id'];
        
        $where = ['d.is_active = 1'];
        $params = [];
        
        // Apply role-based filtering
        if ($userRole === 'judge') {
            $where[] = "EXISTS (SELECT 1 FROM cases c WHERE c.id = d.case_id AND c.assigned_judge_id = ?)";
            $params[] = $userId;
        } elseif ($userRole === 'clerk') {
            $where[] = "EXISTS (SELECT 1 FROM cases c WHERE c.id = d.case_id AND (c.created_by = ? OR c.department_id = ?))";
            $params[] = $userId;
            $params[] = $_SESSION['department_id'] ?? 0;
        }
        
        $whereClause = implode(' AND ', $where);
        
        // Total documents
        $totalDocs = $this->db->fetch("SELECT COUNT(*) as count FROM documents d WHERE {$whereClause}", $params)['count'];
        
        // Documents by type
        $typeStats = $this->db->fetchAll("
            SELECT document_type, COUNT(*) as count 
            FROM documents d 
            WHERE {$whereClause} 
            GROUP BY document_type 
            ORDER BY count DESC
        ", $params);
        
        // Recent uploads
        $recentUploads = $this->db->fetchAll("
            SELECT d.title, d.uploaded_at, c.case_no
            FROM documents d 
            JOIN cases c ON d.case_id = c.id
            WHERE {$whereClause}
            ORDER BY d.uploaded_at DESC 
            LIMIT 5
        ", $params);
        
        return [
            'total_documents' => $totalDocs,
            'type_stats' => $typeStats,
            'recent_uploads' => $recentUploads
        ];
    }
}
?>