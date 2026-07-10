<?php
require_once __DIR__ . '/../includes/auth.php';
checkPermission($pdo, 'purchase', 'add');

$page_title = 'Add Supplier';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = clean($_POST['name']);
    $phone = clean($_POST['phone']);
    $address = clean($_POST['address']);

    if ($name === '') {
        setFlash('error', 'Supplier name is required.');
    } else {
        $stmt = $pdo->prepare("INSERT INTO suppliers (name, phone, address) VALUES (?,?,?)");
        $stmt->execute([$name, $phone, $address]);

        setFlash('success', 'Supplier added successfully.');
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
            <h5 class="fw-bold mb-0">Add Supplier</h5>
            <a href="index.php" class="btn btn-soft btn-sm"><i class="bi bi-arrow-left me-1"></i>Back to List</a>
        </div>

        <div class="card-panel">
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Supplier Name *</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Phone</label>
                        <input type="text" name="phone" class="form-control">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small fw-semibold">Address</label>
                        <textarea name="address" class="form-control" rows="2"></textarea>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save Supplier</button>
                    <a href="index.php" class="btn btn-soft">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>