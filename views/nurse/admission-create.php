<?php


require_once __DIR__ . '/../../controllers/NurseController.php';

$controller = new NurseController($pdo);
$genders = $controller->getGenders();
$roomTypes = $controller->getRoomTypes();
$availableRooms = $controller->getAvailableRooms();
$doctors = $controller->getDoctors();
$availableBedsByType = [];
foreach ($availableRooms as $room) {
    $typeId = (int)$room['room_type_id'];
    $availableBedsByType[$typeId] = ($availableBedsByType[$typeId] ?? 0) + (int)$room['available_beds'];
}

$selectedPatientId = (int)($_GET['patient_id'] ?? 0);
$selectedPatient = $selectedPatientId ? $controller->getPatientDetails($selectedPatientId) : null;
?>

<?php if ($selectedPatient && !empty($selectedPatient['current_admission'])): ?>
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Patient Already Admitted</h1>
        <p class="mt-1 text-sm text-slate-500">This patient already has an active admission. Manage the existing admission instead of creating another one.</p>
    </div>
    <div class="rounded-xl border border-amber-200 bg-amber-50 p-6">
        <p class="font-semibold text-amber-900">
            <?= htmlspecialchars($selectedPatient['first_name'] . ' ' . $selectedPatient['last_name']) ?>
            already has active admission #<?= (int)$selectedPatient['current_admission']['admission_id'] ?>.
        </p>
        <a href="<?= BASE_URL ?>/index.php?page=nurse-room-assignments&admission_id=<?= (int)$selectedPatient['current_admission']['admission_id'] ?>"
           class="mt-4 inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
            Manage existing admission
        </a>
    </div>
<?php else: ?>
<div class="mb-8">
    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">New Admission</h1>
    <p class="mt-1 text-sm text-slate-500">Admit a patient, assign a room, and choose the attending doctor(s).</p>
</div>

<div id="alert" class="hidden mb-5 rounded-lg px-4 py-3 text-sm border"></div>

<div class="bg-white rounded-xl border border-slate-200 p-6 max-w-3xl">
    <form id="admissionForm" method="POST" novalidate>
        <input type="hidden" name="patient_id" id="patient_id" value="<?= $selectedPatientId ?>">

        
        <div class="mb-6">
            <label class="block text-sm font-medium text-slate-700 mb-2">Patient <span class="text-rose-500">*</span></label>
            
            <?php if ($selectedPatient): ?>
                <div class="flex items-center gap-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <div class="w-12 h-12 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-semibold shrink-0">
                        <?= strtoupper(substr($selectedPatient['first_name'], 0, 1) . substr($selectedPatient['last_name'], 0, 1)) ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-slate-900"><?= htmlspecialchars($selectedPatient['first_name'] . ' ' . $selectedPatient['last_name']) ?></p>
                        <p class="text-xs text-slate-500"><?= htmlspecialchars($selectedPatient['gender_name']) ?> · <?= htmlspecialchars($selectedPatient['birth_date'] ?? '—') ?></p>
                    </div>
                    <a href="<?= BASE_URL ?>/index.php?page=nurse-patient-search"
                       class="text-xs font-medium text-blue-600 hover:text-blue-700">Change</a>
                </div>
            <?php else: ?>
                <div class="relative">
                    <input type="text" id="patientSearch" placeholder="Search patient by name, contact, or email…"
                           class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <div id="patientResults" class="hidden absolute z-10 w-full mt-1 bg-white border border-slate-200 rounded-lg shadow-lg max-h-64 overflow-y-auto"></div>
                </div>
                <p class="mt-1.5 text-xs text-slate-500">Start typing to search for an existing patient.</p>
            <?php endif; ?>
            <p class="mt-1.5 text-xs text-red-600 hidden" data-error-for="patient_id"></p>
        </div>

        
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-6">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Admission Type</label>
                <select name="admission_type" id="admission_type"
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="Emergency">Emergency</option>
                    <option value="Elective">Elective</option>
                    <option value="Urgent">Urgent</option>
                </select>
                <p class="mt-1.5 text-xs text-red-600 hidden" data-error-for="admission_type"></p>
            </div>

            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Chief Complaint</label>
                <input type="text" name="chief_complaint" id="chief_complaint" placeholder="e.g. Chest pain and dizziness"
                       class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Admission Notes</label>
                <textarea name="admission_notes" id="admission_notes" rows="2"
                          class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
            </div>
        </div>

        
        <div class="mb-6">
            <div class="flex items-center justify-between mb-2">
                <label class="block text-sm font-medium text-slate-700">
                    Attending Doctor(s)
                    <span class="text-slate-400 font-normal ml-1">(select one or more)</span>
                </label>
                <span id="doctorCount" class="text-xs text-slate-500">0 selected</span>
            </div>
            <p class="text-xs text-slate-500 mb-3">
                Patients assigned to a doctor appear on that doctor's dashboard, patient list,
                service requests, and diagnosis pages.
            </p>

            <?php if (empty($doctors)): ?>
                <p class="text-xs text-slate-400 italic border border-slate-200 rounded-lg p-3">
                    No active doctors available. Add a doctor first from the Doctors page.
                </p>
            <?php else: ?>
                <div class="space-y-2 border border-slate-200 rounded-lg p-3 max-h-64 overflow-y-auto">
                    <?php foreach ($doctors as $d): ?>
                        <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-slate-50 cursor-pointer transition">
                            <input type="checkbox"
                                   name="doctor_ids[]"
                                   value="<?= (int)$d['doctor_id'] ?>"
                                   class="doctor-checkbox rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-slate-900">
                                    Dr. <?= htmlspecialchars($d['first_name'] . ' ' . $d['last_name']) ?>
                                </p>
                                <p class="text-xs text-slate-500">
                                    Consultation fee: ₱<?= number_format((float)$d['consultation_fee'], 2) ?>
                                </p>
                            </div>
                            <select name="doctor_roles[<?= (int)$d['doctor_id'] ?>]"
                                    class="text-xs rounded-lg border border-slate-300 px-2 py-1 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="Attending">Attending</option>
                                <option value="Consulting">Consulting</option>
                                <option value="Referring">Referring</option>
                            </select>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        
        <div class="mb-6">
            <label for="preferred_room_type_id" class="block text-sm font-medium text-slate-700 mb-2">Patient's preferred room type <span class="text-rose-500">*</span></label>
            <select name="preferred_room_type_id" id="preferred_room_type_id" data-total-available-beds="<?= array_sum($availableBedsByType) ?>" required
                    class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <option value="">— Select preferred room type —</option>
                <?php foreach ($roomTypes as $rt): ?>
                    <?php $availableBeds = (int)($availableBedsByType[(int)$rt['room_type_id']] ?? 0); ?>
                    <option value="<?= (int)$rt['room_type_id'] ?>"
                            data-available-beds="<?= $availableBeds ?>">
                        <?= htmlspecialchars($rt['room_type_name']) ?> · <?= $availableBeds ?> bed<?= $availableBeds === 1 ? '' : 's' ?> available · ₱<?= number_format((float)$rt['rate_per_day'], 2) ?>/day
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="mt-1.5 text-xs text-red-600 hidden" data-error-for="preferred_room_type_id"></p>
            <p id="roomAvailabilityMessage" class="mt-2 text-sm text-slate-600" aria-live="polite">
                The system assigns a bed of this type when available; otherwise it assigns a temporary bed from another available room type.
            </p>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
            <a href="<?= BASE_URL ?>/index.php?page=nurse"
               class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Cancel
            </a>
            <button type="submit" id="submitBtn"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60">
                <span id="submitLabel">Admit Patient</span>
            </button>
        </div>
    </form>
