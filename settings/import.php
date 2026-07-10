<?php
require_once __DIR__ . '/../includes/auth.php';

if ($_SESSION['role'] !== 'admin') {
    echo '<div class="alert alert-danger m-4">You do not have permission to access this section.</div>';
    exit;
}

$page_title = 'Import Data';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_FILES['backup_file']['tmp_name'])) {
        setFlash('error', 'Please select a backup file to import.');
        redirect(BASE_URL . 'settings/import.php');
    }

    $content = file_get_contents($_FILES['backup_file']['tmp_name']);
    $backup = json_decode($content, true);

    if (!$backup || !isset($backup['data'])) {
        setFlash('error', 'Invalid backup file format.');
        redirect(BASE_URL . 'settings/import.php');
    }

    // Import order matters due to foreign key relationships
    $import_order = [
        'categories', 'suppliers', 'customers', 'products', 'users', 'user_permissions',
        'expense_categories', 'settings',
        'purchases', 'purchase_items', 'supplier_payments',
        'sales', 'sale_items', 'customer_payments',
        'service_jobs', 'service_job_parts',
        'cash_transactions', 'expenses'
    ];

    $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
    $pdo->beginTransaction();

    try {
        foreach ($import_order as $table) {
            if (!isset($backup['data'][$table]) || count($backup['data'][$table]) === 0) continue;

            $pdo->exec("TRUNCATE TABLE `$table`");

            $rows = $backup['data'][$table];
            $columns = array_keys($rows[0]);
            $columnList = '`' . implode('`,`', $columns) . '`';
            $placeholders = implode(',', array_fill(0, count($columns), '?'));

            $stmt = $pdo->prepare("INSERT INTO `$table` ($columnList) VALUES ($placeholders)");

            foreach ($rows as $row) {
                $stmt->execute(array_values($row));
            }
        }

        $pdo->commit();
        $pdo->exec("SET FOREIGN_KEY_CHECKS=1");

        setFlash('success', 'Data imported successfully. All previous data has been replaced.');
        redirect(BASE_URL . 'settings/import.php');
    } catch (Exception $e) {
        $pdo->rollBack();
        $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
        setFlash('error', 'Import failed: ' . $e->getMessage());
        redirect(BASE_URL . 'settings/import.php');
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
                <div class="card-panel text-center">
                    <i class="bi bi-cloud-upload text-primary" style="font-size:48px;"></i>
                    <h5 class="fw-bold mt-3">Import Data</h5>
                    <div class="alert alert-warning text-start small mt-3">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        <strong>Warning:</strong> Importing a backup file will completely replace all current data in the system with the data from the file. This action cannot be undone. Make sure you have a current backup before proceeding.
                    </div>

                    <form method="POST" enctype="multipart/form-data" class="text-start mt-3" onsubmit="return confirmImport(event)">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Select Backup File (.json)</label>
                            <input type="file" name="backup_file" accept=".json" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-upload me-1"></i>Import & Replace All Data</button>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
function confirmImport(e) {
    e.preventDefault();
    Swal.fire({
        title: 'Replace all data?',
        text: 'This will delete all current data and replace it with the backup file content. This cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e14343',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, import and replace'
    }).then((result) => {
        if (result.isConfirmed) {
            e.target.submit();
        }
    });
    return false;
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>