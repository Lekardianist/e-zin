<?php
require_once 'includes/header.php';
require_once '../config/database.php';

$success = '';
$error = '';

// Handle permission submission with file upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_permission'])) {
    $permission_type = $_POST['permission_type'];
    $detail_permission = $_POST['detail_permission'];
    $lecturer_id = $_POST['lecturer_id'];
    $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
    
    // Validation
    if (empty($permission_type) || empty($detail_permission) || empty($lecturer_id)) {
        $error = "Please fill in all required fields!";
    } else {
        try {
            // Handle file upload
            $attachment_file = null;
            $file_type = null;
            $file_size = null;
            
            if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf', 
                                 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                $max_size = 2 * 1024 * 1024; // 2MB
                
                $file_tmp = $_FILES['attachment']['tmp_name'];
                $file_name = $_FILES['attachment']['name'];
                $file_size = $_FILES['attachment']['size'];
                $file_type = $_FILES['attachment']['type'];
                
                // Validate file type
                if (!in_array($file_type, $allowed_types)) {
                    $error = "File type not allowed. Allowed types: JPG, PNG, GIF, PDF, DOC, DOCX";
                } elseif ($file_size > $max_size) {
                    $error = "File size exceeds 2MB limit.";
                } else {
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
            
            // If no upload error, proceed with database insert
            if (empty($error)) {
                $stmt = $pdo->prepare("INSERT INTO permissions 
                                      (user_id, lecturer_id, name, role, permission_type, detail_permission, 
                                       start_date, end_date, attachment_file, file_type, file_size) 
                                      VALUES (?, ?, ?, 'student', ?, ?, ?, ?, ?, ?, ?)");
                
                $stmt->execute([
                    $_SESSION['user_id'],
                    $lecturer_id,
                    $_SESSION['name'],
                    $permission_type,
                    $detail_permission,
                    $start_date,
                    $end_date,
                    $attachment_file,
                    $file_type,
                    $file_size
                ]);
                
                $success = "Permission request submitted successfully" . ($attachment_file ? " with attachment!" : "!");
            }
            
        } catch (PDOException $e) {
            $error = "Error submitting permission: " . $e->getMessage();
        }
    }
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Request Permission</h1>
    <a href="my_permissions.php" class="btn btn-outline-primary">
        <i class="bi bi-arrow-left"></i> Back to My Permissions
    </a>
</div>

<!-- Success/Error Messages -->
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
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-plus-circle"></i> New Permission Request</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="" enctype="multipart/form-data" id="permissionForm">
                    <div class="mb-3">
                        <label class="form-label">Target Lecturer *</label>
                        <select class="form-select" name="lecturer_id" id="lecturerId" required>
                            <option value="">Select Lecturer</option>
                            <?php
                            try {
                                $stmt = $pdo->prepare("SELECT user_id, name FROM users WHERE role = 'lecturer' AND status = 'active' ORDER BY name ASC");
                                $stmt->execute();
                                $lecturers = $stmt->fetchAll();
                                foreach($lecturers as $lecturer):
                            ?>
                                <option value="<?php echo htmlspecialchars($lecturer['user_id']); ?>">
                                    <?php echo htmlspecialchars($lecturer['name']); ?>
                                </option>
                            <?php 
                                endforeach;
                            } catch (PDOException $e) {
                                echo '<option value="">Error loading lecturers</option>';
                            }
                            ?>
                        </select>
                        <small class="text-muted">Select the lecturer who will review this permission request</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Permission Type *</label>
                        <select class="form-select" name="permission_type" id="permissionType" required>
                            <option value="">Select Permission Type</option>
                            <option value="Sakit">Sakit</option>
                            <option value="Izin">Izin</option>
                            <option value="Cuti">Cuti</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Start Date (Optional)</label>
                            <input type="date" class="form-control" name="start_date" id="startDate">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">End Date (Optional)</label>
                            <input type="date" class="form-control" name="end_date" id="endDate">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Detail Permission *</label>
                        <textarea class="form-control" name="detail_permission" id="detailPermission" 
                                  rows="6" placeholder="Describe your permission request in detail..." required></textarea>
                        <small class="text-muted">Be specific about the reason and duration.</small>
                    </div>
                    
                    <!-- File Upload Section -->
                    <div class="mb-4">
                        <label class="form-label">Attachment / Evidence (Optional)</label>
                        <div class="upload-area" id="uploadArea">
                            <i class="bi bi-cloud-upload"></i>
                            <p class="mb-2">Drag & drop your file here or click to browse</p>
                            <p class="text-muted small mb-0">Max file size: 2MB. Supported: JPG, PNG, PDF, DOC, DOCX</p>
                            <input type="file" class="form-control d-none" id="attachment" 
                                   name="attachment" accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx">
                            <button type="button" class="btn btn-outline-primary mt-3" id="browseBtn">
                                <i class="bi bi-folder2-open"></i> Browse Files
                            </button>
                        </div>
                        
                        <!-- File info display -->
                        <div class="mt-2">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <i class="bi bi-paperclip"></i>
                                    <span id="fileName" class="ms-2">No file chosen</span>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-danger" id="removeFile" style="display: none;">
                                    <i class="bi bi-trash"></i> Remove
                                </button>
                            </div>
                        </div>
                        
                        <!-- File preview -->
                        <div id="attachmentPreview" class="mt-3"></div>
                        
                        <!-- Upload progress (optional) -->
                        <div class="progress mt-2" id="uploadProgress" style="display: none; height: 5px;">
                            <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <h6><i class="bi bi-info-circle"></i> Important Notes:</h6>
                        <ul class="mb-0">
                            <li>Permission requests will be reviewed by administration</li>
                            <li>You will be notified when your request is approved or rejected</li>
                            <li>Keep your explanations clear and concise</li>
                            <li>Submit requests at least 24 hours in advance when possible</li>
                            <li>For medical leave, attach doctor's note if available</li>
                        </ul>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="reset" class="btn btn-secondary" id="resetBtn">
                            <i class="bi bi-arrow-clockwise"></i> Reset Form
                        </button>
                        <button type="submit" name="submit_permission" class="btn btn-primary">
                            <i class="bi bi-send"></i> Submit Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <!-- Permission Guidelines -->
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-book"></i> Permission Guidelines</h6>
            </div>
            <div class="card-body">
                <div class="permission-type-info">
                    <h6 class="text-primary"><i class="bi bi-thermometer-sun"></i> Sakit</h6>
                    <p class="small">Use for medical leave. Include doctor's note if available.</p>
                    
                    <h6 class="text-success"><i class="bi bi-calendar-check"></i> Izin</h6>
                    <p class="small">Use for personal matters, family events, or other valid reasons.</p>
                    
                    <h6 class="text-warning"><i class="bi bi-umbrella"></i> Cuti</h6>
                    <p class="small">Use for planned leave such as holidays or long breaks.</p>
                    
                    <h6 class="text-info"><i class="bi bi-clipboard-check"></i> Lainnya</h6>
                    <p class="small">Use for other types of permissions not listed above.</p>
                </div>
            </div>
        </div>
        
        <!-- Recent Submissions -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-clock-history"></i> Recent Submissions</h6>
            </div>
            <div class="card-body">
                <?php
                $stmt = $pdo->prepare("SELECT permission_type, status, created_at, attachment_file 
                                      FROM permissions 
                                      WHERE user_id = ? 
                                      ORDER BY created_at DESC 
                                      LIMIT 3");
                $stmt->execute([$_SESSION['user_id']]);
                $recent = $stmt->fetchAll();
                
                if(count($recent) > 0):
                ?>
                <ul class="list-group list-group-flush">
                    <?php foreach($recent as $item): ?>
                    <li class="list-group-item px-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="d-flex align-items-center">
                                    <?php if($item['attachment_file']): ?>
                                        <i class="bi bi-paperclip text-success me-2"></i>
                                    <?php endif; ?>
                                    <strong><?php echo $item['permission_type']; ?></strong>
                                </div>
                                <small class="text-muted"><?php echo date('d M', strtotime($item['created_at'])); ?></small>
                            </div>
                            <span class="badge bg-<?php 
                                echo $item['status'] == 'approved' ? 'success' : 
                                       ($item['status'] == 'rejected' ? 'danger' : 'warning');
                            ?>">
                                <?php echo ucfirst($item['status']); ?>
                            </span>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php else: ?>
                <p class="text-muted text-center mb-0">No recent submissions</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Attachment Requirements -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-paperclip"></i> Attachment Tips</h6>
            </div>
            <div class="card-body">
                <ul class="small mb-0">
                    <li>Max file size: 2MB</li>
                    <li>Supported formats: JPG, PNG, PDF, DOC, DOCX</li>
                    <li>For medical leave: Doctor's note</li>
                    <li>For events: Invitation letter</li>
                    <li>Ensure files are clear and readable</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
/* Upload Area Styles */
.upload-area {
    border: 2px dashed #ced4da;
    border-radius: 10px;
    padding: 30px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    background-color: #f8f9fa;
}

.upload-area:hover {
    border-color: #667eea;
    background-color: rgba(102, 126, 234, 0.05);
}

.upload-area i {
    font-size: 3rem;
    color: #6c757d;
    margin-bottom: 15px;
}

.upload-area.dragover {
    border-color: #667eea;
    background-color: rgba(102, 126, 234, 0.1);
}

/* File Preview Styles */
#attachmentPreview {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 15px;
    background-color: #fff;
}

#attachmentPreview img {
    max-width: 100%;
    max-height: 200px;
    border-radius: 6px;
    border: 1px solid #dee2e6;
}

/* Permission Guidelines */
.permission-type-info h6 {
    margin-top: 15px;
    padding-bottom: 5px;
    border-bottom: 1px solid #eee;
}

.permission-type-info h6 i {
    margin-right: 8px;
}

/* Recent Submissions */
.list-group-item {
    border: none;
    padding: 10px 0;
}

.list-group-item:not(:last-child) {
    border-bottom: 1px solid #eee;
}

/* Responsive */
@media (max-width: 768px) {
    .upload-area {
        padding: 20px;
    }
    
    .upload-area i {
        font-size: 2rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('attachment');
    const filePreview = document.getElementById('attachmentPreview');
    const fileNameDisplay = document.getElementById('fileName');
    const removeFileBtn = document.getElementById('removeFile');
    const browseBtn = document.getElementById('browseBtn');
    const uploadArea = document.getElementById('uploadArea');
    const permissionForm = document.getElementById('permissionForm');
    const resetBtn = document.getElementById('resetBtn');
    const permissionType = document.getElementById('permissionType');
    const detailPermission = document.getElementById('detailPermission');
    const startDate = document.getElementById('startDate');
    const endDate = document.getElementById('endDate');
    
    // Set minimum date to today
    const today = new Date().toISOString().split('T')[0];
    if (startDate) startDate.min = today;
    if (endDate) endDate.min = today;
    
    // Auto-hide alerts
    const successAlert = document.querySelector('.alert-success');
    const errorAlert = document.querySelector('.alert-danger');
    
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
    
    if (errorAlert) {
        setTimeout(() => {
            errorAlert.classList.remove('show');
            errorAlert.classList.add('fade');
            setTimeout(() => {
                if (errorAlert.parentNode) {
                    errorAlert.remove();
                }
            }, 300);
        }, 7000);
    }
    
    // Browse button click
    if (browseBtn && fileInput) {
        browseBtn.addEventListener('click', function() {
            fileInput.click();
        });
    }
    
    // File input change
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                fileNameDisplay.textContent = file.name;
                removeFileBtn.style.display = 'block';
                
                // Show preview for images
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        filePreview.innerHTML = `
                            <div class="text-center">
                                <img src="${e.target.result}" class="img-thumbnail" 
                                     style="max-width: 300px; max-height: 200px;">
                                <p class="mt-2 mb-0"><small>${file.name} (${formatFileSize(file.size)})</small></p>
                            </div>
                        `;
                        filePreview.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                } else {
                    // For non-image files, show file icon
                    const fileIcon = getFileIcon(file.name);
                    filePreview.innerHTML = `
                        <div class="text-center">
                            <i class="bi ${fileIcon} display-4 text-secondary"></i>
                            <p class="mt-2 mb-0"><strong>${file.name}</strong></p>
                            <p class="text-muted"><small>${formatFileSize(file.size)}</small></p>
                        </div>
                    `;
                    filePreview.style.display = 'block';
                }
            }
        });
    }
    
    // Remove file
    if (removeFileBtn) {
        removeFileBtn.addEventListener('click', function() {
            if (fileInput) fileInput.value = '';
            fileNameDisplay.textContent = 'No file chosen';
            removeFileBtn.style.display = 'none';
            filePreview.style.display = 'none';
            filePreview.innerHTML = '';
        });
    }
    
    // Drag and drop functionality
    if (uploadArea) {
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        ['dragenter', 'dragover'].forEach(eventName => {
            uploadArea.addEventListener(eventName, highlight, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, unhighlight, false);
        });
        
        function highlight() {
            uploadArea.classList.add('dragover');
        }
        
        function unhighlight() {
            uploadArea.classList.remove('dragover');
        }
        
        uploadArea.addEventListener('drop', handleDrop, false);
        
        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            
            if (files.length > 0) {
                if (fileInput) {
                    fileInput.files = files;
                    
                    // Trigger change event
                    const event = new Event('change');
                    fileInput.dispatchEvent(event);
                }
            }
        }
    }
    
    // Auto-suggest for permission type
    if (permissionType && detailPermission) {
        permissionType.addEventListener('change', function() {
            const type = this.value;
            let suggestion = '';
            
            switch(type) {
                case 'Sakit':
                    suggestion = 'Saya ingin mengajukan izin sakit karena [sebutkan penyakit].\nLama izin: [sebutkan durasi].\nDokumen pendukung: [sebutkan jika ada].';
                    break;
                case 'Izin':
                    suggestion = 'Saya ingin mengajukan izin untuk [sebutkan keperluan].\nTanggal: [sebutkan tanggal].\nLama izin: [sebutkan durasi].';
                    break;
                case 'Cuti':
                    suggestion = 'Saya ingin mengajukan cuti untuk [sebutkan alasan].\nPeriode: [sebutkan periode].\nJumlah hari: [sebutkan jumlah hari].';
                    break;
                case 'Lainnya':
                    suggestion = 'Saya ingin mengajukan izin untuk [sebutkan alasan].\nDetail: [jelaskan secara detail].';
                    break;
            }
            
            if (suggestion && !detailPermission.value) {
                detailPermission.value = suggestion;
                detailPermission.focus();
            }
        });
    }
    
    // Form validation
    if (permissionForm) {
        permissionForm.addEventListener('submit', function(e) {
            // Validate dates
            if (startDate.value && endDate.value) {
                if (new Date(startDate.value) > new Date(endDate.value)) {
                    e.preventDefault();
                    alert('Start date cannot be after end date!');
                    startDate.focus();
                    return false;
                }
            }
            
            // Validate detail length
            if (detailPermission.value.length < 10) {
                e.preventDefault();
                alert('Please provide more details (minimum 10 characters)');
                detailPermission.focus();
                return false;
            }
            
            // Validate file size if exists
            if (fileInput && fileInput.files.length > 0) {
                const file = fileInput.files[0];
                const maxSize = 2 * 1024 * 1024; // 2MB
                
                if (file.size > maxSize) {
                    e.preventDefault();
                    alert('File size exceeds 2MB limit. Please choose a smaller file.');
                    return false;
                }
            }
            
            return confirm('Submit this permission request?');
        });
    }
    
    // Reset button
    if (resetBtn) {
        resetBtn.addEventListener('click', function() {
            if (confirm('Are you sure you want to reset the form? All entered data will be lost.')) {
                permissionForm.reset();
                fileNameDisplay.textContent = 'No file chosen';
                removeFileBtn.style.display = 'none';
                filePreview.style.display = 'none';
                filePreview.innerHTML = '';
                
                // Reset dates to today
                if (startDate) startDate.value = today;
                if (endDate) endDate.value = today;
                
                detailPermission.focus();
            }
        });
    }
    
    // Helper function to get file icon based on extension
    function getFileIcon(filename) {
        const ext = filename.split('.').pop().toLowerCase();
        if (['jpg', 'jpeg', 'png', 'gif', 'bmp'].includes(ext)) {
            return 'bi-file-image';
        } else if (ext === 'pdf') {
            return 'bi-file-pdf';
        } else if (['doc', 'docx'].includes(ext)) {
            return 'bi-file-word';
        } else if (['xls', 'xlsx'].includes(ext)) {
            return 'bi-file-excel';
        } else if (['ppt', 'pptx'].includes(ext)) {
            return 'bi-file-ppt';
        } else if (['zip', 'rar'].includes(ext)) {
            return 'bi-file-zip';
        } else {
            return 'bi-file-earmark';
        }
    }
    
    // Helper function to format file size
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    // Set default dates to today
    if (startDate) startDate.value = today;
    if (endDate) endDate.value = today;
});
</script>

<?php require_once 'includes/footer.php'; ?>