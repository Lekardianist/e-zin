<?php
require_once 'includes/header.php';
require_once 'config/database.php';

// Handle status update
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $action = $_GET['action'];
    
    if (in_array($action, ['approve', 'reject'])) {
        $status = $action == 'approve' ? 'approved' : 'rejected';
        $stmt = $pdo->prepare("UPDATE permissions SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        header('Location: permissions.php?updated=1');
        exit();
    }
}

// Handle search and filter
$search = isset($_GET['search']) ? $_GET['search'] : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

$query = "SELECT p.*, e.name, e.role, e.email FROM permissions p 
          JOIN employees e ON p.user_id = e.user_id WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (p.name LIKE ? OR p.user_id LIKE ? OR p.permission_type LIKE ? OR e.email LIKE ? OR e.name LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_fill(0, 5, $searchTerm);
}

if ($filter != 'all') {
    $query .= " AND p.role = ?";
    $params[] = $filter;
}

$query .= " ORDER BY p.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$permissions = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Permission Management</h1>
</div>

<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">All Permission Requests</h5>
                <div class="mt-2">
                    <a href="?filter=all" class="badge bg-secondary text-decoration-none <?php echo $filter == 'all' ? 'active' : ''; ?>">All</a>
                    <a href="?filter=student" class="badge bg-warning text-decoration-none <?php echo $filter == 'student' ? 'active' : ''; ?>">Student</a>
                    <a href="?filter=staff" class="badge bg-success text-decoration-none <?php echo $filter == 'staff' ? 'active' : ''; ?>">Staff</a>
                    <a href="?filter=lecturer" class="badge bg-primary text-decoration-none <?php echo $filter == 'lecturer' ? 'active' : ''; ?>">Lecturer</a>
                </div>
            </div>
            <div class="text-end">
                <small class="text-muted">
                    <i class="bi bi-info-circle"></i> 
                    Showing <?php echo count($permissions); ?> permission requests
                </small>
            </div>
        </div>
    </div>
    <div class="card-body">
        <?php if(isset($_GET['updated'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i> Permission status updated successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <form method="GET" action="" class="mb-4">
            <div class="row g-2">
                <div class="col-md-9">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" name="search" 
                               placeholder="Search by name, email, permission type, or details..."
                               value="<?php echo htmlspecialchars($search); ?>">
                        <input type="hidden" name="filter" value="<?php echo $filter; ?>">
                        <button class="btn btn-primary" type="submit">Search</button>
                        <?php if(!empty($search)): ?>
                            <a href="permissions.php?filter=<?php echo $filter; ?>" class="btn btn-secondary">Clear</a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="filter" onchange="this.form.submit()">
                        <option value="all" <?php echo $filter == 'all' ? 'selected' : ''; ?>>All Roles</option>
                        <option value="student" <?php echo $filter == 'student' ? 'selected' : ''; ?>>Student</option>
                        <option value="staff" <?php echo $filter == 'staff' ? 'selected' : ''; ?>>Staff</option>
                        <option value="lecturer" <?php echo $filter == 'lecturer' ? 'selected' : ''; ?>>Lecturer</option>
                    </select>
                </div>
            </div>
        </form>
        
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Permission Type</th>
                        <th>Status</th>
                        <th>Attachment</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($permissions) > 0): ?>
                        <?php foreach($permissions as $perm): ?>
                        <tr>
                            <td>
                                <small><?php echo date('d M Y', strtotime($perm['created_at'])); ?></small><br>
                                <small class="text-muted"><?php echo date('H:i', strtotime($perm['created_at'])); ?></small>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($perm['name']); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($perm['email']); ?></small>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $perm['role'] == 'lecturer' ? 'primary' : ($perm['role'] == 'staff' ? 'success' : 'warning'); ?>">
                                    <i class="bi bi-<?php echo $perm['role'] == 'lecturer' ? 'person' : ($perm['role'] == 'staff' ? 'briefcase' : 'person-badge'); ?>"></i>
                                    <?php echo ucfirst($perm['role']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-<?php 
                                        echo $perm['permission_type'] == 'Sakit' ? 'thermometer-sun' : 
                                               ($perm['permission_type'] == 'Izin' ? 'calendar-check' : 
                                               ($perm['permission_type'] == 'Cuti' ? 'umbrella' : 'clipboard-check')); 
                                    ?> me-2 text-primary"></i>
                                    <?php echo $perm['permission_type']; ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $perm['status'] == 'approved' ? 'success' : ($perm['status'] == 'rejected' ? 'danger' : 'warning'); ?>">
                                    <i class="bi bi-<?php echo $perm['status'] == 'approved' ? 'check-circle' : ($perm['status'] == 'rejected' ? 'x-circle' : 'clock'); ?>"></i>
                                    <?php echo ucfirst($perm['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if(!empty($perm['attachment_file'])): ?>
                                    <span class="badge bg-info">
                                        <i class="bi bi-paperclip"></i> Attached
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">None</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <!-- View Button -->
                                    <button class="btn btn-info" data-bs-toggle="modal" 
                                            data-bs-target="#detailModal<?php echo $perm['id']; ?>">
                                        <i class="bi bi-eye"></i> View
                                    </button>
                                    
                                    <!-- Action Buttons (only for pending) -->
                                    <?php if($perm['status'] == 'pending'): ?>
                                        <a href="?action=approve&id=<?php echo $perm['id']; ?>&filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>" 
                                           class="btn btn-success" 
                                           onclick="return confirm('Approve this permission request?')">
                                            <i class="bi bi-check-circle"></i> Approve
                                        </a>
                                        <a href="?action=reject&id=<?php echo $perm['id']; ?>&filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>" 
                                           class="btn btn-danger"
                                           onclick="return confirm('Reject this permission request?')">
                                            <i class="bi bi-x-circle"></i> Reject
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        
                        <!-- Modal for details -->
                        <div class="modal fade" id="detailModal<?php echo $perm['id']; ?>" tabindex="-1" aria-labelledby="detailModalLabel<?php echo $perm['id']; ?>" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header bg-primary text-white">
                                        <h5 class="modal-title" id="detailModalLabel<?php echo $perm['id']; ?>">
                                            <i class="bi bi-clipboard-check"></i> Permission Details
                                        </h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <!-- User Information -->
                                        <div class="card mb-4">
                                            <div class="card-header bg-light">
                                                <h6 class="mb-0"><i class="bi bi-person-circle"></i> Applicant Information</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <p><strong>Name:</strong> <?php echo htmlspecialchars($perm['name']); ?></p>
                                                        <p><strong>Email:</strong> <?php echo htmlspecialchars($perm['email']); ?></p>
                                                        <p><strong>User ID:</strong> <?php echo $perm['user_id']; ?></p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <p><strong>Role:</strong> 
                                                            <span class="badge bg-<?php echo $perm['role'] == 'lecturer' ? 'primary' : ($perm['role'] == 'staff' ? 'success' : 'warning'); ?>">
                                                                <?php echo ucfirst($perm['role']); ?>
                                                            </span>
                                                        </p>
                                                        <p><strong>Request Date:</strong> <?php echo date('d F Y H:i', strtotime($perm['created_at'])); ?></p>
                                                        <p><strong>Status:</strong> 
                                                            <span class="badge bg-<?php echo $perm['status'] == 'approved' ? 'success' : ($perm['status'] == 'rejected' ? 'danger' : 'warning'); ?>">
                                                                <?php echo ucfirst($perm['status']); ?>
                                                            </span>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Permission Details -->
                                        <div class="card mb-4">
                                            <div class="card-header bg-light">
                                                <h6 class="mb-0"><i class="bi bi-clipboard-data"></i> Permission Information</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <p><strong>Permission Type:</strong> 
                                                            <span class="badge bg-primary">
                                                                <i class="bi bi-<?php 
                                                                    echo $perm['permission_type'] == 'Sakit' ? 'thermometer-sun' : 
                                                                           ($perm['permission_type'] == 'Izin' ? 'calendar-check' : 
                                                                           ($perm['permission_type'] == 'Cuti' ? 'umbrella' : 'clipboard-check')); 
                                                                ?>"></i>
                                                                <?php echo $perm['permission_type']; ?>
                                                            </span>
                                                        </p>
                                                        <?php if($perm['start_date']): ?>
                                                            <p><strong>Start Date:</strong> <?php echo date('d F Y', strtotime($perm['start_date'])); ?></p>
                                                        <?php endif; ?>
                                                        <?php if($perm['end_date']): ?>
                                                            <p><strong>End Date:</strong> <?php echo date('d F Y', strtotime($perm['end_date'])); ?></p>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <?php if($perm['start_date'] && $perm['end_date']): ?>
                                                            <p><strong>Duration:</strong> 
                                                                <?php 
                                                                    $start = new DateTime($perm['start_date']);
                                                                    $end = new DateTime($perm['end_date']);
                                                                    $interval = $start->diff($end);
                                                                    echo $interval->days + 1 . ' day(s)';
                                                                ?>
                                                            </p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                
                                                <div class="mt-3">
                                                    <p><strong>Details:</strong></p>
                                                    <div class="card bg-light">
                                                        <div class="card-body">
                                                            <?php echo nl2br(htmlspecialchars($perm['detail_permission'])); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Attachment Section -->
                                        <?php if(!empty($perm['attachment_file'])): ?>
                                        <div class="card">
                                            <div class="card-header bg-light">
                                                <h6 class="mb-0"><i class="bi bi-paperclip"></i> Attachment / Evidence</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-3 text-center">
                                                        <?php 
                                                        $file_ext = pathinfo($perm['attachment_file'], PATHINFO_EXTENSION);
                                                        $file_name = $perm['attachment_file'];
                                                        $file_path = 'uploads/permissions/' . $file_name;
                                                        $file_exists = file_exists($file_path);
                                                        
                                                        $icon_class = '';
                                                        $file_type = 'File';
                                                        
                                                        if (in_array(strtolower($file_ext), ['jpg', 'jpeg', 'png', 'gif'])) {
                                                            $icon_class = 'bi-file-image text-primary';
                                                            $file_type = 'Image';
                                                        } elseif (strtolower($file_ext) == 'pdf') {
                                                            $icon_class = 'bi-file-pdf text-danger';
                                                            $file_type = 'PDF';
                                                        } elseif (in_array(strtolower($file_ext), ['doc', 'docx'])) {
                                                            $icon_class = 'bi-file-word text-primary';
                                                            $file_type = 'Document';
                                                        } else {
                                                            $icon_class = 'bi-file-earmark text-secondary';
                                                        }
                                                        ?>
                                                        <i class="bi <?php echo $icon_class; ?> display-4 mb-3"></i>
                                                        <p class="mb-1"><strong><?php echo $file_type; ?></strong></p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h6><?php echo $file_name; ?></h6>
                                                        <p class="text-muted">
                                                            <small>
                                                                <i class="bi bi-filetype-<?php echo strtolower($file_ext); ?>"></i>
                                                                <?php echo strtoupper($file_ext); ?> • 
                                                                <?php echo !empty($perm['file_size']) ? round($perm['file_size'] / 1024, 1) . ' KB' : 'Unknown size'; ?>
                                                            </small>
                                                        </p>
                                                        <p class="text-muted">
                                                            <small>
                                                                <i class="bi bi-calendar"></i>
                                                                Uploaded: <?php echo date('d M Y H:i', strtotime($perm['created_at'])); ?>
                                                            </small>
                                                        </p>
                                                    </div>
                                                    <div class="col-md-3 text-end">
                                                        <?php if($file_exists): ?>
                                                            <?php if (in_array(strtolower($file_ext), ['jpg', 'jpeg', 'png', 'gif'])): ?>
                                                                <!-- Button to view image in modal -->
                                                                <button type="button" class="btn btn-primary mb-2" 
                                                                        data-bs-toggle="modal" 
                                                                        data-bs-target="#imageModal<?php echo $perm['id']; ?>">
                                                                    <i class="bi bi-eye"></i> View Image
                                                                </button><br>
                                                            <?php endif; ?>
                                                            <!-- Download button -->
                                                            <a href="<?php echo $file_path; ?>" 
                                                               class="btn btn-success" 
                                                               target="_blank"
                                                               download="<?php echo $file_name; ?>">
                                                                <i class="bi bi-download"></i> Download
                                                            </a>
                                                        <?php else: ?>
                                                            <span class="badge bg-warning">File not found</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php else: ?>
                                        <div class="card">
                                            <div class="card-body text-center">
                                                <i class="bi bi-paperclip display-4 text-muted mb-3"></i>
                                                <p class="text-muted mb-0">No attachment provided</p>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                            <i class="bi bi-x-circle"></i> Close
                                        </button>
                                        <?php if($perm['status'] == 'pending'): ?>
                                            <a href="?action=approve&id=<?php echo $perm['id']; ?>&filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>" 
                                               class="btn btn-success" 
                                               onclick="return confirm('Approve this permission request?')">
                                                <i class="bi bi-check-circle"></i> Approve
                                            </a>
                                            <a href="?action=reject&id=<?php echo $perm['id']; ?>&filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>" 
                                               class="btn btn-danger"
                                               onclick="return confirm('Reject this permission request?')">
                                                <i class="bi bi-x-circle"></i> Reject
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Image Preview Modal -->
                        <?php if(!empty($perm['attachment_file']) && $file_exists && in_array(strtolower($file_ext), ['jpg', 'jpeg', 'png', 'gif'])): ?>
                        <div class="modal fade" id="imageModal<?php echo $perm['id']; ?>" tabindex="-1" 
                             aria-labelledby="imageModalLabel<?php echo $perm['id']; ?>" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered modal-xl">
                                <div class="modal-content">
                                    <div class="modal-header bg-dark text-white">
                                        <h5 class="modal-title" id="imageModalLabel<?php echo $perm['id']; ?>">
                                            <i class="bi bi-image"></i> Evidence Preview
                                        </h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body text-center p-0 bg-dark">
                                        <img src="<?php echo $file_path; ?>" 
                                             alt="Permission Evidence: <?php echo $perm['permission_type']; ?>" 
                                             class="img-fluid"
                                             style="max-height: 70vh; object-fit: contain; cursor: zoom-in;"
                                             id="evidenceImage<?php echo $perm['id']; ?>">
                                        <div class="text-white p-3 bg-dark">
                                            <p class="mb-0">
                                                <i class="bi bi-info-circle"></i>
                                                <?php echo $file_name; ?> 
                                                (<?php echo !empty($perm['file_size']) ? round($perm['file_size'] / 1024, 1) . ' KB' : 'Unknown size'; ?>)
                                            </p>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <a href="<?php echo $file_path; ?>" 
                                           class="btn btn-primary" 
                                           target="_blank"
                                           download="<?php echo $file_name; ?>">
                                            <i class="bi bi-download"></i> Download
                                        </a>
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                            <i class="bi bi-x-circle"></i> Close
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <script>
                        // Image zoom functionality
                        document.addEventListener('DOMContentLoaded', function() {
                            const image = document.getElementById('evidenceImage<?php echo $perm['id']; ?>');
                            if (image) {
                                image.addEventListener('click', function() {
                                    this.classList.toggle('img-zoomed');
                                    if (this.classList.contains('img-zoomed')) {
                                        this.style.cursor = 'zoom-out';
                                        this.style.transform = 'scale(1.5)';
                                        this.style.transition = 'transform 0.3s ease';
                                    } else {
                                        this.style.cursor = 'zoom-in';
                                        this.style.transform = 'scale(1)';
                                    }
                                });
                            }
                        });
                        </script>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <i class="bi bi-clipboard-x display-1 text-muted"></i>
                                <h4 class="mt-3">No Permission Requests Found</h4>
                                <p class="text-muted">
                                    <?php if(!empty($search) || $filter != 'all'): ?>
                                        Try changing your search or filter criteria.
                                    <?php else: ?>
                                        There are no permission requests at the moment.
                                    <?php endif; ?>
                                </p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
/* Custom styling for admin permissions page */
.badge.active {
    transform: scale(1.1);
    box-shadow: 0 0 0 2px rgba(0,0,0,0.1);
}

.table-hover tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.05);
}

.btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

.modal-content {
    border-radius: 10px;
    border: none;
}

.modal-header.bg-primary {
    border-radius: 10px 10px 0 0;
}

.card .card-header.bg-light {
    background-color: #f8f9fa !important;
    border-bottom: 1px solid #dee2e6;
}

/* Image zoom styles */
.img-zoomed {
    transform: scale(1.5);
    cursor: zoom-out !important;
}

.modal-xl img {
    transition: transform 0.3s ease;
}

/* Attachment icon colors */
.bi-file-image {
    color: #0d6efd;
}

.bi-file-pdf {
    color: #dc3545;
}

.bi-file-word {
    color: #0d6efd;
}

.bi-file-earmark {
    color: #6c757d;
}
</style>

<script>
// Auto-hide success alert
document.addEventListener('DOMContentLoaded', function() {
    const successAlert = document.querySelector('.alert-success');
    if (successAlert) {
        setTimeout(() => {
            successAlert.classList.remove('show');
            successAlert.classList.add('fade');
            setTimeout(() => {
                if (successAlert.parentNode) {
                    successAlert.remove();
                }
            }, 300);
        }, 5000);
    }
    
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Add confirmation for approve/reject links
    const approveLinks = document.querySelectorAll('a[href*="action=approve"]');
    const rejectLinks = document.querySelectorAll('a[href*="action=reject"]');
    
    approveLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to approve this permission request?')) {
                e.preventDefault();
            }
        });
    });
    
    rejectLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to reject this permission request?')) {
                e.preventDefault();
            }
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>