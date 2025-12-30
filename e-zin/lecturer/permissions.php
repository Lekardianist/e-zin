<?php
// ===========================
// DATABASE CONNECTION
// ===========================
require_once '../config/database.php'; // pastikan $pdo ada di sini

// Start session early and ensure user is lecturer before any redirect/output
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'lecturer') {
    header('Location: ../index.php');
    exit();
}

// ===========================
// HANDLE APPROVE / REJECT (run before any HTML output)
// ===========================
if (isset($_GET['action'], $_GET['id'])) {
    if (in_array($_GET['action'], ['approve', 'reject'])) {
        $status = $_GET['action'] === 'approve' ? 'approved' : 'rejected';

        $stmt = $pdo->prepare("UPDATE permissions SET status = ? WHERE id = ?");
        $stmt->execute([$status, $_GET['id']]);

        header("Location: permissions.php?updated=1");
        exit;
    }
}

// ===========================
// LOAD HEADER (START SESSION + render)
// ===========================
require_once 'includes/header.php';

// ===========================
// SEARCH & FILTER
// ===========================
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? 'all';

$query = "SELECT p.*, e.name, e.email 
          FROM permissions p
          JOIN employees e ON p.user_id = e.user_id
          WHERE e.role = 'student' AND p.lecturer_id = ?";

$params = [$_SESSION['user_id']];

if (!empty($search)) {
    $query .= " AND (
        e.name LIKE ? OR 
        e.email LIKE ? OR 
        p.permission_type LIKE ? OR 
        p.detail_permission LIKE ?
    )";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

if ($filter !== 'all') {
    $query .= " AND p.status = ?";
    $params[] = $filter;
}

$query .= " ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$permissions = $stmt->fetchAll();
?>

<div class="container-fluid py-3">

<h2 class="mb-3">Permission Management</h2>

<?php if (isset($_GET['updated'])): ?>
<div class="alert alert-success">
    Permission updated successfully
</div>
<?php endif; ?>

<form method="get" class="row g-2 mb-3">
    <div class="col-md-8">
        <input type="text"
               name="search"
               class="form-control"
               placeholder="Search..."
               value="<?= htmlspecialchars($search) ?>">
    </div>
    <div class="col-md-3">
        <select name="filter" class="form-select" onchange="this.form.submit()">
            <?php foreach (['all','pending','approved','rejected'] as $f): ?>
                <option value="<?= $f ?>" <?= $filter === $f ? 'selected' : '' ?>>
                    <?= ucfirst($f) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-1">
        <button class="btn btn-primary w-100">Go</button>
    </div>
</form>

<?php if ($permissions): ?>
<div class="table-responsive">
<table class="table table-hover align-middle">
<thead>
<tr>
    <th>Date</th>
    <th>Student</th>
    <th>Type</th>
    <th>Status</th>
    <th>Action</th>
</tr>
</thead>
<tbody>

<?php foreach ($permissions as $p): ?>
<tr>
<td><?= date('d M Y H:i', strtotime($p['created_at'])) ?></td>

<td>
    <strong><?= htmlspecialchars($p['name']) ?></strong><br>
    <small><?= htmlspecialchars($p['email']) ?></small>
</td>

<td><?= htmlspecialchars($p['permission_type']) ?></td>

<td>
    <span class="badge bg-<?=
        $p['status'] === 'approved' ? 'success' :
        ($p['status'] === 'rejected' ? 'danger' : 'warning')
    ?>">
        <?= ucfirst($p['status']) ?>
    </span>
</td>

<td>
<div class="btn-group btn-group-sm">

<button class="btn btn-info"
        data-bs-toggle="modal"
        data-bs-target="#modal-<?= $p['id'] ?>">
    <i class="bi bi-eye"></i> View
</button>

<?php if ($p['status'] === 'pending'): ?>
<a class="btn btn-success"
   onclick="return confirm('Approve this permission?')"
   href="?action=approve&id=<?= $p['id'] ?>">
   Approve
</a>

<a class="btn btn-danger"
   onclick="return confirm('Reject this permission?')"
   href="?action=reject&id=<?= $p['id'] ?>">
   Reject
</a>
<?php endif; ?>

</div>
</td>
</tr>
<?php endforeach; ?>

</tbody>
</table>
</div>
<?php else: ?>
<p class="text-muted text-center py-5">
    No permission requests found.
</p>
<?php endif; ?>

</div>

<!-- ===========================
     MODALS
=========================== -->
<?php foreach ($permissions as $p): ?>
<div class="modal fade" id="modal-<?= $p['id'] ?>" tabindex="-1">
<div class="modal-dialog modal-lg modal-dialog-centered">
<div class="modal-content">

<div class="modal-header bg-primary text-white">
<h5 class="modal-title">Permission Detail</h5>
<button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">
<p><strong>Name:</strong> <?= htmlspecialchars($p['name']) ?></p>
<p><strong>Email:</strong> <?= htmlspecialchars($p['email']) ?></p>
<p><strong>Type:</strong> <?= htmlspecialchars($p['permission_type']) ?></p>
<p><strong>Status:</strong> <?= ucfirst($p['status']) ?></p>
<hr>
<p><?= nl2br(htmlspecialchars($p['detail_permission'])) ?></p>

<?php if (!empty($p['attachment_file'])): ?>
<hr>
<a class="btn btn-outline-primary"
   href="../uploads/permissions/<?= htmlspecialchars($p['attachment_file']) ?>"
   target="_blank">
   <i class="bi bi-paperclip"></i> View Attachment
</a>
<?php endif; ?>
</div>

<div class="modal-footer">
<button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
</div>

</div>
</div>
</div>
<?php endforeach; ?>

<?php require_once 'includes/footer.php'; ?>
