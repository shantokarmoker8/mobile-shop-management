<?php
require_once __DIR__ . '/../includes/auth.php';
checkPermission($pdo, 'expense', 'view');

$page_title = 'Expense Categories';

// Add category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = clean($_POST['name']);

    if ($name === '') {
        setFlash('error', 'Category name is required.');
    } else {
        $stmt = $pdo->prepare("INSERT INTO expense_categories (name) VALUES (?)");
        $stmt->execute([$name]);
        setFlash('success', 'Category added successfully.');
    }
    redirect(BASE_URL . 'expense/category.php');
}

// Toggle status
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $stmt = $pdo->prepare("UPDATE expense_categories SET status = IF(status='active','inactive','active') WHERE id = ?");
    $stmt->execute([$id]);
    redirect(BASE_URL . 'expense/category.php');
}

// Delete category
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM expense_categories WHERE id = ?");
        $stmt->execute([$id]);
        setFlash('success', 'Category deleted successfully.');
    } catch (PDOException $e) {
        setFlash('error', 'Cannot delete. This category is linked with expenses.');
    }
    redirect(BASE_URL . 'expense/category.php');
}

$categories = $pdo->query("SELECT ec.*, (SELECT COUNT(*) FROM expenses e WHERE e.category_id = ec.id) as expense_count 
                            FROM expense_categories ec ORDER BY ec.id DESC")->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">Expense Categories</h5>
            <a href="index.php" class="btn btn-soft btn-sm"><i class="bi bi-arrow-left me-1"></i>Back to Expenses</a>
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
                                    <th>Expenses</th>
                                    <th>Status</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($categories) === 0): ?>
                                <tr><td colspan="4" class="text-center text-muted py-4">No categories found.</td></tr>
                                <?php endif; ?>

                                <?php foreach ($categories as $cat): ?>
                                <tr>
                                    <td class="fw-semibold"><?= htmlspecialchars($cat['name']) ?></td>
                                    <td><?= $cat['expense_count'] ?></td>
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