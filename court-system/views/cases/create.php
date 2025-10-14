<?php
/**
 * Create Case Page
 */

define('COURT_SYSTEM', true);
require_once '../../includes/auth.php';
require_once '../../models/Case.php';

// Check permissions
requirePermission('create_case');

$caseModel = new CaseModel();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate CSRF token
        if (!validateCSRF($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid security token. Please try again.');
        }
        
        // Prepare case data
        $caseData = [
            'case_no' => sanitize($_POST['case_no'] ?? ''),
            'title' => sanitize($_POST['title'] ?? ''),
            'description' => sanitize($_POST['description'] ?? ''),
            'case_type' => sanitize($_POST['case_type'] ?? ''),
            'filing_date' => sanitize($_POST['filing_date'] ?? ''),
            'plaintiff' => sanitize($_POST['plaintiff'] ?? ''),
            'defendant' => sanitize($_POST['defendant'] ?? ''),
            'assigned_judge_id' => !empty($_POST['assigned_judge_id']) ? intval($_POST['assigned_judge_id']) : null,
            'department_id' => !empty($_POST['department_id']) ? intval($_POST['department_id']) : null,
            'priority' => sanitize($_POST['priority'] ?? 'Normal'),
            'status' => sanitize($_POST['status'] ?? 'Filed'),
            'confidential' => isset($_POST['confidential']),
            'tags' => sanitize($_POST['tags'] ?? '')
        ];
        
        // Add participants if provided
        $participants = [];
        if (!empty($_POST['participants'])) {
            foreach ($_POST['participants'] as $participant) {
                if (!empty($participant['name'])) {
                    $participants[] = [
                        'name' => sanitize($participant['name']),
                        'role' => sanitize($participant['role']),
                        'contact_email' => sanitize($participant['contact_email'] ?? ''),
                        'contact_phone' => sanitize($participant['contact_phone'] ?? ''),
                        'address' => sanitize($participant['address'] ?? ''),
                        'notes' => sanitize($participant['notes'] ?? '')
                    ];
                }
            }
        }
        $caseData['participants'] = $participants;
        
        // Create case
        $caseId = $caseModel->create($caseData);
        
        redirect('view.php?id=' . $caseId, 'Case created successfully!', 'success');
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get form data
$judges = $caseModel->getAvailableJudges();
$departments = $caseModel->getDepartments();

$pageTitle = 'Create New Case';
include '../layouts/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="bi bi-plus-circle me-2"></i>Create New Case</h1>
    <a href="list.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to Cases
    </a>
</div>

<?php if ($error): ?>
<div class="alert alert-danger">
    <i class="bi bi-exclamation-triangle me-2"></i>
    <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<form method="POST" class="needs-validation loading-form" novalidate>
    <?= csrfInput() ?>
    
    <div class="row">
        <div class="col-lg-8">
            <!-- Basic Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Basic Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="case_no" class="form-label">Case Number</label>
                                <input type="text" class="form-control" id="case_no" name="case_no" 
                                       placeholder="Auto-generated if empty" 
                                       value="<?= htmlspecialchars($_POST['case_no'] ?? '') ?>">
                                <div class="form-text">Leave empty to auto-generate</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="filing_date" class="form-label">Filing Date *</label>
                                <input type="date" class="form-control" id="filing_date" name="filing_date" 
                                       value="<?= htmlspecialchars($_POST['filing_date'] ?? date('Y-m-d')) ?>" required>
                                <div class="invalid-feedback">Please provide a filing date.</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="title" class="form-label">Case Title *</label>
                        <input type="text" class="form-control" id="title" name="title" 
                               placeholder="e.g., State vs John Doe" 
                               value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required>
                        <div class="invalid-feedback">Please provide a case title.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description *</label>
                        <textarea class="form-control" id="description" name="description" rows="4" 
                                  placeholder="Detailed case description..." required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                        <div class="invalid-feedback">Please provide a case description.</div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="case_type" class="form-label">Case Type *</label>
                                <select class="form-select" id="case_type" name="case_type" required>
                                    <option value="">Select Type</option>
                                    <?php foreach (CASE_TYPES as $type): ?>
                                    <option value="<?= $type ?>" <?= ($_POST['case_type'] ?? '') === $type ? 'selected' : '' ?>>
                                        <?= $type ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Please select a case type.</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="priority" class="form-label">Priority</label>
                                <select class="form-select" id="priority" name="priority">
                                    <?php foreach (PRIORITY_LEVELS as $priority): ?>
                                    <option value="<?= $priority ?>" <?= ($_POST['priority'] ?? 'Normal') === $priority ? 'selected' : '' ?>>
                                        <?= $priority ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <?php foreach (CASE_STATUSES as $status): ?>
                                    <option value="<?= $status ?>" <?= ($_POST['status'] ?? 'Filed') === $status ? 'selected' : '' ?>>
                                        <?= $status ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Parties -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-people me-2"></i>Parties</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="plaintiff" class="form-label">Plaintiff/Prosecutor *</label>
                                <input type="text" class="form-control" id="plaintiff" name="plaintiff" 
                                       placeholder="Name of plaintiff or prosecutor" 
                                       value="<?= htmlspecialchars($_POST['plaintiff'] ?? '') ?>" required>
                                <div class="invalid-feedback">Please provide plaintiff/prosecutor name.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="defendant" class="form-label">Defendant *</label>
                                <input type="text" class="form-control" id="defendant" name="defendant" 
                                       placeholder="Name of defendant" 
                                       value="<?= htmlspecialchars($_POST['defendant'] ?? '') ?>" required>
                                <div class="invalid-feedback">Please provide defendant name.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Additional Participants -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-person-plus me-2"></i>Additional Participants</h5>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="addParticipant()">
                        <i class="bi bi-plus me-1"></i>Add Participant
                    </button>
                </div>
                <div class="card-body">
                    <div id="participants-container">
                        <!-- Participants will be added here dynamically -->
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Assignment -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-person-badge me-2"></i>Assignment</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="assigned_judge_id" class="form-label">Assigned Judge</label>
                        <select class="form-select" id="assigned_judge_id" name="assigned_judge_id">
                            <option value="">Select Judge</option>
                            <?php foreach ($judges as $judge): ?>
                            <option value="<?= $judge['id'] ?>" <?= ($_POST['assigned_judge_id'] ?? '') == $judge['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($judge['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="department_id" class="form-label">Department</label>
                        <select class="form-select" id="department_id" name="department_id">
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $dept): ?>
                            <option value="<?= $dept['id'] ?>" <?= ($_POST['department_id'] ?? '') == $dept['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dept['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Additional Options -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-gear me-2"></i>Options</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="confidential" name="confidential" 
                                   <?= isset($_POST['confidential']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="confidential">
                                <i class="bi bi-lock me-1"></i>Confidential Case
                            </label>
                            <div class="form-text">Restrict access to authorized personnel only</div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="tags" class="form-label">Tags</label>
                        <input type="text" class="form-control" id="tags" name="tags" 
                               placeholder="tag1, tag2, tag3" 
                               value="<?= htmlspecialchars($_POST['tags'] ?? '') ?>">
                        <div class="form-text">Comma-separated tags for organization</div>
                    </div>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="card">
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-court-primary">
                            <i class="bi bi-check-circle me-2"></i>Create Case
                        </button>
                        <a href="list.php" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle me-2"></i>Cancel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
let participantCount = 0;

function addParticipant() {
    participantCount++;
    const container = document.getElementById('participants-container');
    const participantHtml = `
        <div class="participant-item border rounded p-3 mb-3" id="participant-${participantCount}">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0">Participant ${participantCount}</h6>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeParticipant(${participantCount})">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-2">
                        <label class="form-label">Name</label>
                        <input type="text" class="form-control form-control-sm" name="participants[${participantCount}][name]" placeholder="Full name">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-2">
                        <label class="form-label">Role</label>
                        <input type="text" class="form-control form-control-sm" name="participants[${participantCount}][role]" placeholder="e.g., Defense Attorney, Witness">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-2">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control form-control-sm" name="participants[${participantCount}][contact_email]" placeholder="email@example.com">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-2">
                        <label class="form-label">Phone</label>
                        <input type="tel" class="form-control form-control-sm" name="participants[${participantCount}][contact_phone]" placeholder="Phone number">
                    </div>
                </div>
                <div class="col-12">
                    <div class="mb-2">
                        <label class="form-label">Address</label>
                        <textarea class="form-control form-control-sm" name="participants[${participantCount}][address]" rows="2" placeholder="Address"></textarea>
                    </div>
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', participantHtml);
}

function removeParticipant(id) {
    document.getElementById(`participant-${id}`).remove();
}

// Auto-generate case number preview
document.getElementById('case_no').addEventListener('blur', function() {
    if (!this.value) {
        this.placeholder = 'Will be auto-generated: <?= CASE_NUMBER_PREFIX ?>-<?= date('Y') ?>-####';
    }
});
</script>

<?php include '../layouts/footer.php'; ?>