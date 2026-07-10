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

$stmt = $pdo->prepare("SELECT sjp.*, p.name as product_name FROM service_job_parts sjp 
                        LEFT JOIN products p ON sjp.product_id = p.id 
                        WHERE sjp.service_job_id = ?");
$stmt->execute([$id]);
$parts = $stmt->fetchAll();

$settings = getSettings($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Service Invoice - <?= h($job['job_no']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body>

<div class="invoice-box">
    <div class="text-center mb-3">
        <img src="<?= BASE_URL ?>assets/logo.png" style="width:56px;height:56px;object-fit:contain;">
        <h5 class="fw-bold mb-0 mt-1"><?= h($settings['shop_name'] ?? APP_NAME) ?></h5>
        <p class="small text-muted mb-0"><?= h($settings['address'] ?? '') ?></p>
        <p class="small text-muted mb-0"><?= h($settings['phone'] ?? '') ?></p>
    </div>
    <hr>

    <div class="d-flex justify-content-between small mb-2">
        <div>
            <strong>Customer:</strong> <?= h($job['customer_name']) ?><br>
            <strong>Phone:</strong> <?= h($job['customer_phone']) ?><br>
            <strong>Device:</strong> <?= h($job['brand']) ?> <?= h($job['model']) ?>
        </div>
        <div class="text-end">
            <strong>Job No:</strong> <?= h($job['job_no']) ?><br>
            <strong>Date:</strong> <?= formatDate($job['created_at']) ?>
        </div>
    </div>
    <hr>

    <?php if (count($parts) > 0): ?>
    <table>
        <thead>
            <tr class="border-bottom">
                <th class="text-start">Part</th>
                <th class="text-center">Qty</th>
                <th class="text-end">Price</th>
                <th class="text-end">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($parts as $p): ?>
            <tr>
                <td><?= h($p['product_name']) ?></td>
                <td class="text-center"><?= $p['quantity'] ?></td>
                <td class="text-end"><?= money($p['price']) ?></td>
                <td class="text-end"><?= money($p['total']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <hr>
    <?php endif; ?>

    <div class="d-flex justify-content-between"><span>Parts Total</span><strong><?= money($job['parts_total']) ?></strong></div>
    <div class="d-flex justify-content-between"><span>Service Charge</span><strong><?= money($job['labour_charge']) ?></strong></div>
    <div class="d-flex justify-content-between"><span>Discount</span><strong><?= money($job['discount']) ?></strong></div>
    <div class="d-flex justify-content-between"><span>Net Total</span><strong><?= money($job['total_amount']) ?></strong></div>
    <div class="d-flex justify-content-between"><span>Paid Amount</span><strong><?= money($job['paid_amount']) ?></strong></div>
    <div class="d-flex justify-content-between"><span>Due Amount</span><strong><?= money($job['due_amount']) ?></strong></div>

    <?php if (!empty($settings['invoice_footer'])): ?>
    <hr>
    <p class="small text-center text-muted"><?= nl2br(h($settings['invoice_footer'])) ?></p>
    <?php endif; ?>

    <div class="text-center mt-3 no-print">
        <button class="btn btn-primary btn-sm" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
    </div>
</div>

<?php if (!empty($settings['auto_print'])): ?>
<script>window.print();</script>
<?php endif; ?>

</body>
</html>