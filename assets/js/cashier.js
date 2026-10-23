const baseUrl = document.body.dataset.baseUrl || '';
const endpoint = `${baseUrl}/api/cashier.php`;
let root = document.querySelector('[data-cashier-page]');
let page = root?.dataset.cashierPage;

const asArray = value => Array.isArray(value) ? value : [];
const firstArray = (data, keys) => {
    for (const key of keys) if (Array.isArray(data?.[key])) return data[key];
    return [];
};
const html = value => String(value ?? '').replace(/[&<>"']/g, char => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
})[char]);
const number = value => Number(value || 0);
const money = value => `₱${number(value).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
const personName = row => [row?.first_name, row?.last_name].filter(Boolean).join(' ') || row?.patient_name || '—';
const statementId = row => row?.statement_id ?? row?.id ?? '';
const statusName = row => row?.status_name ?? row?.status ?? '—';
const dateLabel = value => {
    if (!value) return '—';
    const date = new Date(String(value).replace(' ', 'T'));
    return Number.isNaN(date.getTime()) ? String(value) : date.toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
};
const dateTimeLabel = value => {
    if (!value) return '—';
    const date = new Date(String(value).replace(' ', 'T'));
    return Number.isNaN(date.getTime()) ? String(value) : date.toLocaleString('en-PH', { year: 'numeric', month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' });
};

function unwrap(response) {
    const envelope = response;
    if (envelope?.success === false) throw new Error(envelope.message || 'The request could not be completed.');
    if (Array.isArray(envelope?.data)) return { ...envelope, items: envelope.data, data: envelope.data };
    return envelope?.data && typeof envelope.data === 'object'
        ? { ...envelope, ...envelope.data }
        : envelope;
}

async function request(operation, params = {}, method = 'GET') {
    let url = `${endpoint}?operation=${encodeURIComponent(operation)}`;
    const options = { method, headers: { Accept: 'application/json' } };
    if (method === 'GET') {
        const query = new URLSearchParams(params);
        if (query.toString()) url += `&${query}`;
    } else {
        const body = new FormData();
        body.append('operation', operation);
        body.append('json', JSON.stringify(params));
        options.body = body;
    }
    const response = await fetch(url, options);
    let data;
    try {
        data = await response.json();
    } catch {
        throw new Error('The cashier service returned an invalid response.');
    }
    if (!response.ok) throw new Error(data?.message || `Request failed (${response.status}).`);
    return unwrap(data);
}

function alertMessage(message, type = 'error') {
    const alert = root?.querySelector('[data-alert]');
    if (!alert) return;
    alert.className = `mb-5 rounded-lg px-4 py-3 text-sm border ${type === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-rose-200 bg-rose-50 text-rose-800'}`;
    alert.textContent = message;
    alert.classList.remove('hidden');
}

function card(label, value, color = 'text-slate-900') {
    return `<div class="rounded-xl border border-slate-200 bg-white p-5"><p class="text-xs font-medium uppercase tracking-wide text-slate-500">${html(label)}</p><p class="mt-2 text-2xl font-bold ${color}">${html(value)}</p></div>`;
}

function modal(id, visible) {
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.toggle('hidden', !visible);
    el.classList.toggle('flex', visible);
}

function statusPill(row) {
    const color = /^#[0-9a-f]{3,8}$/i.test(row?.color_code || '') ? row.color_code : '#64748b';
    return `<span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-medium" style="background:${html(color)}1a;color:${html(color)};border-color:${html(color)}66">${html(statusName(row))}</span>`;
}

function getStatements(data) {
    return firstArray(data, ['statements', 'billing_statements', 'items', 'results']);
}

function getStatement(data) {
    return data?.statement || data?.details || data?.item || data;
}

async function initialize() {
    if (!root) return;
    try {
        if (page === 'dashboard') await loadDashboard();
        else if (page === 'statements') await loadStatementsPage();
        else if (page === 'unpaid') await loadCounter();
        else if (page === 'payments') await loadPayments();
        else if (page === 'reports') await loadReports();
        else if (page === 'receipt') await loadReceipt();
    } catch (error) {
        alertMessage(error.message);
    }
}

