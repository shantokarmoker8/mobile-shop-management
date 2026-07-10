<?php
require_once __DIR__ . '/../includes/auth.php';
checkPermission($pdo, 'purchase', 'edit');

$page_title = 'Edit Supplier';

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id = ?");
$stmt->execute([$id]);
$supplier = $stmt->fetch();

if (!$supplier) {
    setFlash('error', 'Supplier not found.');
    redirect(BASE_URL . 'suppliers/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = clean($_POST['name']);
    $phone = clean($_POST['phone']);
    $address = clean($_POST['address']);
    $status = clean($_POST['status']);

    if ($name === '') {
        setFlash('error', 'Supplier name is required.');
    } else {
        $stmt = $pdo->prepare("UPDATE suppliers SET name=?, phone=?, address=?, status=? WHERE id=?");
        $stmt->execute([$name, $phone, $address, $status, $id]);

        setFlash('success', 'Supplier updated successfully.');
        redirect(BASE_URL . 'suppliers/index.php');
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">Edit Supplier</h5>
            <a href="index.php" class="btn btn-soft btn-sm"><i class="bi bi-arrow-left me-1"></i>Back to List</a>
        </div>

        <div class="card-panel">
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Supplier Name *</label>
                        <input type="text" name="name" class="form-control" value="<?= h($supplier['name']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Phone</label>
                        <input type="text" name="phone" class="form-control" value="<?= h($supplier['phone']) ?>">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label small fw-semibold">Address</label>
                        <textarea name="address" class="form-control" rows="2"><?= h($supplier['address']) ?></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Status</label>
                        <select name="status" class="form-select">
                            <option value="active" <?= $supplier['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= $supplier['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Update Supplier</button>
                    <a href="index.php" class="btn btn-soft">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>