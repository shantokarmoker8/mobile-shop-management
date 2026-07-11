<?php
require_once __DIR__ . '/../includes/auth.php';
checkPermission($pdo, 'reports', 'view');

$page_title = 'Cash Report';

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

$start_full = $start_date . ' 00:00:00';
$end_full = $end_date . ' 23:59:59';

$stmt = $pdo->prepare("SELECT type, direction, SUM(amount) as total FROM cash_transactions 
                        WHERE created_at BETWEEN ? AND ? 
                        GROUP BY type, direction ORDER BY total DESC");
$stmt->execute([$start_full, $end_full]);
$breakdown = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT 
    SUM(CASE WHEN direction = 'in' THEN amount ELSE 0 END) as total_in,
    SUM(CASE WHEN direction = 'out' THEN amount ELSE 0 END) as total_out
    FROM cash_transactions WHERE created_at BETWEEN ? AND ?");
$stmt->execute([$start_full, $end_full]);
$summary = $stmt->fetch();

$current_cash = getCurrentCash($pdo);

$type_icons = [
    'opening' => 'bi-flag', 'deposit' => 'bi-plus-circle', 'withdraw' => 'bi-dash-circle',
    'sale' => 'bi-cash-coin', 'purchase' => 'bi-cart-plus', 'expense' => 'bi-receipt',
    'customer_payment' => 'bi-person-check', 'supplier_payment' => 'bi-truck', 'refund' => 'bi-arrow-return-left'
];

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-body">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h5 class="fw-bold mb-0">Cash Report</h5>
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
            <div class="col-6 col-md-3">
                <div class="stat-card"><div class="stat-text"><div class="stat-value text-primary"><?= money($current_cash) ?></div><div class="stat-label">Current Cash</div></div></div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card"><div class="stat-text"><div class="stat-value text-success"><?= money($summary['total_in'] ?? 0) ?></div><div class="stat-label">Total Cash In</div></div></div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card"><div class="stat-text"><div class="stat-value text-danger"><?= money($summary['total_out'] ?? 0) ?></div><div class="stat-label">Total Cash Out</div></div></div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card"><div class="stat-text"><div class="stat-value"><?= money(($summary['total_in'] ?? 0) - ($summary['total_out'] ?? 0)) ?></div><div class="stat-label">Net Change</div></div></div>
            </div>
        </div>

        <div class="card-panel">
            <h6 class="fw-bold mb-3">Breakdown by Type</h6>

            <!-- Desktop Table -->
            <div class="table-responsive desktop-only-table">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr><th>Type</th><th>Direction</th><th>Amount</th></tr>
                    </thead>
                    <tbody>
                        <?php if (count($breakdown) === 0): ?>
                        <tr><td colspan="3" class="text-center text-muted py-4">No transactions found for this period.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($breakdown as $b): ?>
                        <tr>
                            <td><?= ucfirst(str_replace('_',' ',$b['type'])) ?></td>
                            <td>
                                <?php if ($b['direction'] === 'in'): ?>
                                    <span class="badge-status badge-completed">In</span>
                                <?php else: ?>
                                    <span class="badge-status badge-due">Out</span>
                                <?php endif; ?>
                            </td>
                            <td class="fw-semibold <?= $b['direction'] === 'in' ? 'text-success' : 'text-danger' ?>"><?= money($b['total']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Mobile List -->
            <div class="mlist">
                <?php if (count($breakdown) === 0): ?>
                <div class="mlist-empty"><i class="bi bi-wallet2 d-block mb-2" style="font-size:24px;"></i>No transactions found for this period.</div>
                <?php endif; ?>

                <?php foreach ($breakdown as $b): ?>
                <div class="mlist-item">
                    <div class="mlist-link">
                        <div class="mlist-avatar <?= $b['direction'] === 'in' ? 'is-success' : 'is-danger' ?>">
                            <i class="bi <?= $type_icons[$b['type']] ?? 'bi-cash' ?>"></i>
                        </div>
                        <div class="mlist-body">
                            <div class="mlist-title"><?= ucfirst(str_replace('_',' ',$b['type'])) ?></div>
                            <div class="mlist-sub"><?= $b['direction'] === 'in' ? 'Cash In' : 'Cash Out' ?></div>
                        </div>
                    </div>
                    <div class="mlist-end">
                        <div class="mlist-value <?= $b['direction'] === 'in' ? 'text-success' : 'text-danger' ?>"><?= money($b['total']) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>