async function navigateCashier(url, push = true) {
    try {
        const response = await fetch(url.href, { credentials: 'same-origin', headers: { 'X-Requested-With': 'fetch' } });
        if (!response.ok) throw new Error(`Could not open page (${response.status}).`);
        const documentText = await response.text();
        const nextDocument = new DOMParser().parseFromString(documentText, 'text/html');
        const nextMain = nextDocument.querySelector('main');
        const currentMain = document.querySelector('main');
        if (!nextMain || !currentMain) throw new Error('The requested page could not be loaded.');
        currentMain.innerHTML = nextMain.innerHTML;
        root = currentMain.querySelector('[data-cashier-page]');
        page = root?.dataset.cashierPage;
        if (!root) throw new Error('The requested page is not available.');
        const destination = url.searchParams.get('page') || 'cashier';
        const title = nextDocument.querySelector('#page-title')?.textContent.trim();
        document.body.dataset.page = destination;
        if (title) {
            document.title = `${title} · Billing Hospital`;
            const pageTitle = document.getElementById('page-title');
            if (pageTitle) pageTitle.textContent = title;
        } else {
            document.title = nextDocument.title || document.title;
        }
        document.querySelectorAll("nav a[href*='?page=']").forEach(link => {
            const active = new URL(link.href, location.href).searchParams.get('page') === destination;
            link.setAttribute('aria-current', active ? 'page' : 'false');
            link.classList.toggle('bg-blue-50', active);
            link.classList.toggle('text-blue-700', active);
            link.classList.toggle('text-slate-600', !active);
            link.classList.toggle('hover:bg-slate-50', !active);
            link.classList.toggle('hover:text-slate-900', !active);
            const icon = link.querySelector('svg');
            if (icon) {
                icon.classList.toggle('text-blue-600', active);
                icon.classList.toggle('text-slate-400', !active);
            }
        });
        if (push) history.pushState({}, '', url.href);
        await initialize();
        window.scrollTo(0, 0);
    } catch (error) {
        alertMessage(error.message);
    }
}

