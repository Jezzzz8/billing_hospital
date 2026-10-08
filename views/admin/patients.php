<?php

require_once __DIR__ . '/../../controllers/PatientController.php';

$controller       = new PatientController($pdo);
$patients         = $controller->getAll();
$genders          = $controller->getGenders();
$admissionStatus  = $controller->getAdmissionStatuses();
$availableRooms   = $controller->getAvailableRooms();
$doctors          = $controller->getDoctors();
$allDiagnoses     = $controller->getDiagnoses();

$byId = [];
foreach ($patients as $row) {
    $byId[(int)$row['patient_id']] = $row;
}
$patients = array_values($byId);

$totalPatients    = count($patients);
$activePatients   = count(array_filter($patients, fn($p) => (int)$p['is_active'] === 1));
$archivedPatients = $totalPatients - $activePatients;
$admittedNow      = count(array_filter($patients, fn($p) => !empty($p['admission_id'])));
?>


<div id="patientsData"
     class="hidden"
     data-doctors='<?= htmlspecialchars(json_encode(array_map(fn($d) => [
        'doctor_id' => (int)$d['doctor_id'],
        'name'      => 'Dr. ' . $d['first_name'] . ' ' . $d['last_name'],
        'fee'       => (float)$d['consultation_fee'],
     ], $doctors)), ENT_QUOTES, "UTF-8") ?>'
     data-diagnoses='<?= htmlspecialchars(json_encode(array_map(fn($d) => [
        'diagnosis_id'   => (int)$d['diagnosis_id'],
        'diagnosis_name' => $d['diagnosis_name'],
        'icd_code'       => $d['icd_code'] ?? '',
     ], $allDiagnoses)), ENT_QUOTES, "UTF-8") ?>'></div>


<div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Patients</h1>
        <p class="mt-1 text-sm text-slate-500">Register patients, admit them, and assign rooms, doctors, and diagnoses.</p>
    </div>
    <button type="button" id="openCreateBtn"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        New Patient
    </button>
</div>

<div id="alert" class="hidden mb-5 rounded-lg px-4 py-3 text-sm border"></div>


<div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Total Patients</p>
        <p id="totalPatients" class="mt-2 text-2xl font-bold text-slate-900"><?= $totalPatients ?></p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Active</p>
        <p id="activePatients" class="mt-2 text-2xl font-bold text-emerald-600"><?= $activePatients ?></p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Currently Admitted</p>
        <p id="admittedPatients" class="mt-2 text-2xl font-bold text-blue-600"><?= $admittedNow ?></p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Archived</p>
        <p id="archivedPatients" class="mt-2 text-2xl font-bold text-slate-400"><?= $archivedPatients ?></p>
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
                <input type="text" id="filterSearch" placeholder="Search name, email, room…"
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
        Showing <strong id="filteredCount" class="text-slate-900">0</strong> of <strong id="totalCount" class="text-slate-900">0</strong> patients
    </div>
</div>


