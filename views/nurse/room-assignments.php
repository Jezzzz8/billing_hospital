<?php


require_once __DIR__ . '/../../controllers/NurseController.php';

$controller = new NurseController($pdo);
$admissionId = (int)($_GET['admission_id'] ?? 0);

$admission = $admissionId > 0 ? $controller->getPatientByAdmission($admissionId) : null;
$preferredRoomTypeName = '';
if ($admission && preg_match('/Preferred room type:\s*([^\.\r\n]+)/', (string)($admission['notes'] ?? ''), $matches)) {
    $preferredRoomTypeName = trim($matches[1]);
}

if ($admission && (int)($admission['status_id'] ?? 0) === 2) {
    header('Location: ' . BASE_URL . '/index.php?page=nurse-admissions');
    exit;
}

$currentAssignment = null;
$transferHistory = [];

if ($admission) {
    $assignments = $controller->getRoomAssignments($admissionId);
    foreach ($assignments as $a) {
        if ((int)$a['is_active'] === 1 || empty($a['end_datetime'])) {
            $currentAssignment = $a;
            break;
        }
    }
    $transferHistory = $controller->getRoomTransferHistory($admissionId);
}

$availableRooms  = $controller->getAvailableRooms();
$roomTypes       = $controller->getRoomTypes();


$allDoctors      = $controller->getDoctors();
$assignedDoctors = $admission ? $controller->getAdmissionDoctors($admissionId) : [];


if (!$admission) {
    $admissions = $controller->getAdmissions();
    ?>
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Manage Admission</h1>
        <p class="mt-1 text-sm text-slate-500">
            <?php if ($admissionId > 0): ?>
                Admission #<?= $admissionId ?> was not found or has been discharged.
            <?php else: ?>
                Select an admission to manage its room and doctors.
            <?php endif; ?>
        </p>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="text-left px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Patient</th>
                        <th class="text-left px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Room</th>
                        <th class="text-left px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Admitted</th>
                        <th class="text-left px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Status</th>
                        <th class="text-right px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php
                    $any = false;
                    foreach ($admissions as $a):
                        if (!empty($a['discharge_datetime'])) continue;
                        $any = true;
                    ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4">
                                <p class="font-semibold text-slate-900"><?= htmlspecialchars($a['first_name'] . ' ' . $a['last_name']) ?></p>
                                <p class="text-xs text-slate-500">#<?= (int)$a['admission_id'] ?></p>
                            </td>
                            <td class="px-6 py-4">
                                <?php if (!empty($a['room_number'])): ?>
                                    <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-medium"
                                          style="background-color: <?= htmlspecialchars($a['room_status_color'] ?? '#6b7280') ?>1A;
                                                 color: <?= htmlspecialchars($a['room_status_color'] ?? '#6b7280') ?>;
                                                 border-color: <?= htmlspecialchars($a['room_status_color'] ?? '#6b7280') ?>40;">
                                        <span class="w-1.5 h-1.5 rounded-full" style="background-color: <?= htmlspecialchars($a['room_status_color'] ?? '#6b7280') ?>"></span>
                                        <?= htmlspecialchars($a['room_number']) ?>
                                    </span>
                                    <p class="text-xs text-slate-500 mt-1"><?= htmlspecialchars($a['room_type_name'] ?? '') ?></p>
                                <?php else: ?>
                                    <span class="text-xs text-amber-600">No room assigned</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-slate-600">
                                <?= htmlspecialchars(date('M j, Y g:i A', strtotime($a['admission_datetime']))) ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-medium"
                                      style="background-color: <?= htmlspecialchars($a['admission_status_color'] ?? '#6b7280') ?>1A;
                                             color: <?= htmlspecialchars($a['admission_status_color'] ?? '#6b7280') ?>;
                                             border-color: <?= htmlspecialchars($a['admission_status_color'] ?? '#6b7280') ?>40;">
                                    <?= htmlspecialchars($a['admission_status_name'] ?? '—') ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <a href="<?= BASE_URL ?>/index.php?page=nurse-room-assignments&admission_id=<?= (int)$a['admission_id'] ?>"
                                   class="inline-flex items-center gap-1.5 rounded-lg border border-blue-200 bg-blue-50 px-2.5 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-100">
                                    Manage
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (!$any): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-400">
                                <p class="text-sm font-medium text-slate-600">No active admissions</p>
                                <p class="text-xs mt-1">Admit a patient first to assign a room.</p>
                                <a href="<?= BASE_URL ?>/index.php?page=nurse-admission-create"
                                   class="inline-flex items-center gap-2 mt-4 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                                    New Admission
                                </a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
    return;
}
?>

