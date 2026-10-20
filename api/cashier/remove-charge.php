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

$id       = (int)($_GET['id'] ?? 0);
$chargeId = (int)($_GET['charge_id'] ?? 0);
if ($id <= 0 || $chargeId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing ids.']);
    exit;
}

try {
    $controller->removeCharge($id, $chargeId);
    ws_broadcast('billing.charge.removed', ['statement_id' => $id, 'charge_id' => $chargeId], [1, 4]);
    echo json_encode(['success' => true, 'message' => 'Charge removed.']);
} catch (Throwable $e) {
    http_response_code($e->getCode() === 409 ? 409 : 500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}