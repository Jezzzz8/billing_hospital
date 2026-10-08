<?php

require_once __DIR__ . '/../../controllers/DoctorController.php';

$controller  = new DoctorController($pdo);
$doctors     = $controller->getAll();
$allSpecs    = $controller->getSpecializations();

$totalDoctors    = count($doctors);
$activeDoctors   = count(array_filter($doctors, fn($d) => (int)$d['is_active'] === 1));
$archivedDoctors = $totalDoctors - $activeDoctors;
?>


<div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Doctors</h1>
        <p class="mt-1 text-sm text-slate-500">Manage physicians, their licenses, and specializations.</p>
    </div>
    <button type="button" id="openCreateBtn"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        New Doctor
    </button>
</div>

<div id="alert" class="hidden mb-5 rounded-lg px-4 py-3 text-sm border"></div>


<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Total Doctors</p>
        <p class="mt-2 text-2xl font-bold text-slate-900"><?= $totalDoctors ?></p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Active</p>
        <p class="mt-2 text-2xl font-bold text-emerald-600"><?= $activeDoctors ?></p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Archived</p>
        <p class="mt-2 text-2xl font-bold text-slate-400"><?= $archivedDoctors ?></p>
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
                <input type="text" id="filterSearch" placeholder="Search name, license, email…"
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
        Showing <strong id="filteredCount" class="text-slate-900">0</strong> of <strong id="totalCount" class="text-slate-900">0</strong> doctors
    </div>
</div>


