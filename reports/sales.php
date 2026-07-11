<?php
require_once __DIR__ . '/../includes/auth.php';
checkPermission($pdo, 'reports', 'view');

$page_title = 'Sales Report';

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

$start_full = $start_date . ' 00:00:00';
$end_full = $end_date . ' 23:59:59';

$stmt = $pdo->prepare("SELECT s.*, c.name as customer_name FROM sales s 
                        LEFT JOIN customers c ON s.customer_id = c.id 
                        WHERE s.created_at BETWEEN ? AND ? 
                        ORDER BY s.created_at DESC");
$stmt->execute([$start_full, $end_full]);
$sales = $stmt->fetchAll();

$total_sales = array_sum(array_column($sales, 'total_amount'));
$total_discount = array_sum(array_column($sales, 'discount'));
$total_paid = array_sum(array_column($sales, 'paid_amount'));
$total_due = array_sum(array_column($sales, 'due_amount'));
$total_profit = array_sum(array_column($sales, 'total_profit'));

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-body">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h5 class="fw-bold mb-0">Sales Report</h5>
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
                <div class="stat-card"><div class="stat-text"><div class="stat-value"><?= money($total_sales) ?></div><div class="stat-label">Gross Sales</div></div></div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card"><div class="stat-text"><div class="stat-value"><?= money($total_discount) ?></div><div class="stat-label">Total Discount</div></div></div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card"><div class="stat-text"><div class="stat-value text-success"><?= money($total_paid) ?></div><div class="stat-label">Total Paid</div></div></div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card"><div class="stat-text"><div class="stat-value text-danger"><?= money($total_due) ?></div><div class="stat-label">Total Due</div></div></div>
            </div>
        </div>

        <div class="card-panel">

            <!-- Desktop Table -->
            <div class="table-responsive desktop-only-table">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr><th>Invoice</th><th>Customer</th><th>Date</th><th>Total</th><th>Discount</th><th>Paid</th><th>Due</th><th>Profit</th></tr>
                    </thead>
                    <tbody>
                        <?php if (count($sales) === 0): ?>
                        <tr><td colspan="8" class="text-center text-muted py-4">No sales found for this period.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($sales as $s): ?>
                        <tr>
                            <td><?= h($s['invoice_no']) ?></td>
                            <td><?= h($s['customer_name'] ?: 'Walk-in') ?></td>
                            <td><?= formatDate($s['created_at']) ?></td>
                            <td><?= money($s['total_amount']) ?></td>
                            <td><?= money($s['discount']) ?></td>
                            <td><?= money($s['paid_amount']) ?></td>
                            <td><?= money($s['due_amount']) ?></td>
                            <td class="text-success"><?= money($s['total_profit']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td colspan="3">Total</td>
                            <td><?= money($total_sales) ?></td>
                            <td><?= money($total_discount) ?></td>
                            <td><?= money($total_paid) ?></td>
                            <td><?= money($total_due) ?></td>
                            <td class="text-success"><?= money($total_profit) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Mobile List -->
            <div class="mlist">
                <?php if (count($sales) === 0): ?>
                <div class="mlist-empty"><i class="bi bi-cash-coin d-block mb-2" style="font-size:24px;"></i>No sales found for this period.</div>
                <?php endif; ?>

                <?php foreach ($sales as $s): ?>
                <div class="mlist-item">
                    <a href="<?= BASE_URL ?>sales/view.php?id=<?= $s['id'] ?>" class="mlist-link">
                        <div class="mlist-avatar is-success"><i class="bi bi-cash-coin"></i></div>
                        <div class="mlist-body">
                            <div class="mlist-title"><?= h($s['invoice_no']) ?></div>
                            <div class="mlist-sub"><?= h($s['customer_name'] ?: 'Walk-in') ?> · <?= formatDate($s['created_at']) ?></div>
                        </div>
                    </a>
                    <div class="mlist-end">
                        <div class="mlist-value"><?= money($s['total_amount']) ?></div>
                        <div class="mlist-meta"><?= $s['due_amount'] > 0 ? 'Due: ' . money($s['due_amount']) : 'Paid' ?></div>
                    </div>
                </div>
                <?php endforeach; ?>

                <?php if (count($sales) > 0): ?>
                <div class="card-panel mt-1">
                    <div class="row g-2 text-center">
                        <div class="col-6"><small class="text-muted d-block">Total</small><strong><?= money($total_sales) ?></strong></div>
                        <div class="col-6"><small class="text-muted d-block">Profit</small><strong class="text-success"><?= money($total_profit) ?></strong></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>