<div class="mb-8">
    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Manage Admission</h1>
    <p class="mt-1 text-sm text-slate-500">Manage room assignment and attending doctors for this admission.<?= $preferredRoomTypeName !== '' ? ' Patient preference: ' . htmlspecialchars($preferredRoomTypeName) . '.' : '' ?></p>
</div>

<div id="alert" class="hidden mb-5 rounded-lg px-4 py-3 text-sm border"></div>


<div class="bg-white rounded-xl border border-slate-200 p-6 mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-lg shrink-0">
                <?= strtoupper(substr($admission['first_name'], 0, 1) . substr($admission['last_name'], 0, 1)) ?>
            </div>
            <div>
                <h2 class="text-lg font-bold text-slate-900"><?= htmlspecialchars($admission['first_name'] . ' ' . $admission['last_name']) ?></h2>
                <p class="text-sm text-slate-500">Admission #<?= (int)$admission['admission_id'] ?> · Admitted <?= htmlspecialchars(date('M j, Y g:i A', strtotime($admission['admission_datetime']))) ?></p>
                <?php if (!empty($admission['chief_complaint'])): ?>
                    <p class="text-sm text-slate-600 mt-1">Chief complaint: <?= htmlspecialchars($admission['chief_complaint']) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <span class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-sm font-medium self-start sm:self-auto"
              style="background-color: <?= htmlspecialchars($admission['color_code'] ?? '#6b7280') ?>1A;
                     color: <?= htmlspecialchars($admission['color_code'] ?? '#6b7280') ?>;
                     border-color: <?= htmlspecialchars($admission['color_code'] ?? '#6b7280') ?>40;">
            <span class="w-2 h-2 rounded-full" style="background-color: <?= htmlspecialchars($admission['color_code'] ?? '#6b7280') ?>"></span>
            <?= htmlspecialchars($admission['status_name'] ?? '—') ?>
        </span>
    </div>
</div>


