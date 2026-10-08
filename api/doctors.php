<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../controllers/DoctorPortalController.php';
require_once __DIR__ . '/../controllers/DoctorHelper.php';

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
            $stats = $controller->getDashboardStats();
            $patients = $controller->getAssignedPatients();
            $ready = $controller->getReadyForDischarge();
            echo json_encode([
                'success' => true,
                'data' => [
                    'stats' => $stats,
                    'patients' => $patients,
                    'ready_for_discharge' => $ready,
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
            break;

        case 'remove-diagnosis':
            $controller->removeDiagnosis();
            break;

        case 'create-service-request':
            $controller->createServiceRequest();
            break;

        case 'complete-service-request':
            $controller->completeServiceRequest();
            break;

        case 'cancel-service-request':
            $controller->cancelServiceRequest();
            break;

        case 'confirm-discharge':
            $controller->confirmDischarge();
            break;

        case 'create-consultation':
            $controller->createConsultation();
            break;

        case 'update-consultation':
            $controller->updateConsultationStatus();
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Unknown operation: ' . $operation]);
    }
} catch (Throwable $e) {
    error_log('[doctors.php] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred.', 'debug' => $e->getMessage()]);
}