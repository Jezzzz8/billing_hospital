<?php

$departmentId = (int)($_SESSION['user']['department_id'] ?? 0);

if ($departmentId <= 0) {
    echo '<div class="p-12 text-center"><p class="text-lg font-semibold text-rose-700">No department assigned to your account.</p><p class="mt-2 text-sm text-slate-500">Please contact an administrator.</p></div>';
    return;
}

require_once __DIR__ . '/../../controllers/DepartmentController.php';

$controller = new DepartmentController($pdo, $departmentId);
$stats = $controller->getDashboardStats();
$queue = $controller->getQueue();

$stmt = $pdo->prepare('SELECT department_name, department_code FROM `department` WHERE department_id = ? LIMIT 1');
$stmt->execute([$departmentId]);
$dept = $stmt->fetch();
?>

<div data-department-view="dashboard">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
            <?= htmlspecialchars($dept['department_name'] ?? 'Department') ?> Dashboard
        </h1>
        <p class="mt-1 text-sm text-slate-500">
            Overview of service requests assigned to your department.
            <?php if (!empty($dept['department_code'])): ?>
                Code: <span class="font-mono font-medium"><?= htmlspecialchars($dept['department_code']) ?></span>
            <?php endif; ?>
        </p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6 max-w-2xl">
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Pending Requests</p>
            <p class="mt-2 text-2xl font-bold text-amber-600"><?= (int)$stats['pending_requests'] ?></p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Completed</p>
            <p class="mt-2 text-2xl font-bold text-emerald-600"><?= (int)$stats['completed_requests'] ?></p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Total in Queue</p>
            <p class="mt-2 text-2xl font-bold text-slate-900"><?= count($queue) ?></p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-900">Recent Requests</h2>
            <a href="<?= BASE_URL ?>/index.php?page=department-queue" class="text-sm font-medium text-blue-600 hover:text-blue-700">View all →</a>
        </div>
        <?php if (empty($queue)): ?>
            <div class="px-6 py-10 text-center text-slate-500">
                <p class="font-medium">No service requests yet.</p>
                <p class="text-sm mt-1">Requests from doctors will appear here.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-slate-600">
                        <tr>
                            <th class="px-6 py-3 font-medium">Request</th>
                            <th class="px-6 py-3 font-medium">Patient</th>
                            <th class="px-6 py-3 font-medium">Priority</th>
                            <th class="px-6 py-3 font-medium">Status</th>
                            <th class="px-6 py-3 text-right font-medium">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach (array_slice($queue, 0, 10) as $request): ?>
                            <?php $requestStatus = $controller->getRequestStatusPresentation($request); ?>
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-4">
                                    <p class="font-medium text-slate-800">#<?= (int)($request['request_id'] ?? 0) ?></p>
                                    <p class="text-xs text-slate-500"><?= (int)($request['item_count'] ?? 0) ?> item(s)</p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="font-semibold text-slate-900"><?= htmlspecialchars(trim((string)($request['first_name'] ?? '')) . ' ' . trim((string)($request['last_name'] ?? ''))) ?></p>
                                    <p class="text-xs text-slate-500">Admission #<?= (int)($request['admission_id'] ?? 0) ?></p>
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
                                <td class="px-6 py-4 text-right">
                                    <a href="<?= BASE_URL ?>/index.php?page=department-fulfillment&request_id=<?= (int)($request['request_id'] ?? 0) ?>"
                                       class="inline-flex items-center rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-medium text-blue-700 hover:bg-blue-100">
                                        View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>