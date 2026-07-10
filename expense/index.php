<?php
require_once __DIR__ . '/../includes/auth.php';
checkPermission($pdo, 'expense', 'view');

$page_title = 'Expenses';

$search = clean($_GET['search'] ?? '');
$category_filter = $_GET['category'] ?? '';

$query = "SELECT e.*, ec.name as category_name, u.full_name FROM expenses e 
          LEFT JOIN expense_categories ec ON e.category_id = ec.id 
          LEFT JOIN users u ON e.created_by = u.id 
          WHERE 1=1";
$params = [];

if ($search !== '') {
    $query .= " AND e.note LIKE ?";
    $params[] = "%$search%";
}

if ($category_filter !== '') {
    $query .= " AND e.category_id = ?";
    $params[] = $category_filter;
}

$query .= " ORDER BY e.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$expenses = $stmt->fetchAll();

$total_expense = array_sum(array_column($expenses, 'amount'));

$categories = $pdo->query("SELECT * FROM expense_categories WHERE status = 'active' ORDER BY name")->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-body">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h5 class="fw-bold mb-0">Expenses</h5>
            <div class="d-flex gap-2">
                <a href="category.php" class="btn btn-soft btn-sm"><i class="bi bi-tags me-1"></i>Categories</a>
                <a href="add.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Add Expense</a>
            </div>
        </div>

        <div class="card-panel mb-3">
            <form method="GET" class="row g-2">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by note" value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-3">
                    <select name="category" class="form-select form-select-sm">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $category_filter == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-search me-1"></i>Filter</button>
                </div>
            </form>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="stat-card"><div class="stat-value text-danger"><?= money($total_expense) ?></div><div class="stat-label">Total Expense (Filtered)</div></div>
            </div>
        </div>

        <div class="card-panel">
            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Category</th>
                            <th>Note</th>
                            <th>Amount</th>
                            <th>Added By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($expenses) === 0): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">No expenses found.</td></tr>
                        <?php endif; ?>

                        <?php foreach ($expenses as $e): ?>
                        <tr>
                            <td><?= formatDate($e['created_at']) ?></td>
                            <td><?= htmlspecialchars($e['category_name'] ?: '-') ?></td>
                            <td><?= htmlspecialchars($e['note'] ?: '-') ?></td>
                            <td class="text-danger fw-semibold"><?= money($e['amount']) ?></td>
                            <td><?= htmlspecialchars($e['full_name'] ?? '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>