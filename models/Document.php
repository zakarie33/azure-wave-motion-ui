<?php
/**
 * Document Model
 * Handles document management operations
 */

class Document {
    private $conn;
    private $table_name = "documents";

    public $id;
    public $case_id;
    public $title;
    public $description;
    public $document_type;
    public $file_path;
    public $original_name;
    public $mime_type;
    public $file_size;
    public $file_hash;
    public $visibility;
    public $signed_by;
    public $uploaded_by;
    public $uploaded_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Create new document record
     */
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (case_id, title, description, document_type, file_path, original_name, 
                   mime_type, file_size, file_hash, visibility, signed_by, uploaded_by) 
                  VALUES (:case_id, :title, :description, :document_type, :file_path, 
                          :original_name, :mime_type, :file_size, :file_hash, :visibility, 
                          :signed_by, :uploaded_by)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':case_id', $this->case_id);
        $stmt->bindParam(':title', $this->title);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':document_type', $this->document_type);
        $stmt->bindParam(':file_path', $this->file_path);
        $stmt->bindParam(':original_name', $this->original_name);
        $stmt->bindParam(':mime_type', $this->mime_type);
        $stmt->bindParam(':file_size', $this->file_size);
        $stmt->bindParam(':file_hash', $this->file_hash);
        $stmt->bindParam(':visibility', $this->visibility);
        $stmt->bindParam(':signed_by', $this->signed_by);
        $stmt->bindParam(':uploaded_by', $this->uploaded_by);
        
        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            $this->logAudit('DOCUMENT_UPLOADED', $this->id);
            
            // Send notification if document is for judge only
            if ($this->visibility === 'judge_only') {
                $this->sendJudgeNotification();
            }
            
            return true;
        }
        return false;
    }

    /**
     * Get document by ID
     */
    public function getById($id) {
        $query = "SELECT d.*, 
                         CONCAT(u.first_name, ' ', u.last_name) as uploaded_by_name,
                         c.case_no, c.title as case_title
                  FROM " . $this->table_name . " d 
                  JOIN users u ON d.uploaded_by = u.id 
                  JOIN cases c ON d.case_id = c.id 
                  WHERE d.id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        if ($stmt->rowCount() == 1) {
            $document = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Check access permissions
            if (!AuthMiddleware::canAccessDocument($id)) {
                return false;
            }
            
            // Log document access
            $this->logAudit('DOCUMENT_ACCESSED', $id);
            
            return $document;
        }
        return false;
    }

    /**
     * Get documents by case ID
     */
    public function getByCaseId($case_id) {
        // Check case access first
        if (!AuthMiddleware::canAccessCase($case_id)) {
            return [];
        }
        
        $query = "SELECT d.*, 
                         CONCAT(u.first_name, ' ', u.last_name) as uploaded_by_name
                  FROM " . $this->table_name . " d 
                  JOIN users u ON d.uploaded_by = u.id 
                  WHERE d.case_id = :case_id";
        
        // Filter by visibility based on user role
        $user_role = $_SESSION['user_role'];
        $user_id = $_SESSION['user_id'];
        
        if (!in_array($user_role, ['admin', 'manager'])) {
            $query .= " AND (d.visibility = 'public' OR d.visibility = 'case_staff'";
            
            if ($user_role === 'judge') {
                // Check if user is assigned judge
                $judge_query = "SELECT assigned_judge_id FROM cases WHERE id = :case_id";
                $judge_stmt = $this->conn->prepare($judge_query);
                $judge_stmt->bindParam(':case_id', $case_id);
                $judge_stmt->execute();
                $case_data = $judge_stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($case_data && $case_data['assigned_judge_id'] == $user_id) {
                    $query .= " OR d.visibility = 'judge_only'";
                }
            }
            
            $query .= ")";
        }
        
        $query .= " ORDER BY d.uploaded_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':case_id', $case_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Update document metadata
     */
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET title = :title, description = :description, 
                      document_type = :document_type, visibility = :visibility, 
                      signed_by = :signed_by 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':title', $this->title);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':document_type', $this->document_type);
        $stmt->bindParam(':visibility', $this->visibility);
        $stmt->bindParam(':signed_by', $this->signed_by);
        $stmt->bindParam(':id', $this->id);
        
        if ($stmt->execute()) {
            $this->logAudit('DOCUMENT_UPDATED', $this->id);
            return true;
        }
        return false;
    }

    /**
     * Delete document
     */
    public function delete($id) {
        // Get document info first
        $document = $this->getById($id);
        if (!$document) {
            return false;
        }
        
        // Check permissions - only admin, manager, or uploader can delete
        if (!check_permission('manager') && $document['uploaded_by'] != $_SESSION['user_id']) {
            return false;
        }
        
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            // Delete physical file
            if (file_exists($document['file_path'])) {
                unlink($document['file_path']);
            }
            
            $this->logAudit('DOCUMENT_DELETED', $id, $document);
            return true;
        }
        return false;
    }

    /**
     * Upload and process file
     */
    public function uploadFile($file, $case_id, $metadata = []) {
        // Validate file
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'File upload error'];
        }
        
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($file_ext, ALLOWED_FILE_TYPES)) {
            return ['success' => false, 'message' => 'File type not allowed'];
        }
        
        if ($file['size'] > MAX_FILE_SIZE) {
            return ['success' => false, 'message' => 'File size too large'];
        }
        
        // Create upload directory
        $upload_dir = UPLOAD_PATH . date('Y/m/');
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Generate unique filename
        $new_filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file['name']);
        $file_path = $upload_dir . $new_filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            // Set document properties
            $this->case_id = $case_id;
            $this->title = $metadata['title'] ?? pathinfo($file['name'], PATHINFO_FILENAME);
            $this->description = $metadata['description'] ?? '';
            $this->document_type = $metadata['document_type'] ?? 'Other';
            $this->file_path = $file_path;
            $this->original_name = $file['name'];
            $this->mime_type = $file['type'];
            $this->file_size = $file['size'];
            $this->file_hash = hash_file('sha256', $file_path);
            $this->visibility = $metadata['visibility'] ?? 'case_staff';
            $this->signed_by = $metadata['signed_by'] ?? '';
            $this->uploaded_by = $_SESSION['user_id'];
            
            if ($this->create()) {
                return ['success' => true, 'document_id' => $this->id];
            } else {
                // Clean up file if database insert failed
                unlink($file_path);
                return ['success' => false, 'message' => 'Failed to save document record'];
            }
        } else {
            return ['success' => false, 'message' => 'Failed to move uploaded file'];
        }
    }

    /**
     * Get document statistics
     */
    public function getStatistics($case_id = null) {
        $where_clause = $case_id ? "WHERE case_id = :case_id" : "";
        
        $query = "SELECT 
                    COUNT(*) as total_documents,
                    SUM(file_size) as total_size,
                    SUM(CASE WHEN document_type = 'Filing' THEN 1 ELSE 0 END) as filings,
                    SUM(CASE WHEN document_type = 'Evidence' THEN 1 ELSE 0 END) as evidence,
                    SUM(CASE WHEN document_type = 'Motion' THEN 1 ELSE 0 END) as motions,
                    SUM(CASE WHEN document_type = 'Judgment' THEN 1 ELSE 0 END) as judgments,
                    SUM(CASE WHEN visibility = 'judge_only' THEN 1 ELSE 0 END) as judge_only_docs
                  FROM " . $this->table_name . " $where_clause";
        
        $stmt = $this->conn->prepare($query);
        
        if ($case_id) {
            $stmt->bindParam(':case_id', $case_id);
        }
        
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Search documents
     */
    public function search($query, $filters = []) {
        $where_conditions = ["(d.title LIKE :query OR d.description LIKE :query OR d.original_name LIKE :query)"];
        $params = [':query' => '%' . $query . '%'];
        
        if (!empty($filters['case_id'])) {
            $where_conditions[] = "d.case_id = :case_id";
            $params[':case_id'] = $filters['case_id'];
        }
        
        if (!empty($filters['document_type'])) {
            $where_conditions[] = "d.document_type = :document_type";
            $params[':document_type'] = $filters['document_type'];
        }
        
        if (!empty($filters['uploaded_by'])) {
            $where_conditions[] = "d.uploaded_by = :uploaded_by";
            $params[':uploaded_by'] = $filters['uploaded_by'];
        }
        
        // Handle visibility based on user role
        if (!check_permission('manager')) {
            $visibility_conditions = ["d.visibility = 'public'", "d.visibility = 'case_staff'"];
            
            if ($_SESSION['user_role'] === 'judge') {
                $visibility_conditions[] = "d.visibility = 'judge_only'";
            }
            
            $where_conditions[] = "(" . implode(' OR ', $visibility_conditions) . ")";
        }
        
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        
        $sql = "SELECT d.*, 
                       CONCAT(u.first_name, ' ', u.last_name) as uploaded_by_name,
                       c.case_no, c.title as case_title
                FROM " . $this->table_name . " d 
                JOIN users u ON d.uploaded_by = u.id 
                JOIN cases c ON d.case_id = c.id 
                $where_clause 
                ORDER BY d.uploaded_at DESC 
                LIMIT 50";
        
        $stmt = $this->conn->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Generate document thumbnail (for images/PDFs)
     */
    public function generateThumbnail($document_id) {
        $document = $this->getById($document_id);
        if (!$document) return false;
        
        $file_path = $document['file_path'];
        $thumbnail_dir = dirname($file_path) . '/thumbnails/';
        
        if (!is_dir($thumbnail_dir)) {
            mkdir($thumbnail_dir, 0755, true);
        }
        
        $thumbnail_path = $thumbnail_dir . 'thumb_' . basename($file_path) . '.jpg';
        
        // Check if thumbnail already exists
        if (file_exists($thumbnail_path)) {
            return $thumbnail_path;
        }
        
        $mime_type = $document['mime_type'];
        
        // Generate thumbnail based on file type
        if (strpos($mime_type, 'image/') === 0) {
            return $this->generateImageThumbnail($file_path, $thumbnail_path);
        } elseif ($mime_type === 'application/pdf') {
            return $this->generatePDFThumbnail($file_path, $thumbnail_path);
        }
        
        return false;
    }

    /**
     * Generate image thumbnail
     */
    private function generateImageThumbnail($source, $destination) {
        $image_info = getimagesize($source);
        if (!$image_info) return false;
        
        $width = $image_info[0];
        $height = $image_info[1];
        $type = $image_info[2];
        
        // Calculate thumbnail dimensions
        $thumb_width = 200;
        $thumb_height = ($height * $thumb_width) / $width;
        
        // Create image resource
        switch ($type) {
            case IMAGETYPE_JPEG:
                $source_image = imagecreatefromjpeg($source);
                break;
            case IMAGETYPE_PNG:
                $source_image = imagecreatefrompng($source);
                break;
            case IMAGETYPE_GIF:
                $source_image = imagecreatefromgif($source);
                break;
            default:
                return false;
        }
        
        // Create thumbnail
        $thumb_image = imagecreatetruecolor($thumb_width, $thumb_height);
        imagecopyresampled($thumb_image, $source_image, 0, 0, 0, 0, $thumb_width, $thumb_height, $width, $height);
        
        // Save thumbnail
        $result = imagejpeg($thumb_image, $destination, 80);
        
        // Clean up
        imagedestroy($source_image);
        imagedestroy($thumb_image);
        
        return $result ? $destination : false;
    }

    /**
     * Generate PDF thumbnail (requires ImageMagick)
     */
    private function generatePDFThumbnail($source, $destination) {
        // This requires ImageMagick to be installed
        if (!extension_loaded('imagick')) {
            return false;
        }
        
        try {
            $imagick = new Imagick();
            $imagick->setResolution(150, 150);
            $imagick->readImage($source . '[0]'); // First page only
            $imagick->setImageFormat('jpeg');
            $imagick->thumbnailImage(200, 0);
            $imagick->writeImage($destination);
            $imagick->clear();
            $imagick->destroy();
            
            return $destination;
        } catch (Exception $e) {
            error_log("PDF thumbnail generation failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send notification to judge for judge-only documents
     */
    private function sendJudgeNotification() {
        // Get assigned judge for the case
        $query = "SELECT assigned_judge_id FROM cases WHERE id = :case_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':case_id', $this->case_id);
        $stmt->execute();
        
        if ($stmt->rowCount() == 1) {
            $case = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($case['assigned_judge_id']) {
                $notification_query = "INSERT INTO notifications (recipient_id, subject, message, case_id, type) 
                                      VALUES (:recipient_id, :subject, :message, :case_id, 'info')";
                
                $notification_stmt = $this->conn->prepare($notification_query);
                $notification_stmt->bindParam(':recipient_id', $case['assigned_judge_id']);
                $notification_stmt->bindParam(':subject', 'New Judge-Only Document: ' . $this->title);
                $notification_stmt->bindParam(':message', 'A new document has been uploaded for your review: ' . $this->title);
                $notification_stmt->bindParam(':case_id', $this->case_id);
                $notification_stmt->execute();
            }
        }
    }

    /**
     * Log audit trail
     */
    private function logAudit($action, $record_id, $old_values = null) {
        AuthMiddleware::logActivity($action, $this->table_name, $record_id, $old_values, [
            'case_id' => $this->case_id,
            'title' => $this->title,
            'document_type' => $this->document_type,
            'file_path' => $this->file_path
        ]);
    }
}
?>