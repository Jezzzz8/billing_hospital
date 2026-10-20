<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/api_auth.php';
require_once __DIR__ . '/../includes/ws_notify.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_api_role(1);

require_once __DIR__ . '/../controllers/PatientController.php';

class Patient extends PatientController
{
    public function __construct()
    {
        require __DIR__ . '/../config/connection.php';
        parent::__construct($pdo);
    }

    public function getOptions(): array
    {
        return [
            'genders' => $this->getGenders(),
            'admission_statuses' => $this->getAdmissionStatuses(),
            'rooms' => $this->getAvailableRooms(),
            'doctors' => $this->getDoctors(),
            'diagnoses' => $this->getDiagnoses(),
        ];
    }
}

try {
    $patient = new Patient();
    $operation = $_GET['operation'] ?? $_POST['operation'] ?? '';
    $json = $_SERVER['REQUEST_METHOD'] === 'GET'
        ? ($_GET['json'] ?? '')
        : ($_POST['json'] ?? '');
    $parameters = $json === '' ? [] : json_decode((string)$json, true);

    if ($json !== '' && !is_array($parameters)) {
        api_json(400, ['success' => false, 'message' => 'Invalid JSON parameters.']);
    }

    $parameters = is_array($parameters) ? $parameters : [];

    switch ($operation) {
        case 'list':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                api_json(405, ['success' => false, 'message' => 'Use GET for this operation.']);
            }
            api_json(200, ['success' => true, 'data' => $patient->getAll()]);

        case 'options':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                api_json(405, ['success' => false, 'message' => 'Use GET for this operation.']);
            }
            api_json(200, ['success' => true, 'data' => $patient->getOptions()]);

        case 'details':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                api_json(405, ['success' => false, 'message' => 'Use GET for this operation.']);
            }
            $id = (int)($_GET['id'] ?? $parameters['patient_id'] ?? 0);
            if ($id <= 0) {
                api_json(400, ['success' => false, 'message' => 'Missing patient id.']);
            }

            $details = $patient->getDetails($id);
            if (!$details) {
                api_json(404, ['success' => false, 'message' => 'Patient not found.']);
            }

            api_json(200, ['success' => true, 'data' => $details]);

        case 'create':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                api_json(405, ['success' => false, 'message' => 'Use POST for this operation.']);
            }
            $_POST['json'] = json_encode($parameters);
            $patient->create();
            break;

        case 'update':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                api_json(405, ['success' => false, 'message' => 'Use POST for this operation.']);
            }
            $id = (int)($parameters['patient_id'] ?? $_GET['id'] ?? 0);
            if ($id <= 0) {
                api_json(400, ['success' => false, 'message' => 'Missing patient id.']);
            }

            $_GET['id'] = $id;
            $_POST['json'] = json_encode($parameters);
            $patient->update();
            break;

        case 'toggle-active':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                api_json(405, ['success' => false, 'message' => 'Use POST for this operation.']);
            }
            $id = (int)($_GET['id'] ?? $parameters['patient_id'] ?? 0);
            if ($id <= 0) {
                api_json(400, ['success' => false, 'message' => 'Missing patient id.']);
            }

            $_GET['id'] = $id;
            $patient->toggleActive();
            break;

        default:
            api_json(400, ['success' => false, 'message' => 'Unsupported patient operation.']);
    }
} catch (Throwable $error) {
    error_log('[api/patients.php] ' . $error->getMessage());
    api_json(500, ['success' => false, 'message' => 'Could not process the patient request.']);
}