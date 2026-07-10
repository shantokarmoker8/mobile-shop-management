<?php
require_once __DIR__ . '/../includes/auth.php';
checkPermission($pdo, 'sales', 'print');

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT s.*, c.name as customer_name, c.phone as customer_phone 
                        FROM sales s 
                        LEFT JOIN customers c ON s.customer_id = c.id 
                        WHERE s.id = ?");
$stmt->execute([$id]);
$sale = $stmt->fetch();

if (!$sale) {
    die('Sale not found.');
}

$stmt = $pdo->prepare("SELECT si.*, p.name as product_name FROM sale_items si 
                        LEFT JOIN products p ON si.product_id = p.id 
                        WHERE si.sale_id = ?");
$stmt->execute([$id]);
$items = $stmt->fetchAll();

$settings = getSettings($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Invoice - <?= htmlspecialchars($sale['invoice_no']) ?></title>
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

    <?php if (!empty($settings['invoice_header'])): ?>
    <p class="small text-center"><?= nl2br(htmlspecialchars($settings['invoice_header'])) ?></p>
    <?php endif; ?>

    <hr>

    <div class="d-flex justify-content-between small mb-2">
        <div>
            <strong>Customer:</strong> <?= htmlspecialchars($sale['customer_name'] ?: 'Walk-in') ?><br>
            <?php if ($sale['customer_phone']): ?>
            <strong>Phone:</strong> <?= htmlspecialchars($sale['customer_phone']) ?>
            <?php endif; ?>
        </div>
        <div class="text-end">
            <strong>Invoice:</strong> <?= htmlspecialchars($sale['invoice_no']) ?><br>
            <strong>Date:</strong> <?= formatDate($sale['created_at']) ?>
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
                <td class="text-end"><?= money($it['sell_price']) ?></td>
                <td class="text-end"><?= money($it['total']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <hr>

    <div class="d-flex justify-content-between"><span>Subtotal</span><strong><?= money($sale['total_amount']) ?></strong></div>
    <div class="d-flex justify-content-between"><span>Discount</span><strong><?= money($sale['discount']) ?></strong></div>
    <div class="d-flex justify-content-between"><span>Net Total</span><strong><?= money($sale['total_amount'] - $sale['discount']) ?></strong></div>
    <div class="d-flex justify-content-between"><span>Paid Amount</span><strong><?= money($sale['paid_amount']) ?></strong></div>
    <div class="d-flex justify-content-between"><span>Due Amount</span><strong><?= money($sale['due_amount']) ?></strong></div>

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