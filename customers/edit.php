<?php
require_once __DIR__ . '/../includes/auth.php';
checkPermission($pdo, 'sales', 'edit');

$page_title = 'Edit Customer';

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$id]);
$customer = $stmt->fetch();

if (!$customer) {
    setFlash('error', 'Customer not found.');
    redirect(BASE_URL . 'customers/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = clean($_POST['name']);
    $phone = clean($_POST['phone']);
    $address = clean($_POST['address']);

    if ($name === '') {
        setFlash('error', 'Customer name is required.');
    } else {
        $stmt = $pdo->prepare("UPDATE customers SET name=?, phone=?, address=? WHERE id=?");
        $stmt->execute([$name, $phone, $address, $id]);

        setFlash('success', 'Customer updated successfully.');
        redirect(BASE_URL . 'customers/index.php');
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">Edit Customer</h5>
            <a href="index.php" class="btn btn-soft btn-sm"><i class="bi bi-arrow-left me-1"></i>Back to List</a>
        </div>

        <div class="card-panel">
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Customer Name *</label>
                        <input type="text" name="name" class="form-control" value="<?= h($customer['name']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Phone</label>
                        <input type="text" name="phone" class="form-control" value="<?= h($customer['phone']) ?>">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small fw-semibold">Address</label>
                        <textarea name="address" class="form-control" rows="2"><?= h($customer['address']) ?></textarea>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Update Customer</button>
                    <a href="index.php" class="btn btn-soft">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>