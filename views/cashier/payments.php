<?php


require_once __DIR__ . '/../../controllers/CashierController.php';

$controller = new CashierController($pdo);


$payments = $pdo->query(
    'SELECT p.payment_id, p.statement_id, p.amount, p.payment_datetime,
            p.transaction_reference, p.notes,
            pt.type_name,
            bs.admission_id,
            pat.first_name, pat.last_name,
            u.first_name AS receiver_first, u.last_name AS receiver_last
     FROM `payment` p
     INNER JOIN `payment_type` pt ON pt.payment_type_id = p.payment_type_id
     INNER JOIN `billing_statement` bs ON bs.statement_id = p.statement_id
     INNER JOIN `admission` a ON a.admission_id = bs.admission_id
     INNER JOIN `patient` pat ON pat.patient_id = a.patient_id
     LEFT JOIN `user` u ON u.user_id = p.received_by_user_id
     ORDER BY p.payment_id DESC'
)->fetchAll();

$totalAmount = 0;
foreach ($payments as $p) $totalAmount += (float)$p['amount'];
?>

<div class="mb-8">
    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Payments</h1>
    <p class="mt-1 text-sm text-slate-500">History of all recorded payments.</p>
</div>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Total Payments</p>
        <p class="mt-2 text-2xl font-bold text-slate-900"><?= count($payments) ?></p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Total Collected</p>
        <p class="mt-2 text-2xl font-bold text-emerald-600">₱<?= number_format($totalAmount, 2) ?></p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Today</p>
        <?php
        $todayTotal = 0;
        foreach ($payments as $p) {
            if (date('Y-m-d', strtotime($p['payment_datetime'])) === date('Y-m-d')) {
                $todayTotal += (float)$p['amount'];
            }
        }
        ?>
        <p class="mt-2 text-2xl font-bold text-blue-600">₱<?= number_format($todayTotal, 2) ?></p>
    </div>
</div>


<div class="bg-white rounded-xl border border-slate-200 p-4 mb-4">
    <div class="flex flex-col sm:flex-row sm:items-end gap-3">
        <div class="flex-1">
            <label class="block text-xs font-medium text-slate-500 mb-1.5">Search</label>
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" id="paymentSearch" placeholder="Search patient name, reference, statement #…"
                       class="w-full rounded-lg border border-slate-300 pl-9 pr-3 py-2 text-sm
                              focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>
    </div>
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="text-left px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Payment #</th>
                    <th class="text-left px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Patient</th>
                    <th class="text-left px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Type</th>
                    <th class="text-left px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Reference</th>
                    <th class="text-left px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Date</th>
                    <th class="text-right px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Amount</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100" id="paymentsTableBody">
                <?php foreach ($payments as $p):
                    $search = strtolower($p['first_name'] . ' ' . $p['last_name'] . ' #' . $p['statement_id'] . ' ' . ($p['transaction_reference'] ?? ''));
                ?>
                    <tr class="hover:bg-slate-50 payment-row" data-search="<?= htmlspecialchars($search) ?>">
                        <td class="px-6 py-3">
                            <p class="font-medium text-slate-900">#<?= (int)$p['payment_id'] ?></p>
                            <p class="text-xs text-slate-500">Statement #<?= (int)$p['statement_id'] ?></p>
                        </td>
                        <td class="px-6 py-3">
                            <p class="font-medium text-slate-900"><?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?></p>
                            <p class="text-xs text-slate-500">Admission #<?= (int)$p['admission_id'] ?></p>
                        </td>
                        <td class="px-6 py-3">
                            <span class="inline-block rounded-full bg-emerald-50 border border-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700">
                                <?= htmlspecialchars($p['type_name']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-3 text-slate-600 text-xs"><?= htmlspecialchars($p['transaction_reference'] ?? '—') ?></td>
                        <td class="px-6 py-3 text-slate-600 text-xs">
                            <?= htmlspecialchars(date('M j, Y g:i A', strtotime($p['payment_datetime']))) ?>
                            <?php if ($p['receiver_first']): ?>
                                <p class="text-slate-400">by <?= htmlspecialchars($p['receiver_first']) ?></p>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-3 text-right font-semibold text-emerald-700 whitespace-nowrap">
                            ₱<?= number_format((float)$p['amount'], 2) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if (empty($payments)): ?>
        <div class="text-center py-16">
            <p class="text-sm text-slate-500">No payments recorded yet.</p>
        </div>
    <?php endif; ?>
</div>

<script>
(function() {
    const search = document.getElementById('paymentSearch');
    const rows = document.querySelectorAll('.payment-row');

    search?.addEventListener('input', function () {
        const q = this.value.toLowerCase().trim();
        rows.forEach(r => {
            r.style.display = (!q || (r.dataset.search || '').includes(q)) ? '' : 'none';
        });
    });
})();
</script>