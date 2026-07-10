<?php
require_once __DIR__ . '/config/config.php';

if (isset($_SESSION['user_id'])) {
    redirect(BASE_URL . 'dashboard/index.php');
} else {
    redirect(BASE_URL . 'login/index.php');
}