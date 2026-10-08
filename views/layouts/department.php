<?php
$pageTitle  = $pageTitle  ?? 'Department Dashboard';
$pageScript = $pageScript ?? BASE_URL . '/assets/js/logout.js';

require __DIR__ . '/../partials/header.php';

$sidebarSections = [
    'Main' => [
        ['label' => 'Dashboard', 'href' => BASE_URL . '/index.php?page=department', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
    ],
    'Department' => [
        ['label' => 'Request Queue', 'href' => BASE_URL . '/index.php?page=department-queue', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
        ['label' => 'Fulfillment', 'href' => BASE_URL . '/index.php?page=department-fulfillment', 'icon' => 'M5 13l4 4L19 7'],
    ],
];

$currentPage = $_GET['page'] ?? 'department';

require __DIR__ . '/../partials/sidebar.php';
?>

<div class="lg:pl-64">
    <?php require __DIR__ . '/../partials/topbar.php'; ?>

    <main class="p-6 pt-16 lg:pt-6 max-w-7xl mx-auto">
        <?php require $contentFile; ?>
    </main>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>