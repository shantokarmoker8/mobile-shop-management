<?php
require_once __DIR__ . '/../includes/auth.php';
checkPermission($pdo, 'purchase', 'view');

$page_title = 'Purchase Details';

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT p.*, s.name as supplier_name, s.phone as supplier_phone, u.full_name as created_by_name 
                        FROM purchases p 
                        LEFT JOIN suppliers s ON p.supplier_id = s.id 
                        LEFT JOIN users u ON p.created_by = u.id 
                        WHERE p.id = ?");
$stmt->execute([$id]);
$purchase = $stmt->fetch();

if (!$purchase) {
    setFlash('error', 'Purchase not found.');
    redirect(BASE_URL . 'purchase/index.php');
}

$stmt = $pdo->prepare("SELECT pi.*, pr.name as product_name FROM purchase_items pi 
                        LEFT JOIN products pr ON pi.product_id = pr.id 
                        WHERE pi.purchase_id = ?");
$stmt->execute([$id]);
$items = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h5 class="fw-bold mb-0">Purchase - <?= htmlspecialchars($purchase['invoice_no']) ?></h5>
            <div class="d-flex gap-2 flex-wrap">
                <?php if ($purchase['due_amount'] > 0): ?>
                <a href="pay-due.php?id=<?= $id ?>" class="btn btn-primary btn-sm"><i class="bi bi-cash me-1"></i>Pay Due</a>
                <?php endif; ?>
                <a href="invoice.php?id=<?= $id ?>" class="btn btn-soft btn-sm" target="_blank"><i class="bi bi-printer me-1"></i>Print</a>
                <a href="index.php" class="btn btn-soft btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <div class="card-panel">
                    <p class="small text-muted mb-1">Supplier</p>
                    <p class="fw-semibold mb-0"><?= htmlspecialchars($purchase['supplier_name']) ?></p>
                    <p class="small text-muted mb-0"><?= htmlspecialchars($purchase['supplier_phone']) ?></p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card-panel">
                    <p class="small text-muted mb-1">Date / Created By</p>
                    <p class="fw-semibold mb-0"><?= formatDateTime($purchase['created_at']) ?></p>
                    <p class="small text-muted mb-0"><?= htmlspecialchars($purchase['created_by_name']) ?></p>
                </div>
            </div>
        </div>

        <div class="card-panel mb-3">
            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr><th>Product</th><th>Qty</th><th>Buy Price</th><th>Total</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $it): ?>
                        <tr>
                            <td><?= htmlspecialchars($it['product_name']) ?></td>
                            <td><?= $it['quantity'] ?></td>
                            <td><?= money($it['buy_price']) ?></td>
                            <td><?= money($it['total']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-4">
                <div class="stat-card justify-content-center"><div class="stat-text"><div class="stat-value"><?= money($purchase['total_amount']) ?></div><div class="stat-label">Total Amount</div></div></div>
            </div>
            <div class="col-md-4">
                <div class="stat-card justify-content-center"><div class="stat-text"><div class="stat-value text-success"><?= money($purchase['paid_amount']) ?></div><div class="stat-label">Paid Amount</div></div></div>
            </div>
            <div class="col-md-4">
                <div class="stat-card justify-content-center"><div class="stat-text"><div class="stat-value text-danger"><?= money($purchase['due_amount']) ?></div><div class="stat-label">Due Amount</div></div></div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>