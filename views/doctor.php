<?php

require_once __DIR__ . '/../controllers/DoctorPortalController.php';
require_once __DIR__ . '/../controllers/DoctorHelper.php';

$doctorId = DoctorHelper::getCurrentDoctorId($pdo);

if (!$doctorId) {
    echo '<div class="bg-amber-50 border border-amber-200 rounded-lg p-6 text-amber-800">';
    echo '<p class="font-semibold">Doctor profile not found.</p>';
    echo '<p class="text-sm mt-1">Your account is not linked to a doctor record. Contact an administrator.</p>';
    echo '</div>';
    return;
}

$controller = new DoctorPortalController($pdo, $doctorId);
$stats = $controller->getDashboardStats();
$patients = $controller->getAssignedPatients();
$readyForDischarge = $controller->getReadyForDischarge();
$awaitingDischargeConfirmation = array_values(array_filter(
    $readyForDischarge,
    static fn(array $patient): bool => strcasecmp(
        trim((string)($patient['status_name'] ?? '')),
        'Ready for Discharge'
    ) !== 0
));
?>

<div class="mb-8">
    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Dashboard</h1>
    <p class="mt-1 text-sm text-slate-500">Your assigned patients and pending tasks.</p>
</div>

<?php if (!empty($awaitingDischargeConfirmation)): ?>
    <div class="mb-6 bg-white rounded-xl border border-emerald-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-emerald-200 bg-emerald-50 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Ready for Discharge — Awaiting Your Confirmation</h3>
                    <p class="text-xs text-slate-600">
                        These patients have completed service requests. Confirm them to notify nursing staff and the cashier.
                    </p>
                </div>
            </div>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 border border-emerald-200 px-3 py-1 text-xs font-semibold text-emerald-800">
                <?= count($awaitingDischargeConfirmation) ?> patient<?= count($awaitingDischargeConfirmation) === 1 ? '' : 's' ?>
            </span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="text-left px-6 py-3 font-semibold text-slate-700 text-xs uppercase tracking-wide">Patient</th>
                        <th class="text-left px-6 py-3 font-semibold text-slate-700 text-xs uppercase tracking-wide">Room</th>
                        <th class="text-left px-6 py-3 font-semibold text-slate-700 text-xs uppercase tracking-wide">Admitted</th>
                        <th class="text-right px-6 py-3 font-semibold text-slate-700 text-xs uppercase tracking-wide">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach (array_slice($awaitingDischargeConfirmation, 0, 5) as $r): ?>
                        <tr class="hover:bg-emerald-50/40">
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
                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <a href="<?= BASE_URL ?>/index.php?page=doctor-discharge-ready"
                                   class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Confirm Ready
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
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Assigned Admissions</p>
        <p class="mt-2 text-2xl font-bold text-slate-900"><?= $stats['assigned_admissions'] ?></p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Today's Consultations</p>
        <p class="mt-2 text-2xl font-bold text-blue-600"><?= $stats['today_consultations'] ?></p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Pending Requests</p>
        <p class="mt-2 text-2xl font-bold text-amber-600"><?= $stats['pending_requests'] ?></p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Ready for Discharge</p>
        <p class="mt-2 text-2xl font-bold text-emerald-600"><?= $stats['ready_for_discharge'] ?></p>
    </div>
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
        <div>
            <h3 class="text-lg font-semibold text-slate-900">Assigned Patients</h3>
            <p class="text-xs text-slate-500 mt-0.5">Admissions assigned to you</p>
        </div>
        <a href="<?= BASE_URL ?>/index.php?page=doctor-patients" class="text-sm font-medium text-blue-600 hover:text-blue-700">View all →</a>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-6 py-3 font-medium">Patient</th>
                    <th class="text-left px-6 py-3 font-medium">Room</th>
                    <th class="text-left px-6 py-3 font-medium">Chief Complaint</th>
                    <th class="text-left px-6 py-3 font-medium">Admitted</th>
                    <th class="text-right px-6 py-3 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($patients)): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-slate-400">No patients assigned to you.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach (array_slice($patients, 0, 10) as $p): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-3">
                                <p class="font-medium text-slate-900"><?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?></p>
                                <p class="text-xs text-slate-500">#<?= (int)$p['admission_id'] ?> · <?= htmlspecialchars($p['gender_name']) ?></p>
                            </td>
                            <td class="px-6 py-3">
                                <?php if (!empty($p['room_number'])): ?>
                                    <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-medium"
                                          style="background-color: <?= htmlspecialchars($p['admission_status_color'] ?? '#6b7280') ?>1A;
                                                 color: <?= htmlspecialchars($p['admission_status_color'] ?? '#6b7280') ?>;
                                                 border-color: <?= htmlspecialchars($p['admission_status_color'] ?? '#6b7280') ?>40;">
                                        <?= htmlspecialchars($p['room_number']) ?>
                                    </span>
                                    <p class="text-xs text-slate-500 mt-0.5"><?= htmlspecialchars($p['room_type_name'] ?? '') ?></p>
                                <?php else: ?>
                                    <span class="text-xs text-amber-600">No room</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-3 text-slate-600 max-w-md">
                                <p class="truncate"><?= htmlspecialchars($p['chief_complaint'] ?? '—') ?></p>
                            </td>
                            <td class="px-6 py-3 text-slate-600">
                                <?= htmlspecialchars(date('M j, Y', strtotime($p['admission_datetime']))) ?>
                            </td>
                            <td class="px-6 py-3 text-right whitespace-nowrap">
                                <a href="<?= BASE_URL ?>/index.php?page=doctor-diagnosis-record&admission_id=<?= (int)$p['admission_id'] ?>"
                                   class="inline-flex items-center gap-1.5 rounded-lg border border-blue-200 bg-blue-50 px-2.5 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-100">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                    Open
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>