<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="text-left px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Doctor</th>
                    <th class="text-left px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">License</th>
                    <th class="text-right px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Fee</th>
                    <th class="text-left px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Specializations</th>
                    <th class="text-left px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">State</th>
                    <th class="text-right px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($doctors as $d):
                    $specSearch = implode(' ', array_map(fn($s) => strtolower($s['specialization_name']), $d['specializations']));
                    $search = strtolower($d['first_name'] . ' ' . $d['last_name'] . ' ' . $d['email'] . ' ' . $d['license_number'] . ' ' . $specSearch);
                ?>
                    <tr class="hover:bg-slate-50 doctor-row"
                        data-search="<?= htmlspecialchars($search) ?>"
                        data-status="<?= (int)$d['is_active'] ?>">

                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center font-semibold text-xs shrink-0">
                                    <?= strtoupper(substr($d['first_name'], 0, 1) . substr($d['last_name'], 0, 1)) ?>
                                </div>
                                <div class="min-w-0">
                                    <p class="font-semibold text-slate-900 truncate">Dr. <?= htmlspecialchars($d['first_name'] . ' ' . $d['last_name']) ?></p>
                                    <p class="text-xs text-slate-500 truncate"><?= htmlspecialchars($d['email']) ?></p>
                                </div>
                            </div>
                        </td>

                        <td class="px-6 py-4">
                            <span class="inline-block rounded-md bg-slate-100 border border-slate-200 px-2 py-0.5 text-xs font-mono font-semibold text-slate-700">
                                <?= htmlspecialchars($d['license_number']) ?>
                            </span>
                        </td>

                        <td class="px-6 py-4 text-right font-medium text-slate-900 whitespace-nowrap">
                            ₱<?= number_format((float)$d['consultation_fee'], 2) ?>
                        </td>

                        <td class="px-6 py-4 max-w-md">
                            <?php if (empty($d['specializations'])): ?>
                                <span class="text-xs text-slate-400 ">No specializations</span>
                            <?php else: ?>
                                <div class="flex flex-wrap gap-1">
                                    <?php foreach ($d['specializations'] as $s): ?>
                                        <?php if ((int)$s['is_primary'] === 1): ?>
                                            <span class="inline-flex items-center gap-1 rounded-full bg-blue-100 border border-blue-200 px-2 py-0.5 text-xs font-medium text-blue-800">
                                             
                                                <?= htmlspecialchars($s['specialization_name']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-block rounded-full bg-blue-50 border border-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700">
                                                <?= htmlspecialchars($s['specialization_name']) ?>
                                            </span>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </td>

                        <td class="px-6 py-4">
                            <?php if ((int)$d['is_active'] === 1): ?>
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
                                        data-doctor='<?= htmlspecialchars(json_encode($d), ENT_QUOTES, "UTF-8") ?>'>
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                    Edit
                                </button>

                                <button type="button"
                                        class="pw-btn inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50 hover:border-slate-400"
                                        data-doctor-id="<?= (int)$d['doctor_id'] ?>"
                                        data-doctor-name="Dr. <?= htmlspecialchars($d['first_name'] . ' ' . $d['last_name']) ?>">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                    </svg>
                                    Password
                                </button>

                                <?php if ((int)$d['is_active'] === 1): ?>
                                    <button type="button"
                                            class="deactivate-btn inline-flex items-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-100 hover:border-rose-300"
                                            data-doctor-id="<?= (int)$d['doctor_id'] ?>"
                                            data-doctor-name="Dr. <?= htmlspecialchars($d['first_name'] . ' ' . $d['last_name']) ?>">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                        </svg>
                                        Archive
                                    </button>
                                <?php else: ?>
                                    <button type="button"
                                            class="reactivate-btn inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1.5 text-xs font-medium text-emerald-700 hover:bg-emerald-100 hover:border-emerald-300"
                                            data-doctor-id="<?= (int)$d['doctor_id'] ?>"
                                            data-doctor-name="Dr. <?= htmlspecialchars($d['first_name'] . ' ' . $d['last_name']) ?>">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                        </svg>
                                        Reactivate
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
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <p class="text-sm font-medium text-slate-700">No doctors match your filters</p>
        <p class="text-xs text-slate-500 mt-1">Try adjusting your search or clearing the filters.</p>
    </div>
</div>


<div id="doctorModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/50" data-close-modal></div>

    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col">

        <div class="flex items-center justify-between px-6 py-5 border-b border-slate-200">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h3 id="doctorModalTitle" class="text-base font-semibold text-slate-900">New Doctor</h3>
                    <p class="text-xs text-slate-500">Set the doctor's account, license, and specializations.</p>
                </div>
            </div>
            <button type="button" data-close-modal
                    class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form id="doctorForm" method="POST" novalidate class="flex-1 overflow-y-auto">
            <input type="hidden" id="doctor_id" name="doctor_id">

            <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-5">

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">First Name</label>
                    <input type="text" id="first_name" required
                           class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <p class="mt-1.5 text-xs text-red-600 hidden" data-error-for="first_name"></p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Last Name</label>
                    <input type="text" id="last_name" required
                           class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <p class="mt-1.5 text-xs text-red-600 hidden" data-error-for="last_name"></p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Username</label>
                    <input type="text" id="username" required
                           class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <p class="mt-1.5 text-xs text-red-600 hidden" data-error-for="username"></p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
                    <input type="email" id="email" required
                           class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <p class="mt-1.5 text-xs text-red-600 hidden" data-error-for="email"></p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">
                        Contact <span class="text-slate-400 font-normal">(optional)</span>
                    </label>
                    <input type="text" id="contact_number"
                           class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">License Number</label>
                    <input type="text" id="license_number" required placeholder="PRC-123456"
                           class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <p class="mt-1.5 text-xs text-red-600 hidden" data-error-for="license_number"></p>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Consultation Fee (₱)</label>
                    <input type="number" id="consultation_fee" required min="0" step="0.01" placeholder="500.00"
                           class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <p class="mt-1.5 text-xs text-red-600 hidden" data-error-for="consultation_fee"></p>
                </div>

                
                <div class="sm:col-span-2 border-t border-slate-100 pt-5">
                    <div class="flex items-center justify-between mb-3">
                        <label class="block text-sm font-medium text-slate-700">
                            Specializations
                            <span class="text-slate-400 font-normal ml-1">(select 0 or more)</span>
                        </label>
                        <span id="specCount" class="text-xs text-slate-500">0 selected</span>
                    </div>

                    <div id="specializationOptions">
                        <?php if (empty($allSpecs)): ?>
                            <p class="text-xs text-slate-400 italic">No specializations available. Add some first.</p>
                        <?php else: ?>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-48 overflow-y-auto border border-slate-200 rounded-lg p-3">
                                <?php foreach ($allSpecs as $s): ?>
                                    <label class="flex items-center gap-3 px-3 py-2 rounded-lg border border-slate-200 hover:bg-slate-50 cursor-pointer transition spec-checkbox-wrapper">
                                        <input type="checkbox"
                                               class="spec-checkbox rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                               value="<?= (int)$s['specialization_id'] ?>">
                                        <span class="text-sm text-slate-700"><?= htmlspecialchars($s['specialization_name']) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <p class="mt-1.5 text-xs text-red-600 hidden" data-error-for="specializations"></p>

                    
                    <div id="primaryWrapper" class="hidden mt-4">
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">
                            Primary Specialization
                        </label>
                        <select id="primary_specialization_id"
                                class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">— None marked as primary —</option>
                        </select>
                        <p class="mt-1.5 text-xs text-slate-500">Only one specialization can be marked as primary.</p>
                        <p class="mt-1.5 text-xs text-red-600 hidden" data-error-for="primary_specialization_id"></p>
                    </div>
                </div>

                <div id="passwordFieldWrapper" class="sm:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Password</label>
                    <input type="password" id="password" minlength="6"
                           class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="At least 6 characters">
                    <p class="mt-1.5 text-xs text-red-600 hidden" data-error-for="password"></p>
                </div>

            </div>

            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 rounded-b-2xl flex items-center justify-end gap-3">
                <button type="button" data-close-modal
                        class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Cancel
                </button>
                <button type="submit" id="doctorSubmitBtn"
                        class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60">
                    <span id="doctorSubmitLabel">Create Doctor</span>
                </button>
            </div>
        </form>
    </div>
</div>


<div id="pwModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/50" data-close-pw></div>

    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="flex items-center justify-between px-6 py-5 border-b border-slate-200">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Change Password</h3>
                    <p class="text-xs text-slate-500">
                        New password for <strong id="pwDoctorName" class="text-slate-700"></strong>
                    </p>
                </div>
            </div>
            <button type="button" data-close-pw
                    class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form id="pwForm" method="POST" novalidate>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">New Password</label>
                    <input type="password" id="pw_password" required minlength="6"
                           class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <p class="mt-1.5 text-xs text-red-600 hidden" data-error-for="password"></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Confirm Password</label>
                    <input type="password" id="pw_password_confirm" required minlength="6"
                           class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <p class="mt-1.5 text-xs text-red-600 hidden" data-error-for="password_confirm"></p>
                </div>
            </div>

            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 rounded-b-2xl flex items-center justify-end gap-3">
                <button type="button" data-close-pw
                        class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Cancel
                </button>
                <button type="submit" id="pwSubmitBtn"
                        class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60">
                    <span id="pwSubmitLabel">Update Password</span>
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
                    <h3 class="text-base font-semibold text-slate-900">Archive this doctor?</h3>
                    <p class="mt-1.5 text-sm text-slate-500">
                        <strong id="confirmDoctorName" class="text-slate-700"></strong> will no longer be able to log in.
                        The account is archived, not deleted. You can reactivate it later.
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
                <span id="confirmDeactivateLabel">Archive Doctor</span>
            </button>
        </div>
    </div>
</div>