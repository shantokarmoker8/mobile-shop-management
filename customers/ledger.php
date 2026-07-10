<?php
require_once __DIR__ . '/../includes/auth.php';
checkPermission($pdo, 'sales', 'view');

$page_title = 'Customer Ledger';

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$id]);
$customer = $stmt->fetch();

if (!$customer) {
    setFlash('error', 'Customer not found.');
    redirect(BASE_URL . 'customers/index.php');
}

// Sales history
$stmt = $pdo->prepare("SELECT * FROM sales WHERE customer_id = ? ORDER BY created_at DESC");
$stmt->execute([$id]);
$sales = $stmt->fetchAll();

// Service history (matched by phone number since service_jobs has no customer_id)
$stmt = $pdo->prepare("SELECT * FROM service_jobs WHERE customer_phone = ? ORDER BY created_at DESC");
$stmt->execute([$customer['phone']]);
$services = $stmt->fetchAll();

// Payment history
$stmt = $pdo->prepare("SELECT cp.*, u.full_name FROM customer_payments cp LEFT JOIN users u ON cp.created_by = u.id WHERE cp.customer_id = ? ORDER BY cp.created_at DESC");
$stmt->execute([$id]);
$payments = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <h5 class="fw-bold mb-0"><?= htmlspecialchars($customer['name']) ?></h5>
                <p class="text-muted small mb-0"><?= htmlspecialchars($customer['phone']) ?></p>
            </div>
            <div class="d-flex gap-2">
                <a href="payment.php?id=<?= $customer['id'] ?>" class="btn btn-primary btn-sm"><i class="bi bi-cash me-1"></i>Collect Due</a>
                <a href="index.php" class="btn btn-soft btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-value text-danger"><?= money($customer['total_due']) ?></div>
                    <div class="stat-label">Current Due</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-value"><?= count($sales) ?></div>
                    <div class="stat-label">Total Purchases</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-value"><?= count($services) ?></div>
                    <div class="stat-label">Service Jobs</div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-6">
                <div class="card-panel mb-3">
                    <h6 class="fw-bold mb-3">Purchase History</h6>
                    <div class="table-responsive">
                        <table class="table table-custom mb-0">
                            <thead>
                                <tr><th>Invoice</th><th>Date</th><th>Total</th><th>Due</th></tr>
                            </thead>
                            <tbody>
                                <?php if (count($sales) === 0): ?>
                                <tr><td colspan="4" class="text-center text-muted py-3">No purchases found.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($sales as $s): ?>
                                <tr>
                                    <td><a href="<?= BASE_URL ?>sales/view.php?id=<?= $s['id'] ?>"><?= htmlspecialchars($s['invoice_no']) ?></a></td>
                                    <td><?= formatDate($s['created_at']) ?></td>
                                    <td><?= money($s['total_amount'] - $s['discount']) ?></td>
                                    <td><?= money($s['due_amount']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card-panel">
                    <h6 class="fw-bold mb-3">Service History</h6>
                    <div class="table-responsive">
                        <table class="table table-custom mb-0">
                            <thead>
                                <tr><th>Job No</th><th>Date</th><th>Status</th><th>Due</th></tr>
                            </thead>
                            <tbody>
                                <?php if (count($services) === 0): ?>
                                <tr><td colspan="4" class="text-center text-muted py-3">No service jobs found.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($services as $sv): ?>
                                <tr>
                                    <td><a href="<?= BASE_URL ?>service/view.php?id=<?= $sv['id'] ?>"><?= htmlspecialchars($sv['job_no']) ?></a></td>
                                    <td><?= formatDate($sv['created_at']) ?></td>
                                    <td><span class="badge-status badge-<?= $sv['status'] ?>"><?= ucfirst($sv['status']) ?></span></td>
                                    <td><?= money($sv['due_amount']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card-panel">
                    <h6 class="fw-bold mb-3">Payment History</h6>
                    <div class="table-responsive">
                        <table class="table table-custom mb-0">
                            <thead>
                                <tr><th>Date</th><th>Amount</th><th>Note</th><th>By</th></tr>
                            </thead>
                            <tbody>
                                <?php if (count($payments) === 0): ?>
                                <tr><td colspan="4" class="text-center text-muted py-3">No payments found.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($payments as $pay): ?>
                                <tr>
                                    <td><?= formatDate($pay['created_at']) ?></td>
                                    <td class="text-success fw-semibold"><?= money($pay['amount']) ?></td>
                                    <td><?= htmlspecialchars($pay['note'] ?: '-') ?></td>
                                    <td><?= htmlspecialchars($pay['full_name'] ?? '-') ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>