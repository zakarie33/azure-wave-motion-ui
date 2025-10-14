<?php
require_once 'config/config.php';
include 'views/layouts/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>
        <i class="bi bi-plus-circle"></i>
        New Case Registration
    </h2>
    <a href="cases.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to Cases
    </a>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Case Information</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="new-case.php" id="caseForm" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo AuthMiddleware::generateCSRF(); ?>">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="case_no" class="form-label">Case Number</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="case_no" 
                                   name="case_no" 
                                   placeholder="Auto-generated if left empty"
                                   value="<?php echo generate_case_number(); ?>" 
                                   readonly>
                            <div class="form-text">Case number will be auto-generated</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="case_type" class="form-label">Case Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="case_type" name="case_type" required>
                                <option value="">Select Case Type</option>
                                <option value="Criminal">Criminal</option>
                                <option value="Civil">Civil</option>
                                <option value="Family">Family</option>
                                <option value="Appeal">Appeal</option>
                                <option value="Administrative">Administrative</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="title" class="form-label">Case Title <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control" 
                               id="title" 
                               name="title" 
                               placeholder="e.g., State vs John Doe" 
                               required>
                        <div class="form-text">Brief descriptive title of the case</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Case Description <span class="text-danger">*</span></label>
                        <textarea class="form-control" 
                                  id="description" 
                                  name="description" 
                                  rows="4" 
                                  placeholder="Detailed summary of the case..." 
                                  required></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="plaintiff" class="form-label">Plaintiff/Prosecutor <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control" 
                                   id="plaintiff" 
                                   name="plaintiff" 
                                   placeholder="Name of plaintiff or prosecutor" 
                                   required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="defendant" class="form-label">Defendant <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control" 
                                   id="defendant" 
                                   name="defendant" 
                                   placeholder="Name of defendant" 
                                   required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="filing_date" class="form-label">Filing Date</label>
                            <input type="date" 
                                   class="form-control" 
                                   id="filing_date" 
                                   name="filing_date" 
                                   value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="priority" class="form-label">Priority</label>
                            <select class="form-select" id="priority" name="priority">
                                <option value="Normal" selected>Normal</option>
                                <option value="High">High</option>
                                <option value="Low">Low</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="assigned_judge_id" class="form-label">Assigned Judge</label>
                            <select class="form-select" id="assigned_judge_id" name="assigned_judge_id">
                                <option value="">Select Judge (Optional)</option>
                                <?php foreach ($judges as $judge): ?>
                                <option value="<?php echo $judge['id']; ?>">
                                    <?php echo htmlspecialchars($judge['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Judge can be assigned later</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="tags" class="form-label">Tags</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="tags" 
                                   name="tags" 
                                   placeholder="comma, separated, tags">
                            <div class="form-text">Optional tags for categorization</div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="confidential" name="confidential">
                            <label class="form-check-label" for="confidential">
                                <i class="bi bi-lock"></i> Confidential Case
                            </label>
                            <div class="form-text">Restricts viewing to assigned roles only</div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h6 class="mb-3">
                        <i class="bi bi-paperclip"></i>
                        Attach Documents (Optional)
                    </h6>
                    
                    <div class="mb-3">
                        <label for="documents" class="form-label">Case Documents</label>
                        <input type="file" 
                               class="form-control" 
                               id="documents" 
                               name="documents[]" 
                               multiple 
                               accept=".pdf,.docx,.doc,.jpg,.jpeg,.png"
                               onchange="validateFileUpload(this)">
                        <div class="form-text">
                            Allowed types: PDF, DOCX, DOC, JPG, PNG. Max size: <?php echo (MAX_FILE_SIZE / 1024 / 1024); ?>MB per file.
                        </div>
                        <div id="file-preview" class="mt-2"></div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-outline-secondary" onclick="history.back()">
                            <i class="bi bi-x-circle"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="bi bi-check-circle"></i> Register Case
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Help Panel -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-info-circle"></i>
                    Help & Guidelines
                </h6>
            </div>
            <div class="card-body">
                <h6>Case Registration Tips:</h6>
                <ul class="small">
                    <li>Case number is auto-generated based on current year</li>
                    <li>Use clear, descriptive titles</li>
                    <li>Include all relevant parties</li>
                    <li>Mark sensitive cases as confidential</li>
                    <li>Attach initial documents if available</li>
                </ul>
                
                <hr>
                
                <h6>Case Types:</h6>
                <ul class="small">
                    <li><strong>Criminal:</strong> Criminal proceedings</li>
                    <li><strong>Civil:</strong> Civil disputes</li>
                    <li><strong>Family:</strong> Family law matters</li>
                    <li><strong>Appeal:</strong> Appeal cases</li>
                    <li><strong>Administrative:</strong> Administrative matters</li>
                </ul>
                
                <hr>
                
                <h6>Priority Levels:</h6>
                <ul class="small">
                    <li><strong>High:</strong> Urgent cases requiring immediate attention</li>
                    <li><strong>Normal:</strong> Standard processing timeline</li>
                    <li><strong>Low:</strong> Non-urgent cases</li>
                </ul>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-clock-history"></i>
                    Recent Cases
                </h6>
            </div>
            <div class="card-body">
                <?php
                // Get recent cases for reference
                $recent_query = "SELECT case_no, title, case_type FROM cases ORDER BY created_at DESC LIMIT 3";
                $stmt = $conn->prepare($recent_query);
                $stmt->execute();
                $recent_cases = $stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>
                
                <?php if (!empty($recent_cases)): ?>
                    <?php foreach ($recent_cases as $case): ?>
                    <div class="mb-2">
                        <small class="text-muted"><?php echo $case['case_no']; ?></small><br>
                        <small><?php echo htmlspecialchars($case['title']); ?></small>
                        <span class="badge bg-secondary ms-1"><?php echo $case['case_type']; ?></span>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                <p class="text-muted small">No recent cases</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const form = document.getElementById('caseForm');
    const submitBtn = document.getElementById('submitBtn');
    
    form.addEventListener('submit', function(e) {
        if (!validateForm('caseForm')) {
            e.preventDefault();
            return false;
        }
        
        // Show loading state
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="loading-spinner"></span> Registering...';
    });
    
    // File preview
    document.getElementById('documents').addEventListener('change', function() {
        const files = this.files;
        const preview = document.getElementById('file-preview');
        preview.innerHTML = '';
        
        if (files.length > 0) {
            const list = document.createElement('ul');
            list.className = 'list-unstyled';
            
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const item = document.createElement('li');
                item.className = 'small text-muted mb-1';
                item.innerHTML = `<i class="bi bi-file-earmark"></i> ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)`;
                list.appendChild(item);
            }
            
            preview.appendChild(list);
        }
    });
    
    // Auto-save draft functionality
    enableAutoSave('caseForm', 'api/save-case-draft.php');
    
    // Real-time validation
    const requiredFields = form.querySelectorAll('[required]');
    requiredFields.forEach(field => {
        field.addEventListener('blur', function() {
            if (!this.value.trim()) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });
        
        field.addEventListener('input', function() {
            if (this.value.trim()) {
                this.classList.remove('is-invalid');
            }
        });
    });
});

// Generate case number preview
function generateCaseNumber() {
    const caseType = document.getElementById('case_type').value;
    if (caseType) {
        // This would typically make an AJAX call to generate a preview
        // For now, we'll just show the current format
        const year = new Date().getFullYear();
        const preview = `COURT-${year}-XXXX`;
        document.getElementById('case_no').value = preview;
    }
}

// Case type change handler
document.getElementById('case_type').addEventListener('change', generateCaseNumber);
</script>

<?php include 'views/layouts/footer.php'; ?>