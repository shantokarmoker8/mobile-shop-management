<?php
$flash = getFlash();
?>
    </div><!-- /.main-content -->
</div><!-- /.app-wrapper -->

<?php include __DIR__ . '/mobile-nav.php'; ?>

<script src="<?= BASE_URL ?>assets/js/main.js"></script>

<?php if ($flash): ?>
<script>
Swal.fire({
    icon: '<?= $flash['type'] ?>',
    title: '<?= addslashes($flash['message']) ?>',
    timer: 2200,
    showConfirmButton: false,
    toast: true,
    position: 'top-end'
});
</script>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const loader = document.getElementById('page-loader');
    if (loader) loader.style.display = 'none';
});
</script>
</body>
</html>