<?php
require_once __DIR__ . '/../includes/auth.php';

$page_title = 'Developer Information';

// Static developer information as provided
$developer = [
    'photo' => 'https://avatars.githubusercontent.com/u/126598101?v=4',
    'name' => 'Shanto Karmoker',
    'position' => 'Web Developer',
    'email' => 'shantokarmoker8@gmail.com',
    'whatsapp' => '+8801798046899',
    'facebook' => 'https://facebook.com/shantokarmoker8',
    'instagram' => 'https://instagram.com/shantokarmoker8',
    'linkedin' => 'https://linkedin.com/in/shantokarmoker8',
    'twitter' => 'https://x.com/shantokarmoker8',
    'github' => 'https://github.com/shantokarmoker8',
];

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-body">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h5 class="fw-bold mb-0">Settings</h5>
            <?php if ($_SESSION['role'] === 'admin'): ?>
            <?php include __DIR__ . '/_settings_nav.php'; ?>
            <?php endif; ?>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card-panel text-center">
                    <img src="<?= htmlspecialchars($developer['photo']) ?>" alt="<?= htmlspecialchars($developer['name']) ?>" class="dev-photo mb-3">

                    <h5 class="fw-bold mb-1"><?= htmlspecialchars($developer['name']) ?></h5>
                    <p class="text-muted mb-3"><?= htmlspecialchars($developer['position']) ?></p>

                    <div class="d-flex justify-content-center flex-wrap gap-2 mb-4">
                        <a href="mailto:<?= htmlspecialchars($developer['email']) ?>" class="btn btn-soft btn-sm">
                            <i class="bi bi-envelope me-1"></i><?= htmlspecialchars($developer['email']) ?>
                        </a>
                        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $developer['whatsapp']) ?>" target="_blank" rel="noopener" class="btn btn-soft btn-sm">
                            <i class="bi bi-whatsapp me-1"></i><?= htmlspecialchars($developer['whatsapp']) ?>
                        </a>
                    </div>

                    <p class="small text-muted fw-semibold mb-2">Connect on Social Media</p>
                    <div class="dev-social-buttons mb-3">
                        <a href="<?= htmlspecialchars($developer['facebook']) ?>" target="_blank" rel="noopener" class="dev-social-btn" style="background:#1877F2;" title="Facebook">
                            <i class="bi bi-facebook"></i>
                        </a>
                        <a href="<?= htmlspecialchars($developer['instagram']) ?>" target="_blank" rel="noopener" class="dev-social-btn" style="background:#E1306C;" title="Instagram">
                            <i class="bi bi-instagram"></i>
                        </a>
                        <a href="<?= htmlspecialchars($developer['linkedin']) ?>" target="_blank" rel="noopener" class="dev-social-btn" style="background:#0A66C2;" title="LinkedIn">
                            <i class="bi bi-linkedin"></i>
                        </a>
                        <a href="<?= htmlspecialchars($developer['twitter']) ?>" target="_blank" rel="noopener" class="dev-social-btn" style="background:#000000;" title="X (Twitter)">
                            <i class="bi bi-twitter-x"></i>
                        </a>
                        <a href="<?= htmlspecialchars($developer['github']) ?>" target="_blank" rel="noopener" class="dev-social-btn" style="background:#333333;" title="GitHub">
                            <i class="bi bi-github"></i>
                        </a>
                    </div>

                    <hr>

                    <p class="small text-muted mb-0">
                        <?= htmlspecialchars(APP_NAME) ?> — Version <?= htmlspecialchars(getSettings($pdo)['version'] ?? '1.0.0') ?>
                    </p>
                </div>
            </div>
        </div>

    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>