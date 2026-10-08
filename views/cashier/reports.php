<?php


require_once __DIR__ . '/../../controllers/CashierController.php';

$controller = new CashierController($pdo);
$reports = $controller->getReportsData();

$grandTotal = 0;
foreach ($reports['by_type'] as $t) $grandTotal += (float)$t['total'];
?>

<div class="mb-8">
    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Reports</h1>
    <p class="mt-1 text-sm text-slate-500">Collections and outstanding balances.</p>
</div>


<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">

    <div class="lg:col-span-1">
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200">
                <h3 class="text-base font-semibold text-slate-900">Collections by Payment Type</h3>
            </div>
            <?php if (empty($reports['by_type'])): ?>
                <p class="p-8 text-center text-slate-400 text-sm">No payments yet.</p>
            <?php else: ?>
                <div class="divide-y divide-slate-100">
                    <?php foreach ($reports['by_type'] as $t): ?>
                        <div class="px-6 py-4">
                            <div class="flex items-center justify-between mb-1">
                                <p class="font-medium text-slate-900"><?= htmlspecialchars($t['type_name']) ?></p>
                                <p class="font-semibold text-slate-900">₱<?= number_format((float)$t['total'], 2) ?></p>
                            </div>
                            <div class="flex items-center justify-between text-xs text-slate-500">
                                <span><?= (int)$t['count'] ?> payment(s)</span>
                                <span><?= $grandTotal > 0 ? number_format(((float)$t['total'] / $grandTotal) * 100, 1) : 0 ?>%</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200">
                <h3 class="text-base font-semibold text-slate-900">Daily Collections (Last 7 Days)</h3>
            </div>
            <?php if (empty($reports['daily'])): ?>
                <p class="p-8 text-center text-slate-400 text-sm">No collections in the last 7 days.</p>
            <?php else: ?>
                <div class="p-6">
                    <div class="space-y-3">
                        <?php
                        $maxTotal = 0;
                        foreach ($reports['daily'] as $d) $maxTotal = max($maxTotal, (float)$d['total']);
                        ?>
                        <?php foreach ($reports['daily'] as $d): ?>
                            <div>
                                <div class="flex items-center justify-between text-xs mb-1">
                                    <span class="font-medium text-slate-700">
                                        <?= htmlspecialchars(date('D, M j', strtotime($d['day']))) ?>
                                    </span>
                                    <span class="text-slate-600"><?= (int)$d['count'] ?> payments</span>
                                    <span class="font-semibold text-slate-900">₱<?= number_format((float)$d['total'], 2) ?></span>
                                </div>
                                <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-emerald-500 rounded-full"
                                         style="width: <?= $maxTotal > 0 ? ((float)$d['total'] / $maxTotal) * 100 : 0 ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>


<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-200">
        <h3 class="text-base font-semibold text-slate-900">Top Outstanding Balances</h3>
        <p class="text-xs text-slate-500 mt-0.5">Patients with the highest unpaid amounts</p>
    </div>
    <?php if (empty($reports['top_balances'])): ?>
        <p class="p-8 text-center text-slate-400 text-sm">No outstanding balances.</p>
    <?php else: ?>
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
                    <?php foreach ($reports['top_balances'] as $b): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-3 font-medium text-slate-900">#<?= (int)$b['statement_id'] ?></td>
                            <td class="px-6 py-3 text-slate-700"><?= htmlspecialchars($b['first_name'] . ' ' . $b['last_name']) ?></td>
                            <td class="px-6 py-3 text-right text-slate-700">₱<?= number_format((float)$b['total_amount'], 2) ?></td>
                            <td class="px-6 py-3 text-right text-emerald-700">₱<?= number_format((float)$b['amount_paid'], 2) ?></td>
                            <td class="px-6 py-3 text-right font-semibold text-rose-700">₱<?= number_format((float)$b['balance_amount'], 2) ?></td>
                            <td class="px-6 py-3">
                                <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-medium"
                                      style="background-color: <?= htmlspecialchars($b['color_code'] ?? '#6b7280') ?>1A;
                                             color: <?= htmlspecialchars($b['color_code'] ?? '#6b7280') ?>;
                                             border-color: <?= htmlspecialchars($b['color_code'] ?? '#6b7280') ?>40;">
                                    <?= htmlspecialchars($b['status_name']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>