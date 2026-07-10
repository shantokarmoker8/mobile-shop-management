<?php
require_once __DIR__ . '/../includes/auth.php';
checkPermission($pdo, 'products', 'view');

$page_title = 'Product Categories';

// Add category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = clean($_POST['name']);
    $type = clean($_POST['type']);

    if ($name === '' || !in_array($type, ['mobile','accessories','repair_parts'])) {
        setFlash('error', 'Please fill all fields correctly.');
    } else {
        $stmt = $pdo->prepare("INSERT INTO categories (name, type) VALUES (?, ?)");
        $stmt->execute([$name, $type]);
        setFlash('success', 'Category added successfully.');
    }
    redirect(BASE_URL . 'products/category.php');
}

// Toggle status
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $stmt = $pdo->prepare("UPDATE categories SET status = IF(status='active','inactive','active') WHERE id = ?");
    $stmt->execute([$id]);
    redirect(BASE_URL . 'products/category.php');
}

// Delete category
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        setFlash('success', 'Category deleted successfully.');
    } catch (PDOException $e) {
        setFlash('error', 'Cannot delete. This category is linked with products.');
    }
    redirect(BASE_URL . 'products/category.php');
}

$categories = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) as product_count FROM categories c ORDER BY c.created_at DESC")->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">Product Categories</h5>
            <a href="index.php" class="btn btn-soft btn-sm"><i class="bi bi-arrow-left me-1"></i>Back to Products</a>
        </div>

        <div class="row g-3">
            <div class="col-lg-4">
                <div class="card-panel">
                    <h6 class="fw-bold mb-3">Add New Category</h6>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Category Name *</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Type *</label>
                            <select name="type" class="form-select" required>
                                <option value="mobile">Mobile</option>
                                <option value="accessories">Accessories</option>
                                <option value="repair_parts">Repair Parts</option>
                            </select>
                        </div>
                        <button type="submit" name="add_category" class="btn btn-primary w-100"><i class="bi bi-plus-lg me-1"></i>Add Category</button>
                    </form>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card-panel">
                    <div class="table-responsive">
                        <table class="table table-custom mb-0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Products</th>
                                    <th>Status</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($categories) === 0): ?>
                                <tr><td colspan="5" class="text-center text-muted py-4">No categories found.</td></tr>
                                <?php endif; ?>

                                <?php foreach ($categories as $cat): ?>
                                <tr>
                                    <td class="fw-semibold"><?= htmlspecialchars($cat['name']) ?></td>
                                    <td><?= ucfirst(str_replace('_',' ',$cat['type'])) ?></td>
                                    <td><?= $cat['product_count'] ?></td>
                                    <td>
                                        <a href="?toggle=<?= $cat['id'] ?>" class="badge-status <?= $cat['status'] === 'active' ? 'badge-completed' : 'badge-due' ?>" style="text-decoration:none;">
                                            <?= ucfirst($cat['status']) ?>
                                        </a>
                                    </td>
                                    <td class="text-end">
                                        <a href="javascript:void(0)" onclick="confirmDelete('?delete=<?= $cat['id'] ?>')" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>