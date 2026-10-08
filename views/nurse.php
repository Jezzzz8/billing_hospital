<?php

require_once __DIR__ . '/../controllers/NurseController.php';

$controller = new NurseController($pdo);
$stats = $controller->getDashboardStats();
$activeAdmissions = $controller->getActiveAdmissions();
$roomsNeedingAttention = $controller->getRoomsNeedingAttention();
$readyForDischarge = $controller->getReadyForDischarge();
?>

<div class="mb-8">
    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Dashboard</h1>
    <p class="mt-1 text-sm text-slate-500">Patient assignments and room status at a glance.</p>
</div>

<?php if (!empty($readyForDischarge)): ?>
    <div class="mb-6 bg-white rounded-xl border border-blue-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-blue-200 bg-blue-50 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Ready for Discharge — Awaiting Payment Clearance</h3>
                    <p class="text-xs text-slate-600">
                        The doctor has cleared these patients. Discharge requires an existing statement with a non-overdue paid or partially paid balance.
                    </p>
                </div>
            </div>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-100 border border-blue-200 px-3 py-1 text-xs font-semibold text-blue-800">
                <?= count($readyForDischarge) ?> patient<?= count($readyForDischarge) === 1 ? '' : 's' ?>
            </span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="text-left px-6 py-3 font-semibold text-slate-700 text-xs uppercase tracking-wide">Patient</th>
                        <th class="text-left px-6 py-3 font-semibold text-slate-700 text-xs uppercase tracking-wide">Room</th>
                        <th class="text-left px-6 py-3 font-semibold text-slate-700 text-xs uppercase tracking-wide">Admitted</th>
                        <th class="text-left px-6 py-3 font-semibold text-slate-700 text-xs uppercase tracking-wide">Billing</th>
                        <th class="text-right px-6 py-3 font-semibold text-slate-700 text-xs uppercase tracking-wide">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach (array_slice($readyForDischarge, 0, 5) as $r):
                        $billingStatusLower = strtolower($r['billing_status_name'] ?? '');
                        $billingOverdue = !empty($r['due_date'])
                            && $r['due_date'] < date('Y-m-d')
                            && (float)($r['balance_amount'] ?? 0) > 0;
                        $canDischarge = !empty($r['statement_id'])
                            && in_array($billingStatusLower, ['paid', 'partially paid'], true)
                            && !$billingOverdue;
                    ?>
                        <tr class="hover:bg-blue-50/40">
                            <td class="px-6 py-4">
                                <p class="font-semibold text-slate-900"><?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?></p>
                                <p class="text-xs text-slate-500">#<?= (int)$r['admission_id'] ?></p>
                            </td>
                            <td class="px-6 py-4">
                                <?php if (!empty($r['room_number'])): ?>
                                    <p class="font-medium text-slate-900"><?= htmlspecialchars($r['room_number']) ?></p>
                                    <p class="text-xs text-slate-500"><?= htmlspecialchars($r['room_type_name'] ?? '') ?></p>
                                <?php else: ?>
                                    <span class="text-xs text-slate-400">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-slate-600">
                                <?= htmlspecialchars(date('M j, Y', strtotime($r['admission_datetime']))) ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php if (!empty($r['statement_id'])): ?>
                                    <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-medium"
                                          style="background-color: <?= htmlspecialchars($r['billing_status_color'] ?? '#6b7280') ?>1A;
                                                 color: <?= htmlspecialchars($r['billing_status_color'] ?? '#6b7280') ?>;
                                                 border-color: <?= htmlspecialchars($r['billing_status_color'] ?? '#6b7280') ?>40;">
                                        <?= htmlspecialchars($r['billing_status_name']) ?>
                                    </span>
                                    <p class="text-xs text-slate-500 mt-1">
                                        Balance: ₱<?= number_format((float)$r['balance_amount'], 2) ?>
                                    </p>
                                <?php else: ?>
                                    <span class="text-xs text-amber-600">No statement yet</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <?php if ($canDischarge): ?>
                                    <a href="<?= BASE_URL ?>/index.php?page=nurse-discharge-ready"
                                       class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        Discharge
                                    </a>
                                <?php else: ?>
                                    <a href="<?= BASE_URL ?>/index.php?page=nurse-discharge-ready"
                                       class="inline-flex items-center gap-1.5 rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-medium text-blue-700 hover:bg-blue-100">
                                        View
                                    </a>
                                <?php endif; ?>
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
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Active Admissions</p>
        <p class="mt-2 text-2xl font-bold text-slate-900"><?= $stats['active_admissions'] ?></p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Available Rooms</p>
        <p class="mt-2 text-2xl font-bold text-emerald-600"><?= $stats['available_rooms'] ?></p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Occupied Rooms</p>
        <p class="mt-2 text-2xl font-bold text-amber-600"><?= $stats['occupied_rooms'] ?></p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Ready for Discharge</p>
        <p class="mt-2 text-2xl font-bold text-blue-600"><?= $stats['ready_for_discharge'] ?></p>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <a href="<?= BASE_URL ?>/index.php?page=nurse-patient-search"
       class="flex items-center gap-4 bg-white rounded-xl border border-slate-200 p-5 hover:border-blue-300 hover:shadow-sm transition">
        <div class="w-12 h-12 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>
        <div class="min-w-0">
            <p class="font-semibold text-slate-900 truncate">Find Patient</p>
            <p class="text-xs text-slate-500 truncate">Search existing records</p>
        </div>
    </a>

    <a href="<?= BASE_URL ?>/index.php?page=nurse-patient-register"
       class="flex items-center gap-4 bg-white rounded-xl border border-slate-200 p-5 hover:border-emerald-300 hover:shadow-sm transition">
        <div class="w-12 h-12 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
            </svg>
        </div>
        <div class="min-w-0">
            <p class="font-semibold text-slate-900 truncate">Register Patient</p>
            <p class="text-xs text-slate-500 truncate">Add new patient</p>
        </div>
    </a>

    <a href="<?= BASE_URL ?>/index.php?page=nurse-admission-create"
       class="flex items-center gap-4 bg-white rounded-xl border border-slate-200 p-5 hover:border-amber-300 hover:shadow-sm transition">
        <div class="w-12 h-12 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
        </div>
        <div class="min-w-0">
            <p class="font-semibold text-slate-900 truncate">New Admission</p>
            <p class="text-xs text-slate-500 truncate">Admit a patient</p>
        </div>
    </a>

    <a href="<?= BASE_URL ?>/index.php?page=nurse-room-board"
       class="flex items-center gap-4 bg-white rounded-xl border border-slate-200 p-5 hover:border-violet-300 hover:shadow-sm transition">
        <div class="w-12 h-12 rounded-lg bg-violet-50 text-violet-600 flex items-center justify-center shrink-0">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
            </svg>
        </div>
        <div class="min-w-0">
            <p class="font-semibold text-slate-900 truncate">Room Board</p>
            <p class="text-xs text-slate-500 truncate">View all rooms</p>
        </div>
    </a>
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden mb-8">
    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
        <h3 class="text-lg font-semibold text-slate-900">Active Admissions</h3>
        <a href="<?= BASE_URL ?>/index.php?page=nurse-admissions" class="text-sm font-medium text-blue-600 hover:text-blue-700">View all →</a>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-6 py-3 font-medium">Patient</th>
                    <th class="text-left px-6 py-3 font-medium">Room</th>
                    <th class="text-left px-6 py-3 font-medium">Admitted</th>
                    <th class="text-left px-6 py-3 font-medium">Status</th>
                    <th class="text-right px-6 py-3 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($activeAdmissions)): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-slate-400">No active admissions</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($activeAdmissions as $a): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-3">
                                <p class="font-medium text-slate-900"><?= htmlspecialchars($a['first_name'] . ' ' . $a['last_name']) ?></p>
                                <p class="text-xs text-slate-500">#<?= (int)$a['admission_id'] ?></p>
                            </td>
                            <td class="px-6 py-3">
                                <?php if (!empty($a['room_number'])): ?>
                                    <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-medium"
                                          style="background-color: <?= htmlspecialchars($a['room_status_color'] ?? '#6b7280') ?>1A;
                                                 color: <?= htmlspecialchars($a['room_status_color'] ?? '#6b7280') ?>;
                                                 border-color: <?= htmlspecialchars($a['room_status_color'] ?? '#6b7280') ?>40;">
                                        <span class="w-1.5 h-1.5 rounded-full" style="background-color: <?= htmlspecialchars($a['room_status_color'] ?? '#6b7280') ?>"></span>
                                        <?= htmlspecialchars($a['room_number']) ?>
                                    </span>
                                    <p class="text-xs text-slate-500 mt-0.5"><?= htmlspecialchars($a['room_type_name'] ?? '') ?></p>
                                <?php else: ?>
                                    <span class="text-xs text-amber-600">No room assigned</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-3 text-slate-600">
                                <?= htmlspecialchars(date('M j, Y g:i A', strtotime($a['admission_datetime']))) ?>
                            </td>
                            <td class="px-6 py-3">
                                <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-medium"
                                      style="background-color: <?= htmlspecialchars($a['status_color'] ?? '#6b7280') ?>1A;
                                             color: <?= htmlspecialchars($a['status_color'] ?? '#6b7280') ?>;
                                             border-color: <?= htmlspecialchars($a['status_color'] ?? '#6b7280') ?>40;">
                                    <?= htmlspecialchars($a['status_name'] ?? '—') ?>
                                </span>
                            </td>
                            <td class="px-6 py-3 text-right">
                                <a href="<?= BASE_URL ?>/index.php?page=nurse-room-assignments&admission_id=<?= (int)$a['admission_id'] ?>"
                                   class="inline-flex items-center gap-1.5 rounded-lg border border-blue-200 bg-blue-50 px-2.5 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-100">
                                    Manage
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (!empty($roomsNeedingAttention)): ?>
<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-200">
        <h3 class="text-lg font-semibold text-slate-900">Rooms Needing Attention</h3>
        <p class="text-xs text-slate-500 mt-0.5">Rooms that may need cleaning or maintenance</p>
    </div>
    <div class="p-6">
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
            <?php foreach ($roomsNeedingAttention as $r): ?>
                <div class="rounded-lg border p-3"
                     style="background-color: <?= htmlspecialchars($r['color_code'] ?? '#6b7280') ?>1A;
                            border-color: <?= htmlspecialchars($r['color_code'] ?? '#6b7280') ?>40;">
                    <p class="text-sm font-semibold" style="color: <?= htmlspecialchars($r['color_code'] ?? '#6b7280') ?>">
                        Room <?= htmlspecialchars($r['room_number']) ?>
                    </p>
                    <p class="text-xs mt-0.5" style="color: <?= htmlspecialchars($r['color_code'] ?? '#6b7280') ?>">
                        <?= htmlspecialchars($r['status_name']) ?>
                    </p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>