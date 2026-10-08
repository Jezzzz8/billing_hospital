<?php


require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/../../controllers/DoctorPortalController.php';
require_once __DIR__ . '/../../controllers/DoctorHelper.php';

$doctorId = DoctorHelper::getCurrentDoctorId($pdo);
if (!$doctorId) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Doctor profile not found.']);
    exit;
}

$controller = new DoctorPortalController($pdo, $doctorId);
$controller->createConsultation();