<?php
require_once __DIR__ . '/../includes/auth.php';
checkPermission($pdo, 'reports', 'view');

$page_title = 'Customer Due Report';

$stmt = $pdo->query("SELECT * FROM customers WHERE total_due > 0 ORDER BY total_due DESC");
$customers = $stmt->fetchAll();

$total_due = array_sum(array_column($customers, 'total_due'));

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-body">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h5 class="fw-bold mb-0">Customer Due Report</h5>
            <?php include __DIR__ . '/_report_nav.php'; ?>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="stat-card"><div class="stat-value text-danger"><?= money($total_due) ?></div><div class="stat-label">Total Outstanding Due</div></div>
            </div>
            <div class="col-md-4">
                <div class="stat-card"><div class="stat-value"><?= count($customers) ?></div><div class="stat-label">Customers with Due</div></div>
            </div>
        </div>

        <div class="card-panel">
            <div class="d-flex justify-content-end mb-2">
                <button type="button" class="btn btn-soft btn-sm" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
            </div>
            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr><th>Customer</th><th>Phone</th><th>Due Amount</th><th class="text-end">Action</th></tr>
                    </thead>
                    <tbody>
                        <?php if (count($customers) === 0): ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">No outstanding customer due.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($customers as $c): ?>
                        <tr>
                            <td><?= htmlspecialchars($c['name']) ?></td>
                            <td><?= htmlspecialchars($c['phone']) ?></td>
                            <td class="text-danger fw-semibold"><?= money($c['total_due']) ?></td>
                            <td class="text-end">
                                <a href="<?= BASE_URL ?>customers/ledger.php?id=<?= $c['id'] ?>" class="btn btn-soft btn-sm">View Ledger</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td colspan="2">Total</td>
                            <td class="text-danger"><?= money($total_due) ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>