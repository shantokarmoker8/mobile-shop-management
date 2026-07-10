<?php
require_once __DIR__ . '/../includes/auth.php';
checkPermission($pdo, 'sales', 'edit');

$page_title = 'Sales Return';

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT s.*, c.name as customer_name, c.id as customer_id FROM sales s 
                        LEFT JOIN customers c ON s.customer_id = c.id 
                        WHERE s.id = ?");
$stmt->execute([$id]);
$sale = $stmt->fetch();

if (!$sale) {
    setFlash('error', 'Sale not found.');
    redirect(BASE_URL . 'sales/index.php');
}

// Sale items with already-returned quantity subtracted
$stmt = $pdo->prepare("SELECT si.*, p.name as product_name,
    COALESCE((SELECT SUM(sri.quantity) FROM sale_return_items sri WHERE sri.sale_item_id = si.id), 0) as returned_qty
    FROM sale_items si
    LEFT JOIN products p ON si.product_id = p.id
    WHERE si.sale_id = ?");
$stmt->execute([$id]);
$items = $stmt->fetchAll();

// Filter out fully-returned items for the form, but keep for reference
$returnable_items = array_filter($items, function ($it) {
    return ($it['quantity'] - $it['returned_qty']) > 0;
});

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sale_item_ids = $_POST['sale_item_id'] ?? [];
    $return_qtys = $_POST['return_qty'] ?? [];
    $note = clean($_POST['note']);

    $return_items = [];
    $return_total = 0;

    foreach ($sale_item_ids as $i => $sale_item_id) {
        $sale_item_id = (int)$sale_item_id;
        $qty = (int)$return_qtys[$i];

        if ($qty <= 0) continue;

        // Find the matching item and validate available-to-return quantity
        $matched = null;
        foreach ($items as $it) {
            if ($it['id'] == $sale_item_id) { $matched = $it; break; }
        }
        if (!$matched) continue;

        $available = $matched['quantity'] - $matched['returned_qty'];
        if ($qty > $available) {
            setFlash('error', 'Return quantity for ' . $matched['product_name'] . ' exceeds available quantity (' . $available . ').');
            redirect(BASE_URL . 'sales/return.php?id=' . $id);
        }

        $line_total = $qty * $matched['sell_price'];
        $return_total += $line_total;

        $return_items[] = [
            'sale_item_id' => $sale_item_id,
            'product_id' => $matched['product_id'],
            'quantity' => $qty,
            'price' => $matched['sell_price'],
            'total' => $line_total,
            'buy_price' => $matched['buy_price']
        ];
    }

    if (count($return_items) === 0) {
        setFlash('error', 'Please enter a return quantity for at least one product.');
        redirect(BASE_URL . 'sales/return.php?id=' . $id);
    }

    $pdo->beginTransaction();
    try {
        $return_no = generateNumber('RET', $pdo, 'sale_returns', 'return_no');

        $stmt = $pdo->prepare("INSERT INTO sale_returns (sale_id, return_no, total_amount, note, created_by) VALUES (?,?,?,?,?)");
        $stmt->execute([$id, $return_no, $return_total, $note, $_SESSION['user_id']]);
        $return_id = $pdo->lastInsertId();

        $profit_removed = 0;

        foreach ($return_items as $ri) {
            $stmt = $pdo->prepare("INSERT INTO sale_return_items (sale_return_id, sale_item_id, product_id, quantity, price, total) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$return_id, $ri['sale_item_id'], $ri['product_id'], $ri['quantity'], $ri['price'], $ri['total']]);

            // Stock comes back
            updateStock($pdo, $ri['product_id'], $ri['quantity'], 'increase');

            $profit_removed += ($ri['price'] - $ri['buy_price']) * $ri['quantity'];
        }

        // Determine how the return value is settled:
        // first reduce any outstanding due on this sale, remainder is a cash refund
        $refund_from_due = min($sale['due_amount'], $return_total);
        $refund_cash = $return_total - $refund_from_due;

        $new_due = $sale['due_amount'] - $refund_from_due;
        $new_paid = $sale['paid_amount'] - $refund_cash;
        if ($new_paid < 0) $new_paid = 0;

        $new_total_amount = $sale['total_amount'] - $return_total;
        if ($new_total_amount < 0) $new_total_amount = 0;

        $new_total_profit = $sale['total_profit'] - $profit_removed;

        $stmt = $pdo->prepare("UPDATE sales SET total_amount = ?, paid_amount = ?, due_amount = ?, total_profit = ? WHERE id = ?");
        $stmt->execute([$new_total_amount, $new_paid, $new_due, $new_total_profit, $id]);

        // Reduce customer's overall due if the due portion was cleared by this return
        if ($sale['customer_id'] && $refund_from_due > 0) {
            $stmt = $pdo->prepare("UPDATE customers SET total_due = total_due - ? WHERE id = ?");
            $stmt->execute([$refund_from_due, $sale['customer_id']]);
        }

        // Cash goes out for the refunded cash portion
        if ($refund_cash > 0) {
            addCashTransaction($pdo, 'refund', $refund_cash, 'out', $return_id, 'Refund for Return ' . $return_no . ' (Invoice ' . $sale['invoice_no'] . ')', $_SESSION['user_id']);
        }

        $pdo->commit();
        setFlash('success', 'Return recorded successfully.');
        redirect(BASE_URL . 'sales/view.php?id=' . $id);
    } catch (Exception $e) {
        $pdo->rollBack();
        setFlash('error', 'Something went wrong. Please try again.');
        redirect(BASE_URL . 'sales/return.php?id=' . $id);
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">Sales Return - <?= htmlspecialchars($sale['invoice_no']) ?></h5>
            <a href="view.php?id=<?= $id ?>" class="btn btn-soft btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
        </div>

        <?php if (count($returnable_items) === 0): ?>
        <div class="alert alert-info">All items from this sale have already been fully returned.</div>
        <?php else: ?>

        <form method="POST">
            <div class="card-panel mb-3">
                <div class="table-responsive">
                    <table class="table table-custom mb-0">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Sold Qty</th>
                                <th>Already Returned</th>
                                <th>Price</th>
                                <th style="width:160px;">Return Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($returnable_items as $it): ?>
                            <?php $available = $it['quantity'] - $it['returned_qty']; ?>
                            <tr>
                                <td><?= htmlspecialchars($it['product_name']) ?></td>
                                <td><?= $it['quantity'] ?></td>
                                <td><?= $it['returned_qty'] ?></td>
                                <td><?= money($it['sell_price']) ?></td>
                                <td>
                                    <input type="hidden" name="sale_item_id[]" value="<?= $it['id'] ?>">
                                    <input type="number" name="return_qty[]" class="form-control form-control-sm" min="0" max="<?= $available ?>" value="0">
                                    <p class="small text-muted mb-0 mt-1">Max: <?= $available ?></p>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card-panel">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Reason / Note</label>
                    <textarea name="note" class="form-control" rows="2" placeholder="Optional reason for return"></textarea>
                </div>
                <p class="small text-muted">
                    Returned amount will first clear any due on this invoice; any remaining amount will be refunded from cash.
                </p>
                <button type="submit" class="btn btn-primary"><i class="bi bi-arrow-return-left me-1"></i>Process Return</button>
                <a href="view.php?id=<?= $id ?>" class="btn btn-soft">Cancel</a>
            </div>
        </form>

        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>