<?php
require_once __DIR__ . '/../includes/auth.php';

$page_title = 'Dashboard';

$filter = $_GET['filter'] ?? 'today';
$start_date = '';
$end_date = date('Y-m-d');

switch ($filter) {
    case 'today': $start_date = date('Y-m-d'); break;
    case 'last7': $start_date = date('Y-m-d', strtotime('-6 days')); break;
    case 'last30': $start_date = date('Y-m-d', strtotime('-29 days')); break;
    case 'this_month': $start_date = date('Y-m-01'); break;
    case 'this_year': $start_date = date('Y-01-01'); break;
    case 'last_year': $start_date = date('Y-m-d', strtotime('-1 year')); break;
    default: $start_date = date('Y-m-d');
}

$start_full = $start_date . ' 00:00:00';
$end_full = $end_date . ' 23:59:59';

$current_cash = getCurrentCash($pdo);
$stock_value = getStockValue($pdo);

$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount - discount),0) as total FROM sales WHERE created_at BETWEEN ? AND ?");
$stmt->execute([$start_full, $end_full]);
$total_sales = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) as total FROM purchases WHERE created_at BETWEEN ? AND ?");
$stmt->execute([$start_full, $end_full]);
$total_purchase = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) as total FROM expenses WHERE created_at BETWEEN ? AND ?");
$stmt->execute([$start_full, $end_full]);
$total_expense = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_profit),0) as total FROM sales WHERE created_at BETWEEN ? AND ?");
$stmt->execute([$start_full, $end_full]);
$sales_profit = $stmt->fetch()['total'];

$net_profit = $sales_profit - $total_expense;

$stmt = $pdo->query("SELECT COALESCE(SUM(total_due),0) as total FROM customers");
$customer_due = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COALESCE(SUM(total_due),0) as total FROM suppliers");
$supplier_due = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as cnt FROM service_jobs WHERE status IN ('pending','working')");
$pending_service = $stmt->fetch()['cnt'];

$stmt = $pdo->query("SELECT COUNT(*) as cnt FROM products WHERE quantity <= low_stock_qty AND status = 'active'");
$low_stock = $stmt->fetch()['cnt'];

$chart_labels = [];
$chart_sales = [];
$chart_purchase = [];
$chart_expense = [];

for ($i = 6; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-$i days"));
    $chart_labels[] = date('d M', strtotime($day));

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount - discount),0) as total FROM sales WHERE DATE(created_at) = ?");
    $stmt->execute([$day]);
    $chart_sales[] = (float)$stmt->fetch()['total'];

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) as total FROM purchases WHERE DATE(created_at) = ?");
    $stmt->execute([$day]);
    $chart_purchase[] = (float)$stmt->fetch()['total'];

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) as total FROM expenses WHERE DATE(created_at) = ?");
    $stmt->execute([$day]);
    $chart_expense[] = (float)$stmt->fetch()['total'];
}

