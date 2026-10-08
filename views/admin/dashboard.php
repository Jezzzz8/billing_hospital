<?php

require_once __DIR__ . '/../../controllers/CashierController.php';
require_once __DIR__ . '/../../controllers/NurseController.php';
require_once __DIR__ . '/../../controllers/DoctorController.php';
require_once __DIR__ . '/../../controllers/PatientController.php';
require_once __DIR__ . '/../../controllers/RoomController.php';

$cashierController = new CashierController($pdo);
$nurseController   = new NurseController($pdo);
$doctorController  = new DoctorController($pdo);
$patientController = new PatientController($pdo);
$roomController    = new RoomController($pdo);

$cashierStats = $cashierController->getDashboardStats();
$nurseStats   = $nurseController->getDashboardStats();

$allPatients  = $patientController->getAll();
$allDoctors   = $doctorController->getAll();
$allRooms     = $roomController->getAll();

$totalPatients    = count($allPatients);
$activePatients   = count(array_filter($allPatients, fn($p) => (int)$p['is_active'] === 1));
$admittedNow      = count(array_filter($allPatients, fn($p) => !empty($p['admission_id'])));

$totalDoctors     = count($allDoctors);
$activeDoctors    = count(array_filter($allDoctors, fn($d) => (int)$d['is_active'] === 1));

$totalRooms       = count($allRooms);
$activeRooms      = count(array_filter($allRooms, fn($r) => (int)$r['is_active'] === 1));

$recentStatements = $cashierController->getRecentStatements(8);
$recentAdmissions = $cashierController->getRecentAdmissions(8);

$departmentStats = $pdo->query(
    'SELECT d.department_id, d.department_name,
            (SELECT COUNT(*) FROM `service_request` sr
             WHERE sr.department_id = d.department_id
               AND sr.status IN ("Pending", "In Progress")) AS pending_count
     FROM `department` d
     WHERE d.is_active = 1
     ORDER BY pending_count DESC, d.department_name
     LIMIT 8'
)->fetchAll();

$totalPendingRequests = (int)$pdo->query(
    'SELECT COUNT(*) FROM `service_request` WHERE status IN ("Pending", "In Progress")'
)->fetchColumn();
?>

<div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
            Welcome back, <?= htmlspecialchars($currentUser['first_name']) ?>
        </h1>
        <p class="mt-1 text-sm text-slate-500">Here's what's happening across the hospital today.</p>
    </div>
    <div class="flex items-center gap-2 text-xs text-slate-500">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <?= htmlspecialchars(date('l, F j, Y')) ?>
    </div>
</div>

