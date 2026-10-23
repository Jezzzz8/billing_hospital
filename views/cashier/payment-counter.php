<?php

require_once __DIR__ . '/../../controllers/CashierController.php';
require_once __DIR__ . '/../../controllers/ChargeSyncService.php';

$controller = new CashierController($pdo);
$controller->prepareRunningBilling();

$statements = $controller->getUnpaidStatements();
$paymentTypes = $controller->getPaymentTypes();

$totalUnpaid = 0;
foreach ($statements as $s) $totalUnpaid += (float)$s['balance_amount'];

$todayCollected = (float)$pdo->query(
    'SELECT COALESCE(SUM(amount), 0) FROM `payment` WHERE DATE(payment_datetime) = CURDATE()'
)->fetchColumn();

$todayPaymentCount = (int)$pdo->query(
    'SELECT COUNT(*) FROM `payment` WHERE DATE(payment_datetime) = CURDATE()'
)->fetchColumn();
?>

<div class="mb-8">
    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Payment Counter</h1>
    <p class="mt-1 text-sm text-slate-500">
        Review running bills for admitted patients and collect payments.
    </p>
</div>

<div id="counterAlert" class="hidden mb-5 rounded-lg px-4 py-3 text-sm border"></div>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Running / Unpaid Bills</p>
        <p class="mt-2 text-3xl font-bold text-slate-900"><?= count($statements) ?></p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Total Outstanding</p>
        <p class="mt-2 text-2xl font-bold text-rose-600">₱<?= number_format($totalUnpaid, 2) ?></p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Collected Today</p>
        <p class="mt-2 text-2xl font-bold text-emerald-600">₱<?= number_format($todayCollected, 2) ?></p>
        <p class="text-xs text-slate-500 mt-1"><?= $todayPaymentCount ?> payment<?= $todayPaymentCount === 1 ? '' : 's' ?></p>
    </div>
</div>

