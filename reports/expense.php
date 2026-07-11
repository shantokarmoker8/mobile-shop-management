<?php
require_once __DIR__ . '/../includes/auth.php';
checkPermission($pdo, 'reports', 'view');

$page_title = 'Expense Report';

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

$start_full = $start_date . ' 00:00:00';
$end_full = $end_date . ' 23:59:59';

$stmt = $pdo->prepare("SELECT e.*, ec.name as category_name FROM expenses e 
                        LEFT JOIN expense_categories ec ON e.category_id = ec.id 
                        WHERE e.created_at BETWEEN ? AND ? 
                        ORDER BY e.created_at DESC");
$stmt->execute([$start_full, $end_full]);
$expenses = $stmt->fetchAll();

$total_expense = array_sum(array_column($expenses, 'amount'));

$stmt = $pdo->prepare("SELECT ec.name, SUM(e.amount) as total FROM expenses e 
                        LEFT JOIN expense_categories ec ON e.category_id = ec.id 
                        WHERE e.created_at BETWEEN ? AND ? 
                        GROUP BY e.category_id ORDER BY total DESC");
$stmt->execute([$start_full, $end_full]);
$category_breakdown = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-body">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h5 class="fw-bold mb-0">Expense Report</h5>
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
            <div class="col-lg-4">
                <div class="stat-card"><div class="stat-text"><div class="stat-value text-danger"><?= money($total_expense) ?></div><div class="stat-label">Total Expense</div></div></div>
            </div>
            <div class="col-lg-8">
                <div class="card-panel">
                    <h6 class="fw-bold mb-2">Category Breakdown</h6>
                    <?php foreach ($category_breakdown as $cb): ?>
                    <div class="d-flex justify-content-between small mb-1">
                        <span><?= h($cb['name'] ?: 'Uncategorized') ?></span>
                        <strong><?= money($cb['total']) ?></strong>
                    </div>
                    <?php endforeach; ?>
                    <?php if (count($category_breakdown) === 0): ?>
                    <p class="text-muted small mb-0">No data available.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card-panel">

            <!-- Desktop Table -->
            <div class="table-responsive desktop-only-table">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr><th>Date</th><th>Category</th><th>Note</th><th>Amount</th></tr>
                    </thead>
                    <tbody>
                        <?php if (count($expenses) === 0): ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">No expenses found for this period.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($expenses as $e): ?>
                        <tr>
                            <td><?= formatDate($e['created_at']) ?></td>
                            <td><?= h($e['category_name']) ?></td>
                            <td><?= h($e['note'] ?: '-') ?></td>
                            <td class="text-danger"><?= money($e['amount']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td colspan="3">Total</td>
                            <td class="text-danger"><?= money($total_expense) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Mobile List -->
            <div class="mlist">
                <?php if (count($expenses) === 0): ?>
                <div class="mlist-empty"><i class="bi bi-receipt d-block mb-2" style="font-size:24px;"></i>No expenses found for this period.</div>
                <?php endif; ?>

                <?php foreach ($expenses as $e): ?>
                <div class="mlist-item">
                    <div class="mlist-link">
                        <div class="mlist-avatar is-danger"><i class="bi bi-receipt"></i></div>
                        <div class="mlist-body">
                            <div class="mlist-title"><?= h($e['category_name']) ?></div>
                            <div class="mlist-sub"><?= formatDate($e['created_at']) ?> <?= $e['note'] ? '· ' . h($e['note']) : '' ?></div>
                        </div>
                    </div>
                    <div class="mlist-end">
                        <div class="mlist-value text-danger"><?= money($e['amount']) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>