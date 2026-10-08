<?php


require_once __DIR__ . '/../../controllers/NurseController.php';

$controller = new NurseController($pdo);
$records = $controller->getDischargeRecords();
?>

<div class="mb-8">
    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Discharge Records</h1>
    <p class="mt-1 text-sm text-slate-500">History of discharged patients.</p>
</div>

<?php if (empty($records)): ?>
    <div class="bg-white rounded-xl border border-slate-200 p-12 text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-100 text-slate-400 mb-4">
            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
        </div>
        <p class="text-lg font-medium text-slate-700">No discharge records</p>
        <p class="text-sm text-slate-500 mt-1">Discharged patients will appear here.</p>
    </div>
<?php else: ?>
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="text-left px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Patient</th>
                        <th class="text-left px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Room</th>
                        <th class="text-left px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Admitted</th>
                        <th class="text-left px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Discharged</th>
                        <th class="text-left px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Discharged By</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($records as $r): ?>
                        <tr class="hover:bg-slate-50">
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
                            <td class="px-6 py-4 text-slate-600">
                                <?= htmlspecialchars(date('M j, Y g:i A', strtotime($r['discharge_datetime']))) ?>
                            </td>
                            <td class="px-6 py-4 text-slate-600">
                                <?php if (!empty($r['discharged_by_first'])): ?>
                                    <?= htmlspecialchars($r['discharged_by_first'] . ' ' . $r['discharged_by_last']) ?>
                                <?php else: ?>
                                    <span class="text-xs text-slate-400">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>