<?php

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../controllers/PharmacyStockController.php';

if (empty($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
    exit;
}

if ((int)($_SESSION['user']['role_id'] ?? 0) !== 5) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}

$departmentId = (int)($_SESSION['user']['department_id'] ?? 0);

try {
    $controller = new PharmacyStockController($pdo, $departmentId);
    $operation = $_GET['operation'] ?? '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            throw new RuntimeException('Invalid request body.', 400);
        }
        $operation = (string)($data['operation'] ?? $operation);
    } else {
        $data = [];
    }

    if ($operation === 'stock') {
        echo json_encode(['success' => true, 'data' => $controller->getStockItems()]);
    } elseif ($operation === 'adjust' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        echo json_encode($controller->adjustStock($data));
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Unknown Pharmacy stock operation.']);
    }
} catch (Throwable $e) {
    error_log('[pharmacy-stock.php] ' . $e->getMessage());
    $status = in_array((int)$e->getCode(), [400, 403, 404, 409, 422], true) ? (int)$e->getCode() : 500;
    http_response_code($status);
    echo json_encode([
        'success' => false,
        'message' => $status === 500 ? 'Could not process the Pharmacy stock request.' : $e->getMessage(),
    ]);
}
