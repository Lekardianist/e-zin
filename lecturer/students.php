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

// Handle search
$search = $_GET['search'] ?? '';

// Get students from lecturer's department/subject
$query = "SELECT * FROM employees 
          WHERE role = 'student' 
          AND (department = ? OR subject LIKE ?)";
$params = [$lecturer['department'], '%' . $lecturer['subject'] . '%'];

if (!empty($search)) {
    $query .= " AND (name LIKE ? OR user_id LIKE ? OR email LIKE ? OR subject LIKE ?)";
    $searchTerm = "%$search%";
    array_push($params, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
}

$query .= " ORDER BY name ASC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">My Students</h1>
    <div class="text-muted">
        <i class="bi bi-person-badge"></i> Lecturer: <?php echo htmlspecialchars($lecturer['name']); ?>
    </div>
</div>

<!-- Lecturer Info Card -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-8">
                <h5><i class="bi bi-person-circle"></i> Lecturer Information</h5>
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($lecturer['name']); ?></p>
                        <p class="mb-1"><strong>Subject:</strong> <?php echo $lecturer['subject']; ?></p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Department:</strong> <?php echo $lecturer['department']; ?></p>
                        <p class="mb-0"><strong>Email:</strong> <?php echo htmlspecialchars($lecturer['email']); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 text-end">
                <h5><?php echo count($students); ?></h5>
                <p class="text-muted mb-0">Total Students</p>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Student List</h5>
        <div class="text-muted">
            Showing <?php echo count($students); ?> students
        </div>
    </div>
    <div class="card-body">
        <form method="GET" action="" class="mb-4">
            <div class="input-group">
                <input type="text" class="form-control" name="search" 
                       placeholder="Search students by name, ID, email, or subject..."
                       value="<?php echo htmlspecialchars($search); ?>">
                <button class="btn btn-primary" type="submit">
                    <i class="bi bi-search"></i> Search
                </button>
            </div>
        </form>
        
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Student ID</th>
                        <th>Email</th>
                        <th>Subject</th>
                        <th>Department</th>
                        <th>Phone</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($students) > 0): ?>
                        <?php foreach($students as $student): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($student['name']); ?></strong>
                            </td>
                            <td>
                                <code><?php echo $student['user_id']; ?></code>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($student['email']); ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($student['subject']); ?>
                            </td>
                            <td>
                                <span class="badge bg-info"><?php echo $student['department']; ?></span>
                            </td>
                            <td>
                                <?php if(!empty($student['phone'])): ?>
                                    <small><?php echo $student['phone']; ?></small>
                                <?php else: ?>
                                    <small class="text-muted">-</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-info" data-bs-toggle="modal" 
                                            data-bs-target="#studentModal<?php echo $student['id']; ?>">
                                        <i class="bi bi-eye"></i> View
                                    </button>
                                    <a href="permissions.php?student=<?php echo $student['user_id']; ?>" 
                                       class="btn btn-outline-primary">
                                        <i class="bi bi-clipboard-check"></i> Permissions
                                    </a>
                                </div>
                            </td>
                        </tr>
                        
                        <!-- Student Detail Modal -->
                        <div class="modal fade" id="studentModal<?php echo $student['id']; ?>" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header bg-primary text-white">
                                        <h5 class="modal-title">
                                            <i class="bi bi-person-circle"></i> Student Details
                                        </h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="text-center mb-4">
                                            <div class="student-avatar mb-3">
                                                <i class="bi bi-person-circle"></i>
                                            </div>
                                            <h4><?php echo htmlspecialchars($student['name']); ?></h4>
                                            <p class="text-muted">Student</p>
                                        </div>
                                        
                                        <table class="table table-bordered">
                                            <tr>
                                                <th width="40%">Student ID</th>
                                                <td><?php echo $student['user_id']; ?></td>
                                            </tr>
                                            <tr>
                                                <th>Email</th>
                                                <td><?php echo htmlspecialchars($student['email']); ?></td>
                                            </tr>
                                            <tr>
                                                <th>Phone</th>
                                                <td><?php echo !empty($student['phone']) ? $student['phone'] : '-'; ?></td>
                                            </tr>
                                            <tr>
                                                <th>Subject</th>
                                                <td><?php echo htmlspecialchars($student['subject']); ?></td>
                                            </tr>
                                            <tr>
                                                <th>Department</th>
                                                <td><?php echo $student['department']; ?></td>
                                            </tr>
                                            <?php
                                            // Get student's permission stats
                                            $statsStmt = $pdo->prepare("SELECT 
                                                COUNT(*) as total,
                                                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                                                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                                                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
                                                FROM permissions WHERE user_id = ?");
                                            $statsStmt->execute([$student['user_id']]);
                                            $stats = $statsStmt->fetch();
                                            ?>
                                            <tr>
                                                <th>Total Permissions</th>
                                                <td><?php echo $stats['total']; ?></td>
                                            </tr>
                                            <tr>
                                                <th>Status</th>
                                                <td>
                                                    <span class="badge bg-success me-1">Approved: <?php echo $stats['approved']; ?></span>
                                                    <span class="badge bg-warning me-1">Pending: <?php echo $stats['pending']; ?></span>
                                                    <span class="badge bg-danger">Rejected: <?php echo $stats['rejected']; ?></span>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        <a href="permissions.php?student=<?php echo $student['user_id']; ?>" 
                                           class="btn btn-primary">
                                            <i class="bi bi-clipboard-check"></i> View Permissions
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <i class="bi bi-people display-1 text-muted"></i>
                                <h4 class="mt-3">No Students Found</h4>
                                <p class="text-muted">
                                    <?php if(!empty($search)): ?>
                                        Try changing your search criteria.
                                    <?php else: ?>
                                        You don't have any students assigned yet.
                                    <?php endif; ?>
                                </p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.card {
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.student-avatar {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #4e54c8 0%, #8f94fb 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    font-size: 36px;
    color: white;
}

.table-hover tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.05);
}

.btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

.badge {
    font-size: 0.85em;
    padding: 5px 10px;
}
</style>

<script>
// Auto-focus search input
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        searchInput.focus();
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>