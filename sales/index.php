<?php
require_once __DIR__ . '/../includes/auth.php';
checkPermission($pdo, 'sales', 'view');

$page_title = 'Sales List';

$search = clean($_GET['search'] ?? '');

$query = "SELECT s.*, c.name as customer_name FROM sales s 
          LEFT JOIN customers c ON s.customer_id = c.id 
          WHERE 1=1";
$params = [];

if ($search !== '') {
    $query .= " AND (s.invoice_no LIKE ? OR c.name LIKE ?)";
    $like = "%$search%";
    $params = [$like, $like];
}

$query .= " ORDER BY s.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$sales = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-body">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h5 class="fw-bold mb-0">Sales List</h5>
            <a href="pos.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>New Sale</a>
        </div>

        <div class="card-panel mb-3">
            <form method="GET" class="row g-2">
                <div class="col-md-6">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by invoice no or customer" value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-search me-1"></i>Search</button>
                </div>
            </form>
        </div>

        <div class="card-panel">
            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr>
                            <th>Invoice No</th>
                            <th>Customer</th>
                            <th>Type</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Paid</th>
                            <th>Due</th>
                            <th>Profit</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($sales) === 0): ?>
                        <tr><td colspan="9" class="text-center text-muted py-4">No sales found.</td></tr>
                        <?php endif; ?>

                        <?php foreach ($sales as $s): ?>
                        <tr>
                            <td class="fw-semibold"><?= htmlspecialchars($s['invoice_no']) ?></td>
                            <td><?= htmlspecialchars($s['customer_name'] ?: 'Walk-in') ?></td>
                            <td><?= ucfirst(str_replace('_',' ',$s['sale_type'])) ?></td>
                            <td><?= formatDate($s['created_at']) ?></td>
                            <td><?= money($s['total_amount'] - $s['discount']) ?></td>
                            <td><?= money($s['paid_amount']) ?></td>
                            <td>
                                <?php if ($s['due_amount'] > 0): ?>
                                    <span class="badge-status badge-due"><?= money($s['due_amount']) ?></span>
                                <?php else: ?>
                                    <span class="badge-status badge-paid">Paid</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-success fw-semibold"><?= money($s['total_profit']) ?></td>
                            <td class="text-end">
                                <a href="view.php?id=<?= $s['id'] ?>" class="btn btn-soft btn-sm" title="View"><i class="bi bi-eye"></i></a>
                                <?php if ($s['due_amount'] > 0): ?>
                                <a href="collect-payment.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-success" title="Collect Payment"><i class="bi bi-cash"></i></a>
                                <?php endif; ?>
                                <a href="return.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-danger" title="Sales Return"><i class="bi bi-arrow-return-left"></i></a>
                                <a href="invoice.php?id=<?= $s['id'] ?>" class="btn btn-soft btn-sm" title="Print" target="_blank"><i class="bi bi-printer"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>