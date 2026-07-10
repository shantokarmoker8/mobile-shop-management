<?php
require_once __DIR__ . '/../includes/auth.php';
checkPermission($pdo, 'dashboard', 'view');

$page_title = 'Cash Management';

$current_cash = getCurrentCash($pdo);

// Today's summary
$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT 
    SUM(CASE WHEN direction = 'in' THEN amount ELSE 0 END) as total_in,
    SUM(CASE WHEN direction = 'out' THEN amount ELSE 0 END) as total_out
    FROM cash_transactions WHERE DATE(created_at) = ?");
$stmt->execute([$today]);
$today_summary = $stmt->fetch();

// Recent transactions
$stmt = $pdo->query("SELECT ct.*, u.full_name FROM cash_transactions ct 
                      LEFT JOIN users u ON ct.created_by = u.id 
                      ORDER BY ct.created_at DESC LIMIT 15");
$recent = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-body">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h5 class="fw-bold mb-0">Cash Management</h5>
            <div class="d-flex gap-2">
                <a href="deposit.php" class="btn btn-soft btn-sm"><i class="bi bi-plus-circle me-1"></i>Deposit</a>
                <a href="withdraw.php" class="btn btn-soft btn-sm"><i class="bi bi-dash-circle me-1"></i>Withdraw</a>
                <a href="history.php" class="btn btn-primary btn-sm"><i class="bi bi-clock-history me-1"></i>Full History</a>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#2F5BE0;"><i class="bi bi-wallet2"></i></div>
                    <div class="stat-value"><?= money($current_cash) ?></div>
                    <div class="stat-label">Current Cash Balance</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#1fa15a;"><i class="bi bi-arrow-down-circle"></i></div>
                    <div class="stat-value"><?= money($today_summary['total_in'] ?? 0) ?></div>
                    <div class="stat-label">Today's Cash In</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#e14343;"><i class="bi bi-arrow-up-circle"></i></div>
                    <div class="stat-value"><?= money($today_summary['total_out'] ?? 0) ?></div>
                    <div class="stat-label">Today's Cash Out</div>
                </div>
            </div>
        </div>

        <div class="card-panel">
            <h6 class="fw-bold mb-3">Recent Transactions</h6>
            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Note</th>
                            <th>Direction</th>
                            <th>Amount</th>
                            <th>By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($recent) === 0): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">No transactions found.</td></tr>
                        <?php endif; ?>

                        <?php foreach ($recent as $t): ?>
                        <tr>
                            <td><?= formatDateTime($t['created_at']) ?></td>
                            <td><?= ucfirst(str_replace('_',' ',$t['type'])) ?></td>
                            <td class="small text-muted"><?= htmlspecialchars($t['note'] ?: '-') ?></td>
                            <td>
                                <?php if ($t['direction'] === 'in'): ?>
                                    <span class="badge-status badge-completed"><i class="bi bi-arrow-down-circle"></i> In</span>
                                <?php else: ?>
                                    <span class="badge-status badge-due"><i class="bi bi-arrow-up-circle"></i> Out</span>
                                <?php endif; ?>
                            </td>
                            <td class="fw-semibold <?= $t['direction'] === 'in' ? 'text-success' : 'text-danger' ?>">
                                <?= $t['direction'] === 'in' ? '+' : '-' ?><?= money($t['amount']) ?>
                            </td>
                            <td><?= htmlspecialchars($t['full_name'] ?? '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>