document.addEventListener('spa:page-loaded', event => {
    if (!event.detail.page.startsWith('department')) return;

    const root = document.querySelector('[data-department-view]');
    if (!root) return;

    const baseUrl = document.body.dataset.baseUrl || '';
    const apiUrl = `${baseUrl}/api/department.php`;
    const view = root.dataset.departmentView;

    const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, char => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    })[char]);

    async function request(operation, { method = 'GET', params = {}, body } = {}) {
        const url = new URL(apiUrl, location.origin);
        url.searchParams.set('operation', operation);
        Object.entries(params).forEach(([key, value]) => {
            if (value !== undefined && value !== null && value !== '') {
                url.searchParams.set(key, value);
            }
        });

        const options = {
            method,
            credentials: 'same-origin',
            headers: { Accept: 'application/json' }
        };

        if (body !== undefined) {
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(body);
        }

        const response = await fetch(url, options);
        const payload = await response.json();

        if (!response.ok || payload.success === false) {
            throw new Error(payload.message || 'Request failed.');
        }

        return payload.data || payload;
    }

    function showNotice(message, success = false) {
        const el = document.getElementById('departmentAlert');
        if (!el) return;
        el.className = `mb-5 rounded-lg px-4 py-3 text-sm border ${success
            ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
            : 'border-rose-200 bg-rose-50 text-rose-800'}`;
        el.textContent = message;
        el.classList.remove('hidden');
    }

    async function loadDashboard() {
        const target = document.getElementById('departmentDashboard');
        if (!target) return;

        try {
            const stats = await request('dashboard');
            target.innerHTML = `
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6 max-w-xl">
                    <div class="bg-white rounded-xl border border-slate-200 p-5">
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Pending Requests</p>
                        <p class="mt-2 text-2xl font-bold text-amber-600">${Number(stats.pending_requests || 0)}</p>
                    </div>
                    <div class="bg-white rounded-xl border border-slate-200 p-5">
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Completed Requests</p>
                        <p class="mt-2 text-2xl font-bold text-emerald-600">${Number(stats.completed_requests || 0)}</p>
                    </div>
                </div>
            `;
        } catch (error) {
            target.innerHTML = `<div class="rounded-xl border border-rose-200 bg-rose-50 p-6 text-rose-800">${escapeHtml(error.message)}</div>`;
        }
    }

    async function loadQueue() {
        const target = document.getElementById('departmentQueue');
        if (!target) return;

        try {
            const queue = await request('queue');

            if (!queue.length) {
                target.innerHTML = '<div class="rounded-xl border border-slate-200 bg-white p-10 text-center text-slate-500">No doctor orders or nursing requisitions assigned to your department.</div>';
                return;
            }

            target.innerHTML = `
                <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-slate-600">
                            <tr>
                                <th class="px-6 py-3 font-medium">Patient</th>
                                <th class="px-6 py-3 font-medium">Request</th>
                                <th class="px-6 py-3 font-medium">Priority</th>
                                <th class="px-6 py-3 font-medium">Status</th>
                                <th class="px-6 py-3 font-medium">Items</th>
                                <th class="px-6 py-3 text-right font-medium">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            ${queue.map(r => `
                                <tr class="hover:bg-slate-50">
                                    <td class="px-6 py-4">
                                        <p class="font-semibold text-slate-900">${escapeHtml((r.first_name || '') + ' ' + (r.last_name || ''))}</p>
                                        <p class="text-xs text-slate-500">Admission #${Number(r.admission_id || 0)}</p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="font-medium text-slate-800">#${Number(r.request_id || 0)}</p>
                                        ${r.request_source && r.request_source !== 'DOCTOR' ? `<p class="text-xs font-medium text-blue-700">${r.request_source === 'NURSE_TRANSCRIPTION' ? 'Transcribed doctor order' : 'Ward requisition'}</p>` : ''}
                                        <p class="text-xs text-slate-500">${escapeHtml(r.chief_complaint || '')}</p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
                                            ${escapeHtml(r.priority || 'Routine')}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium ${
                                            ['Pending', 'In Progress'].includes(r.status)
                                                ? 'bg-amber-100 text-amber-700'
                                                : 'bg-emerald-100 text-emerald-700'
                                        }">
                                            ${escapeHtml(r.status || 'Pending')}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-slate-600">
                                        <span class="font-medium">${Number(r.item_count || 0)}</span> item(s)
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <a href="${baseUrl}/index.php?page=department-fulfillment&request_id=${Number(r.request_id || 0)}"
                                           class="inline-flex items-center rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-medium text-blue-700 hover:bg-blue-100">
                                            Open
                                        </a>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
        } catch (error) {
            target.innerHTML = `<div class="rounded-xl border border-rose-200 bg-rose-50 p-6 text-rose-800">${escapeHtml(error.message)}</div>`;
        }
    }

    if (view === 'dashboard') loadDashboard();
    else if (view === 'queue') loadQueue();
});