<div class="bg-white rounded-xl border border-slate-200 p-6 mb-6" id="doctorsPanel">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h3 class="text-base font-semibold text-slate-900">Assigned Doctors</h3>
            <p class="text-xs text-slate-500 mt-0.5">
                Doctors assigned here will see this patient on their dashboard, patient list, service requests, and diagnosis pages.
            </p>
        </div>
        <span id="doctorCountBadge" class="text-xs font-medium text-slate-500">
            <?= count($assignedDoctors) ?> assigned
        </span>
    </div>

    
    <div id="assignedDoctorsList" class="space-y-2 mb-4">
        <?php if (empty($assignedDoctors)): ?>
            <div id="noDoctorsMsg" class="text-center py-6 border border-dashed border-slate-300 rounded-lg">
                <p class="text-sm font-medium text-slate-600">No doctors assigned yet</p>
                <p class="text-xs text-slate-500 mt-1">Add at least one doctor so they can access this patient's chart.</p>
            </div>
        <?php else: ?>
            <?php foreach ($assignedDoctors as $d): ?>
                <div class="flex items-center gap-3 p-3 rounded-lg border border-slate-200 bg-slate-50"
                     data-admission-doctor-id="<?= (int)$d['admission_doctor_id'] ?>">
                    <div class="w-9 h-9 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center font-semibold text-xs shrink-0">
                        <?= strtoupper(substr($d['first_name'], 0, 1) . substr($d['last_name'], 0, 1)) ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-slate-900 truncate">
                            Dr. <?= htmlspecialchars($d['first_name'] . ' ' . $d['last_name']) ?>
                        </p>
                        <p class="text-xs text-slate-500">
                            <?= htmlspecialchars($d['doctor_role']) ?>
                            · Assigned <?= htmlspecialchars(date('M j, Y', strtotime($d['assigned_datetime']))) ?>
                            · ₱<?= number_format((float)$d['consultation_fee_charged'], 2) ?>
                        </p>
                    </div>
                    <button type="button"
                            class="remove-doctor-btn shrink-0 text-xs font-medium text-rose-600 hover:text-rose-700"
                            data-admission-doctor-id="<?= (int)$d['admission_doctor_id'] ?>"
                            data-doctor-name="Dr. <?= htmlspecialchars($d['first_name'] . ' ' . $d['last_name']) ?>">
                        Remove
                    </button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    
    <div class="flex flex-col sm:flex-row gap-2 pt-4 border-t border-slate-100">
        <select id="newDoctorSelect"
                class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm bg-white
                       focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            <option value="">— Select a doctor to add —</option>
            <?php foreach ($allDoctors as $doc): ?>
                <option value="<?= (int)$doc['doctor_id'] ?>">
                    Dr. <?= htmlspecialchars($doc['first_name'] . ' ' . $doc['last_name']) ?>
                    (₱<?= number_format((float)$doc['consultation_fee'], 2) ?>)
                </option>
            <?php endforeach; ?>
        </select>
        <select id="newDoctorRole"
                class="rounded-lg border border-slate-300 px-3 py-2 text-sm bg-white
                       focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            <option value="Attending">Attending</option>
            <option value="Consulting">Consulting</option>
            <option value="Referring">Referring</option>
        </select>
        <button type="button" id="addDoctorBtn"
                class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Add Doctor
        </button>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    
    <div class="lg:col-span-1">
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h3 class="text-base font-semibold text-slate-900 mb-4">Current Room</h3>

            <?php if ($currentAssignment): ?>
                <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-4 mb-4">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-lg font-bold text-slate-900">Room <?= htmlspecialchars($currentAssignment['room_number']) ?></p>
                            <p class="text-sm text-slate-600"><?= htmlspecialchars($currentAssignment['room_type_name']) ?></p>
                        </div>
                    </div>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-slate-500">Daily Rate</span>
                            <span class="font-semibold text-slate-900">₱<?= number_format((float)$currentAssignment['daily_rate_at_assignment'], 2) ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Assigned Since</span>
                            <span class="font-medium text-slate-900"><?= htmlspecialchars(date('M j, Y', strtotime($currentAssignment['start_datetime']))) ?></span>
                        </div>
                    </div>
                </div>

                <button type="button" id="transferBtn"
                        class="w-full inline-flex items-center justify-center gap-2 rounded-lg border border-amber-300 bg-amber-50 px-4 py-2.5 text-sm font-semibold text-amber-700 hover:bg-amber-100">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                    </svg>
                    Transfer Room
                </button>
            <?php else: ?>
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-4 text-center">
                    <svg class="w-10 h-10 text-amber-500 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M5 19h14a2 2 0 001.84-2.75L13.74 4a2 2 0 00-3.48 0L3.16 16.25A2 2 0 005 19z"/>
                    </svg>
                    <p class="text-sm font-medium text-amber-800">No room assigned</p>
                    <p class="text-xs text-amber-600 mt-1">Select a room from the list to assign one.</p>
                </div>
            <?php endif; ?>

            
            <?php if (count($transferHistory) > 1): ?>
                <div class="mt-6 pt-4 border-t border-slate-200">
                    <h4 class="text-sm font-semibold text-slate-700 mb-3">Transfer History</h4>
                    <div class="space-y-2 max-h-48 overflow-y-auto">
                        <?php foreach (array_reverse($transferHistory) as $h): ?>
                            <div class="flex items-start gap-2 text-xs">
                                <div class="w-1.5 h-1.5 rounded-full bg-slate-400 mt-1.5 shrink-0"></div>
                                <div class="flex-1">
                                    <p class="font-medium text-slate-700">Room <?= htmlspecialchars($h['room_number']) ?> (<?= htmlspecialchars($h['room_type_name']) ?>)</p>
                                    <p class="text-slate-500">
                                        <?= htmlspecialchars(date('M j, g:i A', strtotime($h['start_datetime']))) ?>
                                        <?php if ($h['end_datetime']): ?>
                                            — <?= htmlspecialchars(date('M j, g:i A', strtotime($h['end_datetime']))) ?>
                                        <?php else: ?>
                                            — Present
                                        <?php endif; ?>
                                    </p>
                                    <?php if (!empty($h['transfer_reason'])): ?>
                                        <p class="text-slate-400 italic mt-0.5"><?= htmlspecialchars($h['transfer_reason']) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <?php
            $trulyAvailable = count(array_filter($availableRooms, fn($x) => $x['status_name'] === 'Available'));
            ?>
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-slate-900">Available Rooms</h3>
                <span class="text-xs text-slate-500"><?= $trulyAvailable ?> room(s) available</span>
            </div>

            <div class="flex flex-wrap gap-2 mb-4">
                <button type="button" class="room-type-filter active px-3 py-1.5 text-xs font-medium rounded-full border border-blue-200 bg-blue-50 text-blue-700"
                        data-type-id="">All Types</button>
                <?php foreach ($roomTypes as $rt): ?>
                    <button type="button" class="room-type-filter px-3 py-1.5 text-xs font-medium rounded-full border border-slate-200 bg-white text-slate-600 hover:bg-slate-50"
                            data-type-id="<?= (int)$rt['room_type_id'] ?>">
                        <?= htmlspecialchars($rt['room_type_name']) ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <div id="roomList" class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-96 overflow-y-auto">
                <?php if (empty($availableRooms)): ?>
                    <div class="col-span-2 text-center py-8 text-slate-400">
                        <svg class="w-12 h-12 mx-auto mb-3 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                        <p class="text-sm font-medium">No rooms available</p>
                        <p class="text-xs mt-1">All rooms are currently occupied or unavailable.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($availableRooms as $r):
                        $heldByThis = $currentAssignment
                            && (int)$currentAssignment['room_id'] === (int)$r['room_id'];
                        $isBlocked = (int)$r['available_beds'] <= 0 && !$heldByThis;
                    ?>
                        <div class="room-option flex items-center gap-3 p-4 rounded-lg border transition
                                    <?= $isBlocked
                                          ? 'border-slate-200 bg-slate-50 opacity-60 pointer-events-none'
                                          : 'border-slate-200 hover:border-blue-300 hover:bg-blue-50 cursor-pointer' ?>"
                             data-type-id="<?= (int)$r['room_type_id'] ?>"
                             data-room-id="<?= (int)$r['room_id'] ?>"
                             data-room-number="<?= htmlspecialchars($r['room_number']) ?>"
                             data-available-beds="<?= (int)$r['available_beds'] ?>"
                             data-status="<?= htmlspecialchars($r['status_name']) ?>">
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0
                                        <?= $isBlocked
                                              ? 'bg-slate-100 text-slate-400'
                                              : 'bg-emerald-50 text-emerald-600' ?>">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-slate-900">
                                    Room <?= htmlspecialchars($r['room_number']) ?>
                                    <?php if ($heldByThis): ?>
                                        <span class="ml-1 text-xs font-normal text-emerald-600">(current)</span>
                                    <?php endif; ?>
                                </p>
                                <p class="text-xs text-slate-500">
                                    <?= htmlspecialchars($r['room_type_name']) ?> · Floor <?= (int)$r['floor_level'] ?>
                                </p>
                                <p class="text-xs text-slate-500">
                                    <?= (int)$r['available_beds'] ?> of <?= (int)$r['capacity'] ?> beds available
                                </p>
                                <p class="text-sm font-medium <?= $isBlocked ? 'text-slate-400' : 'text-blue-700' ?> mt-1">
                                    ₱<?= number_format((float)$r['rate_per_day'], 2) ?>/day
                                </p>
                            </div>
                            <?php if ($isBlocked): ?>
                                <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 border border-rose-100 px-2 py-0.5 text-xs font-medium text-rose-700">
                                    Full
                                </span>
                            <?php else: ?>
                                <svg class="w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                </svg>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>