document.addEventListener('click', event => {
    const anchor = event.target.closest('a[href]');
    if (!anchor || anchor.target === '_blank' || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    const url = new URL(anchor.href, location.href);
    if (url.origin !== location.origin || !url.pathname.endsWith('/index.php')) return;
    const destination = url.searchParams.get('page') || 'cashier';
    if (destination !== 'cashier' && !destination.startsWith('cashier-')) return;
    event.preventDefault();
    navigateCashier(url);
});

window.addEventListener('popstate', () => {
    const url = new URL(location.href);
    const destination = url.searchParams.get('page') || 'cashier';
    if (destination === 'cashier' || destination.startsWith('cashier-')) navigateCashier(url, false);
});

async function loadDashboard() {
    const data = await request('dashboard');
    const stats = data.stats || data.summary || data;
    const recent = firstArray(data, ['recent_statements', 'recent', 'statements']);
    const admissions = firstArray(data, ['recent_admissions', 'admissions']);
    const host = document.getElementById('cashierDashboard');
    host.innerHTML = `
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
            ${card('Statements', stats.statement_count ?? stats.total_statements ?? 0)}
            ${card('Total billed', money(stats.total_billed ?? (number(stats.total_collected) + number(stats.total_outstanding))), 'text-slate-900')}
            ${card('Collected', money(stats.total_paid ?? stats.total_collected ?? stats.amount_paid), 'text-emerald-600')}
            ${card('Outstanding', money(stats.total_balance ?? stats.total_outstanding ?? stats.balance_amount), 'text-rose-600')}
        </div>
        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
            <section class="rounded-xl border border-slate-200 bg-white p-6 lg:col-span-2">
                <div class="mb-4 flex items-center justify-between"><div><h2 class="font-semibold text-slate-900">Recent statements</h2><p class="text-xs text-slate-500">Latest billing activity</p></div><a href="${baseUrl}/index.php?page=cashier-statements" class="text-sm font-medium text-blue-600">View all →</a></div>
                <div class="overflow-x-auto"><table class="min-w-full text-sm"><thead class="text-left text-xs uppercase text-slate-500"><tr><th class="py-2">Statement</th><th class="py-2">Patient</th><th class="py-2 text-right">Balance</th><th class="py-2">Status</th></tr></thead><tbody class="divide-y">${recent.length ? recent.slice(0, 10).map(row => `<tr><td class="py-3 font-medium">#${html(statementId(row))}</td><td class="py-3">${html(personName(row))}</td><td class="py-3 text-right">${money(row.balance_amount)}</td><td class="py-3">${statusPill(row)}</td></tr>`).join('') : '<tr><td colspan="4" class="py-8 text-center text-slate-400">No statements yet.</td></tr>'}</tbody></table></div>
            </section>
            <section class="rounded-xl border border-slate-200 bg-white p-6"><h2 class="font-semibold text-slate-900">Recent admissions</h2><p class="mb-3 text-xs text-slate-500">Open a patient's running statement</p><div class="space-y-3">${admissions.length ? admissions.slice(0, 8).map(row => `<div class="flex items-center justify-between gap-3 border-b border-slate-100 pb-3"><div><p class="text-sm font-medium">${html(personName(row))}</p><p class="text-xs text-slate-500">Admission #${html(row.admission_id)}</p></div><a href="${baseUrl}/index.php?page=cashier-receipt&id=${encodeURIComponent(row.statement_id)}" class="text-xs font-semibold text-blue-600">Open statement</a></div>`).join('') : '<p class="py-6 text-center text-sm text-slate-400">No admissions yet.</p>'}</div></section>
        </div>`;
}

let statementRows = [];
let options = {};
let activeStatement = null;
let statementFilterRows = [];

async function loadStatementsPage() {
    const [listData, optionData] = await Promise.all([request('list'), request('options')]);
    statementRows = getStatements(listData);
    options = optionData.options || optionData;
    renderStatementList();
    fillStatementOptions();
    if (!root.dataset.statementBindings) {
        bindStatementPage();
        root.dataset.statementBindings = 'true';
    }
}

function renderStatementList() {
    const body = document.getElementById('statementsBody');
    const totalBilled = statementRows.reduce((sum, row) => sum + number(row.total_amount), 0);
    const totalPaid = statementRows.reduce((sum, row) => sum + number(row.amount_paid), 0);
    const totalBalance = statementRows.reduce((sum, row) => sum + number(row.balance_amount), 0);
    document.getElementById('statementSummary').innerHTML = [
        card('Statements', statementRows.length),
        card('Total billed', money(totalBilled)),
        card('Total paid', money(totalPaid), 'text-emerald-600'),
        card('Outstanding', money(totalBalance), 'text-rose-600')
    ].join('');
    body.innerHTML = statementRows.map(row => {
        const id = statementId(row);
        const search = `${personName(row)} #${id} ${statusName(row)}`.toLowerCase();
        return `<tr class="statement-row hover:bg-slate-50" data-search="${html(search)}" data-status="${html(statusName(row))}">
            <td class="px-6 py-4"><p class="font-semibold">#${html(id)}</p><p class="text-xs text-slate-500">${html(dateLabel(row.statement_date))}</p></td>
            <td class="px-6 py-4"><p class="font-medium">${html(personName(row))}</p><p class="text-xs text-slate-500">${html(row.contact_number || row.email || '')}</p></td>
            <td class="px-6 py-4 text-right">${money(row.total_amount)}</td><td class="px-6 py-4 text-right text-emerald-700">${money(row.amount_paid)}</td><td class="px-6 py-4 text-right font-semibold text-rose-700">${money(row.balance_amount)}</td>
            <td class="px-6 py-4">${statusPill(row)}</td><td class="px-6 py-4 text-right whitespace-nowrap"><button type="button" data-manage="${html(id)}" class="rounded-lg border border-blue-200 bg-blue-50 px-2.5 py-1.5 text-xs font-medium text-blue-700">Manage</button> <a target="_blank" href="${baseUrl}/index.php?page=cashier-receipt&id=${encodeURIComponent(id)}" class="rounded-lg border px-2.5 py-1.5 text-xs">Print</a></td>
        </tr>`;
    }).join('');
    document.getElementById('statementsEmpty').classList.toggle('hidden', statementRows.length > 0);
    document.getElementById('filterStatus').innerHTML = '<option value="">All statuses</option>' + [...new Set(statementRows.map(statusName))].filter(name => name !== '—').map(name => `<option value="${html(name)}">${html(name)}</option>`).join('');
    applyStatementFilters();
}

function fillStatementOptions() {
    const paymentTypes = firstArray(options, ['payment_types', 'paymentTypes']);
    const paymentSelect = document.getElementById('payment_type_id');
    paymentSelect.innerHTML = '<option value="">Select type</option>' + paymentTypes.map(row => `<option value="${html(row.payment_type_id ?? row.id)}">${html(row.type_name ?? row.name)}</option>`).join('');
}

function applyStatementFilters() {
    const query = (document.getElementById('filterSearch')?.value || '').trim().toLowerCase();
    const status = document.getElementById('filterStatus')?.value || '';
    const rows = [...document.querySelectorAll('.statement-row')];
    let count = 0;
    rows.forEach(row => {
        const match = (!query || row.dataset.search.includes(query)) && (!status || row.dataset.status === status);
        row.classList.toggle('hidden', !match);
        if (match) count++;
    });
    const filtered = Boolean(query || status);
    document.getElementById('filterSummary').classList.toggle('hidden', !filtered);
    document.getElementById('filterClear').classList.toggle('hidden', !filtered);
    document.getElementById('filteredCount').textContent = count;
    document.getElementById('totalCount').textContent = rows.length;
    document.getElementById('statementsEmpty').classList.toggle('hidden', count > 0);
}

