<?php
require_once __DIR__ . '/../includes/auth.php';
checkPermission($pdo, 'service', 'view');

$page_title = 'Service Jobs';

$search = clean($_GET['search'] ?? '');
$status_filter = $_GET['status'] ?? '';

$query = "SELECT * FROM service_jobs WHERE 1=1";
$params = [];

if ($search !== '') {
    $query .= " AND (job_no LIKE ? OR customer_name LIKE ? OR customer_phone LIKE ? OR imei LIKE ?)";
    $like = "%$search%";
    $params = [$like, $like, $like, $like];
}

if ($status_filter !== '') {
    $query .= " AND status = ?";
    $params[] = $status_filter;
}

$query .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$jobs = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-body">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h5 class="fw-bold mb-0">Service Jobs</h5>
            <a href="add.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>New Service Job</a>
        </div>

        <div class="card-panel mb-3">
            <form method="GET" class="row g-2">
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by job no, customer, phone, IMEI" value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Status</option>
                        <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="working" <?= $status_filter === 'working' ? 'selected' : '' ?>>Working</option>
                        <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="delivered" <?= $status_filter === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-search me-1"></i>Filter</button>
                </div>
            </form>
        </div>

        <div class="card-panel">
            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr>
                            <th>Job No</th>
                            <th>Customer</th>
                            <th>Brand / Model</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Due</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($jobs) === 0): ?>
                        <tr><td colspan="8" class="text-center text-muted py-4">No service jobs found.</td></tr>
                        <?php endif; ?>

                        <?php foreach ($jobs as $j): ?>
                        <tr>
                            <td class="fw-semibold"><?= htmlspecialchars($j['job_no']) ?></td>
                            <td><?= htmlspecialchars($j['customer_name']) ?><br><span class="small text-muted"><?= htmlspecialchars($j['customer_phone']) ?></span></td>
                            <td><?= htmlspecialchars($j['brand']) ?> <?= htmlspecialchars($j['model']) ?></td>
                            <td><?= formatDate($j['created_at']) ?></td>
                            <td><?= money($j['total_amount']) ?></td>
                            <td>
                                <?php if ($j['due_amount'] > 0): ?>
                                    <span class="badge-status badge-due"><?= money($j['due_amount']) ?></span>
                                <?php else: ?>
                                    <span class="badge-status badge-paid">Paid</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge-status badge-<?= $j['status'] ?>"><?= ucfirst($j['status']) ?></span></td>
                            <td class="text-end">
                                <a href="view.php?id=<?= $j['id'] ?>" class="btn btn-soft btn-sm" title="View"><i class="bi bi-eye"></i></a>
                                <a href="edit.php?id=<?= $j['id'] ?>" class="btn btn-soft btn-sm" title="Edit"><i class="bi bi-pencil"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>