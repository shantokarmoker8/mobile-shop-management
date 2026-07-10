<?php
require_once __DIR__ . '/../includes/auth.php';

if ($_SESSION['role'] !== 'admin') {
    echo '<div class="alert alert-danger m-4">You do not have permission to access this section.</div>';
    exit;
}

$page_title = 'Delete All Data';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password1 = $_POST['password1'] ?? '';
    $password2 = $_POST['password2'] ?? '';
    $type_confirm = clean($_POST['type_confirm'] ?? '');

    // Verify current admin's password (direct compare, as per system design)
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if ($password1 === '' || $password2 === '') {
        setFlash('error', 'Both password confirmations are required.');
        redirect(BASE_URL . 'settings/delete-data.php');
    }

    if ($password1 !== $user['password'] || $password2 !== $user['password']) {
        setFlash('error', 'Incorrect password. Data was NOT deleted.');
        redirect(BASE_URL . 'settings/delete-data.php');
    }

    if ($type_confirm !== 'DELETE') {
        setFlash('error', 'You must type DELETE exactly to confirm. Data was NOT deleted.');
        redirect(BASE_URL . 'settings/delete-data.php');
    }

    // All checks passed - wipe transactional data (keep the current admin user itself)
    $tables_to_clear = [
        'service_job_parts', 'service_jobs',
        'customer_payments', 'sale_items', 'sales',
        'supplier_payments', 'purchase_items', 'purchases',
        'cash_transactions', 'expenses', 'expense_categories',
        'products', 'categories', 'customers', 'suppliers',
        'user_permissions'
    ];

    $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
    try {
        foreach ($tables_to_clear as $table) {
            $pdo->exec("TRUNCATE TABLE `$table`");
        }
        // Remove staff accounts too, keep only current logged-in admin
        $stmt = $pdo->prepare("DELETE FROM users WHERE id != ?");
        $stmt->execute([$_SESSION['user_id']]);

        $pdo->exec("SET FOREIGN_KEY_CHECKS=1");

        setFlash('success', 'All data has been permanently deleted.');
        redirect(BASE_URL . 'dashboard/index.php');
    } catch (Exception $e) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
        setFlash('error', 'Something went wrong: ' . $e->getMessage());
        redirect(BASE_URL . 'settings/delete-data.php');
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-body">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h5 class="fw-bold mb-0">Settings</h5>
            <?php include __DIR__ . '/_settings_nav.php'; ?>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-7">
                <div class="card-panel">
                    <div class="text-center mb-3">
                        <i class="bi bi-exclamation-octagon text-danger" style="font-size:48px;"></i>
                        <h5 class="fw-bold mt-3 text-danger">Delete All Data</h5>
                    </div>

                    <div class="alert alert-danger small">
                        <strong>This is permanent and cannot be undone.</strong><br>
                        This will delete ALL products, sales, purchases, customers, suppliers, service jobs, expenses, cash history, and staff accounts. Your admin login will be kept so you can continue using the system.
                        <br><br>
                        Please download a backup first from the <a href="backup.php">Backup</a> page if you are not sure.
                    </div>

                    <form method="POST" id="deleteAllForm" onsubmit="return confirmDeleteAll(event)">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Enter Your Password (1st confirmation) *</label>
                            <input type="password" name="password1" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Enter Your Password Again (2nd confirmation) *</label>
                            <input type="password" name="password2" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Type <code>DELETE</code> to confirm *</label>
                            <input type="text" name="type_confirm" class="form-control" placeholder="DELETE" required>
                        </div>

                        <button type="submit" class="btn btn-outline-danger w-100"><i class="bi bi-trash3 me-1"></i>Permanently Delete All Data</button>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
function confirmDeleteAll(e) {
    e.preventDefault();
    Swal.fire({
        title: 'Are you absolutely sure?',
        text: 'All shop data will be permanently deleted. This cannot be undone.',
        icon: 'error',
        showCancelButton: true,
        confirmButtonColor: '#e14343',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, delete everything'
    }).then((result) => {
        if (result.isConfirmed) {
            e.target.submit();
        }
    });
    return false;
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>