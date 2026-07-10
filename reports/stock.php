<?php
require_once __DIR__ . '/../includes/auth.php';
checkPermission($pdo, 'reports', 'view');

$page_title = 'Stock Report';

$stock_filter = $_GET['stock'] ?? '';
$category_filter = $_GET['category'] ?? '';

$query = "SELECT p.*, c.name as category_name FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE p.status = 'active'";
$params = [];

if ($stock_filter === 'low') {
    $query .= " AND p.quantity <= p.low_stock_qty AND p.quantity > 0";
} elseif ($stock_filter === 'out') {
    $query .= " AND p.quantity <= 0";
}

if ($category_filter !== '') {
    $query .= " AND p.category_id = ?";
    $params[] = $category_filter;
}

$query .= " ORDER BY p.quantity ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();

$total_stock_value = 0;
$total_potential_sale = 0;
foreach ($products as $p) {
    $total_stock_value += $p['buy_price'] * $p['quantity'];
    $total_potential_sale += $p['sell_price'] * $p['quantity'];
}

$categories = $pdo->query("SELECT * FROM categories WHERE status = 'active' ORDER BY name")->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-body">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h5 class="fw-bold mb-0">Stock Report</h5>
            <?php include __DIR__ . '/_report_nav.php'; ?>
        </div>

        <div class="card-panel mb-3">
            <form method="GET" class="row g-2">
                <div class="col-md-4">
                    <select name="stock" class="form-select form-select-sm">
                        <option value="">All Stock</option>
                        <option value="low" <?= $stock_filter === 'low' ? 'selected' : '' ?>>Low Stock</option>
                        <option value="out" <?= $stock_filter === 'out' ? 'selected' : '' ?>>Out of Stock</option>
                    </select>
                </div>
                <div class="col-md-4">
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
                <div class="col-md-2">
                    <button type="button" class="btn btn-soft btn-sm w-100" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
                </div>
            </form>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-6 col-md-4">
                <div class="stat-card"><div class="stat-value"><?= count($products) ?></div><div class="stat-label">Total Items</div></div>
            </div>
            <div class="col-6 col-md-4">
                <div class="stat-card"><div class="stat-value"><?= money($total_stock_value) ?></div><div class="stat-label">Stock Value (Cost)</div></div>
            </div>
            <div class="col-6 col-md-4">
                <div class="stat-card"><div class="stat-value text-success"><?= money($total_potential_sale) ?></div><div class="stat-label">Potential Sale Value</div></div>
            </div>
        </div>

        <div class="card-panel">
            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr><th>Product</th><th>Category</th><th>Buy Price</th><th>Sell Price</th><th>Quantity</th><th>Stock Value</th></tr>
                    </thead>
                    <tbody>
                        <?php if (count($products) === 0): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">No products found.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($products as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['name']) ?></td>
                            <td><?= htmlspecialchars($p['category_name']) ?></td>
                            <td><?= money($p['buy_price']) ?></td>
                            <td><?= money($p['sell_price']) ?></td>
                            <td>
                                <?php if ($p['quantity'] <= 0): ?>
                                    <span class="badge-status badge-due">0</span>
                                <?php elseif ($p['quantity'] <= $p['low_stock_qty']): ?>
                                    <span class="badge-status badge-pending"><?= $p['quantity'] ?></span>
                                <?php else: ?>
                                    <?= $p['quantity'] ?>
                                <?php endif; ?>
                            </td>
                            <td><?= money($p['buy_price'] * $p['quantity']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>