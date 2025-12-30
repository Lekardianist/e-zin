<?php
require_once 'includes/header.php';
require_once 'config/database.php';

/* ===========================
   PASTIKAN KOLOM STATUS ADA
=========================== */
try {
    $pdo->exec("
        ALTER TABLE users 
        ADD COLUMN IF NOT EXISTS status 
        ENUM('active','inactive') DEFAULT 'active'
    ");
} catch (PDOException $e) {
    // Abaikan jika sudah ada
}

/* ===========================
   TAMBAH / UPDATE EMPLOYEE
=========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_employee'])) {

    $user_id    = $_POST['user_id'];
    $name       = $_POST['name'];
    $email      = $_POST['email'];
    $phone      = $_POST['phone'];
    $subject    = $_POST['subject'];
    $department = $_POST['department'];
    $role       = $_POST['role'];

    try {
        $pdo->beginTransaction();

        /* ===========================
           CEK USER
        =========================== */
        $checkUser = $pdo->prepare("
            SELECT COUNT(*) 
            FROM users 
            WHERE user_id = ? OR email = ?
        ");
        $checkUser->execute([$user_id, $email]);
        $userExists = $checkUser->fetchColumn();

        /* ===========================
           PASSWORD DEFAULT BY ROLE
        =========================== */
        switch ($role) {
            case 'lecturer':
                $plainPassword = 'lecturer123';
                break;
            case 'student':
                $plainPassword = 'student123';
                break;
            case 'staff':
                $plainPassword = 'staff123';
                break;
            default:
                $plainPassword = 'password123';
        }

        $defaultPassword = md5($plainPassword);

        /* ===========================
           INSERT / UPDATE USERS
        =========================== */
        if ($userExists == 0) {

            $userStmt = $pdo->prepare("
                INSERT INTO users 
                (user_id, name, email, password, role, status)
                VALUES (?, ?, ?, ?, ?, 'active')
            ");
            $userStmt->execute([
                $user_id,
                $name,
                $email,
                $defaultPassword,
                $role
            ]);

        } else {

            $userStmt = $pdo->prepare("
                UPDATE users 
                SET name = ?, email = ?, role = ?
                WHERE user_id = ?
            ");
            $userStmt->execute([
                $name,
                $email,
                $role,
                $user_id
            ]);
        }

        /* ===========================
           CEK EMPLOYEE
        =========================== */
        $checkEmp = $pdo->prepare("
            SELECT COUNT(*) 
            FROM employees 
            WHERE user_id = ?
        ");
        $checkEmp->execute([$user_id]);
        $empExists = $checkEmp->fetchColumn();

        if ($empExists == 0) {

            $stmt = $pdo->prepare("
                INSERT INTO employees
                (user_id, name, email, phone, subject, department, role)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $user_id,
                $name,
                $email,
                $phone,
                $subject,
                $department,
                $role
            ]);

            $success = "Employee added successfully!";

        } else {

            $stmt = $pdo->prepare("
                UPDATE employees 
                SET name = ?, email = ?, phone = ?, subject = ?, department = ?, role = ?
                WHERE user_id = ?
            ");
            $stmt->execute([
                $name,
                $email,
                $phone,
                $subject,
                $department,
                $role,
                $user_id
            ]);

            $success = "Employee updated successfully!";
        }

        $pdo->commit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}

/* ===========================
   DELETE EMPLOYEE
=========================== */
if (isset($_GET['delete'])) {

    $id = $_GET['delete'];

    try {
        $pdo->beginTransaction();

        $getUser = $pdo->prepare("
            SELECT user_id FROM employees WHERE id = ?
        ");
        $getUser->execute([$id]);
        $employee = $getUser->fetch();

        if ($employee) {

            $pdo->prepare("
                DELETE FROM employees WHERE id = ?
            ")->execute([$id]);

            $checkPerm = $pdo->prepare("
                SELECT COUNT(*) FROM permissions WHERE user_id = ?
            ");
            $checkPerm->execute([$employee['user_id']]);

            $checkEmp = $pdo->prepare("
                SELECT COUNT(*) FROM employees WHERE user_id = ?
            ");
            $checkEmp->execute([$employee['user_id']]);

            if ($checkPerm->fetchColumn() == 0 && $checkEmp->fetchColumn() == 0) {
                $pdo->prepare("
                    DELETE FROM users WHERE user_id = ?
                ")->execute([$employee['user_id']]);
            }
        }

        $pdo->commit();
        $success = "Employee deleted successfully!";

    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Delete failed: " . $e->getMessage();
    }
}

/* ===========================
   SEARCH EMPLOYEE
=========================== */
$search = $_GET['search'] ?? '';
$query = "SELECT * FROM employees WHERE 1=1";
$params = [];

if ($search) {
    $query .= "
        AND (
            name LIKE ? OR
            user_id LIKE ? OR
            email LIKE ? OR
            role LIKE ? OR
            subject LIKE ? OR
            department LIKE ?
        )
    ";
    $params = array_fill(0, 6, "%$search%");
}

$query .= " ORDER BY role, name";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$employees = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Employees</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
        <i class="bi bi-person-plus"></i> Add Employee
    </button>
</div>

<!-- Success/Error Messages -->
<?php if(isset($success)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $success; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if(isset($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">All Employees</h5>
        <div>
            <span class="badge bg-primary me-2">Lecturer</span>
            <span class="badge bg-success me-2">Staff</span>
            <span class="badge bg-warning">Student</span>
        </div>
    </div>
    <div class="card-body">
        <form method="GET" action="" class="mb-3">
            <div class="input-group">
                <input type="text" class="form-control" name="search" 
                       placeholder="Search Employee by name, role, ID or any related keywords"
                       value="<?php echo htmlspecialchars($search); ?>">
                <button class="btn btn-primary" type="submit">
                    <i class="bi bi-search"></i> Search
                </button>
                <?php if(!empty($search)): ?>
                    <a href="employees.php" class="btn btn-secondary">Clear</a>
                <?php endif; ?>
            </div>
        </form>
        
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>User ID</th>
                        <th>Contact</th>
                        <th>Subject/Position</th>
                        <th>Department</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($employees) > 0): ?>
                        <?php foreach($employees as $emp): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($emp['name']); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($emp['email']); ?></small>
                            </td>
                            <td>
                                <code><?php echo $emp['user_id']; ?></code>
                            </td>
                            <td>
                                <?php if(!empty($emp['phone'])): ?>
                                    <small><?php echo $emp['phone']; ?></small>
                                <?php else: ?>
                                    <small class="text-muted">-</small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($emp['subject']); ?></td>
                            <td>
                                <?php if(!empty($emp['department'])): ?>
                                    <span class="badge bg-info"><?php echo $emp['department']; ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $emp['role'] == 'lecturer' ? 'primary' : ($emp['role'] == 'staff' ? 'success' : 'warning'); ?>">
                                    <i class="bi bi-<?php echo $emp['role'] == 'lecturer' ? 'person' : ($emp['role'] == 'staff' ? 'briefcase' : 'person-badge'); ?>"></i>
                                    <?php echo ucfirst($emp['role']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-info" data-bs-toggle="modal" 
                                            data-bs-target="#editEmployeeModal<?php echo $emp['id']; ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-outline-danger" onclick="confirmDelete(<?php echo $emp['id']; ?>)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        
                        <!-- Edit Modal for each employee -->
                        <div class="modal fade" id="editEmployeeModal<?php echo $emp['id']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Employee</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form method="POST" action="">
                                        <div class="modal-body">
                                            <input type="hidden" name="employee_id" value="<?php echo $emp['id']; ?>">
                                            <input type="hidden" name="user_id" value="<?php echo $emp['user_id']; ?>">
                                            <div class="mb-3">
                                                <label class="form-label">User ID</label>
                                                <input type="text" class="form-control" value="<?php echo $emp['user_id']; ?>" readonly>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Name *</label>
                                                <input type="text" class="form-control" name="name" 
                                                       value="<?php echo htmlspecialchars($emp['name']); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Email *</label>
                                                <input type="email" class="form-control" name="email" 
                                                       value="<?php echo htmlspecialchars($emp['email']); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Phone</label>
                                                <input type="text" class="form-control" name="phone" 
                                                       value="<?php echo htmlspecialchars($emp['phone'] ?? ''); ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Subject/Position</label>
                                                <input type="text" class="form-control" name="subject" 
                                                       value="<?php echo htmlspecialchars($emp['subject']); ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Department</label>
                                                <input type="text" class="form-control" name="department" 
                                                       value="<?php echo htmlspecialchars($emp['department'] ?? ''); ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Role *</label>
                                                <select class="form-select" name="role" required>
                                                    <option value="lecturer" <?php echo $emp['role'] == 'lecturer' ? 'selected' : ''; ?>>Lecturer</option>
                                                    <option value="staff" <?php echo $emp['role'] == 'staff' ? 'selected' : ''; ?>>Staff</option>
                                                    <option value="student" <?php echo $emp['role'] == 'student' ? 'selected' : ''; ?>>Student</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" name="edit_employee" class="btn btn-primary">Save Changes</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <p class="text-muted">No employees found.</p>
                                <a href="employees.php" class="btn btn-primary">View All Employees</a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Employee Modal -->
<div class="modal fade" id="addEmployeeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Employee</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">User ID *</label>
                        <input type="text" class="form-control" name="user_id" 
                               placeholder="e.g., #23454GHSJ7YT6" required id="user_id_input">
                        <small class="text-muted">Unique identifier for the employee</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Full Name *</label>
                        <input type="text" class="form-control" name="name" 
                               placeholder="e.g., Julio Lekardianist" required id="name_input">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Address *</label>
                        <input type="email" class="form-control" name="email" 
                               placeholder="e.g., lekardianist@gmail.com" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone Number</label>
                        <input type="text" class="form-control" name="phone" 
                               placeholder="e.g., 081234567890">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Role *</label>
                            <select class="form-select" name="role" required id="role_select">
                                <option value="">Select Role</option>
                                <option value="lecturer">Lecturer</option>
                                <option value="staff">Staff</option>
                                <option value="student">Student</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Department</label>
                            <input type="text" class="form-control" name="department" 
                                   placeholder="e.g., Computer Science" id="department_input">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subject/Position</label>
                        <input type="text" class="form-control" name="subject" 
                               placeholder="e.g., PBO Teknologi Rekayasa Multimedia" id="subject_input">
                        <small class="text-muted">For lecturers: Subject. For staff: Position</small>
                    </div>
                    <div class="mb-3">
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="generateUserId()">
                            <i class="bi bi-shuffle"></i> Generate User ID
                        </button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_employee" class="btn btn-primary">Add Employee</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function confirmDelete(id) {
    if (confirm("Are you sure you want to delete this employee?")) {
        window.location.href = 'employees.php?delete=' + id;
    }
}

function generateUserId() {
    const roleSelect = document.getElementById('role_select');
    const nameInput = document.getElementById('name_input');
    const userIdInput = document.getElementById('user_id_input');
    const departmentInput = document.getElementById('department_input');
    const subjectInput = document.getElementById('subject_input');
    
    if (roleSelect.value && nameInput.value) {
        const role = roleSelect.value;
        const name = nameInput.value.trim();
        const random = Math.random().toString(36).substring(2, 10).toUpperCase();
        const date = new Date();
        const timestamp = date.getFullYear().toString().substr(-2) + 
                         String(date.getMonth() + 1).padStart(2, '0') + 
                         String(date.getDate()).padStart(2, '0');
        
        // Get initials from name
        const nameParts = name.split(' ');
        let initials = '';
        if (nameParts.length >= 2) {
            initials = nameParts[0].charAt(0) + nameParts[1].charAt(0);
        } else if (nameParts.length === 1) {
            initials = nameParts[0].substring(0, 2);
        }
        initials = initials.toUpperCase();
        
        let prefix = '';
        switch(role) {
            case 'lecturer': prefix = 'LEC'; break;
            case 'staff': prefix = 'STF'; break;
            case 'student': prefix = 'STU'; break;
        }
        
        userIdInput.value = `#${prefix}${initials}${timestamp}${random.substring(0, 4)}`;
        
        // Auto-fill department and subject based on role
        if (!departmentInput.value) {
            switch(role) {
                case 'lecturer':
                    departmentInput.placeholder = 'e.g., Computer Science Department';
                    subjectInput.placeholder = 'e.g., PBO Teknologi Rekayasa Multimedia';
                    break;
                case 'staff':
                    departmentInput.placeholder = 'e.g., General Affairs Department';
                    subjectInput.placeholder = 'e.g., Office Boy, Security Officer';
                    break;
                case 'student':
                    departmentInput.placeholder = 'e.g., Information Technology';
                    subjectInput.placeholder = 'e.g., Backend Engineer Part time';
                    break;
            }
        }
    } else {
        alert('Please select a role and enter a name first.');
    }
}

// Auto-generate email if not filled
document.addEventListener('DOMContentLoaded', function() {
    const nameInput = document.getElementById('name_input');
    const emailInput = document.querySelector('input[name="email"]');
    
    if (nameInput && emailInput) {
        nameInput.addEventListener('blur', function() {
            if (this.value && !emailInput.value) {
                const name = this.value.toLowerCase().replace(/\s+/g, '.');
                const domains = ['gmail.com', 'yahoo.com', 'university.edu', 'company.com'];
                const randomDomain = domains[Math.floor(Math.random() * domains.length)];
                emailInput.value = name + '@' + randomDomain;
            }
        });
    }
    
    // Show success message for 5 seconds
    const successAlert = document.querySelector('.alert-success');
    if (successAlert) {
        setTimeout(() => {
            successAlert.classList.remove('show');
            successAlert.classList.add('fade');
        }, 5000);
    }
    
    // Auto-focus search input if it exists
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        searchInput.focus();
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>