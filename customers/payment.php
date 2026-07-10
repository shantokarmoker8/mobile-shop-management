<?php
require_once __DIR__ . '/../includes/auth.php';
checkPermission($pdo, 'sales', 'edit');

$page_title = 'Collect Customer Due';

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$id]);
$customer = $stmt->fetch();

if (!$customer) {
    setFlash('error', 'Customer not found.');
    redirect(BASE_URL . 'customers/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = (float)$_POST['amount'];
    $note = clean($_POST['note']);

    if ($amount <= 0) {
        setFlash('error', 'Payment amount must be greater than zero.');
    } elseif ($amount > $customer['total_due']) {
        setFlash('error', 'Payment amount cannot exceed current due.');
    } else {
        $pdo->beginTransaction();
        try {
            // Record payment
            $stmt = $pdo->prepare("INSERT INTO customer_payments (customer_id, amount, note, created_by) VALUES (?,?,?,?)");
            $stmt->execute([$id, $amount, $note, $_SESSION['user_id']]);

            // Reduce customer due
            $stmt = $pdo->prepare("UPDATE customers SET total_due = total_due - ? WHERE id = ?");
            $stmt->execute([$amount, $id]);

            // Cash comes in (customer paying us)
            addCashTransaction($pdo, 'customer_payment', $amount, 'in', $id, 'Due collection from ' . $customer['name'], $_SESSION['user_id']);

            $pdo->commit();
            setFlash('success', 'Payment collected successfully.');
            redirect(BASE_URL . 'customers/ledger.php?id=' . $id);
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
            <h5 class="fw-bold mb-0">Collect Due - <?= htmlspecialchars($customer['name']) ?></h5>
            <a href="ledger.php?id=<?= $id ?>" class="btn btn-soft btn-sm"><i class="bi bi-arrow-left me-1"></i>Back to Ledger</a>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-5">
                <div class="card-panel">
                    <div class="text-center mb-3">
                        <p class="text-muted small mb-1">Current Due</p>
                        <h3 class="text-danger fw-bold"><?= money($customer['total_due']) ?></h3>
                    </div>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Payment Amount *</label>
                            <input type="number" step="0.01" max="<?= $customer['total_due'] ?>" name="amount" class="form-control" required>
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