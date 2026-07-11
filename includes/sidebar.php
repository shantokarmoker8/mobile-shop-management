<?php
$current_page = basename($_SERVER['SCRIPT_NAME']);
$current_folder = basename(dirname($_SERVER['SCRIPT_NAME']));

function navActive($folder, $target) {
    return $folder === $target ? 'active' : '';
}

$beneficiary_folders = ['customers', 'suppliers', 'staff'];
$beneficiary_open = in_array($current_folder, $beneficiary_folders);
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <img src="<?= BASE_URL ?>assets/logo.png" alt="Logo">
        <span class="brand-text"><?= h($settings['shop_name'] ?? APP_NAME) ?></span>
    </div>

    <nav class="sidebar-menu">
        <a href="<?= BASE_URL ?>dashboard/index.php" class="menu-item <?= navActive($current_folder, 'dashboard') ?>">
            <i class="bi bi-speedometer2"></i><span>Dashboard</span>
        </a>

        <a href="<?= BASE_URL ?>products/index.php" class="menu-item <?= navActive($current_folder, 'products') ?>">
            <i class="bi bi-box-seam"></i><span>Products</span>
        </a>

        <a href="<?= BASE_URL ?>purchase/index.php" class="menu-item <?= navActive($current_folder, 'purchase') ?>">
            <i class="bi bi-cart-plus"></i><span>Purchase</span>
        </a>

        <a href="<?= BASE_URL ?>sales/pos.php" class="menu-item <?= navActive($current_folder, 'sales') ?>">
            <i class="bi bi-cash-coin"></i><span>Sales / POS</span>
        </a>

        <a href="<?= BASE_URL ?>service/index.php" class="menu-item <?= navActive($current_folder, 'service') ?>">
            <i class="bi bi-tools"></i><span>Service Jobs</span>
        </a>

        <button type="button" class="menu-item <?= $beneficiary_open ? 'active' : '' ?>" data-bs-toggle="collapse" data-bs-target="#beneficiaryMenu" aria-expanded="<?= $beneficiary_open ? 'true' : 'false' ?>">
            <i class="bi bi-people"></i><span>Beneficiary</span>
            <i class="bi bi-chevron-down submenu-arrow"></i>
        </button>
        <div class="collapse <?= $beneficiary_open ? 'show' : '' ?>" id="beneficiaryMenu">
            <div class="submenu">
                <a href="<?= BASE_URL ?>customers/index.php" class="submenu-item <?= ($current_folder === 'customers') ? 'active' : '' ?>">
                    <i class="bi bi-person-heart"></i><span>Customers</span>
                </a>
                <a href="<?= BASE_URL ?>suppliers/index.php" class="submenu-item <?= ($current_folder === 'suppliers') ? 'active' : '' ?>">
                    <i class="bi bi-truck"></i><span>Suppliers</span>
                </a>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                <a href="<?= BASE_URL ?>staff/index.php" class="submenu-item <?= ($current_folder === 'staff') ? 'active' : '' ?>">
                    <i class="bi bi-person-badge"></i><span>Staff</span>
                </a>
                <?php endif; ?>
            </div>
        </div>

        <a href="<?= BASE_URL ?>cash/index.php" class="menu-item <?= navActive($current_folder, 'cash') ?>">
            <i class="bi bi-wallet2"></i><span>Cash Management</span>
        </a>

        <a href="<?= BASE_URL ?>expense/index.php" class="menu-item <?= navActive($current_folder, 'expense') ?>">
            <i class="bi bi-receipt"></i><span>Expense</span>
        </a>

        <a href="<?= BASE_URL ?>reports/sales.php" class="menu-item <?= navActive($current_folder, 'reports') ?>">
            <i class="bi bi-bar-chart-line"></i><span>Reports</span>
        </a>

        <?php if ($_SESSION['role'] === 'admin'): ?>
        <a href="<?= BASE_URL ?>settings/shop.php" class="menu-item <?= navActive($current_folder, 'settings') ?>">
            <i class="bi bi-gear"></i><span>Settings</span>
        </a>
        <?php endif; ?>

        <a href="javascript:void(0)" class="menu-item text-danger" onclick="confirmLogout('<?= BASE_URL ?>logout.php')">
            <i class="bi bi-box-arrow-right"></i><span>Logout</span>
        </a>
    </nav>
</aside>

<div class="sidebar-overlay" id="sidebarOverlay"></div>