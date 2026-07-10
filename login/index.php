<?php
require_once __DIR__ . '/../config/config.php';

if (isset($_SESSION['user_id'])) {
    redirect(BASE_URL . 'dashboard/index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = clean($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && $password === $user['password']) {
            if ($user['status'] !== 'active') {
                $error = 'Your account has been deactivated. Contact admin.';
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                redirect(BASE_URL . 'dashboard/index.php');
            }
        } else {
            $error = 'Invalid username or password.';
        }
    }
}

$settings = getSettings($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - <?= h($settings['shop_name'] ?? APP_NAME) ?></title>
<link rel="icon" href="<?= BASE_URL ?>assets/fevicon.png" type="image/png">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body class="login-body">

<div class="login-wrapper d-flex align-items-center justify-content-center">
    <div class="login-card shadow">
        <div class="text-center mb-4">
            <img src="<?= BASE_URL ?>assets/logo.png" alt="Logo" class="login-logo mb-2">
            <h5 class="fw-bold text-primary mb-0"><?= h($settings['shop_name'] ?? APP_NAME) ?></h5>
            <p class="text-muted small">Sign in to continue</p>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-danger py-2 small"><i class="bi bi-exclamation-circle me-1"></i><?= h($error) ?></div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <div class="mb-3">
                <label class="form-label small fw-semibold">Username</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-person"></i></span>
                    <input type="text" name="username" class="form-control" placeholder="Enter username" required autofocus>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" id="loginPassword" class="form-control" placeholder="Enter password" required>
                    <button type="button" class="input-group-text bg-white toggle-password-btn" onclick="togglePassword('loginPassword', this)">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 mt-2">
                <i class="bi bi-box-arrow-in-right me-1"></i> Login
            </button>
        </form>
    </div>
</div>

<img id="page-loader" src="<?= BASE_URL ?>assets/loading.gif" class="page-loader">

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('page-loader').style.display = 'none';

function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}
</script>
</body>
</html>