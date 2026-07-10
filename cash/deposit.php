<?php
require_once __DIR__ . '/../includes/auth.php';
checkPermission($pdo, 'dashboard', 'add');

$page_title = 'Cash Deposit';

// Check if opening balance already exists
$stmt = $pdo->query("SELECT COUNT(*) as cnt FROM cash_transactions WHERE type = 'opening'");
$has_opening = $stmt->fetch()['cnt'] > 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = (float)$_POST['amount'];
    $note = clean($_POST['note']);
    $is_opening = isset($_POST['is_opening']) && !$has_opening;

    if ($amount <= 0) {
        setFlash('error', 'Deposit amount must be greater than zero.');
    } else {
        $type = $is_opening ? 'opening' : 'deposit';
        $default_note = $is_opening ? 'Opening Balance' : 'Cash Deposit';

        addCashTransaction($pdo, $type, $amount, 'in', null, $note ?: $default_note, $_SESSION['user_id']);

        setFlash('success', ($is_opening ? 'Opening balance set' : 'Cash deposited') . ' successfully.');
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
            <h5 class="fw-bold mb-0">Cash Deposit</h5>
            <a href="index.php" class="btn btn-soft btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-5">
                <div class="card-panel">
                    <div class="text-center mb-3">
                        <p class="text-muted small mb-1">Current Cash</p>
                        <h4 class="text-primary fw-bold"><?= money(getCurrentCash($pdo)) ?></h4>
                    </div>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Deposit Amount *</label>
                            <input type="number" step="0.01" name="amount" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Note</label>
                            <textarea name="note" class="form-control" rows="2" placeholder="Optional note"></textarea>
                        </div>

                        <?php if (!$has_opening): ?>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="is_opening" id="isOpening">
                            <label class="form-check-label small" for="isOpening">
                                Set this as Opening Balance
                            </label>
                        </div>
                        <?php endif; ?>

                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-check-lg me-1"></i>Deposit</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>