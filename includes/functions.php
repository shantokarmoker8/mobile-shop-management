<?php
// ================================================
// Reusable Functions
// ================================================

// Safe HTML escape - handles null values without deprecation warnings (PHP 8.1+)
function h($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// Clean input data
function clean($data) {
    return htmlspecialchars(strip_tags(trim($data ?? '')), ENT_QUOTES, 'UTF-8');
}

// Format money
function money($amount) {
    return number_format((float)$amount, 2);
}

// Format date
function formatDate($date, $format = 'd M Y') {
    if (empty($date)) return '-';
    return date($format, strtotime($date));
}

function formatDateTime($date, $format = 'd M Y, h:i A') {
    if (empty($date)) return '-';
    return date($format, strtotime($date));
}

// Generate unique invoice/job number
function generateNumber($prefix, $pdo, $table, $column) {
    $date = date('Ymd');
    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM {$table} WHERE DATE(created_at) = CURDATE()");
    $stmt->execute();
    $row = $stmt->fetch();
    $count = $row['cnt'] + 1;
    return $prefix . '-' . $date . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
}

// Add cash transaction entry (single source of truth for Current Cash)
function addCashTransaction($pdo, $type, $amount, $direction, $reference_id, $note, $user_id) {
    if ($amount <= 0) return;
    $stmt = $pdo->prepare("INSERT INTO cash_transactions (type, amount, direction, reference_id, note, created_by) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$type, $amount, $direction, $reference_id, $note, $user_id]);
}

// Get current cash balance
function getCurrentCash($pdo) {
    $stmt = $pdo->query("SELECT 
        SUM(CASE WHEN direction = 'in' THEN amount ELSE 0 END) as total_in,
        SUM(CASE WHEN direction = 'out' THEN amount ELSE 0 END) as total_out
        FROM cash_transactions");
    $row = $stmt->fetch();
    $total_in = $row['total_in'] ?? 0;
    $total_out = $row['total_out'] ?? 0;
    return $total_in - $total_out;
}

// Get total stock value (buy price based)
function getStockValue($pdo) {
    $stmt = $pdo->query("SELECT SUM(buy_price * quantity) as val FROM products WHERE status = 'active'");
    $row = $stmt->fetch();
    return $row['val'] ?? 0;
}

// Update product stock (increase or decrease)
function updateStock($pdo, $product_id, $qty, $action = 'decrease') {
    if ($action === 'decrease') {
        $stmt = $pdo->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
    } else {
        $stmt = $pdo->prepare("UPDATE products SET quantity = quantity + ? WHERE id = ?");
    }
    $stmt->execute([$qty, $product_id]);
}

// Get setting value
function getSettings($pdo) {
    static $settings = null;
    if ($settings === null) {
        $stmt = $pdo->query("SELECT * FROM settings LIMIT 1");
        $settings = $stmt->fetch();
    }
    return $settings;
}

// Redirect helper
function redirect($url) {
    header("Location: " . $url);
    exit;
}

// Flash message (SweetAlert2 friendly via session)
function setFlash($type, $message) {
    $_SESSION['flash_type'] = $type;
    $_SESSION['flash_message'] = $message;
}

function getFlash() {
    if (isset($_SESSION['flash_message'])) {
        $flash = [
            'type' => $_SESSION['flash_type'],
            'message' => $_SESSION['flash_message']
        ];
        unset($_SESSION['flash_type'], $_SESSION['flash_message']);
        return $flash;
    }
    return null;
}

// Get logged in user info
function currentUser($pdo) {
    static $user = null;
    if ($user === null && isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
    }
    return $user;
}