$stmt = $pdo->query("SELECT u.full_name, COALESCE(SUM(s.total_amount - s.discount),0) as total 
                      FROM sales s LEFT JOIN users u ON s.created_by = u.id 
                      GROUP BY s.created_by ORDER BY total DESC");
$sales_by_user = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-body">
    <div class="dashboard-fit">

        <div class="dash-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="fw-bold mb-0">Overview</h5>
            <form method="GET" class="d-flex gap-2">
                <select name="filter" class="form-select form-select-sm" onchange="this.form.submit()" style="width:auto;">
                    <option value="today" <?= $filter === 'today' ? 'selected' : '' ?>>Today</option>
                    <option value="last7" <?= $filter === 'last7' ? 'selected' : '' ?>>Last 7 Days</option>
                    <option value="last30" <?= $filter === 'last30' ? 'selected' : '' ?>>Last 30 Days</option>
                    <option value="this_month" <?= $filter === 'this_month' ? 'selected' : '' ?>>This Month</option>
                    <option value="this_year" <?= $filter === 'this_year' ? 'selected' : '' ?>>This Year</option>
                    <option value="last_year" <?= $filter === 'last_year' ? 'selected' : '' ?>>Last 1 Year</option>
                </select>
            </form>
        </div>

        <div class="stats-grid">
            <a href="<?= BASE_URL ?>cash/index.php" class="stat-card-link">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#2F5BE0;"><i class="bi bi-wallet2"></i></div>
                    <div class="stat-text">
                        <div class="stat-value"><?= money($current_cash) ?></div>
                        <div class="stat-label">Current Cash</div>
                    </div>
                </div>
            </a>
            <a href="<?= BASE_URL ?>reports/stock.php" class="stat-card-link">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#7c3aed;"><i class="bi bi-boxes"></i></div>
                    <div class="stat-text">
                        <div class="stat-value"><?= money($stock_value) ?></div>
                        <div class="stat-label">Stock Value</div>
                    </div>
                </div>
            </a>
            <a href="<?= BASE_URL ?>sales/index.php" class="stat-card-link">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#1fa15a;"><i class="bi bi-cash-coin"></i></div>
                    <div class="stat-text">
                        <div class="stat-value"><?= money($total_sales) ?></div>
                        <div class="stat-label">Total Sales</div>
                    </div>
                </div>
            </a>
            <a href="<?= BASE_URL ?>purchase/index.php" class="stat-card-link">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#f0a93a;"><i class="bi bi-cart-plus"></i></div>
                    <div class="stat-text">
                        <div class="stat-value"><?= money($total_purchase) ?></div>
                        <div class="stat-label">Total Purchase</div>
                    </div>
                </div>
            </a>
            <a href="<?= BASE_URL ?>expense/index.php" class="stat-card-link">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#e14343;"><i class="bi bi-receipt"></i></div>
                    <div class="stat-text">
                        <div class="stat-value"><?= money($total_expense) ?></div>
                        <div class="stat-label">Total Expense</div>
                    </div>
                </div>
            </a>
            <a href="<?= BASE_URL ?>reports/profit.php" class="stat-card-link">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#0d9488;"><i class="bi bi-graph-up-arrow"></i></div>
                    <div class="stat-text">
                        <div class="stat-value"><?= money($net_profit) ?></div>
                        <div class="stat-label">Net Profit</div>
                    </div>
                </div>
            </a>
            <a href="<?= BASE_URL ?>reports/customer-due.php" class="stat-card-link">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#dc2626;"><i class="bi bi-person-exclamation"></i></div>
                    <div class="stat-text">
                        <div class="stat-value"><?= money($customer_due) ?></div>
                        <div class="stat-label">Customer Due</div>
                    </div>
                </div>
            </a>
            <a href="<?= BASE_URL ?>reports/supplier-due.php" class="stat-card-link">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#9333ea;"><i class="bi bi-truck"></i></div>
                    <div class="stat-text">
                        <div class="stat-value"><?= money($supplier_due) ?></div>
                        <div class="stat-label">Supplier Due</div>
                    </div>
                </div>
            </a>
            <a href="<?= BASE_URL ?>service/index.php?status=pending" class="stat-card-link">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#2563eb;"><i class="bi bi-tools"></i></div>
                    <div class="stat-text">
                        <div class="stat-value"><?= $pending_service ?></div>
                        <div class="stat-label">Pending Service</div>
                    </div>
                </div>
            </a>
            <a href="<?= BASE_URL ?>products/index.php?stock=low" class="stat-card-link">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#ea580c;"><i class="bi bi-exclamation-triangle"></i></div>
                    <div class="stat-text">
                        <div class="stat-value"><?= $low_stock ?></div>
                        <div class="stat-label">Low Stock Items</div>
                    </div>
                </div>
            </a>
        </div>

        <div class="charts-slider dash-charts-row">
            <div class="chart-slide">
                <div class="card-panel h-100">
                    <h6 class="fw-bold mb-3">Sales / Purchase / Expense (Last 7 Days)</h6>
                    <div class="chart-box">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="chart-slide">
                <div class="card-panel h-100">
                    <h6 class="fw-bold mb-3">Sales by User</h6>
                    <div class="chart-box">
                        <canvas id="userSalesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

    </div>
    </div>
</div>

<script>
new Chart(document.getElementById('trendChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [
            { label: 'Sales', data: <?= json_encode($chart_sales) ?>, borderColor: '#2F5BE0', backgroundColor: 'rgba(47,91,224,0.08)', tension: 0.35, fill: true },
            { label: 'Purchase', data: <?= json_encode($chart_purchase) ?>, borderColor: '#f0a93a', backgroundColor: 'rgba(240,169,58,0.08)', tension: 0.35, fill: true },
            { label: 'Expense', data: <?= json_encode($chart_expense) ?>, borderColor: '#e14343', backgroundColor: 'rgba(225,67,67,0.08)', tension: 0.35, fill: true }
        ]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true } } }
});

new Chart(document.getElementById('userSalesChart'), {
    type: 'doughnut',
    data: {
        labels: [<?php foreach ($sales_by_user as $u) echo "'" . addslashes($u['full_name'] ?: 'Unknown') . "',"; ?>],
        datasets: [{
            data: [<?php foreach ($sales_by_user as $u) echo $u['total'] . ","; ?>],
            backgroundColor: ['#2F5BE0', '#7c3aed', '#1fa15a', '#f0a93a', '#e14343', '#0d9488', '#dc2626']
        }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>