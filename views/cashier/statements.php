<?php

require_once __DIR__ . '/../../controllers/CashierController.php';
require_once __DIR__ . '/../../controllers/ChargeSyncService.php';

$controller   = new CashierController($pdo);
$controller->prepareRunningBilling();
$admissionsOpen = [];

$statements     = $controller->getAllStatements();
$statuses       = $controller->getBillingStatuses();
$paymentTypes   = $controller->getPaymentTypes();
$totalBilled = 0; $totalPaid = 0; $totalBalance = 0;
foreach ($statements as $s) {
    $totalBilled  += (float)$s['total_amount'];
    $totalPaid    += (float)$s['amount_paid'];
    $totalBalance += (float)$s['balance_amount'];
}
?>

<div id="cashierData"
     class="hidden"
     data-payment-types='<?= htmlspecialchars(json_encode(array_map(fn($p) => [
        'payment_type_id' => (int)$p['payment_type_id'],
        'type_name'       => $p['type_name'],
     ], $paymentTypes)), ENT_QUOTES, "UTF-8") ?>'></div>

<div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Billing Statements</h1>
        <p class="mt-1 text-sm text-slate-500">Review statements, sync charges from admissions, and record payments.</p>
    </div>
    <p class="text-sm text-slate-500">Statements are created automatically when admissions are opened.</p>
</div>

<div id="alert" class="hidden mb-5 rounded-lg px-4 py-3 text-sm border"></div>

<div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Statements</p>
        <p class="mt-2 text-2xl font-bold text-slate-900"><?= count($statements) ?></p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Total Billed</p>
        <p class="mt-2 text-xl font-bold text-slate-900">₱<?= number_format($totalBilled, 2) ?></p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Total Paid</p>
        <p class="mt-2 text-xl font-bold text-emerald-600">₱<?= number_format($totalPaid, 2) ?></p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Outstanding</p>
        <p class="mt-2 text-xl font-bold text-rose-600">₱<?= number_format($totalBalance, 2) ?></p>
    </div>
</div>

