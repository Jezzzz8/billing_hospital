<?php

ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/../../includes/api_auth.php';

require_api_role(1);

require_once __DIR__ . '/../../controllers/PatientController.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    api_json(400, ['success' => false, 'message' => 'Missing patient id.']);
}

$details = (new PatientController($pdo))->getDetails($id);
if (!$details) {
    api_json(404, ['success' => false, 'message' => 'Patient not found.']);
}

api_json(200, ['success' => true, 'data' => $details]);