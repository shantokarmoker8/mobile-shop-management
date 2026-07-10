<?php
require_once __DIR__ . '/../includes/auth.php';

if ($_SESSION['role'] !== 'admin') {
    echo '<div class="alert alert-danger m-4">You do not have permission to access this section.</div>';
    exit;
}

$page_title = 'Edit Staff';

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'staff'");
$stmt->execute([$id]);
$staff = $stmt->fetch();

if (!$staff) {
    setFlash('error', 'Staff not found.');
    redirect(BASE_URL . 'staff/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = clean($_POST['full_name']);
    $phone = clean($_POST['phone']);
    $status = clean($_POST['status']);
    $password = $_POST['password'];

    if ($full_name === '') {
        setFlash('error', 'Full name is required.');
    } else {
        if ($password !== '') {
            $stmt = $pdo->prepare("UPDATE users SET full_name=?, phone=?, status=?, password=? WHERE id=?");
            $stmt->execute([$full_name, $phone, $status, $password, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET full_name=?, phone=?, status=? WHERE id=?");
            $stmt->execute([$full_name, $phone, $status, $id]);
        }

        setFlash('success', 'Staff updated successfully.');
        redirect(BASE_URL . 'staff/index.php');
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">Edit Staff</h5>
            <a href="index.php" class="btn btn-soft btn-sm"><i class="bi bi-arrow-left me-1"></i>Back to List</a>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card-panel">
                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Full Name *</label>
                                <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($staff['full_name']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Phone</label>
                                <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($staff['phone']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Username</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($staff['username']) ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Status</label>
                                <select name="status" class="form-select">
                                    <option value="active" <?= $staff['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="inactive" <?= $staff['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small fw-semibold">New Password</label>
                                <input type="text" name="password" class="form-control" placeholder="Leave empty to keep current password">
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Update Staff</button>
                            <a href="permission.php?id=<?= $staff['id'] ?>" class="btn btn-soft"><i class="bi bi-shield-lock me-1"></i>Manage Permissions</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>