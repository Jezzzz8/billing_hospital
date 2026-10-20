<?php
require_once __DIR__ . '/../includes/patient_portal_session.php';
require_once __DIR__ . '/../includes/patient_portal_rate_limit.php';
require_once __DIR__ . '/../config/connection.php';

header('Content-Type: application/json; charset=utf-8');

function patient_portal_json(int $status, array $response): void
{
    http_response_code($status);
    echo json_encode($response);
    exit;
}

function patient_portal_input(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (is_array($data)) {
        return $data;
    }
    return $_POST;
}

function patient_portal_dashboard(PDO $pdo, int $patientId): array
{
    $patientStmt = $pdo->prepare(
        'SELECT patient_id, first_name, last_name, birth_date, medical_history
         FROM `patient` WHERE patient_id = ? AND is_active = 1 LIMIT 1'
    );
    $patientStmt->execute([$patientId]);
    $patient = $patientStmt->fetch(PDO::FETCH_ASSOC);
    if (!$patient) {
        throw new RuntimeException('Authenticated patient record could not be found.');
    }

    $admissionStmt = $pdo->prepare(
        'SELECT a.admission_id, a.admission_datetime, a.discharge_datetime,
                a.chief_complaint, ast.status_name AS status
         FROM `admission` a
         INNER JOIN `admission_status` ast ON ast.status_id = a.status_id
         WHERE a.patient_id = ?
         ORDER BY a.admission_datetime DESC
         LIMIT 50'
    );
    $admissionStmt->execute([$patientId]);
    $admissions = $admissionStmt->fetchAll(PDO::FETCH_ASSOC);

    $diagnosisStmt = $pdo->prepare(
        'SELECT ad.admission_id, d.diagnosis_name, d.icd_code,
                ad.diagnosis_type, ad.diagnosed_datetime
         FROM `admission_diagnosis` ad
         INNER JOIN `admission` a ON a.admission_id = ad.admission_id
         INNER JOIN `diagnosis` d ON d.diagnosis_id = ad.diagnosis_id
         WHERE a.patient_id = ?
         ORDER BY ad.diagnosed_datetime DESC
         LIMIT 100'
    );
    $diagnosisStmt->execute([$patientId]);
    $diagnoses = $diagnosisStmt->fetchAll(PDO::FETCH_ASSOC);

    $consultationStmt = $pdo->prepare(
        'SELECT c.consultation_datetime, c.purpose, c.status,
                u.first_name AS doctor_first_name, u.last_name AS doctor_last_name
         FROM `consultation` c
         INNER JOIN `doctor` d ON d.doctor_id = c.doctor_id
         INNER JOIN `user` u ON u.user_id = d.user_id
         WHERE c.patient_id = ?
         ORDER BY c.consultation_datetime DESC
         LIMIT 100'
    );
    $consultationStmt->execute([$patientId]);
    $consultations = $consultationStmt->fetchAll(PDO::FETCH_ASSOC);

    $statementStmt = $pdo->prepare(
        'SELECT bs.statement_id, bs.admission_id, bs.statement_date, bs.due_date,
                bs.subtotal_amount, bs.philhealth_deduction, bs.insurance_coverage_amount,
                bs.hmo_discount, bs.government_discount, bs.tax_amount,
                bs.total_amount, bs.amount_paid, bs.balance_amount,
                s.status_name AS status, a.admission_datetime, a.discharge_datetime
         FROM `billing_statement` bs
         INNER JOIN `billing_status` s ON s.status_id = bs.status_id
         INNER JOIN `admission` a ON a.admission_id = bs.admission_id
         WHERE a.patient_id = ?
         ORDER BY bs.statement_date DESC
         LIMIT 50'
    );
    $statementStmt->execute([$patientId]);
    $statements = $statementStmt->fetchAll(PDO::FETCH_ASSOC);

    $chargeStmt = $pdo->prepare(
        'SELECT c.statement_id, c.charge_datetime, ci.item_name, c.quantity, c.actual_price,
                c.amount
         FROM `charge` c
         INNER JOIN `billing_statement` bs ON bs.statement_id = c.statement_id
         INNER JOIN `admission` a ON a.admission_id = bs.admission_id
         INNER JOIN `charge_item` ci ON ci.charge_item_id = c.charge_item_id
         WHERE a.patient_id = ?
         ORDER BY c.statement_id DESC, c.charge_datetime, c.charge_id'
    );
    $chargeStmt->execute([$patientId]);

    return [
        'patient' => $patient,
        'admissions' => $admissions,
        'diagnoses' => $diagnoses,
        'consultations' => $consultations,
        'statements' => $statements,
        'charges' => $chargeStmt->fetchAll(PDO::FETCH_ASSOC),
    ];
}

