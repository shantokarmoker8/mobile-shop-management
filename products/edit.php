<?php
require_once __DIR__ . '/../includes/auth.php';
checkPermission($pdo, 'products', 'edit');

$page_title = 'Edit Product';

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    setFlash('error', 'Product not found.');
    redirect(BASE_URL . 'products/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = clean($_POST['name']);
    $brand = clean($_POST['brand']);
    $category_id = (int)$_POST['category_id'];
    $buy_price = (float)$_POST['buy_price'];
    $sell_price = (float)$_POST['sell_price'];
    $quantity = (int)$_POST['quantity'];
    $low_stock_qty = (int)$_POST['low_stock_qty'];
    $barcode = clean($_POST['barcode']);
    $imei = clean($_POST['imei']);
    $status = clean($_POST['status']);

    if ($name === '' || $category_id <= 0) {
        setFlash('error', 'Name and Category are required.');
    } else {
        $stmt = $pdo->prepare("UPDATE products SET name=?, brand=?, category_id=?, buy_price=?, sell_price=?, quantity=?, low_stock_qty=?, barcode=?, imei=?, status=? WHERE id=?");
        $stmt->execute([$name, $brand, $category_id, $buy_price, $sell_price, $quantity, $low_stock_qty, $barcode ?: null, $imei ?: null, $status, $id]);

        setFlash('success', 'Product updated successfully.');
        redirect(BASE_URL . 'products/index.php');
    }
}

$categories = $pdo->query("SELECT * FROM categories WHERE status = 'active' ORDER BY name")->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">Edit Product</h5>
            <a href="index.php" class="btn btn-soft btn-sm"><i class="bi bi-arrow-left me-1"></i>Back to List</a>
        </div>

        <div class="card-panel">
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Product Name *</label>
                        <input type="text" name="name" class="form-control" value="<?= h($product['name']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Brand</label>
                        <input type="text" name="brand" class="form-control" value="<?= h($product['brand']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Category *</label>
                        <select name="category_id" class="form-select" required>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $product['category_id'] ? 'selected' : '' ?>><?= h($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Buy Price *</label>
                        <input type="number" step="0.01" name="buy_price" class="form-control" value="<?= h($product['buy_price']) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Sell Price *</label>
                        <input type="number" step="0.01" name="sell_price" class="form-control" value="<?= h($product['sell_price']) ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Quantity *</label>
                        <input type="number" name="quantity" class="form-control" value="<?= h($product['quantity']) ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Low Stock Alert</label>
                        <input type="number" name="low_stock_qty" class="form-control" value="<?= h($product['low_stock_qty']) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Barcode</label>
                        <input type="text" name="barcode" class="form-control" value="<?= h($product['barcode']) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">IMEI</label>
                        <input type="text" name="imei" class="form-control" value="<?= h($product['imei']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Status</label>
                        <select name="status" class="form-select">
                            <option value="active" <?= $product['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= $product['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Update Product</button>
                    <a href="index.php" class="btn btn-soft">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>