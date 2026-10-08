<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

function require_role(int ...$allowedRoles): void
{
    if (!in_array((int)$_SESSION['user']['role_id'], $allowedRoles, true)) {
        http_response_code(403);
        echo 'Access denied.';
        exit;
    }
}

$currentUser = $_SESSION['user'];
$fullName    = trim($currentUser['first_name'] . ' ' . $currentUser['last_name']);
$roleId      = (int)$currentUser['role_id'];