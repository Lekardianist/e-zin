<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header('Location: ../login.php');
    exit();
}

// Get student data
require_once '../config/database.php';
$stmt = $pdo->prepare("SELECT * FROM employees WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch();

// Get recent approved/rejected permissions for notification
$notif_stmt = $pdo->prepare("SELECT id, permission_type, status, created_at AS updated_at FROM permissions 
                             WHERE user_id = ? AND status IN ('approved', 'rejected')
                             ORDER BY created_at DESC LIMIT 5");
$notif_stmt->execute([$_SESSION['user_id']]);
$recent_updates = $notif_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-zin - Student</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/student-style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark student-navbar">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-mortarboard-fill"></i> E-zin Student
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#studentNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="studentNavbar">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" 
                           href="dashboard.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'permissions.php' ? 'active' : ''; ?>" 
                           href="permissions.php">
                            <i class="bi bi-plus-circle"></i> Request Permission
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'my_permissions.php' ? 'active' : ''; ?>" 
                           href="my_permissions.php">
                            <i class="bi bi-clipboard-check"></i> My Permissions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>" 
                           href="profile.php">
                            <i class="bi bi-person-circle"></i> Profile
                        </a>
                    </li>
                </ul>
                <div class="navbar-nav">
                    <div class="nav-item dropdown me-3">
                        <button class="btn btn-outline-light btn-sm position-relative" 
                                type="button" data-bs-toggle="dropdown" id="notifBell">
                            <i class="bi bi-bell"></i>
                            <?php 
                            $unread_count = 0;
                            foreach($recent_updates as $update) {
                                $unread_count++;
                            }
                            if ($unread_count > 0): 
                            ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?php echo $unread_count; ?>
                                </span>
                            <?php endif; ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><h6 class="dropdown-header">Notification</h6></li>
                            <?php if (count($recent_updates) > 0): ?>
                                <?php foreach($recent_updates as $update): ?>
                                <li>
                                    <a class="dropdown-item" href="my_permissions.php">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <i class="bi bi-<?php echo $update['status'] == 'approved' ? 'check-circle text-success' : 'x-circle text-danger'; ?>"></i>
                                                <strong><?php echo $update['permission_type']; ?></strong> - 
                                                <span class="badge bg-<?php echo $update['status'] == 'approved' ? 'success' : 'danger'; ?>">
                                                    <?php echo ucfirst($update['status']); ?>
                                                </span>
                                                <br>
                                                <small class="text-muted"><?php echo date('d M H:i', strtotime($update['updated_at'])); ?></small>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                                <li><hr class="dropdown-divider"></li>
                            <?php else: ?>
                                <li>
                                    <a class="dropdown-item text-muted" href="#">
                                        <i class="bi bi-check-circle text-success"></i> No updates yet
                                    </a>
                                </li>
                            <?php endif; ?>
                            <li><a class="dropdown-item" href="my_permissions.php">View all permissions</a></li>
                        </ul>
                    </div>
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" data-bs-toggle="dropdown">
                            <i class="bi bi-person"></i> <?php echo $_SESSION['name']; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php">
                                <i class="bi bi-person"></i> My Profile
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="../logout.php">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-3">
        <div class="row">
            <!-- Student Info Sidebar -->
            <div class="col-md-3 mb-3">
                <div class="card student-info-card">
                    <div class="card-body text-center">
                        <div class="student-avatar mb-3">
                            <i class="bi bi-person-circle"></i>
                        </div>
                        <h5 class="card-title"><?php echo $student['name']; ?></h5>
                        <p class="card-text text-muted">
                            <i class="bi bi-mortarboard"></i> Student
                        </p>
                        <div class="student-details">
                            <p><i class="bi bi-person-badge"></i> <?php echo $student['user_id']; ?></p>
                            <p><i class="bi bi-envelope"></i> <?php echo $student['email']; ?></p>
                            <?php if(!empty($student['department'])): ?>
                                <p><i class="bi bi-building"></i> <?php echo $student['department']; ?></p>
                            <?php endif; ?>
                            <?php if(!empty($student['subject'])): ?>
                                <p><i class="bi bi-book"></i> <?php echo $student['subject']; ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-bar-chart"></i> My Stats</h6>
                    </div>
                    <div class="card-body">
                        <?php
                        // Get student statistics
                        $statsStmt = $pdo->prepare("SELECT 
                            (SELECT COUNT(*) FROM permissions WHERE user_id = ?) as total,
                            (SELECT COUNT(*) FROM permissions WHERE user_id = ? AND status = 'approved') as approved,
                            (SELECT COUNT(*) FROM permissions WHERE user_id = ? AND status = 'pending') as pending,
                            (SELECT COUNT(*) FROM permissions WHERE user_id = ? AND status = 'rejected') as rejected");
                        $statsStmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);
                        $stats = $statsStmt->fetch();
                        ?>
                        <div class="row text-center">
                            <div class="col-6 mb-2">
                                <small class="text-muted">Total</small>
                                <h5><?php echo $stats['total']; ?></h5>
                            </div>
                            <div class="col-6 mb-2">
                                <small class="text-muted">Approved</small>
                                <h5 class="text-success"><?php echo $stats['approved']; ?></h5>
                            </div>
                            <div class="col-6 mb-2">
                                <small class="text-muted">Pending</small>
                                <h5 class="text-warning"><?php echo $stats['pending']; ?></h5>
                            </div>
                            <div class="col-6 mb-2">
                                <small class="text-muted">Rejected</small>
                                <h5 class="text-danger"><?php echo $stats['rejected']; ?></h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <main class="col-md-9">