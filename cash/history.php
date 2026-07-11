<?php
require_once __DIR__ . '/../includes/auth.php';
checkPermission($pdo, 'dashboard', 'view');

$page_title = 'Cash History';

$filter = $_GET['filter'] ?? 'this_month';
$type_filter = $_GET['type'] ?? '';

$start_date = '';
$end_date = date('Y-m-d');

switch ($filter) {
    case 'today': $start_date = date('Y-m-d'); break;
    case 'last7': $start_date = date('Y-m-d', strtotime('-6 days')); break;
    case 'last30': $start_date = date('Y-m-d', strtotime('-29 days')); break;
    case 'this_month': $start_date = date('Y-m-01'); break;
    case 'this_year': $start_date = date('Y-01-01'); break;
    case 'all': $start_date = '2000-01-01'; break;
    default: $start_date = date('Y-m-01');
}

$start_full = $start_date . ' 00:00:00';
$end_full = $end_date . ' 23:59:59';

$query = "SELECT ct.*, u.full_name FROM cash_transactions ct 
          LEFT JOIN users u ON ct.created_by = u.id 
          WHERE ct.created_at BETWEEN ? AND ?";
$params = [$start_full, $end_full];

if ($type_filter !== '') {
    $query .= " AND ct.type = ?";
    $params[] = $type_filter;
}

$query .= " ORDER BY ct.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

$total_in = 0;
$total_out = 0;
foreach ($transactions as $t) {
    if ($t['direction'] === 'in') $total_in += $t['amount'];
    else $total_out += $t['amount'];
}

$type_icons = [
    'opening' => 'bi-flag',
    'deposit' => 'bi-plus-circle',
    'withdraw' => 'bi-dash-circle',
    'sale' => 'bi-cash-coin',
    'purchase' => 'bi-cart-plus',
    'expense' => 'bi-receipt',
    'customer_payment' => 'bi-person-check',
    'supplier_payment' => 'bi-truck',
    'refund' => 'bi-arrow-return-left'
];

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-body">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h5 class="fw-bold mb-0">Cash History</h5>
            <a href="index.php" class="btn btn-soft btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
        </div>

        <div class="card-panel mb-3">
            <form method="GET" class="row g-2">
                <div class="col-6 col-md-4">
                    <select name="filter" class="form-select form-select-sm">
                        <option value="today" <?= $filter === 'today' ? 'selected' : '' ?>>Today</option>
                        <option value="last7" <?= $filter === 'last7' ? 'selected' : '' ?>>Last 7 Days</option>
                        <option value="last30" <?= $filter === 'last30' ? 'selected' : '' ?>>Last 30 Days</option>
                        <option value="this_month" <?= $filter === 'this_month' ? 'selected' : '' ?>>This Month</option>
                        <option value="this_year" <?= $filter === 'this_year' ? 'selected' : '' ?>>This Year</option>
                        <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>All Time</option>
                    </select>
                </div>
                <div class="col-6 col-md-4">
                    <select name="type" class="form-select form-select-sm">
                        <option value="">All Types</option>
                        <option value="opening" <?= $type_filter === 'opening' ? 'selected' : '' ?>>Opening Balance</option>
                        <option value="deposit" <?= $type_filter === 'deposit' ? 'selected' : '' ?>>Deposit</option>
                        <option value="withdraw" <?= $type_filter === 'withdraw' ? 'selected' : '' ?>>Withdraw</option>
                        <option value="sale" <?= $type_filter === 'sale' ? 'selected' : '' ?>>Sale</option>
                        <option value="purchase" <?= $type_filter === 'purchase' ? 'selected' : '' ?>>Purchase</option>
                        <option value="expense" <?= $type_filter === 'expense' ? 'selected' : '' ?>>Expense</option>
                        <option value="customer_payment" <?= $type_filter === 'customer_payment' ? 'selected' : '' ?>>Customer Payment</option>
                        <option value="supplier_payment" <?= $type_filter === 'supplier_payment' ? 'selected' : '' ?>>Supplier Payment</option>
                        <option value="refund" <?= $type_filter === 'refund' ? 'selected' : '' ?>>Refund</option>
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-search me-1"></i>Filter</button>
                </div>
            </form>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-6">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#1fa15a;"><i class="bi bi-arrow-down-circle"></i></div>
                    <div class="stat-text">
                        <div class="stat-value"><?= money($total_in) ?></div>
                        <div class="stat-label">Total Cash In</div>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#e14343;"><i class="bi bi-arrow-up-circle"></i></div>
                    <div class="stat-text">
                        <div class="stat-value"><?= money($total_out) ?></div>
                        <div class="stat-label">Total Cash Out</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-panel">

            <!-- Desktop Table -->
            <div class="table-responsive desktop-only-table">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr>
                            <th>Date</th><th>Type</th><th>Note</th><th>Direction</th><th>Amount</th><th>By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($transactions) === 0): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">No transactions found for this period.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($transactions as $t): ?>
                        <tr>
                            <td><?= formatDateTime($t['created_at']) ?></td>
                            <td><?= ucfirst(str_replace('_',' ',$t['type'])) ?></td>
                            <td class="small text-muted"><?= h($t['note'] ?: '-') ?></td>
                            <td>
                                <?php if ($t['direction'] === 'in'): ?>
                                    <span class="badge-status badge-completed">In</span>
                                <?php else: ?>
                                    <span class="badge-status badge-due">Out</span>
                                <?php endif; ?>
                            </td>
                            <td class="fw-semibold <?= $t['direction'] === 'in' ? 'text-success' : 'text-danger' ?>">
                                <?= $t['direction'] === 'in' ? '+' : '-' ?><?= money($t['amount']) ?>
                            </td>
                            <td><?= h($t['full_name'] ?? '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Mobile List -->
            <div class="mlist">
                <?php if (count($transactions) === 0): ?>
                <div class="mlist-empty"><i class="bi bi-wallet2 d-block mb-2" style="font-size:24px;"></i>No transactions found for this period.</div>
                <?php endif; ?>

                <?php foreach ($transactions as $t): ?>
                <div class="mlist-item">
                    <div class="mlist-link">
                        <div class="mlist-avatar <?= $t['direction'] === 'in' ? 'is-success' : 'is-danger' ?>">
                            <i class="bi <?= $type_icons[$t['type']] ?? 'bi-cash' ?>"></i>
                        </div>
                        <div class="mlist-body">
                            <div class="mlist-title"><?= ucfirst(str_replace('_',' ',$t['type'])) ?></div>
                            <div class="mlist-sub"><?= formatDateTime($t['created_at']) ?> <?= $t['note'] ? '· ' . h($t['note']) : '' ?></div>
                        </div>
                    </div>
                    <div class="mlist-end">
                        <div class="mlist-value <?= $t['direction'] === 'in' ? 'text-success' : 'text-danger' ?>">
                            <?= $t['direction'] === 'in' ? '+' : '-' ?><?= money($t['amount']) ?>
                        </div>
                        <div class="mlist-meta"><?= h($t['full_name'] ?? '-') ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>