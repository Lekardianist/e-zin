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

// Get lecturer's information
$lecturer_stmt = $pdo->prepare("SELECT * FROM employees WHERE user_id = ?");
$lecturer_stmt->execute([$_SESSION['user_id']]);
$lecturer = $lecturer_stmt->fetch();

// Get counts for lecturer's dashboard
// Total students in lecturer's subject/department
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM employees 
                      WHERE role = 'student' 
                      AND (department = ? OR subject LIKE ?)");
$stmt->execute([$lecturer['department'], '%' . $lecturer['subject'] . '%']);
$total_students = $stmt->fetch()['total'];

// Total permissions from lecturer's students
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM permissions p 
                      WHERE p.lecturer_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$total_permissions = $stmt->fetch()['total'];

// Pending permissions from lecturer's students
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM permissions p 
                      WHERE p.lecturer_id = ?
                      AND p.status = 'pending'");
$stmt->execute([$_SESSION['user_id']]);
$pending_permissions = $stmt->fetch()['total'];

// Approved permissions from lecturer's students
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM permissions p 
                      WHERE p.lecturer_id = ?
                      AND p.status = 'approved'");
$stmt->execute([$_SESSION['user_id']]);
$approved_permissions = $stmt->fetch()['total'];
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Dashboard</h1>
    <div class="text-muted">
        <i class="bi bi-person-badge"></i> Lecturer Panel
    </div>
</div>

<!-- Lecturer Profile Card -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-3 text-center">
                <div class="lecturer-avatar mb-3">
                    <i class="bi bi-person-circle"></i>
                </div>
            </div>
            <div class="col-md-9">
                <h3 class="mb-2"><?php echo htmlspecialchars($lecturer['name']); ?></h3>
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-1"><i class="bi bi-person-badge"></i> <strong>ID:</strong> <?php echo $lecturer['user_id']; ?></p>
                        <p class="mb-1"><i class="bi bi-envelope"></i> <strong>Email:</strong> <?php echo htmlspecialchars($lecturer['email']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1"><i class="bi bi-book"></i> <strong>Subject:</strong> <?php echo $lecturer['subject']; ?></p>
                        <p class="mb-1"><i class="bi bi-building"></i> <strong>Department:</strong> <?php echo $lecturer['department']; ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-white bg-primary mb-3">
            <div class="card-body">
                <h5 class="card-title">My Students</h5>
                <h2><?php echo $total_students; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-info mb-3">
            <div class="card-body">
                <h5 class="card-title">Total Requests</h5>
                <h2><?php echo $total_permissions; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-warning mb-3">
            <div class="card-body">
                <h5 class="card-title">Pending</h5>
                <h2><?php echo $pending_permissions; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-success mb-3">
            <div class="card-body">
                <h5 class="card-title">Approved</h5>
                <h2><?php echo $approved_permissions; ?></h2>
            </div>
        </div>
    </div>
</div>

<!-- Recent Permissions from Students -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Recent Permission Requests from Students</h5>
        <a href="permissions.php" class="btn btn-sm btn-primary">View All</a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Student ID</th>
                        <th>Permission Type</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->prepare("SELECT p.*, e.name, e.user_id FROM permissions p 
                                          JOIN employees e ON p.user_id = e.user_id 
                                          WHERE p.lecturer_id = ?
                                          ORDER BY p.created_at DESC LIMIT 5");
                    $stmt->execute([$_SESSION['user_id']]);
                    $permissions = $stmt->fetchAll();
                    
                    if (count($permissions) > 0):
                        foreach ($permissions as $row):
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo $row['user_id']; ?></td>
                        <td><?php echo $row['permission_type']; ?></td>
                        <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                        <td>
                            <span class="badge bg-<?php 
                                echo $row['status'] == 'approved' ? 'success' : 
                                       ($row['status'] == 'rejected' ? 'danger' : 'warning');
                            ?>">
                                <?php echo ucfirst($row['status']); ?>
                            </span>
                        </td>
                        <td>
                            <a href="permissions.php?action=view&id=<?php echo $row['id']; ?>" 
                               class="btn btn-sm btn-info">View</a>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr>
                        <td colspan="6" class="text-center py-3">
                            <i class="bi bi-clipboard-x text-muted display-6"></i>
                            <p class="mt-2 mb-0 text-muted">No permission requests from students yet.</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- My Students List -->
<div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Student ID</th>
                        <th>Email</th>
                        <th>Subject</th>
                        <th>Department</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->prepare("SELECT * FROM employees 
                                          WHERE role = 'student' 
                                          AND (department = ? OR subject LIKE ?)
                                          ORDER BY name ASC LIMIT 5");
                    $stmt->execute([$lecturer['department'], '%' . $lecturer['subject'] . '%']);
                    $students = $stmt->fetchAll();
                    
                    if (count($students) > 0):
                        foreach ($students as $student):
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($student['name']); ?></td>
                        <td><?php echo $student['user_id']; ?></td>
                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                        <td><?php echo htmlspecialchars($student['subject']); ?></td>
                        <td><?php echo $student['department']; ?></td>
                        <td>
                            <a href="students.php?action=view&id=<?php echo $student['id']; ?>" 
                               class="btn btn-sm btn-outline-info">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr>
                        <td colspan="6" class="text-center py-3">
                            <i class="bi bi-people text-muted display-6"></i>
                            <p class="mt-2 mb-0 text-muted">No students assigned yet.</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.lecturer-avatar {
    width: 100px;
    height: 100px;
    background: linear-gradient(135deg, #4e54c8 0%, #8f94fb 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    font-size: 48px;
    color: white;
}

.card {
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    margin-bottom: 20px;
}

.card-body h3 {
    color: #2c3e50;
    font-weight: 600;
}

.card-body p {
    margin-bottom: 5px;
    color: #555;
}

.card-body i {
    width: 20px;
    color: #4e54c8;
}

.badge {
    font-size: 0.85em;
    padding: 5px 10px;
}

.btn-sm {
    padding: 3px 8px;
    font-size: 0.875rem;
}
</style>

<?php require_once 'includes/footer.php'; ?>