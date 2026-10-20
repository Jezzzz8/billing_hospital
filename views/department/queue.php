<?php

$departmentId = (int)($_SESSION['user']['department_id'] ?? 0);

if ($departmentId <= 0) {
    echo '<div class="p-12 text-center"><p class="text-lg font-semibold text-rose-700">No department assigned to your account.</p></div>';
    return;
}

require_once __DIR__ . '/../../controllers/DepartmentController.php';
$controller = new DepartmentController($pdo, $departmentId);
$queue = $controller->getQueue();
?>

<div data-department-view="queue">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Department Request Queue</h1>
        <p class="mt-1 text-sm text-slate-500">Review assigned service requests and process them in order.</p>
    </div>

    <?php if (empty($queue)): ?>
        <div class="rounded-xl border border-slate-200 bg-white p-10 text-center text-slate-500">
            <p class="font-medium">No service requests assigned</p>
            <p class="text-sm mt-1">Doctor orders will appear here.</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-slate-600">
                    <tr>
                        <th class="px-6 py-3 font-medium">Patient</th>
                        <th class="px-6 py-3 font-medium">Request</th>
                        <th class="px-6 py-3 font-medium">Priority</th>
                        <th class="px-6 py-3 font-medium">Status</th>
                        <th class="px-6 py-3 font-medium">Items</th>
                        <th class="px-6 py-3 text-right font-medium">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($queue as $request): ?>
                        <?php $requestStatus = $controller->getRequestStatusPresentation($request); ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4">
                                <p class="font-semibold text-slate-900"><?= htmlspecialchars(trim((string)($request['first_name'] ?? '')) . ' ' . trim((string)($request['last_name'] ?? ''))) ?: 'Unknown' ?></p>
                                <p class="text-xs text-slate-500">Admission #<?= (int)($request['admission_id'] ?? 0) ?></p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="font-medium text-slate-800">#<?= (int)($request['request_id'] ?? 0) ?></p>
                                <p class="text-xs text-slate-500"><?= htmlspecialchars((string)($request['chief_complaint'] ?? '')) ?></p>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
                                    <?= htmlspecialchars((string)($request['priority'] ?? 'Routine')) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium <?= htmlspecialchars($requestStatus['class']) ?>">
                                    <?= htmlspecialchars($requestStatus['label']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-slate-600">
                                <span class="font-medium"><?= (int)($request['item_count'] ?? 0) ?></span> item(s)
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="<?= BASE_URL ?>/index.php?page=department-fulfillment&request_id=<?= (int)($request['request_id'] ?? 0) ?>"
                                   class="inline-flex items-center rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-medium text-blue-700 hover:bg-blue-100">
                                    Open
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>