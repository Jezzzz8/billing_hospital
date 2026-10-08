<?php

$departmentId = (int)($_GET['department_id'] ?? 0);

if ($departmentId <= 0) {
    echo '<div class="p-12 text-center text-slate-500">Please select a department.</div>';
    return;
}

$stmt = $pdo->prepare('SELECT department_id, department_name, department_code FROM `department` WHERE department_id = ? LIMIT 1');
$stmt->execute([$departmentId]);
$department = $stmt->fetch();

if (!$department) {
    echo '<div class="p-12 text-center text-slate-500">Department not found.</div>';
    return;
}

$stmt = $pdo->prepare(
    'SELECT sr.request_id, sr.priority, sr.status, sr.request_datetime, sr.instructions,
            a.admission_id, a.chief_complaint,
            p.first_name, p.last_name,
            u.first_name AS doctor_first, u.last_name AS doctor_last,
            COUNT(sri.request_item_id) AS item_count,
            COALESCE(SUM(sri.quantity_requested), 0) AS total_requested
     FROM `service_request` sr
     INNER JOIN `admission` a ON a.admission_id = sr.admission_id
     INNER JOIN `patient` p ON p.patient_id = a.patient_id
     LEFT JOIN `doctor` doc ON doc.doctor_id = sr.doctor_id
     LEFT JOIN `user` u ON u.user_id = doc.user_id
     LEFT JOIN `service_request_item` sri ON sri.request_id = sr.request_id
     WHERE sr.department_id = ?
     GROUP BY sr.request_id
     ORDER BY sr.request_datetime DESC'
);
$stmt->execute([$departmentId]);
$requests = $stmt->fetchAll();
?>

<div class="mb-6">
    <a href="<?= BASE_URL ?>/index.php?page=department"
       class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-600 hover:text-slate-900">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
        </svg>
        Back to departments
    </a>
</div>

<div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
            <?= htmlspecialchars($department['department_name']) ?> — Service Requests
        </h1>
        <p class="mt-1 text-sm text-slate-500">
            <?= count($requests) ?> request(s) routed to this department.
            <?php if (!empty($department['department_code'])): ?>
                Code: <span class="font-mono"><?= htmlspecialchars($department['department_code']) ?></span>
            <?php endif; ?>
        </p>
    </div>
</div>

<?php if (empty($requests)): ?>
    <div class="bg-white rounded-xl border border-slate-200 p-12 text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-100 text-slate-400 mb-4">
            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
        </div>
        <p class="text-lg font-medium text-slate-700">No service requests</p>
        <p class="text-sm text-slate-500 mt-1">No requests have been routed to this department yet.</p>
    </div>
<?php else: ?>
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="text-left px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Request</th>
                        <th class="text-left px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Patient</th>
                        <th class="text-left px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Doctor</th>
                        <th class="text-center px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Priority</th>
                        <th class="text-center px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Items</th>
                        <th class="text-left px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Status</th>
                        <th class="text-left px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Requested</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($requests as $r): ?>
                        <?php
                        $statusClass = match(strtolower($r['status'])) {
                            'pending'     => 'bg-amber-50 text-amber-700 border-amber-100',
                            'in progress' => 'bg-blue-50 text-blue-700 border-blue-100',
                            'completed'   => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                            'cancelled'   => 'bg-slate-100 text-slate-600 border-slate-200',
                            default       => 'bg-slate-100 text-slate-600 border-slate-200',
                        };
                        ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4">
                                <p class="font-semibold text-slate-900">#<?= (int)$r['request_id'] ?></p>
                                <p class="text-xs text-slate-500">Admission #<?= (int)$r['admission_id'] ?></p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="font-medium text-slate-900"><?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?></p>
                                <p class="text-xs text-slate-500 truncate max-w-xs"><?= htmlspecialchars($r['chief_complaint'] ?? '') ?></p>
                            </td>
                            <td class="px-6 py-4 text-slate-600 text-sm">
                                <?php if (!empty($r['doctor_first'])): ?>
                                    Dr. <?= htmlspecialchars($r['doctor_first'] . ' ' . $r['doctor_last']) ?>
                                <?php else: ?>
                                    <span class="text-xs text-slate-400">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-block rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
                                    <?= htmlspecialchars($r['priority']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center text-slate-700">
                                <span class="font-medium"><?= (int)$r['item_count'] ?></span>
                                <span class="text-xs text-slate-500">(<?= (int)$r['total_requested'] ?>)</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-block rounded-full border px-2.5 py-1 text-xs font-medium <?= $statusClass ?>">
                                    <?= htmlspecialchars($r['status']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-slate-600 text-xs">
                                <?= htmlspecialchars(date('M j, Y g:i A', strtotime($r['request_datetime']))) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>