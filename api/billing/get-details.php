<?php
require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/../../controllers/BillingController.php';

header('Content-Type: application/json; charset=utf-8');
session_start();

if (empty($_SESSION['user']) || (int)$_SESSION['user']['role_id'] !== 1) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing statement id.']);
    exit;
}

$details = (new BillingController($pdo))->getDetails($id);
if (!$details) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Statement not found.']);
    exit;
}

echo json_encode(['success' => true, 'data' => $details]);