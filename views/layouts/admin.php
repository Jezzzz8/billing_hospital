<?php

$pageTitle  = $pageTitle  ?? 'Admin Dashboard';
$pageScript = $pageScript ?? BASE_URL . '/assets/js/logout.js';

require __DIR__ . '/../partials/header.php';

$sidebarSections = [
    'Main' => [
        ['label' => 'Dashboard', 'href' => BASE_URL . '/index.php?page=dashboard', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
    ],

    'Master Files' => [
        ['label' => 'Genders',            'href' => BASE_URL . '/index.php?page=gender',            'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
        ['label' => 'Roles',              'href' => BASE_URL . '/index.php?page=role',              'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
        ['label' => 'Departments',        'href' => BASE_URL . '/index.php?page=department',        'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
        ['label' => 'Room Statuses',      'href' => BASE_URL . '/index.php?page=room-status',       'icon' => 'M9 12h6m-6 4h6M5 8h14M5 12h14M5 16h14'],
        ['label' => 'Admission Statuses', 'href' => BASE_URL . '/index.php?page=admission-status',  'icon' => 'M9 12h6m-6 4h6M5 8h14M5 12h14M5 16h14'],
        ['label' => 'Billing Statuses',   'href' => BASE_URL . '/index.php?page=billing-status',    'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 1v8m0 0v1'],
        ['label' => 'Payment Types',      'href' => BASE_URL . '/index.php?page=payment-type',      'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
        ['label' => 'Specializations',    'href' => BASE_URL . '/index.php?page=specialization',    'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253'],
        ['label' => 'Diagnoses',          'href' => BASE_URL . '/index.php?page=diagnosis',         'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
        ['label' => 'Charge Categories',  'href' => BASE_URL . '/index.php?page=charge-category',   'icon' => 'M4 6h16M4 10h16M4 14h16M4 18h16'],
        ['label' => 'Charge Items',       'href' => BASE_URL . '/index.php?page=charge-item',       'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
        ['label' => 'Buildings',          'href' => BASE_URL . '/index.php?page=building',          'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
        ['label' => 'Floor Levels',       'href' => BASE_URL . '/index.php?page=floor-level',       'icon' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z'],
        ['label' => 'Room Types',         'href' => BASE_URL . '/index.php?page=room-type',         'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3'],
        ['label' => 'Rooms',              'href' => BASE_URL . '/index.php?page=room',              'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
    ],

    'People' => [
        ['label' => 'Users',    'href' => BASE_URL . '/index.php?page=users',    'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
        ['label' => 'Doctors',  'href' => BASE_URL . '/index.php?page=doctors',  'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
        ['label' => 'Patients', 'href' => BASE_URL . '/index.php?page=patients', 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0'],
    ],

    'Reports' => [
        ['label' => 'Billing', 'href' => BASE_URL . '/index.php?page=billing', 'icon' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
    ],
];

$currentPage = $_GET['page'] ?? 'dashboard';

require __DIR__ . '/../partials/sidebar.php';
?>

<div class="lg:pl-64">
    <?php require __DIR__ . '/../partials/topbar.php'; ?>

    <main class="p-6 pt-16 lg:pt-6 max-w-7xl mx-auto">
        <?php require $contentFile; ?>
    </main>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>