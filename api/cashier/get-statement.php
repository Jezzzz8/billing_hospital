<?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/../../controllers/CashierController.php';
require_once __DIR__ . '/../../controllers/ChargeSyncService.php';

header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user']) || !in_array((int)$_SESSION['user']['role_id'], [1, 4], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Missing statement id.']);
    exit;
}

try {
    (new ChargeSyncService($pdo))->syncStatement($id);
} catch (Throwable $e) {
    error_log('[get-statement] sync failed: ' . $e->getMessage());
}

$controller = new CashierController($pdo);
$statement = $controller->getStatementDetails($id);
if (!$statement) {
    echo json_encode(['success' => false, 'message' => 'Statement not found.']);
    exit;
}

echo json_encode(['success' => true, 'statement' => $statement]);