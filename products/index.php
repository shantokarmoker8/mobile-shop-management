<?php
require_once __DIR__ . '/../includes/auth.php';
checkPermission($pdo, 'products', 'view');

$page_title = 'Products';

$search = clean($_GET['search'] ?? '');
$category_filter = $_GET['category'] ?? '';
$stock_filter = $_GET['stock'] ?? '';

$query = "SELECT p.*, c.name as category_name, c.type as category_type 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE 1=1";
$params = [];

if ($search !== '') {
    $query .= " AND (p.name LIKE ? OR p.brand LIKE ? OR p.barcode LIKE ? OR p.imei LIKE ?)";
    $like = "%$search%";
    $params = [$like, $like, $like, $like];
}

if ($category_filter !== '') {
    $query .= " AND p.category_id = ?";
    $params[] = $category_filter;
}

if ($stock_filter === 'low') {
    $query .= " AND p.quantity <= p.low_stock_qty";
} elseif ($stock_filter === 'out') {
    $query .= " AND p.quantity <= 0";
}

$query .= " ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();

$categories = $pdo->query("SELECT * FROM categories WHERE status = 'active' ORDER BY name")->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-body">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h5 class="fw-bold mb-0">Products</h5>
            <div class="d-flex gap-2">
                <a href="category.php" class="btn btn-soft btn-sm"><i class="bi bi-tags me-1"></i>Categories</a>
                <a href="add.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Add Product</a>
            </div>
        </div>

        <div class="card-panel mb-3">
            <form method="GET" class="row g-2">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by name, brand, barcode, IMEI" value="<?= h($search) ?>">
                </div>
                <div class="col-md-3">
                    <select name="category" class="form-select form-select-sm">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $category_filter == $cat['id'] ? 'selected' : '' ?>><?= h($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="stock" class="form-select form-select-sm">
                        <option value="">All Stock</option>
                        <option value="low" <?= $stock_filter === 'low' ? 'selected' : '' ?>>Low Stock</option>
                        <option value="out" <?= $stock_filter === 'out' ? 'selected' : '' ?>>Out of Stock</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-search me-1"></i>Filter</button>
                </div>
            </form>
        </div>

        <div class="card-panel">
            <div class="table-responsive">
                <table class="table table-custom table-mobile-cards mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Brand</th>
                            <th>Category</th>
                            <th>Buy Price</th>
                            <th>Sell Price</th>
                            <th>Quantity</th>
                            <th>Barcode / IMEI</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($products) === 0): ?>
                        <tr><td colspan="9" class="text-center text-muted py-4">No products found.</td></tr>
                        <?php endif; ?>

                        <?php foreach ($products as $p): ?>
                        <tr>
                            <td class="fw-semibold cell-title" data-label="Name"><?= h($p['name']) ?></td>
                            <td data-label="Brand"><?= h($p['brand'] ?: '-') ?></td>
                            <td data-label="Category"><?= h($p['category_name'] ?: '-') ?></td>
                            <td data-label="Buy Price"><?= money($p['buy_price']) ?></td>
                            <td data-label="Sell Price"><?= money($p['sell_price']) ?></td>
                            <td data-label="Quantity">
                                <?php if ($p['quantity'] <= 0): ?>
                                    <span class="badge-status badge-due">Out of Stock</span>
                                <?php elseif ($p['quantity'] <= $p['low_stock_qty']): ?>
                                    <span class="badge-status badge-pending"><?= $p['quantity'] ?> (Low)</span>
                                <?php else: ?>
                                    <?= $p['quantity'] ?>
                                <?php endif; ?>
                            </td>
                            <td class="small text-muted" data-label="Barcode/IMEI">
                                <?= $p['barcode'] ? h($p['barcode']) : '' ?>
                                <?= $p['imei'] ? '<br>' . h($p['imei']) : '' ?>
                                <?= (!$p['barcode'] && !$p['imei']) ? '-' : '' ?>
                            </td>
                            <td data-label="Status">
                                <span class="badge-status <?= $p['status'] === 'active' ? 'badge-completed' : 'badge-due' ?>">
                                    <?= ucfirst($p['status']) ?>
                                </span>
                            </td>
                            <td class="text-end cell-actions" data-label="Action">
                                <a href="edit.php?id=<?= $p['id'] ?>" class="btn btn-soft btn-sm"><i class="bi bi-pencil"></i></a>
                                <form action="delete.php" method="POST" class="delete-form d-inline">
                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
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