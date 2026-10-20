<?php

require_once __DIR__ . '/../../controllers/DoctorPortalController.php';
require_once __DIR__ . '/../../controllers/DoctorHelper.php';

$doctorId = DoctorHelper::getCurrentDoctorId($pdo);
$controller = new DoctorPortalController($pdo, $doctorId);
$patients = $controller->getReadyForDischarge();
?>

<div class="mb-8">
    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Ready for Discharge</h1>
    <p class="mt-1 text-sm text-slate-500">Confirm discharge readiness after reviewing medicine use. Choose unused quantities to return to Pharmacy, or leave them at zero for the patient to take home.</p>
</div>

<div id="alert" class="hidden mb-5 rounded-lg px-4 py-3 text-sm border"></div>

<?php if (empty($patients)): ?>
    <div class="bg-white rounded-xl border border-slate-200 p-12 text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-emerald-50 text-emerald-500 mb-4">
            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <p class="text-lg font-medium text-slate-700">No patients ready for discharge</p>
                <p class="text-sm text-slate-500 mt-1">Patients appear here when no service requests are pending or in progress. Cancelled requests do not block discharge.</p>
    </div>
<?php else: ?>
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="text-left px-6 py-3 font-medium">Patient</th>
                        <th class="text-left px-6 py-3 font-medium">Room</th>
                        <th class="text-left px-6 py-3 font-medium">Admitted</th>
                        <th class="text-left px-6 py-3 font-medium">Length of Stay</th>
                        <th class="text-left px-6 py-3 font-medium">Requests</th>
                        <th class="text-right px-6 py-3 font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($patients as $p):
                        $admitted = new DateTime($p['admission_datetime']);
                        $now = new DateTime();
                        $los = $admitted->diff($now);
                        $alreadyReady = strtolower($p['status_name'] ?? '') === 'ready for discharge';
                    ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4">
                                <p class="font-semibold text-slate-900"><?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?></p>
                                <p class="text-xs text-slate-500">#<?= (int)$p['admission_id'] ?></p>
                            </td>
                            <td class="px-6 py-4 text-slate-600">
                                <?= $p['room_number'] ? htmlspecialchars($p['room_number'] . ' (' . ($p['room_type_name'] ?? '') . ')') : '—' ?>
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
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 border border-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700">
                                    <?= (int)$p['completed_count'] ?> completed
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <?php if ($alreadyReady): ?>
                                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 border border-emerald-200 px-3 py-1.5 text-xs font-semibold text-emerald-700">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        Marked Ready
                                    </span>
                                <?php else: ?>
                                    <button type="button"
                                            class="confirm-discharge-btn inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700"
                                            data-admission-id="<?= (int)$p['admission_id'] ?>"
                                            data-patient-name="<?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?>"
                                            data-medicines="<?= htmlspecialchars(json_encode($p['medicine_return_items'] ?? [], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8') ?>">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        Confirm Ready
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

<div id="dischargeModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/50" data-close-modal></div>

    <div class="relative flex max-h-[calc(100vh-2rem)] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
        <div class="min-h-0 overflow-y-auto p-5 sm:p-6">
            <div class="flex items-start gap-4">
                <div class="shrink-0 w-11 h-11 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Confirm Patient Ready for Discharge?</h3>
                    <p class="mt-1.5 text-sm text-slate-500">
                        <strong id="dischargePatientName" class="text-slate-700"></strong> will be marked as ready.
                        Nursing staff will handle the actual discharge (freeing the room and updating the admission).
                    </p>
                </div>
            </div>

            <div class="mt-4">
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Clinical Notes <span class="text-slate-400 font-normal">(optional)</span></label>
                <textarea id="dischargeNotes" rows="2" placeholder="e.g. Vitals stable, no further treatment needed."
                          class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
            </div>
            <div id="dischargeMedicineReturns" class="hidden mt-4 rounded-lg border border-amber-200 bg-amber-50 p-4">
                <p class="text-sm font-semibold text-amber-900">Dispensed Pharmacy items: take home or return</p>
                <p class="mt-1 text-xs text-amber-800">Choose whether the patient takes the remaining item home or returns it to Pharmacy. Take-home items keep their existing charge. A return is credited only after Pharmacy confirms receipt.</p>
                <div id="dischargeMedicineReturnItems" class="mt-3 space-y-2"></div>
            </div>
        </div>

        <div class="flex shrink-0 items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:px-6">
            <button type="button" data-close-modal
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Cancel
            </button>
            <button type="button" id="confirmDischargeBtn"
                    class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-60">
                <span id="confirmDischargeLabel">Yes, Confirm</span>
            </button>
        </div>
    </div>
