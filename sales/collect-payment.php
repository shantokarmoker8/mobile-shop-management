<?php
require_once __DIR__ . '/../includes/auth.php';
checkPermission($pdo, 'sales', 'edit');

$page_title = 'Collect Sale Payment';

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

if ($sale['due_amount'] <= 0) {
    setFlash('error', 'This sale has no due amount.');
    redirect(BASE_URL . 'sales/view.php?id=' . $id);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = (float)$_POST['amount'];
    $note = clean($_POST['note']);

    if ($amount <= 0) {
        setFlash('error', 'Payment amount must be greater than zero.');
        redirect(BASE_URL . 'sales/collect-payment.php?id=' . $id);
    }

    if ($amount > $sale['due_amount']) {
        setFlash('error', 'Payment amount cannot exceed the due amount.');
        redirect(BASE_URL . 'sales/collect-payment.php?id=' . $id);
    }

    $pdo->beginTransaction();
    try {
        $new_paid = $sale['paid_amount'] + $amount;
        $new_due = $sale['due_amount'] - $amount;

        $stmt = $pdo->prepare("UPDATE sales SET paid_amount = ?, due_amount = ? WHERE id = ?");
        $stmt->execute([$new_paid, $new_due, $id]);

        // Reduce customer's overall due
        if ($sale['customer_id']) {
            $stmt = $pdo->prepare("UPDATE customers SET total_due = total_due - ? WHERE id = ?");
            $stmt->execute([$amount, $sale['customer_id']]);

            // Log in customer_payments so it also shows in customer ledger
            $stmt = $pdo->prepare("INSERT INTO customer_payments (customer_id, sale_id, amount, note, created_by) VALUES (?,?,?,?,?)");
            $stmt->execute([$sale['customer_id'], $id, $amount, $note ?: ('Payment for Invoice ' . $sale['invoice_no']), $_SESSION['user_id']]);
        }

        // Cash comes in
        addCashTransaction($pdo, 'customer_payment', $amount, 'in', $id, 'Due collection for Invoice ' . $sale['invoice_no'], $_SESSION['user_id']);

        $pdo->commit();
        setFlash('success', 'Payment collected successfully.');
        redirect(BASE_URL . 'sales/view.php?id=' . $id);
    } catch (Exception $e) {
        $pdo->rollBack();
        setFlash('error', 'Something went wrong. Please try again.');
        redirect(BASE_URL . 'sales/collect-payment.php?id=' . $id);
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">Collect Payment - <?= htmlspecialchars($sale['invoice_no']) ?></h5>
            <a href="view.php?id=<?= $id ?>" class="btn btn-soft btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-5">
                <div class="card-panel">
                    <div class="text-center mb-3">
                        <p class="text-muted small mb-1">Customer</p>
                        <p class="fw-semibold mb-2"><?= htmlspecialchars($sale['customer_name'] ?: 'Walk-in') ?></p>
                        <p class="text-muted small mb-1">Due Amount</p>
                        <h3 class="text-danger fw-bold"><?= money($sale['due_amount']) ?></h3>
                    </div>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Payment Amount *</label>
                            <input type="number" step="0.01" max="<?= $sale['due_amount'] ?>" name="amount" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Note</label>
                            <textarea name="note" class="form-control" rows="2" placeholder="Optional note"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-check-lg me-1"></i>Record Payment</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>