$operation = $_GET['operation'] ?? '';
$input = $_SERVER['REQUEST_METHOD'] === 'POST' ? patient_portal_input() : [];
if (isset($input['operation'])) {
    $operation = (string)$input['operation'];
}

if ($operation === 'captcha') {
    $left = random_int(2, 9);
    $right = random_int(2, 9);
    $_SESSION['patient_portal_captcha'] = [
        'answer' => (string)($left + $right),
        'created_at' => time(),
    ];
    patient_portal_json(200, ['success' => true, 'question' => "{$left} + {$right}"]);
}

if ($operation === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $ipAddress = (string)($_SERVER['REMOTE_ADDR'] ?? '');
    $limit = patient_portal_check_rate_limit($ipAddress);
    if (!empty($limit['unavailable'])) {
        patient_portal_json(503, ['success' => false, 'message' => 'Patient sign-in is temporarily unavailable. Please try again shortly.']);
    }
    if (empty($limit['allowed'])) {
        header('Retry-After: ' . (int)($limit['retry_after'] ?? 60));
        patient_portal_json(429, ['success' => false, 'message' => 'Too many sign-in attempts. Please wait before trying again.']);
    }

    $captcha = $_SESSION['patient_portal_captcha'] ?? [];
    unset($_SESSION['patient_portal_captcha']);
    $answer = trim((string)($input['captcha_answer'] ?? ''));
    if (
        empty($captcha['answer'])
        || time() - (int)($captcha['created_at'] ?? 0) > 300
        || !hash_equals((string)$captcha['answer'], $answer)
    ) {
        patient_portal_json(401, ['success' => false, 'message' => 'Invalid name, date of birth, or CAPTCHA answer.']);
    }

    $firstName = trim((string)($input['first_name'] ?? ''));
    $lastName = trim((string)($input['last_name'] ?? ''));
    $birthDate = trim((string)($input['birth_date'] ?? ''));
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $birthDate);
    $dateErrors = DateTimeImmutable::getLastErrors();
    $validDate = $date !== false
        && ($dateErrors === false || ($dateErrors['warning_count'] === 0 && $dateErrors['error_count'] === 0))
        && $date->format('Y-m-d') === $birthDate;

    if ($firstName === '' || $lastName === '' || strlen($firstName) > 100
        || strlen($lastName) > 100 || !$validDate) {
        patient_portal_json(401, ['success' => false, 'message' => 'Invalid name, date of birth, or CAPTCHA answer.']);
    }

    try {
        $stmt = $pdo->prepare(
            'SELECT patient_id FROM `patient`
             WHERE first_name = ? AND last_name = ? AND birth_date = ? AND is_active = 1
             LIMIT 2'
        );
        $stmt->execute([$firstName, $lastName, $birthDate]);
        $matchingPatients = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if (count($matchingPatients) !== 1) {
            patient_portal_json(401, ['success' => false, 'message' => 'Invalid name, date of birth, or CAPTCHA answer.']);
        }
    } catch (Throwable $error) {
        error_log('[patient_portal_login] ' . $error->getMessage());
        patient_portal_json(500, ['success' => false, 'message' => 'Could not sign in right now. Please try again.']);
    }

    session_regenerate_id(true);
    $_SESSION = [
        'patient_portal' => [
            'patient_id' => (int)$matchingPatients[0],
            'last_activity' => time(),
        ],
    ];
    patient_portal_json(200, ['success' => true]);
}

if ($operation === 'logout' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => BASE_URL . '/',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
    session_destroy();
    patient_portal_json(200, ['success' => true]);
}

if ($operation === 'touch' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (empty($_SESSION['patient_portal']['patient_id'])) {
        patient_portal_json(401, ['success' => false, 'message' => 'Your portal session has ended. Please sign in again.']);
    }
    patient_portal_json(200, ['success' => true]);
}

if ($operation === 'dashboard' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $patientId = (int)($_SESSION['patient_portal']['patient_id'] ?? 0);
    if ($patientId <= 0) {
        patient_portal_json(401, ['success' => false, 'message' => 'Your portal session has ended. Please sign in again.']);
    }
    try {
        patient_portal_json(200, ['success' => true, 'data' => patient_portal_dashboard($pdo, $patientId)]);
    } catch (Throwable $error) {
        error_log('[patient_portal_dashboard] ' . $error->getMessage());
        patient_portal_json(500, ['success' => false, 'message' => 'Could not load your patient records. Please try again.']);
    }
}

patient_portal_json(400, ['success' => false, 'message' => 'Unknown patient portal operation.']);
