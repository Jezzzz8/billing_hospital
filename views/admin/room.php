<?php

require_once __DIR__ . '/../../controllers/RoomController.php';

$controller = new RoomController($pdo);
$rooms      = $controller->getAll();
$roomTypes  = $controller->getRoomTypes();
$statuses   = $controller->getStatuses();
$buildings  = $controller->getBuildings();
$floorLevels = $controller->getFloorLevels();

$totalRooms    = count($rooms);
$activeRooms   = count(array_filter($rooms, fn($r) => (int)$r['is_active'] === 1));
$archivedRooms = $totalRooms - $activeRooms;
?>

<div id="roomsData"
     class="hidden"
     data-floor-levels='<?= htmlspecialchars(json_encode(array_map(fn($fl) => [
        'floor_level_id'   => (int)$fl['floor_level_id'],
        'building_id'      => (int)$fl['building_id'],
        'floor_level_name' => $fl['floor_level_name'],
     ], $floorLevels)), ENT_QUOTES, "UTF-8") ?>'></div>

<div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Rooms</h1>
        <p class="mt-1 text-sm text-slate-500">All hospital rooms, their type, and current status.</p>
    </div>
    <button type="button" id="openCreateBtn"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        New Room
    </button>
</div>

<div id="alert" class="hidden mb-5 rounded-lg px-4 py-3 text-sm border"></div>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Total Rooms</p>
        <p class="mt-2 text-2xl font-bold text-slate-900"><?= $totalRooms ?></p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Active</p>
        <p class="mt-2 text-2xl font-bold text-emerald-600"><?= $activeRooms ?></p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Archived</p>
        <p class="mt-2 text-2xl font-bold text-slate-400"><?= $archivedRooms ?></p>
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
                <input type="text" id="filterSearch" placeholder="Search room number, type, status, building…"
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
        Showing <strong id="filteredCount" class="text-slate-900">0</strong> of <strong id="totalCount" class="text-slate-900">0</strong> rooms
    </div>
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="text-left px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Room</th>
                    <th class="text-left px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Type</th>
                    <th class="text-left px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Status</th>
                    <th class="text-left px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Location</th>
                    <th class="text-left px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">State</th>
                    <th class="text-right px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($rooms as $r): ?>
                    <tr class="hover:bg-slate-50 room-row"
                        data-search="<?= htmlspecialchars(strtolower(
                            $r['room_number'] . ' ' . $r['room_type_name'] . ' ' . $r['status_name'] . ' ' . ($r['building_name'] ?? '') . ' ' . ($r['floor_level_name'] ?? '')
                        )) ?>"
                        data-status="<?= (int)$r['is_active'] ?>">

                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                </div>
                                <p class="font-semibold text-slate-900"><?= htmlspecialchars($r['room_number']) ?></p>
                            </div>
                        </td>

                        <td class="px-6 py-4 text-slate-700"><?= htmlspecialchars($r['room_type_name']) ?></td>

                        <td class="px-6 py-4">
                            <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-medium"
                                  style="background-color: <?= htmlspecialchars($r['color_code']) ?>1A;
                                         color: <?= htmlspecialchars($r['color_code']) ?>;
                                         border-color: <?= htmlspecialchars($r['color_code']) ?>40;">
                                <span class="w-1.5 h-1.5 rounded-full" style="background-color: <?= htmlspecialchars($r['color_code']) ?>"></span>
                                <?= htmlspecialchars($r['status_name']) ?>
                            </span>
                        </td>

                        <td class="px-6 py-4 text-slate-600 text-xs">
                            <?php if (!empty($r['floor_level_name']) || !empty($r['building_name'])): ?>
                                <?php if (!empty($r['floor_level_name'])): ?>Floor <?= htmlspecialchars($r['floor_level_name']) ?><?php endif; ?>
                                <?php if (!empty($r['floor_level_name']) && !empty($r['building_name'])): ?> · <?php endif; ?>
                                <?= htmlspecialchars($r['building_name'] ?? '') ?>
                            <?php else: ?>
                                <span class="text-slate-400">—</span>
                            <?php endif; ?>
                        </td>

                        <td class="px-6 py-4">
                            <?php if ((int)$r['is_active'] === 1): ?>
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
                                        data-room='<?= htmlspecialchars(json_encode($r), ENT_QUOTES, "UTF-8") ?>'>
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                    Edit
                                </button>

                                <?php if ((int)$r['is_active'] === 1): ?>
                                    <button type="button"
                                            class="deactivate-btn inline-flex items-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-100 hover:border-rose-300"
                                            data-room-id="<?= (int)$r['room_id'] ?>"
                                            data-room-number="<?= htmlspecialchars($r['room_number']) ?>">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                        </svg>
                                        Archive
                                    </button>
                                <?php else: ?>
                                    <button type="button"
                                            class="reactivate-btn inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1.5 text-xs font-medium text-emerald-700 hover:bg-emerald-100 hover:border-emerald-300"
                                            data-room-id="<?= (int)$r['room_id'] ?>"
                                            data-room-number="<?= htmlspecialchars($r['room_number']) ?>">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                        </svg>
                                        Reactivate
                                    </button>

                                    <button type="button"
                                            class="delete-btn inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-rose-50 hover:border-rose-300 hover:text-rose-700"
                                            data-room-id="<?= (int)$r['room_id'] ?>"
                                            data-room-number="<?= htmlspecialchars($r['room_number']) ?>">
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
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
        </div>
        <p class="text-sm font-medium text-slate-700">No rooms match your filters</p>
        <p class="text-xs text-slate-500 mt-1">Try adjusting your search or clearing the filters.</p>
    </div>
