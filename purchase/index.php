<?php
require_once __DIR__ . '/../includes/auth.php';
checkPermission($pdo, 'purchase', 'view');

$page_title = 'Purchase List';

$search = clean($_GET['search'] ?? '');

$query = "SELECT p.*, s.name as supplier_name FROM purchases p 
          LEFT JOIN suppliers s ON p.supplier_id = s.id 
          WHERE 1=1";
$params = [];

if ($search !== '') {
    $query .= " AND (p.invoice_no LIKE ? OR s.name LIKE ?)";
    $like = "%$search%";
    $params = [$like, $like];
}

$query .= " ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$purchases = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-body">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h5 class="fw-bold mb-0">Purchase List</h5>
            <a href="add.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>New Purchase</a>
        </div>

        <div class="card-panel mb-3">
            <form method="GET" class="row g-2">
                <div class="col-md-6">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by invoice no or supplier" value="<?= htmlspecialchars($search) ?>">
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
                            <th>Supplier</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Paid</th>
                            <th>Due</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($purchases) === 0): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">No purchases found.</td></tr>
                        <?php endif; ?>

                        <?php foreach ($purchases as $p): ?>
                        <tr>
                            <td class="fw-semibold"><?= htmlspecialchars($p['invoice_no']) ?></td>
                            <td><?= htmlspecialchars($p['supplier_name'] ?: '-') ?></td>
                            <td><?= formatDate($p['created_at']) ?></td>
                            <td><?= money($p['total_amount']) ?></td>
                            <td><?= money($p['paid_amount']) ?></td>
                            <td>
                                <?php if ($p['due_amount'] > 0): ?>
                                    <span class="badge-status badge-due"><?= money($p['due_amount']) ?></span>
                                <?php else: ?>
                                    <span class="badge-status badge-paid">Paid</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a href="view.php?id=<?= $p['id'] ?>" class="btn btn-soft btn-sm" title="View"><i class="bi bi-eye"></i></a>
                                <?php if ($p['due_amount'] > 0): ?>
                                <a href="pay-due.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-success" title="Pay Due"><i class="bi bi-cash"></i></a>
                                <?php endif; ?>
                                <a href="invoice.php?id=<?= $p['id'] ?>" class="btn btn-soft btn-sm" title="Print" target="_blank"><i class="bi bi-printer"></i></a>
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