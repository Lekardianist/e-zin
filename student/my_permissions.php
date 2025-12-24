<?php
require_once 'includes/header.php';
require_once '../config/database.php';

$success = '';
$error = '';

// Handle delete permission
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    try {
        // Get permission data first
        $getStmt = $pdo->prepare("SELECT attachment_file FROM permissions WHERE id = ? AND user_id = ?");
        $getStmt->execute([$id, $_SESSION['user_id']]);
        $permission = $getStmt->fetch();
        
        if ($permission) {
            // Delete attached file if exists
            if (!empty($permission['attachment_file'])) {
                $file_path = '../uploads/permissions/' . $permission['attachment_file'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }
            
            // Delete from database
            $stmt = $pdo->prepare("DELETE FROM permissions WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $_SESSION['user_id']]);
            $success = "Permission request deleted successfully!";
        } else {
            $error = "Permission not found or you don't have permission to delete it!";
        }
        
    } catch (PDOException $e) {
        $error = "Error deleting permission: " . $e->getMessage();
    }
}

// Handle update permission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_permission'])) {
    $id = $_POST['permission_id'];
    $permission_type = $_POST['permission_type'];
    $detail_permission = $_POST['detail_permission'];
    $start_date = $_POST['start_date'] ?? null;
    $end_date = $_POST['end_date'] ?? null;
    $current_attachment = $_POST['current_attachment'] ?? null;
    $remove_attachment = isset($_POST['remove_attachment']) && $_POST['remove_attachment'] == '1';
    
    // Check if permission is still pending
    $checkStmt = $pdo->prepare("SELECT status FROM permissions WHERE id = ? AND user_id = ?");
    $checkStmt->execute([$id, $_SESSION['user_id']]);
    $permission = $checkStmt->fetch();
    
    if ($permission && $permission['status'] == 'pending') {
        try {
            // Handle file upload/removal
            $attachment_file = $current_attachment;
            $file_type = null;
            $file_size = null;
            
            // Remove current attachment if requested
            if ($remove_attachment && !empty($current_attachment)) {
                $file_path = '../uploads/permissions/' . $current_attachment;
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
                $attachment_file = null;
            }
            
            // Check if uploading new file
            if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf', 
                                 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                $max_size = 2 * 1024 * 1024; // 2MB
                
                $file_tmp = $_FILES['attachment']['tmp_name'];
                $file_name = $_FILES['attachment']['name'];
                $file_size = $_FILES['attachment']['size'];
                $file_type = $_FILES['attachment']['type'];
                
                // Validate file
                if (!in_array($file_type, $allowed_types)) {
                    $error = "File type not allowed. Allowed types: JPG, PNG, GIF, PDF, DOC, DOCX";
                } elseif ($file_size > $max_size) {
                    $error = "File size exceeds 2MB limit.";
                } else {
                    // Remove old file
                    if (!empty($current_attachment)) {
                        $old_file_path = '../uploads/permissions/' . $current_attachment;
                        if (file_exists($old_file_path)) {
                            unlink($old_file_path);
                        }
                    }
                    
                    // Create upload directory if not exists
                    $upload_dir = '../uploads/permissions/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    // Generate unique filename
                    $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
                    $unique_name = 'permission_' . $_SESSION['user_id'] . '_' . time() . '.' . $file_extension;
                    $upload_path = $upload_dir . $unique_name;
                    
                    // Move uploaded file
                    if (move_uploaded_file($file_tmp, $upload_path)) {
                        $attachment_file = $unique_name;
                    } else {
                        $error = "Failed to upload file.";
                    }
                }
            }
            
            // Update database if no error
            if (empty($error)) {
                $stmt = $pdo->prepare("UPDATE permissions SET 
                                      permission_type = ?, 
                                      detail_permission = ?,
                                      start_date = ?,
                                      end_date = ?,
                                      attachment_file = ?,
                                      file_type = ?,
                                      file_size = ?,
                                      updated_at = NOW()
                                      WHERE id = ? AND user_id = ?");
                $stmt->execute([
                    $permission_type, 
                    $detail_permission, 
                    $start_date, 
                    $end_date,
                    $attachment_file,
                    $file_type,
                    $file_size,
                    $id, 
                    $_SESSION['user_id']
                ]);
                
                $success = "Permission request updated successfully!";
            }
            
        } catch (PDOException $e) {
            $error = "Error updating permission: " . $e->getMessage();
        }
    } else {
        $error = "Cannot update permission that is not pending!";
    }
}

// Handle filter and search
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

$query = "SELECT * FROM permissions WHERE user_id = ?";
$params = [$_SESSION['user_id']];

