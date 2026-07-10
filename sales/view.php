<?php
require_once __DIR__ . '/../includes/auth.php';
checkPermission($pdo, 'sales', 'view');

$page_title = 'Sale Details';

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT s.*, c.name as customer_name, c.phone as customer_phone, u.full_name as created_by_name 
                        FROM sales s 
                        LEFT JOIN customers c ON s.customer_id = c.id 
                        LEFT JOIN users u ON s.created_by = u.id 
                        WHERE s.id = ?");
$stmt->execute([$id]);
$sale = $stmt->fetch();

if (!$sale) {
    setFlash('error', 'Sale not found.');
    redirect(BASE_URL . 'sales/index.php');
}

$stmt = $pdo->prepare("SELECT si.*, p.name as product_name FROM sale_items si 
                        LEFT JOIN products p ON si.product_id = p.id 
                        WHERE si.sale_id = ?");
$stmt->execute([$id]);
$items = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM sale_returns WHERE sale_id = ? ORDER BY created_at DESC");
$stmt->execute([$id]);
$returns = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM sale_items si 
                        WHERE si.sale_id = ? AND si.quantity > COALESCE((SELECT SUM(sri.quantity) FROM sale_return_items sri WHERE sri.sale_item_id = si.id), 0)");
$stmt->execute([$id]);
$has_returnable = $stmt->fetch()['cnt'] > 0;

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h5 class="fw-bold mb-0">Sale - <?= htmlspecialchars($sale['invoice_no']) ?></h5>
            <div class="d-flex gap-2 flex-wrap">
                <?php if ($sale['due_amount'] > 0): ?>
                <a href="collect-payment.php?id=<?= $id ?>" class="btn btn-primary btn-sm"><i class="bi bi-cash me-1"></i>Collect Payment</a>
                <?php endif; ?>
                <?php if ($has_returnable): ?>
                <a href="return.php?id=<?= $id ?>" class="btn btn-outline-danger btn-sm"><i class="bi bi-arrow-return-left me-1"></i>Return</a>
                <?php endif; ?>
                <a href="invoice.php?id=<?= $id ?>" class="btn btn-soft btn-sm" target="_blank"><i class="bi bi-printer me-1"></i>Print (Optional)</a>
                <a href="index.php" class="btn btn-soft btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <div class="card-panel">
                    <p class="small text-muted mb-1">Customer</p>
                    <p class="fw-semibold mb-0"><?= htmlspecialchars($sale['customer_name'] ?: 'Walk-in Customer') ?></p>
                    <p class="small text-muted mb-0"><?= htmlspecialchars($sale['customer_phone'] ?? '') ?></p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card-panel">
                    <p class="small text-muted mb-1">Date / Sold By</p>
                    <p class="fw-semibold mb-0"><?= formatDateTime($sale['created_at']) ?></p>
                    <p class="small text-muted mb-0"><?= htmlspecialchars($sale['created_by_name']) ?></p>
                </div>
            </div>
        </div>

        <div class="card-panel mb-3">
            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr><th>Product</th><th>Qty</th><th>Price</th><th>Total</th><th>Profit</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $it): ?>
                        <tr>
                            <td><?= htmlspecialchars($it['product_name']) ?></td>
                            <td><?= $it['quantity'] ?></td>
                            <td><?= money($it['sell_price']) ?></td>
                            <td><?= money($it['total']) ?></td>
                            <td class="text-success"><?= money($it['profit']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <div class="stat-card justify-content-center"><div class="stat-text"><div class="stat-value"><?= money($sale['total_amount']) ?></div><div class="stat-label">Subtotal (After Returns)</div></div></div>
            </div>
            <div class="col-md-3">
                <div class="stat-card justify-content-center"><div class="stat-text"><div class="stat-value"><?= money($sale['discount']) ?></div><div class="stat-label">Discount</div></div></div>
            </div>
            <div class="col-md-3">
                <div class="stat-card justify-content-center"><div class="stat-text"><div class="stat-value text-success"><?= money($sale['paid_amount']) ?></div><div class="stat-label">Paid</div></div></div>
            </div>
            <div class="col-md-3">
                <div class="stat-card justify-content-center"><div class="stat-text"><div class="stat-value text-danger"><?= money($sale['due_amount']) ?></div><div class="stat-label">Due</div></div></div>
            </div>
        </div>

        <?php if (count($returns) > 0): ?>
        <div class="card-panel">
            <h6 class="fw-bold mb-3">Return History</h6>
            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr><th>Return No</th><th>Date</th><th>Amount</th><th>Note</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($returns as $r): ?>
                        <tr>
                            <td><span class="badge-status badge-returned"><?= htmlspecialchars($r['return_no']) ?></span></td>
                            <td><?= formatDateTime($r['created_at']) ?></td>
                            <td class="text-danger"><?= money($r['total_amount']) ?></td>
                            <td><?= htmlspecialchars($r['note'] ?: '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>