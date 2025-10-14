<?php
/**
 * View Case Page
 */

define('COURT_SYSTEM', true);
require_once '../../includes/auth.php';
require_once '../../models/Case.php';

$caseId = intval($_GET['id'] ?? 0);
if (!$caseId) {
    redirect('list.php', 'Invalid case ID', 'error');
}

// Check if user can access this case
if (!canAccessCase($caseId)) {
    http_response_code(403);
    die('Access denied. You do not have permission to view this case.');
}

$caseModel = new CaseModel();
$case = $caseModel->getById($caseId);

if (!$case) {
    redirect('list.php', 'Case not found', 'error');
}

// Get related data
$participants = $caseModel->getParticipants($caseId);
$notes = $caseModel->getNotes($caseId);

// Get documents
$db = getDB();
$documents = $db->fetchAll("
    SELECT d.*, CONCAT(u.first_name, ' ', u.last_name) as uploaded_by_name
    FROM documents d
    JOIN users u ON d.uploaded_by = u.id
    WHERE d.case_id = ? AND d.is_active = 1
    ORDER BY d.uploaded_at DESC
", [$caseId]);

// Get hearings
$hearings = $db->fetchAll("
    SELECT h.*, CONCAT(j.first_name, ' ', j.last_name) as judge_name
    FROM hearings h
    JOIN users j ON h.judge_id = j.id
    WHERE h.case_id = ?
    ORDER BY h.hearing_date DESC
", [$caseId]);

// Get judgments
$judgments = $db->fetchAll("
    SELECT jd.*, CONCAT(j.first_name, ' ', j.last_name) as judge_name
    FROM judgments jd
    JOIN users j ON jd.judge_id = j.id
    WHERE jd.case_id = ?
    ORDER BY jd.judgment_date DESC
", [$caseId]);

$pageTitle = 'Case: ' . $case['case_no'];
include '../layouts/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1>
            <i class="bi bi-folder-open me-2"></i>
            <?= htmlspecialchars($case['case_no']) ?>
            <?php if ($case['confidential']): ?>
            <i class="bi bi-lock-fill text-warning ms-2" title="Confidential Case"></i>
            <?php endif; ?>
        </h1>
        <p class="text-muted mb-0"><?= htmlspecialchars($case['title']) ?></p>
    </div>
    <div class="btn-group">
        <a href="list.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to Cases
        </a>
        <?php if (hasPermission('edit_case') || ($case['assigned_judge_id'] == $_SESSION['user_id'])): ?>
        <a href="edit.php?id=<?= $case['id'] ?>" class="btn btn-outline-primary">
            <i class="bi bi-pencil me-2"></i>Edit
        </a>
        <?php endif; ?>
        <?php if (hasPermission('upload_document')): ?>
        <a href="../documents/upload.php?case_id=<?= $case['id'] ?>" class="btn btn-court-primary">
            <i class="bi bi-upload me-2"></i>Upload Document
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <!-- Case Details -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Case Details</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless table-sm">
                            <tr>
                                <th width="120">Case Type:</th>
                                <td><span class="badge bg-info"><?= htmlspecialchars($case['case_type']) ?></span></td>
                            </tr>
                            <tr>
                                <th>Status:</th>
                                <td><?= getCaseStatusBadge($case['status']) ?></td>
                            </tr>
                            <tr>
                                <th>Priority:</th>
                                <td><?= getPriorityBadge($case['priority']) ?></td>
                            </tr>
                            <tr>
                                <th>Filing Date:</th>
                                <td><?= formatDate($case['filing_date']) ?></td>
                            </tr>
                            <tr>
                                <th>Created:</th>
                                <td><?= formatDateTime($case['created_at']) ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless table-sm">
                            <tr>
                                <th width="120">Assigned Judge:</th>
                                <td>
                                    <?php if ($case['judge_name']): ?>
                                        <?= htmlspecialchars($case['judge_name']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">Unassigned</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Department:</th>
                                <td>
                                    <?php if ($case['department_name']): ?>
                                        <?= htmlspecialchars($case['department_name']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">Not assigned</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Created By:</th>
                                <td><?= htmlspecialchars($case['created_by_name']) ?></td>
                            </tr>
                            <tr>
                                <th>Last Updated:</th>
                                <td><?= formatDateTime($case['updated_at']) ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <hr>
                
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="bi bi-person me-2"></i>Plaintiff/Prosecutor</h6>
                        <p><?= htmlspecialchars($case['plaintiff']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="bi bi-person me-2"></i>Defendant</h6>
                        <p><?= htmlspecialchars($case['defendant']) ?></p>
                    </div>
                </div>
                
                <h6><i class="bi bi-file-text me-2"></i>Description</h6>
                <p><?= nl2br(htmlspecialchars($case['description'])) ?></p>
                
                <?php if ($case['tags']): ?>
                <h6><i class="bi bi-tags me-2"></i>Tags</h6>
                <p>
                    <?php foreach (explode(',', $case['tags']) as $tag): ?>
                    <span class="badge bg-secondary me-1"><?= htmlspecialchars(trim($tag)) ?></span>
                    <?php endforeach; ?>
                </p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Participants -->
        <?php if (!empty($participants)): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-people me-2"></i>Participants</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Role</th>
                                <th>Contact</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($participants as $participant): ?>
                            <tr>
                                <td><?= htmlspecialchars($participant['name']) ?></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($participant['role']) ?></span></td>
                                <td>
                                    <?php if ($participant['contact_email']): ?>
                                    <a href="mailto:<?= htmlspecialchars($participant['contact_email']) ?>">
                                        <?= htmlspecialchars($participant['contact_email']) ?>
                                    </a><br>
                                    <?php endif; ?>
                                    <?php if ($participant['contact_phone']): ?>
                                    <a href="tel:<?= htmlspecialchars($participant['contact_phone']) ?>">
                                        <?= htmlspecialchars($participant['contact_phone']) ?>
                                    </a>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($participant['notes']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Documents -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-file-earmark-text me-2"></i>Documents (<?= count($documents) ?>)</h5>
                <?php if (hasPermission('upload_document')): ?>
                <a href="../documents/upload.php?case_id=<?= $case['id'] ?>" class="btn btn-sm btn-court-primary">
                    <i class="bi bi-plus me-1"></i>Upload
                </a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (empty($documents)): ?>
                <div class="text-center py-3">
                    <i class="bi bi-file-earmark-x display-6 text-muted"></i>
                    <p class="text-muted mt-2">No documents uploaded yet.</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Document</th>
                                <th>Type</th>
                                <th>Size</th>
                                <th>Uploaded By</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($documents as $doc): ?>
                            <tr>
                                <td>
                                    <i class="bi bi-file-earmark-text me-2"></i>
                                    <?= htmlspecialchars($doc['title']) ?>
                                    <?php if ($doc['visibility'] !== 'public'): ?>
                                    <i class="bi bi-lock text-warning ms-1" title="<?= ucfirst($doc['visibility']) ?>"></i>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-info"><?= htmlspecialchars($doc['document_type']) ?></span></td>
                                <td><?= formatFileSize($doc['file_size']) ?></td>
                                <td><?= htmlspecialchars($doc['uploaded_by_name']) ?></td>
                                <td><?= formatDateTime($doc['uploaded_at']) ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="../documents/view.php?id=<?= $doc['id'] ?>" 
                                           class="btn btn-outline-primary" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="../documents/download.php?id=<?= $doc['id'] ?>" 
                                           class="btn btn-outline-success" title="Download">
                                            <i class="bi bi-download"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Hearings -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-calendar-event me-2"></i>Hearings (<?= count($hearings) ?>)</h5>
                <?php if (hasPermission('schedule_hearing')): ?>
                <a href="../hearings/create.php?case_id=<?= $case['id'] ?>" class="btn btn-sm btn-court-primary">
                    <i class="bi bi-plus me-1"></i>Schedule
                </a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (empty($hearings)): ?>
                <div class="text-center py-3">
                    <i class="bi bi-calendar-x display-6 text-muted"></i>
                    <p class="text-muted mt-2">No hearings scheduled yet.</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Type</th>
                                <th>Court Room</th>
                                <th>Judge</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($hearings as $hearing): ?>
                            <tr>
                                <td><?= formatDateTime($hearing['hearing_date']) ?></td>
                                <td><span class="badge bg-info"><?= htmlspecialchars($hearing['hearing_type']) ?></span></td>
                                <td><?= htmlspecialchars($hearing['court_room']) ?></td>
                                <td><?= htmlspecialchars($hearing['judge_name']) ?></td>
                                <td>
                                    <?php
                                    $statusClass = [
                                        'Scheduled' => 'bg-primary',
                                        'Rescheduled' => 'bg-warning',
                                        'In Progress' => 'bg-info',
                                        'Completed' => 'bg-success',
                                        'Cancelled' => 'bg-danger'
                                    ][$hearing['status']] ?? 'bg-secondary';
                                    ?>
                                    <span class="badge <?= $statusClass ?>"><?= $hearing['status'] ?></span>
                                </td>
                                <td>
                                    <a href="../hearings/view.php?id=<?= $hearing['id'] ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Judgments -->
        <?php if (!empty($judgments)): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-gavel me-2"></i>Judgments</h5>
            </div>
            <div class="card-body">
                <?php foreach ($judgments as $judgment): ?>
                <div class="border rounded p-3 mb-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h6 class="mb-1"><?= formatDate($judgment['judgment_date']) ?></h6>
                            <small class="text-muted">By <?= htmlspecialchars($judgment['judge_name']) ?></small>
                        </div>
                        <span class="badge bg-success"><?= htmlspecialchars($judgment['outcome']) ?></span>
                    </div>
                    <p class="mb-2"><?= nl2br(htmlspecialchars(substr($judgment['judgment_text'], 0, 200))) ?>...</p>
                    <a href="../judgments/view.php?id=<?= $judgment['id'] ?>" class="btn btn-sm btn-outline-primary">
                        View Full Judgment
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="col-lg-4">
        <!-- Quick Actions -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-lightning me-2"></i>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <?php if (hasPermission('upload_document')): ?>
                    <a href="../documents/upload.php?case_id=<?= $case['id'] ?>" class="btn btn-outline-primary">
                        <i class="bi bi-upload me-2"></i>Upload Document
                    </a>
                    <?php endif; ?>
                    
                    <?php if (hasPermission('schedule_hearing')): ?>
                    <a href="../hearings/create.php?case_id=<?= $case['id'] ?>" class="btn btn-outline-success">
                        <i class="bi bi-calendar-plus me-2"></i>Schedule Hearing
                    </a>
                    <?php endif; ?>
                    
                    <?php if (hasPermission('add_judgment') && $case['assigned_judge_id'] == $_SESSION['user_id']): ?>
                    <a href="../judgments/create.php?case_id=<?= $case['id'] ?>" class="btn btn-outline-warning">
                        <i class="bi bi-gavel me-2"></i>Add Judgment
                    </a>
                    <?php endif; ?>
                    
                    <button type="button" class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#addNoteModal">
                        <i class="bi bi-journal-plus me-2"></i>Add Note
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Case Notes -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-journal-text me-2"></i>Notes (<?= count($notes) ?>)</h5>
            </div>
            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                <?php if (empty($notes)): ?>
                <div class="text-center py-3">
                    <i class="bi bi-journal-x text-muted"></i>
                    <p class="text-muted mt-2 mb-0">No notes yet.</p>
                </div>
                <?php else: ?>
                <?php foreach ($notes as $note): ?>
                <div class="border-bottom pb-2 mb-2">
                    <div class="d-flex justify-content-between align-items-start">
                        <small class="text-muted"><?= htmlspecialchars($note['author_name']) ?></small>
                        <small class="text-muted"><?= timeAgo($note['created_at']) ?></small>
                    </div>
                    <?php if ($note['title']): ?>
                    <h6 class="mb-1"><?= htmlspecialchars($note['title']) ?></h6>
                    <?php endif; ?>
                    <p class="mb-1 small"><?= nl2br(htmlspecialchars($note['content'])) ?></p>
                    <span class="badge badge-sm bg-secondary"><?= ucfirst($note['note_type']) ?></span>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add Note Modal -->
<div class="modal fade" id="addNoteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="add-note.php">
                <input type="hidden" name="case_id" value="<?= $case['id'] ?>">
                <?= csrfInput() ?>
                
                <div class="modal-header">
                    <h5 class="modal-title">Add Case Note</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="note_title" class="form-label">Title (Optional)</label>
                        <input type="text" class="form-control" id="note_title" name="title" placeholder="Note title">
                    </div>
                    
                    <div class="mb-3">
                        <label for="note_content" class="form-label">Content *</label>
                        <textarea class="form-control" id="note_content" name="content" rows="4" placeholder="Enter your note..." required></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="note_type" class="form-label">Type</label>
                                <select class="form-select" id="note_type" name="note_type">
                                    <option value="general">General</option>
                                    <option value="private">Private</option>
                                    <option value="court_order">Court Order</option>
                                    <option value="reminder">Reminder</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="visibility" class="form-label">Visibility</label>
                                <select class="form-select" id="visibility" name="visibility">
                                    <option value="staff_only">Staff Only</option>
                                    <option value="judge_only">Judge Only</option>
                                    <option value="public">Public</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-court-primary">Add Note</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>