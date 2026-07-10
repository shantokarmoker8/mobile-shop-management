<?php
require_once __DIR__ . '/../includes/auth.php';

if ($_SESSION['role'] !== 'admin') {
    echo '<div class="alert alert-danger m-4">You do not have permission to access this section.</div>';
    exit;
}

$page_title = 'Backup Data';

// Handle Download Request
if (isset($_GET['download'])) {
    $tables = [
        'users', 'user_permissions', 'categories', 'products', 'suppliers',
        'purchases', 'purchase_items', 'supplier_payments', 'customers',
        'sales', 'sale_items', 'customer_payments', 'service_jobs',
        'service_job_parts', 'cash_transactions', 'expense_categories',
        'expenses', 'settings'
    ];

    $backup = [
        'generated_at' => date('Y-m-d H:i:s'),
        'app' => APP_NAME,
        'data' => []
    ];

    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT * FROM `$table`");
        $backup['data'][$table] = $stmt->fetchAll();
    }

    $filename = 'backup_' . date('Y-m-d_His') . '.json';

    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// Quick stats for display
$stmt = $pdo->query("SELECT 
    (SELECT COUNT(*) FROM products) as products,
    (SELECT COUNT(*) FROM sales) as sales,
    (SELECT COUNT(*) FROM purchases) as purchases,
    (SELECT COUNT(*) FROM customers) as customers,
    (SELECT COUNT(*) FROM suppliers) as suppliers,
    (SELECT COUNT(*) FROM service_jobs) as services
");
$stats = $stmt->fetch();

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
                    <i class="bi bi-cloud-download text-primary" style="font-size:48px;"></i>
                    <h5 class="fw-bold mt-3">Backup All Data</h5>
                    <p class="text-muted small mb-4">Download a complete backup of your shop data as a JSON file. Keep this file safe — it contains all your products, sales, purchases, customers, suppliers, and settings.</p>

                    <div class="row g-2 mb-4 text-start">
                        <div class="col-4">
                            <div class="stat-card text-center">
                                <div class="stat-value"><?= $stats['products'] ?></div>
                                <div class="stat-label">Products</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-card text-center">
                                <div class="stat-value"><?= $stats['sales'] ?></div>
                                <div class="stat-label">Sales</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-card text-center">
                                <div class="stat-value"><?= $stats['purchases'] ?></div>
                                <div class="stat-label">Purchases</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-card text-center">
                                <div class="stat-value"><?= $stats['customers'] ?></div>
                                <div class="stat-label">Customers</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-card text-center">
                                <div class="stat-value"><?= $stats['suppliers'] ?></div>
                                <div class="stat-label">Suppliers</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-card text-center">
                                <div class="stat-value"><?= $stats['services'] ?></div>
                                <div class="stat-label">Service Jobs</div>
                            </div>
                        </div>
                    </div>

                    <a href="?download=1" class="btn btn-primary"><i class="bi bi-download me-1"></i>Download Full Backup (JSON)</a>
                </div>
            </div>
        </div>

    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>