<div id="transferModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/50" data-close-modal></div>

    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="flex items-center justify-between px-6 py-5 border-b border-slate-200">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Transfer Room</h3>
                    <p class="text-xs text-slate-500">Move patient to a different room.</p>
                </div>
            </div>
            <button type="button" data-close-modal class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form id="transferForm" class="p-6 space-y-4">
            <input type="hidden" name="admission_id" value="<?= (int)$admissionId ?>">

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">New Room</label>
                <select name="new_room_id" required
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">— Select room —</option>
                    <?php foreach ($availableRooms as $r):
                        if ($r['status_name'] === 'Occupied') continue;
                    ?>
                        <option value="<?= (int)$r['room_id'] ?>">
                            <?= htmlspecialchars($r['room_number']) ?> — <?= htmlspecialchars($r['room_type_name']) ?>
                            (₱<?= number_format((float)$r['rate_per_day'], 2) ?>/day)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Transfer Reason</label>
                <input type="text" name="transfer_reason" placeholder="e.g. Patient requested upgrade"
                       class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <label class="inline-flex items-center gap-3 cursor-pointer select-none">
                <input type="checkbox" name="set_old_room_maintenance" value="1"
                       class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 w-4 h-4">
                <span class="text-sm text-slate-700">Set old room to Maintenance</span>
            </label>
        </form>

        <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 rounded-b-2xl flex items-center justify-end gap-3">
            <button type="button" data-close-modal
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Cancel
            </button>
            <button type="button" id="confirmTransferBtn"
                    class="inline-flex items-center gap-2 rounded-lg bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-amber-700 disabled:opacity-60">
                <span id="confirmTransferLabel">Transfer Room</span>
            </button>
        </div>
    </div>
