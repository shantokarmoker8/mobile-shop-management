<?php
// ================================================
// App Configuration
// ================================================

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$script_dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));

$folders = ['login', 'dashboard', 'products', 'purchase', 'suppliers', 'sales', 'customers', 'service', 'cash', 'expense', 'reports', 'staff', 'settings', 'account'];
$path = $script_dir;
foreach ($folders as $f) {
    $path = preg_replace('#/' . $f . '$#', '', $path);
}

define('BASE_URL', $protocol . $host . $path . '/');
define('APP_NAME', 'Mobile Shop & Service Manager');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

date_default_timezone_set('Asia/Dhaka');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../includes/functions.php';