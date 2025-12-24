<?php
require_once 'includes/header.php';
require_once 'config/database.php';

// Get counts
$stmt = $pdo->query("SELECT COUNT(*) as total FROM employees");
$total_employees = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM employees WHERE role = 'lecturer'");
$lecturers = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM employees WHERE role = 'staff'");
$staff = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM employees WHERE role = 'student'");
$students = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM permissions WHERE status = 'pending'");
$pending = $stmt->fetch()['total'];
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Dashboard</h1>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-white bg-primary mb-3">
            <div class="card-body">
                <h5 class="card-title">Total Employees</h5>
                <h2><?php echo $total_employees; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-success mb-3">
            <div class="card-body">
                <h5 class="card-title">Lecturers</h5>
                <h2><?php echo $lecturers; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-warning mb-3">
            <div class="card-body">
                <h5 class="card-title">Staff</h5>
                <h2><?php echo $staff; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-info mb-3">
            <div class="card-body">
                <h5 class="card-title">Students</h5>
                <h2><?php echo $students; ?></h2>
            </div>
        </div>
    </div>
</div>

<!-- Recent Permissions -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Recent Permission Requests</h5>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <input type="text" class="form-control" placeholder="Search Employee by name, role, ID or any related keywords">
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>User ID</th>
                        <th>Role</th>
                        <th>Permission Type</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->query("SELECT p.*, e.name, e.role FROM permissions p 
                                       JOIN employees e ON p.user_id = e.user_id 
                                       ORDER BY p.created_at DESC LIMIT 5");
                    while ($row = $stmt->fetch()):
                    ?>
                    <tr>
                        <td><?php echo $row['name']; ?></td>
                        <td><?php echo $row['user_id']; ?></td>
                        <td><span class="badge bg-<?php echo $row['role'] == 'lecturer' ? 'primary' : ($row['role'] == 'staff' ? 'success' : 'warning'); ?>">
                            <?php echo ucfirst($row['role']); ?>
                        </span></td>
                        <td><?php echo $row['permission_type']; ?></td>
                        <td><span class="badge bg-<?php echo $row['status'] == 'approved' ? 'success' : ($row['status'] == 'rejected' ? 'danger' : 'warning'); ?>">
                            <?php echo ucfirst($row['status']); ?>
                        </span></td>
                        <td>
                            <a href="permissions.php?action=view&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info">View</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>