function bindStatementPage() {
    document.getElementById('filterSearch').addEventListener('input', applyStatementFilters);
    document.getElementById('filterStatus').addEventListener('change', applyStatementFilters);
    document.getElementById('filterClear').addEventListener('click', () => {
        document.getElementById('filterSearch').value = '';
        document.getElementById('filterStatus').value = '';
        applyStatementFilters();
    });
    root.addEventListener('click', async event => {
        const close = event.target.closest('[data-close-modal]');
        if (close) modal(close.dataset.closeModal, false);
        if (['manageModal', 'paymentModal'].includes(event.target.id)) modal(event.target.id, false);
        const manage = event.target.closest('[data-manage]');
        if (manage) await openStatement(manage.dataset.manage);
        const tab = event.target.closest('[data-detail-tab]');
        if (tab) {
            root.querySelectorAll('[data-detail-tab]').forEach(button => {
                button.classList.toggle('border-blue-600', button === tab);
                button.classList.toggle('text-blue-700', button === tab);
                button.classList.toggle('border-transparent', button !== tab);
                button.classList.toggle('text-slate-600', button !== tab);
            });
            root.querySelectorAll('[data-detail-panel]').forEach(panel => panel.classList.toggle('hidden', panel.dataset.detailPanel !== tab.dataset.detailTab));
        }
        const removeCharge = event.target.closest('[data-remove-charge]');
        if (removeCharge && await window.confirmWithSweetAlert('Remove this charge from the statement?')) await mutate('remove-charge', { statement_id: statementId(activeStatement), charge_id: removeCharge.dataset.removeCharge }, 'Charge removed.', () => openStatement(statementId(activeStatement)));
        const removePayment = event.target.closest('[data-remove-payment]');
        if (removePayment && await window.confirmWithSweetAlert('Remove this payment?')) await mutate('remove-payment', { statement_id: statementId(activeStatement), payment_id: removePayment.dataset.removePayment }, 'Payment removed.', () => openStatement(statementId(activeStatement)));
    });
    document.getElementById('syncChargesBtn').addEventListener('click', () => mutate('sync-charges', { statement_id: statementId(activeStatement) }, 'Charges synchronized.', () => openStatement(statementId(activeStatement))));
    document.getElementById('addPaymentBtn').addEventListener('click', () => {
        document.getElementById('paymentCurrentBalance').textContent = money(activeStatement?.balance_amount);
        document.getElementById('payment_amount').value = number(activeStatement?.balance_amount).toFixed(2);
        modal('paymentModal', true);
    });
    document.getElementById('savePaymentBtn').addEventListener('click', async () => {
        const typeId = document.getElementById('payment_type_id').value;
        const amount = number(document.getElementById('payment_amount').value);
        if (!typeId || amount <= 0) return alertMessage('Select a payment type and enter a valid amount.');
        await mutate('add-payment', {
            statement_id: statementId(activeStatement),
            payment_type_id: typeId,
            amount,
            notes: document.getElementById('payment_notes').value
        }, 'Payment recorded.', async () => { modal('paymentModal', false); await openStatement(statementId(activeStatement)); });
    });
}

async function openStatement(id) {
    try {
        const data = await request('details', { statement_id: id });
        activeStatement = getStatement(data);
        renderStatementDetails(activeStatement);
        document.getElementById('manageTitle').textContent = `Statement #${id}`;
        document.getElementById('manageSubtitle').textContent = `${personName(activeStatement)} · Admission #${activeStatement.admission_id ?? '—'}`;
        document.getElementById('statementPrintLink').href = `${baseUrl}/index.php?page=cashier-receipt&id=${encodeURIComponent(id)}`;
        modal('manageModal', true);
    } catch (error) {
        alertMessage(error.message);
    }
}

