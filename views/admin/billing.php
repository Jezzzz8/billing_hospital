<?php

require_once __DIR__ . '/../../controllers/BillingController.php';

$controller       = new BillingController($pdo);
$statements       = $controller->getAll();
$billingStatuses  = $controller->getBillingStatuses();
$paymentTypes     = $controller->getPaymentTypes();
$chargeItems      = $controller->getChargeItems();

$totalStatements = count($statements);
$totalBilled     = 0;
$totalPaid       = 0;
$totalBalance    = 0;
foreach ($statements as $s) {
    $totalBilled  += (float)$s['total_amount'];
    $totalPaid    += (float)$s['amount_paid'];
    $totalBalance += (float)$s['balance_amount'];
}
?>


<div id="billingData"
     class="hidden"
     data-charge-items='<?= htmlspecialchars(json_encode(array_map(fn($c) => [
        'charge_item_id' => (int)$c['charge_item_id'],
        'item_code'      => $c['item_code'],
        'item_name'      => $c['item_name'],
        'default_price'  => (float)$c['default_price'],
        'is_taxable'     => (int)$c['is_taxable'],
        'unit'           => $c['unit_of_measure'] ?? '',
        'category'       => $c['category_name'],
     ], $chargeItems)), ENT_QUOTES, "UTF-8") ?>'
     data-payment-types='<?= htmlspecialchars(json_encode(array_map(fn($p) => [
        'payment_type_id' => (int)$p['payment_type_id'],
        'type_name'       => $p['type_name'],
     ], $paymentTypes)), ENT_QUOTES, "UTF-8") ?>'></div>


<div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Billing</h1>
        <p class="mt-1 text-sm text-slate-500">Manage statements, charges, and payments per admission.</p>
    </div>
    <p class="text-sm text-slate-500">Statements are created automatically when admissions are opened.</p>
</div>

<div id="alert" class="hidden mb-5 rounded-lg px-4 py-3 text-sm border"></div>


<div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Statements</p>
        <p class="mt-2 text-2xl font-bold text-slate-900"><?= $totalStatements ?></p>
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
                <?php foreach ($billingStatuses as $s): ?>
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
                                <button type="button"
                                        class="edit-btn inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50 hover:border-slate-400"
                                        data-statement-id="<?= (int)$s['statement_id'] ?>">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                    Edit
                                </button>
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
        <p class="text-xs text-slate-500 mt-1">Try adjusting your search or clearing the filters.</p>
    </div>
</div>


<div id="statementModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/60" data-close-statement></div>

    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] flex flex-col">

        <div class="flex items-center justify-between px-6 py-5 border-b border-slate-200">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div>
                    <h3 id="statementModalTitle" class="text-base font-semibold text-slate-900">Billing Adjustments</h3>
                    <p class="text-xs text-slate-500">Update the due date, deductions, and notes for this statement.</p>
                </div>
            </div>
            <button type="button" data-close-statement class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form id="statementForm" method="POST" novalidate class="flex-1 overflow-y-auto">
            <input type="hidden" id="statement_id" name="statement_id">

            <div class="p-6 space-y-5">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Due Date</label>
                        <input type="date" id="due_date" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Insurance Coverage (₱)</label>
                        <input type="number" id="insurance_coverage_amount" min="0" step="0.01" placeholder="0.00"
                               class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">PhilHealth Deduction (₱)</label>
                        <input type="number" id="philhealth_deduction" min="0" step="0.01" placeholder="0.00"
                               class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">HMO Discount (₱)</label>
                        <input type="number" id="hmo_discount" min="0" step="0.01" placeholder="0.00"
                               class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Government Discount (₱)</label>
                        <input type="number" id="government_discount" min="0" step="0.01" placeholder="0.00"
                               class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Notes</label>
                    <textarea id="notes" rows="2" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                </div>

                <p class="text-xs text-slate-500 bg-slate-50 border border-slate-200 rounded-lg p-3">
                    Totals are computed automatically from charges. Add charges and payments in the <strong>Manage</strong> view after saving.
                </p>
            </div>

            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 rounded-b-2xl flex items-center justify-end gap-3">
                <button type="button" data-close-statement
                        class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Cancel
                </button>
                <button type="submit" id="statementSubmitBtn"
                        class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60">
                    <span id="statementSubmitLabel">Save Changes</span>
                </button>
            </div>
        </form>
    </div>
