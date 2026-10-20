<?php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../includes/ws_notify.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'data' => 0, 'message' => 'Not authenticated.']);
    exit;
}

$roleId = (int)($_SESSION['user']['role_id'] ?? 0);
if ($roleId !== 1) {
    http_response_code(403);
    echo json_encode(['success' => false, 'data' => 0, 'message' => 'Access denied.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$rawBody = (string)file_get_contents('php://input');
$jsonBody = json_decode($rawBody, true);
if (!is_array($jsonBody)) {
    $jsonBody = [];
}

foreach ($jsonBody as $key => $value) {
    if (!array_key_exists($key, $_POST)) {
        $_POST[$key] = $value;
    }
}
$_POST['json'] = $rawBody;

$resource = (string)($_GET['resource'] ?? $_POST['resource'] ?? $jsonBody['resource'] ?? '');
$operation = (string)($_GET['operation'] ?? $_POST['operation'] ?? $jsonBody['operation'] ?? '');
$id = (int)($_GET['id'] ?? $_POST['id'] ?? $jsonBody['id'] ?? 0);

$resourceMap = [
    'admission-statuses' => ['controller' => 'AdmissionStatusController', 'supportsArchive' => false],
    'billing-statuses'   => ['controller' => 'BillingStatusController', 'supportsArchive' => false],
    'billing'            => ['controller' => 'BillingController', 'supportsArchive' => false],
    'buildings'          => ['controller' => 'BuildingController', 'supportsArchive' => false],
    'charge-categories'  => ['controller' => 'ChargeCategoryController', 'supportsArchive' => false],
    'charge-items'       => ['controller' => 'ChargeItemController', 'supportsArchive' => true],
    'departments'        => ['controller' => 'DepartmentAdminController', 'supportsArchive' => true],
    'diagnoses'          => ['controller' => 'DiagnosisController', 'supportsArchive' => true],
    'doctors'            => ['controller' => 'DoctorController', 'supportsArchive' => true],
    'floor-levels'       => ['controller' => 'FloorLevelController', 'supportsArchive' => false],
    'genders'            => ['controller' => 'GenderController', 'supportsArchive' => false],
    'payment-types'      => ['controller' => 'PaymentTypeController', 'supportsArchive' => false],
    'roles'              => ['controller' => 'RoleController', 'supportsArchive' => false],
    'room-statuses'      => ['controller' => 'RoomStatusController', 'supportsArchive' => false],
    'room-types'         => ['controller' => 'RoomTypeController', 'supportsArchive' => true],
    'rooms'              => ['controller' => 'RoomController', 'supportsArchive' => true],
    'specializations'    => ['controller' => 'SpecializationController', 'supportsArchive' => false],
    'users'              => ['controller' => 'UserController', 'supportsArchive' => true],
];

if (!isset($resourceMap[$resource])) {
    http_response_code(404);
    echo json_encode(['success' => false, 'data' => 0, 'message' => 'Unknown admin resource: ' . $resource]);
    exit;
}

if ($id > 0) {
    $_GET['id'] = $id;
}

$controllerName = $resourceMap[$resource]['controller'];
$controllerFile = __DIR__ . '/../controllers/' . $controllerName . '.php';
if (file_exists($controllerFile)) {
    require_once $controllerFile;
}

if (!class_exists($controllerName)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'data' => 0, 'message' => 'Controller not available.']);
    exit;
}

$controller = new $controllerName($pdo);

$methodMap = [
    'create' => 'create',
    'update' => 'update',
    'delete' => 'delete',
    'toggle-active' => 'toggleActive',
    'change-password' => 'changePassword',
    'add-charge' => 'addCharge',
    'remove-charge' => 'removeCharge',
    'add-payment' => 'addPayment',
    'remove-payment' => 'removePayment',
];

if ($resource === 'billing' && $operation === 'details') {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Use GET for this operation.']);
        exit;
    }

    try {
        $details = $controller->getDetails($id);
        if ($details === null) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Statement not found.']);
            exit;
        }

        echo json_encode(['success' => true, 'data' => $details]);
    } catch (Throwable $e) {
        error_log('[api/admin.php] billing.details: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Could not load statement details.']);
    }
    exit;
}

if (!isset($methodMap[$operation])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'data' => 0, 'message' => 'Unknown operation: ' . $operation]);
    exit;
}

if ($operation === 'toggle-active' && !$resourceMap[$resource]['supportsArchive']) {
    http_response_code(400);
    echo json_encode(['success' => false, 'data' => 0, 'message' => 'This resource does not support archiving.']);
    exit;
}

$method = $methodMap[$operation];
if (!method_exists($controller, $method)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'data' => 0, 'message' => 'Operation not implemented: ' . $operation]);
    exit;
}

try {
    $controller->$method();

    $broadcastRoles = match ($resource) {
        'users'              => [1],
        'doctors'            => [1, 2],
        'charge-items',
        'charge-categories',
        'payment-types',
        'billing-statuses',
        'billing'            => [1, 4],
        'rooms',
        'room-types',
        'room-statuses',
        'buildings',
        'floor-levels',
        'departments'        => [1, 3],
        'genders',
        'roles',
        'specializations',
        'diagnoses',
        'admission-statuses' => [1],
        default              => [1],
    };

    ws_broadcast('master.updated', [
        'resource'  => $resource,
        'operation' => $operation,
        'id'        => $id,
    ], $broadcastRoles);
} catch (Throwable $e) {
    error_log('[api/admin.php] ' . $resource . '.' . $operation . ': ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'data' => 0, 'message' => 'An error occurred.']);
}