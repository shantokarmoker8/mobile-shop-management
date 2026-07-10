<?php
require_once __DIR__ . '/../includes/auth.php';
checkPermission($pdo, 'reports', 'view');

$page_title = 'Service Report';

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$status_filter = $_GET['status'] ?? '';

$start_full = $start_date . ' 00:00:00';
$end_full = $end_date . ' 23:59:59';

$query = "SELECT * FROM service_jobs WHERE created_at BETWEEN ? AND ?";
$params = [$start_full, $end_full];

if ($status_filter !== '') {
    $query .= " AND status = ?";
    $params[] = $status_filter;
}

$query .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$jobs = $stmt->fetchAll();

$total_amount = array_sum(array_column($jobs, 'total_amount'));
$total_paid = array_sum(array_column($jobs, 'paid_amount'));
$total_due = array_sum(array_column($jobs, 'due_amount'));
$total_labour = array_sum(array_column($jobs, 'labour_charge'));
$total_parts = array_sum(array_column($jobs, 'parts_total'));

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-body">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h5 class="fw-bold mb-0">Service Report</h5>
            <?php include __DIR__ . '/_report_nav.php'; ?>
        </div>

        <div class="card-panel mb-3">
            <form method="GET" class="row g-2">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Start Date</label>
                    <input type="date" name="start_date" class="form-control form-control-sm" value="<?= $start_date ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">End Date</label>
                    <input type="date" name="end_date" class="form-control form-control-sm" value="<?= $end_date ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Status</option>
                        <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="working" <?= $status_filter === 'working' ? 'selected' : '' ?>>Working</option>
                        <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="delivered" <?= $status_filter === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-search me-1"></i>Filter</button>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="button" class="btn btn-soft btn-sm w-100" onclick="window.print()"><i class="bi bi-printer"></i></button>
                </div>
            </form>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-6 col-md-3">
                <div class="stat-card"><div class="stat-value"><?= count($jobs) ?></div><div class="stat-label">Total Jobs</div></div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card"><div class="stat-value"><?= money($total_labour) ?></div><div class="stat-label">Labour Income</div></div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card"><div class="stat-value"><?= money($total_parts) ?></div><div class="stat-label">Parts Income</div></div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card"><div class="stat-value text-danger"><?= money($total_due) ?></div><div class="stat-label">Total Due</div></div>
            </div>
        </div>

        <div class="card-panel">
            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr><th>Job No</th><th>Customer</th><th>Device</th><th>Date</th><th>Total</th><th>Due</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <?php if (count($jobs) === 0): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">No service jobs found for this period.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($jobs as $j): ?>
                        <tr>
                            <td><?= htmlspecialchars($j['job_no']) ?></td>
                            <td><?= htmlspecialchars($j['customer_name']) ?></td>
                            <td><?= htmlspecialchars($j['brand']) ?> <?= htmlspecialchars($j['model']) ?></td>
                            <td><?= formatDate($j['created_at']) ?></td>
                            <td><?= money($j['total_amount']) ?></td>
                            <td><?= money($j['due_amount']) ?></td>
                            <td><span class="badge-status badge-<?= $j['status'] ?>"><?= ucfirst($j['status']) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td colspan="4">Total</td>
                            <td><?= money($total_amount) ?></td>
                            <td><?= money($total_due) ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>