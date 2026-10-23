<?php

$loginRoles = [
    1 => ['name' => 'Administrator', 'url' => 'admin-login.php', 'description' => 'System administration and master data'],
    2 => ['name' => 'Doctor', 'url' => 'doctor-login.php', 'description' => 'Clinical care and discharge workflows'],
    3 => ['name' => 'Nurse', 'url' => 'nurse-login.php', 'description' => 'Admissions, room assignments, and nursing'],
    4 => ['name' => 'Cashier', 'url' => 'cashier-login.php', 'description' => 'Statements, payments, and billing'],
    5 => ['name' => 'Department', 'url' => 'department-login.php', 'description' => 'Service requests and fulfillment'],
];
$loginRoleId = (int)($loginRoleId ?? 0);
$loginRole = $loginRoles[$loginRoleId] ?? null;
$pageTitle  = $loginRole ? $loginRole['name'] . ' sign in' : 'Staff sign in';
$loginScriptPath = __DIR__ . '/../assets/js/login.js';
$pageScript = $loginRole
    ? BASE_URL . '/assets/js/login.js?v=' . (file_exists($loginScriptPath) ? filemtime($loginScriptPath) : time())
    : null;
require __DIR__ . '/partials/header.php';
?>

<?php if (!$loginRole): ?>
<main class="mx-auto flex min-h-[70vh] max-w-5xl items-center justify-center px-4 py-12 sm:px-6">
    <section class="w-full max-w-xl rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm sm:p-12">
        <p class="text-sm font-semibold uppercase tracking-wide text-blue-700">Billing Hospital</p>
        <h1 class="mt-3 text-3xl font-bold tracking-tight text-slate-900">Patient documents</h1>
        <p class="mt-3 text-sm leading-6 text-slate-600">Access your billing statements and other available records through the secure patient portal.</p>
        <a href="<?= BASE_URL ?>/patient-portal.php"
           class="mt-7 inline-flex items-center justify-center rounded-lg bg-blue-700 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
            Open Patient Portal
        </a>
    </section>
</main>
<?php else: ?>

<div class="min-h-screen grid grid-cols-1 lg:grid-cols-8">
    <div class="hidden lg:flex lg:col-span-5 relative bg-slate-900 text-white overflow-hidden">
        <img src="<?= BASE_URL ?>/assets/images/bg1.jpg" alt="Billing Hospital" class="absolute inset-0 w-full h-full object-cover">
        <div class="absolute inset-0 bg-slate-900/50"></div>
        <div class="relative z-10 flex flex-col justify-between w-full p-14">
            <span class="text-xl font-semibold tracking-tight">Hospital Billing System</span>
            <h2 class="text-4xl font-bold leading-tight max-w-lg">Patient information and billing, all in one place.</h2>
            <p class="text-xs text-white/50"></p>
        </div>
    </div>
    <div class="flex items-center justify-center px-6 py-12 bg-white lg:col-span-3">
        <div class="w-full max-w-sm">
            <div class="mb-8">
                <p class="text-sm font-semibold uppercase tracking-wide text-blue-700"><?= htmlspecialchars($loginRole['name']) ?> portal</p>
                <h1 class="mt-2 text-2xl font-bold text-slate-900 tracking-tight">Sign in</h1>
            </div>

            <div id="alert" class="hidden mb-5 rounded-lg px-4 py-3 text-sm border" role="alert"></div>

            <form id="loginForm" method="POST" action="<?= BASE_URL ?>/api/login.php?expected_role_id=<?= $loginRoleId ?>" novalidate class="space-y-5">
                <input id="expectedRoleId" type="hidden" value="<?= $loginRoleId ?>">

                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
                    <input type="email" id="email" name="email" autocomplete="email" required
                           class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400
                                  focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                           placeholder="name@example.com">
                    <p class="mt-1.5 text-xs text-red-600 hidden" data-error-for="email"></p>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
                     
                    </div>
                    <div class="relative">
                        <input type="password" id="password" name="password" autocomplete="current-password" required
                               class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 pr-11 text-sm text-slate-900 placeholder-slate-400
                                      focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                               placeholder="••••••••">
                        <button type="button" id="togglePassword"
                                class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:text-slate-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </button>
                    </div>
                    <p class="mt-1.5 text-xs text-red-600 hidden" data-error-for="password"></p>
                </div>

                <label class="inline-flex items-center gap-2 text-sm text-slate-600 select-none cursor-pointer">
                    <input type="checkbox" id="remember" name="remember"
                           class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    Remember me
                </label>

                <button type="submit" id="submitBtn"
                        class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5
                               text-sm font-semibold text-white hover:bg-blue-700
                               focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2
                               disabled:opacity-60 disabled:cursor-not-allowed transition">
                    <svg id="spinner" class="hidden animate-spin w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    <span id="submitLabel">Sign in</span>
                </button>
            </form>

        </div>
    </div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>