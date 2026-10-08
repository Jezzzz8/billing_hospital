<?php


require_once __DIR__ . '/../../controllers/DoctorPortalController.php';
require_once __DIR__ . '/../../controllers/DoctorHelper.php';

$doctorId = DoctorHelper::getCurrentDoctorId($pdo);
$controller = new DoctorPortalController($pdo, $doctorId);

$admissionId = (int)($_GET['admission_id'] ?? 0);
$admission = $admissionId ? $controller->getAdmissionDetails($admissionId) : null;
$allDiagnoses = $controller->getActiveDiagnoses();
$assigned = $controller->getAssignedPatients();

if (!$admission) {
    
    ?>
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Record Diagnosis</h1>
        <p class="mt-1 text-sm text-slate-500">Select an admission to record or view diagnoses.</p>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="text-left px-6 py-3 font-medium">Patient</th>
                        <th class="text-left px-6 py-3 font-medium">Room</th>
                        <th class="text-left px-6 py-3 font-medium">Admitted</th>
                        <th class="text-right px-6 py-3 font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($assigned as $p): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-3">
                                <p class="font-medium text-slate-900"><?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?></p>
                                <p class="text-xs text-slate-500">#<?= (int)$p['admission_id'] ?></p>
                            </td>
                            <td class="px-6 py-3 text-slate-600"><?= $p['room_number'] ? htmlspecialchars($p['room_number']) : '—' ?></td>
                            <td class="px-6 py-3 text-slate-600"><?= htmlspecialchars(date('M j, Y', strtotime($p['admission_datetime']))) ?></td>
                            <td class="px-6 py-3 text-right">
                                <a href="<?= BASE_URL ?>/index.php?page=doctor-diagnosis-record&admission_id=<?= (int)$p['admission_id'] ?>"
                                   class="inline-flex items-center gap-1.5 rounded-lg border border-blue-200 bg-blue-50 px-2.5 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-100">
                                    Open
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
    return;
}
?>

<div class="mb-6">
    <a href="<?= BASE_URL ?>/index.php?page=doctor-diagnosis-record"
       class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-600 hover:text-slate-900">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
        </svg>
        Back to admissions
    </a>
</div>


<div class="bg-white rounded-xl border border-slate-200 p-6 mb-6">
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <div class="flex items-start gap-4">
            <div class="w-14 h-14 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-lg shrink-0">
                <?= strtoupper(substr($admission['first_name'], 0, 1) . substr($admission['last_name'], 0, 1)) ?>
            </div>
            <div>
                <h1 class="text-xl font-bold text-slate-900"><?= htmlspecialchars($admission['first_name'] . ' ' . $admission['last_name']) ?></h1>
                <p class="text-sm text-slate-500">
                    Admission #<?= (int)$admission['admission_id'] ?> ·
                    <?= htmlspecialchars($admission['gender_name']) ?> ·
                    Admitted <?= htmlspecialchars(date('M j, Y g:i A', strtotime($admission['admission_datetime']))) ?>
                </p>
                <?php if ($admission['room']): ?>
                    <p class="text-sm text-slate-600 mt-1">
                        <strong>Room:</strong> <?= htmlspecialchars($admission['room']['room_number']) ?>
                        (<?= htmlspecialchars($admission['room']['room_type_name']) ?>)
                    </p>
                <?php endif; ?>
                <?php if ($admission['chief_complaint']): ?>
                    <p class="text-sm text-slate-600 mt-1"><strong>Chief complaint:</strong> <?= htmlspecialchars($admission['chief_complaint']) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <span class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-sm font-medium self-start"
              style="background-color: <?= htmlspecialchars($admission['admission_status_color'] ?? '#6b7280') ?>1A;
                     color: <?= htmlspecialchars($admission['admission_status_color'] ?? '#6b7280') ?>;
                     border-color: <?= htmlspecialchars($admission['admission_status_color'] ?? '#6b7280') ?>40;">
            <?= htmlspecialchars($admission['admission_status_name']) ?>
        </span>
    </div>
</div>

