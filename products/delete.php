<?php
require_once __DIR__ . '/../includes/auth.php';
checkPermission($pdo, 'products', 'delete');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . 'products/index.php');
}

$id = (int)($_POST['id'] ?? 0);

if ($id > 0) {
    try {
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount() > 0) {
            setFlash('success', 'Product deleted successfully.');
        } else {
            setFlash('error', 'Product not found or already deleted.');
        }
    } catch (PDOException $e) {
        // Product is linked to purchase/sale/service records (foreign key restriction).
        // Automatically deactivate it instead of a hard delete, so history stays intact.
        $stmt = $pdo->prepare("UPDATE products SET status = 'inactive' WHERE id = ?");
        $stmt->execute([$id]);
        setFlash('success', 'This product has purchase/sale/service history, so it was deactivated instead of deleted. It will no longer appear in new transactions.');
    }
} else {
    setFlash('error', 'Invalid product.');
}

redirect(BASE_URL . 'products/index.php');