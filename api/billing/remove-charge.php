<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/../../includes/ws_notify.php';
require_once __DIR__ . '/../../controllers/BillingController.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user']) || (int)($_SESSION['user']['role_id'] ?? 0) !== 1) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}
(new BillingController($pdo))->removeCharge();