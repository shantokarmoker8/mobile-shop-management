<?php
require_once __DIR__ . '/../includes/auth.php';
checkPermission($pdo, 'reports', 'view');

$page_title = 'Profit Report';

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

$start_full = $start_date . ' 00:00:00';
$end_full = $end_date . ' 23:59:59';

$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_profit),0) as total FROM sales WHERE created_at BETWEEN ? AND ?");
$stmt->execute([$start_full, $end_full]);
$sales_profit = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COALESCE(SUM(labour_charge),0) as labour FROM service_jobs WHERE created_at BETWEEN ? AND ?");
$stmt->execute([$start_full, $end_full]);
$service_labour = $stmt->fetch()['labour'];

$stmt = $pdo->prepare("SELECT COALESCE(SUM((sjp.price - p.buy_price) * sjp.quantity),0) as parts_profit 
                        FROM service_job_parts sjp 
                        LEFT JOIN service_jobs sj ON sjp.service_job_id = sj.id 
                        LEFT JOIN products p ON sjp.product_id = p.id 
                        WHERE sj.created_at BETWEEN ? AND ?");
$stmt->execute([$start_full, $end_full]);
$service_parts_profit = $stmt->fetch()['parts_profit'];

$service_profit = $service_labour + $service_parts_profit;

$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) as total FROM expenses WHERE created_at BETWEEN ? AND ?");
$stmt->execute([$start_full, $end_full]);
$total_expense = $stmt->fetch()['total'];

$gross_profit = $sales_profit + $service_profit;
$net_profit = $gross_profit - $total_expense;

$chart_labels = [];
$chart_data = [];
$days = min(30, (strtotime($end_date) - strtotime($start_date)) / 86400 + 1);
for ($i = $days - 1; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime($end_date . " -$i days"));
    $chart_labels[] = date('d M', strtotime($day));

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_profit),0) as total FROM sales WHERE DATE(created_at) = ?");
    $stmt->execute([$day]);
    $chart_data[] = (float)$stmt->fetch()['total'];
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-body">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h5 class="fw-bold mb-0">Profit Report</h5>
            <?php include __DIR__ . '/_report_nav.php'; ?>
        </div>

        <div class="card-panel mb-3">
            <form method="GET" class="row g-2">
                <div class="col-6 col-md-4">
                    <label class="form-label small fw-semibold">Start Date</label>
                    <input type="date" name="start_date" class="form-control form-control-sm" value="<?= $start_date ?>">
                </div>
                <div class="col-6 col-md-4">
                    <label class="form-label small fw-semibold">End Date</label>
                    <input type="date" name="end_date" class="form-control form-control-sm" value="<?= $end_date ?>">
                </div>
                <div class="col-6 col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-search me-1"></i>Filter</button>
                </div>
                <div class="col-6 col-md-2 d-flex align-items-end">
                    <button type="button" class="btn btn-soft btn-sm w-100" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
                </div>
            </form>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-6 col-md-3">
                <div class="stat-card"><div class="stat-text"><div class="stat-value text-success"><?= money($sales_profit) ?></div><div class="stat-label">Sales Profit</div></div></div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card"><div class="stat-text"><div class="stat-value text-success"><?= money($service_profit) ?></div><div class="stat-label">Service Profit</div></div></div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card"><div class="stat-text"><div class="stat-value text-danger"><?= money($total_expense) ?></div><div class="stat-label">Total Expense</div></div></div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card"><div class="stat-text"><div class="stat-value text-primary"><?= money($net_profit) ?></div><div class="stat-label">Net Profit</div></div></div>
            </div>
        </div>

        <div class="card-panel">
            <h6 class="fw-bold mb-3">Sales Profit Trend</h6>
            <div class="chart-box" style="height:220px;">
                <canvas id="profitChart"></canvas>
            </div>
        </div>

    </div>
</div>

<script>
new Chart(document.getElementById('profitChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [{
            label: 'Profit',
            data: <?= json_encode($chart_data) ?>,
            backgroundColor: '#2F5BE0',
            borderRadius: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>