function renderStatementDetails(statement) {
    const totals = [
        ['Subtotal', statement.subtotal_amount], ['Tax', statement.tax_amount], ['Total', statement.total_amount],
        ['Paid', statement.amount_paid], ['Balance', statement.balance_amount]
    ];
    document.getElementById('statementTotals').innerHTML = totals.map(([label, value]) => `<div class="rounded-lg border bg-slate-50 p-3"><p class="text-xs text-slate-500">${label}</p><p class="font-bold">${money(value)}</p></div>`).join('');
    const charges = firstArray(statement, ['charges', 'statement_charges']);
    document.getElementById('chargesBody').innerHTML = charges.map(row => `<tr><td class="p-3 font-medium">${html(row.item_name)}</td><td class="p-3 text-slate-500">${html(row.category_name)}</td><td class="p-3 text-center">${html(row.quantity)}</td><td class="p-3 text-right">${money(row.actual_price)}</td><td class="p-3 text-right">${money(row.line_total)}</td><td class="p-3 text-right"><button data-remove-charge="${html(row.charge_id)}" type="button" class="text-rose-600">Remove</button></td></tr>`).join('');
    document.getElementById('noCharges').classList.toggle('hidden', charges.length > 0);
    const awaitingReceipt = firstArray(statement, ['awaiting_receipt']);
    const awaitingReceiptPanel = document.getElementById('awaitingReceiptPanel');
    document.getElementById('awaitingReceiptBody').innerHTML = awaitingReceipt.map(row => `<tr><td class="py-2 pr-4 font-medium text-amber-950">${html(row.item_name)}${row.item_code ? ` <span class="text-xs text-amber-800">(${html(row.item_code)})</span>` : ''}</td><td class="py-2 pr-4 text-amber-900">${html(row.category_name)}${row.department_name ? ` · ${html(row.department_name)}` : ''}</td><td class="py-2 pr-4 text-right font-semibold text-amber-900">${html(row.quantity_waiting)} ${html(row.unit_of_measure || '')}</td><td class="py-2 text-right text-amber-900">${money(row.unit_price)}</td></tr>`).join('');
    awaitingReceiptPanel.classList.toggle('hidden', awaitingReceipt.length === 0);
    const payments = firstArray(statement, ['payments', 'statement_payments']);
    document.getElementById('paymentsBody').innerHTML = payments.map(row => `<tr><td class="p-3">${html(row.type_name ?? row.payment_type_name)}</td><td class="p-3 font-mono text-xs">${html(row.transaction_reference || '—')}</td><td class="p-3">${html(dateTimeLabel(row.payment_datetime))}</td><td class="p-3 text-right font-medium text-emerald-700">${money(row.amount)}</td><td class="p-3 text-right"><button data-remove-payment="${html(row.payment_id)}" type="button" class="text-rose-600">Remove</button></td></tr>`).join('');
    document.getElementById('noPayments').classList.toggle('hidden', payments.length > 0);
    const rooms = firstArray(statement, ['room_history', 'rooms']);
    document.getElementById('roomsBody').innerHTML = rooms.map(row => `<tr><td class="p-3 font-medium">${html(row.room_number)}</td><td class="p-3">${html(row.room_type_name)}</td><td class="p-3">${html(dateTimeLabel(row.start_datetime))}</td><td class="p-3">${html(row.end_datetime ? dateTimeLabel(row.end_datetime) : 'Present')}</td><td class="p-3 text-right">${money(row.daily_rate_at_assignment)}</td></tr>`).join('');
    document.getElementById('noRooms').classList.toggle('hidden', rooms.length > 0);
}

async function mutate(operation, values, success, after) {
    try {
        const result = await request(operation, values, 'POST');
        alertMessage(result.message || success, 'success');
        if (after) await after();
        if (page === 'statements') {
            statementRows = getStatements(await request('list'));
            renderStatementList();
        }
        return result;
    } catch (error) {
        alertMessage(error.message);
        return null;
    }
}

