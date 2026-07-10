<?php
$current_folder_mnav = basename(dirname($_SERVER['SCRIPT_NAME']));
function mnavActive($folder, $target) {
    return $folder === $target ? 'active' : '';
}
?>
<nav class="mobile-bottom-nav">
    <a href="<?= BASE_URL ?>dashboard/index.php" class="mobile-nav-item <?= mnavActive($current_folder_mnav, 'dashboard') ?>">
        <i class="bi bi-speedometer2"></i>
        <span>Home</span>
    </a>
    <a href="<?= BASE_URL ?>purchase/index.php" class="mobile-nav-item <?= mnavActive($current_folder_mnav, 'purchase') ?>">
        <i class="bi bi-cart-plus"></i>
        <span>Purchase</span>
    </a>
    <a href="<?= BASE_URL ?>sales/pos.php" class="mobile-nav-item fab">
        <span class="fab-circle"><i class="bi bi-cash-coin"></i></span>
        <span>Sale</span>
    </a>
    <a href="<?= BASE_URL ?>service/index.php" class="mobile-nav-item <?= mnavActive($current_folder_mnav, 'service') ?>">
        <i class="bi bi-tools"></i>
        <span>Service</span>
    </a>
    <button type="button" class="mobile-nav-item" id="mobileMoreBtn">
        <i class="bi bi-list"></i>
        <span>Menu</span>
    </button>
</nav>