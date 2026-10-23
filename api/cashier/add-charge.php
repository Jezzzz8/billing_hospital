<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/../../includes/ws_notify.php';
require_once __DIR__ . '/../../controllers/CashierController.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user']) || (int)$_SESSION['user']['role_id'] !== 1) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}

$controller = new CashierController($pdo);

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing statement id.']);
    exit;
}

try {
    $controller->addCharge($id, $controller->input());
    ws_broadcast('billing.charge.added', ['statement_id' => $id], [1]);
    echo json_encode(['success' => true, 'message' => 'Charge added.']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}