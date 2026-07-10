<?php
// ================================================
// Authentication & Permission Guard
// Include this file at the top of every protected page
// ================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

// Not logged in -> redirect to login
if (!isset($_SESSION['user_id'])) {
    redirect(BASE_URL . 'login/index.php');
}

$user = currentUser($pdo);

// Account deactivated mid-session
if (!$user || $user['status'] !== 'active') {
    session_destroy();
    redirect(BASE_URL . 'login/index.php');
}

// Permission check function - call this inside a page for module-level guard
function checkPermission($pdo, $module, $action = 'view') {
    if ($_SESSION['role'] === 'admin') {
        return true; // Admin has full access
    }

    $column = 'can_' . $action;
    $stmt = $pdo->prepare("SELECT {$column} FROM user_permissions WHERE user_id = ? AND module = ?");
    $stmt->execute([$_SESSION['user_id'], $module]);
    $row = $stmt->fetch();

    if (!$row || !$row[$column]) {
        echo '<div class="alert alert-danger m-4">You do not have permission to access this section.</div>';
        exit;
    }
    return true;
}