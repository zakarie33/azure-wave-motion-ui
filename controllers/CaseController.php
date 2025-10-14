<?php
/**
 * Case Controller
 * Handles case management operations
 */

class CaseController {
    private $db;
    private $case;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->case = new CaseModel($this->db);
    }

    /**
     * Show all cases
     */
    public function index() {
        AuthMiddleware::requireRole('clerk');
        
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 20;
        
        // Get filters from request
        $filters = [
            'search' => sanitize_input($_GET['search'] ?? ''),
            'case_type' => sanitize_input($_GET['case_type'] ?? ''),
            'status' => sanitize_input($_GET['status'] ?? ''),
            'assigned_judge_id' => sanitize_input($_GET['assigned_judge_id'] ?? ''),
            'date_from' => sanitize_input($_GET['date_from'] ?? ''),
            'date_to' => sanitize_input($_GET['date_to'] ?? '')
        ];
        
        // Remove empty filters
        $filters = array_filter($filters);
        
        $cases = $this->case->getAll($page, $limit, $filters);
        $total_cases = $this->case->getTotalCount($filters);
        $total_pages = ceil($total_cases / $limit);
        
        // Get judges for filter dropdown
        $user_model = new User($this->db);
        $judges = $user_model->getJudges();
        
        $page_title = "All Cases";
        include 'views/cases/index.php';
    }

    /**
     * Show new case form
     */
    public function create() {
        AuthMiddleware::requireRole('clerk');
        
        // Get judges for assignment dropdown
        $user_model = new User($this->db);
        $judges = $user_model->getJudges();
        
        $page_title = "New Case Registration";
        include 'views/cases/create.php';
    }

    /**
     * Process new case creation
     */
    public function store() {
        AuthMiddleware::requireRole('clerk');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('new-case.php');
        }
        
        // Validate CSRF token
        if (!AuthMiddleware::validateCSRF()) {
            flash_message('error', 'Invalid request. Please try again.');
            redirect('new-case.php');
        }
        
        // Sanitize and validate input
        $case_no = sanitize_input($_POST['case_no'] ?? '');
        $title = sanitize_input($_POST['title'] ?? '');
        $description = sanitize_input($_POST['description'] ?? '');
        $case_type = sanitize_input($_POST['case_type'] ?? '');
        $filing_date = sanitize_input($_POST['filing_date'] ?? date('Y-m-d'));
        $plaintiff = sanitize_input($_POST['plaintiff'] ?? '');
        $defendant = sanitize_input($_POST['defendant'] ?? '');
        $assigned_judge_id = !empty($_POST['assigned_judge_id']) ? (int)$_POST['assigned_judge_id'] : null;
        $priority = sanitize_input($_POST['priority'] ?? 'Normal');
        $confidential = isset($_POST['confidential']) ? 1 : 0;
        $tags = sanitize_input($_POST['tags'] ?? '');
        
        // Validation
        $errors = [];
        
        if (empty($title)) $errors[] = "Case title is required";
        if (empty($description)) $errors[] = "Case description is required";
        if (empty($case_type) || !in_array($case_type, ['Criminal', 'Civil', 'Family', 'Appeal', 'Administrative'])) {
            $errors[] = "Valid case type is required";
        }
        if (empty($plaintiff)) $errors[] = "Plaintiff/Prosecutor is required";
        if (empty($defendant)) $errors[] = "Defendant is required";
        if (!empty($filing_date) && !strtotime($filing_date)) {
            $errors[] = "Valid filing date is required";
        }
        
        if (!empty($errors)) {
            flash_message('error', implode('<br>', $errors));
            redirect('new-case.php');
        }
        
        // Create case
        $this->case->case_no = $case_no;
        $this->case->title = $title;
        $this->case->description = $description;
        $this->case->case_type = $case_type;
        $this->case->filing_date = $filing_date;
        $this->case->status = 'Filed';
        $this->case->priority = $priority;
        $this->case->plaintiff = $plaintiff;
        $this->case->defendant = $defendant;
        $this->case->assigned_judge_id = $assigned_judge_id;
        $this->case->confidential = $confidential;
        $this->case->tags = $tags;
        $this->case->created_by = $_SESSION['user_id'];
        
        if ($this->case->create()) {
            // Handle file uploads if any
            if (!empty($_FILES['documents']['name'][0])) {
                $this->handleDocumentUploads($this->case->id);
            }
            
            flash_message('success', 'Case registered successfully. Case No: ' . $this->case->case_no);
            redirect('case-details.php?id=' . $this->case->id);
        } else {
            flash_message('error', 'Failed to register case. Please try again.');
            redirect('new-case.php');
        }
    }

    /**
     * Show case details
     */
    public function show($id) {
        AuthMiddleware::requireAuth();
        
        $case_data = $this->case->getById($id);
        
        if (!$case_data) {
            flash_message('error', 'Case not found or access denied');
            redirect('cases.php');
        }
        
        // Get case documents
        $document_model = new Document($this->db);
        $documents = $document_model->getByCaseId($id);
        
        // Get case hearings
        $hearing_model = new Hearing($this->db);
        $hearings = $hearing_model->getByCaseId($id);
        
        // Get case notes
        $notes = $this->case->getNotes($id);
        
        // Get case judgments
        $judgment_model = new Judgment($this->db);
        $judgments = $judgment_model->getByCaseId($id);
        
        $page_title = "Case Details - " . $case_data['case_no'];
        include 'views/cases/show.php';
    }

    /**
     * Show case edit form
     */
    public function edit($id) {
        AuthMiddleware::requireRole('clerk');
        
        $case_data = $this->case->getById($id);
        
        if (!$case_data) {
            flash_message('error', 'Case not found or access denied');
            redirect('cases.php');
        }
        
        // Get judges for assignment dropdown
        $user_model = new User($this->db);
        $judges = $user_model->getJudges();
        
        $page_title = "Edit Case - " . $case_data['case_no'];
        include 'views/cases/edit.php';
    }

    /**
     * Process case update
     */
    public function update($id) {
        AuthMiddleware::requireRole('clerk');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('edit-case.php?id=' . $id);
        }
        
        // Validate CSRF token
        if (!AuthMiddleware::validateCSRF()) {
            flash_message('error', 'Invalid request. Please try again.');
            redirect('edit-case.php?id=' . $id);
        }
        
        $case_data = $this->case->getById($id);
        if (!$case_data) {
            flash_message('error', 'Case not found');
            redirect('cases.php');
        }
        
        // Sanitize and validate input
        $title = sanitize_input($_POST['title'] ?? '');
        $description = sanitize_input($_POST['description'] ?? '');
        $case_type = sanitize_input($_POST['case_type'] ?? '');
        $status = sanitize_input($_POST['status'] ?? '');
        $plaintiff = sanitize_input($_POST['plaintiff'] ?? '');
        $defendant = sanitize_input($_POST['defendant'] ?? '');
        $assigned_judge_id = !empty($_POST['assigned_judge_id']) ? (int)$_POST['assigned_judge_id'] : null;
        $priority = sanitize_input($_POST['priority'] ?? 'Normal');
        $confidential = isset($_POST['confidential']) ? 1 : 0;
        $tags = sanitize_input($_POST['tags'] ?? '');
        
        // Validation
        $errors = [];
        
        if (empty($title)) $errors[] = "Case title is required";
        if (empty($description)) $errors[] = "Case description is required";
        if (empty($case_type) || !in_array($case_type, ['Criminal', 'Civil', 'Family', 'Appeal', 'Administrative'])) {
            $errors[] = "Valid case type is required";
        }
        if (empty($status) || !in_array($status, ['Filed', 'Pending', 'In Hearing', 'Judged', 'Closed', 'Dismissed'])) {
            $errors[] = "Valid status is required";
        }
        if (empty($plaintiff)) $errors[] = "Plaintiff/Prosecutor is required";
        if (empty($defendant)) $errors[] = "Defendant is required";
        
        if (!empty($errors)) {
            flash_message('error', implode('<br>', $errors));
            redirect('edit-case.php?id=' . $id);
        }
        
        // Update case
        $this->case->id = $id;
        $this->case->title = $title;
        $this->case->description = $description;
        $this->case->case_type = $case_type;
        $this->case->status = $status;
        $this->case->priority = $priority;
        $this->case->plaintiff = $plaintiff;
        $this->case->defendant = $defendant;
        $this->case->assigned_judge_id = $assigned_judge_id;
        $this->case->confidential = $confidential;
        $this->case->tags = $tags;
        
        if ($this->case->update()) {
            flash_message('success', 'Case updated successfully');
            redirect('case-details.php?id=' . $id);
        } else {
            flash_message('error', 'Failed to update case. Please try again.');
            redirect('edit-case.php?id=' . $id);
        }
    }

    /**
     * Add case note
     */
    public function addNote() {
        AuthMiddleware::requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('cases.php');
        }
        
        $case_id = (int)($_POST['case_id'] ?? 0);
        $note_text = sanitize_input($_POST['note_text'] ?? '');
        $is_private = isset($_POST['is_private']) ? 1 : 0;
        
        if (empty($case_id) || empty($note_text)) {
            flash_message('error', 'Case ID and note text are required');
            redirect('case-details.php?id=' . $case_id);
        }
        
        // Check if user can access this case
        if (!AuthMiddleware::canAccessCase($case_id)) {
            flash_message('error', 'Access denied');
            redirect('cases.php');
        }
        
        if ($this->case->addNote($case_id, $note_text, $is_private)) {
            flash_message('success', 'Note added successfully');
        } else {
            flash_message('error', 'Failed to add note');
        }
        
        redirect('case-details.php?id=' . $case_id);
    }

    /**
     * Search cases (AJAX)
     */
    public function search() {
        AuthMiddleware::requireAuth();
        
        $query = sanitize_input($_GET['q'] ?? '');
        
        if (strlen($query) < 2) {
            echo json_encode(['html' => '<p>Please enter at least 2 characters</p>']);
            return;
        }
        
        $filters = ['search' => $query];
        $cases = $this->case->getAll(1, 10, $filters);
        
        $html = '';
        if (!empty($cases)) {
            foreach ($cases as $case) {
                $status_class = 'status-' . strtolower(str_replace(' ', '-', $case['status']));
                $html .= '<div class="card mb-2">';
                $html .= '<div class="card-body p-3">';
                $html .= '<div class="d-flex justify-content-between align-items-start">';
                $html .= '<div>';
                $html .= '<h6 class="mb-1"><a href="case-details.php?id=' . $case['id'] . '">' . htmlspecialchars($case['case_no']) . '</a></h6>';
                $html .= '<p class="mb-1">' . htmlspecialchars($case['title']) . '</p>';
                $html .= '<small class="text-muted">' . htmlspecialchars($case['plaintiff']) . ' vs ' . htmlspecialchars($case['defendant']) . '</small>';
                $html .= '</div>';
                $html .= '<div class="text-end">';
                $html .= '<span class="badge badge-status ' . $status_class . '">' . $case['status'] . '</span>';
                $html .= '<br><small class="text-muted">' . date('M j, Y', strtotime($case['filing_date'])) . '</small>';
                $html .= '</div>';
                $html .= '</div>';
                $html .= '</div>';
                $html .= '</div>';
            }
        } else {
            $html = '<p>No cases found matching your search.</p>';
        }
        
        echo json_encode(['html' => $html]);
    }

    /**
     * Get judge's cases
     */
    public function judgesCases() {
        AuthMiddleware::requireRole('judge');
        
        $status = sanitize_input($_GET['status'] ?? '');
        $cases = $this->case->getByJudge($_SESSION['user_id'], $status);
        
        $page_title = "My Cases";
        include 'views/cases/judge-cases.php';
    }

    /**
     * Handle document uploads during case creation
     */
    private function handleDocumentUploads($case_id) {
        if (empty($_FILES['documents']['name'][0])) return;
        
        $document_model = new Document($this->db);
        $upload_dir = UPLOAD_PATH . date('Y/m/');
        
        // Create upload directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $uploaded_count = 0;
        $total_files = count($_FILES['documents']['name']);
        
        for ($i = 0; $i < $total_files; $i++) {
            if ($_FILES['documents']['error'][$i] === UPLOAD_ERR_OK) {
                $file_name = $_FILES['documents']['name'][$i];
                $file_tmp = $_FILES['documents']['tmp_name'][$i];
                $file_size = $_FILES['documents']['size'][$i];
                $file_type = $_FILES['documents']['type'][$i];
                
                // Validate file
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                if (!in_array($file_ext, ALLOWED_FILE_TYPES)) {
                    continue;
                }
                
                if ($file_size > MAX_FILE_SIZE) {
                    continue;
                }
                
                // Generate unique filename
                $new_filename = uniqid() . '_' . $file_name;
                $file_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($file_tmp, $file_path)) {
                    // Save document record
                    $document_model->case_id = $case_id;
                    $document_model->title = pathinfo($file_name, PATHINFO_FILENAME);
                    $document_model->document_type = 'Filing';
                    $document_model->file_path = $file_path;
                    $document_model->original_name = $file_name;
                    $document_model->mime_type = $file_type;
                    $document_model->file_size = $file_size;
                    $document_model->file_hash = hash_file('sha256', $file_path);
                    $document_model->uploaded_by = $_SESSION['user_id'];
                    
                    if ($document_model->create()) {
                        $uploaded_count++;
                    }
                }
            }
        }
        
        if ($uploaded_count > 0) {
            flash_message('success', "$uploaded_count document(s) uploaded successfully");
        }
    }
}
?>