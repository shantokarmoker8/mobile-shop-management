<?php
require_once __DIR__ . '/../includes/auth.php';
checkPermission($pdo, 'service', 'print');

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM service_jobs WHERE id = ?");
$stmt->execute([$id]);
$job = $stmt->fetch();

if (!$job) {
    die('Service job not found.');
}

$settings = getSettings($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Service Receipt - <?= htmlspecialchars($job['job_no']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body>

<div class="invoice-box">
    <div class="text-center mb-3">
        <img src="<?= BASE_URL ?>assets/logo.png" style="width:56px;height:56px;object-fit:contain;">
        <h5 class="fw-bold mb-0 mt-1"><?= htmlspecialchars($settings['shop_name'] ?? APP_NAME) ?></h5>
        <p class="small text-muted mb-0"><?= htmlspecialchars($settings['phone'] ?? '') ?></p>
    </div>
    <hr>

    <h6 class="text-center fw-bold mb-3">SERVICE RECEIPT</h6>

    <table class="w-100 small">
        <tr><td class="fw-semibold">Job No</td><td class="text-end"><?= htmlspecialchars($job['job_no']) ?></td></tr>
        <tr><td class="fw-semibold">Date</td><td class="text-end"><?= formatDate($job['created_at']) ?></td></tr>
        <tr><td class="fw-semibold">Customer</td><td class="text-end"><?= htmlspecialchars($job['customer_name']) ?></td></tr>
        <tr><td class="fw-semibold">Phone</td><td class="text-end"><?= htmlspecialchars($job['customer_phone']) ?></td></tr>
        <tr><td class="fw-semibold">Device</td><td class="text-end"><?= htmlspecialchars($job['brand']) ?> <?= htmlspecialchars($job['model']) ?></td></tr>
        <?php if ($job['imei']): ?>
        <tr><td class="fw-semibold">IMEI</td><td class="text-end"><?= htmlspecialchars($job['imei']) ?></td></tr>
        <?php endif; ?>
    </table>
    <hr>

    <p class="small fw-semibold mb-1">Problem Description</p>
    <p class="small"><?= nl2br(htmlspecialchars($job['problem_description'])) ?></p>
    <hr>

    <div class="d-flex justify-content-between"><span>Status</span><strong><?= ucfirst($job['status']) ?></strong></div>
    <div class="d-flex justify-content-between"><span>Advance Paid</span><strong><?= money($job['paid_amount']) ?></strong></div>

    <p class="small text-center text-muted mt-3">Please keep this receipt safe. It is required to collect your device.</p>

    <div class="text-center mt-3 no-print">
        <button class="btn btn-primary btn-sm" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
    </div>
</div>

</body>
</html>