<?php
require_once __DIR__ . '/../includes/auth.php';
checkPermission($pdo, 'purchase', 'view');

$page_title = 'Supplier Ledger';

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id = ?");
$stmt->execute([$id]);
$supplier = $stmt->fetch();

if (!$supplier) {
    setFlash('error', 'Supplier not found.');
    redirect(BASE_URL . 'suppliers/index.php');
}

// Purchase history
$stmt = $pdo->prepare("SELECT * FROM purchases WHERE supplier_id = ? ORDER BY created_at DESC");
$stmt->execute([$id]);
$purchases = $stmt->fetchAll();

// Payment history
$stmt = $pdo->prepare("SELECT sp.*, u.full_name FROM supplier_payments sp LEFT JOIN users u ON sp.created_by = u.id WHERE sp.supplier_id = ? ORDER BY sp.created_at DESC");
$stmt->execute([$id]);
$payments = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <h5 class="fw-bold mb-0"><?= htmlspecialchars($supplier['name']) ?></h5>
                <p class="text-muted small mb-0"><?= htmlspecialchars($supplier['phone']) ?></p>
            </div>
            <div class="d-flex gap-2">
                <a href="payment.php?id=<?= $supplier['id'] ?>" class="btn btn-primary btn-sm"><i class="bi bi-cash me-1"></i>Collect / Pay Due</a>
                <a href="index.php" class="btn btn-soft btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-value text-danger"><?= money($supplier['total_due']) ?></div>
                    <div class="stat-label">Current Due</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-value"><?= count($purchases) ?></div>
                    <div class="stat-label">Total Purchases</div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-6">
                <div class="card-panel">
                    <h6 class="fw-bold mb-3">Purchase History</h6>
                    <div class="table-responsive">
                        <table class="table table-custom mb-0">
                            <thead>
                                <tr><th>Invoice</th><th>Date</th><th>Total</th><th>Paid</th><th>Due</th></tr>
                            </thead>
                            <tbody>
                                <?php if (count($purchases) === 0): ?>
                                <tr><td colspan="5" class="text-center text-muted py-3">No purchases found.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($purchases as $p): ?>
                                <tr>
                                    <td><a href="<?= BASE_URL ?>purchase/view.php?id=<?= $p['id'] ?>"><?= htmlspecialchars($p['invoice_no']) ?></a></td>
                                    <td><?= formatDate($p['created_at']) ?></td>
                                    <td><?= money($p['total_amount']) ?></td>
                                    <td><?= money($p['paid_amount']) ?></td>
                                    <td><?= money($p['due_amount']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card-panel">
                    <h6 class="fw-bold mb-3">Payment History</h6>
                    <div class="table-responsive">
                        <table class="table table-custom mb-0">
                            <thead>
                                <tr><th>Date</th><th>Amount</th><th>Note</th><th>By</th></tr>
                            </thead>
                            <tbody>
                                <?php if (count($payments) === 0): ?>
                                <tr><td colspan="4" class="text-center text-muted py-3">No payments found.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($payments as $pay): ?>
                                <tr>
                                    <td><?= formatDate($pay['created_at']) ?></td>
                                    <td class="text-success fw-semibold"><?= money($pay['amount']) ?></td>
                                    <td><?= htmlspecialchars($pay['note'] ?: '-') ?></td>
                                    <td><?= htmlspecialchars($pay['full_name'] ?? '-') ?></td>
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