<?php
require_once __DIR__ . '/../includes/auth.php';
checkPermission($pdo, 'dashboard', 'add');

$page_title = 'Cash Withdraw';

$current_cash = getCurrentCash($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = (float)$_POST['amount'];
    $note = clean($_POST['note']);

    if ($amount <= 0) {
        setFlash('error', 'Withdraw amount must be greater than zero.');
    } elseif ($amount > $current_cash) {
        setFlash('error', 'Withdraw amount cannot exceed current cash balance.');
    } else {
        addCashTransaction($pdo, 'withdraw', $amount, 'out', null, $note ?: 'Cash Withdraw', $_SESSION['user_id']);

        setFlash('success', 'Cash withdrawn successfully.');
        redirect(BASE_URL . 'cash/index.php');
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">Cash Withdraw</h5>
            <a href="index.php" class="btn btn-soft btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-5">
                <div class="card-panel">
                    <div class="text-center mb-3">
                        <p class="text-muted small mb-1">Current Cash</p>
                        <h4 class="text-primary fw-bold"><?= money($current_cash) ?></h4>
                    </div>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Withdraw Amount *</label>
                            <input type="number" step="0.01" max="<?= $current_cash ?>" name="amount" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Note</label>
                            <textarea name="note" class="form-control" rows="2" placeholder="Optional note"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-check-lg me-1"></i>Withdraw</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>