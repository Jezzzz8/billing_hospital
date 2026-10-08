<?php

require_once __DIR__ . '/../../controllers/DoctorPortalController.php';
require_once __DIR__ . '/../../controllers/DoctorHelper.php';

$doctorId = DoctorHelper::getCurrentDoctorId($pdo);
$controller = new DoctorPortalController($pdo, $doctorId);

$admissionId = (int)($_GET['admission_id'] ?? 0);
$admission = $admissionId ? $controller->getAdmissionDetails($admissionId) : null;
$departments = $controller->getDepartmentsWithCategories();
$assigned = $controller->getAssignedPatients();

if (!$admission) {
    ?>
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Service Requests</h1>
        <p class="mt-1 text-sm text-slate-500">Select an admission to request medications, labs, or procedures.</p>
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
                                <a href="<?= BASE_URL ?>/index.php?page=doctor-service-requests&admission_id=<?= (int)$p['admission_id'] ?>"
                                   class="inline-flex items-center gap-1.5 rounded-lg border border-blue-200 bg-blue-50 px-2.5 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-100">
                                    Manage
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
    <a href="<?= BASE_URL ?>/index.php?page=doctor-service-requests"
       class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-600 hover:text-slate-900">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
        </svg>
        Back
    </a>
</div>

<div class="bg-white rounded-xl border border-slate-200 p-6 mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900"><?= htmlspecialchars($admission['first_name'] . ' ' . $admission['last_name']) ?></h1>
            <p class="text-sm text-slate-500">
                Admission #<?= (int)$admission['admission_id'] ?>
                <?= $admission['room'] ? ' · Room ' . htmlspecialchars($admission['room']['room_number']) : '' ?>
            </p>
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
            <h3 class="text-base font-semibold text-slate-900 mb-4">New Service Request</h3>

            <form id="requestForm" class="space-y-4">
                <input type="hidden" name="admission_id" value="<?= (int)$admissionId ?>">

                <div>
                    <label for="department_id" class="block text-sm font-medium text-slate-700 mb-1.5">Department</label>
                    <select name="department_id" id="department_id" required
                            class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">— Select department —</option>
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= (int)$d['department_id'] ?>">
                                <?= htmlspecialchars($d['department_name']) ?><?= !empty($d['charge_category_name']) ? ' · ' . htmlspecialchars($d['charge_category_name']) : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div id="requestItems" class="space-y-3">
                    <div class="request-item-row rounded-lg border border-slate-200 p-3 space-y-3">
                        <label class="block text-sm font-medium text-slate-700">
                            Item / Service
                            <select data-field="charge-item" required disabled
                                    class="mt-1 w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:bg-slate-50 disabled:text-slate-400">
                                <option value="">Select a department first</option>
                            </select>
                        </label>
                        <div class="flex items-end gap-3">
                            <label class="flex-1 block text-sm font-medium text-slate-700">
                                Quantity
                                <input data-field="quantity" type="number" value="1" min="1" required
                                       class="mt-1 w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </label>
                            <button type="button" data-remove-request-item class="rounded-lg border border-rose-200 px-3 py-2.5 text-sm text-rose-700 hover:bg-rose-50" disabled>Remove</button>
                        </div>
                    </div>
                </div>
                <button type="button" id="addRequestItem" class="w-full rounded-lg border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-medium text-blue-700 hover:bg-blue-100">Add another item</button>

                <button type="submit" id="requestBtn"
                        class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span id="requestBtnLabel">Submit Request</span>
                </button>
            </form>
        </div>
    </div>

    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200">
                <h3 class="text-base font-semibold text-slate-900">Service Requests</h3>
                <p class="text-xs text-slate-500 mt-0.5"><?= count($admission['service_requests']) ?> request(s)</p>
            </div>

            <?php if (empty($admission['service_requests'])): ?>
                <div class="p-12 text-center text-slate-400">
                    <p class="text-sm">No service requests yet.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-slate-600">
                            <tr>
                                <th class="text-left px-6 py-3 font-medium">Item</th>
                                <th class="text-center px-6 py-3 font-medium">Qty</th>
                                <th class="text-left px-6 py-3 font-medium">Requested</th>
                                <th class="text-left px-6 py-3 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($admission['service_requests'] as $r): ?>
                                <?php
                                $statusClass = match(strtolower($r['status'])) {
                                    'pending'   => 'bg-amber-50 text-amber-700 border-amber-100',
                                    'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                                    'cancelled' => 'bg-slate-100 text-slate-600 border-slate-200',
                                    default     => 'bg-slate-100 text-slate-600 border-slate-200',
                                };
                                ?>
                                <tr class="hover:bg-slate-50">
                                    <td class="px-6 py-3">
                                        <p class="font-medium text-slate-900"><?= htmlspecialchars($r['item_name'] ?? 'Service request') ?></p>
                                        <?php $itemDetails = trim(implode(' · ', array_filter([
                                            $r['category_name'] ?? '',
                                            $r['item_code'] ?? '',
                                        ]))); ?>
                                        <?php if ($itemDetails !== ''): ?>
                                            <p class="text-xs text-slate-500"><?= htmlspecialchars($itemDetails) ?></p>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-3 text-center text-slate-700"><?= (int)($r['quantity'] ?? 0) ?></td>
                                    <td class="px-6 py-3 text-slate-600 text-xs">
                                        <?= htmlspecialchars(date('M j, Y g:i A', strtotime($r['request_datetime']))) ?>
                                    </td>
                                    <td class="px-6 py-3">
                                        <span class="inline-block rounded-full border px-2 py-0.5 text-xs font-medium <?= $statusClass ?>">
                                            <?= htmlspecialchars($r['status']) ?>
                                        </span>
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
    const departmentSelect = document.getElementById('department_id');
    const requestItems = document.getElementById('requestItems');

    let departmentItems = {};

    function showAlert(type, msg) {
        alertEl.className = 'mb-5 rounded-lg px-4 py-3 text-sm border ' +
            (type === 'success'
                ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
                : 'border-rose-200 bg-rose-50 text-rose-800');
        alertEl.textContent = msg;
        alertEl.classList.remove('hidden');
    }

    async function loadDepartmentItems(departmentId) {
        if (!departmentId) return [];
        if (departmentItems[departmentId]) return departmentItems[departmentId];

        try {
            const res = await axios.get(`${baseUrl}/api/doctors.php`, {
                params: { operation: 'department-items', department_id: departmentId }
            });
            const items = res.data?.data || [];
            departmentItems[departmentId] = items;
            return items;
        } catch (err) {
            console.error(err);
            return [];
        }
    }

    function buildItemOptions(items) {
        if (!items.length) {
            return '<option value="">No items available in this department</option>';
        }
        return '<option value="">— Select item —</option>' + items.map(i =>
            `<option value="${i.charge_item_id}">${i.item_name} (₱${Number(i.default_price).toFixed(2)}${i.unit_of_measure ? ' / ' + i.unit_of_measure : ''})</option>`
        ).join('');
    }

    async function refreshRow(row, items) {
        const select = row.querySelector('[data-field="charge-item"]');
        select.innerHTML = buildItemOptions(items);
        select.disabled = items.length === 0;
    }

    departmentSelect.addEventListener('change', async function() {
        const departmentId = this.value;
        const rows = requestItems.querySelectorAll('.request-item-row');
        if (!departmentId) {
            rows.forEach(row => {
                const select = row.querySelector('[data-field="charge-item"]');
                select.innerHTML = '<option value="">Select a department first</option>';
                select.disabled = true;
            });
            return;
        }
        const items = await loadDepartmentItems(departmentId);
        for (const row of rows) {
            await refreshRow(row, items);
        }
    });

    document.getElementById('addRequestItem').addEventListener('click', async () => {
        const row = requestItems.querySelector('.request-item-row').cloneNode(true);
        row.querySelector('[data-field="charge-item"]').value = '';
        row.querySelector('[data-field="quantity"]').value = '1';
        row.querySelector('[data-remove-request-item]').disabled = false;
        requestItems.appendChild(row);

        const departmentId = departmentSelect.value;
        if (departmentId) {
            const items = await loadDepartmentItems(departmentId);
            await refreshRow(row, items);
        }
    });

    requestItems.addEventListener('click', (event) => {
        const removeButton = event.target.closest('[data-remove-request-item]');
        if (removeButton && requestItems.querySelectorAll('.request-item-row').length > 1) {
            removeButton.closest('.request-item-row').remove();
        }
    });

    document.getElementById('requestForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('requestBtn');
        const label = document.getElementById('requestBtnLabel');
        btn.disabled = true;
        label.textContent = 'Submitting…';

        const data = Object.fromEntries(new FormData(this).entries());
        data.items = Array.from(requestItems.querySelectorAll('.request-item-row'), (row) => ({
            charge_item_id: row.querySelector('[data-field="charge-item"]').value,
            quantity: row.querySelector('[data-field="quantity"]').value,
        }));

        try {
            const res = await axios.post(`${baseUrl}/api/doctors/create-service-request.php`, data, {
                headers: { 'Content-Type': 'application/json' }
            });
            if (res.data.success) {
                showAlert('success', res.data.message);
                setTimeout(() => location.reload(), 800);
            }
        } catch (err) {
            showAlert('error', err.response?.data?.message || 'Could not create request.');
            btn.disabled = false;
            label.textContent = 'Submit Request';
        }
    });

})();
</script>