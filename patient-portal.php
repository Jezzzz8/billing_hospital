<?php
require_once __DIR__ . '/includes/patient_portal_session.php';

$isAuthenticated = !empty($_SESSION['patient_portal']['patient_id']);
$appCssPath = __DIR__ . '/assets/css/app.css';
$patientPortalJsPath = __DIR__ . '/assets/js/patient-portal.js';
$sweetAlertCssPath = __DIR__ . '/assets/css/sweetalert2.min.css';
$sweetAlertJsPath = __DIR__ . '/assets/js/vendor/sweetalert2.all.min.js';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Patient Portal · Billing Hospital</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css?v=<?= (int)filemtime($appCssPath) ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/sweetalert2.min.css?v=<?= (int)filemtime($sweetAlertCssPath) ?>">
</head>
<body class="min-h-screen bg-slate-100 font-sans antialiased" data-base-url="<?= BASE_URL ?>" data-authenticated="<?= $isAuthenticated ? '1' : '0' ?>">
    <main class="min-h-screen px-4 py-8 sm:px-6 sm:py-12">
        <section id="patientPortalLogin" class="<?= $isAuthenticated ? 'hidden' : 'flex' ?> min-h-[75vh] items-center justify-center">
            <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <div class="mb-8">
                <p class="text-sm font-semibold uppercase tracking-wide text-blue-700">Patient Portal</p>
                <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900">Sign in</h1>
                <p class="mt-2 text-sm leading-6 text-slate-600">Use your Patient ID and registered personal details to access your documents.</p>
            </div>
            <form id="patientPortalLoginForm" class="mt-6 space-y-4">
                <label class="block text-sm font-medium text-slate-700" for="patientId">Patient ID
                    <input id="patientId" name="patient_id" type="number" inputmode="numeric" min="1" step="1" required class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm font-normal text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </label>
                <label class="block text-sm font-medium text-slate-700" for="firstName">First name
                    <input id="firstName" name="first_name" type="text" autocomplete="given-name" maxlength="100" required class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm font-normal text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </label>
                <label class="block text-sm font-medium text-slate-700" for="lastName">Last name
                    <input id="lastName" name="last_name" type="text" autocomplete="family-name" maxlength="100" required class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm font-normal text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </label>
                <label class="block text-sm font-medium text-slate-700" for="birthDate">Date of birth
                    <input id="birthDate" name="birth_date" type="date" autocomplete="bday" required class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm font-normal text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </label>
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <p class="text-sm font-medium text-slate-700">Security check: <span id="captchaQuestion" class="font-bold text-slate-900">Loading…</span></p>
                    <div class="mt-2 flex gap-2">
                        <input id="captchaAnswer" name="captcha_answer" type="number" inputmode="numeric" required aria-label="Answer to security question" class="min-w-0 flex-1 rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <button id="refreshCaptcha" type="button" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-white">New question</button>
                    </div>
                </div>
                <p id="portalLoginError" class="hidden rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-800" role="alert"></p>
                <button id="patientPortalLoginButton" type="submit" class="w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-wait disabled:opacity-60">Sign in securely</button>
            </form>
            <p class="mt-6 text-xs leading-5 text-slate-500">For privacy, sign out when finished. Your portal session ends after five minutes of inactivity.</p>
            </div>
        </section>

        <section id="patientPortalRecords" class="<?= $isAuthenticated ? '' : 'hidden' ?> lg:col-span-8 px-4 py-8 sm:px-6 sm:py-12">
            <div id="patientPortalLoading" class="rounded-xl border border-slate-200 bg-white p-8 text-center text-sm text-slate-600">Loading your records…</div>
            <div id="patientPortalContent" class="hidden"></div>
        </section>
    </main>

    <script src="<?= BASE_URL ?>/assets/js/vendor/sweetalert2.all.min.js?v=<?= (int)filemtime($sweetAlertJsPath) ?>"></script>
    <script src="<?= BASE_URL ?>/assets/js/sweetalert-notifications.js?v=<?= (int)filemtime(__DIR__ . '/assets/js/sweetalert-notifications.js') ?>"></script>
    <script src="<?= BASE_URL ?>/assets/js/patient-portal.js?v=<?= (int)filemtime($patientPortalJsPath) ?>"></script>
</body>
</html>
