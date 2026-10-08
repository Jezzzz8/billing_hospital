<?php


require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/../../controllers/PosController.php';

header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user']) || !in_array((int)$_SESSION['user']['role_id'], [1, 4], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Missing sale id.']);
    exit;
}

$controller = new PosController($pdo);
$sale = $controller->getSaleDetails($id);
if (!$sale) {
    echo json_encode(['success' => false, 'message' => 'Sale not found.']);
    exit;
}
echo json_encode(['success' => true, 'sale' => $sale]);