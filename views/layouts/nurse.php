<?php

$pageTitle  = $pageTitle  ?? 'Nurse Dashboard';
$pageScript = $pageScript ?? BASE_URL . '/assets/js/logout.js';

require __DIR__ . '/../partials/header.php';

$sidebarSections = [
    'Main' => [
        ['label' => 'Dashboard', 'href' => BASE_URL . '/index.php?page=nurse', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
    ],

    'Patient Care' => [
        ['label' => 'Find Patient',        'href' => BASE_URL . '/index.php?page=nurse-patient-search',    'icon' => 'M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z'],
        ['label' => 'Register Patient',    'href' => BASE_URL . '/index.php?page=nurse-patient-register',  'icon' => 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z'],
        ['label' => 'New Admission',       'href' => BASE_URL . '/index.php?page=nurse-admission-create',  'icon' => 'M12 4v16m8-8H4'],
        ['label' => 'Active Admissions',   'href' => BASE_URL . '/index.php?page=nurse-admissions',         'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
        ['label' => 'Receive Department Items', 'href' => BASE_URL . '/index.php?page=nurse-fulfillment-receipts', 'icon' => 'M5 13l4 4L19 7'],
        ['label' => 'Medicine Intake Monitor', 'href' => BASE_URL . '/index.php?page=nurse-medicine-intakes', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a2 2 0 011.414.586l5.414 5.414A2 2 0 0119 10.414V19a2 2 0 01-2 2z'],
    ],

    'Room Management' => [
        ['label' => 'Room Assignments',    'href' => BASE_URL . '/index.php?page=nurse-room-assignments',  'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
        ['label' => 'Room Status Board',   'href' => BASE_URL . '/index.php?page=nurse-room-board',        'icon' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z'],
    ],

    'Discharge' => [
        ['label' => 'Ready for Discharge', 'href' => BASE_URL . '/index.php?page=nurse-discharge-ready',   'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
        ['label' => 'Discharge Records',   'href' => BASE_URL . '/index.php?page=nurse-discharge-records', 'icon' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
    ],
];

$currentPage = $_GET['page'] ?? 'nurse';

require __DIR__ . '/../partials/sidebar.php';
?>

<div class="lg:pl-64">
    <?php require __DIR__ . '/../partials/topbar.php'; ?>

    <main class="p-6 pt-16 lg:pt-6 max-w-7xl mx-auto">
        <?php require $contentFile; ?>
    </main>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>