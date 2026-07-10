<?php
require_once __DIR__ . '/../includes/auth.php';
checkPermission($pdo, 'service', 'edit');

$page_title = 'Edit Service Job';

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM service_jobs WHERE id = ?");
$stmt->execute([$id]);
$job = $stmt->fetch();

if (!$job) {
    setFlash('error', 'Service job not found.');
    redirect(BASE_URL . 'service/index.php');
}

// This edit form only allows updating status, problem description, and collecting additional payment.
// Parts and pricing are locked after creation to keep stock and cash calculations consistent.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = clean($_POST['status']);
    $problem_description = clean($_POST['problem_description']);
    $additional_payment = (float)$_POST['additional_payment'];

    if (!in_array($status, ['pending','working','completed','delivered'])) {
        setFlash('error', 'Invalid status.');
        redirect(BASE_URL . 'service/edit.php?id=' . $id);
    }

    if ($additional_payment > $job['due_amount']) {
        setFlash('error', 'Additional payment cannot exceed current due.');
        redirect(BASE_URL . 'service/edit.php?id=' . $id);
    }

    $pdo->beginTransaction();
    try {
        $new_paid = $job['paid_amount'] + $additional_payment;
        $new_due = $job['due_amount'] - $additional_payment;

        $stmt = $pdo->prepare("UPDATE service_jobs SET status=?, problem_description=?, paid_amount=?, due_amount=? WHERE id=?");
        $stmt->execute([$status, $problem_description, $new_paid, $new_due, $id]);

        if ($additional_payment > 0) {
            addCashTransaction($pdo, 'sale', $additional_payment, 'in', $id, 'Additional payment for Service Job ' . $job['job_no'], $_SESSION['user_id']);
        }

        $pdo->commit();
        setFlash('success', 'Service job updated successfully.');
        redirect(BASE_URL . 'service/view.php?id=' . $id);
    } catch (Exception $e) {
        $pdo->rollBack();
        setFlash('error', 'Something went wrong. Please try again.');
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">Edit Service Job - <?= htmlspecialchars($job['job_no']) ?></h5>
            <a href="view.php?id=<?= $id ?>" class="btn btn-soft btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-7">
                <div class="card-panel">
                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Status</label>
                                <select name="status" class="form-select">
                                    <option value="pending" <?= $job['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="working" <?= $job['status'] === 'working' ? 'selected' : '' ?>>Working</option>
                                    <option value="completed" <?= $job['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                    <option value="delivered" <?= $job['status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Current Due</label>
                                <input type="text" class="form-control" readonly value="<?= money($job['due_amount']) ?>">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small fw-semibold">Problem Description</label>
                                <textarea name="problem_description" class="form-control" rows="3"><?= htmlspecialchars($job['problem_description']) ?></textarea>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small fw-semibold">Collect Additional Payment</label>
                                <input type="number" step="0.01" max="<?= $job['due_amount'] ?>" name="additional_payment" class="form-control" value="0">
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Update Job</button>
                            <a href="view.php?id=<?= $id ?>" class="btn btn-soft">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>