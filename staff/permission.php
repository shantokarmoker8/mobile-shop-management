<?php
require_once __DIR__ . '/../includes/auth.php';

if ($_SESSION['role'] !== 'admin') {
    echo '<div class="alert alert-danger m-4">You do not have permission to access this section.</div>';
    exit;
}

$page_title = 'Staff Permissions';

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'staff'");
$stmt->execute([$id]);
$staff = $stmt->fetch();

if (!$staff) {
    setFlash('error', 'Staff not found.');
    redirect(BASE_URL . 'staff/index.php');
}

$modules = [
    'dashboard' => 'Dashboard',
    'products'  => 'Products',
    'purchase'  => 'Purchase',
    'sales'     => 'Sales',
    'service'   => 'Service',
    'reports'   => 'Reports',
    'expense'   => 'Expense',
    'settings'  => 'Settings'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo->beginTransaction();
    try {
        foreach ($modules as $key => $label) {
            $can_view = isset($_POST['perm'][$key]['view']) ? 1 : 0;
            $can_add = isset($_POST['perm'][$key]['add']) ? 1 : 0;
            $can_edit = isset($_POST['perm'][$key]['edit']) ? 1 : 0;
            $can_delete = isset($_POST['perm'][$key]['delete']) ? 1 : 0;
            $can_print = isset($_POST['perm'][$key]['print']) ? 1 : 0;

            // Upsert permission row
            $stmt = $pdo->prepare("SELECT id FROM user_permissions WHERE user_id = ? AND module = ?");
            $stmt->execute([$id, $key]);
            $existing = $stmt->fetch();

            if ($existing) {
                $stmt = $pdo->prepare("UPDATE user_permissions SET can_view=?, can_add=?, can_edit=?, can_delete=?, can_print=? WHERE id=?");
                $stmt->execute([$can_view, $can_add, $can_edit, $can_delete, $can_print, $existing['id']]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO user_permissions (user_id, module, can_view, can_add, can_edit, can_delete, can_print) VALUES (?,?,?,?,?,?,?)");
                $stmt->execute([$id, $key, $can_view, $can_add, $can_edit, $can_delete, $can_print]);
            }
        }

        $pdo->commit();
        setFlash('success', 'Permissions updated successfully.');
        redirect(BASE_URL . 'staff/permission.php?id=' . $id);
    } catch (Exception $e) {
        $pdo->rollBack();
        setFlash('error', 'Something went wrong. Please try again.');
    }
}

// Load current permissions
$stmt = $pdo->prepare("SELECT * FROM user_permissions WHERE user_id = ?");
$stmt->execute([$id]);
$current_permissions = [];
foreach ($stmt->fetchAll() as $p) {
    $current_permissions[$p['module']] = $p;
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h5 class="fw-bold mb-0">Permissions - <?= htmlspecialchars($staff['full_name']) ?></h5>
            <a href="index.php" class="btn btn-soft btn-sm"><i class="bi bi-arrow-left me-1"></i>Back to List</a>
        </div>

        <form method="POST">
            <div class="card-panel">
                <div class="table-responsive">
                    <table class="table table-custom mb-0">
                        <thead>
                            <tr>
                                <th>Module</th>
                                <th class="text-center">View</th>
                                <th class="text-center">Add</th>
                                <th class="text-center">Edit</th>
                                <th class="text-center">Delete</th>
                                <th class="text-center">Print</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($modules as $key => $label): 
                                $perm = $current_permissions[$key] ?? null;
                            ?>
                            <tr>
                                <td class="fw-semibold"><?= $label ?></td>
                                <td class="text-center">
                                    <input type="checkbox" class="form-check-input" name="perm[<?= $key ?>][view]" <?= ($perm && $perm['can_view']) ? 'checked' : '' ?>>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" class="form-check-input" name="perm[<?= $key ?>][add]" <?= ($perm && $perm['can_add']) ? 'checked' : '' ?>>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" class="form-check-input" name="perm[<?= $key ?>][edit]" <?= ($perm && $perm['can_edit']) ? 'checked' : '' ?>>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" class="form-check-input" name="perm[<?= $key ?>][delete]" <?= ($perm && $perm['can_delete']) ? 'checked' : '' ?>>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" class="form-check-input" name="perm[<?= $key ?>][print]" <?= ($perm && $perm['can_print']) ? 'checked' : '' ?>>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save Permissions</button>
                <a href="index.php" class="btn btn-soft">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>