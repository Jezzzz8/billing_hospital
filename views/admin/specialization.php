<?php

require_once __DIR__ . '/../../controllers/SpecializationController.php';

$specializations = (new SpecializationController($pdo))->getAll();
$totalSpecializations = count($specializations);
?>


<div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Specializations</h1>
        <p class="mt-1 text-sm text-slate-500">Medical specializations assigned to doctors.</p>
    </div>
    <button type="button" id="openCreateBtn"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        New Specialization
    </button>
</div>

<div id="alert" class="hidden mb-5 rounded-lg px-4 py-3 text-sm border"></div>


<div class="bg-white rounded-xl border border-slate-200 p-5 mb-6 max-w-xs">
    <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Total Specializations</p>
    <p class="mt-2 text-2xl font-bold text-slate-900"><?= $totalSpecializations ?></p>
</div>


<div class="bg-white rounded-xl border border-slate-200 p-4 mb-4">
    <div class="flex flex-col sm:flex-row sm:items-end gap-3">
        <div class="flex-1">
            <label class="block text-xs font-medium text-slate-500 mb-1.5">Search</label>
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" id="filterSearch" placeholder="Search specialization name…"
                       class="w-full rounded-lg border border-slate-300 pl-9 pr-3 py-2 text-sm
                              focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>

        <button type="button" id="filterClear"
                class="hidden items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 self-start sm:self-auto">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
            Clear
        </button>
    </div>

    <div id="filterSummary" class="hidden mt-3 pt-3 border-t border-slate-100 text-xs text-slate-500">
        Showing <strong id="filteredCount" class="text-slate-900">0</strong> of <strong id="totalCount" class="text-slate-900">0</strong> specializations
    </div>
</div>


<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="text-left px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Specialization</th>
                    <th class="text-right px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($specializations as $s): ?>
                    <tr class="hover:bg-slate-50 specialization-row"
                        data-search="<?= htmlspecialchars(strtolower($s['specialization_name'])) ?>">

                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-lg bg-violet-50 text-violet-600 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253"/>
                                    </svg>
                                </div>
                                <p class="font-semibold text-slate-900"><?= htmlspecialchars($s['specialization_name']) ?></p>
                            </div>
                        </td>

                        <td class="px-6 py-4 text-right whitespace-nowrap">
                            <div class="inline-flex items-center gap-1.5">

                                <button type="button"
                                        class="edit-btn inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50 hover:border-slate-400"
                                        data-specialization='<?= htmlspecialchars(json_encode($s), ENT_QUOTES, "UTF-8") ?>'>
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                    Edit
                                </button>

                                <button type="button"
                                        class="delete-btn inline-flex items-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-100 hover:border-rose-300"
                                        data-specialization-id="<?= (int)$s['specialization_id'] ?>"
                                        data-specialization-name="<?= htmlspecialchars($s['specialization_name']) ?>">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                    Delete
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
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253"/>
            </svg>
        </div>
        <p class="text-sm font-medium text-slate-700">No specializations match your search</p>
        <p class="text-xs text-slate-500 mt-1">Try adjusting your search.</p>
    </div>
</div>


<div id="specializationModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/50" data-close-modal></div>

    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md max-h-[90vh] flex flex-col">

        <div class="flex items-center justify-between px-6 py-5 border-b border-slate-200">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-violet-50 text-violet-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253"/>
                    </svg>
                </div>
                <div>
                    <h3 id="specializationModalTitle" class="text-base font-semibold text-slate-900">New Specialization</h3>
                    <p class="text-xs text-slate-500">Enter the medical specialization name.</p>
                </div>
            </div>
            <button type="button" data-close-modal
                    class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form id="specializationForm" method="POST" novalidate class="flex-1 overflow-y-auto">
            <input type="hidden" id="specialization_id" name="specialization_id">

            <div class="p-6">
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Specialization Name</label>
                <input type="text" id="specialization_name" required placeholder="e.g. Cardiology, Pediatrics"
                       class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <p class="mt-1.5 text-xs text-red-600 hidden" data-error-for="specialization_name"></p>
            </div>

            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 rounded-b-2xl flex items-center justify-end gap-3">
                <button type="button" data-close-modal
                        class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Cancel
                </button>
                <button type="submit" id="specializationSubmitBtn"
                        class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60">
                    <span id="specializationSubmitLabel">Create Specialization</span>
                </button>
            </div>
        </form>
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
                    <h3 class="text-base font-semibold text-slate-900">Delete this specialization?</h3>
                    <p class="mt-1.5 text-sm text-slate-500">
                        <strong id="deleteSpecializationName" class="text-slate-700"></strong> will be permanently removed.
                        This action cannot be undone.
                    </p>
                    <p class="mt-2 text-xs text-slate-500">
                        If any doctor is assigned this specialization, the delete will be blocked.
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
                <span id="confirmDeleteLabel">Yes, delete</span>
            </button>
        </div>
    </div>
</div>