</div>

<script>
(function() {
    const baseUrl = document.body.dataset.baseUrl;
    const admissionId = <?= (int)$admissionId ?>;
    const alertEl = document.getElementById('alert');

    function showAlert(type, msg) {
        alertEl.className = 'mb-5 rounded-lg px-4 py-3 text-sm border ' +
            (type === 'success'
                ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
                : 'border-rose-200 bg-rose-50 text-rose-800');
        alertEl.textContent = msg;
        alertEl.classList.remove('hidden');
        alertEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }




    const addBtn  = document.getElementById('addDoctorBtn');
    const docSel  = document.getElementById('newDoctorSelect');
    const roleSel = document.getElementById('newDoctorRole');

    if (addBtn) {
        addBtn.addEventListener('click', async function() {
            const doctorId = docSel.value;
            if (!doctorId) {
                showAlert('error', 'Please select a doctor first.');
                return;
            }

            addBtn.disabled = true;
            const originalHtml = addBtn.innerHTML;
            addBtn.innerHTML = '<span>Adding…</span>';

            try {
                const res = await axios.post(`${baseUrl}/api/nurses/assign-doctor.php`, {
                    admission_id: admissionId,
                    doctor_id:    doctorId,
                    doctor_role:  roleSel.value
                }, { headers: { 'Content-Type': 'application/json' } });

                if (res.data.success) {
                    showAlert('success', res.data.message);
                    setTimeout(() => location.reload(), 700);
                }
            } catch (err) {
                showAlert('error', err.response?.data?.message || 'Could not add doctor.');
                addBtn.disabled = false;
                addBtn.innerHTML = originalHtml;
            }
        });
    }

    document.querySelectorAll('.remove-doctor-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            const name = this.dataset.doctorName;
            if (!confirm(`Remove ${name} from this admission?\n\nThey will no longer see this patient on their dashboard.`)) return;

            const id = this.dataset.admissionDoctorId;
            this.disabled = true;
            this.textContent = 'Removing…';

            try {
                const res = await axios.post(`${baseUrl}/api/nurses/remove-doctor.php?id=${id}`);
                if (res.data.success) {
                    showAlert('success', res.data.message);
                    setTimeout(() => location.reload(), 700);
                }
            } catch (err) {
                showAlert('error', err.response?.data?.message || 'Could not remove doctor.');
                this.disabled = false;
                this.textContent = 'Remove';
            }
        });
    });




    document.querySelectorAll('.room-type-filter').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.room-type-filter').forEach(b => {
                b.classList.remove('active', 'border-blue-200', 'bg-blue-50', 'text-blue-700');
                b.classList.add('border-slate-200', 'bg-white', 'text-slate-600');
            });
            this.classList.add('active', 'border-blue-200', 'bg-blue-50', 'text-blue-700');
            this.classList.remove('border-slate-200', 'bg-white', 'text-slate-600');

            const typeId = this.dataset.typeId;
            document.querySelectorAll('.room-option').forEach(opt => {
                if (!typeId || opt.dataset.typeId === typeId) {
                    opt.style.display = '';
                } else {
                    opt.style.display = 'none';
                }
            });
        });
    });




    document.querySelectorAll('.room-option').forEach(opt => {
        opt.addEventListener('click', async function() {
            if (this.dataset.status === 'Occupied') {
                return;
            }

            if (!confirm(`Assign Room ${this.dataset.roomNumber} to this patient?`)) return;

            const roomId = this.dataset.roomId;
            this.style.opacity = '0.5';
            this.style.pointerEvents = 'none';

            try {
                const response = await axios.post(`${baseUrl}/api/nurses/room-assign.php`, {
                    admission_id: admissionId,
                    room_id: roomId
                }, { headers: { 'Content-Type': 'application/json' } });

                if (response.data.success) {
                    showAlert('success', response.data.message);
                    setTimeout(() => location.reload(), 1000);
                }
            } catch (err) {
                const res = err.response?.data;
                showAlert('error', res?.message || 'Could not assign room.');
                this.style.opacity = '1';
                this.style.pointerEvents = '';
            }
        });
    });




    const transferBtn = document.getElementById('transferBtn');
    const transferModal = document.getElementById('transferModal');

    if (transferBtn && transferModal) {
        transferBtn.addEventListener('click', () => transferModal.classList.remove('hidden'));

        transferModal.querySelectorAll('[data-close-modal]').forEach(el => {
            el.addEventListener('click', () => transferModal.classList.add('hidden'));
        });

        document.getElementById('confirmTransferBtn').addEventListener('click', async function() {
            const form = document.getElementById('transferForm');
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());

            if (!data.new_room_id) {
                showAlert('error', 'Please select a room.');
                return;
            }

            const btn = this;
            const label = document.getElementById('confirmTransferLabel');
            btn.disabled = true;
            label.textContent = 'Transferring…';

            try {
                const response = await axios.post(`${baseUrl}/api/nurses/room-transfer.php`, data, {
                    headers: { 'Content-Type': 'application/json' }
                });

                if (response.data.success) {
                    showAlert('success', response.data.message);
                    setTimeout(() => location.reload(), 1000);
                }
            } catch (err) {
                const res = err.response?.data;
                showAlert('error', res?.message || 'Could not transfer room.');
            } finally {
                btn.disabled = false;
                label.textContent = 'Transfer Room';
            }
        });
    }
})();
</script>