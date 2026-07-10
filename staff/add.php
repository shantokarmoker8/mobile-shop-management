<?php
require_once __DIR__ . '/../includes/auth.php';

if ($_SESSION['role'] !== 'admin') {
    echo '<div class="alert alert-danger m-4">You do not have permission to access this section.</div>';
    exit;
}

$page_title = 'Add Staff';

// List of modules for default permission setup
$modules = ['dashboard','products','purchase','sales','service','reports','expense','settings'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = clean($_POST['full_name']);
    $username = clean($_POST['username']);
    $password = $_POST['password'];
    $phone = clean($_POST['phone']);

    if ($full_name === '' || $username === '' || $password === '') {
        setFlash('error', 'Full name, username, and password are required.');
        redirect(BASE_URL . 'staff/add.php');
    }

    // Check username uniqueness
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        setFlash('error', 'This username is already taken.');
        redirect(BASE_URL . 'staff/add.php');
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("INSERT INTO users (full_name, username, password, phone, role, status) VALUES (?,?,?,?,'staff','active')");
        $stmt->execute([$full_name, $username, $password, $phone]);
        $user_id = $pdo->lastInsertId();

        // Create default permission rows (all disabled by default)
        foreach ($modules as $module) {
            $stmt = $pdo->prepare("INSERT INTO user_permissions (user_id, module, can_view, can_add, can_edit, can_delete, can_print) VALUES (?,?,0,0,0,0,0)");
            $stmt->execute([$user_id, $module]);
        }

        $pdo->commit();
        setFlash('success', 'Staff added successfully. Please set their permissions.');
        redirect(BASE_URL . 'staff/permission.php?id=' . $user_id);
    } catch (Exception $e) {
        $pdo->rollBack();
        setFlash('error', 'Something went wrong. Please try again.');
        redirect(BASE_URL . 'staff/add.php');
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">Add Staff</h5>
            <a href="index.php" class="btn btn-soft btn-sm"><i class="bi bi-arrow-left me-1"></i>Back to List</a>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card-panel">
                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Full Name *</label>
                                <input type="text" name="full_name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Phone</label>
                                <input type="text" name="phone" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Username *</label>
                                <input type="text" name="username" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Password *</label>
                                <input type="text" name="password" class="form-control" required>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save Staff</button>
                            <a href="index.php" class="btn btn-soft">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>