<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="text-left px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Patient</th>
                    <th class="text-left px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Contact</th>
                    <th class="text-left px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Admission</th>
                    <th class="text-left px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Room</th>
                    <th class="text-left px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">State</th>
                    <th class="text-right px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Actions</th>
                </tr>
            </thead>
            <tbody id="patientRows" class="divide-y divide-slate-100">
                <?php foreach ($patients as $p):
                    $search = strtolower($p['first_name'] . ' ' . $p['last_name'] . ' ' . ($p['email'] ?? '') . ' ' . ($p['room_number'] ?? ''));
                ?>
                    <tr class="hover:bg-slate-50 patient-row"
                        data-search="<?= htmlspecialchars($search) ?>"
                        data-status="<?= (int)$p['is_active'] ?>">

                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full bg-slate-100 text-slate-700 flex items-center justify-center font-semibold text-xs shrink-0">
                                    <?= strtoupper(substr($p['first_name'], 0, 1) . substr($p['last_name'], 0, 1)) ?>
                                </div>
                                <div class="min-w-0">
                                    <p class="font-semibold text-slate-900 truncate">
                                        <?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?>
                                    </p>
                                    <p class="text-xs text-slate-500"><?= htmlspecialchars($p['gender_name']) ?> · <?= htmlspecialchars($p['birth_date'] ?? '—') ?></p>
                                </div>
                            </div>
                        </td>

                        <td class="px-6 py-4">
                            <p class="text-slate-700 text-xs truncate"><?= htmlspecialchars($p['email'] ?? '—') ?></p>
                            <p class="text-xs text-slate-500"><?= htmlspecialchars($p['contact_number'] ?? '') ?></p>
                        </td>

                        <td class="px-6 py-4">
                            <?php if (!empty($p['admission_id'])): ?>
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium border"
                                      style="background-color: <?= htmlspecialchars($p['admission_status_color'] ?? '#6b7280') ?>1A;
                                             color: <?= htmlspecialchars($p['admission_status_color'] ?? '#6b7280') ?>;
                                             border-color: <?= htmlspecialchars($p['admission_status_color'] ?? '#6b7280') ?>40;">
                                    <span class="w-1.5 h-1.5 rounded-full" style="background-color: <?= htmlspecialchars($p['admission_status_color'] ?? '#6b7280') ?>"></span>
                                    <?= htmlspecialchars($p['admission_status_name'] ?? '—') ?>
                                </span>
                                <p class="text-xs text-slate-500 mt-1">
                                    <?= htmlspecialchars(date('M j, Y', strtotime($p['admission_datetime']))) ?>
                                </p>
                            <?php else: ?>
                                <span class="text-xs text-slate-400 italic">Not admitted</span>
                            <?php endif; ?>
                        </td>

                        <td class="px-6 py-4">
                            <?php if (!empty($p['room_number'])): ?>
                                <p class="font-medium text-slate-900">
                                    <?= htmlspecialchars($p['room_number']) ?>
                                    <?php if ((int)($p['room_assignment_is_active'] ?? 0) !== 1): ?>
                                        <span class="ml-1 text-xs font-normal text-slate-400">(past)</span>
                                    <?php endif; ?>
                                </p>
                                <p class="text-xs text-slate-500"><?= htmlspecialchars($p['room_type_name'] ?? '') ?></p>
                            <?php else: ?>
                                <span class="text-xs text-slate-400">—</span>
                            <?php endif; ?>
                        </td>

                        <td class="px-6 py-4">
                            <?php if ((int)$p['is_active'] === 1): ?>
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
                                        data-patient-id="<?= (int)$p['patient_id'] ?>">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                    Edit
                                </button>

                                <?php if ((int)$p['is_active'] === 1): ?>
                                    <button type="button"
                                            class="deactivate-btn inline-flex items-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-100 hover:border-rose-300"
                                            data-patient-id="<?= (int)$p['patient_id'] ?>"
                                            data-patient-name="<?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?>">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                        </svg>
                                        Archive
                                    </button>
                                <?php else: ?>
                                    <button type="button"
                                            class="reactivate-btn inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1.5 text-xs font-medium text-emerald-700 hover:bg-emerald-100 hover:border-emerald-300"
                                            data-patient-id="<?= (int)$p['patient_id'] ?>"
                                            data-patient-name="<?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?>">
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
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
        </div>
        <p class="text-sm font-medium text-slate-700">No patients match your filters</p>
        <p class="text-xs text-slate-500 mt-1">Try adjusting your search or clearing the filters.</p>
    </div>
</div>


