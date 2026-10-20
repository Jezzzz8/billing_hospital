<?php
// views/nurse/discharge-ready.php

require_once __DIR__ . '/../../controllers/NurseController.php';

$controller = new NurseController($pdo);
$readyPatients = $controller->getReadyForDischarge();
?>

<div class="mb-8">
    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Ready for Discharge</h1>
    <p class="mt-1 text-sm text-slate-500">Patients cleared by their doctor and ready for discharge.</p>
</div>

<!-- Workflow notice -->
<div class="mb-5 rounded-lg px-4 py-3 text-sm border border-blue-200 bg-blue-50 text-blue-800 flex items-start gap-3">
    <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    <div>
        <p class="font-semibold">Discharge requires doctor clearance and resolved service requests</p>
        <p class="text-xs mt-0.5">
            Patients appear here after the attending doctor marks them
            <strong>Ready for Discharge</strong>. Resolve active service requests before discharge; unpaid balances do not prevent discharge.
        </p>
    </div>
</div>

<div id="alert" class="hidden mb-5 rounded-lg px-4 py-3 text-sm border"></div>

<?php if (empty($readyPatients)): ?>
    <div class="bg-white rounded-xl border border-slate-200 p-12 text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-emerald-50 text-emerald-500 mb-4">
            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <p class="text-lg font-medium text-slate-700">No patients ready for discharge</p>
        <p class="text-sm text-slate-500 mt-1">No patient has been cleared for discharge by their doctor yet.</p>
    </div>
<?php else: ?>
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="text-left px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Patient</th>
                        <th class="text-left px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Room</th>
                        <th class="text-left px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Admitted</th>
                        <th class="text-left px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Length of Stay</th>
                        <th class="text-left px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Billing</th>
                        <th class="text-right px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($readyPatients as $p):
                        $admitted = new DateTime($p['admission_datetime']);
                        $now = new DateTime();
                        $los = $admitted->diff($now);
                        $serviceRequestsResolved = (int)($p['active_request_count'] ?? 0) === 0;

                    ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4">
                                <p class="font-semibold text-slate-900"><?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?></p>
                                <p class="text-xs text-slate-500">#<?= (int)$p['admission_id'] ?></p>
                            </td>
                            <td class="px-6 py-4">
                                <?php if (!empty($p['room_number'])): ?>
                                    <p class="font-medium text-slate-900"><?= htmlspecialchars($p['room_number']) ?></p>
                                    <p class="text-xs text-slate-500"><?= htmlspecialchars($p['room_type_name'] ?? '') ?></p>
                                <?php else: ?>
                                    <span class="text-xs text-slate-400">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-slate-600">
                                <?= htmlspecialchars(date('M j, Y', strtotime($p['admission_datetime']))) ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 border border-blue-100 px-2.5 py-1 text-xs font-medium text-blue-700">
                                    <?= $los->days ?> day<?= $los->days !== 1 ? 's' : '' ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <?php if (!empty($p['statement_id'])): ?>
                                    <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-medium"
                                          style="background-color: <?= htmlspecialchars($p['billing_status_color'] ?? '#6b7280') ?>1A;
                                                 color: <?= htmlspecialchars($p['billing_status_color'] ?? '#6b7280') ?>;
                                                 border-color: <?= htmlspecialchars($p['billing_status_color'] ?? '#6b7280') ?>40;">
                                        <?= htmlspecialchars($p['billing_status_name']) ?>
                                    </span>
                                    <p class="text-xs text-slate-500 mt-1">
                                        Balance: ₱<?= number_format((float)$p['balance_amount'], 2) ?>
                                    </p>
                                    <?php if ((float)($p['balance_amount'] ?? 0) > 0): ?>
                                        <p class="mt-1 text-xs text-rose-600">
                                            Outstanding balance remains payable after discharge.
                                        </p>
                                    <?php endif; ?>
                                    <?php if (!$serviceRequestsResolved): ?>
                                        <p class="mt-1 text-xs text-amber-700">Active service requests must be completed first. Check with Pharmacy if a medicine return is pending.</p>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-xs text-amber-600">No statement yet</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <?php if ($serviceRequestsResolved): ?>
                                    <button type="button"
                                            class="discharge-btn inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700"
                                            data-admission-id="<?= (int)$p['admission_id'] ?>"
                                            data-patient-name="<?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?>">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        Discharge
                                    </button>
                                <?php else: ?>
                                    <button type="button" disabled
                                            class="cursor-not-allowed rounded-lg bg-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-500"
                                            title="Active service requests must be resolved before discharge.">
                                        Requests Pending
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<!-- Discharge Confirmation Modal -->
<div id="dischargeModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/50" data-close-modal></div>

    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="p-6">
            <div class="flex items-start gap-4">
                <div class="shrink-0 w-11 h-11 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Discharge Patient?</h3>
                    <p class="mt-1.5 text-sm text-slate-500">
                        <strong id="dischargePatientName" class="text-slate-700"></strong> will be discharged.
                        The room will be freed up and the admission will be closed.
                    </p>
                </div>
            </div>

            <div class="mt-4">
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Discharge Notes (optional)</label>
                <textarea id="dischargeNotes" rows="2" placeholder="Any notes about the discharge…"
                          class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
            </div>
        </div>

        <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 rounded-b-2xl flex items-center justify-end gap-3">
            <button type="button" data-close-modal
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Cancel
            </button>
            <button type="button" id="confirmDischargeBtn"
                    class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-60">
                <span id="confirmDischargeLabel">Yes, Discharge</span>
            </button>
        </div>
    </div>
</div>

<script>
(function() {
    const baseUrl = document.body.dataset.baseUrl;
    const alertEl = document.getElementById('alert');
    const dischargeModal = document.getElementById('dischargeModal');
    let currentAdmissionId = null;

    document.querySelectorAll('.discharge-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            currentAdmissionId = this.dataset.admissionId;
            document.getElementById('dischargePatientName').textContent = this.dataset.patientName;
            dischargeModal.classList.remove('hidden');
        });
    });

    dischargeModal.querySelectorAll('[data-close-modal]').forEach(el => {
        el.addEventListener('click', () => dischargeModal.classList.add('hidden'));
    });

    document.getElementById('confirmDischargeBtn').addEventListener('click', async function() {
        if (!currentAdmissionId) return;

        const btn = this;
        const label = document.getElementById('confirmDischargeLabel');
        btn.disabled = true;
        label.textContent = 'Discharging…';

        try {
            const response = await axios.post(`${baseUrl}/api/nurses/discharge.php`, {
                admission_id: currentAdmissionId,
                discharge_notes: document.getElementById('dischargeNotes').value
            }, { headers: { 'Content-Type': 'application/json' } });

            if (response.data.success) {
                alertEl.className = 'mb-5 rounded-lg px-4 py-3 text-sm border border-emerald-200 bg-emerald-50 text-emerald-800';
                alertEl.textContent = response.data.message;
                alertEl.classList.remove('hidden');
                setTimeout(() => location.reload(), 1000);
            }
        } catch (err) {
            const res = err.response?.data;
            alertEl.className = 'mb-5 rounded-lg px-4 py-3 text-sm border border-rose-200 bg-rose-50 text-rose-800';
            alertEl.textContent = res?.message || 'Could not discharge patient.';
            alertEl.classList.remove('hidden');
            dischargeModal.classList.add('hidden');
        } finally {
            btn.disabled = false;
            label.textContent = 'Yes, Discharge';
        }
    });
})();
</script>