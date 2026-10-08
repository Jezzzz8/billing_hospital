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

$q = trim($_GET['q'] ?? '');
$controller = new PosController($pdo);
echo json_encode(['success' => true, 'patients' => $controller->searchPatients($q)]);