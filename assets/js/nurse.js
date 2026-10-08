function initializeNursePage(event) {
    const page = event?.detail?.page || document.body.dataset.page || '';
    if (!page.startsWith('nurse')) return;

    const root = document.querySelector('[data-nurse-view]');
    if (!root) return;
    const baseUrl = document.body.dataset.baseUrl || '';
    const apiUrl = `${baseUrl}/api/nurse.php`;
    const view = root.dataset.nurseView;
    const writeOperations = new Set([
        'register-patient', 'create-admission', 'assign-room', 'transfer-room',
        'assign-doctor', 'remove-doctor', 'discharge', 'receive-fulfillment-item',
        'create-nurse-order-request'
    ]);
    const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, char => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    })[char]);
    const money = value => `₱${Number(value || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    const date = (value, withTime = false) => {
        if (!value) return '—';
        const parsed = new Date(String(value).replace(' ', 'T'));
        return Number.isNaN(parsed.getTime()) ? '—' : parsed.toLocaleString(undefined, withTime
            ? { year: 'numeric', month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }
            : { year: 'numeric', month: 'short', day: 'numeric' });
    };
    const initials = p => `${(p.first_name || '')[0] || ''}${(p.last_name || '')[0] || ''}`.toUpperCase();
    const badge = (label, color = '#64748b') => `<span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-medium" style="background-color:${escapeHtml(color)}1A;color:${escapeHtml(color)};border-color:${escapeHtml(color)}40">${escapeHtml(label || '—')}</span>`;
    const emptyState = (title, detail = '') => `<div class="rounded-xl border border-slate-200 bg-white p-10 text-center"><p class="font-medium text-slate-700">${escapeHtml(title)}</p><p class="mt-1 text-sm text-slate-500">${escapeHtml(detail)}</p></div>`;
    const link = (page, params = {}) => {
        const url = new URL(`${baseUrl}/index.php`, location.origin);
        url.searchParams.set('page', page);
        Object.entries(params).forEach(([key, value]) => url.searchParams.set(key, value));
        return url.toString();
    };
    async function request(operation, { method = 'GET', params = {}, body } = {}) {
        const url = new URL(apiUrl, location.origin);
        url.searchParams.set('operation', operation);
        Object.entries(params).forEach(([key, value]) => {
            if (value !== undefined && value !== null && value !== '') url.searchParams.set(key, value);
        });
        const response = await fetch(url, {
            method,
            credentials: 'same-origin',
            headers: body === undefined ? { Accept: 'application/json' } : {
                Accept: 'application/json', 'Content-Type': 'application/json'
            },
            body: body === undefined ? undefined : JSON.stringify(body)
        });
        const payload = await response.json();
        if (!response.ok || payload.success === false) throw Object.assign(new Error(payload.message || 'Request failed.'), { payload });
        return writeOperations.has(operation) ? payload : payload.data;
    }
    function showNotice(message, success = false) {
        const el = document.getElementById('nurseAlert');
        if (!el) return;
        el.className = `mb-5 rounded-lg px-4 py-3 text-sm border ${success
            ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
            : 'border-rose-200 bg-rose-50 text-rose-800'}`;
        el.textContent = message;
        el.classList.remove('hidden');
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    function showValidationErrors(errors = {}) {
        document.querySelectorAll('[data-error-for]').forEach(el => {
            el.textContent = errors[el.dataset.errorFor] || '';
        });
    }
    function cell(content, className = 'px-5 py-4') {
        return `<td class="${className}">${content}</td>`;
    }
    function roomMarkup(room, radioName = 'room_id') {
        const occupied = room.status_name === 'Occupied';
        return `<label class="flex items-center gap-3 rounded-lg border p-3 ${occupied ? 'bg-slate-50 opacity-60' : 'hover:bg-slate-50'}">
            <input type="radio" name="${radioName}" value="${Number(room.room_id)}" data-room-id="${Number(room.room_id)}" ${occupied ? 'disabled' : ''}>
            <span class="min-w-0 flex-1"><span class="block font-semibold">Room ${escapeHtml(room.room_number)}</span>
                <span class="block text-xs text-slate-500">${escapeHtml(room.room_type_name)} · ${money(room.rate_per_day)}/day</span>
                <span class="block text-xs text-slate-500">${escapeHtml(room.building_name || '')} ${escapeHtml(room.floor_level_name || '')}</span>
            </span><span class="text-xs ${occupied ? 'text-rose-700' : 'text-emerald-700'}">${occupied ? 'Occupied' : 'Available'}</span>
        </label>`;
    }

    async function loadDashboard() {
        const target = document.getElementById('nurseDashboard');
        try {
            const data = await request('dashboard');
            const s = data.stats || {};
            const ready = (data.ready_for_discharge || []).slice(0, 5);
            target.innerHTML = `<div class="grid grid-cols-1 gap-4 mb-8 sm:grid-cols-2 lg:grid-cols-4">
                ${[['Active Admissions', s.active_admissions, 'text-slate-900'], ['Available Rooms', s.available_rooms, 'text-emerald-600'], ['Occupied Rooms', s.occupied_rooms, 'text-amber-600'], ['Ready for Discharge', s.ready_for_discharge, 'text-blue-600']].map(([label, value, color]) => `<div class="rounded-xl border bg-white p-5"><p class="text-xs font-medium uppercase tracking-wide text-slate-500">${label}</p><p class="mt-2 text-2xl font-bold ${color}">${Number(value || 0)}</p></div>`).join('')}
            </div>
            ${ready.length ? `<div class="mb-8 overflow-hidden rounded-xl border border-blue-200 bg-white">
                <div class="border-b bg-blue-50 px-5 py-4"><h2 class="font-semibold">Ready for Discharge — Waiting for Billing</h2><p class="text-xs text-slate-600">Billing must be Paid or Partially Paid before discharge.</p></div>
                <div class="overflow-x-auto"><table class="min-w-full text-sm"><thead class="bg-slate-50 text-left text-xs uppercase"><tr><th class="px-5 py-3">Patient</th><th class="px-5 py-3">Room</th><th class="px-5 py-3">Admitted</th><th class="px-5 py-3">Billing</th><th class="px-5 py-3">Action</th></tr></thead><tbody class="divide-y">${ready.map(p => `<tr>${cell(`<strong>${escapeHtml(p.first_name)} ${escapeHtml(p.last_name)}</strong><small class="block text-slate-500">#${Number(p.admission_id)}</small>`)}${cell(`${escapeHtml(p.room_number || '—')}<small class="block text-slate-500">${escapeHtml(p.room_type_name || '')}</small>`)}${cell(date(p.admission_datetime))}${cell(p.statement_id ? `${badge(p.billing_status_name, p.billing_status_color)}<small class="block text-slate-500">Balance: ${money(p.balance_amount)}</small>` : 'No statement yet')}${cell(`<a class="text-blue-700 underline" href="${link('nurse-discharge-ready')}">View discharge</a>`)}</tr>`).join('')}</tbody></table></div>
            </div>` : ''}
            <div class="mb-8 overflow-hidden rounded-xl border bg-white"><div class="border-b px-5 py-4"><h2 class="font-semibold">Active Admissions</h2></div>
                ${(data.active_admissions || []).length ? `<div class="overflow-x-auto"><table class="min-w-full text-sm"><thead class="bg-slate-50 text-left text-xs uppercase"><tr><th class="px-5 py-3">Patient</th><th class="px-5 py-3">Room</th><th class="px-5 py-3">Admitted</th><th class="px-5 py-3">Status</th><th class="px-5 py-3"></th></tr></thead><tbody class="divide-y">${data.active_admissions.map(a => `<tr>${cell(`<strong>${escapeHtml(a.first_name)} ${escapeHtml(a.last_name)}</strong><small class="block text-slate-500">#${Number(a.admission_id)}</small>`)}${cell(`${escapeHtml(a.room_number || 'No room assigned')}<small class="block text-slate-500">${escapeHtml(a.room_type_name || '')}</small>`)}${cell(date(a.admission_datetime, true))}${cell(badge(a.status_name, a.status_color))}${cell(`<a class="text-blue-700 underline" href="${link('nurse-room-assignments', { admission_id: a.admission_id })}">Manage</a>`)}</tr>`).join('')}</tbody></table></div>` : emptyState('No active admissions')}</div>
            ${(data.rooms_needing_attention || []).length ? `<div class="rounded-xl border bg-white p-5"><h2 class="mb-3 font-semibold">Rooms Needing Attention</h2><div class="flex flex-wrap gap-3">${data.rooms_needing_attention.map(r => `<span class="rounded-lg border px-3 py-2 text-sm" style="color:${escapeHtml(r.color_code || '#64748b')}">Room ${escapeHtml(r.room_number)} · ${escapeHtml(r.status_name)}</span>`).join('')}</div></div>` : ''}`;
        } catch (error) {
            target.innerHTML = emptyState('Could not load nurse dashboard', error.message);
        }
    }

    async function loadFulfillmentReceipts() {
            const target = document.getElementById('nurseFulfillmentReceipts') || root;
            const render = rows => {
                if (!rows.length) {
                    target.innerHTML = emptyState('No department items are waiting to be received.');
                    return;
                }

                target.innerHTML = `<div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                            <tr><th class="px-5 py-3">Patient / Admission</th><th class="px-5 py-3">Item</th><th class="px-5 py-3">Department</th><th class="px-5 py-3 text-right">Fulfilled</th><th class="px-5 py-3 text-right">Received</th><th class="px-5 py-3 text-right">Waiting</th><th class="px-5 py-3 text-right">Unit Price</th><th class="px-5 py-3">Receive Quantity</th><th class="px-5 py-3"></th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">${rows.map(row => {
                            const remaining = Number(row.quantity_fulfilled) - Number(row.quantity_received);
                            return `<tr>
                                <td class="px-5 py-4"><p class="font-medium text-slate-900">${escapeHtml(row.first_name)} ${escapeHtml(row.last_name)}</p><p class="text-xs text-slate-500">Admission #${Number(row.admission_id)} · Request #${Number(row.request_id)}</p></td>
                                <td class="px-5 py-4"><p class="font-medium text-slate-900">${escapeHtml(row.item_name)}</p><p class="text-xs text-slate-500">${escapeHtml(row.category_name)}${row.item_code ? ` · ${escapeHtml(row.item_code)}` : ''}</p></td>
                                <td class="px-5 py-4">${escapeHtml(row.department_name || '—')}</td>
                                <td class="px-5 py-4 text-right">${Number(row.quantity_fulfilled)} ${escapeHtml(row.unit_of_measure || '')}</td>
                                <td class="px-5 py-4 text-right">${Number(row.quantity_received)}</td>
                                <td class="px-5 py-4 text-right font-semibold text-amber-700">${remaining}</td>
                                <td class="px-5 py-4 text-right">${money(row.unit_price)}</td>
                                <td class="px-5 py-4"><input type="number" min="1" max="${remaining}" value="${remaining}" data-receive-quantity="${Number(row.fulfillment_item_id)}" class="w-24 rounded-lg border border-slate-300 px-2.5 py-2 text-right"></td>
                                <td class="px-5 py-4 text-right"><button type="button" data-receive-item="${Number(row.fulfillment_item_id)}" class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700">Receive</button></td>
                            </tr>`;
                        }).join('')}</tbody>
                    </table>
                </div>`;

                target.querySelectorAll('[data-receive-item]').forEach(button => {
                    button.addEventListener('click', async () => {
                        const itemId = Number(button.dataset.receiveItem);
                        const quantityInput = target.querySelector(`[data-receive-quantity="${itemId}"]`);
                        const quantity = Number(quantityInput?.value);
                        if (!Number.isInteger(quantity) || quantity <= 0 || quantity > Number(quantityInput.max)) {
                            showNotice('Enter a valid quantity no greater than the amount waiting to be received.');
                            return;
                        }
                        button.disabled = true;
                        try {
                            const result = await request('receive-fulfillment-item', {
                                method: 'POST',
                                body: { fulfillment_item_id: itemId, quantity_received: quantity }
                            });
                            showNotice(result.message || 'Item receipt recorded and billing updated.', true);
                            render(await request('pending-fulfillment-receipts'));
                        } catch (error) {
                            showNotice(error.message);
                            button.disabled = false;
                        }
                    });
                });
            };

            try {
                render(await request('pending-fulfillment-receipts'));
            } catch (error) {
                target.innerHTML = emptyState('Could not load department fulfillments.', error.message);
            }
        }

    async function initNurseOrderEntry() {
        const target = document.getElementById('nurseOrderEntry') || root;
        try {
            const [options, requests] = await Promise.all([
                request('nurse-order-options'),
                request('nurse-order-requests')
            ]);
            const admissionOptions = (options.admissions || []).map(admission =>
                `<option value="${Number(admission.admission_id)}">${escapeHtml(admission.last_name)}, ${escapeHtml(admission.first_name)} · Admission #${Number(admission.admission_id)} · ${escapeHtml(admission.room_number || 'No room')}</option>`
            ).join('');
            const departmentOptions = (options.departments || []).map(department =>
                `<option value="${Number(department.department_id)}">${escapeHtml(department.department_name)}</option>`
            ).join('');
            const itemOptions = (options.items || []).map(item =>
                `<option value="${Number(item.charge_item_id)}">${escapeHtml(item.category_name)} — ${escapeHtml(item.item_name)}${item.unit_of_measure ? ` (${escapeHtml(item.unit_of_measure)})` : ''}</option>`
            ).join('');

            target.innerHTML = `<div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                <section class="rounded-xl border border-slate-200 bg-white p-6 xl:col-span-1">
                    <h2 class="mb-4 text-lg font-semibold text-slate-900">Create Department Request</h2>
                    <form id="nurseOrderForm" class="space-y-4">
                        <label class="block text-sm font-medium text-slate-700">Request type
                            <select name="request_source" id="nurseOrderSource" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                                <option value="NURSE_TRANSCRIPTION">Transcribe doctor order</option>
                                <option value="WARD_REQUISITION">Ward requisition</option>
                            </select>
                        </label>
                        <label class="block text-sm font-medium text-slate-700">Active admission
                            <select name="admission_id" id="nurseOrderAdmission" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                                <option value="">Select patient</option>${admissionOptions}
                            </select>
                        </label>
                        <label class="block text-sm font-medium text-slate-700">Responsible assigned doctor
                            <select name="doctor_id" id="nurseOrderDoctor" required disabled class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                                <option value="">Select an admission first</option>
                            </select>
                        </label>
                        <label class="block text-sm font-medium text-slate-700">Fulfillment department
                            <select name="department_id" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                                <option value="">Select department</option>${departmentOptions}
                            </select>
                        </label>
                        <label id="nurseOrderTextWrap" class="block text-sm font-medium text-slate-700">Doctor's order to transcribe
                            <textarea name="order_text" rows="3" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="Enter the doctor's documented order"></textarea>
                        </label>
                        <div>
                            <div class="mb-2 flex items-center justify-between">
                                <span class="text-sm font-medium text-slate-700">Requested items</span>
                                <button type="button" id="addNurseOrderItem" class="text-sm font-medium text-blue-700">Add item</button>
                            </div>
                            <div id="nurseOrderItems" class="space-y-3"></div>
                        </div>
                        <label class="block text-sm font-medium text-slate-700">Additional notes
                            <textarea name="notes" rows="2" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
                        </label>
                        <button type="submit" class="w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Send to Department</button>
                    </form>
                </section>
                <section class="overflow-hidden rounded-xl border border-slate-200 bg-white xl:col-span-2">
                    <div class="border-b border-slate-200 px-5 py-4">
                        <h2 class="font-semibold text-slate-900">Requests Submitted by Nursing</h2>
                        <p class="mt-1 text-xs text-slate-500">Department fulfillment and patient charges are recorded through the existing fulfillment and receipt workflow.</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                                <tr><th class="px-5 py-3">Patient / Request</th><th class="px-5 py-3">Type / Department</th><th class="px-5 py-3">Items</th><th class="px-5 py-3">Responsible Doctor</th><th class="px-5 py-3">Status</th></tr>
                            </thead>
                            <tbody id="nurseOrderHistory" class="divide-y divide-slate-100"></tbody>
                        </table>
                    </div>
                </section>
            </div>`;

            const itemRows = target.querySelector('#nurseOrderItems');
            const addItem = () => {
                const row = document.createElement('div');
                row.className = 'nurse-order-item rounded-lg border border-slate-200 p-3';
                row.innerHTML = `<label class="block text-sm">Item / service
                        <select data-order-item class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" required>
                            <option value="">Select item</option>${itemOptions}
                        </select>
                    </label>
                    <div class="mt-3 flex items-end gap-2">
                        <label class="flex-1 text-sm">Quantity
                            <input data-order-quantity type="number" min="1" step="1" value="1" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                        </label>
                        <button type="button" data-remove-order-item class="rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-600">Remove</button>
                    </div>
                    <label class="mt-3 block text-sm">Item instructions
                        <input data-order-instructions class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                    </label>`;
                itemRows.append(row);
                row.querySelector('[data-remove-order-item]').addEventListener('click', () => {
                    if (itemRows.querySelectorAll('.nurse-order-item').length > 1) row.remove();
                });
            };
            addItem();
            target.querySelector('#addNurseOrderItem').addEventListener('click', addItem);

            const sourceSelect = target.querySelector('#nurseOrderSource');
            const orderText = target.querySelector('[name="order_text"]');
            const updateSource = () => {
                const transcribing = sourceSelect.value === 'NURSE_TRANSCRIPTION';
                target.querySelector('#nurseOrderTextWrap').classList.toggle('hidden', !transcribing);
                orderText.required = transcribing;
            };
            sourceSelect.addEventListener('change', updateSource);
            updateSource();

            const doctorSelect = target.querySelector('#nurseOrderDoctor');
            target.querySelector('#nurseOrderAdmission').addEventListener('change', async event => {
                doctorSelect.disabled = true;
                doctorSelect.innerHTML = '<option value="">Loading assigned doctors…</option>';
                if (!event.target.value) {
                    doctorSelect.innerHTML = '<option value="">Select an admission first</option>';
                    return;
                }
                try {
                    const doctors = await request('admission-doctors', {
                        params: { admission_id: event.target.value }
                    });
                    doctorSelect.innerHTML = doctors.length
                        ? `<option value="">Select responsible doctor</option>${doctors.map(doctor => `<option value="${Number(doctor.doctor_id)}">${escapeHtml(doctor.first_name)} ${escapeHtml(doctor.last_name)} · ${escapeHtml(doctor.doctor_role)}</option>`).join('')}`
                        : '<option value="">No assigned doctor — assign a doctor first</option>';
                    doctorSelect.disabled = doctors.length === 0;
                } catch (error) {
                    doctorSelect.innerHTML = '<option value="">Could not load assigned doctors</option>';
                    showNotice(error.message);
                }
            });

            const history = target.querySelector('#nurseOrderHistory');
            history.innerHTML = requests.length ? requests.map(row => `<tr>
                <td class="px-5 py-4"><span class="font-medium">${escapeHtml(row.first_name)} ${escapeHtml(row.last_name)}</span><span class="block text-xs text-slate-500">Admission #${Number(row.admission_id)} · Request #${Number(row.request_id)}</span><span class="block text-xs text-slate-500">${date(row.request_datetime, true)}</span></td>
                <td class="px-5 py-4">${escapeHtml(row.request_source === 'NURSE_TRANSCRIPTION' ? 'Transcribed order' : 'Ward requisition')}<span class="block text-xs text-slate-500">${escapeHtml(row.department_name || '—')}</span></td>
                <td class="px-5 py-4">${Number(row.item_count)} item(s), qty ${Number(row.total_quantity)}</td>
                <td class="px-5 py-4">${escapeHtml(row.doctor_first_name || '')} ${escapeHtml(row.doctor_last_name || '')}</td>
                <td class="px-5 py-4">${badge(row.status)}</td>
            </tr>`).join('') : `<tr><td colspan="5" class="px-5 py-8 text-center text-slate-500">No nursing requests have been submitted yet.</td></tr>`;

            target.querySelector('#nurseOrderForm').addEventListener('submit', async event => {
                event.preventDefault();
                const form = event.currentTarget;
                const items = [...itemRows.querySelectorAll('.nurse-order-item')].map(row => ({
                    charge_item_id: Number(row.querySelector('[data-order-item]').value),
                    quantity: Number(row.querySelector('[data-order-quantity]').value),
                    instructions: row.querySelector('[data-order-instructions]').value.trim()
                }));
                const body = Object.fromEntries(new FormData(form).entries());
                body.admission_id = Number(body.admission_id);
                body.doctor_id = Number(body.doctor_id);
                body.department_id = Number(body.department_id);
                body.items = items;
                const submitButton = form.querySelector('[type="submit"]');
                submitButton.disabled = true;
                try {
                    const result = await request('create-nurse-order-request', {
                        method: 'POST',
                        body
                    });
                    showNotice(result.message, true);
                    await initNurseOrderEntry();
                } catch (error) {
                    showNotice(error.message);
                    submitButton.disabled = false;
                }
            });
        } catch (error) {
            target.innerHTML = emptyState('Could not load nurse order entry.', error.message);
        }
    }

    async function searchPatients(query, target, summary) {
        if (!query.trim()) {
            target.innerHTML = emptyState('Search for a patient', 'Enter a name, contact number, or email.');
            if (summary) summary.classList.add('hidden');
            return;
        }
        try {
            const patients = await request('search-patients', { params: { q: query.trim() } });
            if (summary) {
                summary.textContent = `Found ${patients.length} result(s) for “${query.trim()}”.`;
                summary.classList.remove('hidden');
            }
            target.innerHTML = patients.length ? `<div class="overflow-hidden rounded-xl border bg-white"><div class="overflow-x-auto"><table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase"><tr><th class="px-5 py-3">Patient</th><th class="px-5 py-3">Contact</th><th class="px-5 py-3">Admission Status</th><th class="px-5 py-3 text-right">Actions</th></tr></thead>
                <tbody class="divide-y">${patients.map(p => `<tr><td class="px-5 py-4"><strong>${escapeHtml(p.first_name)} ${escapeHtml(p.last_name)}</strong><small class="block text-slate-500">${escapeHtml(p.gender_name)} · ${escapeHtml(p.birth_date || '—')}</small></td>
                    <td class="px-5 py-4">${escapeHtml(p.email || '—')}<small class="block text-slate-500">${escapeHtml(p.contact_number || '')}</small></td>
                    <td class="px-5 py-4">${p.admission_id ? `${badge(p.admission_status_name, p.admission_status_color)}<small class="block text-slate-500">${date(p.admission_datetime)}</small>` : '<span class="text-slate-400">Not admitted</span>'}</td>
                    <td class="px-5 py-4 text-right">${p.admission_id
                        ? `<a class="text-blue-700 underline" href="${link('nurse-room-assignments', { admission_id: p.admission_id })}">Manage admission</a>`
                        : `<a class="text-blue-700 underline" href="${link('nurse-admission-create', { patient_id: p.patient_id })}">Admit</a>`}</td></tr>`).join('')}</tbody>
                </table></div></div>` : emptyState('No patients found', 'Try a different search term or register a new patient.');
        } catch (error) {
            target.innerHTML = emptyState('Could not search patients', error.message);
        }
    }

    async function initPatientSearch() {
        const form = document.getElementById('patientSearchForm');
        const input = document.getElementById('patientSearchQuery');
        const target = document.getElementById('patientSearchResults');
        const summary = document.getElementById('patientSearchSummary');
        const query = new URLSearchParams(location.search).get('q') || '';
        input.value = query;
        await searchPatients(query, target, summary);
        form.addEventListener('submit', event => {
            event.preventDefault();
            const next = new URL(location.href);
            if (input.value.trim()) next.searchParams.set('q', input.value.trim());
            else next.searchParams.delete('q');
            history.replaceState({}, '', next);
            searchPatients(input.value, target, summary);
        });
    }

    async function initRegister() {
        const form = document.getElementById('registerForm');
        try {
            const options = await request('options');
            document.getElementById('genderSelect').innerHTML = '<option value="">— Select gender —</option>' + options.genders.map(g => `<option value="${Number(g.gender_id)}">${escapeHtml(g.gender_name)}</option>`).join('');
        } catch (error) {
            showNotice(`Could not load form options: ${error.message}`);
        }
        form.addEventListener('submit', async event => {
            event.preventDefault();
            showValidationErrors();
            const button = document.getElementById('registerSubmit');
            button.disabled = true;
            try {
                const result = await request('register-patient', { method: 'POST', body: Object.fromEntries(new FormData(form)) });
                showNotice(result.message || 'Patient registered successfully.', true);
                form.reset();
                const id = result.patient_id;
                if (id) {
                    const followup = document.createElement('a');
                    followup.href = link('nurse-admission-create', { patient_id: id });
                    followup.textContent = ' Create admission';
                    followup.className = 'ml-2 font-semibold underline';
                    document.getElementById('nurseAlert').append(followup);
                }
            } catch (error) {
                showValidationErrors(error.payload?.errors || {});
                showNotice(error.message || 'Could not register patient.');
            } finally {
                button.disabled = false;
            }
        });
    }

    async function initAdmissionCreate() {
        const form = document.getElementById('admissionForm');
        const patientInput = document.getElementById('patientSearch');
        const patientId = document.getElementById('patient_id');
        const results = document.getElementById('patientResults');
        const selected = document.getElementById('selectedPatient');
        let roomOptions = [];
        let searchTimer;
        let selectedData = null;
        const renderSelected = patient => {
            selectedData = patient;
            patientId.value = patient.patient_id;
            patientInput.value = `${patient.first_name} ${patient.last_name}`;
            selected.innerHTML = `<span class="font-semibold">${escapeHtml(patient.first_name)} ${escapeHtml(patient.last_name)}</span><span class="ml-2 text-slate-600">${escapeHtml(patient.gender_name || '')} · ${escapeHtml(patient.birth_date || '—')}</span>${patient.current_admission ? `<span class="ml-2 text-amber-700">Already has an active admission (#${Number(patient.current_admission.admission_id)}).</span><a class="ml-2 underline" href="${link('nurse-room-assignments', { admission_id: patient.current_admission.admission_id })}">Manage it</a>` : ''}<button type="button" id="changePatient" class="ml-3 text-blue-700 underline">Change</button>`;
            selected.classList.remove('hidden');
            document.getElementById('changePatient').addEventListener('click', () => {
                selectedData = null;
                patientId.value = '';
                patientInput.value = '';
                selected.classList.add('hidden');
                patientInput.focus();
            });
        };
        try {
            const options = await request('options');
            const statuses = options.admission_statuses || [];
            document.getElementById('admissionStatusSelect').innerHTML = statuses.map(s => `<option value="${Number(s.status_id)}" ${s.status_name === 'Admitted' ? 'selected' : ''}>${escapeHtml(s.status_name)}</option>`).join('');
            document.getElementById('doctorOptions').innerHTML = options.doctors.length ? options.doctors.map(d => `<label class="flex items-center gap-3 rounded-lg p-2 hover:bg-slate-50"><input class="doctor-checkbox" type="checkbox" name="doctor_ids[]" value="${Number(d.doctor_id)}"><span class="flex-1 text-sm">Dr. ${escapeHtml(d.first_name)} ${escapeHtml(d.last_name)}<small class="block text-slate-500">Consultation fee: ${money(d.consultation_fee)}</small></span><select name="doctor_roles[${Number(d.doctor_id)}]" class="rounded border px-2 py-1 text-xs"><option>Attending</option><option>Consulting</option><option>Referring</option></select></label>`).join('') : '<p class="text-sm text-slate-500">No active doctors available.</p>';
            document.getElementById('roomTypeFilters').innerHTML = `<button type="button" class="room-type-filter rounded-full border px-3 py-1.5 text-xs" data-type="">All Types</button>${options.room_types.map(t => `<button type="button" class="room-type-filter rounded-full border px-3 py-1.5 text-xs" data-type="${Number(t.room_type_id)}">${escapeHtml(t.room_type_name)} (${money(t.rate_per_day)}/day)</button>`).join('')}`;
            roomOptions = options.available_rooms || [];
            const renderRooms = typeId => {
                const items = roomOptions.filter(r => !typeId || String(r.room_type_id) === typeId);
                document.getElementById('roomList').innerHTML = items.length ? items.map(r => roomMarkup(r)).join('') : '<p class="col-span-2 py-4 text-center text-sm text-slate-500">No rooms found.</p>';
            };
            renderRooms('');
            document.getElementById('roomTypeFilters').addEventListener('click', event => {
                const button = event.target.closest('.room-type-filter');
                if (button) renderRooms(button.dataset.type);
            });
            document.getElementById('doctorOptions').addEventListener('change', () => {
                document.getElementById('doctorCount').textContent = `${document.querySelectorAll('.doctor-checkbox:checked').length} selected`;
            });
        } catch (error) {
            showNotice(`Could not load admission options: ${error.message}`);
        }
        const initialId = Number(patientId.value || 0);
        if (initialId) {
            try {
                const p = await request('patient-details', { params: { id: initialId } });
                renderSelected(p);
                if (p.current_admission) showNotice(`This patient already has an active admission (#${p.current_admission.admission_id}). Manage that admission instead.`);
            } catch (error) {
                patientId.value = '';
                showNotice(error.message);
            }
        }
        patientInput.addEventListener('input', () => {
            clearTimeout(searchTimer);
            const query = patientInput.value.trim();
            if (query.length < 2) {
                results.classList.add('hidden');
                return;
            }
            searchTimer = setTimeout(async () => {
                try {
                    const patients = await request('search-patients', { params: { q: query } });
                    results.innerHTML = patients.length ? patients.map(p => `<button type="button" data-patient-id="${Number(p.patient_id)}" class="block w-full border-b px-4 py-3 text-left hover:bg-slate-50"><strong>${escapeHtml(p.first_name)} ${escapeHtml(p.last_name)}</strong><small class="block text-slate-500">${escapeHtml(p.contact_number || p.email || 'No contact')}${p.admission_id ? ` · ${escapeHtml(p.admission_status_name)}` : ''}</small></button>`).join('') : '<p class="px-4 py-3 text-sm text-slate-500">No patients found.</p>';
                    results.classList.remove('hidden');
                } catch (error) { results.innerHTML = `<p class="px-4 py-3 text-sm text-rose-600">${escapeHtml(error.message)}</p>`; results.classList.remove('hidden'); }
            }, 250);
        });
        results.addEventListener('click', async event => {
            const button = event.target.closest('[data-patient-id]');
            if (!button) return;
            try {
                renderSelected(await request('patient-details', { params: { id: button.dataset.patientId } }));
                results.classList.add('hidden');
            } catch (error) { showNotice(error.message); }
        });
        form.addEventListener('submit', async event => {
            event.preventDefault();
            showValidationErrors();
            const button = document.getElementById('admissionSubmit');
            button.disabled = true;
            const body = { admission_type: form.elements.admission_type.value, admission_status_id: form.elements.admission_status_id.value, patient_id: patientId.value, chief_complaint: form.elements.chief_complaint.value, admission_notes: form.elements.admission_notes.value };
            body.doctor_ids = [...form.querySelectorAll('.doctor-checkbox:checked')].map(box => box.value);
            body.doctor_roles = Object.fromEntries([...form.querySelectorAll('select[name^="doctor_roles"]')].map(select => [select.name.match(/\d+/)[0], select.value]));
            const room = form.querySelector('input[name="room_id"]:checked');
            if (room) body.room_id = room.value;
            try {
                const result = await request('create-admission', { method: 'POST', body });
                showNotice(result.message || 'Patient admitted successfully.', true);
                form.reset();
                patientId.value = '';
                selected.classList.add('hidden');
                const followup = document.createElement('a');
                followup.href = link('nurse-room-assignments', { admission_id: result.admission_id });
                followup.textContent = ' Manage admission';
                followup.className = 'ml-2 font-semibold underline';
                document.getElementById('nurseAlert').append(followup);
            } catch (error) {
                showValidationErrors(error.payload?.errors || {});
                showNotice(error.message || 'Could not create admission.');
            } finally { button.disabled = false; }
        });
    }

    async function initAdmissions() {
        const table = document.getElementById('admissionsTable');
        let admissions = [];
        const render = () => {
            const filter = document.getElementById('admissionFilter').value.trim().toLowerCase();
            const selected = admissions.filter(a => `${a.first_name} ${a.last_name} ${a.room_number || ''}`.toLowerCase().includes(filter));
            const active = admissions.filter(a => a.admission_status_name !== 'Discharged');
            document.getElementById('activeCount').textContent = active.length;
            document.getElementById('dischargedCount').textContent = admissions.length - active.length;
            table.innerHTML = selected.length ? `<div class="overflow-x-auto rounded-xl border bg-white"><table class="min-w-full text-sm"><thead class="bg-slate-50 text-left text-xs uppercase"><tr><th class="px-5 py-3">Patient</th><th class="px-5 py-3">Room</th><th class="px-5 py-3">Admitted</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Transfers</th><th class="px-5 py-3"></th></tr></thead><tbody class="divide-y">${selected.map(a => `<tr>${cell(`<strong>${escapeHtml(a.first_name)} ${escapeHtml(a.last_name)}</strong><small class="block text-slate-500">#${Number(a.admission_id)}</small>`)}${cell(`${escapeHtml(a.room_number || 'No room assigned')}<small class="block text-slate-500">${escapeHtml(a.room_type_name || '')}</small>`)}${cell(date(a.admission_datetime, true))}${cell(badge(a.admission_status_name, a.admission_status_color))}${cell(Number(a.total_room_transfers || 0))}${cell(a.admission_status_name !== 'Discharged' ? `<a class="text-blue-700 underline" href="${link('nurse-room-assignments', { admission_id: a.admission_id })}">Manage</a>` : '—')}</tr>`).join('')}</tbody></table></div>` : emptyState('No admissions found.');
        };
        document.getElementById('admissionFilter').addEventListener('input', render);
        try { admissions = await request('admissions'); render(); }
        catch (error) { table.innerHTML = emptyState('Could not load admissions', error.message); }
    }

    async function initRoomBoard() {
        const target = document.getElementById('roomBoard');
        try {
            const rooms = await request('rooms');
            const counts = {};
            rooms.forEach(r => counts[r.status_name] = (counts[r.status_name] || 0) + 1);
            document.getElementById('roomStatusCounts').innerHTML = Object.entries(counts).map(([status, count]) => `<div class="rounded-xl border bg-white p-5"><p class="text-xs uppercase text-slate-500">${escapeHtml(status)}</p><p class="mt-2 text-3xl font-bold">${count}</p></div>`).join('');
            const grouped = rooms.reduce((all, room) => ((all[room.room_type_name] ||= []).push(room), all), {});
            target.innerHTML = Object.entries(grouped).map(([type, items]) => `<section class="mb-8"><div class="mb-4 flex justify-between"><h2 class="text-lg font-semibold">${escapeHtml(type)}</h2><span class="text-sm text-slate-500">${items.length} room(s)</span></div><div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">${items.map(r => `<article class="rounded-xl border-2 p-4" style="background:${escapeHtml(r.color_code || '#64748b')}0D;border-color:${escapeHtml(r.color_code || '#64748b')}40"><div class="mb-2 flex justify-between"><strong>${escapeHtml(r.room_number)}</strong><span class="h-2.5 w-2.5 rounded-full" style="background:${escapeHtml(r.color_code || '#64748b')}"></span></div><p class="text-xs font-medium" style="color:${escapeHtml(r.color_code || '#64748b')}">${escapeHtml(r.status_name)}</p><p class="mt-1 text-xs text-slate-500">${escapeHtml(r.floor_level_name || '')} ${escapeHtml(r.building_name || '')}</p><p class="mt-2 text-xs">${money(r.rate_per_day)}/day</p></article>`).join('')}</div></section>`).join('') || emptyState('No active rooms found.');
        } catch (error) { target.innerHTML = emptyState('Could not load rooms', error.message); }
    }

    async function initDischargeRecords() {
        const target = document.getElementById('dischargeRecords');
        try {
            const records = await request('discharge-records');
            target.innerHTML = records.length ? `<div class="overflow-x-auto rounded-xl border bg-white"><table class="min-w-full text-sm"><thead class="bg-slate-50 text-left text-xs uppercase"><tr><th class="px-5 py-3">Patient</th><th class="px-5 py-3">Room</th><th class="px-5 py-3">Admitted</th><th class="px-5 py-3">Discharged</th><th class="px-5 py-3">Discharged By</th></tr></thead><tbody class="divide-y">${records.map(r => `<tr>${cell(`<strong>${escapeHtml(r.first_name)} ${escapeHtml(r.last_name)}</strong><small class="block text-slate-500">#${Number(r.admission_id)}</small>`)}${cell(`${escapeHtml(r.room_number || '—')}<small class="block text-slate-500">${escapeHtml(r.room_type_name || '')}</small>`)}${cell(date(r.admission_datetime))}${cell(date(r.discharge_datetime, true))}${cell(r.discharged_by_first ? `${escapeHtml(r.discharged_by_first)} ${escapeHtml(r.discharged_by_last)}` : '—')}</tr>`).join('')}</tbody></table></div>` : emptyState('No discharge records', 'Discharged patients will appear here.');
        } catch (error) { target.innerHTML = emptyState('Could not load discharge records', error.message); }
    }

    async function initDischargeReady() {
        const target = document.getElementById('readyPatients');
        const modal = document.getElementById('dischargeModal');
        const close = () => { modal.classList.add('hidden'); modal.classList.remove('flex'); };
        const refresh = async () => {
            const patients = await request('ready-for-discharge');
            target.innerHTML = patients.length ? `<div class="overflow-x-auto rounded-xl border bg-white"><table class="min-w-full text-sm"><thead class="bg-slate-50 text-left text-xs uppercase"><tr><th class="px-5 py-3">Patient</th><th class="px-5 py-3">Room</th><th class="px-5 py-3">Admitted</th><th class="px-5 py-3">Length of Stay</th><th class="px-5 py-3">Billing</th><th class="px-5 py-3">Action</th></tr></thead><tbody class="divide-y">${patients.map(p => {
                const paid = ['paid', 'partially paid'].includes(String(p.billing_status_name || '').toLowerCase());
                const canDischarge = Boolean(p.statement_id) && paid;
                const admitted = new Date(String(p.admission_datetime || '').replace(' ', 'T'));
                const admittedDay = new Date(admitted.getFullYear(), admitted.getMonth(), admitted.getDate());
                const today = new Date();
                const todayDay = new Date(today.getFullYear(), today.getMonth(), today.getDate());
                const days = Number.isNaN(admitted.getTime()) ? 0 : Math.max(1, Math.floor((todayDay - admittedDay) / 86400000) + 1);
                return `<tr>${cell(`<strong>${escapeHtml(p.first_name)} ${escapeHtml(p.last_name)}</strong><small class="block text-slate-500">#${Number(p.admission_id)}</small>`)}${cell(`${escapeHtml(p.room_number || '—')}<small class="block text-slate-500">${escapeHtml(p.room_type_name || '')}</small>`)}${cell(date(p.admission_datetime))}${cell(`${days} day${days === 1 ? '' : 's'}`)}${cell(p.statement_id ? `${badge(p.billing_status_name, p.billing_status_color)}<small class="mt-1 block text-slate-500">Balance: ${money(p.balance_amount)}</small>` : '<span class="text-amber-600">No statement yet</span>')}${cell(canDischarge ? `<button data-discharge-id="${Number(p.admission_id)}" data-patient-name="${escapeHtml(p.first_name)} ${escapeHtml(p.last_name)}" class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white">Discharge</button>` : '<span class="text-xs text-slate-500">Awaiting billing</span>')}</tr>`;
            }).join('')}</tbody></table></div>` : emptyState('No patients ready for discharge', 'No patient has been cleared for discharge by their doctor yet.');
        };
        target.addEventListener('click', event => {
            const button = event.target.closest('[data-discharge-id]');
            if (!button) return;
            document.getElementById('dischargePatientName').textContent = button.dataset.patientName;
            document.getElementById('dischargeForm').elements.admission_id.value = button.dataset.dischargeId;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        });
        modal.addEventListener('click', event => {
            if (event.target === modal || event.target.closest('[data-close-discharge]')) close();
        });
        document.getElementById('dischargeForm').addEventListener('submit', async event => {
            event.preventDefault();
            const button = document.getElementById('confirmDischarge');
            button.disabled = true;
            try {
                const body = Object.fromEntries(new FormData(event.currentTarget));
                await request('discharge', { method: 'POST', body });
                close();
                showNotice('Patient discharged successfully.', true);
                await refresh();
            } catch (error) { showNotice(error.message || 'Could not discharge patient.'); }
            finally { button.disabled = false; }
        });
        try { await refresh(); } catch (error) { target.innerHTML = emptyState('Could not load discharge queue', error.message); }
    }

    async function initRoomAssignments() {
        const target = document.getElementById('roomAssignmentContent');
        const admissionId = Number(new URLSearchParams(location.search).get('admission_id') || 0);
        const refresh = async () => {
            if (!admissionId) {
                const admissions = (await request('admissions')).filter(a => a.admission_status_name !== 'Discharged');
                target.innerHTML = admissions.length ? `<div class="overflow-x-auto rounded-xl border bg-white"><table class="min-w-full text-sm"><thead class="bg-slate-50 text-left text-xs uppercase"><tr><th class="px-5 py-3">Patient</th><th class="px-5 py-3">Room</th><th class="px-5 py-3">Admitted</th><th class="px-5 py-3">Status</th><th class="px-5 py-3"></th></tr></thead><tbody class="divide-y">${admissions.map(a => `<tr>${cell(`<strong>${escapeHtml(a.first_name)} ${escapeHtml(a.last_name)}</strong><small class="block text-slate-500">#${Number(a.admission_id)}</small>`)}${cell(escapeHtml(a.room_number || 'No room assigned'))}${cell(date(a.admission_datetime, true))}${cell(badge(a.admission_status_name, a.admission_status_color))}${cell(`<a class="text-blue-700 underline" href="${link('nurse-room-assignments', { admission_id: a.admission_id })}">Manage</a>`)}</tr>`).join('')}</tbody></table></div>` : emptyState('No active admissions', 'Admit a patient first to assign a room.');
                return;
            }
            const [admission, assignments, history, doctors, options] = await Promise.all([
                request('patient-by-admission', { params: { admission_id: admissionId } }),
                request('room-assignments', { params: { admission_id: admissionId } }),
                request('room-transfer-history', { params: { admission_id: admissionId } }),
                request('admission-doctors', { params: { admission_id: admissionId } }),
                request('options')
            ]);
            if (admission.status_id === '2' || Number(admission.status_id) === 2) {
                target.innerHTML = emptyState('This admission has been discharged.');
                return;
            }
            const current = assignments.find(a => Number(a.is_active) === 1 || !a.end_datetime);
            const available = (options.available_rooms || []).filter(room => room.status_name === 'Available');
            target.innerHTML = `<article class="mb-6 rounded-xl border bg-white p-6"><div class="flex flex-wrap items-center gap-4"><span class="flex h-14 w-14 items-center justify-center rounded-full bg-blue-100 font-bold text-blue-700">${initials(admission)}</span><div><h2 class="text-lg font-bold">${escapeHtml(admission.first_name)} ${escapeHtml(admission.last_name)}</h2><p class="text-sm text-slate-500">Admission #${admissionId} · Admitted ${date(admission.admission_datetime, true)}</p><p class="text-sm text-slate-600">${escapeHtml(admission.chief_complaint || '')}</p></div>${badge(admission.status_name, admission.color_code)}</div></article>
            <div class="mb-6 rounded-xl border bg-white p-6"><h3 class="mb-3 font-semibold">Assigned Doctors</h3><div class="mb-4 space-y-2" id="assignedDoctorRows">${doctors.length ? doctors.map(d => `<div class="flex items-center gap-3 rounded-lg border p-3"><div class="flex-1"><strong>Dr. ${escapeHtml(d.first_name)} ${escapeHtml(d.last_name)}</strong><small class="block text-slate-500">${escapeHtml(d.doctor_role)} · Assigned ${date(d.assigned_datetime)} · ${money(d.consultation_fee_charged)}</small></div><button data-remove-doctor="${Number(d.admission_doctor_id)}" data-name="Dr. ${escapeHtml(d.first_name)} ${escapeHtml(d.last_name)}" class="text-xs text-rose-700">Remove</button></div>`).join('') : emptyState('No doctors assigned yet')}</div><div class="flex flex-col gap-2 sm:flex-row"><select id="newDoctorSelect" class="flex-1 rounded-lg border bg-white px-3 py-2 text-sm"><option value="">— Select a doctor —</option>${options.doctors.map(d => `<option value="${Number(d.doctor_id)}">Dr. ${escapeHtml(d.first_name)} ${escapeHtml(d.last_name)} (${money(d.consultation_fee)})</option>`).join('')}</select><select id="newDoctorRole" class="rounded-lg border bg-white px-3 py-2 text-sm"><option>Attending</option><option>Consulting</option><option>Referring</option></select><button id="addDoctorBtn" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Add Doctor</button></div></div>
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2"><section class="rounded-xl border bg-white p-6"><h3 class="mb-3 font-semibold">Current Room</h3>${current ? `<div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 p-4"><strong>Room ${escapeHtml(current.room_number)} · ${escapeHtml(current.room_type_name)}</strong><p class="mt-1 text-sm">Daily rate ${money(current.daily_rate_at_assignment)} · Assigned ${date(current.start_datetime, true)}</p><p class="text-xs text-slate-500">${escapeHtml(current.building_name || '')} ${escapeHtml(current.floor_level_name || '')}</p></div><div class="mb-3"><label class="block text-sm font-medium">Transfer to</label><select id="transferRoomSelect" class="mt-1 w-full rounded-lg border bg-white px-3 py-2 text-sm"><option value="">— Select available room —</option>${available.map(r => `<option value="${Number(r.room_id)}">${escapeHtml(r.room_number)} — ${escapeHtml(r.room_type_name)} (${money(r.rate_per_day)}/day)</option>`).join('')}</select></div><label class="block text-sm">Transfer Reason<input id="transferReason" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" placeholder="Reason"></label><label class="mt-3 flex items-center gap-2 text-sm"><input id="oldRoomMaintenance" type="checkbox">Set old room to Maintenance</label><button id="transferRoomBtn" class="mt-4 rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white">Transfer Room</button>` : `<p class="mb-3 text-sm text-slate-500">No room assigned yet.</p><select id="assignRoomSelect" class="w-full rounded-lg border bg-white px-3 py-2 text-sm"><option value="">— Select available room —</option>${available.map(r => `<option value="${Number(r.room_id)}">${escapeHtml(r.room_number)} — ${escapeHtml(r.room_type_name)} (${money(r.rate_per_day)}/day)</option>`).join('')}</select><button id="assignRoomBtn" class="mt-4 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Assign Room</button>`}</section>
            <section class="rounded-xl border bg-white p-6"><h3 class="mb-3 font-semibold">Room Transfer History</h3>${history.length ? `<ol class="space-y-3">${history.map(h => `<li class="border-l-2 border-blue-200 pl-3"><strong>Room ${escapeHtml(h.room_number)} · ${escapeHtml(h.room_type_name)}</strong><p class="text-xs text-slate-500">${date(h.start_datetime, true)}${h.end_datetime ? ` – ${date(h.end_datetime, true)}` : ' – Current'}</p><p class="text-xs text-slate-500">${escapeHtml(h.transfer_reason || '')} · ${money(h.daily_rate_at_assignment)}/day</p></li>`).join('')}</ol>` : '<p class="text-sm text-slate-500">No room history yet.</p>'}</section></div>`;
            const assign = document.getElementById('assignRoomBtn');
            if (assign) assign.addEventListener('click', async () => {
                const roomId = document.getElementById('assignRoomSelect').value;
                if (!roomId) return showNotice('Please select a room.');
                assign.disabled = true;
                try { await request('assign-room', { method: 'POST', body: { admission_id: admissionId, room_id: roomId } }); showNotice('Room assigned successfully.', true); await refresh(); }
                catch (error) { showNotice(error.message); assign.disabled = false; }
            });
            const transfer = document.getElementById('transferRoomBtn');
            if (transfer) transfer.addEventListener('click', async () => {
                const roomId = document.getElementById('transferRoomSelect').value;
                if (!roomId) return showNotice('Please select a room.');
                transfer.disabled = true;
                try {
                    await request('transfer-room', { method: 'POST', body: { admission_id: admissionId, new_room_id: roomId, transfer_reason: document.getElementById('transferReason').value, set_old_room_maintenance: document.getElementById('oldRoomMaintenance').checked ? 1 : 0 } });
                    showNotice('Room transfer completed successfully.', true);
                    await refresh();
                } catch (error) { showNotice(error.message); transfer.disabled = false; }
            });
            document.getElementById('addDoctorBtn').addEventListener('click', async event => {
                const button = event.currentTarget;
                const doctorId = document.getElementById('newDoctorSelect').value;
                if (!doctorId) return showNotice('Please select a doctor first.');
                button.disabled = true;
                try {
                    await request('assign-doctor', { method: 'POST', body: { admission_id: admissionId, doctor_id: doctorId, doctor_role: document.getElementById('newDoctorRole').value } });
                    showNotice('Doctor assigned successfully.', true);
                    await refresh();
                } catch (error) { showNotice(error.message); button.disabled = false; }
            });
            target.querySelectorAll('[data-remove-doctor]').forEach(button => button.addEventListener('click', async () => {
                if (!confirm(`Remove ${button.dataset.name} from this admission?`)) return;
                button.disabled = true;
                try {
                    await request('remove-doctor', { method: 'POST', params: { id: button.dataset.removeDoctor }, body: { admission_doctor_id: button.dataset.removeDoctor } });
                    showNotice('Doctor removed from admission.', true);
                    await refresh();
                } catch (error) { showNotice(error.message); button.disabled = false; }
            }));
        };
        try { await refresh(); } catch (error) { target.innerHTML = emptyState('Could not load admission', error.message); }
    }

    const init = {
        dashboard: loadDashboard,
        'patient-search': initPatientSearch,
        'patient-register': initRegister,
        'admission-create': initAdmissionCreate,
        admissions: initAdmissions,
        'room-board': initRoomBoard,
        'discharge-records': initDischargeRecords,
        'discharge-ready': initDischargeReady,
        'room-assignments': initRoomAssignments,
        'order-entry': initNurseOrderEntry,
        'fulfillment-receipts': loadFulfillmentReceipts
    }[view];
    if (init && root.dataset.nurseInitStarted !== 'true') {
        root.dataset.nurseInitStarted = 'true';
        init();
    }
}

document.addEventListener('spa:page-loaded', initializeNursePage);
document.addEventListener('DOMContentLoaded', () => {
    initializeNursePage({ detail: { page: document.body.dataset.page || '' } });
}, { once: true });
if (document.readyState !== 'loading') {
    initializeNursePage({ detail: { page: document.body.dataset.page || '' } });
}
