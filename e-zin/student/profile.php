<?php
require_once 'includes/header.php';
require_once '../config/database.php';

// Get student data
$stmt = $pdo->prepare("SELECT e.*, u.phone as user_phone, u.status as user_status, u.created_at as user_created 
                      FROM employees e 
                      LEFT JOIN users u ON e.user_id = u.user_id 
                      WHERE e.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch();

$success = '';
$error = '';

// Handle update profile
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $department = $_POST['department'];
    $subject = $_POST['subject'];
    
    try {
        $pdo->beginTransaction();
        
        // Update employees table
        $stmt = $pdo->prepare("UPDATE employees SET name = ?, email = ?, department = ?, subject = ? WHERE user_id = ?");
        $stmt->execute([$name, $email, $department, $subject, $_SESSION['user_id']]);
        
        // Update users table
        $userStmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE user_id = ?");
        $userStmt->execute([$name, $email, $phone, $_SESSION['user_id']]);
        
        // Update session
        $_SESSION['name'] = $name;
        $_SESSION['email'] = $email;
        
        $pdo->commit();
        $success = "Profile updated successfully!";
        
        // Refresh student data
        $stmt = $pdo->prepare("SELECT e.*, u.phone as user_phone, u.status as user_status, u.created_at as user_created 
                              FROM employees e 
                              LEFT JOIN users u ON e.user_id = u.user_id 
                              WHERE e.user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $student = $stmt->fetch();
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Error updating profile: " . $e->getMessage();
    }
}

// Handle change password
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "All password fields are required!";
    } elseif ($new_password !== $confirm_password) {
        $error = "New password and confirmation do not match!";
    } elseif (strlen($new_password) < 6) {
        $error = "New password must be at least 6 characters!";
    } else {
        // Verify current password
        $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if (md5($current_password) === $user['password']) {
            try {
                $new_password_hash = md5($new_password);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $stmt->execute([$new_password_hash, $_SESSION['user_id']]);
                $success = "Password changed successfully!";
            } catch (PDOException $e) {
                $error = "Error changing password: " . $e->getMessage();
            }
        } else {
            $error = "Current password is incorrect!";
        }
    }
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">My Profile</h1>
</div>