async function loadCounter() {
    const [unpaidData, summaryData, optionData] = await Promise.all([request('unpaid'), request('dashboard'), request('options')]);
    const rows = firstArray(unpaidData, ['statements', 'unpaid', 'items', 'results']);
    const summary = summaryData.stats || summaryData.summary || summaryData;
    const types = firstArray(optionData.options || optionData, ['payment_types', 'paymentTypes']);
    document.getElementById('counterSummary').innerHTML = [
        card('Unpaid statements', rows.length),
        card('Total outstanding', money(summary.total_unpaid ?? summary.total_outstanding ?? rows.reduce((total, row) => total + number(row.balance_amount), 0)), 'text-rose-600'),
        card('Collected today', money(summary.today_collected ?? summary.collected_today ?? summary.today_amount), 'text-emerald-600')
    ].join('');
    document.getElementById('col_payment_type_id').innerHTML = '<option value="">Select type</option>' + types.map(row => `<option value="${html(row.payment_type_id ?? row.id)}">${html(row.type_name ?? row.name)}</option>`).join('');
    const statusNames = [...new Set(rows.map(statusName))].filter(name => name !== '—');
    document.getElementById('counterStatus').innerHTML = '<option value="">All unpaid</option>' + statusNames.map(name => `<option>${html(name)}</option>`).join('');
    const body = document.getElementById('unpaidBody');
    body.innerHTML = rows.map(row => {
        const id = statementId(row);
        const search = `${personName(row)} ${row.contact_number || ''} ${row.email || ''} #${id}`.toLowerCase();
        return `<tr class="unpaid-row hover:bg-slate-50" data-search="${html(search)}" data-status="${html(statusName(row))}"><td class="p-4 font-semibold">#${html(id)}<p class="text-xs font-normal text-slate-500">${html(dateLabel(row.statement_date))}</p></td><td class="p-4">${html(personName(row))}<p class="text-xs text-slate-500">${html(row.contact_number || row.email || '')}</p></td><td class="p-4 text-right">${money(row.total_amount)}</td><td class="p-4 text-right text-emerald-700">${money(row.amount_paid)}</td><td class="p-4 text-right font-semibold text-rose-700">${money(row.balance_amount)}</td><td class="p-4">${statusPill(row)}</td><td class="p-4 text-right"><button type="button" data-collect="${html(id)}" class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white">Collect</button></td></tr>`;
    }).join('');
    document.getElementById('unpaidEmpty').classList.toggle('hidden', rows.length > 0);
    bindCounter(rows);
}

function bindCounter() {
    const filter = () => {
        const query = document.getElementById('counterSearch').value.trim().toLowerCase();
        const status = document.getElementById('counterStatus').value;
        const rows = [...document.querySelectorAll('.unpaid-row')];
        let visible = 0;
        rows.forEach(row => {
            const match = (!query || row.dataset.search.includes(query)) && (!status || row.dataset.status === status);
            row.classList.toggle('hidden', !match);
            if (match) visible++;
        });
        const filtered = Boolean(query || status);
        document.getElementById('counterFilterSummary').classList.toggle('hidden', !filtered);
        document.getElementById('counterClear').classList.toggle('hidden', !filtered);
        document.getElementById('counterFilteredCount').textContent = visible;
        document.getElementById('counterTotalCount').textContent = rows.length;
        document.getElementById('unpaidEmpty').classList.toggle('hidden', visible > 0);
    };
    document.getElementById('counterSearch').oninput = filter;
    document.getElementById('counterStatus').onchange = filter;
    document.getElementById('counterClear').onclick = () => {
        document.getElementById('counterSearch').value = '';
        document.getElementById('counterStatus').value = '';
        filter();
    };
    root.onclick = async event => {
        const close = event.target.closest('[data-close-modal]');
        if (close) modal(close.dataset.closeModal, false);
        const collect = event.target.closest('[data-collect]');
        if (collect) await openCollect(collect.dataset.collect);
    };
    document.getElementById('confirmCollectBtn').onclick = async () => {
        const type = document.getElementById('col_payment_type_id').value;
        const amount = number(document.getElementById('col_amount').value);
        if (!type || amount <= 0) return alertMessage('Select a payment type and enter a valid amount.');
        const id = document.getElementById('collectModal').dataset.statementId;
        await mutate('add-payment', { statement_id: id, payment_type_id: type, amount, notes: document.getElementById('col_notes').value }, 'Payment recorded.', async () => {
            modal('collectModal', false);
            await loadCounter();
        });
    };
}

async function openCollect(id) {
    try {
        const data = await request('summary', { statement_id: id });
        const row = getStatement(data);
        document.getElementById('collectModal').dataset.statementId = id;
        document.getElementById('collectSubtitle').textContent = `Statement #${id}`;
        document.getElementById('colPatient').textContent = personName(row);
        document.getElementById('colTotal').textContent = money(row.total_amount);
        document.getElementById('colPaid').textContent = money(row.amount_paid);
        document.getElementById('colBalance').textContent = money(row.balance_amount);
        document.getElementById('col_amount').max = number(row.balance_amount);
        document.getElementById('col_amount').value = number(row.balance_amount).toFixed(2);
        modal('collectModal', true);
    } catch (error) {
        alertMessage(error.message);
    }
}

