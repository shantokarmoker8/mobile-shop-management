<?php
require_once __DIR__ . '/../includes/auth.php';

$page_title = 'My Profile';

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$me = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = clean($_POST['full_name']);
    $phone = clean($_POST['phone']);
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($full_name === '') {
        setFlash('error', 'Full name is required.');
        redirect(BASE_URL . 'account/profile.php');
    }

    if ($new_password !== '' || $confirm_password !== '') {
        if ($current_password === '' || $current_password !== $me['password']) {
            setFlash('error', 'Current password is incorrect.');
            redirect(BASE_URL . 'account/profile.php');
        }
        if ($new_password !== $confirm_password) {
            setFlash('error', 'New password and confirmation do not match.');
            redirect(BASE_URL . 'account/profile.php');
        }
        if (strlen($new_password) < 4) {
            setFlash('error', 'New password must be at least 4 characters.');
            redirect(BASE_URL . 'account/profile.php');
        }

        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ?, password = ? WHERE id = ?");
        $stmt->execute([$full_name, $phone, $new_password, $_SESSION['user_id']]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ? WHERE id = ?");
        $stmt->execute([$full_name, $phone, $_SESSION['user_id']]);
    }

    $_SESSION['full_name'] = $full_name;

    setFlash('success', 'Profile updated successfully.');
    redirect(BASE_URL . 'account/profile.php');
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">My Profile</h5>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card-panel">
                    <form method="POST">
                        <h6 class="fw-bold mb-3">Account Information</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Full Name *</label>
                                <input type="text" name="full_name" class="form-control" value="<?= h($me['full_name']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Phone</label>
                                <input type="text" name="phone" class="form-control" value="<?= h($me['phone']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Username</label>
                                <input type="text" class="form-control" value="<?= h($me['username']) ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Role</label>
                                <input type="text" class="form-control" value="<?= h(ucfirst($me['role'])) ?>" readonly>
                            </div>
                        </div>

                        <h6 class="fw-bold mb-3">Change Password</h6>
                        <p class="small text-muted mb-3">Leave these fields empty if you don't want to change your password.</p>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label small fw-semibold">Current Password</label>
                                <div class="input-group">
                                    <input type="password" name="current_password" id="currentPassword" class="form-control">
                                    <button type="button" class="input-group-text bg-white" onclick="togglePassword('currentPassword', this)"><i class="bi bi-eye"></i></button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">New Password</label>
                                <div class="input-group">
                                    <input type="password" name="new_password" id="newPassword" class="form-control">
                                    <button type="button" class="input-group-text bg-white" onclick="togglePassword('newPassword', this)"><i class="bi bi-eye"></i></button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Confirm New Password</label>
                                <div class="input-group">
                                    <input type="password" name="confirm_password" id="confirmPassword" class="form-control">
                                    <button type="button" class="input-group-text bg-white" onclick="togglePassword('confirmPassword', this)"><i class="bi bi-eye"></i></button>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>