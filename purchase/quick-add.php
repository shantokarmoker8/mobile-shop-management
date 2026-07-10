<?php
require_once __DIR__ . '/../includes/auth.php';
checkPermission($pdo, 'purchase', 'add');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$name = clean($_POST['name'] ?? '');
$brand = clean($_POST['brand'] ?? '');
$category_id = (int)($_POST['category_id'] ?? 0);

if ($name === '' || $category_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Product name and category are required.']);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO products (name, brand, category_id, buy_price, sell_price, quantity, low_stock_qty) VALUES (?,?,?,0,0,0,5)");
$stmt->execute([$name, $brand, $category_id]);
$id = $pdo->lastInsertId();

$full_name = $name . ($brand ? ' (' . $brand . ')' : '');

echo json_encode(['success' => true, 'id' => $id, 'name' => $full_name]);