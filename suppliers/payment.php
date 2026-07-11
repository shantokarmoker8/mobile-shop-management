<?php
require_once __DIR__ . '/../includes/auth.php';
checkPermission($pdo, 'purchase', 'edit');

$page_title = 'Supplier Payment';

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id = ?");
$stmt->execute([$id]);
$supplier = $stmt->fetch();

if (!$supplier) {
    setFlash('error', 'Supplier not found.');
    redirect(BASE_URL . 'suppliers/index.php');
}

$current_cash = getCurrentCash($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = (float)$_POST['amount'];
    $note = clean($_POST['note']);

    if ($amount <= 0) {
        setFlash('error', 'Payment amount must be greater than zero.');
    } elseif ($amount > $supplier['total_due']) {
        setFlash('error', 'Payment amount cannot exceed current due.');
    } elseif ($amount > $current_cash) {
        setFlash('error', 'Insufficient cash balance. Current cash: ' . money($current_cash) . '.');
    } else {
        $pdo->beginTransaction();
        try {
            $remaining = $amount;

            $stmt = $pdo->prepare("SELECT id, invoice_no, due_amount, paid_amount FROM purchases WHERE supplier_id = ? AND due_amount > 0 ORDER BY created_at ASC");
            $stmt->execute([$id]);
            $unpaid_purchases = $stmt->fetchAll();

            foreach ($unpaid_purchases as $purchase) {
                if ($remaining <= 0) break;

                $pay_here = min($purchase['due_amount'], $remaining);

                $new_paid = $purchase['paid_amount'] + $pay_here;
                $new_due = $purchase['due_amount'] - $pay_here;

                $stmt2 = $pdo->prepare("UPDATE purchases SET paid_amount = ?, due_amount = ? WHERE id = ?");
                $stmt2->execute([$new_paid, $new_due, $purchase['id']]);

                $stmt2 = $pdo->prepare("INSERT INTO supplier_payments (supplier_id, purchase_id, amount, note, created_by) VALUES (?,?,?,?,?)");
                $stmt2->execute([$id, $purchase['id'], $pay_here, ($note ?: 'Payment') . ' - Invoice ' . $purchase['invoice_no'], $_SESSION['user_id']]);

                $remaining -= $pay_here;
            }

            $stmt = $pdo->prepare("UPDATE suppliers SET total_due = total_due - ? WHERE id = ?");
            $stmt->execute([$amount, $id]);

            addCashTransaction($pdo, 'supplier_payment', $amount, 'out', $id, 'Payment to ' . $supplier['name'], $_SESSION['user_id']);

            $pdo->commit();
            setFlash('success', 'Payment recorded successfully.');
            redirect(BASE_URL . 'suppliers/ledger.php?id=' . $id);
        } catch (Exception $e) {
            $pdo->rollBack();
            setFlash('error', 'Something went wrong. Please try again.');
        }
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">Pay Due - <?= h($supplier['name']) ?></h5>
            <a href="ledger.php?id=<?= $id ?>" class="btn btn-soft btn-sm"><i class="bi bi-arrow-left me-1"></i>Back to Ledger</a>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-5">
                <div class="card-panel">
                    <div class="text-center mb-3">
                        <p class="text-muted small mb-1">Current Due</p>
                        <h3 class="text-danger fw-bold"><?= money($supplier['total_due']) ?></h3>
                        <p class="text-muted small mb-0">Available Cash: <strong><?= money($current_cash) ?></strong></p>
                    </div>

                    <?php if ($current_cash <= 0): ?>
                    <div class="alert alert-warning small">You have no cash available to make a payment right now.</div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Payment Amount *</label>
                            <input type="number" step="0.01" max="<?= min($supplier['total_due'], $current_cash) ?>" name="amount" class="form-control" required <?= $current_cash <= 0 ? 'disabled' : '' ?>>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Note</label>
                            <textarea name="note" class="form-control" rows="2" placeholder="Optional note"></textarea>
                        </div>
                        <p class="small text-muted">This will be applied to the oldest unpaid purchase invoices first.</p>
                        <button type="submit" class="btn btn-primary w-100" <?= $current_cash <= 0 ? 'disabled' : '' ?>><i class="bi bi-check-lg me-1"></i>Record Payment</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>