<?php

require_once __DIR__ . '/../../controllers/ChargeItemController.php';

$controller = new ChargeItemController($pdo);
$items      = $controller->getAll();
$categories = $controller->getCategories();

$totalItems    = count($items);
$activeItems   = count(array_filter($items, fn($i) => (int)$i['is_active'] === 1));
$archivedItems = $totalItems - $activeItems;
?>


<div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Charge Items</h1>
        <p class="mt-1 text-sm text-slate-500">Billable items and services with their default prices.</p>
    </div>
    <button type="button" id="openCreateBtn"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        New Charge Item
    </button>
</div>

<div id="alert" class="hidden mb-5 rounded-lg px-4 py-3 text-sm border"></div>


<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Total Items</p>
        <p class="mt-2 text-2xl font-bold text-slate-900"><?= $totalItems ?></p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Active</p>
        <p class="mt-2 text-2xl font-bold text-emerald-600"><?= $activeItems ?></p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Archived</p>
        <p class="mt-2 text-2xl font-bold text-slate-400"><?= $archivedItems ?></p>
    </div>
</div>


<div class="bg-white rounded-xl border border-slate-200 p-4 mb-4">
    <div class="flex flex-col lg:flex-row lg:items-end gap-3">
        <div class="flex-1">
            <label class="block text-xs font-medium text-slate-500 mb-1.5">Search</label>
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" id="filterSearch" placeholder="Search item name, code, category…"
                       class="w-full rounded-lg border border-slate-300 pl-9 pr-3 py-2 text-sm
                              focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>

        <div class="flex items-center gap-3">
            <label class="inline-flex items-center gap-2 text-sm text-slate-700 select-none cursor-pointer whitespace-nowrap
                          rounded-lg border border-slate-300 px-3 py-2 hover:bg-slate-50">
                <input type="checkbox" id="showArchived"
                       class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                Show archived only
            </label>

            <button type="button" id="filterClear"
                    class="hidden items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                Clear
            </button>
        </div>
    </div>

    <div id="filterSummary" class="hidden mt-3 pt-3 border-t border-slate-100 text-xs text-slate-500">
        Showing <strong id="filteredCount" class="text-slate-900">0</strong> of <strong id="totalCount" class="text-slate-900">0</strong> items
    </div>
</div>


