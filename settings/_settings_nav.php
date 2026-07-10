<?php
$current_settings = basename($_SERVER['SCRIPT_NAME']);
?>
<div class="d-flex gap-1 flex-wrap">
    <a href="shop.php" class="btn btn-sm <?= $current_settings === 'shop.php' ? 'btn-primary' : 'btn-soft' ?>">Shop</a>
    <a href="print.php" class="btn btn-sm <?= $current_settings === 'print.php' ? 'btn-primary' : 'btn-soft' ?>">Print</a>
    <?php if ($_SESSION['role'] === 'admin'): ?>
    <a href="backup.php" class="btn btn-sm <?= $current_settings === 'backup.php' ? 'btn-primary' : 'btn-soft' ?>">Backup</a>
    <a href="import.php" class="btn btn-sm <?= $current_settings === 'import.php' ? 'btn-primary' : 'btn-soft' ?>">Import</a>
    <a href="delete-data.php" class="btn btn-sm <?= $current_settings === 'delete-data.php' ? 'btn-primary' : 'btn-soft' ?>">Delete All Data</a>
    <?php endif; ?>
    <a href="developer.php" class="btn btn-sm <?= $current_settings === 'developer.php' ? 'btn-primary' : 'btn-soft' ?>">Developer Info</a>
</div>