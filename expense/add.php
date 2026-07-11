<?php
require_once __DIR__ . '/../includes/auth.php';
checkPermission($pdo, 'expense', 'add');

$page_title = 'Add Expense';

$current_cash = getCurrentCash($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id = (int)$_POST['category_id'];
    $amount = (float)$_POST['amount'];
    $note = clean($_POST['note']);

    if ($category_id <= 0 || $amount <= 0) {
        setFlash('error', 'Please select a category and enter a valid amount.');
    } elseif ($amount > $current_cash) {
        setFlash('error', 'Insufficient cash balance. Current cash: ' . money($current_cash) . '.');
    } else {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO expenses (category_id, amount, note, created_by) VALUES (?,?,?,?)");
            $stmt->execute([$category_id, $amount, $note, $_SESSION['user_id']]);
            $expense_id = $pdo->lastInsertId();

            addCashTransaction($pdo, 'expense', $amount, 'out', $expense_id, $note ?: 'Expense', $_SESSION['user_id']);

            $pdo->commit();
            setFlash('success', 'Expense added successfully.');
            redirect(BASE_URL . 'expense/index.php');
        } catch (Exception $e) {
            $pdo->rollBack();
            setFlash('error', 'Something went wrong. Please try again.');
        }
    }
}

$categories = $pdo->query("SELECT * FROM expense_categories WHERE status = 'active' ORDER BY name")->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">Add Expense</h5>
            <a href="index.php" class="btn btn-soft btn-sm"><i class="bi bi-arrow-left me-1"></i>Back to List</a>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="alert alert-info small mb-3">
                    <i class="bi bi-wallet2 me-1"></i>
                    Available Cash Balance: <strong><?= money($current_cash) ?></strong>
                </div>

                <div class="card-panel">
                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Category *</label>
                                <select name="category_id" class="form-select" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= h($cat['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Amount * <span class="text-muted">(Max: <?= money($current_cash) ?>)</span></label>
                                <input type="number" step="0.01" name="amount" class="form-control" max="<?= $current_cash ?>" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small fw-semibold">Note</label>
                                <textarea name="note" class="form-control" rows="2" placeholder="Optional note"></textarea>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary" <?= $current_cash <= 0 ? 'disabled' : '' ?>><i class="bi bi-check-lg me-1"></i>Save Expense</button>
                            <a href="index.php" class="btn btn-soft">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>