<div id="alert" class="hidden mb-5 rounded-lg px-4 py-3 text-sm border"></div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    
    <div class="lg:col-span-1">
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h3 class="text-base font-semibold text-slate-900 mb-4">Add Diagnosis</h3>

            <form id="diagnosisForm" class="space-y-4">
                <input type="hidden" name="admission_id" value="<?= (int)$admissionId ?>">

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Diagnosis</label>
                    <select name="diagnosis_id" required
                            class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">— Select diagnosis —</option>
                        <?php foreach ($allDiagnoses as $d): ?>
                            <option value="<?= (int)$d['diagnosis_id'] ?>">
                                <?= $d['icd_code'] ? htmlspecialchars($d['icd_code']) . ' — ' : '' ?>
                                <?= htmlspecialchars($d['diagnosis_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Type</label>
                    <select name="diagnosis_type"
                            class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="Primary">Primary</option>
                        <option value="Secondary">Secondary</option>
                        <option value="Differential">Differential</option>
                        <option value="Admitting">Admitting</option>
                    </select>
                </div>

                <button type="submit" id="diagnosisBtn"
                        class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span id="diagnosisBtnLabel">Add Diagnosis</span>
                </button>
            </form>
        </div>
    </div>

    
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200">
                <h3 class="text-base font-semibold text-slate-900">Recorded Diagnoses</h3>
                <p class="text-xs text-slate-500 mt-0.5"><?= count($admission['diagnoses']) ?> diagnosis(es)</p>
            </div>

            <?php if (empty($admission['diagnoses'])): ?>
                <div class="p-12 text-center text-slate-400">
                    <p class="text-sm">No diagnoses recorded yet.</p>
                </div>
            <?php else: ?>
                <div class="divide-y divide-slate-100">
                    <?php foreach ($admission['diagnoses'] as $d): ?>
                        <div class="px-6 py-4 flex items-start justify-between gap-4">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <?php if (!empty($d['icd_code'])): ?>
                                        <span class="inline-block rounded-md bg-slate-100 border border-slate-200 px-2 py-0.5 text-xs font-mono font-semibold text-slate-700">
                                            <?= htmlspecialchars($d['icd_code']) ?>
                                        </span>
                                    <?php endif; ?>
                                    <p class="font-semibold text-slate-900"><?= htmlspecialchars($d['diagnosis_name']) ?></p>
                                    <span class="inline-block rounded-full bg-blue-50 border border-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700">
                                        <?= htmlspecialchars($d['diagnosis_type']) ?>
                                    </span>
                                </div>
                                <?php if (!empty($d['description'])): ?>
                                    <p class="text-sm text-slate-600 mt-1"><?= htmlspecialchars($d['description']) ?></p>
                                <?php endif; ?>
                                <p class="text-xs text-slate-500 mt-2">
                                    Recorded <?= htmlspecialchars(date('M j, Y g:i A', strtotime($d['diagnosed_datetime']))) ?>
                                    <?php if (!empty($d['doctor_first'])): ?>
                                        by Dr. <?= htmlspecialchars($d['doctor_first'] . ' ' . $d['doctor_last']) ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <button type="button"
                                    class="remove-diagnosis-btn shrink-0 text-slate-400 hover:text-rose-600"
                                    data-id="<?= (int)$d['admission_diagnosis_id'] ?>">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
(function() {
    const baseUrl = document.body.dataset.baseUrl;
    const alertEl = document.getElementById('alert');

    function showAlert(type, msg) {
        alertEl.className = 'mb-5 rounded-lg px-4 py-3 text-sm border ' +
            (type === 'success'
                ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
                : 'border-rose-200 bg-rose-50 text-rose-800');
        alertEl.textContent = msg;
        alertEl.classList.remove('hidden');
    }

    document.getElementById('diagnosisForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('diagnosisBtn');
        const label = document.getElementById('diagnosisBtnLabel');
        btn.disabled = true;
        label.textContent = 'Saving…';
        alertEl.classList.add('hidden');

        const data = Object.fromEntries(new FormData(this).entries());

        try {
            const res = await axios.post(`${baseUrl}/api/doctors/save-diagnosis.php`, data, {
                headers: { 'Content-Type': 'application/json' }
            });
            if (res.data.success) {
                showAlert('success', res.data.message);
                setTimeout(() => location.reload(), 800);
            }
        } catch (err) {
            showAlert('error', err.response?.data?.message || 'Could not save diagnosis.');
            btn.disabled = false;
            label.textContent = 'Add Diagnosis';
        }
    });

    document.querySelectorAll('.remove-diagnosis-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            if (!confirm('Remove this diagnosis?')) return;
            const id = this.dataset.id;
            try {
                const res = await axios.post(`${baseUrl}/api/doctors/remove-diagnosis.php?id=${id}`);
                if (res.data.success) {
                    this.closest('div.px-6').remove();
                    showAlert('success', res.data.message);
                }
            } catch (err) {
                showAlert('error', err.response?.data?.message || 'Could not remove diagnosis.');
            }
        });
    });
})();
</script>