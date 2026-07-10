<?php
require_once __DIR__ . '/../includes/auth.php';

if ($_SESSION['role'] !== 'admin') {
    echo '<div class="alert alert-danger m-4">You do not have permission to access this section.</div>';
    exit;
}

$page_title = 'Shop Settings';

$settings = getSettings($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shop_name = clean($_POST['shop_name']);
    $owner_name = clean($_POST['owner_name']);
    $phone = clean($_POST['phone']);
    $whatsapp = clean($_POST['whatsapp']);
    $address = clean($_POST['address']);
    $invoice_header = clean($_POST['invoice_header']);
    $invoice_footer = clean($_POST['invoice_footer']);

    if ($settings) {
        $stmt = $pdo->prepare("UPDATE settings SET shop_name=?, owner_name=?, phone=?, whatsapp=?, address=?, invoice_header=?, invoice_footer=? WHERE id=?");
        $stmt->execute([$shop_name, $owner_name, $phone, $whatsapp, $address, $invoice_header, $invoice_footer, $settings['id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO settings (shop_name, owner_name, phone, whatsapp, address, invoice_header, invoice_footer) VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([$shop_name, $owner_name, $phone, $whatsapp, $address, $invoice_header, $invoice_footer]);
    }

    setFlash('success', 'Shop settings updated successfully.');
    redirect(BASE_URL . 'settings/shop.php');
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

        <div class="card-panel">
            <form method="POST">
                <h6 class="fw-bold mb-3">Shop Information</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Shop Name</label>
                        <input type="text" name="shop_name" class="form-control" value="<?= htmlspecialchars($settings['shop_name'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Owner Name</label>
                        <input type="text" name="owner_name" class="form-control" value="<?= htmlspecialchars($settings['owner_name'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Phone</label>
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($settings['phone'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">WhatsApp</label>
                        <input type="text" name="whatsapp" class="form-control" value="<?= htmlspecialchars($settings['whatsapp'] ?? '') ?>">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small fw-semibold">Address</label>
                        <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($settings['address'] ?? '') ?></textarea>
                    </div>
                </div>

                <h6 class="fw-bold mb-3">Invoice Text</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Invoice Header Note</label>
                        <textarea name="invoice_header" class="form-control" rows="2"><?= htmlspecialchars($settings['invoice_header'] ?? '') ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Invoice Footer Note</label>
                        <textarea name="invoice_footer" class="form-control" rows="2"><?= htmlspecialchars($settings['invoice_footer'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save Settings</button>
                </div>
            </form>
        </div>

    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>