<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="text-left px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Item</th>
                    <th class="text-left px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Category</th>
                    <th class="text-right px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Price</th>
                    <th class="text-center px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Flags</th>
                    <th class="text-left px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">State</th>
                    <th class="text-right px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($items as $i): ?>
                    <tr class="hover:bg-slate-50 charge-item-row"
                        data-search="<?= htmlspecialchars(strtolower($i['item_name'] . ' ' . $i['item_code'] . ' ' . $i['category_name'])) ?>"
                        data-status="<?= (int)$i['is_active'] ?>">

                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-lg bg-teal-50 text-teal-600 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="font-semibold text-slate-900 truncate"><?= htmlspecialchars($i['item_name']) ?></p>
                                    <p class="text-xs text-slate-500 font-mono"><?= htmlspecialchars($i['item_code']) ?></p>
                                </div>
                            </div>
                        </td>

                        <td class="px-6 py-4">
                            <span class="inline-block rounded-md bg-slate-100 border border-slate-200 px-2 py-0.5 text-xs font-medium text-slate-700">
                                <?= htmlspecialchars($i['category_name']) ?>
                            </span>
                        </td>

                        <td class="px-6 py-4 text-right font-medium text-slate-900 whitespace-nowrap">
                            ₱<?= number_format((float)$i['default_price'], 2) ?>
                            <?php if (!empty($i['unit_of_measure'])): ?>
                                <span class="text-xs text-slate-500 font-normal">/ <?= htmlspecialchars($i['unit_of_measure']) ?></span>
                            <?php endif; ?>
                        </td>

                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                <?php if ((int)$i['is_taxable'] === 1): ?>
                                    <span class="inline-block rounded-full bg-purple-50 border border-purple-100 px-2 py-0.5 text-xs font-medium text-purple-700">Tax</span>
                                <?php endif; ?>
                                <?php if ((int)$i['requires_doctor_order'] === 1): ?>
                                    <span class="inline-block rounded-full bg-blue-50 border border-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700">Doctor order</span>
                                <?php endif; ?>
                                <?php if ((int)$i['is_taxable'] === 0 && (int)$i['requires_doctor_order'] === 0): ?>
                                    <span class="text-xs text-slate-400">—</span>
                                <?php endif; ?>
                            </div>
                        </td>

                        <td class="px-6 py-4">
                            <?php if ((int)$i['is_active'] === 1): ?>
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 border border-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    Active
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 border border-slate-200 px-2.5 py-1 text-xs font-medium text-slate-600">
                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                    Archived
                                </span>
                            <?php endif; ?>
                        </td>

                        <td class="px-6 py-4 text-right whitespace-nowrap">
                            <div class="inline-flex items-center gap-1.5">

                                <button type="button"
                                        class="edit-btn inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50 hover:border-slate-400"
                                        data-item='<?= htmlspecialchars(json_encode($i), ENT_QUOTES, "UTF-8") ?>'>
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                    Edit
                                </button>

                                <?php if ((int)$i['is_active'] === 1): ?>
                                    <button type="button"
                                            class="deactivate-btn inline-flex items-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-100 hover:border-rose-300"
                                            data-item-id="<?= (int)$i['charge_item_id'] ?>"
                                            data-item-name="<?= htmlspecialchars($i['item_name']) ?>">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                        </svg>
                                        Archive
                                    </button>
                                <?php else: ?>
                                    <button type="button"
                                            class="reactivate-btn inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1.5 text-xs font-medium text-emerald-700 hover:bg-emerald-100 hover:border-emerald-300"
                                            data-item-id="<?= (int)$i['charge_item_id'] ?>"
                                            data-item-name="<?= htmlspecialchars($i['item_name']) ?>">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                        </svg>
                                        Reactivate
                                    </button>

                                    <button type="button"
                                            class="delete-btn inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-rose-50 hover:border-rose-300 hover:text-rose-700"
                                            data-item-id="<?= (int)$i['charge_item_id'] ?>"
                                            data-item-name="<?= htmlspecialchars($i['item_name']) ?>">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                        Delete
                                    </button>
                                <?php endif; ?>
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
                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
        </div>
        <p class="text-sm font-medium text-slate-700">No charge items match your filters</p>
        <p class="text-xs text-slate-500 mt-1">Try adjusting your search or clearing the filters.</p>
    </div>
</div>


