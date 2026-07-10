<?php
require_once __DIR__ . '/../includes/auth.php';
checkPermission($pdo, 'sales', 'add');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$name = clean($_POST['name'] ?? '');
$phone = clean($_POST['phone'] ?? '');

if ($name === '') {
    echo json_encode(['success' => false, 'message' => 'Customer name is required.']);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO customers (name, phone) VALUES (?, ?)");
$stmt->execute([$name, $phone]);
$id = $pdo->lastInsertId();

echo json_encode(['success' => true, 'id' => $id, 'name' => $name, 'phone' => $phone]);