if ($filter != 'all') {
    $query .= " AND status = ?";
    $params[] = $filter;
}

if (!empty($search)) {
    $query .= " AND (permission_type LIKE ? OR detail_permission LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$query .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$permissions = $stmt->fetchAll();

// Get user data
$user_stmt = $pdo->prepare("SELECT * FROM employees WHERE user_id = ?");
$user_stmt->execute([$_SESSION['user_id']]);
$user = $user_stmt->fetch();

// Get statistics
$statsStmt = $pdo->prepare("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
    FROM permissions WHERE user_id = ?");
$statsStmt->execute([$_SESSION['user_id']]);
$stats = $statsStmt->fetch();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">My Permissions</h1>
    <a href="permissions.php" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> New Request
    </a>
</div>

<!-- Messages -->
<?php if($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i> <?php echo $success; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i> <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row">
    <!-- Sidebar -->
    <div class="col-md-3 mb-4">        
        <!-- Quick Filter -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-filter"></i> Quick Filter</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="my_permissions.php?filter=all" class="btn btn-sm btn-outline-secondary <?php echo $filter == 'all' ? 'active' : ''; ?>">All</a>
                    <a href="my_permissions.php?filter=pending" class="btn btn-sm btn-outline-warning <?php echo $filter == 'pending' ? 'active' : ''; ?>">Pending</a>
                    <a href="my_permissions.php?filter=approved" class="btn btn-sm btn-outline-success <?php echo $filter == 'approved' ? 'active' : ''; ?>">Approved</a>
                    <a href="my_permissions.php?filter=rejected" class="btn btn-sm btn-outline-danger <?php echo $filter == 'rejected' ? 'active' : ''; ?>">Rejected</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="col-md-9">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h5 class="mb-0"><i class="bi bi-clipboard-check"></i> My Permission Requests</h5>
                    </div>
                    <div class="col-md-6">
                        <form method="GET" action="" class="row g-2">
                            <div class="col-md-8">
                                <input type="text" class="form-control" name="search" 
                                       placeholder="Search permissions..." value="<?php echo htmlspecialchars($search); ?>">
                                <input type="hidden" name="filter" value="<?php echo $filter; ?>">
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-search"></i> Search
                                </button>
                                <?php if(!empty($search)): ?>
                                    <a href="my_permissions.php?filter=<?php echo $filter; ?>" class="btn btn-secondary w-100 mt-2">Reset</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?php if(count($permissions) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Details</th>
                                    <th>Status</th>
                                    <th>Attachment</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($permissions as $perm): ?>
                                <tr>
                                    <td>
                                        <small><?php echo date('d M Y', strtotime($perm['created_at'])); ?></small>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($perm['permission_type']); ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $detail = $perm['detail_permission'];
                                        echo strlen($detail) > 30 ? substr($detail, 0, 30) . '...' : $detail;
                                        ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $perm['status'] == 'approved' ? 'success' : 
                                                   ($perm['status'] == 'rejected' ? 'danger' : 'warning');
                                        ?>">
                                            <?php echo ucfirst($perm['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if(!empty($perm['attachment_file'])): ?>
                                            <?php 
                                            $file_ext = pathinfo($perm['attachment_file'], PATHINFO_EXTENSION);
                                            $is_image = in_array(strtolower($file_ext), ['jpg', 'jpeg', 'png', 'gif']);
                                            ?>
                                            <?php if($is_image): ?>
                                                <i class="bi bi-file-image text-success"></i>
                                            <?php else: ?>
                                                <i class="bi bi-paperclip text-primary"></i>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <!-- View Button -->
                                            <button type="button" class="btn btn-info" data-bs-toggle="modal" 
                                                    data-bs-target="#viewModal<?php echo $perm['id']; ?>">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            
                                            <!-- Edit Button (only for pending) -->
                                            <?php if($perm['status'] == 'pending'): ?>
                                                <button type="button" class="btn btn-warning" data-bs-toggle="modal" 
                                                        data-bs-target="#editModal<?php echo $perm['id']; ?>">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                            <?php endif; ?>
                                            
                                            <!-- Delete Button (only for pending) -->
                                            <?php if($perm['status'] == 'pending'): ?>
                                                <button type="button" class="btn btn-danger" 
                                                        onclick="confirmDelete(<?php echo $perm['id']; ?>, '<?php echo htmlspecialchars($perm['permission_type']); ?>')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-clipboard display-1 text-muted"></i>
                        <h4 class="mt-3">No Permission Requests Found</h4>
                        <p class="text-muted">
                            <?php if(!empty($search) || $filter != 'all'): ?>
                                Try changing your search or filter criteria.
                            <?php else: ?>
                                You haven't made any permission requests yet.
                            <?php endif; ?>
                        </p>
                        <a href="permissions.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Create Your First Request
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- View Modals -->
<?php foreach($permissions as $perm): ?>
<div class="modal fade" id="viewModal<?php echo $perm['id']; ?>" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="bi bi-clipboard-check"></i> Permission Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- User Info -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6><i class="bi bi-person"></i> Applicant Information</h6>
                        <table class="table table-sm">
                            <tr><td><strong>Name:</strong></td><td><?php echo htmlspecialchars($user['name']); ?></td></tr>
                            <tr><td><strong>Email:</strong></td><td><?php echo htmlspecialchars($user['email']); ?></td></tr>
                            <tr><td><strong>ID:</strong></td><td><?php echo $user['user_id']; ?></td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="bi bi-info-circle"></i> Request Details</h6>
                        <table class="table table-sm">
                            <tr><td><strong>Date:</strong></td><td><?php echo date('d F Y H:i', strtotime($perm['created_at'])); ?></td></tr>
                            <tr><td><strong>Type:</strong></td><td><?php echo $perm['permission_type']; ?></td></tr>
                            <tr><td><strong>Status:</strong></td>
                                <td><span class="badge bg-<?php echo $perm['status'] == 'approved' ? 'success' : ($perm['status'] == 'rejected' ? 'danger' : 'warning'); ?>">
                                    <?php echo ucfirst($perm['status']); ?>
                                </span></td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <!-- Duration -->
                <?php if($perm['start_date'] && $perm['end_date']): ?>
                <div class="alert alert-info">
                    <i class="bi bi-calendar-range"></i>
                    <strong>Duration:</strong> 
                    <?php echo date('d F Y', strtotime($perm['start_date'])); ?> 
                    to 
                    <?php echo date('d F Y', strtotime($perm['end_date'])); ?>
                </div>
                <?php endif; ?>
                
                <!-- Details -->
                <div class="mb-4">
                    <h6><i class="bi bi-journal-text"></i> Details</h6>
                    <div class="card bg-light">
                        <div class="card-body">
                            <?php echo nl2br(htmlspecialchars($perm['detail_permission'])); ?>
                        </div>
                    </div>
                </div>
                
                <!-- Attachment Section (Sama seperti di halaman admin) -->
                <?php if(!empty($perm['attachment_file'])): ?>
                <div class="mb-3">
                    <h6><i class="bi bi-paperclip"></i> Attachment / Evidence</h6>
                    <div class="card border">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-2 text-center">
                                    <?php 
                                    $file_ext = pathinfo($perm['attachment_file'], PATHINFO_EXTENSION);
                                    $file_name = $perm['attachment_file'];
                                    $file_path = '../uploads/permissions/' . $file_name;
                                    $file_exists = file_exists($file_path);
                                    
                                    $icon_class = '';
                                    $is_image = in_array(strtolower($file_ext), ['jpg', 'jpeg', 'png', 'gif']);
                                    
                                    if ($is_image) {
                                        $icon_class = 'bi-file-image text-primary';
                                    } elseif (strtolower($file_ext) == 'pdf') {
                                        $icon_class = 'bi-file-pdf text-danger';
                                    } elseif (in_array(strtolower($file_ext), ['doc', 'docx'])) {
                                        $icon_class = 'bi-file-word text-primary';
                                    } else {
                                        $icon_class = 'bi-file-earmark text-secondary';
                                    }
                                    ?>
                                    <i class="bi <?php echo $icon_class; ?> display-4"></i>
                                </div>
                                <div class="col-md-7">
                                    <h6 class="mb-1"><?php echo $file_name; ?></h6>
                                    <p class="text-muted mb-1">
                                        <small>
                                            <i class="bi bi-filetype-<?php echo strtolower($file_ext); ?>"></i>
                                            <?php echo strtoupper($file_ext); ?> • 
                                            <?php echo !empty($perm['file_size']) ? round($perm['file_size'] / 1024, 1) . ' KB' : 'Unknown size'; ?>
                                        </small>
                                    </p>
                                </div>
                                <div class="col-md-3 text-end">
                                    <?php if($file_exists): ?>
                                        <?php if ($is_image): ?>
                                            <!-- View Image Button -->
                                            <button type="button" class="btn btn-sm btn-primary mb-1" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#imageModal<?php echo $perm['id']; ?>">
                                                <i class="bi bi-eye"></i> View Image
                                            </button><br>
                                        <?php endif; ?>
                                        <!-- Download Button -->
                                        <a href="<?php echo $file_path; ?>" 
                                           class="btn btn-sm btn-success" 
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
                </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle"></i> Close
                </button>
                <?php if($perm['status'] == 'pending'): ?>
                    <button type="button" class="btn btn-warning" data-bs-toggle="modal" 
                            data-bs-target="#editModal<?php echo $perm['id']; ?>"
                            data-bs-dismiss="modal">
                        <i class="bi bi-pencil"></i> Edit
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Image Preview Modal (Sama seperti di admin) -->
<?php if(!empty($perm['attachment_file'])): 
    $file_ext = pathinfo($perm['attachment_file'], PATHINFO_EXTENSION);
    $file_name = $perm['attachment_file'];
    $file_path = '../uploads/permissions/' . $file_name;
    $file_exists = file_exists($file_path);
    $is_image = in_array(strtolower($file_ext), ['jpg', 'jpeg', 'png', 'gif']);
?>
<?php if($is_image && $file_exists): ?>
<div class="modal fade" id="imageModal<?php echo $perm['id']; ?>" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title">
                    <i class="bi bi-image"></i> Evidence Preview
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
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
<?php endif; ?>
<?php endif; ?>

<!-- Edit Modal (only for pending) -->
<?php if($perm['status'] == 'pending'): ?>
<div class="modal fade" id="editModal<?php echo $perm['id']; ?>" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning bg-opacity-10">
                <h5 class="modal-title">
                    <i class="bi bi-pencil-square"></i> Edit Permission
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="permission_id" value="<?php echo $perm['id']; ?>">
                <input type="hidden" name="current_attachment" value="<?php echo $perm['attachment_file'] ?? ''; ?>">
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Permission Type *</label>
                        <select class="form-select" name="permission_type" required>
                            <option value="Sakit" <?php echo $perm['permission_type'] == 'Sakit' ? 'selected' : ''; ?>>Sakit</option>
                            <option value="Izin" <?php echo $perm['permission_type'] == 'Izin' ? 'selected' : ''; ?>>Izin</option>
                            <option value="Cuti" <?php echo $perm['permission_type'] == 'Cuti' ? 'selected' : ''; ?>>Cuti</option>
                            <option value="Lainnya" <?php echo $perm['permission_type'] == 'Lainnya' ? 'selected' : ''; ?>>Lainnya</option>
                        </select>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Start Date</label>
                            <input type="date" class="form-control" name="start_date" 
                                   value="<?php echo $perm['start_date']; ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">End Date</label>
                            <input type="date" class="form-control" name="end_date" 
                                   value="<?php echo $perm['end_date']; ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Details *</label>
                        <textarea class="form-control" name="detail_permission" rows="4" required><?php echo htmlspecialchars($perm['detail_permission']); ?></textarea>
                    </div>
                    
                    <!-- Attachment -->
                    <div class="mb-3">
                        <label class="form-label">Attachment</label>
                        
                        <?php if(!empty($perm['attachment_file'])): ?>
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" 
                                           id="removeAttachment<?php echo $perm['id']; ?>" 
                                           name="remove_attachment" value="1">
                                    <label class="form-check-label" for="removeAttachment<?php echo $perm['id']; ?>">
                                        Remove current file: <?php echo $perm['attachment_file']; ?>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="input-group">
                            <input type="file" class="form-control" name="attachment">
                            <small class="form-text text-muted w-100">Max 2MB. Allowed: JPG, PNG, PDF, DOC, DOCX</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_permission" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php endforeach; ?>

<style>
.avatar-lg {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 36px;
    color: white;
}

.user-details p {
    margin-bottom: 8px;
    padding-bottom: 8px;
    border-bottom: 1px solid #eee;
    font-size: 0.9rem;
}

.user-details i {
    width: 20px;
    margin-right: 10px;
    color: #667eea;
}

/* Image zoom styles */
.img-zoomed {
    transform: scale(1.5);
    cursor: zoom-out !important;
}

.modal-xl img {
    transition: transform 0.3s ease;
}
</style>

<script>
function confirmDelete(id, permissionType) {
    if (confirm(`Delete this permission request?\n\nType: ${permissionType}\n\nThis action cannot be undone!`)) {
        window.location.href = 'my_permissions.php?delete=' + id;
    }
}

// Auto-hide alerts
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.classList.remove('show');
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
    
    // Image zoom functionality
    document.querySelectorAll('[id^="evidenceImage"]').forEach(image => {
        image.addEventListener('click', function() {
            this.classList.toggle('img-zoomed');
            this.style.cursor = this.classList.contains('img-zoomed') ? 'zoom-out' : 'zoom-in';
            this.style.transform = this.classList.contains('img-zoomed') ? 'scale(1.5)' : 'scale(1)';
            this.style.transition = 'transform 0.3s ease';
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>