<?php if (!empty($recentAdmissions)): ?>
    <div class="mb-6 bg-white rounded-xl border border-amber-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Recent Admissions</h3>
                    <p class="text-xs text-slate-600">
                        Open each admission's running billing statement.
                    </p>
                </div>
            </div>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-100 border border-blue-200 px-3 py-1 text-xs font-semibold text-blue-800">
                <?= count($recentAdmissions) ?> admission<?= count($recentAdmissions) === 1 ? '' : 's' ?>
            </span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="text-left px-6 py-3 font-semibold text-slate-700 text-xs uppercase tracking-wide">Admission</th>
                        <th class="text-left px-6 py-3 font-semibold text-slate-700 text-xs uppercase tracking-wide">Patient</th>
                        <th class="text-left px-6 py-3 font-semibold text-slate-700 text-xs uppercase tracking-wide">Statement</th>
                        <th class="text-right px-6 py-3 font-semibold text-slate-700 text-xs uppercase tracking-wide">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach (array_slice($recentAdmissions, 0, 5) as $a): ?>
                        <tr class="hover:bg-blue-50/40">
                            <td class="px-6 py-4">
                                <p class="font-semibold text-slate-900">#<?= (int)$a['admission_id'] ?></p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="font-medium text-slate-900"><?= htmlspecialchars($a['first_name'] . ' ' . $a['last_name']) ?></p>
                            </td>
                            <td class="px-6 py-4 text-slate-600">
                                #<?= (int)$a['statement_id'] ?> · <?= htmlspecialchars($a['billing_status_name']) ?>
                                · ₱<?= number_format((float)$a['balance_amount'], 2) ?> due
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <a href="<?= BASE_URL ?>/index.php?page=admin-receipt&id=<?= (int)$a['statement_id'] ?>"
                                   class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    View Statement
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <div class="flex items-center justify-between mb-2">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Total Patients</p>
            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
        </div>
        <p class="text-3xl font-bold text-slate-900"><?= $totalPatients ?></p>
        <p class="text-xs text-slate-500 mt-1"><?= $activePatients ?> active · <?= $admittedNow ?> admitted</p>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <div class="flex items-center justify-between mb-2">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Active Admissions</p>
            <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
        <p class="text-3xl font-bold text-slate-900"><?= $nurseStats['active_admissions'] ?></p>
        <p class="text-xs text-slate-500 mt-1"><?= $nurseStats['ready_for_discharge'] ?> ready for discharge</p>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <div class="flex items-center justify-between mb-2">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Rooms</p>
            <div class="w-8 h-8 rounded-lg bg-violet-50 text-violet-600 flex items-center justify-center">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </div>
        </div>
        <p class="text-3xl font-bold text-slate-900"><?= $totalRooms ?></p>
        <p class="text-xs text-slate-500 mt-1">
            <span class="text-emerald-600 font-medium"><?= $nurseStats['available_rooms'] ?> available</span>
            · <span class="text-rose-600 font-medium"><?= $nurseStats['occupied_rooms'] ?> occupied</span>
        </p>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <div class="flex items-center justify-between mb-2">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Pending Requests</p>
            <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
        </div>
        <p class="text-3xl font-bold text-amber-600"><?= $totalPendingRequests ?></p>
        <p class="text-xs text-slate-500 mt-1">Across all departments</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
            <div>
                <h3 class="text-base font-semibold text-slate-900">Department Queues</h3>
                <p class="text-xs text-slate-500 mt-0.5">Pending service requests per department</p>
            </div>
            <a href="<?= BASE_URL ?>/index.php?page=department" class="text-sm font-medium text-blue-600 hover:text-blue-700">Manage →</a>
        </div>
        <?php if (empty($departmentStats)): ?>
            <p class="p-8 text-center text-sm text-slate-400">No departments configured.</p>
        <?php else: ?>
            <div class="divide-y divide-slate-100">
                <?php foreach ($departmentStats as $d): ?>
                    <div class="flex items-center justify-between px-6 py-3">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                            </div>
                            <p class="font-medium text-slate-900"><?= htmlspecialchars($d['department_name']) ?></p>
                        </div>
                        <span class="inline-flex items-center justify-center rounded-full px-2.5 py-0.5 text-xs font-semibold
                                     <?= (int)$d['pending_count'] > 0 ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-600' ?>">
                            <?= (int)$d['pending_count'] ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200">
            <h3 class="text-base font-semibold text-slate-900">Billing Snapshot</h3>
            <p class="text-xs text-slate-500 mt-0.5">Today's collections and outstanding</p>
        </div>
        <div class="grid grid-cols-2 gap-4 p-6">
            <div class="bg-emerald-50 border border-emerald-100 rounded-lg p-4">
                <p class="text-xs text-emerald-700 uppercase tracking-wide">Collected Today</p>
                <p class="mt-2 text-xl font-bold text-emerald-800">₱<?= number_format($cashierStats['collected_today'], 2) ?></p>
            </div>
            <div class="bg-amber-50 border border-amber-100 rounded-lg p-4">
                <p class="text-xs text-amber-700 uppercase tracking-wide">Pending Statements</p>
                <p class="mt-2 text-xl font-bold text-amber-800"><?= $cashierStats['pending_statements'] ?></p>
            </div>
            <div class="bg-rose-50 border border-rose-100 rounded-lg p-4">
                <p class="text-xs text-rose-700 uppercase tracking-wide">Overdue</p>
                <p class="mt-2 text-xl font-bold text-rose-800"><?= $cashierStats['overdue'] ?></p>
            </div>
            <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
                <p class="text-xs text-slate-600 uppercase tracking-wide">Outstanding</p>
                <p class="mt-2 text-lg font-bold text-slate-900">₱<?= number_format($cashierStats['total_outstanding'], 2) ?></p>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <a href="<?= BASE_URL ?>/index.php?page=patients"
       class="flex items-center gap-4 bg-white rounded-xl border border-slate-200 p-5 hover:border-blue-300 hover:shadow-sm transition">
        <div class="w-12 h-12 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
        </div>
        <div class="min-w-0">
            <p class="font-semibold text-slate-900 truncate">Patients</p>
            <p class="text-xs text-slate-500 truncate">Manage records</p>
        </div>
    </a>

    <a href="<?= BASE_URL ?>/index.php?page=doctors"
       class="flex items-center gap-4 bg-white rounded-xl border border-slate-200 p-5 hover:border-teal-300 hover:shadow-sm transition">
        <div class="w-12 h-12 rounded-lg bg-teal-50 text-teal-600 flex items-center justify-center shrink-0">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div class="min-w-0">
            <p class="font-semibold text-slate-900 truncate">Doctors</p>
            <p class="text-xs text-slate-500 truncate">Manage physicians</p>
        </div>
    </a>

    <a href="<?= BASE_URL ?>/index.php?page=department"
       class="flex items-center gap-4 bg-white rounded-xl border border-slate-200 p-5 hover:border-indigo-300 hover:shadow-sm transition">
        <div class="w-12 h-12 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
        </div>
        <div class="min-w-0">
            <p class="font-semibold text-slate-900 truncate">Departments</p>
            <p class="text-xs text-slate-500 truncate">Manage queues</p>
        </div>
    </a>

    <a href="<?= BASE_URL ?>/index.php?page=billing"
       class="flex items-center gap-4 bg-white rounded-xl border border-slate-200 p-5 hover:border-emerald-300 hover:shadow-sm transition">
        <div class="w-12 h-12 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1"/>
            </svg>
        </div>
        <div class="min-w-0">
            <p class="font-semibold text-slate-900 truncate">Billing</p>
            <p class="text-xs text-slate-500 truncate">View statements</p>
        </div>
    </a>
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
        <div>
            <h3 class="text-lg font-semibold text-slate-900">Recent Statements</h3>
            <p class="text-xs text-slate-500 mt-0.5">Latest 8 billing statements</p>
        </div>
        <a href="<?= BASE_URL ?>/index.php?page=billing" class="text-sm font-medium text-blue-600 hover:text-blue-700">View all →</a>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-6 py-3 font-medium">Statement</th>
                    <th class="text-left px-6 py-3 font-medium">Patient</th>
                    <th class="text-right px-6 py-3 font-medium">Total</th>
                    <th class="text-right px-6 py-3 font-medium">Paid</th>
                    <th class="text-right px-6 py-3 font-medium">Balance</th>
                    <th class="text-left px-6 py-3 font-medium">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($recentStatements)): ?>
                    <tr><td colspan="6" class="px-6 py-8 text-center text-slate-400">No statements yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($recentStatements as $s): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-3">
                                <p class="font-medium text-slate-900">#<?= (int)$s['statement_id'] ?></p>
                                <p class="text-xs text-slate-500"><?= htmlspecialchars(date('M j, Y', strtotime($s['statement_date']))) ?></p>
                            </td>
                            <td class="px-6 py-3">
                                <p class="font-medium text-slate-900"><?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></p>
                                <p class="text-xs text-slate-500">Admission #<?= (int)$s['admission_id'] ?></p>
                            </td>
                            <td class="px-6 py-3 text-right font-medium text-slate-900 whitespace-nowrap">
                                ₱<?= number_format((float)$s['total_amount'], 2) ?>
                            </td>
                            <td class="px-6 py-3 text-right text-emerald-700 whitespace-nowrap">
                                ₱<?= number_format((float)$s['amount_paid'], 2) ?>
                            </td>
                            <td class="px-6 py-3 text-right font-semibold whitespace-nowrap <?= (float)$s['balance_amount'] > 0 ? 'text-rose-700' : 'text-slate-400' ?>">
                                ₱<?= number_format((float)$s['balance_amount'], 2) ?>
                            </td>
                            <td class="px-6 py-3">
                                <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-medium"
                                      style="background-color: <?= htmlspecialchars($s['color_code'] ?? '#6b7280') ?>1A;
                                             color: <?= htmlspecialchars($s['color_code'] ?? '#6b7280') ?>;
                                             border-color: <?= htmlspecialchars($s['color_code'] ?? '#6b7280') ?>40;">
                                    <span class="w-1.5 h-1.5 rounded-full" style="background-color: <?= htmlspecialchars($s['color_code'] ?? '#6b7280') ?>"></span>
                                    <?= htmlspecialchars($s['status_name']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>