<div class="bg-white rounded-xl border border-slate-200 p-4 mb-4">
    <div class="flex flex-col lg:flex-row lg:items-end gap-3">
        <div class="flex-1">
            <label class="block text-xs font-medium text-slate-500 mb-1.5">Search</label>
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" id="counterSearch" placeholder="Search patient name, contact, or statement #…"
                       class="w-full rounded-lg border border-slate-300 pl-9 pr-3 py-2.5 text-sm
                              focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>

        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1.5">Status</label>
            <select id="counterStatus"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm bg-white
                           focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <option value="">All unpaid</option>
                <?php
                $seen = [];
                foreach ($statements as $s) {
                    if (isset($seen[$s['status_name']])) continue;
                    $seen[$s['status_name']] = true;
                    echo '<option value="' . htmlspecialchars($s['status_name']) . '">'
                        . htmlspecialchars($s['status_name']) . '</option>';
                }
                ?>
            </select>
        </div>

        <button type="button" id="counterClear"
                class="hidden items-center gap-1.5 rounded-lg px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-100">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
            Clear
        </button>
    </div>

    <div id="counterSummary" class="hidden mt-3 pt-3 border-t border-slate-100 text-xs text-slate-500">
        Showing <strong id="counterFilteredCount" class="text-slate-900">0</strong> of
        <strong id="counterTotalCount" class="text-slate-900">0</strong> statements
    </div>
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="text-left px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Statement</th>
                    <th class="text-left px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Patient</th>
                    <th class="text-right px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Total</th>
                    <th class="text-right px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Paid</th>
                    <th class="text-right px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Balance</th>
                    <th class="text-left px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Due</th>
                    <th class="text-left px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Status</th>
                    <th class="text-right px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($statements as $s):
                    $search = strtolower(
                        $s['first_name'] . ' ' . $s['last_name'] . ' #' . $s['statement_id']
                        . ' ' . ($s['contact_number'] ?? '')
                        . ' ' . ($s['email'] ?? '')
                    );
                    $isOverdue = !empty($s['due_date'])
                        && $s['due_date'] < date('Y-m-d')
                        && (float)$s['balance_amount'] > 0;
                ?>
                    <tr class="hover:bg-slate-50 counter-row"
                        data-statement-id="<?= (int)$s['statement_id'] ?>"
                        data-search="<?= htmlspecialchars($search) ?>"
                        data-status="<?= htmlspecialchars($s['status_name']) ?>">

                        <td class="px-6 py-4">
                            <p class="font-semibold text-slate-900">#<?= (int)$s['statement_id'] ?></p>
                            <p class="text-xs text-slate-500"><?= htmlspecialchars(date('M j, Y', strtotime($s['statement_date']))) ?></p>
                        </td>
                        <td class="px-6 py-4">
                            <p class="font-medium text-slate-900"><?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></p>
                            <p class="text-xs text-slate-500">
                                <?= htmlspecialchars($s['contact_number'] ?? $s['email'] ?? '') ?>
                            </p>
                        </td>
                        <td class="px-6 py-4 text-right text-slate-700 whitespace-nowrap">
                            ₱<?= number_format((float)$s['total_amount'], 2) ?>
                        </td>
                        <td class="px-6 py-4 text-right text-emerald-700 whitespace-nowrap">
                            ₱<?= number_format((float)$s['amount_paid'], 2) ?>
                        </td>
                        <td class="px-6 py-4 text-right font-semibold text-rose-700 whitespace-nowrap">
                            ₱<?= number_format((float)$s['balance_amount'], 2) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if (!empty($s['due_date'])): ?>
                                <span class="text-xs <?= $isOverdue ? 'text-rose-700 font-semibold' : 'text-slate-600' ?>">
                                    <?= htmlspecialchars(date('M j, Y', strtotime($s['due_date']))) ?>
                                </span>
                                <?php if ($isOverdue): ?>
                                    <p class="text-xs text-rose-500">Overdue</p>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-xs text-slate-400">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-medium"
                                  style="background-color: <?= htmlspecialchars($s['color_code'] ?? '#6b7280') ?>1A;
                                         color: <?= htmlspecialchars($s['color_code'] ?? '#6b7280') ?>;
                                         border-color: <?= htmlspecialchars($s['color_code'] ?? '#6b7280') ?>40;">
                                <span class="w-1.5 h-1.5 rounded-full" style="background-color: <?= htmlspecialchars($s['color_code'] ?? '#6b7280') ?>"></span>
                                <?= htmlspecialchars($s['status_name']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right whitespace-nowrap">
                            <div class="inline-flex items-center gap-2">
                                <a target="_blank"
                                   href="<?= BASE_URL ?>/index.php?page=cashier-receipt&id=<?= (int)$s['statement_id'] ?>"
                                   class="rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">View / Print</a>
                                <button type="button"
                                        <?php if ((float)$s['balance_amount'] > 0): ?>
                                            <button type="button"
                                                    class="collect-btn inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700"
                                                    data-statement-id="<?= (int)$s['statement_id'] ?>">Collect</button>
                                        <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div id="counterEmpty" class="hidden text-center py-16">
        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-slate-100 text-slate-400 mb-3">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
        </div>
        <p class="text-sm font-medium text-slate-700">No running or unpaid bills match your filters</p>
        <p class="text-xs text-slate-500 mt-1">Try adjusting your search or wait for a patient admission.</p>
    </div>
</div>

<div id="collectModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/60" data-close-collect></div>

    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md max-h-[95vh] flex flex-col">

        <div class="flex items-center justify-between px-6 py-5 border-b border-slate-200">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Collect Payment</h3>
                    <p id="collectSubtitle" class="text-xs text-slate-500">Select a payment type and enter the amount.</p>
                </div>
            </div>
            <button type="button" data-close-collect class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form id="collectForm" class="flex-1 overflow-y-auto p-6 space-y-4">

            <div class="bg-slate-50 border border-slate-200 rounded-lg p-4 space-y-2">
                <div class="flex items-center justify-between text-xs">
                    <span class="text-slate-500">Statement</span>
                    <span id="colStatement" class="font-semibold text-slate-900">#—</span>
                </div>
                <div class="flex items-center justify-between text-xs">
                    <span class="text-slate-500">Patient</span>
                    <span id="colPatient" class="font-medium text-slate-800">—</span>
                </div>
                <div class="flex items-center justify-between text-xs">
                    <span class="text-slate-500">Contact</span>
                    <span id="colContact" class="text-slate-700">—</span>
                </div>
                <div class="border-t border-slate-200 my-2"></div>
                <div class="flex items-center justify-between text-xs">
                    <span class="text-slate-500">Subtotal</span>
                    <span id="colSubtotal" class="text-slate-700">₱0.00</span>
                </div>
                <div class="flex items-center justify-between text-xs">
                    <span class="text-slate-500">Tax (12%)</span>
                    <span id="colTax" class="text-slate-700">₱0.00</span>
                </div>
                <div class="flex items-center justify-between text-xs">
                    <span class="text-slate-500">PhilHealth Deduction</span>
                    <span id="colPhilhealth" class="text-emerald-700">₱0.00</span>
                </div>
                <div class="flex items-center justify-between text-xs">
                    <span class="text-slate-500">Insurance Coverage</span>
                    <span id="colInsurance" class="text-emerald-700">₱0.00</span>
                </div>
                <div class="flex items-center justify-between text-xs">
                    <span class="text-slate-500">HMO Discount</span>
                    <span id="colHmo" class="text-emerald-700">₱0.00</span>
                </div>
                <div class="flex items-center justify-between text-xs">
                    <span class="text-slate-500">Government Discount</span>
                    <span id="colGovernment" class="text-emerald-700">₱0.00</span>
                </div>
                <div class="flex items-center justify-between text-xs">
                    <span class="text-slate-500">Total</span>
                    <span id="colTotal" class="text-slate-700">₱0.00</span>
                </div>
                <div class="flex items-center justify-between text-xs">
                    <span class="text-slate-500">Already Paid</span>
                    <span id="colPaid" class="text-emerald-700">₱0.00</span>
                </div>
                <div class="flex items-center justify-between border-t border-slate-200 pt-2">
                    <span class="text-sm font-semibold text-slate-900">Balance Due</span>
                    <span id="colBalance" class="text-xl font-bold text-rose-700">₱0.00</span>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Payment Type</label>
                <select id="col_payment_type_id" required
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm bg-white
                               focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">— Select type —</option>
                    <?php foreach ($paymentTypes as $pt): ?>
                        <option value="<?= (int)$pt['payment_type_id'] ?>"><?= htmlspecialchars($pt['type_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="block text-sm font-medium text-slate-700">Amount (₱)</label>
                    <button type="button" id="colFullBtn" class="text-xs font-medium text-blue-600 hover:text-blue-700">
                        Pay full balance
                    </button>
                </div>
                <input type="text" id="col_amount" required inputmode="decimal" autocomplete="off"
                       class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-lg font-semibold
                              focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <p id="colAmountHint" class="mt-1 text-xs text-slate-500"></p>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Reference</label>
                <input type="text" id="col_reference" readonly
                       value="Will be generated automatically"
                       class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm font-mono text-slate-500 cursor-not-allowed">
                <p class="mt-1 text-xs text-slate-500">Reference is generated by the system on save.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">
                    Notes <span class="text-slate-400 font-normal">(optional)</span>
                </label>
                <input type="text" id="col_notes"
                       class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm
                              focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div class="bg-blue-50 border border-blue-100 rounded-lg p-4 space-y-1.5">
                <div class="flex justify-between text-xs text-slate-600">
                    <span>Balance before</span>
                    <span id="colBalanceBefore" class="font-medium text-slate-900">₱0.00</span>
                </div>
                <div class="flex justify-between text-xs text-slate-600">
                    <span>This payment</span>
                    <span id="colThisPayment" class="font-medium text-emerald-700">₱0.00</span>
                </div>
                <div class="flex justify-between border-t border-blue-100 pt-2">
                    <span class="text-sm font-semibold text-slate-900">Balance after</span>
                    <span id="colBalanceAfter" class="text-lg font-bold text-blue-700">₱0.00</span>
                </div>
            </div>
        </form>

        <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 rounded-b-2xl flex items-center justify-end gap-3">
            <button type="button" data-close-collect
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Cancel
            </button>
            <button type="button" id="confirmCollectBtn"
                    class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-60">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
                <span id="confirmCollectLabel">Record Payment</span>
            </button>
        </div>
    </div>
</div>

<div id="receiptModal" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/60" data-close-receipt></div>

    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
        <div class="text-center mb-4">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-emerald-50 text-emerald-600 mb-3">
                <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h3 class="text-lg font-bold text-slate-900">Payment Recorded</h3>
            <p id="receiptSubtitle" class="text-sm text-slate-500 mt-1">—</p>
        </div>

        <div class="bg-slate-50 rounded-lg p-4 space-y-1.5 text-sm">
            <div class="flex justify-between">
                <span class="text-slate-500">Reference</span>
                <span id="receiptReference" class="font-mono text-slate-900 text-xs">—</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">Amount</span>
                <span id="receiptAmount" class="font-semibold text-slate-900">₱0.00</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">New Balance</span>
                <span id="receiptNewBalance" class="font-bold text-emerald-700">₱0.00</span>
            </div>
        </div>

        <div class="mt-6 flex items-center justify-end gap-3">
            <button type="button" data-close-receipt
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Close
            </button>
            <a id="receiptPrintBtn" href="#" target="_blank"
               class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Print Receipt
            </a>
        </div>
    </div>
</div>

<script>
(function () {
    const baseUrl = document.body.dataset.baseUrl;
    const alertEl = document.getElementById('counterAlert');
    const rows = document.querySelectorAll('.counter-row');
    const searchInput = document.getElementById('counterSearch');
    const statusFilter = document.getElementById('counterStatus');
    const clearBtn = document.getElementById('counterClear');
    const summaryEl = document.getElementById('counterSummary');
    const filteredCount = document.getElementById('counterFilteredCount');
    const totalCount = document.getElementById('counterTotalCount');
    const emptyEl = document.getElementById('counterEmpty');

    const collectModal = document.getElementById('collectModal');
    const receiptModal = document.getElementById('receiptModal');
    const collectForm = document.getElementById('collectForm');

    let currentStatementId = null;
    let paymentInFlight = false;

    function parseCurrency(value) {
        const normalized = String(value ?? '').replace(/,/g, '').trim();
        return normalized === '' ? 0 : Number(normalized);
    }

    function formatCurrency(value) {
        return (Number(value) || 0).toLocaleString('en-PH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
    }

    function formatCurrencyInput(input) {
        const start = input.selectionStart;
        const beforeCaret = input.value.slice(0, start ?? input.value.length).replace(/,/g, '');
        let raw = input.value.replace(/,/g, '').replace(/[^\d.]/g, '');
        const decimalIndex = raw.indexOf('.');
        if (decimalIndex !== -1) {
            raw = raw.slice(0, decimalIndex + 1) + raw.slice(decimalIndex + 1).replace(/\./g, '').slice(0, 2);
        }
        const [integerPart, decimalPart] = raw.split('.');
        const groupedInteger = (integerPart || '').replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        const formatted = decimalIndex === -1
            ? groupedInteger
            : `${groupedInteger || '0'}.${decimalPart ?? ''}`;
        input.value = formatted;

        if (start !== null) {
            const charactersBeforeCaret = beforeCaret.length;
            let position = 0;
            let counted = 0;
            while (position < formatted.length && counted < charactersBeforeCaret) {
                if (formatted[position] !== ',') counted++;
                position++;
            }
            input.setSelectionRange(position, position);
        }
    }

    function bindCurrencyInput(input) {
        input.addEventListener('input', () => {
            formatCurrencyInput(input);
            if (input.id === 'col_amount') updateBalancePreview();
        });
        input.addEventListener('blur', () => {
            const value = parseCurrency(input.value);
            if (Number.isFinite(value) && input.value.trim() !== '') {
                input.value = formatCurrency(value);
            }
        });
        input.addEventListener('focus', () => {
            input.value = input.value.replace(/,/g, '');
        });
    }

    const paymentAmountInput = document.getElementById('col_amount');
    bindCurrencyInput(paymentAmountInput);

    function showAlert(type, msg) {
        if (!alertEl) return;
        alertEl.className = 'mb-5 rounded-lg px-4 py-3 text-sm border ' +
            (type === 'success'
                ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
                : 'border-rose-200 bg-rose-50 text-rose-800');
        alertEl.textContent = msg;
        alertEl.classList.remove('hidden');
        alertEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function filterRows() {
        const q = (searchInput?.value || '').toLowerCase().trim();
        const status = statusFilter?.value || '';
        let visible = 0;

        rows.forEach(row => {
            const search = row.dataset.search || '';
            const rowStatus = row.dataset.status || '';
            const matchSearch = !q || search.includes(q);
            const matchStatus = !status || rowStatus === status;

            if (matchSearch && matchStatus) {
                row.style.display = '';
                visible++;
            } else {
                row.style.display = 'none';
            }
        });

        if (q || status) {
            summaryEl.classList.remove('hidden');
            clearBtn.classList.remove('hidden');
            clearBtn.classList.add('flex');
            filteredCount.textContent = visible;
            totalCount.textContent = rows.length;
        } else {
            summaryEl.classList.add('hidden');
            clearBtn.classList.add('hidden');
            clearBtn.classList.remove('flex');
        }

        emptyEl.classList.toggle('hidden', visible > 0);
    }

    searchInput?.addEventListener('input', filterRows);
    statusFilter?.addEventListener('change', filterRows);
    clearBtn?.addEventListener('click', () => {
        searchInput.value = '';
        statusFilter.value = '';
        filterRows();
    });

    function openCollectModal(id) {
        currentStatementId = id;
        collectModal.classList.remove('hidden');
        loadStatementSummary(id);
    }

    function closeCollectModal() {
        collectModal.classList.add('hidden');
        currentStatementId = null;
        collectForm.reset();
        document.getElementById('colAmountHint').textContent = '';
        document.getElementById('colBalanceBefore').textContent = '₱0.00';
        document.getElementById('colThisPayment').textContent = '₱0.00';
        document.getElementById('colBalanceAfter').textContent = '₱0.00';
    }

    async function loadStatementSummary(id) {
        try {
            const res = await axios.get(`${baseUrl}/api/cashier/get-statement-summary.php?id=${id}`);
            if (!res.data.success) {
                showAlert('error', res.data.message || 'Could not load statement.');
                closeCollectModal();
                return;
            }
            const s = res.data.statement;
            document.getElementById('collectSubtitle').textContent = 'Statement #' + s.statement_id;
            document.getElementById('colStatement').textContent = '#' + s.statement_id;
            document.getElementById('colPatient').textContent = `${s.first_name} ${s.last_name}`;
            document.getElementById('colContact').textContent = s.contact_number || '—';
            document.getElementById('colSubtotal').textContent = '₱' + formatCurrency(s.subtotal_amount);
            document.getElementById('colTax').textContent = '₱' + formatCurrency(s.tax_amount);
            document.getElementById('colPhilhealth').textContent = '₱' + formatCurrency(s.philhealth_deduction || 0);
            document.getElementById('colInsurance').textContent = '₱' + formatCurrency(s.insurance_coverage_amount || 0);
            document.getElementById('colHmo').textContent = '₱' + formatCurrency(s.hmo_discount || 0);
            document.getElementById('colGovernment').textContent = '₱' + formatCurrency(s.government_discount || 0);
            document.getElementById('colTotal').textContent = '₱' + formatCurrency(s.total_amount);
            document.getElementById('colPaid').textContent = '₱' + formatCurrency(s.amount_paid);
            document.getElementById('colBalance').textContent = '₱' + formatCurrency(s.balance_amount);
            document.getElementById('colBalanceBefore').textContent = '₱' + formatCurrency(s.balance_amount);
            paymentAmountInput.dataset.maxBalance = Number(s.balance_amount).toFixed(2);
            paymentAmountInput.value = formatCurrency(s.balance_amount);
            document.getElementById('colAmountHint').textContent =
                'Maximum: ₱' + formatCurrency(s.balance_amount);
            updateBalancePreview();
        } catch (err) {
            showAlert('error', err.response?.data?.message || 'Could not load statement.');
            closeCollectModal();
        }
    }

    function updateBalancePreview() {
        const before = parseFloat(
            document.getElementById('colBalanceBefore').textContent.replace(/[^0-9.-]/g, '')
        ) || 0;
        const amount = parseCurrency(paymentAmountInput.value);
        const after = Math.max(0, before - amount);

        document.getElementById('colThisPayment').textContent = '₱' + formatCurrency(amount);
        document.getElementById('colBalanceAfter').textContent = '₱' + formatCurrency(after);
    }

    document.getElementById('colFullBtn')?.addEventListener('click', () => {
        const balance = parseFloat(
            document.getElementById('colBalanceBefore').textContent.replace(/[^0-9.-]/g, '')
        ) || 0;
        paymentAmountInput.value = formatCurrency(balance);
        updateBalancePreview();
    });

    document.querySelectorAll('.collect-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            openCollectModal(this.dataset.statementId);
        });
    });

    collectModal.querySelectorAll('[data-close-collect]').forEach(el => {
        el.addEventListener('click', closeCollectModal);
    });

    document.getElementById('confirmCollectBtn').addEventListener('click', async function () {
        if (paymentInFlight) return;

        if (!currentStatementId) return;

        const typeId = document.getElementById('col_payment_type_id').value;
        const amount = parseCurrency(paymentAmountInput.value);

        if (!typeId) {
            showAlert('error', 'Please select a payment type.');
            return;
        }
        if (amount <= 0) {
            showAlert('error', 'Please enter a valid amount.');
            return;
        }
        const maxBalance = Number(paymentAmountInput.dataset.maxBalance || 0);
        if (!Number.isFinite(amount) || amount > maxBalance) {
            showAlert('error', 'Payment amount cannot exceed the remaining balance.');
            return;
        }

        paymentInFlight = true;

        const btn = this;
        const label = document.getElementById('confirmCollectLabel');
        btn.disabled = true;
        label.textContent = 'Recording…';

        try {
            const res = await axios.post(
                `${baseUrl}/api/cashier/add-payment.php?id=${currentStatementId}`,
                {
                    payment_type_id: typeId,
                    amount: amount,
                    notes: document.getElementById('col_notes').value
                },
                { headers: { 'Content-Type': 'application/json' } }
            );

            if (res.data.success) {
                const statementId = currentStatementId;
                closeCollectModal();
                showReceipt(res.data, statementId);
                setTimeout(() => location.reload(), 2200);
            }
        } catch (err) {
            showAlert('error', err.response?.data?.message || 'Could not record payment.');
        } finally {
            btn.disabled = false;
            label.textContent = 'Record Payment';
            paymentInFlight = false;
        }
    });

    function showReceipt(data, statementId) {
        if (!statementId) {
            showAlert('error', 'Payment was recorded, but its statement ID is unavailable for printing.');
            return;
        }
        document.getElementById('receiptSubtitle').textContent =
            'Statement #' + statementId + ' · ' + (data.reference || '');
        document.getElementById('receiptReference').textContent = data.reference || '—';
        document.getElementById('receiptAmount').textContent =
            '₱' + formatCurrency(data.amount_paid ? data.amount_paid : 0);
        document.getElementById('receiptNewBalance').textContent =
            '₱' + formatCurrency(data.new_balance || 0);
        document.getElementById('receiptPrintBtn').href =
            `${baseUrl}/index.php?page=cashier-receipt&id=${statementId}`;
        receiptModal.classList.remove('hidden');
    }

    receiptModal.querySelectorAll('[data-close-receipt]').forEach(el => {
        el.addEventListener('click', () => receiptModal.classList.add('hidden'));
    });

    document.getElementById('col_payment_type_id')?.focus();
})();
</script>