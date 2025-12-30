<?php
require_once 'includes/header.php';
require_once '../config/database.php';

// Get student's recent permissions
$stmt = $pdo->prepare("SELECT * FROM permissions WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$_SESSION['user_id']]);
$recent_permissions = $stmt->fetchAll();

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
    <h1 class="h2">Student Dashboard</h1>
    <a href="permissions.php" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> New Permission Request
    </a>
</div>

<!-- Welcome Card -->
<div class="card welcome-card mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-8">
                <h4 class="card-title">Welcome back, <?php echo $_SESSION['name']; ?>!</h4>
                <p class="card-text">
                    You have <strong><?php echo $stats['pending'] ?? 0; ?> pending</strong> permission requests.
                    <?php if($stats['pending'] > 0): ?>
                        Check their status in <a href="my_permissions.php">My Permissions</a>.
                    <?php endif; ?>
                </p>
                <a href="permissions.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Create New Request
                </a>
            </div>
            <div class="col-md-4 text-center">
                <i class="bi bi-mortarboard dashboard-icon"></i>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-white bg-primary mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Total Requests</h5>
                        <h2><?php echo $stats['total'] ?? 0; ?></h2>
                    </div>
                    <i class="bi bi-clipboard-data display-6"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-success mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Approved</h5>
                        <h2><?php echo $stats['approved'] ?? 0; ?></h2>
                    </div>
                    <i class="bi bi-check-circle display-6"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-warning mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Pending</h5>
                        <h2><?php echo $stats['pending'] ?? 0; ?></h2>
                    </div>
                    <i class="bi bi-clock-history display-6"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-danger mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Rejected</h5>
                        <h2><?php echo $stats['rejected'] ?? 0; ?></h2>
                    </div>
                    <i class="bi bi-x-circle display-6"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Permissions -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-clock-history"></i> Recent Permission Requests</h5>
    </div>
    <div class="card-body">
        <?php if(count($recent_permissions) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Permission Type</th>
                            <th>Details</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($recent_permissions as $perm): ?>
                        <tr>
                            <td><?php echo date('d M Y', strtotime($perm['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($perm['permission_type']); ?></td>
                            <td>
                                <?php 
                                $detail = $perm['detail_permission'];
                                echo strlen($detail) > 50 ? substr($detail, 0, 50) . '...' : $detail;
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
                                <a href="my_permissions.php?view=<?php echo $perm['id']; ?>" 
                                   class="btn btn-sm btn-info">
                                    <i class="bi bi-eye"></i> View
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="text-center mt-3">
                <a href="my_permissions.php" class="btn btn-outline-primary">
                    View All Permissions
                </a>
            </div>
        <?php else: ?>
            <div class="text-center py-4">
                <p class="text-muted">You haven't made any permission requests yet.</p>
                <a href="permissions.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Create Your First Request
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Quick Links -->
<div class="row mt-4">
    <div class="col-md-4">
        <div class="card quick-link-card">
            <div class="card-body text-center">
                <i class="bi bi-plus-circle display-4 text-primary mb-3"></i>
                <h5>New Permission</h5>
                <p>Submit a new permission request</p>
                <a href="permissions.php" class="btn btn-primary">Get Started</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card quick-link-card">
            <div class="card-body text-center">
                <i class="bi bi-clipboard-check display-4 text-success mb-3"></i>
                <h5>My Permissions</h5>
                <p>View all your permission requests</p>
                <a href="my_permissions.php" class="btn btn-success">View All</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card quick-link-card">
            <div class="card-body text-center">
                <i class="bi bi-person-circle display-4 text-info mb-3"></i>
                <h5>My Profile</h5>
                <p>Update your personal information</p>
                <a href="profile.php" class="btn btn-info">Edit Profile</a>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>