<?php

$pageTitle  = $pageTitle  ?? 'Cashier Dashboard';
$pageScript = $pageScript ?? BASE_URL . '/assets/js/logout.js';

require __DIR__ . '/../partials/header.php';

$sidebarSections = [
    'Main' => [
        ['label' => 'Dashboard',        'href' => BASE_URL . '/index.php?page=cashier',                'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
    ],

    'Billing' => [
        ['label' => 'Payment Counter',  'href' => BASE_URL . '/index.php?page=cashier-payment-counter', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
        ['label' => 'Statements',       'href' => BASE_URL . '/index.php?page=cashier-statements',      'icon' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
        ['label' => 'Payments',         'href' => BASE_URL . '/index.php?page=cashier-payments',        'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
    ],

    'Reports' => [
        ['label' => 'Reports',          'href' => BASE_URL . '/index.php?page=cashier-reports',         'icon' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
    ],
];

$currentPage = $_GET['page'] ?? 'cashier';

require __DIR__ . '/../partials/sidebar.php';
?>

<div class="lg:pl-64">
    <?php require __DIR__ . '/../partials/topbar.php'; ?>

    <main class="p-6 pt-16 lg:pt-6 max-w-7xl mx-auto">
        <?php require $contentFile; ?>
    </main>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>