<?php
require_once __DIR__ . '/../includes/auth.php';
checkPermission($pdo, 'service', 'view');

$page_title = 'Service Job Details';

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT sj.*, u.full_name as created_by_name FROM service_jobs sj 
                        LEFT JOIN users u ON sj.created_by = u.id 
                        WHERE sj.id = ?");
$stmt->execute([$id]);
$job = $stmt->fetch();

if (!$job) {
    setFlash('error', 'Service job not found.');
    redirect(BASE_URL . 'service/index.php');
}

$stmt = $pdo->prepare("SELECT sjp.*, p.name as product_name FROM service_job_parts sjp 
                        LEFT JOIN products p ON sjp.product_id = p.id 
                        WHERE sjp.service_job_id = ?");
$stmt->execute([$id]);
$parts = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h5 class="fw-bold mb-0">Service Job - <?= h($job['job_no']) ?></h5>
            <div class="d-flex gap-2">
                <a href="receipt.php?id=<?= $id ?>" class="btn btn-soft btn-sm" target="_blank"><i class="bi bi-receipt me-1"></i>Receipt</a>
                <a href="invoice.php?id=<?= $id ?>" class="btn btn-primary btn-sm" target="_blank"><i class="bi bi-printer me-1"></i>Invoice</a>
                <a href="edit.php?id=<?= $id ?>" class="btn btn-soft btn-sm"><i class="bi bi-pencil me-1"></i>Edit</a>
                <a href="index.php" class="btn btn-soft btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="card-panel">
                    <p class="small text-muted mb-1">Customer</p>
                    <p class="fw-semibold mb-0"><?= h($job['customer_name']) ?></p>
                    <p class="small text-muted mb-0"><?= h($job['customer_phone']) ?></p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card-panel">
                    <p class="small text-muted mb-1">Device</p>
                    <p class="fw-semibold mb-0"><?= h($job['brand']) ?> <?= h($job['model']) ?></p>
                    <p class="small text-muted mb-0"><?= h($job['imei'] ?: 'No IMEI') ?></p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card-panel">
                    <p class="small text-muted mb-1">Status</p>
                    <span class="badge-status badge-<?= $job['status'] ?>"><?= ucfirst($job['status']) ?></span>
                </div>
            </div>
        </div>

        <div class="card-panel mb-3">
            <p class="small text-muted mb-1">Problem Description</p>
            <p class="mb-0"><?= nl2br(h($job['problem_description'])) ?></p>
        </div>

        <?php if (count($parts) > 0): ?>
        <div class="card-panel mb-3">
            <h6 class="fw-bold mb-3">Used Parts</h6>
            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr><th>Part</th><th>Qty</th><th>Price</th><th>Total</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($parts as $p): ?>
                        <tr>
                            <td><?= h($p['product_name']) ?></td>
                            <td><?= $p['quantity'] ?></td>
                            <td><?= money($p['price']) ?></td>
                            <td><?= money($p['total']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <div class="row g-3">
            <div class="col-6 col-md-3">
                <div class="stat-card justify-content-center"><div class="stat-text"><div class="stat-value"><?= money($job['labour_charge']) ?></div><div class="stat-label">Service Charge</div></div></div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card justify-content-center"><div class="stat-text"><div class="stat-value"><?= money($job['total_amount']) ?></div><div class="stat-label">Total Amount</div></div></div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card justify-content-center"><div class="stat-text"><div class="stat-value text-success"><?= money($job['paid_amount']) ?></div><div class="stat-label">Paid</div></div></div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card justify-content-center"><div class="stat-text"><div class="stat-value text-danger"><?= money($job['due_amount']) ?></div><div class="stat-label">Due</div></div></div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>