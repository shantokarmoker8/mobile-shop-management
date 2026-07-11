<?php
require_once __DIR__ . '/../includes/auth.php';
checkPermission($pdo, 'purchase', 'edit');

$page_title = 'Pay Purchase Due';

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT p.*, s.name as supplier_name FROM purchases p 
                        LEFT JOIN suppliers s ON p.supplier_id = s.id 
                        WHERE p.id = ?");
$stmt->execute([$id]);
$purchase = $stmt->fetch();

if (!$purchase) {
    setFlash('error', 'Purchase not found.');
    redirect(BASE_URL . 'purchase/index.php');
}

if ($purchase['due_amount'] <= 0) {
    setFlash('error', 'This purchase has no due amount.');
    redirect(BASE_URL . 'purchase/view.php?id=' . $id);
}

$current_cash = getCurrentCash($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = (float)$_POST['amount'];
    $note = clean($_POST['note']);

    if ($amount <= 0) {
        setFlash('error', 'Payment amount must be greater than zero.');
        redirect(BASE_URL . 'purchase/pay-due.php?id=' . $id);
    }

    if ($amount > $purchase['due_amount']) {
        setFlash('error', 'Payment amount cannot exceed the due amount.');
        redirect(BASE_URL . 'purchase/pay-due.php?id=' . $id);
    }

    if ($amount > $current_cash) {
        setFlash('error', 'Insufficient cash balance. Current cash: ' . money($current_cash) . '.');
        redirect(BASE_URL . 'purchase/pay-due.php?id=' . $id);
    }

    $pdo->beginTransaction();
    try {
        $new_paid = $purchase['paid_amount'] + $amount;
        $new_due = $purchase['due_amount'] - $amount;

        $stmt = $pdo->prepare("UPDATE purchases SET paid_amount = ?, due_amount = ? WHERE id = ?");
        $stmt->execute([$new_paid, $new_due, $id]);

        $stmt = $pdo->prepare("UPDATE suppliers SET total_due = total_due - ? WHERE id = ?");
        $stmt->execute([$amount, $purchase['supplier_id']]);

        $stmt = $pdo->prepare("INSERT INTO supplier_payments (supplier_id, purchase_id, amount, note, created_by) VALUES (?,?,?,?,?)");
        $stmt->execute([$purchase['supplier_id'], $id, $amount, $note ?: ('Payment for Invoice ' . $purchase['invoice_no']), $_SESSION['user_id']]);

        addCashTransaction($pdo, 'supplier_payment', $amount, 'out', $id, 'Due payment for Invoice ' . $purchase['invoice_no'], $_SESSION['user_id']);

        $pdo->commit();
        setFlash('success', 'Payment recorded successfully.');
        redirect(BASE_URL . 'purchase/view.php?id=' . $id);
    } catch (Exception $e) {
        $pdo->rollBack();
        setFlash('error', 'Something went wrong. Please try again.');
        redirect(BASE_URL . 'purchase/pay-due.php?id=' . $id);
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">Pay Due - <?= h($purchase['invoice_no']) ?></h5>
            <a href="view.php?id=<?= $id ?>" class="btn btn-soft btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-5">
                <div class="card-panel">
                    <div class="text-center mb-3">
                        <p class="text-muted small mb-1">Supplier</p>
                        <p class="fw-semibold mb-2"><?= h($purchase['supplier_name']) ?></p>
                        <p class="text-muted small mb-1">Due Amount</p>
                        <h3 class="text-danger fw-bold"><?= money($purchase['due_amount']) ?></h3>
                        <p class="text-muted small mb-0">Available Cash: <strong><?= money($current_cash) ?></strong></p>
                    </div>

                    <?php if ($current_cash <= 0): ?>
                    <div class="alert alert-warning small">You have no cash available to make a payment right now.</div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Payment Amount *</label>
                            <input type="number" step="0.01" max="<?= min($purchase['due_amount'], $current_cash) ?>" name="amount" class="form-control" required <?= $current_cash <= 0 ? 'disabled' : '' ?>>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Note</label>
                            <textarea name="note" class="form-control" rows="2" placeholder="Optional note"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100" <?= $current_cash <= 0 ? 'disabled' : '' ?>><i class="bi bi-check-lg me-1"></i>Record Payment</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>