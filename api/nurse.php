<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../controllers/NurseController.php';

if (empty($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
    exit;
}

$roleId = (int)($_SESSION['user']['role_id'] ?? 0);
if ($roleId !== 3 && $roleId !== 1) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}

$controller = new NurseController($pdo);
$operation = $_GET['operation'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    if (is_array($json) && isset($json['operation'])) {
        $operation = $json['operation'];
    }
}

try {
    switch ($operation) {
        case 'dashboard':
            echo json_encode([
                'success' => true,
                'data' => [
                    'stats' => $controller->getDashboardStats(),
                    'active_admissions' => $controller->getActiveAdmissions(),
                    'ready_for_discharge' => $controller->getReadyForDischarge(),
                    'rooms_needing_attention' => $controller->getRoomsNeedingAttention(),
                ],
            ]);
            break;

        case 'options':
            echo json_encode([
                'success' => true,
                'data' => [
                    'genders' => $controller->getGenders(),
                    'admission_statuses' => $controller->getAdmissionStatuses(),
                    'room_types' => $controller->getRoomTypes(),
                    'available_rooms' => $controller->getAvailableRooms(),
                    'doctors' => $controller->getDoctors(),
                ],
            ]);
            break;

        case 'search-patients':
            echo json_encode(['success' => true, 'data' => $controller->searchPatients($_GET['q'] ?? '')]);
            break;

        case 'patient-details':
            $id = (int)($_GET['id'] ?? 0);
            $patient = $controller->getPatientDetails($id);
            if ($patient === null) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Patient not found.']);
                exit;
            }
            echo json_encode(['success' => true, 'data' => $patient]);
            break;

        case 'register-patient':
            $controller->registerPatient();
            break;

        case 'create-admission':
            $controller->createAdmission();
            break;

        case 'admissions':
            echo json_encode(['success' => true, 'data' => $controller->getAdmissions()]);
            break;

        case 'rooms':
            echo json_encode(['success' => true, 'data' => $controller->getAllRooms()]);
            break;

        case 'patient-by-admission':
            $admissionId = (int)($_GET['admission_id'] ?? 0);
            $admission = $controller->getPatientByAdmission($admissionId);
            if ($admission === null) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Admission not found.']);
                exit;
            }
            echo json_encode(['success' => true, 'data' => $admission]);
            break;

        case 'room-assignments':
            $admissionId = (int)($_GET['admission_id'] ?? 0);
            echo json_encode(['success' => true, 'data' => $controller->getRoomAssignments($admissionId ?: null)]);
            break;

        case 'room-transfer-history':
            echo json_encode(['success' => true, 'data' => $controller->getRoomTransferHistory((int)($_GET['admission_id'] ?? 0))]);
            break;

        case 'admission-doctors':
            echo json_encode(['success' => true, 'data' => $controller->getAdmissionDoctors((int)($_GET['admission_id'] ?? 0))]);
            break;

        case 'assign-room':
            $controller->assignRoomApi();
            break;

        case 'transfer-room':
            $controller->transferRoom();
            break;

        case 'assign-doctor':
            $controller->assignDoctorApi();
            break;

        case 'remove-doctor':
            $controller->removeDoctorApi();
            break;

        case 'ready-for-discharge':
            echo json_encode(['success' => true, 'data' => $controller->getReadyForDischarge()]);
            break;

        case 'discharge':
            $controller->dischargePatient();
            break;

        case 'discharge-records':
            echo json_encode(['success' => true, 'data' => $controller->getDischargeRecords()]);
            break;

        case 'pending-fulfillment-receipts':
            echo json_encode(['success' => true, 'data' => $controller->getPendingFulfillmentReceipts()]);
            break;

        case 'receive-fulfillment-item':
            $result = $controller->receiveFulfillmentItem();
            echo json_encode($result);
            break;

        case 'medicine-intakes':
            echo json_encode(['success' => true, 'data' => $controller->getMedicineIntakes()]);
            break;

        case 'record-medicine-dose':
            if ($roleId !== 3) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Only nurses can record administered doses.']);
                break;
            }
            echo json_encode($controller->recordMedicineDose());
            break;

        case 'record-medicine-doses-batch':
            if ($roleId !== 3) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Only nurses can record administered doses.']);
                break;
            }
            echo json_encode($controller->recordMedicineDosesBatch());
            break;

        case 'reconcile-medicine-intake':
            if ($roleId !== 3) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Only nurses can reconcile medicine quantities.']);
                break;
            }
            echo json_encode($controller->reconcileMedicineIntake());
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Unknown operation: ' . $operation]);
    }
} catch (Throwable $e) {
    error_log('[nurse.php] ' . $e->getMessage());
    $status = in_array($e->getCode(), [400, 403, 404, 409, 422], true) ? $e->getCode() : 500;
    http_response_code($status);
    echo json_encode([
        'success' => false,
        'message' => $status === 500 ? 'An error occurred while processing the nurse request.' : $e->getMessage(),
        'debug'   => $e->getMessage(),
    ]);
}