<?php
require_once __DIR__ . '/../includes/auth.php';
checkPermission($pdo, 'reports', 'view');

$page_title = 'Supplier Due Report';

$stmt = $pdo->query("SELECT * FROM suppliers WHERE total_due > 0 ORDER BY total_due DESC");
$suppliers = $stmt->fetchAll();

$total_due = array_sum(array_column($suppliers, 'total_due'));

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-body">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h5 class="fw-bold mb-0">Supplier Due Report</h5>
            <?php include __DIR__ . '/_report_nav.php'; ?>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-6">
                <div class="stat-card"><div class="stat-text"><div class="stat-value text-danger"><?= money($total_due) ?></div><div class="stat-label">Total Payable Due</div></div></div>
            </div>
            <div class="col-6">
                <div class="stat-card"><div class="stat-text"><div class="stat-value"><?= count($suppliers) ?></div><div class="stat-label">Suppliers with Due</div></div></div>
            </div>
        </div>

        <div class="card-panel">
            <div class="d-flex justify-content-end mb-2">
                <button type="button" class="btn btn-soft btn-sm" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
            </div>

            <!-- Desktop Table -->
            <div class="table-responsive desktop-only-table">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr><th>Supplier</th><th>Phone</th><th>Due Amount</th><th class="text-end">Action</th></tr>
                    </thead>
                    <tbody>
                        <?php if (count($suppliers) === 0): ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">No outstanding supplier due.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($suppliers as $s): ?>
                        <tr>
                            <td><?= h($s['name']) ?></td>
                            <td><?= h($s['phone']) ?></td>
                            <td class="text-danger fw-semibold"><?= money($s['total_due']) ?></td>
                            <td class="text-end">
                                <a href="<?= BASE_URL ?>suppliers/ledger.php?id=<?= $s['id'] ?>" class="btn btn-soft btn-sm">View Ledger</a>
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

            <!-- Mobile List -->
            <div class="mlist">
                <?php if (count($suppliers) === 0): ?>
                <div class="mlist-empty"><i class="bi bi-truck d-block mb-2" style="font-size:24px;"></i>No outstanding supplier due.</div>
                <?php endif; ?>

                <?php foreach ($suppliers as $s): ?>
                <div class="mlist-item">
                    <a href="<?= BASE_URL ?>suppliers/ledger.php?id=<?= $s['id'] ?>" class="mlist-link">
                        <div class="mlist-avatar is-danger"><?= h(mb_substr($s['name'], 0, 1)) ?></div>
                        <div class="mlist-body">
                            <div class="mlist-title"><?= h($s['name']) ?></div>
                            <div class="mlist-sub"><?= h($s['phone']) ?></div>
                        </div>
                    </a>
                    <div class="mlist-end">
                        <div class="mlist-value text-danger"><?= money($s['total_due']) ?></div>
                        <div class="mlist-meta">Due</div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>