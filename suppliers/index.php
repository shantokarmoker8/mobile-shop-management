<?php
require_once __DIR__ . '/../includes/auth.php';
checkPermission($pdo, 'purchase', 'view');

$page_title = 'Suppliers';

$search = clean($_GET['search'] ?? '');

$query = "SELECT * FROM suppliers WHERE 1=1";
$params = [];

if ($search !== '') {
    $query .= " AND (name LIKE ? OR phone LIKE ?)";
    $like = "%$search%";
    $params = [$like, $like];
}

$query .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$suppliers = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-body">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h5 class="fw-bold mb-0">Suppliers</h5>
            <a href="add.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Add Supplier</a>
        </div>

        <div class="card-panel mb-3">
            <form method="GET" class="row g-2">
                <div class="col-md-6">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by name or phone" value="<?= h($search) ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-search me-1"></i>Search</button>
                </div>
            </form>
        </div>

        <!-- Desktop Table -->
        <div class="card-panel desktop-only-table">
            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Address</th>
                            <th>Total Due</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($suppliers) === 0): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">No suppliers found.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($suppliers as $s): ?>
                        <tr>
                            <td class="fw-semibold"><?= h($s['name']) ?></td>
                            <td><?= h($s['phone'] ?: '-') ?></td>
                            <td><?= h($s['address'] ?: '-') ?></td>
                            <td>
                                <?php if ($s['total_due'] > 0): ?>
                                    <span class="badge-status badge-due"><?= money($s['total_due']) ?></span>
                                <?php else: ?>
                                    <span class="badge-status badge-paid">0.00</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge-status <?= $s['status'] === 'active' ? 'badge-completed' : 'badge-due' ?>">
                                    <?= ucfirst($s['status']) ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="ledger.php?id=<?= $s['id'] ?>" class="btn btn-soft btn-sm" title="Ledger"><i class="bi bi-journal-text"></i></a>
                                <a href="edit.php?id=<?= $s['id'] ?>" class="btn btn-soft btn-sm" title="Edit"><i class="bi bi-pencil"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Mobile List -->
        <div class="mlist">
            <?php if (count($suppliers) === 0): ?>
            <div class="mlist-empty"><i class="bi bi-truck d-block mb-2" style="font-size:24px;"></i>No suppliers found.</div>
            <?php endif; ?>

            <?php foreach ($suppliers as $s): ?>
            <div class="mlist-item">
                <a href="ledger.php?id=<?= $s['id'] ?>" class="mlist-link">
                    <div class="mlist-avatar"><?= h(mb_substr($s['name'], 0, 1)) ?></div>
                    <div class="mlist-body">
                        <div class="mlist-title"><?= h($s['name']) ?></div>
                        <div class="mlist-sub"><?= h($s['phone'] ?: 'No phone') ?></div>
                    </div>
                </a>
                <div class="mlist-end">
                    <?php if ($s['total_due'] > 0): ?>
                        <div class="mlist-value text-danger"><?= money($s['total_due']) ?></div>
                        <div class="mlist-meta">Due</div>
                    <?php else: ?>
                        <div class="mlist-value text-success">Paid</div>
                        <div class="mlist-meta"><?= ucfirst($s['status']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="dropdown">
                    <button class="mlist-kebab" data-bs-toggle="dropdown"><i class="bi bi-three-dots-vertical"></i></button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="ledger.php?id=<?= $s['id'] ?>"><i class="bi bi-journal-text me-2"></i>View Ledger</a></li>
                        <li><a class="dropdown-item" href="edit.php?id=<?= $s['id'] ?>"><i class="bi bi-pencil me-2"></i>Edit</a></li>
                        <?php if ($s['total_due'] > 0): ?>
                        <li><a class="dropdown-item" href="payment.php?id=<?= $s['id'] ?>"><i class="bi bi-cash me-2"></i>Pay Due</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>