<div id="patientModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/60" data-close-modal></div>

    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[95vh] flex flex-col">

        
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <div>
                    <h3 id="patientModalTitle" class="text-base font-semibold text-slate-900">New Patient</h3>
                    <p class="text-xs text-slate-500">Fill in patient info, then optionally admit them.</p>
                </div>
            </div>
            <button type="button" data-close-modal class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        
        <div class="border-b border-slate-200 px-6">
            <nav class="flex gap-1 -mb-px overflow-x-auto">
                <button type="button" class="tab-btn px-4 py-3 text-sm font-medium border-b-2 border-blue-600 text-blue-600 whitespace-nowrap" data-tab="info">Patient Info</button>
                <button type="button" class="tab-btn px-4 py-3 text-sm font-medium border-b-2 border-transparent text-slate-500 hover:text-slate-700 whitespace-nowrap" data-tab="admission">Admission</button>
                <button type="button" class="tab-btn px-4 py-3 text-sm font-medium border-b-2 border-transparent text-slate-500 hover:text-slate-700 whitespace-nowrap" data-tab="room">Room</button>
                <button type="button" class="tab-btn px-4 py-3 text-sm font-medium border-b-2 border-transparent text-slate-500 hover:text-slate-700 whitespace-nowrap" data-tab="doctors">Doctors</button>
                <button type="button" class="tab-btn px-4 py-3 text-sm font-medium border-b-2 border-transparent text-slate-500 hover:text-slate-700 whitespace-nowrap" data-tab="diagnoses">Diagnoses</button>
            </nav>
        </div>

        
        <form id="patientForm" method="POST" novalidate class="flex-1 flex flex-col min-h-0">
            <input type="hidden" id="patient_id" name="patient_id">
            <input type="hidden" id="admission_id" name="admission_id">

            <div class="flex-1 overflow-y-auto p-6">

                
                <div class="tab-panel" data-panel="info">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">First Name</label>
                            <input type="text" id="first_name" required class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <p class="mt-1.5 text-xs text-red-600 hidden" data-error-for="first_name"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Last Name</label>
                            <input type="text" id="last_name" required class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <p class="mt-1.5 text-xs text-red-600 hidden" data-error-for="last_name"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Gender</label>
                            <select id="gender_id" required class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">— Select gender —</option>
                                <?php foreach ($genders as $g): ?>
                                    <option value="<?= (int)$g['gender_id'] ?>"><?= htmlspecialchars($g['gender_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="mt-1.5 text-xs text-red-600 hidden" data-error-for="gender_id"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Birth Date</label>
                            <input type="date" id="birth_date" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
                            <input type="email" id="email" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <p class="mt-1.5 text-xs text-red-600 hidden" data-error-for="email"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Contact Number</label>
                            <input type="text" id="contact_number" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Address</label>
                            <input type="text" id="address" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Emergency Contact</label>
                            <input type="text" id="emergency_contact" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Emergency Contact Number</label>
                            <input type="text" id="emergency_contact_number" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Medical History</label>
                            <textarea id="medical_history" rows="3" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                        </div>
                    </div>
                </div>

                
                <div class="tab-panel hidden" data-panel="admission">
                    <div class="bg-blue-50 border border-blue-100 rounded-lg p-4 mb-5">
                        <label class="inline-flex items-center gap-3 cursor-pointer select-none">
                            <input type="checkbox" id="create_admission" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 w-4 h-4">
                            <span class="text-sm font-medium text-blue-900">Admit this patient now</span>
                        </label>
                        <p class="text-xs text-blue-700 mt-1">If unchecked, only the patient record is created. You can admit them later.</p>
                    </div>

                    <div id="admissionFields" class="grid grid-cols-1 sm:grid-cols-2 gap-5 opacity-50 pointer-events-none">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Admission Status</label>
                            <select id="admission_status_id" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <?php foreach ($admissionStatus as $s): ?>
                                    <option value="<?= (int)$s['status_id'] ?>" <?= (int)$s['status_id'] === 1 ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($s['status_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="mt-1.5 text-xs text-red-600 hidden" data-error-for="admission_status_id"></p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Admission Type</label>
                            <select id="admission_type" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="Emergency">Emergency</option>
                                <option value="Elective">Elective</option>
                                <option value="Urgent">Urgent</option>
                            </select>
                            <p class="mt-1.5 text-xs text-red-600 hidden" data-error-for="admission_type"></p>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Chief Complaint</label>
                            <input type="text" id="chief_complaint" placeholder="e.g. Chest pain and dizziness" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Admission Notes</label>
                            <textarea id="admission_notes" rows="2" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                        </div>
                    </div>
                </div>

                
                <div class="tab-panel hidden" data-panel="room">
                    <div id="roomNotice" class="bg-amber-50 border border-amber-100 rounded-lg p-4 mb-5 text-sm text-amber-800">
                        Room assignment requires the patient to be admitted. Enable <strong>Admit this patient now</strong> in the Admission tab first.
                    </div>

                    <div id="roomFields" class="opacity-50 pointer-events-none">
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Available Rooms</label>
                        <select id="room_id" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">— No room assigned —</option>
                            <?php foreach ($availableRooms as $r): ?>
                                <option value="<?= (int)$r['room_id'] ?>"
                                        data-occupied-by="<?= (int)($r['occupied_by_admission_id'] ?? 0) ?>">
                                    <?= htmlspecialchars($r['room_number']) ?> — <?= htmlspecialchars($r['room_type_name']) ?>
                                    <?= $r['building_name'] ? ' · ' . htmlspecialchars($r['building_name']) : '' ?>
                                    <?= $r['status_name'] === 'Occupied' ? ' (Occupied)' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-xs text-slate-500 mt-2">Only active rooms with <strong>Available</strong> status are listed. Once assigned, the room becomes Occupied.</p>
                    </div>
                </div>

                
                <div class="tab-panel hidden" data-panel="doctors">
                    <div id="doctorsNotice" class="bg-amber-50 border border-amber-100 rounded-lg p-4 mb-5 text-sm text-amber-800">
                        Doctors can be assigned once the patient is admitted.
                    </div>

                    <div id="doctorsFields" class="opacity-50 pointer-events-none space-y-3">
                        <div class="flex items-center justify-between">
                            <p class="text-sm text-slate-600">Add one or more doctors to this admission.</p>
                            <button type="button" id="addDoctorRow"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-100">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                </svg>
                                Add Doctor
                            </button>
                        </div>

                        <div id="doctorsList" class="space-y-2"></div>
                    </div>
                </div>

                
                <div class="tab-panel hidden" data-panel="diagnoses">
                    <div id="diagnosesNotice" class="bg-amber-50 border border-amber-100 rounded-lg p-4 mb-5 text-sm text-amber-800">
                        Diagnoses can be assigned once the patient is admitted.
                    </div>

                    <div id="diagnosesFields" class="opacity-50 pointer-events-none space-y-3">
                        <div class="flex items-center justify-between">
                            <p class="text-sm text-slate-600">Add one or more diagnoses to this admission.</p>
                            <button type="button" id="addDiagnosisRow"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-100">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                </svg>
                                Add Diagnosis
                            </button>
                        </div>

                        <div id="diagnosesList" class="space-y-2"></div>
                    </div>
                </div>

            </div>

            
            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 rounded-b-2xl flex items-center justify-between gap-3">
                <button type="button" id="prevTab" class="hidden items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Back
                </button>
                <div class="flex-1"></div>
                <button type="button" id="nextTab" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Next
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
                <button type="button" data-close-modal class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Cancel
                </button>
                <button type="submit" id="patientSubmitBtn"
                        class="hidden inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60">
                    <span id="patientSubmitLabel">Save Patient</span>
                </button>
            </div>
        </form>
    </div>
</div>


<div id="confirmModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/50" data-close-confirm></div>

    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
        <div class="flex items-start gap-4">
            <div class="shrink-0 w-11 h-11 rounded-full bg-rose-50 flex items-center justify-center text-rose-600">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M5 19h14a2 2 0 001.84-2.75L13.74 4a2 2 0 00-3.48 0L3.16 16.25A2 2 0 005 19z"/>
                </svg>
            </div>
            <div>
                <h3 class="text-base font-semibold text-slate-900">Archive this patient?</h3>
                <p class="mt-1.5 text-sm text-slate-500">
                    <strong id="confirmPatientName" class="text-slate-700"></strong> will be marked as archived.
                    Existing records stay intact — you can reactivate later.
                </p>
            </div>
        </div>

        <div class="mt-6 flex items-center justify-end gap-3">
            <button type="button" data-close-confirm class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</button>
            <button type="button" id="confirmDeactivateBtn" class="inline-flex items-center gap-2 rounded-lg bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-rose-700 disabled:opacity-60">
                <span id="confirmDeactivateLabel">Archive Patient</span>
            </button>
        </div>
    </div>
</div>