<?php
require_once 'includes/header.php';
require_once 'config/database.php';

// Ambil data admin saat ini
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$admin = $stmt->fetch();

$success = '';
$error = '';

// Handle update profile
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    
    try {
        $pdo->beginTransaction();
        
        // Update users table
        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE user_id = ?");
        $stmt->execute([$name, $email, $_SESSION['user_id']]);
        
        // Update session
        $_SESSION['name'] = $name;
        $_SESSION['email'] = $email;
        
        // Update employees table if admin is also in employees
        $empStmt = $pdo->prepare("UPDATE employees SET name = ?, email = ?, phone = ? WHERE user_id = ?");
        $empStmt->execute([$name, $email, $phone, $_SESSION['user_id']]);
        
        $pdo->commit();
        $success = "Profile updated successfully!";
        
        // Refresh admin data
        $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $admin = $stmt->fetch();
        
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
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($admin['user_id']); ?>" readonly>
                        <small class="text-muted">User ID cannot be changed</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Full Name *</label>
                        <input type="text" class="form-control" name="name" 
                               value="<?php echo htmlspecialchars($admin['name']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Address *</label>
                        <input type="email" class="form-control" name="email" 
                               value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone Number</label>
                        <input type="text" class="form-control" name="phone" 
                               value="<?php echo htmlspecialchars($admin['phone'] ?? ''); ?>"
                               placeholder="e.g., 081234567890">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <input type="text" class="form-control" value="Administrator" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Account Status</label>
                        <input type="text" class="form-control" 
                               value="<?php echo ucfirst($admin['status'] ?? 'active'); ?>" 
                               style="color: <?php echo ($admin['status'] ?? 'active') == 'active' ? '#198754' : '#6c757d'; ?>" 
                               readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Member Since</label>
                        <input type="text" class="form-control" 
                               value="<?php echo date('d F Y', strtotime($admin['created_at'])); ?>" readonly>
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
                <h5 class="mb-0"><i class="bi bi-gear"></i> System Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6 mb-2">
                        <small class="text-muted">Application Version</small>
                        <p class="mb-0"><strong>E-zin v1.0</strong></p>
                    </div>
                    <div class="col-6 mb-2">
                        <small class="text-muted">PHP Version</small>
                        <p class="mb-0"><strong><?php echo phpversion(); ?></strong></p>
                    </div>
                    <div class="col-6 mb-2">
                        <small class="text-muted">Server Software</small>
                        <p class="mb-0"><strong><?php echo $_SERVER['SERVER_SOFTWARE']; ?></strong></p>
                    </div>
                    <div class="col-6 mb-2">
                        <small class="text-muted">Database</strong></p>
                    </div>
                    <div class="col-6 mb-2">
                        <small class="text-muted">Last Login</small>
                        <p class="mb-0"><strong><?php echo date('d M Y H:i'); ?></strong></p>
                    </div>
                    <div class="col-6 mb-2">
                        <small class="text-muted">IP Address</small>
                        <p class="mb-0"><strong><?php echo $_SERVER['REMOTE_ADDR']; ?></strong></p>
                    </div>
                </div>
                <hr>
                <div class="text-center">
                    <small class="text-muted">© <?php echo date('Y'); ?> E-zin System. All rights reserved.</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Account Statistics Card -->
<div class="card mt-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-bar-chart"></i> Account Statistics</h5>
    </div>
    <div class="card-body">
        <div class="row text-center">
            <?php
            // Get statistics
            $statsStmt = $pdo->query("SELECT 
                (SELECT COUNT(*) FROM employees) as total_employees,
                (SELECT COUNT(*) FROM employees WHERE role = 'lecturer') as lecturers,
                (SELECT COUNT(*) FROM employees WHERE role = 'staff') as staff,
                (SELECT COUNT(*) FROM employees WHERE role = 'student') as students,
                (SELECT COUNT(*) FROM permissions WHERE status = 'pending') as pending_permissions");
            $stats = $statsStmt->fetch();
            ?>
            <div class="col-md-3 col-6 mb-3">
                <div class="p-3 border rounded">
                    <h3 class="text-primary"><?php echo $stats['total_employees']; ?></h3>
                    <small class="text-muted">Total Employees</small>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="p-3 border rounded">
                    <h3 class="text-success"><?php echo $stats['lecturers']; ?></h3>
                    <small class="text-muted">Lecturers</small>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="p-3 border rounded">
                    <h3 class="text-warning"><?php echo $stats['staff']; ?></h3>
                    <small class="text-muted">Staff</small>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="p-3 border rounded">
                    <h3 class="text-info"><?php echo $stats['students']; ?></h3>
                    <small class="text-muted">Students</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Activity Log (Optional) -->
<div class="card mt-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-clock-history"></i> Recent Activity</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Activity</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Simulated activity log - in real app, you would have an activity_log table
                    $activities = [
                        ['time' => 'Just now', 'activity' => 'Viewed settings page', 'ip' => $_SERVER['REMOTE_ADDR']],
                        ['time' => 'Today, 10:30', 'activity' => 'Updated employee profile', 'ip' => $_SERVER['REMOTE_ADDR']],
                        ['time' => 'Yesterday, 14:45', 'activity' => 'Approved permission request', 'ip' => $_SERVER['REMOTE_ADDR']],
                        ['time' => '2 days ago', 'activity' => 'Added new employee', 'ip' => $_SERVER['REMOTE_ADDR']],
                        ['time' => '1 week ago', 'activity' => 'Changed password', 'ip' => $_SERVER['REMOTE_ADDR']]
                    ];
                    
                    foreach ($activities as $activity):
                    ?>
                    <tr>
                        <td><small class="text-muted"><?php echo $activity['time']; ?></small></td>
                        <td><?php echo $activity['activity']; ?></td>
                        <td><small class="text-muted"><?php echo $activity['ip']; ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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
    
    // Add phone number formatting
    const phoneInput = document.querySelector('input[name="phone"]');
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 0) {
                if (value.length <= 3) {
                    value = value;
                } else if (value.length <= 6) {
                    value = value.substr(0, 3) + '-' + value.substr(3);
                } else if (value.length <= 10) {
                    value = value.substr(0, 3) + '-' + value.substr(3, 3) + '-' + value.substr(6);
                } else {
                    value = value.substr(0, 3) + '-' + value.substr(3, 4) + '-' + value.substr(7);
                }
            }
            e.target.value = value;
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>