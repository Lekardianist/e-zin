<?php
require_once 'includes/header.php';

// Ambil data lecturer lengkap
$stmt = $pdo->prepare("SELECT u.*, e.phone, e.subject, e.department, 
                       e.created_at as employee_since 
                       FROM users u 
                       LEFT JOIN employees e ON u.user_id = e.user_id 
                       WHERE u.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$lecturer = $stmt->fetch();

// Get statistics
$statsStmt = $pdo->prepare("SELECT 
    (SELECT COUNT(*) FROM employees 
     WHERE role = 'student' 
     AND (department = ? OR subject LIKE ?)) as total_students,
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
    $lecturer['department'], '%' . $lecturer['subject'] . '%'
]);
$stats = $statsStmt->fetch();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <div class="profile-avatar mb-3">
                        <i class="bi bi-person-circle"></i>
                    </div>
                    <h3 class="mb-2"><?php echo htmlspecialchars($lecturer['name']); ?></h3>
                    <p class="text-muted mb-3">
                        <span class="badge bg-primary">Lecturer</span>
                    </p>
                    
                    <div class="profile-info text-start">
                        <p><i class="bi bi-person-badge"></i> <strong>ID:</strong> <?php echo $lecturer['user_id']; ?></p>
                        <p><i class="bi bi-envelope"></i> <strong>Email:</strong> <?php echo htmlspecialchars($lecturer['email']); ?></p>
                        <?php if(!empty($lecturer['phone'])): ?>
                            <p><i class="bi bi-telephone"></i> <strong>Phone:</strong> <?php echo $lecturer['phone']; ?></p>
                        <?php endif; ?>
                        <p><i class="bi bi-book"></i> <strong>Subject:</strong> <?php echo $lecturer['subject']; ?></p>
                        <p><i class="bi bi-building"></i> <strong>Department:</strong> <?php echo $lecturer['department']; ?></p>
                        <p><i class="bi bi-calendar"></i> <strong>Member Since:</strong> <?php echo date('d F Y', strtotime($lecturer['employee_since'])); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Quick Stats -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-bar-chart"></i> Quick Stats</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6 mb-3">
                            <div class="p-3 border rounded">
                                <h4 class="text-primary mb-0"><?php echo $stats['total_students'] ?? 0; ?></h4>
                                <small class="text-muted">Students</small>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="p-3 border rounded">
                                <h4 class="text-warning mb-0"><?php echo $stats['pending_permissions'] ?? 0; ?></h4>
                                <small class="text-muted">Pending</small>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="p-3 border rounded">
                                <h4 class="text-success mb-0"><?php echo $stats['approved_permissions'] ?? 0; ?></h4>
                                <small class="text-muted">Approved Requests</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <!-- Recent Activities -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-clock-history"></i> Recent Activities</h5>
                    <a href="permissions.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Student</th>
                                    <th>Permission Type</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // FIXED: Gunakan created_at bukan updated_at
                                $activityStmt = $pdo->prepare("SELECT p.*, e.name as student_name 
                                                              FROM permissions p 
                                                              JOIN employees e ON p.user_id = e.user_id 
                                                              WHERE e.role = 'student' 
                                                              AND (e.department = ? OR e.subject LIKE ?)
                                                              ORDER BY p.created_at DESC LIMIT 10");
                                $activityStmt->execute([$lecturer['department'], '%' . $lecturer['subject'] . '%']);
                                $activities = $activityStmt->fetchAll();
                                
                                if (count($activities) > 0):
                                    foreach ($activities as $activity):
                                ?>
                                <tr>
                                    <td>
                                        <small><?php echo date('d M', strtotime($activity['created_at'])); ?></small>
                                    </td>
                                    <td>
                                        <small><?php echo htmlspecialchars($activity['student_name']); ?></small>
                                    </td>
                                    <td>
                                        <small><?php echo $activity['permission_type']; ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $activity['status'] == 'approved' ? 'success' : 
                                                   ($activity['status'] == 'rejected' ? 'danger' : 'warning');
                                        ?>">
                                            <?php echo ucfirst($activity['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-3">
                                        <p class="text-muted mb-0">No recent activities</p>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Contact Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Contact Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="bi bi-person-lines-fill"></i> Personal Details</h6>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td width="40%"><strong>Full Name:</strong></td>
                                    <td><?php echo htmlspecialchars($lecturer['name']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>User ID:</strong></td>
                                    <td><?php echo $lecturer['user_id']; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Email:</strong></td>
                                    <td><?php echo htmlspecialchars($lecturer['email']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Phone:</strong></td>
                                    <td><?php echo !empty($lecturer['phone']) ? $lecturer['phone'] : 'Not provided'; ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="bi bi-briefcase"></i> Professional Details</h6>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td width="40%"><strong>Subject:</strong></td>
                                    <td><?php echo $lecturer['subject']; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Department:</strong></td>
                                    <td><?php echo $lecturer['department']; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Role:</strong></td>
                                    <td><span class="badge bg-primary">Lecturer</span></td>
                                </tr>
                                <tr>
                                    <td><strong>Status:</strong></td>
                                    <td>
                                        <span class="badge bg-<?php echo ($lecturer['status'] ?? 'active') == 'active' ? 'success' : 'secondary'; ?>">
                                            <?php echo ucfirst($lecturer['status'] ?? 'active'); ?>
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <div class="mt-3 text-center">
                        <a href="settings.php" class="btn btn-primary">
                            <i class="bi bi-gear"></i> Edit Profile Settings
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Account Information -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-shield-check"></i> Account Security</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="bi bi-key"></i> Password Information</h6>
                            <p class="text-muted">Last password change: <?php echo date('d F Y'); ?></p>
                            <a href="settings.php" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-key"></i> Change Password
                            </a>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="bi bi-calendar-check"></i> Account Activity</h6>
                            <p class="text-muted mb-1">Last login: Today, <?php echo date('H:i'); ?></p>
                            <p class="text-muted mb-0">IP Address: <?php echo $_SERVER['REMOTE_ADDR']; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.profile-avatar {
    width: 120px;
    height: 120px;
    background: linear-gradient(135deg, #4e54c8 0%, #8f94fb 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    font-size: 60px;
    color: white;
}

.profile-info p {
    margin-bottom: 10px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
    color: #555;
}

.profile-info i {
    width: 20px;
    margin-right: 10px;
    color: #4e54c8;
}
</style>

<?php require_once 'includes/footer.php'; ?>