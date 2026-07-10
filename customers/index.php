<?php
require_once __DIR__ . '/../includes/auth.php';
checkPermission($pdo, 'sales', 'view');

$page_title = 'Customers';

$search = clean($_GET['search'] ?? '');

$query = "SELECT * FROM customers WHERE 1=1";
$params = [];

if ($search !== '') {
    $query .= " AND (name LIKE ? OR phone LIKE ?)";
    $like = "%$search%";
    $params = [$like, $like];
}

$query .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$customers = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-body">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h5 class="fw-bold mb-0">Customers</h5>
            <a href="add.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Add Customer</a>
        </div>

        <div class="card-panel mb-3">
            <form method="GET" class="row g-2">
                <div class="col-md-6">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by name or phone" value="<?= htmlspecialchars($search) ?>">
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
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Address</th>
                            <th>Total Due</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($customers) === 0): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">No customers found.</td></tr>
                        <?php endif; ?>

                        <?php foreach ($customers as $c): ?>
                        <tr>
                            <td class="fw-semibold"><?= htmlspecialchars($c['name']) ?></td>
                            <td><?= htmlspecialchars($c['phone'] ?: '-') ?></td>
                            <td><?= htmlspecialchars($c['address'] ?: '-') ?></td>
                            <td>
                                <?php if ($c['total_due'] > 0): ?>
                                    <span class="badge-status badge-due"><?= money($c['total_due']) ?></span>
                                <?php else: ?>
                                    <span class="badge-status badge-paid">0.00</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a href="ledger.php?id=<?= $c['id'] ?>" class="btn btn-soft btn-sm" title="Ledger"><i class="bi bi-journal-text"></i></a>
                                <a href="edit.php?id=<?= $c['id'] ?>" class="btn btn-soft btn-sm" title="Edit"><i class="bi bi-pencil"></i></a>
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