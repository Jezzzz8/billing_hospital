<?php

session_start();

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: 0');

require_once __DIR__ . '/config/config.php';

if (empty($_SESSION['user'])) {
    require __DIR__ . '/views/login.php';
    exit;
}

$currentUser = $_SESSION['user'];
$fullName    = trim($currentUser['first_name'] . ' ' . $currentUser['last_name']);
$roleId      = (int)$currentUser['role_id'];

$defaultPage = match ($roleId) {
    1 => 'dashboard',
    2 => 'doctor',
    3 => 'nurse',
    4 => 'cashier',
    5 => 'department',
    default => 'dashboard',
};

$page = $_GET['page'] ?? $defaultPage;

$adminPages = [
    'dashboard',
    'users', 'users-create',
    'doctors', 'patients',
    'billing', 'reports',
    'gender',
    'role',
    'room-status',
    'admission-status',
    'billing-status',
    'payment-type',
    'specialization',
    'diagnosis',
    'charge-category',
    'charge-item',
    'building',
    'floor-level',
    'room-type',
    'room',
    'department',
    'department-requests',
];

$cashierPages = [
    'cashier',
    'cashier-payment-counter',
    'cashier-statements',
    'cashier-payments',
    'cashier-reports',
    'cashier-receipt',
];

$allowed = [
    1 => array_merge($adminPages, [
        'admin-receipt',
    ]),
    2 => [
        'doctor',
        'doctor-patients',
        'doctor-consultations',
        'doctor-diagnosis-record',
        'doctor-service-requests',
        'doctor-discharge-ready',
    ],
    3 => [
        'nurse',
        'nurse-patient-search',
        'nurse-patient-register',
        'nurse-admission-create',
        'nurse-admissions',
        'nurse-fulfillment-receipts',
        'nurse-room-assignments',
        'nurse-room-board',
        'nurse-discharge-ready',
        'nurse-discharge-records',
    ],
    4 => $cashierPages,
    5 => [
        'department',
        'department-queue',
        'department-fulfillment',
    ],
];

if (!in_array($page, $allowed[$roleId] ?? [], true)) {
    http_response_code(403);
    echo 'Access denied. Requested page: ' . htmlspecialchars($page);
    exit;
}

$layout = match ($roleId) {
    1 => 'admin',
    2 => 'doctor',
    3 => 'nurse',
    4 => 'cashier',
    5 => 'department',
    default => 'admin',
};

$contentFile = null;

if ($roleId === 1) {
    if ($page === 'admin-receipt') {
        $contentFile = __DIR__ . '/views/cashier/receipt.php';
    } else {
        $contentFile = __DIR__ . '/views/admin/' . $page . '.php';
    }
} elseif ($roleId === 2) {
    if ($page === 'doctor') {
        $contentFile = __DIR__ . '/views/doctor.php';
    } elseif (str_starts_with($page, 'doctor-')) {
        $viewName = substr($page, 7);
        $contentFile = __DIR__ . '/views/doctor/' . $viewName . '.php';
    }
} elseif ($roleId === 3) {
    if ($page === 'nurse') {
        $contentFile = __DIR__ . '/views/nurse.php';
    } elseif (str_starts_with($page, 'nurse-')) {
        $viewName = substr($page, 6);
        $contentFile = __DIR__ . '/views/nurse/' . $viewName . '.php';
    }
} elseif ($roleId === 4) {
    if ($page === 'cashier') {
        $contentFile = __DIR__ . '/views/cashier.php';
    } elseif (str_starts_with($page, 'cashier-')) {
        $viewName = substr($page, 8);
        $contentFile = __DIR__ . '/views/cashier/' . $viewName . '.php';
    }
} elseif ($roleId === 5) {
    if ($page === 'department') {
        $contentFile = __DIR__ . '/views/department/dashboard.php';
    } elseif (str_starts_with($page, 'department-')) {
        $viewName = substr($page, 11);
        $contentFile = __DIR__ . '/views/department/' . $viewName . '.php';
    }
} else {
    $contentFile = __DIR__ . '/views/' . $page . '.php';
}

if (!$contentFile || !file_exists($contentFile)) {
    http_response_code(404);
    echo 'Content file not found: ' . htmlspecialchars($contentFile ?? 'unknown');
    exit;
}

$pageTitle = ucwords(str_replace(['-', '/'], [' ', ' · '], $page));

$pageTitle = match ($page) {
    'cashier-statements'      => 'Billing Statements',
    'cashier-payment-counter' => 'Payment Counter',
    'cashier-payments'        => 'Payments',
    'cashier-reports'         => 'Reports',
    'cashier-receipt'         => 'Statement',
    'admin-receipt'           => 'Statement',
    'department'              => 'Departments',
    'department-requests'     => 'Department Requests',
    'department-queue'        => 'Request Queue',
    'department-fulfillment'  => 'Fulfillment',
    default                   => $pageTitle,
};

$moduleName = match ($roleId) {
    1 => 'admin',
    2 => 'doctor',
    3 => 'nurse',
    4 => 'cashier',
    5 => 'department',
    default => null,
};

$masterPages = [
    'gender', 'role', 'department',
    'room-status', 'admission-status', 'billing-status',
    'payment-type', 'specialization', 'diagnosis',
    'charge-category', 'charge-item',
    'building', 'floor-level', 'room-type', 'room',
    'doctors',
];

$jsCandidates = [];
$pageScriptIsModule = false;

if ($roleId === 1 && in_array($page, $masterPages, true)) {
    $jsCandidates[] = '/assets/js/master.js';
    $pageScriptIsModule = true;
} elseif ($roleId === 1 && $page === 'patients') {
    $jsCandidates[] = '/assets/js/master/patients.js';
    $pageScriptIsModule = true;
} elseif ($roleId === 1 && $page === 'billing') {
    $jsCandidates[] = '/assets/js/master/billing.js';
    $pageScriptIsModule = true;
} elseif ($roleId === 1 && in_array($page, ['dashboard', 'reports'], true)) {
    $jsCandidates[] = '/assets/js/admin.js';
    $pageScriptIsModule = true;
} elseif ($moduleName !== null && !in_array($moduleName, ['admin', 'cashier'], true)) {
    $jsCandidates[] = '/assets/js/' . $moduleName . '.js';
}

$pageScript = null;
foreach ($jsCandidates as $relPath) {
    if (file_exists(__DIR__ . $relPath)) {
        $pageScript = BASE_URL . $relPath;
        break;
    }
}

$currentPage = $page;

require_once __DIR__ . '/config/connection.php';

if (isset($_GET['partial']) && $_GET['partial'] === '1') {
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Page-Title: ' . rawurlencode($pageTitle));
    header('X-Page-Script: ' . ($pageScript ?? ''));
    header('X-Page-Module: ' . ($pageScriptIsModule ? '1' : '0'));
    header('X-Page-Id: ' . rawurlencode($page));

    require $contentFile;
    exit;
}

require __DIR__ . '/views/layouts/' . $layout . '.php';