async function loadPayments() {
    const listData = await request('list');
    const statements = getStatements(listData);
    const details = await Promise.all(statements.map(async row => {
        try {
            return getStatement(await request('details', { statement_id: statementId(row) }));
        } catch {
            return null;
        }
    }));
    const payments = details.filter(Boolean).flatMap(row => firstArray(row, ['payments', 'statement_payments']));
    const total = payments.reduce((sum, row) => sum + number(row.amount), 0);
    const today = new Date().toDateString();
    const todayTotal = payments.filter(row => new Date(String(row.payment_datetime || '').replace(' ', 'T')).toDateString() === today).reduce((sum, row) => sum + number(row.amount), 0);
    document.getElementById('paymentsSummary').innerHTML = [
        card('Total payments', payments.length),
        card('Total collected', money(total), 'text-emerald-600'),
        card('Today', money(todayTotal), 'text-blue-600')
    ].join('');
    const body = document.getElementById('paymentsTableBody');
    const render = query => {
        const filtered = payments.filter(row => `${personName(row)} ${row.transaction_reference || ''} #${row.statement_id || ''} ${row.type_name || ''}`.toLowerCase().includes(query));
        body.innerHTML = filtered.map(row => `<tr class="hover:bg-slate-50"><td class="p-4 font-medium">#${html(row.payment_id)}<p class="text-xs text-slate-500">Statement #${html(row.statement_id)}</p></td><td class="p-4">${html(personName(row))}<p class="text-xs text-slate-500">Admission #${html(row.admission_id || '—')}</p></td><td class="p-4">${html(row.type_name || row.payment_type_name || '—')}</td><td class="p-4 text-xs text-slate-600">${html(row.transaction_reference || '—')}</td><td class="p-4 text-xs text-slate-600">${html(dateTimeLabel(row.payment_datetime))}</td><td class="p-4 text-right font-semibold text-emerald-700">${money(row.amount)}</td></tr>`).join('');
        document.getElementById('paymentsEmpty').classList.toggle('hidden', filtered.length > 0);
    };
    render('');
    document.getElementById('paymentSearch').addEventListener('input', event => render(event.target.value.trim().toLowerCase()));
}

async function loadReports() {
    const data = await request('reports');
    const reports = data.reports || data;
    const byType = firstArray(reports, ['by_type', 'by_payment_type', 'collections_by_type']);
    const daily = firstArray(reports, ['daily', 'daily_collections']);
    const top = firstArray(reports, ['top_balances', 'outstanding', 'top_outstanding']);
    const grandTotal = byType.reduce((sum, row) => sum + number(row.total), 0);
    const max = Math.max(0, ...daily.map(row => number(row.total)));
    document.getElementById('cashierReports').innerHTML = `
        <div class="mb-8 grid grid-cols-1 gap-6 lg:grid-cols-3">
            <section class="overflow-hidden rounded-xl border bg-white lg:col-span-1"><h2 class="border-b px-6 py-4 font-semibold">Collections by Payment Type</h2><div class="divide-y">${byType.length ? byType.map(row => `<div class="px-6 py-4"><div class="mb-1 flex justify-between"><span>${html(row.type_name)}</span><strong>${money(row.total)}</strong></div><div class="flex justify-between text-xs text-slate-500"><span>${html(row.count)} payment(s)</span><span>${grandTotal ? (number(row.total) / grandTotal * 100).toFixed(1) : '0.0'}%</span></div></div>`).join('') : '<p class="p-8 text-center text-sm text-slate-400">No payments yet.</p>'}</div></section>
            <section class="overflow-hidden rounded-xl border bg-white lg:col-span-2"><h2 class="border-b px-6 py-4 font-semibold">Daily Collections (Last 7 Days)</h2><div class="space-y-4 p-6">${daily.length ? daily.map(row => `<div><div class="mb-1 flex justify-between gap-3 text-xs"><span>${html(dateLabel(row.day))}</span><span>${html(row.count)} payments</span><strong>${money(row.total)}</strong></div><div class="h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-emerald-500" style="width:${max ? Math.min(100, number(row.total) / max * 100) : 0}%"></div></div></div>`).join('') : '<p class="py-8 text-center text-sm text-slate-400">No collections in the last 7 days.</p>'}</div></section>
        </div>
        <section class="overflow-hidden rounded-xl border bg-white"><div class="border-b px-6 py-4"><h2 class="font-semibold">Top Outstanding Balances</h2><p class="text-xs text-slate-500">Patients with the highest unpaid amounts</p></div><div class="overflow-x-auto"><table class="min-w-full text-sm"><thead class="bg-slate-50"><tr><th class="p-4 text-left">Statement</th><th class="p-4 text-left">Patient</th><th class="p-4 text-right">Total</th><th class="p-4 text-right">Paid</th><th class="p-4 text-right">Balance</th><th class="p-4 text-left">Status</th></tr></thead><tbody class="divide-y">${top.length ? top.map(row => `<tr><td class="p-4 font-medium">#${html(statementId(row))}</td><td class="p-4">${html(personName(row))}</td><td class="p-4 text-right">${money(row.total_amount)}</td><td class="p-4 text-right text-emerald-700">${money(row.amount_paid)}</td><td class="p-4 text-right font-semibold text-rose-700">${money(row.balance_amount)}</td><td class="p-4">${statusPill(row)}</td></tr>`).join('') : '<tr><td colspan="6" class="p-8 text-center text-slate-400">No outstanding balances.</td></tr>'}</tbody></table></div></section>`;
}

