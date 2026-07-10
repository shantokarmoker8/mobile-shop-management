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
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Start Date</label>
                    <input type="date" name="start_date" class="form-control form-control-sm" value="<?= $start_date ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">End Date</label>
                    <input type="date" name="end_date" class="form-control form-control-sm" value="<?= $end_date ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-search me-1"></i>Filter</button>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="button" class="btn btn-soft btn-sm w-100" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
                </div>
            </form>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-6 col-md-3">
                <div class="stat-card"><div class="stat-value"><?= money($total_sales) ?></div><div class="stat-label">Gross Sales</div></div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card"><div class="stat-value"><?= money($total_discount) ?></div><div class="stat-label">Total Discount</div></div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card"><div class="stat-value text-success"><?= money($total_paid) ?></div><div class="stat-label">Total Paid</div></div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card"><div class="stat-value text-danger"><?= money($total_due) ?></div><div class="stat-label">Total Due</div></div>
            </div>
        </div>

        <div class="card-panel">
            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr>
                            <th>Invoice</th><th>Customer</th><th>Date</th><th>Total</th><th>Discount</th><th>Paid</th><th>Due</th><th>Profit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($sales) === 0): ?>
                        <tr><td colspan="8" class="text-center text-muted py-4">No sales found for this period.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($sales as $s): ?>
                        <tr>
                            <td><?= htmlspecialchars($s['invoice_no']) ?></td>
                            <td><?= htmlspecialchars($s['customer_name'] ?: 'Walk-in') ?></td>
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
        </div>

    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>