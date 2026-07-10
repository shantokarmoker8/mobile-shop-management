<?php
require_once __DIR__ . '/../includes/auth.php';

if ($_SESSION['role'] !== 'admin') {
    echo '<div class="alert alert-danger m-4">You do not have permission to access this section.</div>';
    exit;
}

$page_title = 'Print Settings';

$settings = getSettings($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $print_size = clean($_POST['print_size']);
    $auto_print = isset($_POST['auto_print']) ? 1 : 0;

    if (!in_array($print_size, ['58mm','80mm','A5'])) {
        $print_size = '80mm';
    }

    if ($settings) {
        $stmt = $pdo->prepare("UPDATE settings SET print_size=?, auto_print=? WHERE id=?");
        $stmt->execute([$print_size, $auto_print, $settings['id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO settings (print_size, auto_print) VALUES (?,?)");
        $stmt->execute([$print_size, $auto_print]);
    }

    setFlash('success', 'Print settings updated successfully.');
    redirect(BASE_URL . 'settings/print.php');
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-body">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h5 class="fw-bold mb-0">Settings</h5>
            <?php include __DIR__ . '/_settings_nav.php'; ?>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card-panel">
                    <form method="POST">
                        <h6 class="fw-bold mb-3">Print Configuration</h6>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Print Size</label>
                            <select name="print_size" class="form-select">
                                <option value="58mm" <?= ($settings['print_size'] ?? '') === '58mm' ? 'selected' : '' ?>>58mm Thermal</option>
                                <option value="80mm" <?= ($settings['print_size'] ?? '') === '80mm' ? 'selected' : '' ?>>80mm Thermal</option>
                                <option value="A5" <?= ($settings['print_size'] ?? '') === 'A5' ? 'selected' : '' ?>>A5</option>
                            </select>
                        </div>

                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" name="auto_print" id="autoPrint" <?= !empty($settings['auto_print']) ? 'checked' : '' ?>>
                            <label class="form-check-label small fw-semibold" for="autoPrint">
                                Auto Print (Opens print dialog automatically after Invoice)
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save Settings</button>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>