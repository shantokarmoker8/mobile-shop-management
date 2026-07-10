<?php
require_once __DIR__ . '/../includes/auth.php';
checkPermission($pdo, 'purchase', 'print');

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT p.*, s.name as supplier_name, s.phone as supplier_phone 
                        FROM purchases p 
                        LEFT JOIN suppliers s ON p.supplier_id = s.id 
                        WHERE p.id = ?");
$stmt->execute([$id]);
$purchase = $stmt->fetch();

if (!$purchase) {
    die('Purchase not found.');
}

$stmt = $pdo->prepare("SELECT pi.*, pr.name as product_name FROM purchase_items pi 
                        LEFT JOIN products pr ON pi.product_id = pr.id 
                        WHERE pi.purchase_id = ?");
$stmt->execute([$id]);
$items = $stmt->fetchAll();

$settings = getSettings($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Purchase Invoice - <?= htmlspecialchars($purchase['invoice_no']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body>

<div class="invoice-box">
    <div class="text-center mb-3">
        <img src="<?= BASE_URL ?>assets/logo.png" style="width:56px;height:56px;object-fit:contain;">
        <h5 class="fw-bold mb-0 mt-1"><?= htmlspecialchars($settings['shop_name'] ?? APP_NAME) ?></h5>
        <p class="small text-muted mb-0"><?= htmlspecialchars($settings['address'] ?? '') ?></p>
        <p class="small text-muted mb-0"><?= htmlspecialchars($settings['phone'] ?? '') ?></p>
    </div>
    <hr>

    <div class="d-flex justify-content-between small mb-2">
        <div>
            <strong>Supplier:</strong> <?= htmlspecialchars($purchase['supplier_name']) ?><br>
            <strong>Phone:</strong> <?= htmlspecialchars($purchase['supplier_phone']) ?>
        </div>
        <div class="text-end">
            <strong>Invoice:</strong> <?= htmlspecialchars($purchase['invoice_no']) ?><br>
            <strong>Date:</strong> <?= formatDate($purchase['created_at']) ?>
        </div>
    </div>
    <hr>

    <table>
        <thead>
            <tr class="border-bottom">
                <th class="text-start">Item</th>
                <th class="text-center">Qty</th>
                <th class="text-end">Price</th>
                <th class="text-end">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $it): ?>
            <tr>
                <td><?= htmlspecialchars($it['product_name']) ?></td>
                <td class="text-center"><?= $it['quantity'] ?></td>
                <td class="text-end"><?= money($it['buy_price']) ?></td>
                <td class="text-end"><?= money($it['total']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <hr>

    <div class="d-flex justify-content-between"><span>Total Amount</span><strong><?= money($purchase['total_amount']) ?></strong></div>
    <div class="d-flex justify-content-between"><span>Paid Amount</span><strong><?= money($purchase['paid_amount']) ?></strong></div>
    <div class="d-flex justify-content-between"><span>Due Amount</span><strong><?= money($purchase['due_amount']) ?></strong></div>

    <?php if (!empty($settings['invoice_footer'])): ?>
    <hr>
    <p class="small text-center text-muted"><?= nl2br(htmlspecialchars($settings['invoice_footer'])) ?></p>
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