<?php
require_once __DIR__ . '/config/config.php';

$_SESSION = [];
session_unset();
session_destroy();

redirect(BASE_URL . 'login/index.php');