async function loadReceipt() {
    const id = new URLSearchParams(location.search).get('id');
    if (!id) throw new Error('A statement ID is required.');
    const data = await request('details', { statement_id: id });
    const row = getStatement(data);
    document.getElementById('receiptTitle').textContent = `Statement #${statementId(row)}`;
    document.getElementById('receiptDate').textContent = `Generated ${dateTimeLabel(row.statement_date)}`;
    const charges = firstArray(row, ['charges', 'statement_charges']);
    const payments = firstArray(row, ['payments', 'statement_payments']);
    document.getElementById('receiptContent').innerHTML = `
        <div class="mb-8 border-b pb-6 text-center"><h2 class="text-2xl font-bold">Billing Hospital</h2><p class="mt-1 text-sm text-slate-500">Official Billing Statement</p></div>
        <div class="mb-8 grid grid-cols-2 gap-6 text-sm"><div><p class="mb-2 text-xs font-semibold uppercase text-slate-500">Patient</p><p class="font-semibold">${html(personName(row))}</p><p>${html(row.contact_number || '')}</p><p>${html(row.address || '')}</p></div><div class="text-right"><p class="mb-2 text-xs font-semibold uppercase text-slate-500">Statement</p><p>#${html(statementId(row))}</p><p>Admission #${html(row.admission_id)}</p><p>Admitted ${html(dateLabel(row.admission_datetime))}</p></div></div>
        <h3 class="mb-3 text-xs font-semibold uppercase text-slate-500">Charges</h3><div class="mb-8 overflow-x-auto"><table class="min-w-full text-sm"><thead class="border-b-2"><tr><th class="py-2 text-left">Item</th><th class="py-2 text-left">Category</th><th class="py-2 text-center">Qty</th><th class="py-2 text-right">Unit</th><th class="py-2 text-right">Total</th></tr></thead><tbody class="divide-y">${charges.map(charge => `<tr><td class="py-2">${html(charge.item_name)}</td><td class="py-2">${html(charge.category_name)}</td><td class="py-2 text-center">${html(charge.quantity)}</td><td class="py-2 text-right">${money(charge.actual_price)}</td><td class="py-2 text-right">${money(charge.line_total)}</td></tr>`).join('') || '<tr><td colspan="5" class="py-6 text-center text-slate-400">No charges on this statement.</td></tr>'}</tbody></table></div>
        <div class="mb-8 ml-auto max-w-xs space-y-2 border-t pt-4 text-sm"><div class="flex justify-between"><span>Subtotal</span><span>${money(row.subtotal_amount)}</span></div><div class="flex justify-between"><span>Tax</span><span>${money(row.tax_amount)}</span></div><div class="flex justify-between border-t pt-2 text-base font-bold"><span>Total</span><span>${money(row.total_amount)}</span></div><div class="flex justify-between"><span>Insurance</span><span>− ${money(row.insurance_coverage_amount)}</span></div><div class="flex justify-between"><span>Government Discount</span><span>− ${money(row.government_discount)}</span></div><div class="flex justify-between"><span>Paid</span><span>${money(row.amount_paid)}</span></div><div class="flex justify-between text-lg font-bold"><span>Balance</span><span>${money(row.balance_amount)}</span></div></div>
        <h3 class="mb-3 text-xs font-semibold uppercase text-slate-500">Payments</h3><div class="overflow-x-auto"><table class="min-w-full text-sm"><thead class="border-b"><tr><th class="py-2 text-left">Date</th><th class="py-2 text-left">Type</th><th class="py-2 text-left">Reference</th><th class="py-2 text-right">Amount</th></tr></thead><tbody class="divide-y">${payments.map(payment => `<tr><td class="py-2">${html(dateTimeLabel(payment.payment_datetime))}</td><td class="py-2">${html(payment.type_name || payment.payment_type_name)}</td><td class="py-2">${html(payment.transaction_reference || '—')}</td><td class="py-2 text-right">${money(payment.amount)}</td></tr>`).join('') || '<tr><td colspan="4" class="py-5 text-center text-slate-400">No payments recorded.</td></tr>'}</tbody></table></div>`;
}

initialize();
