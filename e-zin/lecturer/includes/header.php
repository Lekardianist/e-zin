<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database config FIRST before any redirect
require_once __DIR__ . '/../config/database.php';

// Cek apakah user sudah login dan role adalah lecturer
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'lecturer') {
    header('Location: index.php');
    exit();
}

// Cek apakah lecturer aktif
$stmt = $pdo->prepare("SELECT status FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || $user['status'] !== 'active') {
    session_destroy();
    header('Location: index.php?error=Account inactive or not found');
    exit();
}

// Get lecturer data for sidebar
$stmt = $pdo->prepare("SELECT * FROM employees WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$lecturer_data = $stmt->fetch();

// Get pending count for notification
$pending_stmt = $pdo->prepare("SELECT COUNT(*) as pending FROM permissions p 
                              WHERE p.lecturer_id = ?
                              AND p.status = 'pending'");
$pending_stmt->execute([$_SESSION['user_id']]);
$pending_count = $pending_stmt->fetch()['pending'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lecturer Panel - E-zin System</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --lecturer-primary: #4e54c8;
            --lecturer-secondary: #8f94fb;
            --lecturer-light: #f8f9ff;
        }
        
        .sidebar {
            background: linear-gradient(180deg, var(--lecturer-primary) 0%, var(--lecturer-secondary) 100%);
            min-height: 100vh;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            position: fixed;
            width: 250px;
            z-index: 1000;
        }
        
        .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            margin: 5px 10px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .nav-link:hover, .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        
        .nav-link i {
            width: 24px;
            font-size: 1.1rem;
        }
        
        .logo {
            color: white;
            font-weight: 600;
            font-size: 1.3rem;
            padding: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .user-profile {
            color: white;
            padding: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .user-avatar {
            width: 50px;
            height: 50px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--lecturer-primary);
            font-size: 1.5rem;
            margin-right: 15px;
        }
        
        .main-content {
            margin-left: 250px;
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        
        .navbar-custom {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 10px 20px;
        }
        
        .page-title {
            color: var(--lecturer-primary);
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                left: -250px;
                transition: left 0.3s ease;
            }
            
            .sidebar.show {
                left: 0;
            }
            
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar d-none d-md-block">
            <!-- Logo -->
            <div class="logo">
                <i class="bi bi-person-badge-fill me-2"></i>
                Lecturer Panel
            </div>
            
            <!-- User Profile -->
            <div class="user-profile d-flex align-items-center">
                <div class="user-avatar">
                    <i class="bi bi-person-circle"></i>
                </div>
                <div>
                    <h6 class="mb-0"><?php echo htmlspecialchars($_SESSION['name'] ?? 'Lecturer'); ?></h6>
                    <small class="opacity-75">Lecturer</small>
                </div>
            </div>
            
            <!-- Navigation -->
            <nav class="nav flex-column mt-3">
                <?php
                $current_page = basename($_SERVER['PHP_SELF']);
                ?>
                
                <a class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>" 
                   href="dashboard.php">
                    <i class="bi bi-speedometer2"></i>
                    Dashboard
                </a>
                
                <a class="nav-link <?php echo $current_page == 'permissions.php' ? 'active' : ''; ?>" 
                   href="permissions.php">
                    <i class="bi bi-clipboard-check"></i>
                    Permissions
                    <?php if ($pending_count > 0): ?>
                    <span class="badge bg-danger float-end"><?php echo $pending_count; ?></span>
                    <?php endif; ?>
                </a>
                
                <a class="nav-link <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>" 
                   href="profile.php">
                    <i class="bi bi-person"></i>
                    My Profile
                </a>
                
                <a class="nav-link <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>" 
                   href="settings.php">
                    <i class="bi bi-gear"></i>
                    Settings
                </a>
                
                <hr class="my-3 opacity-25 mx-3">
                
                <a class="nav-link text-warning mx-3" href="logout.php">
                    <i class="bi bi-box-arrow-right"></i>
                    Logout
                </a>
            </nav>
            
            <!-- Copyright -->
            <div class="position-absolute bottom-0 start-0 end-0 p-3">
                <small class="text-white-50">
                    &copy; <?php echo date('Y'); ?> E-zin System
                </small>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content flex-grow-1">
            <!-- Top Navbar -->
            <nav class="navbar navbar-expand-lg navbar-light bg-white navbar-custom">
                <div class="container-fluid">
                    <button class="btn btn-sm d-md-none me-2" type="button" 
                            data-bs-toggle="collapse" data-bs-target="#mobileSidebar">
                        <i class="bi bi-list"></i>
                    </button>
                    
                    <div class="d-flex align-items-center">
                        <h4 class="page-title mb-0">
                            <?php 
                            $pageTitle = 'Dashboard';
                            if ($current_page == 'permissions.php') $pageTitle = 'Permissions';
                            if ($current_page == 'students.php') $pageTitle = 'My Students';
                            if ($current_page == 'profile.php') $pageTitle = 'My Profile';
                            if ($current_page == 'settings.php') $pageTitle = 'Settings';
                            echo $pageTitle;
                            ?>
                        </h4>
                    </div>
                    
                    <div class="d-flex align-items-center">
                        <!-- Notifications -->
                        <div class="dropdown me-3">
                            <button class="btn btn-outline-secondary btn-sm position-relative" 
                                    type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-bell"></i>
                                <?php if ($pending_count > 0): ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                        <?php echo $pending_count; ?>
                                    </span>
                                <?php endif; ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><h6 class="dropdown-header">Notifications</h6></li>
                                <?php if ($pending_count > 0): ?>
                                    <li>
                                        <a class="dropdown-item" href="permissions.php?filter=pending">
                                            <i class="bi bi-exclamation-triangle text-warning"></i>
                                            You have <?php echo $pending_count; ?> pending permission requests
                                        </a>
                                    </li>
                                <?php else: ?>
                                    <li>
                                        <a class="dropdown-item" href="#">
                                            <i class="bi bi-check-circle text-success"></i>
                                            No pending requests
                                        </a>
                                    </li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="permissions.php">View all permissions</a></li>
                            </ul>
                        </div>
                        
                        <!-- User Dropdown -->
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary btn-sm dropdown-toggle d-flex align-items-center" 
                                    type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle me-2"></i>
                                <span class="d-none d-md-inline"><?php echo explode(' ', $_SESSION['name'] ?? 'User')[0]; ?></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <h6 class="dropdown-header">
                                        <i class="bi bi-person-badge"></i>
                                        <?php echo htmlspecialchars($_SESSION['name'] ?? 'User'); ?>
                                    </h6>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="profile.php">
                                        <i class="bi bi-person"></i> My Profile
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="settings.php">
                                        <i class="bi bi-gear"></i> Settings
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="logout.php">
                                        <i class="bi bi-box-arrow-right"></i> Logout
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>

            
            <!-- Mobile Sidebar -->
            <div class="collapse d-md-none" id="mobileSidebar">
                <div class="card m-3">
                    <div class="card-body">
                        <nav class="nav flex-column">
                            <a class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>" 
                               href="dashboard.php">
                                <i class="bi bi-speedometer2 me-2"></i> Dashboard
                            </a>
                            <a class="nav-link <?php echo $current_page == 'permissions.php' ? 'active' : ''; ?>" 
                               href="permissions.php">
                                <i class="bi bi-clipboard-check me-2"></i> Permissions
                            </a>
                            <a class="nav-link <?php echo $current_page == 'students.php' ? 'active' : ''; ?>" 
                               href="students.php">
                                <i class="bi bi-people me-2"></i> My Students
                            </a>
                            <a class="nav-link <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>" 
                               href="profile.php">
                                <i class="bi bi-person me-2"></i> My Profile
                            </a>
                            <a class="nav-link <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>" 
                               href="settings.php">
                                <i class="bi bi-gear me-2"></i> Settings
                            </a>
                            <hr>
                            <a class="nav-link text-danger" href="logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i> Logout
                            </a>
                        </nav>
                    </div>
                </div>
            </div>
            
            <!-- Page Content -->
            <div class="p-3">