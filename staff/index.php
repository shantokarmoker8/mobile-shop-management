<?php
require_once __DIR__ . '/../includes/auth.php';

// Staff module is admin-only
if ($_SESSION['role'] !== 'admin') {
    echo '<div class="alert alert-danger m-4">You do not have permission to access this section.</div>';
    exit;
}

$page_title = 'Staff Management';

$search = clean($_GET['search'] ?? '');

$query = "SELECT * FROM users WHERE role = 'staff'";
$params = [];

if ($search !== '') {
    $query .= " AND (full_name LIKE ? OR username LIKE ? OR phone LIKE ?)";
    $like = "%$search%";
    $params = [$like, $like, $like];
}

$query .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$staff = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-body">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h5 class="fw-bold mb-0">Staff Management</h5>
            <a href="add.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Add Staff</a>
        </div>

        <div class="card-panel mb-3">
            <form method="GET" class="row g-2">
                <div class="col-md-6">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by name, username, or phone" value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-search me-1"></i>Search</button>
                </div>
            </form>
        </div>

        <div class="card-panel">
            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr>
                            <th>Full Name</th>
                            <th>Username</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($staff) === 0): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">No staff found.</td></tr>
                        <?php endif; ?>

                        <?php foreach ($staff as $s): ?>
                        <tr>
                            <td class="fw-semibold"><?= htmlspecialchars($s['full_name']) ?></td>
                            <td><?= htmlspecialchars($s['username']) ?></td>
                            <td><?= htmlspecialchars($s['phone'] ?: '-') ?></td>
                            <td>
                                <span class="badge-status <?= $s['status'] === 'active' ? 'badge-completed' : 'badge-due' ?>">
                                    <?= ucfirst($s['status']) ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="permission.php?id=<?= $s['id'] ?>" class="btn btn-soft btn-sm" title="Permissions"><i class="bi bi-shield-lock"></i></a>
                                <a href="edit.php?id=<?= $s['id'] ?>" class="btn btn-soft btn-sm" title="Edit"><i class="bi bi-pencil"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>