<!-- Success/Error Messages -->
<?php if($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $success; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-4">
        <!-- Profile Card -->
        <div class="card mb-4">
            <div class="card-body text-center">
                <div class="profile-avatar mb-3">
                    <i class="bi bi-person-circle"></i>
                </div>
                <h4 class="card-title"><?php echo htmlspecialchars($student['name']); ?></h4>
                <p class="card-text">
                    <span class="badge bg-primary">
                        <i class="bi bi-mortarboard"></i> Student
                    </span>
                </p>
                <div class="profile-info text-start">
                    <p><i class="bi bi-person-badge"></i> <strong>ID:</strong> <?php echo $student['user_id']; ?></p>
                    <p><i class="bi bi-envelope"></i> <strong>Email:</strong> <?php echo htmlspecialchars($student['email']); ?></p>
                    <?php if(!empty($student['user_phone'])): ?>
                        <p><i class="bi bi-telephone"></i> <strong>Phone:</strong> <?php echo $student['user_phone']; ?></p>
                    <?php endif; ?>
                    <?php if(!empty($student['department'])): ?>
                        <p><i class="bi bi-building"></i> <strong>Department:</strong> <?php echo $student['department']; ?></p>
                    <?php endif; ?>
                    <?php if(!empty($student['subject'])): ?>
                        <p><i class="bi bi-book"></i> <strong>Subject:</strong> <?php echo $student['subject']; ?></p>
                    <?php endif; ?>
                    <p><i class="bi bi-calendar"></i> <strong>Member since:</strong> 
                       <?php echo date('F Y', strtotime($student['user_created'])); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Quick Stats -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-bar-chart"></i> Permission Stats</h6>
            </div>
            <div class="card-body">
                <?php
                $statsStmt = $pdo->prepare("SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
                    FROM permissions WHERE user_id = ?");
                $statsStmt->execute([$_SESSION['user_id']]);
                $stats = $statsStmt->fetch();
                ?>
                <div class="row text-center">
                    <div class="col-6 mb-2">
                        <div class="p-2 border rounded">
                            <h5 class="mb-0"><?php echo $stats['total']; ?></h5>
                            <small class="text-muted">Total</small>
                        </div>
                    </div>
                    <div class="col-6 mb-2">
                        <div class="p-2 border rounded bg-success bg-opacity-10">
                            <h5 class="mb-0 text-success"><?php echo $stats['approved']; ?></h5>
                            <small class="text-muted">Approved</small>
                        </div>
                    </div>
                    <div class="col-6 mb-2">
                        <div class="p-2 border rounded bg-warning bg-opacity-10">
                            <h5 class="mb-0 text-warning"><?php echo $stats['pending']; ?></h5>
                            <small class="text-muted">Pending</small>
                        </div>
                    </div>
                    <div class="col-6 mb-2">
                        <div class="p-2 border rounded bg-danger bg-opacity-10">
                            <h5 class="mb-0 text-danger"><?php echo $stats['rejected']; ?></h5>
                            <small class="text-muted">Rejected</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <!-- Update Profile Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-person-gear"></i> Update Profile Information</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Full Name *</label>
                            <input type="text" class="form-control" name="name" 
                                   value="<?php echo htmlspecialchars($student['name']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email Address *</label>
                            <input type="email" class="form-control" name="email" 
                                   value="<?php echo htmlspecialchars($student['email']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="text" class="form-control" name="phone" 
                                   value="<?php echo htmlspecialchars($student['user_phone'] ?? ''); ?>"
                                   placeholder="0812-3456-7890">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Department</label>
                            <input type="text" class="form-control" name="department" 
                                   value="<?php echo htmlspecialchars($student['department'] ?? ''); ?>"
                                   placeholder="Computer Science">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Subject / Major</label>
                        <input type="text" class="form-control" name="subject" 
                               value="<?php echo htmlspecialchars($student['subject'] ?? ''); ?>"
                               placeholder="Information Technology">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">User ID</label>
                        <input type="text" class="form-control" value="<?php echo $student['user_id']; ?>" readonly>
                        <small class="text-muted">User ID cannot be changed</small>
                    </div>
                    
                    <button type="submit" name="update_profile" class="btn btn-primary">
                        <i class="bi bi-save"></i> Update Profile
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Change Password Form -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-shield-lock"></i> Change Password</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">Current Password *</label>
                        <div class="input-group">
                            <input type="password" class="form-control" name="current_password" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword(this)">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">New Password *</label>
                        <div class="input-group">
                            <input type="password" class="form-control" name="new_password" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword(this)">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <small class="text-muted">Minimum 6 characters</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password *</label>
                        <div class="input-group">
                            <input type="password" class="form-control" name="confirm_password" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword(this)">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <h6><i class="bi bi-info-circle"></i> Password Requirements:</h6>
                        <ul class="mb-0">
                            <li>At least 6 characters long</li>
                            <li>Use a combination of letters and numbers</li>
                            <li>Avoid using personal information</li>
                            <li>Don't reuse old passwords</li>
                        </ul>
                    </div>
                    
                    <button type="submit" name="change_password" class="btn btn-primary">
                        <i class="bi bi-key"></i> Change Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword(button) {
    const input = button.parentNode.querySelector('input');
    const icon = button.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide alerts
    const successAlert = document.querySelector('.alert-success');
    if (successAlert) {
        setTimeout(() => {
            successAlert.classList.remove('show');
            successAlert.classList.add('fade');
        }, 5000);
    }
    
    // Phone number formatting
    const phoneInput = document.querySelector('input[name="phone"]');
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 0) {
                if (value.length <= 4) {
                    value = value;
                } else if (value.length <= 8) {
                    value = value.substr(0, 4) + '-' + value.substr(4);
                } else {
                    value = value.substr(0, 4) + '-' + value.substr(4, 4) + '-' + value.substr(8);
                }
            }
            e.target.value = value;
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>