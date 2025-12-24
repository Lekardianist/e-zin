<?php
require_once 'includes/header.php';
require_once 'config/database.php';

// Pastikan user adalah lecturer
$stmt = $pdo->prepare("SELECT role FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if ($user['role'] !== 'lecturer') {
    header('Location: dashboard.php');
    exit();
}

// Ambil data lecturer saat ini
$stmt = $pdo->prepare("SELECT u.*, e.phone, e.subject, e.department 
                       FROM users u 
                       LEFT JOIN employees e ON u.user_id = e.user_id 
                       WHERE u.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$lecturer = $stmt->fetch();

$success = '';
$error = '';

// Handle update profile
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $subject = $_POST['subject'];
    $department = $_POST['department'];
    
    try {
        $pdo->beginTransaction();
        
        // Update users table
        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE user_id = ?");
        $stmt->execute([$name, $email, $_SESSION['user_id']]);
        
        // Update session
        $_SESSION['name'] = $name;
        $_SESSION['email'] = $email;
        
        // Update employees table
        $empStmt = $pdo->prepare("UPDATE employees SET name = ?, email = ?, phone = ?, 
                                  subject = ?, department = ? WHERE user_id = ?");
        $empStmt->execute([$name, $email, $phone, $subject, $department, $_SESSION['user_id']]);
        
        $pdo->commit();
        $success = "Profile updated successfully!";
        
        // Refresh lecturer data
        $stmt = $pdo->prepare("SELECT u.*, e.phone, e.subject, e.department 
                              FROM users u 
                              LEFT JOIN employees e ON u.user_id = e.user_id 
                              WHERE u.user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $lecturer = $stmt->fetch();
        
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
    <h1 class="h2">Settings</h1>
    <div class="text-muted">
        <i class="bi bi-person-badge"></i> Lecturer Settings
    </div>
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
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-person-circle"></i> Profile Information</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">User ID</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($lecturer['user_id']); ?>" readonly>
                        <small class="text-muted">User ID cannot be changed</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Full Name *</label>
                        <input type="text" class="form-control" name="name" 
                               value="<?php echo htmlspecialchars($lecturer['name']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Address *</label>
                        <input type="email" class="form-control" name="email" 
                               value="<?php echo htmlspecialchars($lecturer['email']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone Number</label>
                        <input type="text" class="form-control" name="phone" 
                               value="<?php echo htmlspecialchars($lecturer['phone'] ?? ''); ?>"
                               placeholder="e.g., 081234567890">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subject *</label>
                        <input type="text" class="form-control" name="subject" 
                               value="<?php echo htmlspecialchars($lecturer['subject']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Department *</label>
                        <input type="text" class="form-control" name="department" 
                               value="<?php echo htmlspecialchars($lecturer['department']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <input type="text" class="form-control" value="Lecturer" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Account Status</label>
                        <input type="text" class="form-control" 
                               value="<?php echo ucfirst($lecturer['status'] ?? 'active'); ?>" 
                               style="color: <?php echo ($lecturer['status'] ?? 'active') == 'active' ? '#198754' : '#6c757d'; ?>" 
                               readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Member Since</label>
                        <input type="text" class="form-control" 
                               value="<?php echo date('d F Y', strtotime($lecturer['created_at'])); ?>" readonly>
                    </div>
                    <button type="submit" name="update_profile" class="btn btn-primary">
                        <i class="bi bi-save"></i> Update Profile
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-shield-lock"></i> Security & Password</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="" id="passwordForm">
                    <div class="mb-3">
                        <label class="form-label">Current Password *</label>
                        <div class="input-group">
                            <input type="password" class="form-control" name="current_password" 
                                   id="current_password" required>
                            <button class="btn btn-outline-secondary" type="button" 
                                    onclick="togglePassword('current_password')">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password *</label>
                        <div class="input-group">
                            <input type="password" class="form-control" name="new_password" 
                                   id="new_password" required>
                            <button class="btn btn-outline-secondary" type="button" 
                                    onclick="togglePassword('new_password')">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <small class="text-muted">Minimum 6 characters</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password *</label>
                        <div class="input-group">
                            <input type="password" class="form-control" name="confirm_password" 
                                   id="confirm_password" required>
                            <button class="btn btn-outline-secondary" type="button" 
                                    onclick="togglePassword('confirm_password')">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="showPasswordRules">
                            <label class="form-check-label" for="showPasswordRules">
                                Show password requirements
                            </label>
                        </div>
                    </div>
                    <div class="password-rules alert alert-info" style="display: none;">
                        <h6><i class="bi bi-info-circle"></i> Password Requirements:</h6>
                        <ul class="mb-0">
                            <li>Minimum 6 characters</li>
                            <li>Use a combination of letters and numbers</li>
                            <li>Avoid using common passwords</li>
                            <li>Don't reuse old passwords</li>
                        </ul>
                    </div>
                    <button type="submit" name="change_password" class="btn btn-primary">
                        <i class="bi bi-key"></i> Change Password
                    </button>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-bar-chart"></i> My Statistics</h5>
            </div>
            <div class="card-body">
                <?php
                // Get statistics for this lecturer
                $statsStmt = $pdo->prepare("SELECT 
                    (SELECT COUNT(*) FROM employees 
                     WHERE role = 'student' 
                     AND (department = ? OR subject LIKE ?)) as total_students,
                    (SELECT COUNT(*) FROM permissions p 
                     JOIN employees e ON p.user_id = e.user_id 
                     WHERE e.role = 'student' 
                     AND (e.department = ? OR e.subject LIKE ?)) as total_permissions,
                    (SELECT COUNT(*) FROM permissions p 
                     JOIN employees e ON p.user_id = e.user_id 
                     WHERE e.role = 'student' 
                     AND (e.department = ? OR e.subject LIKE ?)
                     AND p.status = 'pending') as pending_permissions,
                    (SELECT COUNT(*) FROM permissions p 
                     JOIN employees e ON p.user_id = e.user_id 
                     WHERE e.role = 'student' 
                     AND (e.department = ? OR e.subject LIKE ?)
                     AND p.status = 'approved') as approved_permissions");
                $statsStmt->execute([
                    $lecturer['department'], '%' . $lecturer['subject'] . '%',
                    $lecturer['department'], '%' . $lecturer['subject'] . '%',
                    $lecturer['department'], '%' . $lecturer['subject'] . '%',
                    $lecturer['department'], '%' . $lecturer['subject'] . '%'
                ]);
                $stats = $statsStmt->fetch();
                ?>
                <div class="row text-center">
                    <div class="col-6 mb-3">
                        <div class="p-3 border rounded">
                            <h3 class="text-primary"><?php echo $stats['total_students']; ?></h3>
                            <small class="text-muted">Total Students</small>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="p-3 border rounded">
                            <h3 class="text-info"><?php echo $stats['total_permissions']; ?></h3>
                            <small class="text-muted">Total Requests</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 border rounded">
                            <h3 class="text-warning"><?php echo $stats['pending_permissions']; ?></h3>
                            <small class="text-muted">Pending Requests</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 border rounded">
                            <h3 class="text-success"><?php echo $stats['approved_permissions']; ?></h3>
                            <small class="text-muted">Approved Requests</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<script>
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const button = input.nextElementSibling;
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

// Show/hide password rules
document.getElementById('showPasswordRules').addEventListener('change', function() {
    const rulesDiv = document.querySelector('.password-rules');
    if (this.checked) {
        rulesDiv.style.display = 'block';
    } else {
        rulesDiv.style.display = 'none';
    }
});

// Password strength indicator
document.getElementById('new_password').addEventListener('input', function() {
    const password = this.value;
    const strengthText = document.getElementById('passwordStrength');
    
    if (!strengthText) {
        const strengthDiv = document.createElement('div');
        strengthDiv.id = 'passwordStrength';
        strengthDiv.className = 'mt-2';
        this.parentNode.parentNode.appendChild(strengthDiv);
    }
    
    const strengthDiv = document.getElementById('passwordStrength');
    
    if (password.length === 0) {
        strengthDiv.innerHTML = '';
        return;
    }
    
    let strength = 0;
    let message = '';
    let color = '';
    
    if (password.length >= 6) strength++;
    if (/[a-z]/.test(password)) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^a-zA-Z0-9]/.test(password)) strength++;
    
    switch(strength) {
        case 1:
            message = 'Very Weak';
            color = '#dc3545';
            break;
        case 2:
            message = 'Weak';
            color = '#fd7e14';
            break;
        case 3:
            message = 'Fair';
            color = '#ffc107';
            break;
        case 4:
            message = 'Good';
            color = '#20c997';
            break;
        case 5:
            message = 'Strong';
            color = '#198754';
            break;
    }
    
    strengthDiv.innerHTML = `
        <small>Password Strength: <strong style="color: ${color}">${message}</strong></small>
        <div class="progress mt-1" style="height: 5px;">
            <div class="progress-bar" style="width: ${strength * 20}%; background-color: ${color};"></div>
        </div>
    `;
});

// Confirm before changing password
document.getElementById('passwordForm').addEventListener('submit', function(e) {
    const newPass = document.getElementById('new_password').value;
    const confirmPass = document.getElementById('confirm_password').value;
    
    if (newPass !== confirmPass) {
        e.preventDefault();
        alert('New password and confirmation do not match!');
        return false;
    }
    
    if (newPass.length < 6) {
        e.preventDefault();
        alert('New password must be at least 6 characters!');
        return false;
    }
    
    return confirm('Are you sure you want to change your password?');
});

// Auto-hide success message after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const successAlert = document.querySelector('.alert-success');
    if (successAlert) {
        setTimeout(() => {
            successAlert.classList.remove('show');
            successAlert.classList.add('fade');
        }, 5000);
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>