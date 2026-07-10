<?php
require_once __DIR__ . '/../includes/auth.php';
checkPermission($pdo, 'products', 'add');

$page_title = 'Add Product';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = clean($_POST['name']);
    $brand = clean($_POST['brand']);
    $category_id = (int)$_POST['category_id'];
    $low_stock_qty = (int)$_POST['low_stock_qty'];
    $barcode = clean($_POST['barcode']);
    $imei = clean($_POST['imei']);

    if ($name === '' || $category_id <= 0) {
        setFlash('error', 'Name and Category are required.');
    } else {
        $stmt = $pdo->prepare("INSERT INTO products (name, brand, category_id, buy_price, sell_price, quantity, low_stock_qty, barcode, imei) VALUES (?,?,?,0,0,0,?,?,?)");
        $stmt->execute([$name, $brand, $category_id, $low_stock_qty, $barcode ?: null, $imei ?: null]);

        setFlash('success', 'Product added successfully. Now add stock and price for it via Purchase.');
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
            <h5 class="fw-bold mb-0">Add Product</h5>
            <a href="index.php" class="btn btn-soft btn-sm"><i class="bi bi-arrow-left me-1"></i>Back to List</a>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-7">
                <div class="alert alert-warning small">
                    <i class="bi bi-info-circle me-1"></i>
                    Price and stock quantity are not set here. Once the product is created, go to
                    <strong>Purchase → New Purchase</strong> to add stock at a specific buy price and sell price.
                    A product with no stock will not appear in Sales / POS.
                </div>

                <div class="card-panel">
                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Product Name *</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Brand</label>
                                <input type="text" name="brand" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Category *</label>
                                <select name="category_id" class="form-select" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?> (<?= ucfirst(str_replace('_',' ',$cat['type'])) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Low Stock Alert Qty</label>
                                <input type="number" name="low_stock_qty" class="form-control" value="5">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Barcode (Optional)</label>
                                <input type="text" name="barcode" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">IMEI (Optional)</label>
                                <input type="text" name="imei" class="form-control">
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save Product</button>
                            <a href="index.php" class="btn btn-soft">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>