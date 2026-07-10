<header class="topbar">
    <div class="d-flex align-items-center">
        <button class="btn-toggle-sidebar" id="sidebarToggle">
            <i class="bi bi-list"></i>
        </button>
        <h6 class="topbar-title mb-0"><?= htmlspecialchars($page_title ?? '') ?></h6>
    </div>

    <div class="d-flex align-items-center gap-3">
        <div class="topbar-cash d-none d-md-flex">
            <i class="bi bi-wallet2"></i>
            <span>Cash: <?= money(getCurrentCash($pdo)) ?></span>
        </div>

        <div class="dropdown">
            <button class="btn-user-dropdown" data-bs-toggle="dropdown">
                <i class="bi bi-person-circle"></i>
                <span class="d-none d-md-inline"><?= htmlspecialchars($_SESSION['full_name']) ?></span>
                <i class="bi bi-chevron-down small"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><span class="dropdown-item-text small text-muted"><?= ucfirst($_SESSION['role']) ?></span></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="<?= BASE_URL ?>account/profile.php"><i class="bi bi-person-gear me-2"></i>My Profile</a></li>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                <li><a class="dropdown-item" href="<?= BASE_URL ?>settings/shop.php"><i class="bi bi-gear me-2"></i>Settings</a></li>
                <?php endif; ?>
                <li><a class="dropdown-item text-danger" href="javascript:void(0)" onclick="confirmLogout('<?= BASE_URL ?>logout.php')"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
            </ul>
        </div>
    </div>
</header>