(function () {
    const baseUrl = document.body.dataset.baseUrl;
    const apiUrl = `${baseUrl}/api/patient-portal.php`;
    const loginSection = document.getElementById('patientPortalLogin');
    const dashboardSection = document.getElementById('patientPortalDashboard');
    const loginForm = document.getElementById('patientPortalLoginForm');
    const loginError = document.getElementById('portalLoginError');
    const loginButton = document.getElementById('patientPortalLoginButton');
    const captchaQuestion = document.getElementById('captchaQuestion');
    const captchaAnswer = document.getElementById('captchaAnswer');
    const loading = document.getElementById('patientPortalLoading');
    const content = document.getElementById('patientPortalContent');
    let idleTimer;
    let lastActivity = Date.now();

    const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, char => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    })[char]);
    const money = value => `₱${Number(value || 0).toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })}`;
    const date = value => {
        if (!value) return '—';
        const parsed = new Date(String(value).replace(' ', 'T'));
        return Number.isNaN(parsed.getTime()) ? String(value) : parsed.toLocaleDateString('en-PH', {
            year: 'numeric', month: 'short', day: 'numeric',
        });
    };

    async function request(operation, { method = 'GET', body } = {}) {
        const url = new URL(apiUrl, location.origin);
        url.searchParams.set('operation', operation);
        const response = await fetch(url, {
            method,
            credentials: 'same-origin',
            headers: body ? { 'Content-Type': 'application/json', Accept: 'application/json' } : { Accept: 'application/json' },
            body: body ? JSON.stringify(body) : undefined,
        });
        const result = await response.json();
        if (!response.ok || !result.success) {
            const error = new Error(result.message || 'The request could not be completed.');
            error.status = response.status;
            throw error;
        }
        return result;
    }

    async function refreshCaptcha() {
        const result = await request('captcha');
        captchaQuestion.textContent = result.question;
        captchaAnswer.value = '';
    }

    function showLoginError(message) {
        loginError.textContent = message;
        loginError.classList.remove('hidden');
    }

    function renderDashboard(data) {
        const patient = data.patient;
        const statementRows = data.statements.length
            ? data.statements.map(statement => `
                <tr class="border-t border-slate-100">
                    <td class="px-4 py-3">#${Number(statement.statement_id)} · Admission #${Number(statement.admission_id)}</td>
                    <td class="px-4 py-3">${escapeHtml(date(statement.statement_date))}</td>
                    <td class="px-4 py-3">${escapeHtml(statement.status)}</td>
                    <td class="px-4 py-3 text-right">${money(statement.total_amount)}</td>
                    <td class="px-4 py-3 text-right">${money(statement.amount_paid)}</td>
                    <td class="px-4 py-3 text-right font-semibold">${money(statement.balance_amount)}</td>
                    <td class="px-4 py-3 text-right"><a target="_blank" rel="noopener" href="${baseUrl}/patient-statement.php?statement_id=${Number(statement.statement_id)}" class="whitespace-nowrap rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-100">View Statement</a></td>
                </tr>`).join('')
            : '<tr><td colspan="7" class="px-4 py-8 text-center text-sm text-slate-500">No billing statements available.</td></tr>';
        const chargeRows = data.charges.length
            ? data.charges.map(charge => `
                <tr class="border-t border-slate-100">
                    <td class="px-4 py-3">${escapeHtml(date(charge.charge_datetime))}</td>
                    <td class="px-4 py-3">#${Number(charge.statement_id)}</td>
                    <td class="px-4 py-3">${escapeHtml(charge.item_name)}</td>
                    <td class="px-4 py-3 text-right">${Number(charge.quantity)}</td>
                    <td class="px-4 py-3 text-right">${money(charge.amount)}</td>
                </tr>`).join('')
            : '<tr><td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500">No itemized charges available.</td></tr>';
        const admissions = data.admissions.length
            ? data.admissions.map(admission => `
                <article class="rounded-lg border border-slate-200 p-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h3 class="font-semibold text-slate-900">Admission #${Number(admission.admission_id)}</h3>
                        <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700">${escapeHtml(admission.status)}</span>
                    </div>
                    <p class="mt-2 text-sm text-slate-600">${escapeHtml(admission.chief_complaint || 'No reason recorded')}</p>
                    <p class="mt-1 text-xs text-slate-500">${escapeHtml(date(admission.admission_datetime))}${admission.discharge_datetime ? ` – ${escapeHtml(date(admission.discharge_datetime))}` : ''}</p>
                </article>`).join('')
            : '<p class="text-sm text-slate-500">No admissions recorded.</p>';
        const diagnoses = data.diagnoses.length
            ? data.diagnoses.map(diagnosis => `
                <li class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 py-3 first:pt-0 last:pb-0">
                    <div><p class="font-medium text-slate-900">${escapeHtml(diagnosis.diagnosis_name)}</p>
                    <p class="text-xs text-slate-500">${escapeHtml(diagnosis.diagnosis_type || 'Diagnosis')}${diagnosis.icd_code ? ` · ${escapeHtml(diagnosis.icd_code)}` : ''} · Admission #${Number(diagnosis.admission_id)}</p></div>
                    <time class="text-xs text-slate-500">${escapeHtml(date(diagnosis.diagnosed_datetime))}</time>
                </li>`).join('')
            : '<li class="text-sm text-slate-500">No diagnoses recorded.</li>';
        const consultations = data.consultations.length
            ? data.consultations.map(consultation => `
                <li class="flex flex-wrap justify-between gap-x-4 gap-y-1 border-t border-slate-100 py-3 first:border-0 first:pt-0 last:pb-0">
                    <div><p class="font-medium text-slate-900">${escapeHtml(consultation.purpose || 'Consultation')}</p>
                    <p class="text-xs text-slate-500">Dr. ${escapeHtml(`${consultation.doctor_first_name} ${consultation.doctor_last_name}`)} · ${escapeHtml(consultation.status || 'Recorded')}</p></div>
                    <time class="text-xs text-slate-500">${escapeHtml(date(consultation.consultation_datetime))}</time>
                </li>`).join('')
            : '<li class="text-sm text-slate-500">No consultations recorded.</li>';

        content.innerHTML = `
            <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
                <div><p class="text-sm font-medium text-blue-700">Patient Portal</p>
                    <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Welcome, ${escapeHtml(patient.first_name)}</h1>
                    <p class="mt-1 text-sm text-slate-600">${escapeHtml(`${patient.first_name} ${patient.last_name}`)} · Date of birth ${escapeHtml(date(patient.birth_date))}</p></div>
                <button id="patientPortalLogout" type="button" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Sign out</button>
            </div>
            <div class="mb-6 grid gap-4 sm:grid-cols-3">
                <article class="rounded-xl border border-slate-200 bg-white p-5"><p class="text-xs font-medium uppercase tracking-wide text-slate-500">Admissions</p><p class="mt-2 text-2xl font-bold text-slate-900">${data.admissions.length}</p></article>
                <article class="rounded-xl border border-slate-200 bg-white p-5"><p class="text-xs font-medium uppercase tracking-wide text-slate-500">Billed</p><p class="mt-2 text-2xl font-bold text-blue-700">${money(data.statements.reduce((sum, statement) => sum + Number(statement.total_amount), 0))}</p></article>
                <article class="rounded-xl border border-slate-200 bg-white p-5"><p class="text-xs font-medium uppercase tracking-wide text-slate-500">Outstanding balance</p><p class="mt-2 text-2xl font-bold text-amber-700">${money(data.statements.reduce((sum, statement) => sum + Number(statement.balance_amount), 0))}</p></article>
            </div>
            <section class="mb-6 overflow-hidden rounded-xl border border-slate-200 bg-white">
                <div class="border-b border-slate-100 px-5 py-4"><h2 class="font-semibold text-slate-900">Billing statements</h2><p class="mt-1 text-xs text-slate-500">Amounts shown are for information and may not reflect pending adjustments.</p></div>
                <div class="overflow-x-auto"><table class="min-w-full text-sm"><thead class="bg-slate-50 text-left text-xs font-medium uppercase text-slate-500"><tr>
                    <th class="px-4 py-3">Statement</th><th class="px-4 py-3">Date</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Total</th><th class="px-4 py-3 text-right">Paid</th><th class="px-4 py-3 text-right">Balance</th>
                    <th class="px-4 py-3 text-right">Statement of Account</th>
                </tr></thead><tbody>${statementRows}</tbody></table></div>
            </section>
            <section class="mb-6 overflow-hidden rounded-xl border border-slate-200 bg-white">
                <div class="border-b border-slate-100 px-5 py-4"><h2 class="font-semibold text-slate-900">Recent charges</h2></div>
                <div class="overflow-x-auto"><table class="min-w-full text-sm"><thead class="bg-slate-50 text-left text-xs font-medium uppercase text-slate-500"><tr>
                    <th class="px-4 py-3">Date</th><th class="px-4 py-3">Statement</th><th class="px-4 py-3">Item</th><th class="px-4 py-3 text-right">Qty</th><th class="px-4 py-3 text-right">Amount</th>
                </tr></thead><tbody>${chargeRows}</tbody></table></div>
            </section>
            <div class="grid gap-6 lg:grid-cols-2">
                <section class="rounded-xl border border-slate-200 bg-white p-5"><h2 class="font-semibold text-slate-900">Medical history</h2>
                    <p class="mt-3 whitespace-pre-wrap text-sm leading-6 text-slate-700">${escapeHtml(patient.medical_history || 'No medical history recorded.')}</p>
                    <h3 class="mt-6 border-t border-slate-100 pt-5 font-semibold text-slate-900">Diagnoses</h3>
                    <ul class="mt-3 divide-y divide-slate-100">${diagnoses}</ul>
                </section>
                <div class="space-y-6">
                    <section class="rounded-xl border border-slate-200 bg-white p-5"><h2 class="font-semibold text-slate-900">Admissions</h2><div class="mt-3 space-y-3">${admissions}</div></section>
                    <section class="rounded-xl border border-slate-200 bg-white p-5"><h2 class="font-semibold text-slate-900">Consultations</h2><ul class="mt-3 divide-y divide-slate-100">${consultations}</ul></section>
                </div>
            </div>
            <p class="mt-6 text-center text-xs text-slate-500">This portal session automatically ends after five minutes without activity.</p>`;

        loading.classList.add('hidden');
        content.classList.remove('hidden');
        document.getElementById('patientPortalLogout').addEventListener('click', logout);
    }

    function beginIdleTimeout() {
        clearTimeout(idleTimer);
        idleTimer = setTimeout(async () => {
            try {
                await request('logout', { method: 'POST', body: {} });
            } catch (error) {
                console.error('[patient-portal] Could not destroy expired session.', error);
            }
            showLogin();
            await Swal.fire({
                icon: 'info',
                title: 'Session expired',
                text: 'For your privacy, sign in again to continue.',
            });
        }, 300000);
    }

    function recordActivity() {
        const now = Date.now();
        if (now - lastActivity < 1000) return;
        lastActivity = now;
        if (document.body.dataset.authenticated === '1') beginIdleTimeout();
    }

    async function logout() {
        try {
            await request('logout', { method: 'POST', body: {} });
            showLogin();
            await Swal.fire({ icon: 'success', title: 'Signed out', timer: 1200, showConfirmButton: false });
        } catch (error) {
            await Swal.fire({ icon: 'error', title: 'Could not sign out', text: error.message });
        }
    }

    function showLogin() {
        clearTimeout(idleTimer);
        document.body.dataset.authenticated = '0';
        dashboardSection.classList.add('hidden');
        loginSection.classList.remove('hidden');
        content.classList.add('hidden');
        loginForm.reset();
        loginError.classList.add('hidden');
        refreshCaptcha().catch(error => showLoginError(error.message));
    }

    async function loadDashboard() {
        try {
            const result = await request('dashboard');
            renderDashboard(result.data);
            document.body.dataset.authenticated = '1';
            recordActivity();
        } catch (error) {
            if (error.status === 401) {
                showLogin();
                return;
            }
            loading.innerHTML = `<p class="text-rose-700">${escapeHtml(error.message)}</p><button id="patientPortalRetry" type="button" class="mt-3 rounded-lg border px-3 py-2 text-sm">Try again</button>`;
            document.getElementById('patientPortalRetry').addEventListener('click', loadDashboard);
        }
    }

    loginForm.addEventListener('submit', async event => {
        event.preventDefault();
        loginError.classList.add('hidden');
        loginButton.disabled = true;
        loginButton.textContent = 'Signing in…';
        try {
            await request('login', {
                method: 'POST',
                body: Object.fromEntries(new FormData(loginForm).entries()),
            });
            document.body.dataset.authenticated = '1';
            loginSection.classList.add('hidden');
            dashboardSection.classList.remove('hidden');
            loading.classList.remove('hidden');
            content.classList.add('hidden');
            await loadDashboard();
            if (document.body.dataset.authenticated === '1') {
                await Swal.fire({ icon: 'success', title: 'Signed in', toast: true, position: 'top-end', timer: 1800, showConfirmButton: false });
            }
        } catch (error) {
            showLoginError(error.message);
            captchaAnswer.value = '';
            await refreshCaptcha().catch(refreshError => showLoginError(refreshError.message));
        } finally {
            loginButton.disabled = false;
            loginButton.textContent = 'Sign in securely';
        }
    });

    document.getElementById('refreshCaptcha').addEventListener('click', () => {
        refreshCaptcha().catch(error => showLoginError(error.message));
    });

    for (const eventName of ['pointerdown', 'pointermove', 'keydown', 'touchstart']) {
        document.addEventListener(eventName, recordActivity, { passive: true });
    }

    setInterval(() => {
        if (document.body.dataset.authenticated !== '1' || Date.now() - lastActivity >= 300000) return;
        request('touch').catch(error => {
            if (error.status === 401) showLogin();
        });
    }, 60000);

    refreshCaptcha().catch(error => showLoginError(error.message));
    if (document.body.dataset.authenticated === '1') loadDashboard();
})();
