<?php

require_once __DIR__ . '/../../config/config.php';
$pageTitle = $pageTitle ?? 'Billing Hospital';
$pageScript = $pageScript ?? null;
$pageScriptIsModule = $pageScriptIsModule ?? false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> · Billing Hospital</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
    <script src="<?= BASE_URL ?>/assets/js/vendor/axios.min.js"></script>
</head>
<body data-base-url="<?= BASE_URL ?>" data-page="<?= htmlspecialchars($_GET['page'] ?? '') ?>" class="min-h-screen bg-slate-100 font-sans antialiased">