</div>

<script>
(function() {
    const baseUrl = document.body.dataset.baseUrl;
    const alertEl = document.getElementById('alert');
    const modal = document.getElementById('dischargeModal');
    const returnsSection = document.getElementById('dischargeMedicineReturns');
    const returnItemsContainer = document.getElementById('dischargeMedicineReturnItems');
    let currentAdmissionId = null;
    let currentMedicineItems = [];

    function escapeHtml(value) {
        return String(value).replace(/[&<>"']/g, (character) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        })[character]);
    }

    function showAlert(type, msg) {
        alertEl.className = 'mb-5 rounded-lg px-4 py-3 text-sm border ' +
            (type === 'success'
                ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
                : 'border-rose-200 bg-rose-50 text-rose-800');
        alertEl.textContent = msg;
        alertEl.classList.remove('hidden');
    }

    document.querySelectorAll('.confirm-discharge-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            currentAdmissionId = this.dataset.admissionId;
            document.getElementById('dischargePatientName').textContent = this.dataset.patientName;
            currentMedicineItems = JSON.parse(this.dataset.medicines || '[]');
            returnItemsContainer.innerHTML = currentMedicineItems.map((item) => {
                const quantity = Number(item.returnable_quantity) || 0;
                const unit = item.unit_of_measure ? ` ${escapeHtml(item.unit_of_measure)}` : ' units';
                return `<div data-medicine-return-item class="grid gap-2 rounded-lg border border-amber-200 bg-white p-3 text-sm sm:grid-cols-[minmax(0,1fr)_minmax(12rem,1fr)] sm:items-center">
                    <div class="min-w-0">
                        <p class="font-medium text-slate-800">${escapeHtml(item.item_name)}</p>
                        <p class="mt-0.5 text-xs text-slate-500">Intake: ${escapeHtml(item.intake_status)} · Remaining: ${quantity}${unit} · ₱${Number(item.unit_price).toFixed(2)} each</p>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="min-w-0 text-xs font-medium text-slate-600">Decision
                            <select data-disposition class="mt-1 w-full rounded-lg border border-slate-300 px-2 py-2 text-xs">
                                <option value="take-home">Take home</option>
                                <option value="return">Return to Pharmacy</option>
                            </select>
                        </label>
                        <label class="min-w-0 text-xs font-medium text-slate-600">Return quantity
                            <input type="number" min="0" max="${quantity}" step="1" inputmode="numeric" value="0" disabled
                                data-fulfillment-item-id="${Number(item.fulfillment_item_id)}"
                                class="mt-1 w-full rounded-lg border border-slate-300 px-2 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:bg-slate-100">
                        </label>
                    </div>
                </div>`;
            }).join('');
            returnItemsContainer.querySelectorAll('[data-disposition]').forEach((select) => {
                select.addEventListener('change', () => {
                    const row = select.closest('[data-medicine-return-item]');
                    const input = row.querySelector('[data-fulfillment-item-id]');
                    input.disabled = select.value !== 'return';
                    input.value = select.value === 'return' ? input.max : '0';
                });
            });
            returnsSection.classList.toggle('hidden', currentMedicineItems.length === 0);
            modal.classList.remove('hidden');
        });
    });

    modal.querySelectorAll('[data-close-modal]').forEach(el => {
        el.addEventListener('click', () => modal.classList.add('hidden'));
    });

    document.getElementById('confirmDischargeBtn').addEventListener('click', async function() {
        if (!currentAdmissionId) return;
        const btn = this;
        const label = document.getElementById('confirmDischargeLabel');
        btn.disabled = true;
        label.textContent = 'Confirming…';

        try {
            const res = await axios.post(`${baseUrl}/api/doctors/confirm-discharge.php`, {
                admission_id: currentAdmissionId,
                notes: document.getElementById('dischargeNotes').value,
                medicine_returns: Array.from(returnItemsContainer.querySelectorAll('input[data-fulfillment-item-id]'))
                    .map((input) => {
                        const row = input.closest('[data-medicine-return-item]');
                        return {
                            fulfillment_item_id: Number(input.dataset.fulfillmentItemId),
                            quantity: Number(input.value || 0),
                            disposition: row.querySelector('[data-disposition]').value
                        };
                    })
            }, { headers: { 'Content-Type': 'application/json' } });

            if (res.data.success) {
                showAlert('success', res.data.message);
                modal.classList.add('hidden');
                setTimeout(() => location.reload(), 1000);
            }
        } catch (err) {
            showAlert('error', err.response?.data?.message || 'Could not confirm.');
            btn.disabled = false;
            label.textContent = 'Yes, Confirm';
        }
    });
})();
</script>