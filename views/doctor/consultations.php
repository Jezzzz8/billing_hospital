<?php


require_once __DIR__ . '/../../controllers/DoctorPortalController.php';
require_once __DIR__ . '/../../controllers/DoctorHelper.php';

$doctorId = DoctorHelper::getCurrentDoctorId($pdo);
$controller = new DoctorPortalController($pdo, $doctorId);

$consultations = $controller->getConsultations();
$patients = $controller->getAllMyPatients();
?>

<div class="mb-8">
    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Consultations</h1>
    <p class="mt-1 text-sm text-slate-500">All your scheduled and completed consultations.</p>
</div>

<div id="alert" class="hidden mb-5 rounded-lg px-4 py-3 text-sm border"></div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    
    <div class="lg:col-span-1">
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h3 class="text-base font-semibold text-slate-900 mb-4">Schedule Consultation</h3>

            <form id="consultationForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Patient</label>
                    <select name="patient_id" required
                            class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">— Select patient —</option>
                        <?php foreach ($patients as $p): ?>
                            <option value="<?= (int)$p['patient_id'] ?>">
                                <?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Date & Time</label>
                    <input type="datetime-local" name="consultation_datetime" required
                           value="<?= date('Y-m-d\TH:i') ?>"
                           class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Purpose</label>
                    <input type="text" name="purpose" required placeholder="e.g. Follow-up check"
                           class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Notes <span class="text-slate-400 font-normal">(optional)</span></label>
                    <textarea name="notes" rows="2"
                              class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                </div>

                <button type="submit" id="consultBtn"
                        class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60">
                    <span id="consultBtnLabel">Schedule</span>
                </button>
            </form>
        </div>
    </div>

    
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200">
                <h3 class="text-base font-semibold text-slate-900">Consultation List</h3>
                <p class="text-xs text-slate-500 mt-0.5"><?= count($consultations) ?> consultation(s)</p>
            </div>

            <?php if (empty($consultations)): ?>
                <div class="p-12 text-center text-slate-400">
                    <p class="text-sm">No consultations yet.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-slate-600">
                            <tr>
                                <th class="text-left px-6 py-3 font-medium">Patient</th>
                                <th class="text-left px-6 py-3 font-medium">Date</th>
                                <th class="text-left px-6 py-3 font-medium">Purpose</th>
                                <th class="text-left px-6 py-3 font-medium">Status</th>
                                <th class="text-right px-6 py-3 font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($consultations as $c): ?>
                                <?php
                                $statusClass = match(strtolower($c['status'] ?? '')) {
                                    'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                                    'scheduled' => 'bg-blue-50 text-blue-700 border-blue-100',
                                    'cancelled' => 'bg-slate-100 text-slate-600 border-slate-200',
                                    default     => 'bg-slate-100 text-slate-600 border-slate-200',
                                };
                                ?>
                                <tr class="hover:bg-slate-50">
                                    <td class="px-6 py-3">
                                        <p class="font-medium text-slate-900"><?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name']) ?></p>
                                    </td>
                                    <td class="px-6 py-3 text-slate-600">
                                        <?= htmlspecialchars(date('M j, Y g:i A', strtotime($c['consultation_datetime']))) ?>
                                    </td>
                                    <td class="px-6 py-3 text-slate-600 max-w-xs truncate"><?= htmlspecialchars($c['purpose'] ?? '—') ?></td>
                                    <td class="px-6 py-3">
                                        <span class="inline-block rounded-full border px-2 py-0.5 text-xs font-medium <?= $statusClass ?>">
                                            <?= htmlspecialchars($c['status']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-3 text-right whitespace-nowrap">
                                        <?php if (strtolower($c['status']) === 'scheduled'): ?>
                                            <button type="button" class="mark-completed-btn text-xs font-medium text-emerald-600 hover:text-emerald-700"
                                                    data-id="<?= (int)$c['consultation_id'] ?>">Complete</button>
                                            <button type="button" class="mark-cancelled-btn text-xs font-medium text-rose-600 hover:text-rose-700 ml-2"
                                                    data-id="<?= (int)$c['consultation_id'] ?>">Cancel</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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

    document.getElementById('consultationForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('consultBtn');
        const label = document.getElementById('consultBtnLabel');
        btn.disabled = true;
        label.textContent = 'Scheduling…';

        const data = Object.fromEntries(new FormData(this).entries());

        try {
            const res = await axios.post(`${baseUrl}/api/doctors/create-consultation.php`, data, {
                headers: { 'Content-Type': 'application/json' }
            });
            if (res.data.success) {
                showAlert('success', res.data.message);
                setTimeout(() => location.reload(), 800);
            }
        } catch (err) {
            showAlert('error', err.response?.data?.message || 'Could not schedule consultation.');
            btn.disabled = false;
            label.textContent = 'Schedule';
        }
    });

    function updateStatus(id, status) {
        return axios.post(`${baseUrl}/api/doctors/update-consultation.php?id=${id}&status=${status}`);
    }

    document.querySelectorAll('.mark-completed-btn').forEach(b => b.addEventListener('click', async function() {
        try {
            await updateStatus(this.dataset.id, 'Completed');
            showAlert('success', 'Marked as completed.');
            setTimeout(() => location.reload(), 600);
        } catch (e) { showAlert('error', 'Failed.'); }
    }));

    document.querySelectorAll('.mark-cancelled-btn').forEach(b => b.addEventListener('click', async function() {
        if (!confirm('Cancel this consultation?')) return;
        try {
            await updateStatus(this.dataset.id, 'Cancelled');
            showAlert('success', 'Cancelled.');
            setTimeout(() => location.reload(), 600);
        } catch (e) { showAlert('error', 'Failed.'); }
    }));
})();
</script>