<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/../../includes/ws_notify.php';
require_once __DIR__ . '/../../controllers/CashierController.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user']) || !in_array((int)$_SESSION['user']['role_id'], [1, 4], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}

$controller = new CashierController($pdo);
$controller->createStatement();
ws_broadcast('billing.statement.created', [], [1, 4]);