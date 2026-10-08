<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../controllers/DepartmentController.php';

if (empty($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
    exit;
}

$roleId = (int)($_SESSION['user']['role_id'] ?? 0);
if ($roleId !== 5 && $roleId !== 1) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}

$departmentId = (int)($_SESSION['user']['department_id'] ?? 0);
if ($departmentId <= 0 && $roleId === 5) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No department assigned to your account.']);
    exit;
}

$controller = new DepartmentController($pdo, $departmentId);

$operation = $_GET['operation'] ?? $_POST['operation'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    if (is_array($json) && isset($json['operation'])) {
        $operation = $json['operation'];
    }
}

$input = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        $input = $decoded;
    } else {
        $input = $_POST;
    }
}

switch ($operation) {
    case 'queue':
        echo json_encode(['success' => true, 'data' => $controller->getQueue()]);
        break;

    case 'request-details':
        $requestId = (int)($_GET['request_id'] ?? $input['request_id'] ?? 0);
        $details = $controller->getRequestDetails($requestId);
        if ($details === null) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Request not found.']);
            exit;
        }
        echo json_encode(['success' => true, 'data' => $details]);
        break;

    case 'dashboard':
        echo json_encode(['success' => true, 'data' => $controller->getDashboardStats()]);
        break;

    case 'accept':
        $requestId = (int)($input['request_id'] ?? 0);
        $result = $controller->acceptRequest($requestId);
        echo json_encode($result);
        break;

    case 'process':
        $requestId = (int)($input['request_id'] ?? 0);
        $result = $controller->processRequest($requestId);
        echo json_encode($result);
        break;

    case 'fulfill':
        $requestId = (int)($input['request_id'] ?? 0);
        $items = $input['items'] ?? [];
        $notes = $input['notes'] ?? null;
        $result = $controller->recordFulfillment($requestId, $items, $notes);
        echo json_encode($result);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Unknown operation: ' . $operation]);
}