</div>

<script>
(function() {
    const baseUrl = document.body.dataset.baseUrl;
    const patientSearch = document.getElementById('patientSearch');
    const patientResults = document.getElementById('patientResults');
    const patientIdInput = document.getElementById('patient_id');
    let searchTimeout = null;


    const doctorCheckboxes = document.querySelectorAll('.doctor-checkbox');
    const doctorCountEl = document.getElementById('doctorCount');
    function updateDoctorCount() {
        const n = document.querySelectorAll('.doctor-checkbox:checked').length;
        if (doctorCountEl) doctorCountEl.textContent = n + ' selected';
    }
    doctorCheckboxes.forEach(cb => cb.addEventListener('change', updateDoctorCount));


    if (patientSearch) {
        patientSearch.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const q = this.value.trim();

            if (q.length < 2) {
                patientResults.classList.add('hidden');
                return;
            }

            searchTimeout = setTimeout(async () => {
                try {
                    const response = await axios.get(`${baseUrl}/api/nurses/get-patient.php`, {
                        params: { q }
                    });
                    const patients = response.data.patients || [];

                    if (patients.length === 0) {
                        patientResults.innerHTML = '<div class="px-4 py-3 text-sm text-slate-500">No patients found</div>';
                    } else {
                        patientResults.innerHTML = patients.map(p => `
                            <button type="button" class="patient-option w-full text-left px-4 py-3 hover:bg-slate-50 border-b border-slate-100 last:border-b-0"
                                    data-id="${p.patient_id}" data-name="${p.first_name} ${p.last_name}">
                                <p class="font-medium text-slate-900">${p.first_name} ${p.last_name}</p>
                                <p class="text-xs text-slate-500">${p.contact_number || p.email || 'No contact'}</p>
                                ${p.admission_id ? '<span class="text-xs text-amber-600">Already admitted</span>' : ''}
                            </button>
                        `).join('');
                    }
                    patientResults.classList.remove('hidden');

                    patientResults.querySelectorAll('.patient-option').forEach(btn => {
                        btn.addEventListener('click', function() {
                            const id = this.dataset.id;
                            const name = this.dataset.name;
                            patientIdInput.value = id;
                            patientSearch.value = name;
                            patientResults.classList.add('hidden');


                            const wrapper = patientSearch.parentElement;
                            wrapper.innerHTML = `
                                <div class="flex items-center gap-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                                    <div class="w-12 h-12 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-semibold shrink-0">
                                        ${name.split(' ').map(n => n[0]).join('').toUpperCase()}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-semibold text-slate-900">${name}</p>
                                    </div>
                                    <button type="button" onclick="location.reload()" class="text-xs font-medium text-blue-600 hover:text-blue-700">Change</button>
                                </div>
                            `;
                        });
                    });
                } catch (err) {
                    console.error(err);
                }
            }, 300);
        });


        document.addEventListener('click', function(e) {
            if (!patientSearch.contains(e.target) && !patientResults.contains(e.target)) {
                patientResults.classList.add('hidden');
            }
        });
    }


    const preferredRoomType = document.getElementById('preferred_room_type_id');
    const roomAvailabilityMessage = document.getElementById('roomAvailabilityMessage');
    preferredRoomType.addEventListener('change', function() {
        const selected = this.options[this.selectedIndex];
        const preferredBeds = Number(selected.dataset.availableBeds || 0);
        const allBeds = Number(this.dataset.totalAvailableBeds || 0);

        if (!this.value) {
            roomAvailabilityMessage.textContent = 'The system assigns a bed of the preferred type when available; otherwise it assigns a temporary bed from another available room type.';
        } else if (preferredBeds > 0) {
            roomAvailabilityMessage.textContent = `${preferredBeds} bed(s) are available in the preferred room type. The system will assign one of them.`;
        } else if (allBeds > 0) {
            roomAvailabilityMessage.textContent = 'No beds are currently available in this type. The system will assign a temporary bed from another available room type.';
        } else {
            roomAvailabilityMessage.textContent = 'No beds are currently available. Admission cannot be completed until a bed becomes available.';
        }
    });


    document.getElementById('admissionForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        const btn = document.getElementById('submitBtn');
        const label = document.getElementById('submitLabel');
        const alertEl = document.getElementById('alert');

        document.querySelectorAll('[data-error-for]').forEach(el => {
            el.textContent = '';
            el.classList.add('hidden');
        });


        const formEl = this;
        const data = {};

        new FormData(formEl).forEach((value, key) => {
            if (key === 'doctor_ids[]') {
                (data.doctor_ids ||= []).push(value);
                return;
            }
            const m = key.match(/^doctor_roles\[(\d+)\]$/);
            if (m) {
                (data.doctor_roles ||= {})[m[1]] = value;
                return;
            }
            data[key] = value;
        });

        const clientErrors = {};

        if (!data.patient_id || data.patient_id === '0') {
            clientErrors.patient_id = 'Please select a patient.';
        }

        if (!data.preferred_room_type_id || data.preferred_room_type_id === '') {
            clientErrors.preferred_room_type_id = 'Please select the patient\'s preferred room type.';
        }

        if (Object.keys(clientErrors).length > 0) {
            Object.entries(clientErrors).forEach(([field, msg]) => {
                const el = document.querySelector(`[data-error-for="${field}"]`);
                if (el) { el.textContent = msg; el.classList.remove('hidden'); }
            });

            alertEl.className = 'mb-5 rounded-lg px-4 py-3 text-sm border border-rose-200 bg-rose-50 text-rose-800';
            alertEl.textContent = 'Please fix the highlighted fields.';
            alertEl.classList.remove('hidden');

            const firstError = document.querySelector('[data-error-for]:not(.hidden)');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            return;
        }

        btn.disabled = true;
        label.textContent = 'Admitting…';
        alertEl.classList.add('hidden');

        try {
            const response = await axios.post(`${baseUrl}/api/nurses/admission-create.php`, data, {
                headers: { 'Content-Type': 'application/json' }
            });

            if (response.data.success) {
                alertEl.className = 'mb-5 rounded-lg px-4 py-3 text-sm border border-emerald-200 bg-emerald-50 text-emerald-800';
                alertEl.textContent = response.data.message;
                alertEl.classList.remove('hidden');

                setTimeout(() => {
                    window.location.href = `${baseUrl}/index.php?page=nurse-room-assignments&admission_id=${response.data.admission_id}`;
                }, 1000);
            }
        } catch (err) {
            const res = err.response?.data;
            if (res?.errors) {
                Object.entries(res.errors).forEach(([field, msg]) => {
                    const el = document.querySelector(`[data-error-for="${field}"]`);
                    if (el) { el.textContent = msg; el.classList.remove('hidden'); }
                });
            }
            alertEl.className = 'mb-5 rounded-lg px-4 py-3 text-sm border border-rose-200 bg-rose-50 text-rose-800';
            alertEl.textContent = res?.message || 'Something went wrong.';
            alertEl.classList.remove('hidden');
        } finally {
            btn.disabled = false;
            label.textContent = 'Admit Patient';
        }
    });
})();
</script>
<?php endif; ?>