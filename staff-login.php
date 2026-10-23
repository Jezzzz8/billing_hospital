<?php

require_once __DIR__ . '/config/config.php';

if (!defined('STAFF_LOGIN_ROLE_ID') || !in_array(STAFF_LOGIN_ROLE_ID, [1, 2, 3, 4, 5], true)) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

session_start();
if (!empty($_SESSION['user'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$loginRoleId = STAFF_LOGIN_ROLE_ID;
require __DIR__ . '/views/login.php';