<div class="bg-white rounded-xl border border-slate-200 p-4 mb-4">
    <div class="flex flex-col lg:flex-row lg:items-end gap-3">
        <div class="flex-1">
            <label class="block text-xs font-medium text-slate-500 mb-1.5">Search</label>
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" id="filterSearch" placeholder="Search patient name, statement #…"
                       class="w-full rounded-lg border border-slate-300 pl-9 pr-3 py-2 text-sm
                              focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1.5">Status</label>
            <select id="filterStatus"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm bg-white
                           focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <option value="">All statuses</option>
                <?php foreach ($statuses as $s): ?>
                    <option value="<?= htmlspecialchars($s['status_name']) ?>"><?= htmlspecialchars($s['status_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="button" id="filterClear"
                class="hidden items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
            Clear
        </button>
    </div>
    <div id="filterSummary" class="hidden mt-3 pt-3 border-t border-slate-100 text-xs text-slate-500">
        Showing <strong id="filteredCount" class="text-slate-900">0</strong> of <strong id="totalCount" class="text-slate-900">0</strong> statements
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
                    <th class="text-left px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Status</th>
                    <th class="text-right px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($statements as $s):
                    $search = strtolower($s['first_name'] . ' ' . $s['last_name'] . ' #' . $s['statement_id'] . ' ' . $s['status_name']);
                ?>
                    <tr class="hover:bg-slate-50 statement-row"
                        data-statement-id="<?= (int)$s['statement_id'] ?>"
                        data-search="<?= htmlspecialchars($search) ?>"
                        data-status="<?= htmlspecialchars($s['status_name']) ?>">

                        <td class="px-6 py-4">
                            <p class="font-semibold text-slate-900">#<?= (int)$s['statement_id'] ?></p>
                            <p class="text-xs text-slate-500"><?= htmlspecialchars(date('M j, Y', strtotime($s['statement_date']))) ?></p>
                        </td>
                        <td class="px-6 py-4">
                            <p class="font-medium text-slate-900"><?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></p>
                            <p class="text-xs text-slate-500">Admission #<?= (int)$s['admission_id'] ?></p>
                        </td>
                        <td class="px-6 py-4 text-right font-medium text-slate-900 whitespace-nowrap">
                            ₱<?= number_format((float)$s['total_amount'], 2) ?>
                        </td>
                        <td class="px-6 py-4 text-right text-emerald-700 whitespace-nowrap">
                            ₱<?= number_format((float)$s['amount_paid'], 2) ?>
                        </td>
                        <td class="px-6 py-4 text-right font-semibold whitespace-nowrap
                                   <?= (float)$s['balance_amount'] > 0 ? 'text-rose-700' : 'text-slate-400' ?>">
                            ₱<?= number_format((float)$s['balance_amount'], 2) ?>
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
                            <div class="inline-flex items-center gap-1.5">
                                <button type="button"
                                        class="manage-btn inline-flex items-center gap-1.5 rounded-lg border border-blue-200 bg-blue-50 px-2.5 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-100"
                                        data-statement-id="<?= (int)$s['statement_id'] ?>">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                    Manage
                                </button>

                                <?php if ((float)$s['balance_amount'] > 0 && (int)$s['is_paid_status'] === 0): ?>
                                    <button type="button"
                                            class="collect-btn inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700"
                                            data-statement-id="<?= (int)$s['statement_id'] ?>">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1"/>
                                        </svg>
                                        Collect
                                    </button>
                                <?php endif; ?>

                                <a href="<?= BASE_URL ?>/index.php?page=cashier-receipt&id=<?= (int)$s['statement_id'] ?>"
                                   target="_blank"
                                   class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                    </svg>
                                    Print
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div id="emptyState" class="hidden text-center py-16">
        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-slate-100 text-slate-400 mb-3">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
        </div>
        <p class="text-sm font-medium text-slate-700">No statements match your filters</p>
    </div>
</div>

<div id="newStatementModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/60" data-close-new-statement></div>

    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md max-h-[90vh] flex flex-col">

        <div class="flex items-center justify-between px-6 py-5 border-b border-slate-200">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-slate-900">New Billing Statement</h3>
                    <p class="text-xs text-slate-500">Select a discharged admission to prepare the final bill.</p>
                </div>
            </div>
            <button type="button" data-close-new-statement
                    class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form id="newStatementForm" class="flex-1 overflow-y-auto p-6 space-y-4">

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">
                    Admission <span class="text-rose-500">*</span>
                </label>
                <select id="new_admission_id" required
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm bg-white
                               focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">— Select a patient —</option>
                    <?php foreach ($admissionsOpen as $a): ?>
                        <option value="<?= (int)$a['admission_id'] ?>">
                            #<?= (int)$a['admission_id'] ?> —
                            <?= htmlspecialchars($a['first_name'] . ' ' . $a['last_name']) ?>
                            (<?= htmlspecialchars(date('M j, Y', strtotime($a['admission_datetime']))) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <p id="newAdmissionEmpty" class="mt-1.5 text-xs text-amber-600 <?= empty($admissionsOpen) ? '' : 'hidden' ?>">
                    No discharged patients are waiting for a statement right now.
                </p>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Due Date</label>
                    <input type="date" id="new_due_date"
                           class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm
                                  focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">
                        Insurance (₱)
                    </label>
                    <input type="number" id="new_insurance_coverage_amount" min="0" step="0.01" placeholder="0.00"
                           class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm
                                  focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">PhilHealth Deduction (₱)</label>
                    <input type="number" id="new_philhealth_deduction" min="0" step="0.01" placeholder="0.00"
                           class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">HMO Discount (₱)</label>
                    <input type="number" id="new_hmo_discount" min="0" step="0.01" placeholder="0.00"
                           class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Government Discount (₱)</label>
                <input type="number" id="new_government_discount" min="0" step="0.01" placeholder="0.00"
                       class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">
                    Notes <span class="text-slate-400 font-normal">(optional)</span>
                </label>
                <textarea id="new_notes" rows="2"
                          class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm
                                 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
            </div>

            <p class="text-xs text-slate-500 bg-slate-50 border border-slate-200 rounded-lg p-3">
                Charges from room assignments, service requests, and doctor consultations will be
                <strong>pulled in automatically</strong> when you save.
            </p>
        </form>

        <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 rounded-b-2xl flex items-center justify-end gap-3">
            <button type="button" data-close-new-statement
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Cancel
            </button>
            <button type="submit" form="newStatementForm" id="saveNewStatementBtn"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60">
                <span id="saveNewStatementLabel">Create Statement</span>
            </button>
        </div>
    </div>
</div>

<div id="manageModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/60" data-close-manage></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-5xl max-h-[95vh] flex flex-col">

        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
            <div>
                <h3 id="manageTitle" class="text-base font-semibold text-slate-900">Statement Details</h3>
                <p id="manageSubtitle" class="text-xs text-slate-500"></p>
            </div>
            <button type="button" data-close-manage class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="border-b border-slate-200 px-6">
            <nav class="flex gap-1 -mb-px overflow-x-auto">
                <button type="button" class="manage-tab-btn px-4 py-3 text-sm font-medium border-b-2 border-blue-600 text-blue-600 whitespace-nowrap" data-mtab="charges">Charges</button>
                <button type="button" class="manage-tab-btn px-4 py-3 text-sm font-medium border-b-2 border-transparent text-slate-500 hover:text-slate-700 whitespace-nowrap" data-mtab="payments">Payments</button>
                <button type="button" class="manage-tab-btn px-4 py-3 text-sm font-medium border-b-2 border-transparent text-slate-500 hover:text-slate-700 whitespace-nowrap" data-mtab="rooms">Rooms</button>
            </nav>
        </div>

        <div class="flex-1 overflow-y-auto p-6">

            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-5">
                <div class="bg-slate-50 border border-slate-200 rounded-lg p-3">
                    <p class="text-xs text-slate-500">Subtotal</p>
                    <p id="sumSubtotal" class="text-lg font-bold text-slate-900">₱0.00</p>
                </div>
                <div class="bg-slate-50 border border-slate-200 rounded-lg p-3">
                    <p class="text-xs text-slate-500">Tax (12%)</p>
                    <p id="sumTax" class="text-lg font-bold text-slate-900">₱0.00</p>
                </div>
                <div class="bg-slate-50 border border-slate-200 rounded-lg p-3">
                    <p class="text-xs text-slate-500">Total</p>
                    <p id="sumTotal" class="text-lg font-bold text-slate-900">₱0.00</p>
                </div>
                <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-3">
                    <p class="text-xs text-emerald-600">Paid</p>
                    <p id="sumPaid" class="text-lg font-bold text-emerald-700">₱0.00</p>
                </div>
                <div class="bg-rose-50 border border-rose-200 rounded-lg p-3">
                    <p class="text-xs text-rose-600">Balance</p>
                    <p id="sumBalance" class="text-lg font-bold text-rose-700">₱0.00</p>
                </div>
            </div>

            <section class="mb-5 rounded-lg border border-slate-200 p-4">
                <h4 class="mb-3 text-sm font-semibold text-slate-900">Deductions</h4>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <label class="text-xs font-medium text-slate-600">PhilHealth (₱)
                        <input type="number" id="managePhilhealthDeduction" min="0" step="0.01"
                               class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900">
                    </label>
                    <label class="text-xs font-medium text-slate-600">Insurance Coverage (₱)
                        <input type="number" id="manageInsuranceCoverage" min="0" step="0.01"
                               class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900">
                    </label>
                    <label class="text-xs font-medium text-slate-600">HMO Discount (₱)
                        <input type="number" id="manageHmoDiscount" min="0" step="0.01"
                               class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900">
                    </label>
                    <label class="text-xs font-medium text-slate-600">Government Discount (₱)
                        <input type="number" id="manageGovernmentDiscount" min="0" step="0.01"
                               class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900">
                    </label>
                </div>
                <button type="button" id="saveDeductionsBtn"
                        class="mt-3 rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700 disabled:opacity-60">
                    Save Deductions
                </button>
            </section>

            <div class="manage-tab-panel" data-mpanel="charges">
                <section id="awaitingReceiptPanel" class="mb-4 hidden rounded-lg border border-amber-200 bg-amber-50 p-4">
                    <h4 class="text-sm font-semibold text-amber-900">Awaiting nursing receipt — not billed yet</h4>
                    <p class="mt-1 text-xs text-amber-800">Department-fulfilled quantities appear here until nursing records receipt. They are not included in this statement's charges or total.</p>
                    <div class="mt-3 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="text-left text-xs uppercase text-amber-900">
                                <tr><th class="py-2 pr-4">Item</th><th class="py-2 pr-4">Category / Department</th><th class="py-2 pr-4 text-right">Waiting</th><th class="py-2 text-right">Unit Price</th></tr>
                            </thead>
                            <tbody id="awaitingReceiptBody" class="divide-y divide-amber-100"></tbody>
                        </table>
                    </div>
                </section>
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-sm font-semibold text-slate-900">Charges</h4>
                    <div class="flex items-center gap-2">
                        <button type="button" id="syncChargesBtn"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-violet-200 bg-violet-50 px-3 py-1.5 text-xs font-medium text-violet-700 hover:bg-violet-100">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Recompute Charges
                        </button>
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="text-left px-4 py-2.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Item</th>
                                <th class="text-left px-4 py-2.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Category</th>
                                <th class="text-center px-4 py-2.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Qty</th>
                                <th class="text-right px-4 py-2.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Unit Price</th>
                                <th class="text-right px-4 py-2.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Total</th>
                                <th class="text-right px-4 py-2.5"></th>
                            </tr>
                        </thead>
                        <tbody id="chargesBody" class="divide-y divide-slate-100"></tbody>
                    </table>
                    <p id="noCharges" class="hidden text-center text-sm text-slate-400 py-8">No charges yet. Click <strong>Recompute Charges</strong> to sync room and fulfilled-service charges.</p>
                </div>
            </div>

            <div class="manage-tab-panel hidden" data-mpanel="payments">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-sm font-semibold text-slate-900">Payments</h4>
                    <button type="button" id="addPaymentBtn"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-medium text-emerald-700 hover:bg-emerald-100">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                        Record Payment
                    </button>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="text-left px-4 py-2.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Type</th>
                                <th class="text-left px-4 py-2.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Reference</th>
                                <th class="text-left px-4 py-2.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Date</th>
                                <th class="text-right px-4 py-2.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Amount</th>
                                <th class="text-right px-4 py-2.5"></th>
                            </tr>
                        </thead>
                        <tbody id="paymentsBody" class="divide-y divide-slate-100"></tbody>
                    </table>
                    <p id="noPayments" class="hidden text-center text-sm text-slate-400 py-8">No payments yet.</p>
                </div>
            </div>

            <div class="manage-tab-panel hidden" data-mpanel="rooms">
                <h4 class="text-sm font-semibold text-slate-900 mb-3">Room History</h4>
                <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="text-left px-4 py-2.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Room</th>
                                <th class="text-left px-4 py-2.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Type</th>
                                <th class="text-left px-4 py-2.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Start</th>
                                <th class="text-left px-4 py-2.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">End</th>
                                <th class="text-right px-4 py-2.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Daily Rate</th>
                            </tr>
                        </thead>
                        <tbody id="roomsBody" class="divide-y divide-slate-100"></tbody>
                    </table>
                    <p id="noRooms" class="hidden text-center text-sm text-slate-400 py-8">No room assignments.</p>
                </div>
            </div>
        </div>

        <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 rounded-b-2xl flex items-center justify-end gap-3">
            <button type="button" data-close-manage
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Close
            </button>
        </div>
    </div>
</div>

<div id="paymentModal" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/60" data-close-payment></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="flex items-center justify-between px-6 py-5 border-b border-slate-200">
            <h3 class="text-base font-semibold text-slate-900">Record Payment</h3>
            <button type="button" data-close-payment class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <form id="paymentForm" class="p-6 space-y-4">
            <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-3">
                <p class="text-xs text-emerald-700">Current balance</p>
                <p id="paymentCurrentBalance" class="text-xl font-bold text-emerald-800">₱0.00</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Payment Type</label>
                <select id="payment_type_id" required class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">— Select type —</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Amount (₱)</label>
                <input type="number" id="payment_amount" required min="0.01" step="0.01"
                       class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Reference</label>
                <input type="text" readonly value="Will be generated automatically"
                    class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm font-mono text-slate-500 cursor-not-allowed">
                <p class="mt-1 text-xs text-slate-500">Reference is generated by the system on save.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Notes <span class="text-slate-400 font-normal">(optional)</span></label>
                <input type="text" id="payment_notes" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
        </form>
        <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 rounded-b-2xl flex items-center justify-end gap-3">
            <button type="button" data-close-payment class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</button>
            <button type="button" id="savePaymentBtn" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-60">
                <span id="savePaymentLabel">Record Payment</span>
            </button>
        </div>
    </div>
</div>

<script>
(function () {
    const baseUrl    = document.body.dataset.baseUrl;
    const alertEl    = document.getElementById('alert');
    const dataEl     = document.getElementById('cashierData');
    const paymentTypes = JSON.parse(dataEl.dataset.paymentTypes || '[]');

    let currentStatementId = null;
    let currentStatement = null;
    let paymentInFlight = false;
    let statementInFlight = false;

    function showAlert(type, msg) {
        alertEl.className = 'mb-5 rounded-lg px-4 py-3 text-sm border ' +
            (type === 'success'
                ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
                : 'border-rose-200 bg-rose-50 text-rose-800');
        alertEl.textContent = msg;
        alertEl.classList.remove('hidden');
        alertEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function money(n) {
        return '₱' + Number(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    const rows = document.querySelectorAll('.statement-row');
    const searchInput = document.getElementById('filterSearch');
    const statusSelect = document.getElementById('filterStatus');
    const clearBtn = document.getElementById('filterClear');
    const summary = document.getElementById('filterSummary');
    const filteredCount = document.getElementById('filteredCount');
    const totalCount = document.getElementById('totalCount');
    const emptyState = document.getElementById('emptyState');

    function applyFilters() {
        const q = (searchInput?.value || '').toLowerCase().trim();
        const status = statusSelect?.value || '';
        let visible = 0;

        rows.forEach(r => {
            const matchSearch = !q || (r.dataset.search || '').includes(q);
            const matchStatus = !status || (r.dataset.status || '') === status;
            if (matchSearch && matchStatus) {
                r.style.display = '';
                visible++;
            } else {
                r.style.display = 'none';
            }
        });

        if (q || status) {
            summary.classList.remove('hidden');
            clearBtn.classList.remove('hidden');
            clearBtn.classList.add('flex');
            filteredCount.textContent = visible;
            totalCount.textContent = rows.length;
        } else {
            summary.classList.add('hidden');
            clearBtn.classList.add('hidden');
            clearBtn.classList.remove('flex');
        }
        emptyState.classList.toggle('hidden', visible > 0);
    }

    searchInput?.addEventListener('input', applyFilters);
    statusSelect?.addEventListener('change', applyFilters);
    clearBtn?.addEventListener('click', () => {
        searchInput.value = '';
        statusSelect.value = '';
        applyFilters();
    });

    const manageModal = document.getElementById('manageModal');
    document.querySelectorAll('.manage-btn').forEach(btn => {
        btn.addEventListener('click', () => openManage(btn.dataset.statementId));
    });

    manageModal.querySelectorAll('[data-close-manage]').forEach(el =>
        el.addEventListener('click', () => manageModal.classList.add('hidden'))
    );

    manageModal.querySelectorAll('.manage-tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            manageModal.querySelectorAll('.manage-tab-btn').forEach(b => {
                b.classList.remove('border-blue-600', 'text-blue-600');
                b.classList.add('border-transparent', 'text-slate-500');
            });
            btn.classList.remove('border-transparent', 'text-slate-500');
            btn.classList.add('border-blue-600', 'text-blue-600');

            manageModal.querySelectorAll('.manage-tab-panel').forEach(p => p.classList.add('hidden'));
            const target = manageModal.querySelector(`.manage-tab-panel[data-mpanel="${btn.dataset.mtab}"]`);
            target?.classList.remove('hidden');
        });
    });

    async function openManage(id) {
        currentStatementId = id;
        manageModal.classList.remove('hidden');
        document.getElementById('manageTitle').textContent = 'Statement #' + id;

        try {
            const res = await axios.get(`${baseUrl}/api/cashier/get-statement.php?id=${id}`);
            if (!res.data.success) {
                showAlert('error', res.data.message || 'Could not load statement.');
                return;
            }
            renderStatement(res.data.statement);
        } catch (err) {
            showAlert('error', err.response?.data?.message || 'Could not load statement.');
        }
    }

    function renderStatement(s) {
        currentStatement = s;
        document.getElementById('manageSubtitle').textContent =
            `${s.first_name} ${s.last_name} · Admission #${s.admission_id}`;

        document.getElementById('sumSubtotal').textContent = money(s.subtotal_amount);
        document.getElementById('sumTax').textContent = money(s.tax_amount);
        document.getElementById('sumTotal').textContent = money(s.total_amount);
        document.getElementById('sumPaid').textContent = money(s.amount_paid);
        document.getElementById('sumBalance').textContent = money(s.balance_amount);
        document.getElementById('managePhilhealthDeduction').value = Number(s.philhealth_deduction || 0).toFixed(2);
        document.getElementById('manageInsuranceCoverage').value = Number(s.insurance_coverage_amount || 0).toFixed(2);
        document.getElementById('manageHmoDiscount').value = Number(s.hmo_discount || 0).toFixed(2);
        document.getElementById('manageGovernmentDiscount').value = Number(s.government_discount || 0).toFixed(2);

        const chargesBody = document.getElementById('chargesBody');
        const charges = s.charges || [];
        if (charges.length === 0) {
            chargesBody.innerHTML = '';
            document.getElementById('noCharges').classList.remove('hidden');
        } else {
            document.getElementById('noCharges').classList.add('hidden');
            chargesBody.innerHTML = charges.map(c => `
                <tr>
                    <td class="px-4 py-2.5">
                        <p class="font-medium text-slate-900">${escapeHtml(c.item_name)}</p>
                        <p class="text-xs text-slate-500">${escapeHtml(c.item_code)}</p>
                    </td>
                    <td class="px-4 py-2.5 text-slate-600 text-xs">${escapeHtml(c.category_name)}</td>
                    <td class="px-4 py-2.5 text-center text-slate-700">${c.quantity}</td>
                    <td class="px-4 py-2.5 text-right text-slate-700">${money(c.actual_price)}</td>
                    <td class="px-4 py-2.5 text-right font-medium text-slate-900">${money(c.line_total)}</td>
                    <td class="px-4 py-2.5 text-right">
                        ${c.source_type === 'MANUAL'
                            ? `<button type="button" class="remove-charge-btn text-rose-500 hover:text-rose-700" data-charge-id="${c.charge_id}" aria-label="Remove manual charge">
                                <svg class="w-4 h-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                              </button>`
                            : `<span class="inline-flex text-slate-400" title="This charge is system-generated and cannot be removed." aria-label="System-generated charge">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <rect x="5" y="11" width="14" height="10" rx="2"/><path stroke-linecap="round" stroke-linejoin="round" d="M8 11V7a4 4 0 118 0v4"/>
                                </svg>
                              </span>`}
                    </td>
                </tr>
            `).join('');
        }

        const paymentsBody = document.getElementById('paymentsBody');
        const payments = s.payments || [];
        if (payments.length === 0) {
            paymentsBody.innerHTML = '';
            document.getElementById('noPayments').classList.remove('hidden');
        } else {
            document.getElementById('noPayments').classList.add('hidden');
            paymentsBody.innerHTML = payments.map(p => `
                <tr>
                    <td class="px-4 py-2.5 text-slate-700">${escapeHtml(p.type_name)}</td>
                    <td class="px-4 py-2.5 text-xs font-mono text-slate-600">${escapeHtml(p.transaction_reference || '—')}</td>
                    <td class="px-4 py-2.5 text-xs text-slate-600">${formatDate(p.payment_datetime)}</td>
                    <td class="px-4 py-2.5 text-right font-medium text-emerald-700">${money(p.amount)}</td>
                    <td class="px-4 py-2.5 text-right">
                        <button type="button" class="remove-payment-btn text-rose-500 hover:text-rose-700"
                                data-payment-id="${p.payment_id}">
                            <svg class="w-4 h-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </td>
                </tr>
            `).join('');
        }

        const roomsBody = document.getElementById('roomsBody');
        const rooms = s.room_history || [];
        if (rooms.length === 0) {
            roomsBody.innerHTML = '';
            document.getElementById('noRooms').classList.remove('hidden');
        } else {
            document.getElementById('noRooms').classList.add('hidden');
            roomsBody.innerHTML = rooms.map(r => `
                <tr>
                    <td class="px-4 py-2.5 text-slate-900 font-medium">${escapeHtml(r.room_number)}</td>
                    <td class="px-4 py-2.5 text-slate-600 text-xs">${escapeHtml(r.room_type_name)}</td>
                    <td class="px-4 py-2.5 text-slate-600 text-xs">${formatDate(r.start_datetime)}</td>
                    <td class="px-4 py-2.5 text-slate-600 text-xs">${r.end_datetime ? formatDate(r.end_datetime) : 'Present'}</td>
                    <td class="px-4 py-2.5 text-right text-slate-700">${money(r.daily_rate_at_assignment)}</td>
                </tr>
            `).join('');
        }

        document.querySelectorAll('.remove-charge-btn').forEach(b =>
            b.addEventListener('click', () => removeCharge(b.dataset.chargeId))
        );
        document.querySelectorAll('.remove-payment-btn').forEach(b =>
            b.addEventListener('click', () => removePayment(b.dataset.paymentId))
        );
    }

    function escapeHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g, c => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        })[c]);
    }

    function formatDate(s) {
        if (!s) return '—';
        const d = new Date(s.replace(' ', 'T'));
        if (isNaN(d)) return s;
        return d.toLocaleString('en-PH', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });
    }

    async function removeCharge(chargeId) {
        if (!await window.confirmWithSweetAlert('Remove this charge from the statement?')) return;
        try {
            const res = await axios.post(`${baseUrl}/api/cashier/remove-charge.php?id=${currentStatementId}&charge_id=${chargeId}`);
            if (res.data.success) {
                showAlert('success', res.data.message);
                openManage(currentStatementId);
            }
        } catch (err) {
            showAlert('error', err.response?.data?.message || 'Could not remove charge.');
        }
    }

    async function removePayment(paymentId) {
        if (!await window.confirmWithSweetAlert('Remove this payment?')) return;
        try {
            const res = await axios.post(`${baseUrl}/api/cashier/remove-payment.php?id=${currentStatementId}&payment_id=${paymentId}`);
            if (res.data.success) {
                showAlert('success', res.data.message);
                openManage(currentStatementId);
            }
        } catch (err) {
            showAlert('error', err.response?.data?.message || 'Could not remove payment.');
        }
    }

    document.getElementById('syncChargesBtn')?.addEventListener('click', async function () {
        if (!currentStatementId) return;
        this.disabled = true;
        try {
            const res = await axios.post(`${baseUrl}/api/cashier/sync-charges.php?id=${currentStatementId}`);
            if (res.data.success) {
                showAlert('success', res.data.message);
                openManage(currentStatementId);
            }
        } catch (err) {
            showAlert('error', err.response?.data?.message || 'Sync failed.');
        } finally {
            this.disabled = false;
        }
    });

    document.getElementById('saveDeductionsBtn')?.addEventListener('click', async function () {
        if (!currentStatementId) return;
        const fields = {
            philhealth_deduction: 'managePhilhealthDeduction',
            insurance_coverage_amount: 'manageInsuranceCoverage',
            hmo_discount: 'manageHmoDiscount',
            government_discount: 'manageGovernmentDiscount'
        };
        const deductions = {};
        for (const [key, id] of Object.entries(fields)) {
            const value = Number(document.getElementById(id).value);
            if (!Number.isFinite(value) || value < 0) {
                showAlert('error', 'Enter non-negative amounts for all deductions.');
                return;
            }
            deductions[key] = value;
        }
        this.disabled = true;
        try {
            const res = await axios.post(`${baseUrl}/api/cashier.php?operation=update-deductions`, {
                statement_id: currentStatementId,
                ...deductions
            });
            if (res.data.success) {
                showAlert('success', res.data.message || 'Deductions saved.');
                await openManage(currentStatementId);
            } else {
                showAlert('error', res.data.message || 'Could not save deductions.');
            }
        } catch (err) {
            showAlert('error', err.response?.data?.message || 'Could not save deductions.');
        } finally {
            this.disabled = false;
        }
    });

    const paymentModal = document.getElementById('paymentModal');
    const payTypeSelect = document.getElementById('payment_type_id');

    if (payTypeSelect) {
        payTypeSelect.innerHTML = '<option value="">— Select type —</option>' +
            paymentTypes.map(p => `<option value="${p.payment_type_id}">${p.type_name}</option>`).join('');
    }

    document.getElementById('addPaymentBtn')?.addEventListener('click', () => {
        document.getElementById('paymentCurrentBalance').textContent = money(currentStatement?.balance_amount || 0);
        document.getElementById('payment_amount').max = currentStatement?.balance_amount || 0;
        document.getElementById('payment_amount').value = currentStatement?.balance_amount || '';
        paymentModal.classList.remove('hidden');
    });

    paymentModal.querySelectorAll('[data-close-payment]').forEach(el =>
        el.addEventListener('click', () => paymentModal.classList.add('hidden'))
    );

    document.getElementById('savePaymentBtn')?.addEventListener('click', async function () {
        if (paymentInFlight) return;

        const typeId = document.getElementById('payment_type_id').value;
        const amount = parseFloat(document.getElementById('payment_amount').value) || 0;

        if (!typeId || amount <= 0) {
            showAlert('error', 'Please select a payment type and enter an amount.');
            return;
        }

        paymentInFlight = true;

        this.disabled = true;
        const label = document.getElementById('savePaymentLabel');
        label.textContent = 'Recording…';

        try {
            const res = await axios.post(`${baseUrl}/api/cashier/add-payment.php?id=${currentStatementId}`, {
                payment_type_id: typeId,
                amount: amount,
                notes: document.getElementById('payment_notes').value
            }, { headers: { 'Content-Type': 'application/json' } });

            if (res.data.success) {
                showAlert('success', res.data.message + ' Reference: ' + (res.data.reference || ''));
                paymentModal.classList.add('hidden');
                openManage(currentStatementId);
            }
        } catch (err) {
            showAlert('error', err.response?.data?.message || 'Could not record payment.');
        } finally {
            this.disabled = false;
            label.textContent = 'Record Payment';
            paymentInFlight = false;
        }
    });

    document.querySelectorAll('.collect-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.statementId;
            const printUrl = `${baseUrl}/index.php?page=cashier-receipt&id=${id}`;
            window.open(printUrl, '_blank');
        });
    });

})();
</script>