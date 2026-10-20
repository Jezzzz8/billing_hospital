<?php

require_once __DIR__ . '/../../controllers/DepartmentAdminController.php';

$controller = new DepartmentAdminController($pdo);
$departments = $controller->getAll();
$chargeCategories = $controller->getChargeCategories();
?>

<div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Departments</h1>
        <p class="mt-1 text-sm text-slate-500">Manage hospital departments and their offered charge category.</p>
    </div>
    <button type="button" id="openCreateBtn"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        New Department
    </button>
</div>

<div id="alert" class="hidden mb-5 rounded-lg px-4 py-3 text-sm border"></div>

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="text-left px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Department</th>
                    <th class="text-left px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Code</th>
                    <th class="text-left px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Charge Category</th>
                    <th class="text-center px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Staff</th>
                    <th class="text-center px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Pending</th>
                    <th class="text-left px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Status</th>
                    <th class="text-right px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($departments)): ?>
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                            <p class="text-sm font-medium text-slate-600">No departments yet</p>
                            <p class="text-xs mt-1">Create a department to get started.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($departments as $d): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4">
                                <p class="font-semibold text-slate-900"><?= htmlspecialchars($d['department_name']) ?></p>
                                <?php if (!empty($d['description'])): ?>
                                    <p class="text-xs text-slate-500 truncate max-w-xs"><?= htmlspecialchars($d['description']) ?></p>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php if (!empty($d['department_code'])): ?>
                                    <span class="inline-block rounded-md bg-slate-100 border border-slate-200 px-2 py-0.5 text-xs font-mono font-semibold text-slate-700">
                                        <?= htmlspecialchars($d['department_code']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-xs text-slate-400">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php if (!empty($d['charge_category_name'])): ?>
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 border border-blue-100 px-2.5 py-1 text-xs font-medium text-blue-700">
                                        <?= htmlspecialchars($d['charge_category_name']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-xs text-slate-400 italic">Not mapped</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-center text-slate-700">
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-slate-100 text-slate-700 text-xs font-semibold">
                                    <?= (int)$d['staff_count'] ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <?php if ((int)$d['pending_count'] > 0): ?>
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-amber-100 text-amber-700 text-xs font-semibold">
                                        <?= (int)$d['pending_count'] ?>
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-slate-100 text-slate-400 text-xs font-semibold">
                                        0
                                    </span>
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
                                            class="edit-btn rounded-lg border border-blue-200 bg-blue-50 px-2.5 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-100"
                                            data-id="<?= (int)$d['department_id'] ?>"
                                            data-name="<?= htmlspecialchars($d['department_name'], ENT_QUOTES) ?>"
                                            data-code="<?= htmlspecialchars($d['department_code'] ?? '', ENT_QUOTES) ?>"
                                            data-category="<?= (int)($d['charge_category_id'] ?? 0) ?>"
                                            data-description="<?= htmlspecialchars($d['description'] ?? '', ENT_QUOTES) ?>">
                                        Edit
                                    </button>
                                    <button type="button"
                                            class="toggle-btn rounded-lg border px-2.5 py-1.5 text-xs font-medium
                                                <?= (int)$d['is_active'] === 1
                                                    ? 'border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100'
                                                    : 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100' ?>"
                                            data-id="<?= (int)$d['department_id'] ?>"
                                            data-active="<?= (int)$d['is_active'] ?>">
                                        <?= (int)$d['is_active'] === 1 ? 'Archive' : 'Reactivate' ?>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="departmentModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/50" data-close-modal></div>

    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="flex items-center justify-between px-6 py-5 border-b border-slate-200">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <div>
                    <h3 id="modalTitle" class="text-base font-semibold text-slate-900">New Department</h3>
                    <p class="text-xs text-slate-500">Define the department and its offered charge category.</p>
                </div>
            </div>
            <button type="button" data-close-modal class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form id="departmentForm" class="p-6 space-y-4">
            <input type="hidden" name="id" id="deptId" value="">

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Department Name <span class="text-rose-500">*</span></label>
                <input type="text" name="department_name" id="deptName" required
                       class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <p class="mt-1.5 text-xs text-red-600 hidden" data-error-for="department_name"></p>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Department Code</label>
                <input type="text" name="department_code" id="deptCode" placeholder="e.g. PHARM"
                       class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm font-mono uppercase focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <p class="mt-1.5 text-xs text-slate-500">Short identifier. Letters and digits only.</p>
                <p class="mt-1.5 text-xs text-red-600 hidden" data-error-for="department_code"></p>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Offered Charge Category</label>
                <select name="charge_category_id" id="deptCategory"
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">— None —</option>
                    <?php foreach ($chargeCategories as $cat): ?>
                        <option value="<?= (int)$cat['category_id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="mt-1.5 text-xs text-slate-500">
                    Doctors can only request items from this category when selecting this department.
                </p>
                <p class="mt-1.5 text-xs text-red-600 hidden" data-error-for="charge_category_id"></p>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Description</label>
                <textarea name="description" id="deptDescription" rows="2"
                          class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                <p class="mt-1.5 text-xs text-red-600 hidden" data-error-for="description"></p>
            </div>
        </form>

        <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 rounded-b-2xl flex items-center justify-end gap-3">
            <button type="button" data-close-modal
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Cancel
            </button>
            <button type="button" id="saveBtn"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60">
                <span id="saveBtnLabel">Save</span>
            </button>
        </div>
    </div>
</div>

<script>
(function() {
    const baseUrl = document.body.dataset.baseUrl;
    const alertEl = document.getElementById('alert');
    const modal = document.getElementById('departmentModal');
    const form = document.getElementById('departmentForm');
    const saveBtn = document.getElementById('saveBtn');
    const saveBtnLabel = document.getElementById('saveBtnLabel');
    const modalTitle = document.getElementById('modalTitle');

    function showAlert(type, msg) {
        alertEl.className = 'mb-5 rounded-lg px-4 py-3 text-sm border ' +
            (type === 'success'
                ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
                : 'border-rose-200 bg-rose-50 text-rose-800');
        alertEl.textContent = msg;
        alertEl.classList.remove('hidden');
        alertEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function clearErrors() {
        document.querySelectorAll('[data-error-for]').forEach(el => {
            el.textContent = '';
            el.classList.add('hidden');
        });
    }

    function resetForm() {
        form.reset();
        document.getElementById('deptId').value = '';
        clearErrors();
    }

    function openCreate() {
        resetForm();
        modalTitle.textContent = 'New Department';
        modal.classList.remove('hidden');
        document.getElementById('deptName').focus();
    }

    function openEdit(data) {
        resetForm();
        modalTitle.textContent = 'Edit Department';
        document.getElementById('deptId').value = data.id;
        document.getElementById('deptName').value = data.name;
        document.getElementById('deptCode').value = data.code;
        document.getElementById('deptCategory').value = data.category > 0 ? String(data.category) : '';
        document.getElementById('deptDescription').value = data.description;
        modal.classList.remove('hidden');
        document.getElementById('deptName').focus();
    }

    document.getElementById('openCreateBtn').addEventListener('click', openCreate);

    modal.querySelectorAll('[data-close-modal]').forEach(el => {
        el.addEventListener('click', () => modal.classList.add('hidden'));
    });

    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            openEdit({
                id: this.dataset.id,
                name: this.dataset.name,
                code: this.dataset.code,
                category: parseInt(this.dataset.category || '0', 10),
                description: this.dataset.description
            });
        });
    });

    document.querySelectorAll('.toggle-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            const id = this.dataset.id;
            const isActive = parseInt(this.dataset.active, 10) === 1;
            const action = isActive ? 'archive' : 'reactivate';
            if (!await window.confirmWithSweetAlert(`Are you sure you want to ${action} this department?`)) return;

            this.disabled = true;
            const originalText = this.textContent;
            this.textContent = 'Working…';

            try {
                const res = await axios.post(`${baseUrl}/api/admin.php?resource=departments&operation=toggle-active&id=${id}`);
                if (res.data.success) {
                    showAlert('success', res.data.message);
                    setTimeout(() => location.reload(), 700);
                }
            } catch (err) {
                showAlert('error', err.response?.data?.message || 'Could not update department.');
                this.disabled = false;
                this.textContent = originalText;
            }
        });
    });

    saveBtn.addEventListener('click', async function() {
        clearErrors();

        const id = document.getElementById('deptId').value;
        const payload = {
            department_name: document.getElementById('deptName').value.trim(),
            department_code: document.getElementById('deptCode').value.trim(),
            charge_category_id: document.getElementById('deptCategory').value,
            description: document.getElementById('deptDescription').value.trim()
        };

        if (!payload.department_name) {
            const el = document.querySelector('[data-error-for="department_name"]');
            if (el) { el.textContent = 'Department name is required.'; el.classList.remove('hidden'); }
            return;
        }

        saveBtn.disabled = true;
        saveBtnLabel.textContent = 'Saving…';

        try {
            const url = id
                ? `${baseUrl}/api/admin.php?resource=departments&operation=update&id=${id}`
                : `${baseUrl}/api/admin.php?resource=departments&operation=create`;
            const res = await axios.post(url, payload, {
                headers: { 'Content-Type': 'application/json' }
            });

            if (res.data.success) {
                modal.classList.add('hidden');
                showAlert('success', res.data.message);
                setTimeout(() => location.reload(), 700);
            }
        } catch (err) {
            const res = err.response?.data;
            if (res?.errors) {
                Object.entries(res.errors).forEach(([field, msg]) => {
                    const el = document.querySelector(`[data-error-for="${field}"]`);
                    if (el) { el.textContent = msg; el.classList.remove('hidden'); }
                });
            }
            showAlert('error', res?.message || 'Could not save department.');
            saveBtn.disabled = false;
            saveBtnLabel.textContent = 'Save';
        }
    });
})();
</script>