<div id="itemModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/50" data-close-modal></div>

    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col">

        <div class="flex items-center justify-between px-6 py-5 border-b border-slate-200">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-teal-50 text-teal-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <div>
                    <h3 id="itemModalTitle" class="text-base font-semibold text-slate-900">New Charge Item</h3>
                    <p class="text-xs text-slate-500">Define the billable item with its code, price, and flags.</p>
                </div>
            </div>
            <button type="button" data-close-modal
                    class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form id="itemForm" method="POST" novalidate class="flex-1 overflow-y-auto">
            <input type="hidden" id="charge_item_id" name="charge_item_id">

            <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-5">

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Category</label>
                    <select id="category_id" required
                            class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">— Select category —</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= (int)$cat['category_id'] ?>">
                                <?= htmlspecialchars($cat['category_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="mt-1.5 text-xs text-red-600 hidden" data-error-for="category_id"></p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Item Code</label>
                    <input type="text" id="item_code" required placeholder="e.g. LAB-CBC"
                           class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <p class="mt-1.5 text-xs text-red-600 hidden" data-error-for="item_code"></p>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Item Name</label>
                    <input type="text" id="item_name" required placeholder="e.g. Complete Blood Count"
                           class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <p class="mt-1.5 text-xs text-red-600 hidden" data-error-for="item_name"></p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Default Price (₱)</label>
                    <input type="number" id="default_price" required min="0" step="0.01" placeholder="0.00"
                           class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <p class="mt-1.5 text-xs text-red-600 hidden" data-error-for="default_price"></p>
                </div>

                <div>
    <label class="block text-sm font-medium text-slate-700 mb-1.5">
        Unit of Measure <span class="text-slate-400 font-normal">(optional)</span>
    </label>
    <select id="unit_of_measure"
            class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
        <option value="">— Select unit —</option>
        <option value="day">Day</option>
        <option value="night">Night</option>
        <option value="hour">Hour</option>
        <option value="visit">Visit</option>
        <option value="test">Test</option>
        <option value="procedure">Procedure</option>
        <option value="session">Session</option>
        <option value="tablet">Tablet</option>
        <option value="capsule">Capsule</option>
        <option value="bottle">Bottle</option>
        <option value="vial">Vial</option>
        <option value="ampoule">Ampoule</option>
        <option value="sachet">Sachet</option>
        <option value="tube">Tube</option>
        <option value="piece">Piece</option>
        <option value="kit">Kit</option>
        <option value="set">Set</option>
        <option value="unit">Unit</option>
    </select>
    <p class="mt-1.5 text-xs text-red-600 hidden" data-error-for="unit_of_measure"></p>
</div>

                <div class="sm:col-span-2 space-y-3 pt-2 border-t border-slate-100">
                    <label class="inline-flex items-center gap-3 cursor-pointer select-none">
                        <input type="checkbox" id="is_taxable"
                               class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 w-4 h-4">
                        <span class="text-sm text-slate-700">Taxable</span>
                    </label>
                    <label class="inline-flex items-center gap-3 cursor-pointer select-none ml-6">
                        <input type="checkbox" id="requires_doctor_order"
                               class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 w-4 h-4">
                        <span class="text-sm text-slate-700">Requires doctor's order</span>
                    </label>
                </div>

            </div>

            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 rounded-b-2xl flex items-center justify-end gap-3">
                <button type="button" data-close-modal
                        class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Cancel
                </button>
                <button type="submit" id="itemSubmitBtn"
                        class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60">
                    <span id="itemSubmitLabel">Create Charge Item</span>
                </button>
            </div>
        </form>
    </div>
</div>


<div id="confirmModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/50" data-close-confirm></div>

    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="p-6">
            <div class="flex items-start gap-4">
                <div class="shrink-0 w-11 h-11 rounded-full bg-rose-50 flex items-center justify-center text-rose-600">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M5 19h14a2 2 0 001.84-2.75L13.74 4a2 2 0 00-3.48 0L3.16 16.25A2 2 0 005 19z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Archive this charge item?</h3>
                    <p class="mt-1.5 text-sm text-slate-500">
                        <strong id="confirmItemName" class="text-slate-700"></strong> will no longer be selectable
                        for new charges. Existing records keep their reference — you can reactivate it later.
                    </p>
                </div>
            </div>
        </div>

        <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 rounded-b-2xl flex items-center justify-end gap-3">
            <button type="button" data-close-confirm
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Cancel
            </button>
            <button type="button" id="confirmDeactivateBtn"
                    class="inline-flex items-center gap-2 rounded-lg bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-rose-700 disabled:opacity-60">
                <span id="confirmDeactivateLabel">Archive Item</span>
            </button>
        </div>
    </div>
</div>


<div id="deleteModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/50" data-close-delete></div>

    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="p-6">
            <div class="flex items-start gap-4">
                <div class="shrink-0 w-11 h-11 rounded-full bg-rose-50 flex items-center justify-center text-rose-600">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Permanently delete this charge item?</h3>
                    <p class="mt-1.5 text-sm text-slate-500">
                        <strong id="deleteItemName" class="text-slate-700"></strong> will be permanently removed.
                        This action cannot be undone.
                    </p>
                    <p class="mt-2 text-xs text-slate-500">
                        If any charge or service request references it, the delete will be blocked.
                    </p>
                </div>
            </div>
        </div>

        <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 rounded-b-2xl flex items-center justify-end gap-3">
            <button type="button" data-close-delete
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Cancel
            </button>
            <button type="button" id="confirmDeleteBtn"
                    class="inline-flex items-center gap-2 rounded-lg bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-rose-700 disabled:opacity-60">
                <span id="confirmDeleteLabel">Yes, delete permanently</span>
            </button>
        </div>
    </div>
</div>