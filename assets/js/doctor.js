document.addEventListener('spa:page-loaded', event => {
    if (!event.detail.page.startsWith('doctor')) return;

    const app = document.querySelector('#doctor-app');
    if (!app) return;
    if (app.dataset.doctorInitStarted === 'true') return;
    app.dataset.doctorInitStarted = 'true';

    if (app) {
    const apiUrl = app.dataset.apiUrl;
    const baseUrl = app.dataset.baseUrl;
    const page = app.dataset.page;
    const admissionId = Number(app.dataset.admissionId || 0);

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    })[char]);
    const date = (value, options = { dateStyle: 'medium' }) => value
        ? new Intl.DateTimeFormat(undefined, options).format(new Date(value.replace(' ', 'T')))
        : '—';
    const href = (name, id = '') => `${baseUrl}/index.php?page=${name}${id ? `&admission_id=${id}` : ''}`;
    const endpoint = (operation) => `${apiUrl}?operation=${encodeURIComponent(operation)}`;
    const button = (label, action, attrs = '', style = 'blue') => {
        const colors = {
            blue: 'bg-blue-600 hover:bg-blue-700',
            emerald: 'bg-emerald-600 hover:bg-emerald-700',
            rose: 'bg-rose-600 hover:bg-rose-700',
        };
        return `<button type="button" data-action="${action}" ${attrs} class="rounded-lg px-3 py-1.5 text-xs font-semibold text-white ${colors[style] || colors.blue}">${label}</button>`;
    };
    const card = (title, content, extra = '') =>
        `<section class="overflow-hidden rounded-xl border border-slate-200 bg-white ${extra}"><div class="border-b border-slate-200 px-6 py-4"><h2 class="font-semibold text-slate-900">${title}</h2></div>${content}</section>`;
    const heading = (title, description) =>
        `<header class="mb-6"><h1 class="text-2xl font-bold tracking-tight text-slate-900">${title}</h1><p class="mt-1 text-sm text-slate-500">${description}</p></header>`;
    const renderNotice = (message, success = false) =>
        `<div class="mb-5 rounded-lg border px-4 py-3 text-sm ${success ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-rose-200 bg-rose-50 text-rose-800'}" role="status">${escapeHtml(message)}</div>`;

    async function request(operation, { method = 'GET', data = null, params = {} } = {}) {
        const url = new URL(endpoint(operation), location.href);
        Object.entries(params).forEach(([key, value]) => url.searchParams.set(key, value));
        const response = await fetch(url, {
            method,
            credentials: 'same-origin',
            headers: data ? { 'Content-Type': 'application/json' } : {},
            body: data ? JSON.stringify(data) : undefined,
        });
        const result = await response.json();
        if (!response.ok || !result.success) throw new Error(result.message || 'Could not complete request.');
        return result.data;
    }

    const patientName = (patient) => `${patient.first_name ?? ''} ${patient.last_name ?? ''}`.trim();
    const emptyRow = (columns, text) => `<tr><td colspan="${columns}" class="px-6 py-10 text-center text-slate-400">${text}</td></tr>`;
    const table = (headers, rows) => `<div class="overflow-x-auto"><table class="min-w-full text-sm"><thead class="bg-slate-50 text-slate-600"><tr>${headers.map((h) => `<th class="px-5 py-3 text-left font-medium">${h}</th>`).join('')}</tr></thead><tbody class="divide-y divide-slate-100">${rows}</tbody></table></div>`;
    const assignedRows = (items, managePage = 'doctor-diagnosis-record') => items.length
        ? items.map((p) => `<tr class="hover:bg-slate-50">
            <td class="px-5 py-3"><p class="font-medium text-slate-900">${escapeHtml(patientName(p))}</p><p class="text-xs text-slate-500">#${Number(p.admission_id)} · ${escapeHtml(p.gender_name ?? '')}</p></td>
            <td class="px-5 py-3">${escapeHtml(p.room_number || '—')}</td>
            <td class="px-5 py-3 text-slate-600">${escapeHtml(p.chief_complaint || '—')}</td>
            <td class="px-5 py-3 text-slate-600">${escapeHtml(date(p.admission_datetime))}</td>
            <td class="px-5 py-3 text-right"><a class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-700" href="${href(managePage, Number(p.admission_id))}">Open</a></td>
        </tr>`).join('')
        : emptyRow(5, 'No active assignments.');

    function alertArea() {
        return '<div id="doctor-alert" class="hidden mb-5 rounded-lg border px-4 py-3 text-sm" role="status"></div>';
    }

    function showAlert(message, success = false) {
        const el = document.querySelector('#doctor-alert');
        if (!el) return;
        el.className = `mb-5 rounded-lg border px-4 py-3 text-sm ${success ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-rose-200 bg-rose-50 text-rose-800'}`;
        el.textContent = message;
    }

    async function load() {
        app.innerHTML = '<div class="rounded-xl border border-slate-200 bg-white p-8 text-center text-sm text-slate-500" role="status">Loading clinical data…</div>';
        try {
            if (page === 'dashboard') return renderDashboard(await request('dashboard'));
            if (page === 'patients') return renderPatients(await request('patients'));
            if (page === 'consultations') return renderConsultations(await request('consultations'));
            if (page === 'diagnosis') return renderDiagnosis(await Promise.all([
                request('diagnosis-options'),
                admissionId ? request('admission-details', { params: { admission_id: admissionId } }) : Promise.resolve(null),
            ]));
            if (page === 'services') return renderServices(await Promise.all([
                request('service-options'),
                admissionId ? request('admission-details', { params: { admission_id: admissionId } }) : Promise.resolve(null),
            ]));
            if (page === 'discharge') return renderDischarge(await request('discharge-ready'));
        } catch (error) {
            app.innerHTML = `${renderNotice(error.message)}<button class="rounded-lg border px-3 py-2 text-sm" data-action="refresh">Try again</button>`;
        }
    }

    function renderDashboard(data) {
        const { stats, patients, ready_for_discharge: ready } = data;
        const awaitingConfirmation = ready.filter((patient) =>
            String(patient.status_name || '').toLowerCase() !== 'ready for discharge'
        );
        const metrics = [
            ['Assigned Admissions', stats.assigned_admissions, 'text-slate-900'],
            ["Today's Consultations", stats.today_consultations, 'text-blue-600'],
            ['Pending Requests', stats.pending_requests, 'text-amber-600'],
            ['Ready for Discharge', stats.ready_for_discharge, 'text-emerald-600'],
        ].map(([label, value, color]) => `<div class="rounded-xl border border-slate-200 bg-white p-5"><p class="text-xs font-medium uppercase tracking-wide text-slate-500">${label}</p><p class="mt-2 text-2xl font-bold ${color}">${Number(value)}</p></div>`).join('');
        const readyRows = awaitingConfirmation.slice(0, 5).map((p) => `<tr><td class="px-5 py-3 font-medium">${escapeHtml(patientName(p))} <span class="text-xs text-slate-500">#${Number(p.admission_id)}</span></td><td class="px-5 py-3">${escapeHtml(p.room_number || '—')}</td><td class="px-5 py-3">${escapeHtml(date(p.admission_datetime))}</td><td class="px-5 py-3"><a href="${href('doctor-discharge-ready')}" class="text-emerald-700">Confirm Ready</a></td></tr>`).join('');
        app.innerHTML = `${heading('Dashboard', 'Your assigned patients and pending tasks.')}
            ${awaitingConfirmation.length ? card('Ready for Discharge — Awaiting Your Confirmation', table(['Patient', 'Room', 'Admitted', 'Actions'], readyRows)) : ''}
            <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">${metrics}</div>
            ${card('Assigned Patients', table(['Patient', 'Room', 'Chief Complaint', 'Admitted', 'Actions'], assignedRows(patients.slice(0, 10))))}
            <div class="mt-4 text-right"><a class="text-sm font-medium text-blue-600" href="${href('doctor-patients')}">View all patients →</a></div>`;
    }

    function renderPatients({ assigned, history }) {
        const active = assigned.length ? assigned.map((p) => `<tr><td class="px-5 py-3"><p class="font-medium">${escapeHtml(patientName(p))}</p><p class="text-xs text-slate-500">#${Number(p.admission_id)}</p></td><td class="px-5 py-3">${escapeHtml(p.room_number || 'No room')}</td><td class="px-5 py-3">${escapeHtml(p.chief_complaint || '—')}</td><td class="px-5 py-3">${escapeHtml(date(p.admission_datetime))}</td><td class="px-5 py-3 text-right"><a class="text-blue-700" href="${href('doctor-diagnosis-record', Number(p.admission_id))}">Open Chart</a></td></tr>`).join('') : emptyRow(5, 'No active assignments.');
        const past = history.length ? history.map((p) => `<tr><td class="px-5 py-3"><p class="font-medium">${escapeHtml(patientName(p))}</p><p class="text-xs text-slate-500">${escapeHtml(p.gender_name || '')}</p></td><td class="px-5 py-3">${escapeHtml(p.email || '—')}<p class="text-xs text-slate-500">${escapeHtml(p.contact_number || '')}</p></td><td class="px-5 py-3">${Number(p.total_admissions)}</td><td class="px-5 py-3">${escapeHtml(date(p.last_admission))}</td></tr>`).join('') : emptyRow(4, 'No patient history yet.');
        app.innerHTML = `${heading('My Patients', 'All patients you have treated or are currently assigned to.')}
            ${card('Currently Assigned', table(['Patient', 'Room', 'Chief Complaint', 'Admitted', 'Actions'], active), 'mb-8')}
            ${card('Patient History', table(['Patient', 'Contact', 'Admissions', 'Last Seen'], past))}`;
    }

    function renderConsultations({ consultations, patients }) {
        const options = patients.map((p) => `<option value="${Number(p.patient_id)}">${escapeHtml(patientName(p))}</option>`).join('');
        const rows = consultations.length ? consultations.map((c) => `<tr>
            <td class="px-5 py-3 font-medium">${escapeHtml(patientName(c))}</td><td class="px-5 py-3">${escapeHtml(date(c.consultation_datetime, { dateStyle: 'medium', timeStyle: 'short' }))}</td>
            <td class="px-5 py-3">${escapeHtml(c.purpose || '—')}</td><td class="px-5 py-3">${escapeHtml(c.status)}</td>
            <td class="px-5 py-3 text-right">${String(c.status).toLowerCase() === 'scheduled' ? `${button('Complete', 'consult-status', `data-id="${Number(c.consultation_id)}" data-status="Completed"`, 'emerald')} ${button('Cancel', 'consult-status', `data-id="${Number(c.consultation_id)}" data-status="Cancelled"`, 'rose')}` : ''}</td>
        </tr>`).join('') : emptyRow(5, 'No consultations yet.');
        app.innerHTML = `${heading('Consultations', 'All your scheduled and completed consultations.')}${alertArea()}
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                ${card('Schedule Consultation', `<form id="consultation-form" class="space-y-4 p-6">
                    <label class="block text-sm">Patient<select name="patient_id" required class="mt-1 w-full rounded-lg border px-3 py-2"><option value="">Select patient</option>${options}</select></label>
                    <label class="block text-sm">Date & Time<input type="datetime-local" name="consultation_datetime" required value="${new Date(Date.now() - new Date().getTimezoneOffset() * 60000).toISOString().slice(0, 16)}" class="mt-1 w-full rounded-lg border px-3 py-2"></label>
                    <label class="block text-sm">Purpose<input name="purpose" required class="mt-1 w-full rounded-lg border px-3 py-2"></label>
                    <label class="block text-sm">Notes<textarea name="notes" rows="2" class="mt-1 w-full rounded-lg border px-3 py-2"></textarea></label>
                    <button class="w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white">Schedule</button>
                </form>`, 'lg:col-span-1')}
                ${card(`Consultation List (${consultations.length})`, table(['Patient', 'Date', 'Purpose', 'Status', 'Actions'], rows), 'lg:col-span-2')}
            </div>`;
    }

    function renderDiagnosis([options, admission]) {
        if (!admission) {
            app.innerHTML = `${heading('Record Diagnosis', 'Select an admission to record or view diagnoses.')}${card('Assigned Admissions', table(['Patient', 'Room', 'Chief Complaint', 'Admitted', 'Actions'], assignedRows(options.assigned)))}`;
            return;
        }
        const diagnoses = admission.diagnoses || [];
        const diagnosisOptions = options.diagnoses.map((d) => `<option value="${Number(d.diagnosis_id)}">${escapeHtml(d.icd_code ? `${d.icd_code} — ` : '')}${escapeHtml(d.diagnosis_name)}</option>`).join('');
        const diagnosisRows = diagnoses.length ? diagnoses.map((d) => `<article class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-4 last:border-0">
            <div><p class="font-semibold text-slate-900">${escapeHtml(d.diagnosis_name)} <span class="text-xs font-normal text-slate-500">${escapeHtml(d.icd_code || '')}</span></p>
            <p class="mt-1 text-sm text-slate-600">${escapeHtml(d.description || '')}</p><p class="mt-2 text-xs text-slate-500">${escapeHtml(d.diagnosis_type)} · Recorded ${escapeHtml(date(d.diagnosed_datetime, { dateStyle: 'medium', timeStyle: 'short' }))}</p></div>
            ${button('Remove', 'remove-diagnosis', `data-id="${Number(d.admission_diagnosis_id)}"`, 'rose')}</article>`).join('') : '<p class="p-10 text-center text-slate-400">No diagnoses recorded yet.</p>';
        app.innerHTML = `${heading('Patient Diagnosis', 'Admission chart and clinical diagnosis record.')}<p class="mb-4"><a class="text-sm text-blue-700" href="${href('doctor-diagnosis-record')}">← Back to admissions</a></p>${alertArea()}
            <div class="mb-6 rounded-xl border border-slate-200 bg-white p-6"><h2 class="text-xl font-bold">${escapeHtml(patientName(admission))}</h2><p class="text-sm text-slate-500">Admission #${Number(admission.admission_id)} · ${escapeHtml(admission.gender_name)} · Admitted ${escapeHtml(date(admission.admission_datetime, { dateStyle: 'medium', timeStyle: 'short' }))}</p><p class="mt-2 text-sm">${escapeHtml(admission.chief_complaint || '')}</p></div>
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                ${card('Add Diagnosis', `<form id="diagnosis-form" class="space-y-4 p-6"><input type="hidden" name="admission_id" value="${Number(admission.admission_id)}">
                    <label class="block text-sm">Diagnosis<select name="diagnosis_id" required class="mt-1 w-full rounded-lg border px-3 py-2"><option value="">Select diagnosis</option>${diagnosisOptions}</select></label>
                    <label class="block text-sm">Type<select name="diagnosis_type" class="mt-1 w-full rounded-lg border px-3 py-2"><option>Primary</option><option>Secondary</option><option>Differential</option><option>Admitting</option></select></label>
                    <button class="w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white">Add Diagnosis</button></form>`, 'lg:col-span-1')}
                ${card(`Recorded Diagnoses (${diagnoses.length})`, `<div>${diagnosisRows}</div>`, 'lg:col-span-2')}</div>`;
    }

    function renderServices([options, admission]) {
        if (!admission) {
            app.innerHTML = `${heading('Service Requests', 'Select an admission to request medications, labs, or procedures.')}${card('Assigned Admissions', table(['Patient', 'Room', 'Chief Complaint', 'Admitted', 'Actions'], assignedRows(options.assigned, 'doctor-service-requests')))}`;
            return;
        }
        const requests = admission.service_requests || [];
        const items = options.items.map((item) => `<option value="${Number(item.charge_item_id)}">${escapeHtml(item.category_name)} — ${escapeHtml(item.item_name)} (${Number(item.default_price).toFixed(2)}${item.unit_of_measure ? ` / ${escapeHtml(item.unit_of_measure)}` : ''})</option>`).join('');
        const departments = (options.departments || []).map((department) => `<option value="${Number(department.department_id)}">${escapeHtml(department.department_name)}</option>`).join('');
        const rows = requests.length ? requests.map((r) => `<tr><td class="px-5 py-3"><p class="font-medium">${escapeHtml(String(r.notes || '').startsWith('MED_RETURN:') ? 'Pharmacy stock return — ' + r.item_name : r.item_name)}</p><p class="text-xs text-slate-500">${escapeHtml(r.category_name)} · ${escapeHtml(r.item_code)}${String(r.notes || '').startsWith('MED_RETURN:') ? ` · ${escapeHtml(String(r.instructions || ''))}` : ''}</p></td>
            <td class="px-5 py-3">${Number(r.quantity)}</td><td class="px-5 py-3">${escapeHtml(date(r.request_datetime, { dateStyle: 'medium', timeStyle: 'short' }))}</td><td class="px-5 py-3">${escapeHtml(r.status)}</td></tr>`).join('') : emptyRow(4, 'No service requests yet.');
        app.innerHTML = `${heading('Service Requests', 'Order and manage services for this admission.')}<p class="mb-4"><a class="text-sm text-blue-700" href="${href('doctor-service-requests')}">← Back to admissions</a></p>${alertArea()}
            <div class="mb-6 rounded-xl border border-slate-200 bg-white p-6"><h2 class="text-xl font-bold">${escapeHtml(patientName(admission))}</h2><p class="text-sm text-slate-500">Admission #${Number(admission.admission_id)} · ${escapeHtml(admission.admission_status_name)}</p></div>
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                ${card('New Service Request', `<form id="request-form" class="space-y-4 p-6"><input type="hidden" name="admission_id" value="${Number(admission.admission_id)}">
                    <label class="block text-sm">Fulfillment Department<select name="department_id" required class="mt-1 w-full rounded-lg border px-3 py-2"><option value="">Select department</option>${departments}</select></label>
                    <div id="request-items" class="space-y-3"><div class="request-item-row rounded-lg border p-3">
                        <label class="block text-sm">Item / Service<select data-field="charge-item" required class="mt-1 w-full rounded-lg border px-3 py-2"><option value="">Select item</option>${items}</select></label>
                        <div class="mt-3 flex items-end gap-2"><label class="block flex-1 text-sm">Quantity<input data-field="quantity" type="number" value="1" min="1" required class="mt-1 w-full rounded-lg border px-3 py-2"></label>
                        <button type="button" data-action="remove-request-item" class="rounded-lg border px-3 py-2 text-xs" disabled>Remove</button></div>
                    </div></div>
                    <button type="button" data-action="add-request-item" class="w-full rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-sm text-blue-700">Add another item</button>
                    <button class="w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white">Submit Request</button></form>`, 'lg:col-span-1')}
                ${card('Pharmacy items at discharge', '<p class="p-6 text-sm text-slate-500">Nursing tracks intake status and remaining quantity. When confirming discharge readiness, the doctor decides whether remaining medicines or other Pharmacy items go home with the patient or are returned. Take-home items remain charged.</p>')}
                ${card(`Service Requests (${requests.length})`, table(['Item', 'Qty', 'Requested', 'Status'], rows), 'lg:col-span-3')}</div>`;
    }

    function renderDischarge(patients) {
        const rows = patients.length ? patients.map((p) => {
            const days = Math.max(0, Math.floor((Date.now() - new Date(p.admission_datetime.replace(' ', 'T')).getTime()) / 86400000));
            const ready = String(p.status_name || '').toLowerCase() === 'ready for discharge';
            return `<tr><td class="px-5 py-4"><p class="font-semibold">${escapeHtml(patientName(p))}</p><p class="text-xs text-slate-500">#${Number(p.admission_id)}</p></td><td class="px-5 py-4">${escapeHtml(p.room_number || '—')}</td>
                <td class="px-5 py-4">${escapeHtml(date(p.admission_datetime))}</td><td class="px-5 py-4">${days} day${days === 1 ? '' : 's'}</td><td class="px-5 py-4">${Number(p.completed_count)} completed</td>
                <td class="px-5 py-4 text-right">${ready ? '<span class="text-emerald-700">Marked Ready</span>' : button('Confirm Ready', 'confirm-discharge', `data-id="${Number(p.admission_id)}" data-name="${escapeHtml(patientName(p))}"`, 'emerald')}</td></tr>`;
        }).join('') : emptyRow(6, 'No patients ready for discharge. Pending and in-progress requests block discharge; cancelled requests do not.');
        app.innerHTML = `${heading('Ready for Discharge', 'Confirm readiness so nursing staff can complete discharge.')}${alertArea()}${card('Patients Awaiting Confirmation', table(['Patient', 'Room', 'Admitted', 'Length of Stay', 'Requests', 'Actions'], rows))}`;
    }

    app.addEventListener('submit', async (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;
        const operations = {
            'diagnosis-form': 'save-diagnosis',
            'request-form': 'create-service-request',
            'consultation-form': 'create-consultation',
        };
        const operation = operations[form.id];
        if (!operation) return;
        event.preventDefault();
        const values = Object.fromEntries(new FormData(form).entries());
        if (form.id === 'request-form') {
            values.items = Array.from(form.querySelectorAll('.request-item-row'), (row) => ({
                charge_item_id: row.querySelector('[data-field="charge-item"]').value,
                quantity: row.querySelector('[data-field="quantity"]').value,
            }));
        }
        try {
            const result = await request(operation, { method: 'POST', data: values });
            await load();
            showAlert(result?.message || 'Saved successfully.', true);
        } catch (error) {
            showAlert(error.message);
        }
    });

    app.addEventListener('click', async (event) => {
        const target = event.target.closest('[data-action]');
        if (!target) return;
        const { action, id, status, name } = target.dataset;
        if (action === 'refresh') return load();
        if (action === 'add-request-item' || action === 'remove-request-item') {
            const rows = app.querySelectorAll('.request-item-row');
            if (action === 'add-request-item') {
                const row = rows[0].cloneNode(true);
                row.querySelector('[data-field="charge-item"]').value = '';
                row.querySelector('[data-field="quantity"]').value = '1';
                row.querySelector('[data-action="remove-request-item"]').disabled = false;
                rows[0].parentElement.appendChild(row);
            } else if (rows.length > 1) {
                target.closest('.request-item-row').remove();
            }
            return;
        }
        if (['remove-diagnosis', 'consult-status'].includes(action)
            && !await window.confirmWithSweetAlert('Are you sure you want to continue?')) return;
        try {
            let operation;
            let data = { id: Number(id) };
            if (action === 'remove-diagnosis') operation = 'remove-diagnosis';
            if (action === 'consult-status') {
                operation = 'update-consultation';
                data.status = status;
            }
            if (action === 'confirm-discharge') {
                const notes = await window.promptWithSweetAlert(
                    `Confirm ${name || 'patient'} is ready for discharge. Optional clinical notes:`,
                    { title: 'Confirm discharge readiness', placeholder: 'Optional clinical notes' }
                );
                if (notes === null) return;
                operation = 'confirm-discharge';
                data = { admission_id: Number(id), notes };
            }
            const result = await request(operation, { method: 'POST', data });
            await load();
            showAlert(result?.message || 'Updated successfully.', true);
        } catch (error) {
            showAlert(error.message);
        }
    });

    load();
    }
});