</div>


<div id="manageModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/60" data-close-manage></div>

    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[95vh] flex flex-col">

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
            <nav class="flex gap-1 -mb-px">
                <button type="button" class="manage-tab-btn px-4 py-3 text-sm font-medium border-b-2 border-blue-600 text-blue-600 whitespace-nowrap" data-mtab="charges">Charges</button>
                <button type="button" class="manage-tab-btn px-4 py-3 text-sm font-medium border-b-2 border-transparent text-slate-500 hover:text-slate-700 whitespace-nowrap" data-mtab="payments">Payments</button>
            </nav>
        </div>

        <div class="flex-1 overflow-y-auto p-6">

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
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
                <div class="bg-rose-50 border border-rose-200 rounded-lg p-3">
                    <p class="text-xs text-rose-600">Balance</p>
                    <p id="sumBalance" class="text-lg font-bold text-rose-700">₱0.00</p>
                </div>
            </div>

            <div class="manage-tab-panel" data-mpanel="charges">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-sm font-semibold text-slate-900">Charges</h4>
                    <button type="button" id="addChargeBtn"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-100">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                        Add Charge
                    </button>
                </div>

                <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="text-left px-4 py-2.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Item</th>
                                <th class="text-center px-4 py-2.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Qty</th>
                                <th class="text-right px-4 py-2.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Unit Price</th>
                                <th class="text-right px-4 py-2.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Line Total</th>
                                <th class="text-right px-4 py-2.5 font-semibold text-slate-700 text-xs uppercase tracking-wide"></th>
                            </tr>
                        </thead>
                        <tbody id="chargesBody" class="divide-y divide-slate-100"></tbody>
                    </table>
                    <p id="noCharges" class="hidden text-center text-sm text-slate-400 py-8">No charges yet.</p>
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
                                <th class="text-right px-4 py-2.5 font-semibold text-slate-700 text-xs uppercase tracking-wide"></th>
                            </tr>
                        </thead>
                        <tbody id="paymentsBody" class="divide-y divide-slate-100"></tbody>
                    </table>
                    <p id="noPayments" class="hidden text-center text-sm text-slate-400 py-8">No payments yet.</p>
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


<div id="chargeModal" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/60" data-close-charge></div>

    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="flex items-center justify-between px-6 py-5 border-b border-slate-200">
            <h3 class="text-base font-semibold text-slate-900">Add Charge</h3>
            <button type="button" data-close-charge class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form id="chargeForm" class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Charge Item</label>
                <select id="charge_item_id" required class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">— Select item —</option>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Quantity</label>
                    <input type="number" id="charge_quantity" required min="1" step="1" value="1"
                           class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Price (₱)</label>
                    <input type="number" id="charge_price" required min="0" step="0.01" readonly tabindex="-1"
                           class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-600 cursor-not-allowed">
                </div>
            </div>

            
            <div id="chargeSummary" class="hidden rounded-lg border border-blue-100 bg-blue-50 p-4 space-y-1.5">
                <div class="flex items-center justify-between text-xs text-slate-600">
                    <span>Unit price</span>
                    <span id="summaryUnitPrice" class="font-medium text-slate-900">₱0.00</span>
                </div>
                <div class="flex items-center justify-between text-xs text-slate-600">
                    <span>Quantity</span>
                    <span id="summaryQty" class="font-medium text-slate-900">1</span>
                </div>
                <div id="summaryTaxRow" class="hidden items-center justify-between text-xs text-slate-600">
                    <span>Tax (12% — taxable item)</span>
                    <span id="summaryTax" class="font-medium text-slate-900">₱0.00</span>
                </div>
                <div class="flex items-center justify-between border-t border-blue-100 pt-2 mt-2">
                    <span class="text-sm font-semibold text-slate-900">Total</span>
                    <span id="summaryLineTotal" class="text-lg font-bold text-blue-700">₱0.00</span>
                </div>
            </div>

          

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Notes <span class="text-slate-400 font-normal">(optional)</span></label>
                <input type="text" id="charge_notes" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
        </form>

        <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 rounded-b-2xl flex items-center justify-end gap-3">
            <button type="button" data-close-charge class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</button>
            <button type="button" id="saveChargeBtn" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60">
                <span id="saveChargeLabel">Add Charge</span>
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