</div>

<div id="roomModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/50" data-close-modal></div>

    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col">

        <div class="flex items-center justify-between px-6 py-5 border-b border-slate-200">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <div>
                    <h3 id="roomModalTitle" class="text-base font-semibold text-slate-900">New Room</h3>
                    <p class="text-xs text-slate-500">Set the room's number, type, status, and location.</p>
                </div>
            </div>
            <button type="button" data-close-modal
                    class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form id="roomForm" method="POST" novalidate class="flex-1 overflow-y-auto">
            <input type="hidden" id="room_id" name="room_id">

            <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-5">

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Room Number</label>
                    <input type="text" id="room_number" required placeholder="e.g. 101, ICU-1"
                           class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <p class="mt-1.5 text-xs text-red-600 hidden" data-error-for="room_number"></p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Room Type</label>
                    <select id="room_type_id" required
                            class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">— Select type —</option>
                        <?php foreach ($roomTypes as $rt): ?>
                            <option value="<?= (int)$rt['room_type_id'] ?>">
                                <?= htmlspecialchars($rt['room_type_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="mt-1.5 text-xs text-red-600 hidden" data-error-for="room_type_id"></p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Status</label>
                    <select id="status_id" required
                            class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">— Select status —</option>
                        <?php foreach ($statuses as $s): ?>
                            <option value="<?= (int)$s['status_id'] ?>">
                                <?= htmlspecialchars($s['status_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="mt-1.5 text-xs text-red-600 hidden" data-error-for="status_id"></p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Building</label>
                    <select id="building_id" required
                            class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">— Select building —</option>
                        <?php foreach ($buildings as $b): ?>
                            <option value="<?= (int)$b['building_id'] ?>">
                                <?= htmlspecialchars($b['building_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="mt-1.5 text-xs text-red-600 hidden" data-error-for="building_id"></p>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Floor Level</label>
                    <select id="floor_level_id" required
                            class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">— Select floor level —</option>
                    </select>
                    <p class="mt-1.5 text-xs text-red-600 hidden" data-error-for="floor_level_id"></p>
                </div>

            </div>

            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 rounded-b-2xl flex items-center justify-end gap-3">
                <button type="button" data-close-modal
                        class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Cancel
                </button>
                <button type="submit" id="roomSubmitBtn"
                        class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60">
                    <span id="roomSubmitLabel">Create Room</span>
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
                    <h3 class="text-base font-semibold text-slate-900">Archive this room?</h3>
                    <p class="mt-1.5 text-sm text-slate-500">
                        Room <strong id="confirmRoomNumber" class="text-slate-700"></strong> will no longer appear in room selection.
                        You can reactivate it later.
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
                <span id="confirmDeactivateLabel">Archive Room</span>
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
                    <h3 class="text-base font-semibold text-slate-900">Permanently delete this room?</h3>
                    <p class="mt-1.5 text-sm text-slate-500">
                        Room <strong id="deleteRoomNumber" class="text-slate-700"></strong> will be permanently removed.
                        This action cannot be undone.
                    </p>
                    <p class="mt-2 text-xs text-slate-500">
                        If the room is referenced by any room assignment, the delete will be blocked.
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