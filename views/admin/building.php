<?php

require_once __DIR__ . '/../../controllers/BuildingController.php';

$buildings = (new BuildingController($pdo))->getAll();
$totalBuildings = count($buildings);
?>

<div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Buildings</h1>
        <p class="mt-1 text-sm text-slate-500">Hospital buildings and their floors.</p>
    </div>
    <button type="button" id="openCreateBtn"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        New Building
    </button>
</div>

<div id="alert" class="hidden mb-5 rounded-lg px-4 py-3 text-sm border"></div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6 max-w-xl">
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Total Buildings</p>
        <p class="mt-2 text-2xl font-bold text-slate-900"><?= $totalBuildings ?></p>
    </div>
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
                <input type="text" id="filterSearch" placeholder="Search building name…"
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
        Showing <strong id="filteredCount" class="text-slate-900">0</strong> of <strong id="totalCount" class="text-slate-900">0</strong> buildings
    </div>
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="text-left px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Building</th>
                    <th class="text-left px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Description</th>
                    <th class="text-center px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Floors</th>
                    <th class="text-center px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Rooms</th>
                    <th class="text-right px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($buildings as $b): ?>
                    <tr class="hover:bg-slate-50 building-row"
                        data-search="<?= htmlspecialchars(strtolower($b['building_name'] . ' ' . ($b['description'] ?? ''))) ?>">

                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                </div>
                                <p class="font-semibold text-slate-900"><?= htmlspecialchars($b['building_name']) ?></p>
                            </div>
                        </td>

                        <td class="px-6 py-4 text-slate-600 max-w-xl">
                            <?php if (!empty($b['description'])): ?>
                                <p class="truncate"><?= htmlspecialchars($b['description']) ?></p>
                            <?php else: ?>
                                <span class="text-slate-400 text-xs">No description</span>
                            <?php endif; ?>
                        </td>

                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-slate-100 text-slate-700 text-xs font-semibold">
                                <?= (int)$b['floor_count'] ?>
                            </span>
                        </td>

                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-slate-100 text-slate-700 text-xs font-semibold">
                                <?= (int)$b['room_count'] ?>
                            </span>
                        </td>

                        <td class="px-6 py-4 text-right whitespace-nowrap">
                            <div class="inline-flex items-center gap-1.5">
                                <button type="button"
                                        class="edit-btn inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50 hover:border-slate-400"
                                        data-building='<?= htmlspecialchars(json_encode($b), ENT_QUOTES, "UTF-8") ?>'>
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                    Edit
                                </button>

                                <button type="button"
                                        class="delete-btn inline-flex items-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-100 hover:border-rose-300"
                                        data-building-id="<?= (int)$b['building_id'] ?>"
                                        data-building-name="<?= htmlspecialchars($b['building_name']) ?>">
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
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
        </div>
        <p class="text-sm font-medium text-slate-700">No buildings match your search</p>
        <p class="text-xs text-slate-500 mt-1">Try adjusting your search.</p>
    </div>
</div>

<div id="buildingModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/50" data-close-modal></div>

    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md max-h-[90vh] flex flex-col">

        <div class="flex items-center justify-between px-6 py-5 border-b border-slate-200">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <div>
                    <h3 id="buildingModalTitle" class="text-base font-semibold text-slate-900">New Building</h3>
                    <p class="text-xs text-slate-500">Enter the building name and description.</p>
                </div>
            </div>
            <button type="button" data-close-modal
                    class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form id="buildingForm" method="POST" novalidate class="flex-1 overflow-y-auto">
            <input type="hidden" id="building_id" name="building_id">

            <div class="p-6 space-y-5">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Building Name</label>
                    <input type="text" id="building_name" required placeholder="e.g. Main Building"
                           class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <p class="mt-1.5 text-xs text-red-600 hidden" data-error-for="building_name"></p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">
                        Description <span class="text-slate-400 font-normal">(optional)</span>
                    </label>
                    <textarea id="description" rows="2" placeholder="Brief description of this building"
                              class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                    <p class="mt-1.5 text-xs text-red-600 hidden" data-error-for="description"></p>
                </div>
            </div>

            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 rounded-b-2xl flex items-center justify-end gap-3">
                <button type="button" data-close-modal
                        class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Cancel
                </button>
                <button type="submit" id="buildingSubmitBtn"
                        class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60">
                    <span id="buildingSubmitLabel">Create Building</span>
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
                    <h3 class="text-base font-semibold text-slate-900">Delete this building?</h3>
                    <p class="mt-1.5 text-sm text-slate-500">
                        <strong id="deleteBuildingName" class="text-slate-700"></strong> will be permanently removed.
                        This action cannot be undone.
                    </p>
                    <p class="mt-2 text-xs text-slate-500">
                        If any floor level belongs to this building, the delete will be blocked.
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