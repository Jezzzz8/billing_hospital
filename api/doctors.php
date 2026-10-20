<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../controllers/DoctorPortalController.php';
require_once __DIR__ . '/../controllers/DoctorHelper.php';
require_once __DIR__ . '/../includes/ws_notify.php';

if (empty($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
    exit;
}

$roleId = (int)($_SESSION['user']['role_id'] ?? 0);
if ($roleId !== 2) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}

$doctorId = DoctorHelper::getCurrentDoctorId($pdo);
if ($doctorId === null) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Doctor profile not found.']);
    exit;
}

$controller = new DoctorPortalController($pdo, $doctorId);

$operation = $_GET['operation'] ?? '';

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

$admissionId = (int)($_GET['admission_id'] ?? $input['admission_id'] ?? 0);

try {
    switch ($operation) {
        case 'dashboard':
            echo json_encode([
                'success' => true,
                'data' => [
                    'stats' => $controller->getDashboardStats(),
                    'patients' => $controller->getAssignedPatients(),
                    'ready_for_discharge' => $controller->getReadyForDischarge(),
                ],
            ]);
            break;

        case 'patients':
            echo json_encode([
                'success' => true,
                'data' => [
                    'assigned' => $controller->getAssignedPatients(),
                    'history' => $controller->getAllMyPatients(),
                ],
            ]);
            break;

        case 'consultations':
            echo json_encode([
                'success' => true,
                'data' => [
                    'consultations' => $controller->getConsultations(),
                    'patients' => $controller->getAllMyPatients(),
                ],
            ]);
            break;

        case 'diagnosis-options':
            echo json_encode([
                'success' => true,
                'data' => [
                    'diagnoses' => $controller->getActiveDiagnoses(),
                    'assigned' => $controller->getAssignedPatients(),
                ],
            ]);
            break;

        case 'service-options':
            echo json_encode([
                'success' => true,
                'data' => [
                    'departments' => $controller->getDepartmentsWithCategories(),
                    'assigned' => $controller->getAssignedPatients(),
                ],
            ]);
            break;

        case 'department-items':
            $departmentId = (int)($_GET['department_id'] ?? 0);
            echo json_encode([
                'success' => true,
                'data' => $controller->getChargeItemsForDepartment($departmentId),
            ]);
            break;

        case 'admission-details':
            $details = $controller->getAdmissionDetails($admissionId);
            if ($details === null) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Admission not found.']);
                exit;
            }
            echo json_encode(['success' => true, 'data' => $details]);
            break;

        case 'discharge-ready':
            echo json_encode(['success' => true, 'data' => $controller->getReadyForDischarge()]);
            break;

        case 'save-diagnosis':
            $controller->saveDiagnosis();
            ws_broadcast('diagnosis.added', [], [1, 2]);
            break;

        case 'remove-diagnosis':
            $controller->removeDiagnosis();
            ws_broadcast('diagnosis.removed', [], [1, 2]);
            break;

        case 'create-service-request':
            $controller->createServiceRequest();
            ws_broadcast('service.request.created', [], [1, 2, 5]);
            break;

        case 'request-medicine-return':
            $result = $controller->requestMedicineReturn($input);
            ws_broadcast('service.request.created', [
                'request_id' => (int)$result['request_id'],
                'admission_id' => (int)($input['admission_id'] ?? 0),
                'department_id' => (int)$result['department_id'],
            ], [1, 2, 5], (int)$result['department_id']);
            echo json_encode($result);
            break;

        case 'complete-service-request':
            $controller->completeServiceRequest();
            ws_broadcast('service.request.completed', [], [1, 2, 5]);
            break;

        case 'cancel-service-request':
            $controller->cancelServiceRequest();
            ws_broadcast('service.request.cancelled', [], [1, 2, 5]);
            break;

        case 'confirm-discharge':
            $controller->confirmDischarge();
            ws_broadcast('discharge.confirmed', [], [1, 2, 3, 4]);
            break;

        case 'create-consultation':
            $controller->createConsultation();
            ws_broadcast('consultation.created', [], [1, 2]);
            break;

        case 'update-consultation':
            $controller->updateConsultationStatus();
            ws_broadcast('consultation.updated', [], [1, 2]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Unknown operation: ' . $operation]);
    }
} catch (Throwable $e) {
    error_log('[doctors.php] ' . $e->getMessage());
    $status = in_array($e->getCode(), [400, 403, 404, 409, 422], true) ? $e->getCode() : 500;
    http_response_code($status);
    echo json_encode([
        'success' => false,
        'message' => $status === 500 ? 'An error occurred.' : $e->getMessage(),
        'debug' => $e->getMessage(),
    ]);
}