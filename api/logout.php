<?php

require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

session_start();
$roleId = (int)($_SESSION['user']['role_id'] ?? 0);
$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $cookie = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $cookie['path'] ?: '/',
        'domain' => $cookie['domain'],
        'secure' => $cookie['secure'],
        'httponly' => $cookie['httponly'],
        'samesite' => $cookie['samesite'] ?? 'Lax',
    ]);
}

session_destroy();

$loginPages = [
    1 => 'admin-login.php',
    2 => 'doctor-login.php',
    3 => 'nurse-login.php',
    4 => 'cashier-login.php',
    5 => 'department-login.php',
];
$redirect = BASE_URL . '/' . ($loginPages[$roleId] ?? 'index.php');

echo json_encode(['success' => true, 'redirect' => $redirect]);