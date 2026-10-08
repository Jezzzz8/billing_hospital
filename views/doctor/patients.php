<?php


require_once __DIR__ . '/../../controllers/DoctorPortalController.php';
require_once __DIR__ . '/../../controllers/DoctorHelper.php';

$doctorId = DoctorHelper::getCurrentDoctorId($pdo);
$controller = new DoctorPortalController($pdo, $doctorId);

$patients = $controller->getAllMyPatients();
$assigned = $controller->getAssignedPatients();
?>

<div class="mb-8">
    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">My Patients</h1>
    <p class="mt-1 text-sm text-slate-500">All patients you have treated or are currently assigned to.</p>
</div>


<div class="mb-8">
    <h2 class="text-lg font-semibold text-slate-900 mb-3">Currently Assigned</h2>
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
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
                    <?php if (empty($assigned)): ?>
                        <tr><td colspan="5" class="px-6 py-8 text-center text-slate-400">No active assignments.</td></tr>
                    <?php else: ?>
                        <?php foreach ($assigned as $p): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-3">
                                    <p class="font-medium text-slate-900"><?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?></p>
                                    <p class="text-xs text-slate-500">#<?= (int)$p['admission_id'] ?></p>
                                </td>
                                <td class="px-6 py-3 text-slate-600">
                                    <?= $p['room_number'] ? htmlspecialchars($p['room_number']) : '<span class="text-xs text-amber-600">No room</span>' ?>
                                </td>
                                <td class="px-6 py-3 text-slate-600 max-w-md truncate"><?= htmlspecialchars($p['chief_complaint'] ?? '—') ?></td>
                                <td class="px-6 py-3 text-slate-600"><?= htmlspecialchars(date('M j, Y', strtotime($p['admission_datetime']))) ?></td>
                                <td class="px-6 py-3 text-right">
                                    <a href="<?= BASE_URL ?>/index.php?page=doctor-diagnosis-record&admission_id=<?= (int)$p['admission_id'] ?>"
                                       class="inline-flex items-center gap-1.5 rounded-lg border border-blue-200 bg-blue-50 px-2.5 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-100">
                                        Open Chart
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


<div>
    <h2 class="text-lg font-semibold text-slate-900 mb-3">Patient History</h2>
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="text-left px-6 py-3 font-medium">Patient</th>
                        <th class="text-left px-6 py-3 font-medium">Contact</th>
                        <th class="text-left px-6 py-3 font-medium">Admissions</th>
                        <th class="text-left px-6 py-3 font-medium">Last Seen</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($patients)): ?>
                        <tr><td colspan="4" class="px-6 py-8 text-center text-slate-400">No patients yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($patients as $p): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-3">
                                    <p class="font-medium text-slate-900"><?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?></p>
                                    <p class="text-xs text-slate-500"><?= htmlspecialchars($p['gender_name']) ?></p>
                                </td>
                                <td class="px-6 py-3">
                                    <p class="text-xs text-slate-700"><?= htmlspecialchars($p['email'] ?? '—') ?></p>
                                    <p class="text-xs text-slate-500"><?= htmlspecialchars($p['contact_number'] ?? '') ?></p>
                                </td>
                                <td class="px-6 py-3">
                                    <span class="inline-block rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">
                                        <?= (int)$p['total_admissions'] ?>
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-slate-600">
                                    <?= $p['last_admission'] ? htmlspecialchars(date('M j, Y', strtotime($p['last_admission']))) : '—' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>