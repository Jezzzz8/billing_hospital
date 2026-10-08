<?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/../../controllers/NurseController.php';

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user']) || !in_array((int)$_SESSION['user']['role_id'], [1, 3], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}

$q = trim($_GET['q'] ?? '');

if (strlen($q) < 2) {
    echo json_encode(['success' => true, 'patients' => []]);
    exit;
}

try {
    $controller = new NurseController($pdo);
    $patients = $controller->searchPatients($q);
    echo json_encode(['success' => true, 'patients' => $patients]);
} catch (Throwable $e) {
    error_log('[api/nurses/get-patient] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}