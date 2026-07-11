<?php
require_once __DIR__ . '/../includes/auth.php';
checkPermission($pdo, 'reports', 'view');

$page_title = 'Purchase Report';

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

$start_full = $start_date . ' 00:00:00';
$end_full = $end_date . ' 23:59:59';

$stmt = $pdo->prepare("SELECT p.*, s.name as supplier_name FROM purchases p 
                        LEFT JOIN suppliers s ON p.supplier_id = s.id 
                        WHERE p.created_at BETWEEN ? AND ? 
                        ORDER BY p.created_at DESC");
$stmt->execute([$start_full, $end_full]);
$purchases = $stmt->fetchAll();

$total_amount = array_sum(array_column($purchases, 'total_amount'));
$total_paid = array_sum(array_column($purchases, 'paid_amount'));
$total_due = array_sum(array_column($purchases, 'due_amount'));

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-body">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h5 class="fw-bold mb-0">Purchase Report</h5>
            <?php include __DIR__ . '/_report_nav.php'; ?>
        </div>

        <div class="card-panel mb-3">
            <form method="GET" class="row g-2">
                <div class="col-6 col-md-4">
                    <label class="form-label small fw-semibold">Start Date</label>
                    <input type="date" name="start_date" class="form-control form-control-sm" value="<?= $start_date ?>">
                </div>
                <div class="col-6 col-md-4">
                    <label class="form-label small fw-semibold">End Date</label>
                    <input type="date" name="end_date" class="form-control form-control-sm" value="<?= $end_date ?>">
                </div>
                <div class="col-6 col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-search me-1"></i>Filter</button>
                </div>
                <div class="col-6 col-md-2 d-flex align-items-end">
                    <button type="button" class="btn btn-soft btn-sm w-100" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
                </div>
            </form>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-4">
                <div class="stat-card"><div class="stat-text"><div class="stat-value"><?= money($total_amount) ?></div><div class="stat-label">Total Purchase</div></div></div>
            </div>
            <div class="col-4">
                <div class="stat-card"><div class="stat-text"><div class="stat-value text-success"><?= money($total_paid) ?></div><div class="stat-label">Total Paid</div></div></div>
            </div>
            <div class="col-4">
                <div class="stat-card"><div class="stat-text"><div class="stat-value text-danger"><?= money($total_due) ?></div><div class="stat-label">Total Due</div></div></div>
            </div>
        </div>

        <div class="card-panel">

            <!-- Desktop Table -->
            <div class="table-responsive desktop-only-table">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr><th>Invoice</th><th>Supplier</th><th>Date</th><th>Total</th><th>Paid</th><th>Due</th></tr>
                    </thead>
                    <tbody>
                        <?php if (count($purchases) === 0): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">No purchases found for this period.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($purchases as $p): ?>
                        <tr>
                            <td><?= h($p['invoice_no']) ?></td>
                            <td><?= h($p['supplier_name']) ?></td>
                            <td><?= formatDate($p['created_at']) ?></td>
                            <td><?= money($p['total_amount']) ?></td>
                            <td><?= money($p['paid_amount']) ?></td>
                            <td><?= money($p['due_amount']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td colspan="3">Total</td>
                            <td><?= money($total_amount) ?></td>
                            <td><?= money($total_paid) ?></td>
                            <td><?= money($total_due) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Mobile List -->
            <div class="mlist">
                <?php if (count($purchases) === 0): ?>
                <div class="mlist-empty"><i class="bi bi-cart-plus d-block mb-2" style="font-size:24px;"></i>No purchases found for this period.</div>
                <?php endif; ?>

                <?php foreach ($purchases as $p): ?>
                <div class="mlist-item">
                    <a href="<?= BASE_URL ?>purchase/view.php?id=<?= $p['id'] ?>" class="mlist-link">
                        <div class="mlist-avatar is-warning"><i class="bi bi-cart-plus"></i></div>
                        <div class="mlist-body">
                            <div class="mlist-title"><?= h($p['invoice_no']) ?></div>
                            <div class="mlist-sub"><?= h($p['supplier_name']) ?> · <?= formatDate($p['created_at']) ?></div>
                        </div>
                    </a>
                    <div class="mlist-end">
                        <div class="mlist-value"><?= money($p['total_amount']) ?></div>
                        <div class="mlist-meta"><?= $p['due_amount'] > 0 ? 'Due: ' . money($p['due_amount']) : 'Paid' ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>