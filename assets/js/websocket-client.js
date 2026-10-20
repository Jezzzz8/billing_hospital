(function () {
    const userRole = parseInt(document.body.dataset.userRole || '0', 10);
    const departmentId = parseInt(document.body.dataset.departmentId || '0', 10);
    const userId = parseInt(document.body.dataset.userId || '0', 10);
    const storageKey = `billing-hospital-notifications-${userId}`;
    const notificationToggle = document.getElementById('notificationToggle');
    const notificationPanel = document.getElementById('notificationPanel');
    const notificationList = document.getElementById('notificationList');
    const notificationCount = document.getElementById('notificationCount');
    const notificationConnection = document.getElementById('notificationConnection');
    const clearNotifications = document.getElementById('clearNotifications');
    const markAllNotificationsRead = document.getElementById('markAllNotificationsRead');
    const closeNotifications = document.getElementById('closeNotifications');
    const adminNotificationEvents = new Set([
        'master.updated',
        'doctor.profile.created',
        'doctor.profile.toggled',
        'doctor.profile.updated',
    ]);
    const eventMessages = {
        'admission.created': ['New admission', 'A patient admission was created.'],
        'admission.discharged': ['Patient discharged', 'A patient admission was discharged.'],
        'admission.room.assigned': ['Room assigned', 'A patient was assigned to a room.'],
        'admission.room.transferred': ['Room transfer', 'A patient was transferred to another room.'],
        'billing.charge.added': ['Charge added', 'A charge was added to a billing statement.'],
        'billing.charge.removed': ['Charge removed', 'A charge was removed from a billing statement.'],
        'billing.payment.added': ['Payment recorded', 'A payment was recorded.'],
        'billing.payment.removed': ['Payment removed', 'A payment was removed.'],
        'billing.statement.created': ['Statement created', 'A billing statement was created.'],
        'billing.statement.updated': ['Statement updated', 'A billing statement was updated.'],
        'consultation.created': ['Consultation recorded', 'A consultation was created.'],
        'consultation.updated': ['Consultation updated', 'A consultation was updated.'],
        'diagnosis.added': ['Diagnosis added', 'A diagnosis was added to an admission.'],
        'diagnosis.removed': ['Diagnosis removed', 'A diagnosis was removed from an admission.'],
        'discharge.confirmed': ['Discharge confirmed', 'A patient was cleared for discharge.'],
        'doctor.assigned': ['Doctor assigned', 'A doctor was assigned to an admission.'],
        'doctor.profile.created': ['Doctor added', 'A doctor profile was created.'],
        'doctor.profile.toggled': ['Doctor status changed', 'A doctor profile status was changed.'],
        'doctor.profile.updated': ['Doctor updated', 'A doctor profile was updated.'],
        'doctor.removed': ['Doctor assignment removed', 'A doctor was removed from an admission.'],
        'fulfillment.received': ['Items received', 'Fulfilled items were received by nursing staff.'],
        'master.updated': ['Records updated', 'Hospital reference data was updated.'],
        'medicine.return.received': ['Medicine return received', 'Pharmacy received a medicine return and updated the patient bill.'],
        'medicine.intake.updated': ['Intake tracking updated', "Nursing recorded a dose or reconciled a patient's Pharmacy-item quantity."],
        'pharmacy.stock.updated': ['Pharmacy stock updated', 'A Pharmacy stock quantity was adjusted.'],
        'patient.created': ['Patient registered', 'A new patient was registered.'],
        'service.request.accepted': ['Request accepted', 'A department accepted a service request.'],
        'service.request.cancelled': ['Request cancelled', 'A service request was cancelled.'],
        'service.request.completed': ['Request completed', 'A service request was completed.'],
        'service.request.created': ['New service request', 'A new service request was submitted.'],
        'service.request.fulfilled': ['Request fulfilled', 'A department recorded fulfillment for a service request.'],
        'service.request.processing': ['Request in progress', 'A service request is being processed.'],
    };
    const eventRoles = {
        'admission.created': [3],
        'admission.discharged': [2, 3],
        'admission.room.assigned': [3],
        'admission.room.transferred': [3],
        'billing.charge.added': [4],
        'billing.charge.removed': [4],
        'billing.payment.added': [4],
        'billing.payment.removed': [4],
        'billing.statement.created': [4],
        'billing.statement.updated': [4],
        'consultation.created': [2],
        'consultation.updated': [2],
        'diagnosis.added': [2],
        'diagnosis.removed': [2],
        'discharge.confirmed': [2, 3],
        'doctor.assigned': [2, 3],
        'doctor.profile.created': [1],
        'doctor.profile.toggled': [1],
        'doctor.profile.updated': [1, 2],
        'doctor.removed': [2, 3],
        'fulfillment.received': [3, 5],
        'medicine.return.received': [2, 4, 5],
        'medicine.intake.updated': [3],
        'pharmacy.stock.updated': [5],
        'patient.created': [3],
        'service.request.accepted': [2, 5],
        'service.request.cancelled': [2, 5],
        'service.request.completed': [2, 5],
        'service.request.created': [2, 5],
        'service.request.fulfilled': [2, 3, 4, 5],
        'service.request.processing': [2, 5],
    };
    let socket = null;
    let reconnectTimer = null;
    let refreshTimer = null;
    let notifications = loadNotifications();

    function loadNotifications() {
        try {
            const saved = JSON.parse(localStorage.getItem(storageKey) || '[]');
            return Array.isArray(saved)
                ? saved
                    .filter(item =>
                        (userRole !== 1 || adminNotificationEvents.has(String(item.event || '')))
                        && notificationUrl(item.event, item.payload || '') !== null
                    )
                    .slice(0, 50).map((item, index) => ({
                    ...item,
                    id: String(item.id || `${item.timestamp || 'saved'}-${index}`),
                    read: Boolean(item.read),
                }))
                : [];
        } catch (error) {
            console.warn('[ws] Could not restore notifications.', error);
            return [];
        }
    }

    function renderNotifications() {
        if (!notificationList) return;
        const unreadCount = notifications.filter(item => !item.read).length;
        if (notificationCount) {
            notificationCount.textContent = unreadCount > 99 ? '99+' : String(unreadCount);
            notificationCount.classList.toggle('hidden', unreadCount === 0);
        }
        notificationList.innerHTML = notifications.length
            ? notifications.map(item => {
                const href = notificationUrl(item.event, item.payload);
                const icon = notificationIcon(item.event);
                const content = `<p class="text-sm font-semibold text-slate-800">${escapeHtml(item.title)}</p>
                    <p class="mt-1 text-xs text-slate-600">${escapeHtml(item.message)}</p>`;
                return `<article class="group flex gap-3 px-4 py-3 transition-colors hover:bg-slate-50 ${item.read ? '' : 'bg-blue-50/70'}">
                <a href="${escapeHtml(href)}" data-notification-link="${escapeHtml(item.id)}" class="relative mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-full ${icon.background} ${icon.color} focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500" aria-label="Open ${escapeHtml(item.title)}">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${icon.path}</svg>
                    ${item.read ? '' : '<span class="absolute -right-0.5 -top-0.5 h-3 w-3 rounded-full border-2 border-white bg-blue-600" aria-label="Unread"></span>'}
                </a>
                <div class="min-w-0 flex-1">
                    <a href="${escapeHtml(href)}" data-notification-link="${escapeHtml(item.id)}" class="block rounded focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500" aria-label="Open ${escapeHtml(item.title)}">${content}</a>
                    <time class="mt-1 block text-[11px] text-slate-400" datetime="${escapeHtml(item.timestamp)}" title="${escapeHtml(new Date(item.timestamp).toLocaleString())}">${escapeHtml(relativeTime(item.timestamp))}</time>
                    <button type="button" data-notification-action="toggle-read" data-notification-id="${escapeHtml(item.id)}" class="mt-1 text-xs font-medium text-blue-700 hover:text-blue-800">${item.read ? 'Mark unread' : 'Mark read'}</button>
                </div>
                <button type="button" data-notification-action="remove" data-notification-id="${escapeHtml(item.id)}" aria-label="Remove notification" title="Remove notification" class="h-7 w-7 shrink-0 rounded-full text-slate-400 opacity-70 transition hover:bg-slate-200 hover:text-slate-700 sm:opacity-0 sm:group-hover:opacity-100 focus:opacity-100">
                    <svg class="mx-auto h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </article>`;
            }).join('')
            : '<p class="px-4 py-6 text-center text-sm text-slate-500">No notifications yet.</p>';
        try {
            localStorage.setItem(storageKey, JSON.stringify(notifications));
        } catch (error) {
            console.warn('[ws] Could not save notifications.', error);
        }
    }

    function notificationIcon(eventName) {
        let background = 'bg-slate-100';
        let color = 'text-slate-600';
        let path = '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M10 21h4"/>';

        if (eventName.startsWith('admission.room.') || eventName === 'admission.created' || eventName === 'admission.discharged') {
            background = 'bg-violet-100';
            color = 'text-violet-700';
            path = '<path d="M3 11h18v9H3z"/><path d="M5 11V7a2 2 0 0 1 2-2h3v6"/><path d="M3 16h18"/>';
        } else if (eventName.startsWith('billing.')) {
            background = 'bg-emerald-100';
            color = 'text-emerald-700';
            path = '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h3"/>';
        } else if (eventName.startsWith('service.request.') || eventName === 'fulfillment.received') {
            background = 'bg-blue-100';
            color = 'text-blue-700';
            path = '<path d="M8 6h13M8 12h13M8 18h13"/><path d="m3 6 1 1 2-2m-3 7 1 1 2-2m-3 7 1 1 2-2"/>';
        } else if (eventName.startsWith('medicine.') || eventName === 'pharmacy.stock.updated') {
            background = 'bg-teal-100';
            color = 'text-teal-700';
            path = '<path d="m10.5 20.5 10-10a5.66 5.66 0 0 0-8-8l-10 10a5.66 5.66 0 0 0 8 8Z"/><path d="m8.5 8.5 7 7"/>';
        } else if (eventName.startsWith('doctor.') || eventName.startsWith('consultation.') || eventName.startsWith('diagnosis.')) {
            background = 'bg-indigo-100';
            color = 'text-indigo-700';
            path = '<path d="M12 8v4l3 2"/><circle cx="12" cy="12" r="9"/>';
        } else if (eventName === 'patient.created') {
            background = 'bg-rose-100';
            color = 'text-rose-700';
            path = '<circle cx="9" cy="8" r="4"/><path d="M3 21v-2a6 6 0 0 1 12 0v2m3-11v6m-3-3h6"/>';
        }

        return { background, color, path };
    }

    function relativeTime(timestamp) {
        const elapsedSeconds = Math.max(0, Math.floor((Date.now() - new Date(timestamp).getTime()) / 1000));
        if (!Number.isFinite(elapsedSeconds)) return 'Just now';
        if (elapsedSeconds < 60) return 'Just now';
        if (elapsedSeconds < 3600) return `${Math.floor(elapsedSeconds / 60)}m ago`;
        if (elapsedSeconds < 86400) return `${Math.floor(elapsedSeconds / 3600)}h ago`;
        if (elapsedSeconds < 604800) return `${Math.floor(elapsedSeconds / 86400)}d ago`;
        return new Date(timestamp).toLocaleDateString();
    }

    function escapeHtml(value) {
        return String(value).replace(/[&<>"']/g, char => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
        })[char]);
    }

    function notificationUrl(eventName, payload = {}) {
        eventName = String(eventName || '');
        const admissionId = Number(payload.admission_id || 0);
        const requestId = Number(payload.request_id || 0);
        const statementId = Number(payload.statement_id || 0);
        let page = '';
        const params = {};

        if (eventName.startsWith('service.request.')) {
            if (userRole === 5) {
                page = requestId ? 'department-fulfillment' : 'department-queue';
                if (requestId) params.request_id = requestId;
            } else if (userRole === 2) {
                page = 'doctor-service-requests';
                if (admissionId) params.admission_id = admissionId;
            } else if (userRole === 3) {
                page = 'nurse-fulfillment-receipts';
            } else if (userRole === 4) {
                page = 'cashier-statements';
            }
        } else if (eventName === 'admission.created' && userRole === 3) {
            page = 'nurse-room-assignments';
            if (admissionId) params.admission_id = admissionId;
        } else if (['admission.room.assigned', 'admission.room.transferred', 'doctor.assigned', 'doctor.removed'].includes(eventName)) {
            if (userRole === 3) {
                page = 'nurse-room-assignments';
                if (admissionId) params.admission_id = admissionId;
            } else if (userRole === 2) {
                page = 'doctor-patients';
            }
        } else if (eventName === 'admission.discharged') {
            page = userRole === 3 ? 'nurse-discharge-records' : 'doctor-discharge-ready';
        } else if (eventName === 'discharge.confirmed') {
            page = userRole === 3 ? 'nurse-discharge-ready' : 'doctor-discharge-ready';
        } else if (['billing.charge.added', 'billing.charge.removed', 'billing.payment.added',
            'billing.payment.removed', 'billing.statement.created', 'billing.statement.updated'].includes(eventName)) {
            if (userRole === 4) {
                page = statementId ? 'cashier-receipt' : 'cashier-statements';
                if (statementId) params.id = statementId;
            }
        } else if (eventName === 'fulfillment.received' && userRole === 3) {
            page = 'nurse-fulfillment-receipts';
        } else if (eventName === 'fulfillment.received' && userRole === 5) {
            page = 'department-queue';
        } else if (eventName === 'medicine.intake.updated' && userRole === 3) {
            page = 'nurse-medicine-intakes';
        } else if (eventName === 'pharmacy.stock.updated' && userRole === 5) {
            page = 'pharmacy-stock';
        } else if (eventName.startsWith('doctor.profile.') && userRole === 1) {
            page = 'doctors';
        } else if (eventName.startsWith('doctor.profile.') && userRole === 2) {
            page = 'doctor';
        } else if (eventName === 'patient.created' && userRole === 3) {
            page = 'nurse-patient-search';
        } else if (eventName === 'medicine.return.received') {
            page = userRole === 5
                ? 'department-fulfillment'
                : userRole === 4
                    ? 'cashier-statements'
                    : userRole === 2
                        ? 'doctor-discharge-ready'
                        : 'nurse-discharge-ready';
            if (userRole === 5 && requestId) params.request_id = requestId;
        } else if (eventName === 'master.updated' && userRole === 1) {
            const masterPages = {
                'admission-statuses': 'admission-status',
                'billing-statuses': 'billing-status',
                'buildings': 'building',
                'charge-categories': 'charge-category',
                'charge-items': 'charge-item',
                'diagnoses': 'diagnosis',
                'departments': 'department',
                'floor-levels': 'floor-level',
                'genders': 'gender',
                'payment-types': 'payment-type',
                'roles': 'role',
                'room-statuses': 'room-status',
                'room-types': 'room-type',
                'rooms': 'room',
                'specializations': 'specialization',
                'users': 'users',
                'doctors': 'doctors',
            };
            page = masterPages[payload.resource] || '';
        } else if (eventName === 'master.updated' && userRole === 2 && payload.resource === 'diagnoses') {
            page = 'doctor-diagnosis-record';
        } else if (eventName.startsWith('consultation.') && userRole === 2) {
            page = 'doctor-consultations';
        } else if (eventName.startsWith('diagnosis.') && userRole === 2) {
            page = 'doctor-diagnosis-record';
            if (admissionId) params.admission_id = admissionId;
        }

        if (!page) return null;
        if (!pageAllowedForRole(page, userRole)) return null;
        const query = new URLSearchParams({ page, ...params });
        return `${document.body.dataset.baseUrl || ''}/index.php?${query.toString()}`;
    }

    function pageAllowedForRole(page, role) {
        const allowedPages = {
            1: ['dashboard', 'users', 'users-create', 'doctors', 'patients', 'billing', 'reports',
                'gender', 'role', 'room-status', 'admission-status', 'billing-status', 'payment-type',
                'specialization', 'diagnosis', 'charge-category', 'charge-item', 'building',
                'floor-level', 'room-type', 'room', 'department', 'department-requests', 'admin-receipt'],
            2: ['doctor', 'doctor-patients', 'doctor-consultations', 'doctor-diagnosis-record',
                'doctor-service-requests', 'doctor-discharge-ready'],
            3: ['nurse', 'nurse-patient-search', 'nurse-patient-register', 'nurse-admission-create',
                'nurse-admissions', 'nurse-fulfillment-receipts', 'nurse-medicine-intakes',
                'nurse-room-assignments', 'nurse-room-board', 'nurse-discharge-ready',
                'nurse-discharge-records'],
            4: ['cashier', 'cashier-payment-counter', 'cashier-statements', 'cashier-payments',
                'cashier-reports', 'cashier-receipt'],
            5: ['department', 'department-queue', 'department-fulfillment', 'pharmacy-stock'],
        };
        return (allowedPages[role] || []).includes(page);
    }

    function notificationMessage(eventName, payload = {}) {
        const [, baseMessage] = eventMessages[eventName] || ['Records updated', 'Hospital records were updated.'];
        const details = [];
        const patientName = payload.patient_name || payload.patient_full_name;
        if (patientName) details.push(`Patient: ${patientName}`);
        else if (payload.admission_id) details.push(`Admission #${payload.admission_id}`);
        if (payload.request_id) details.push(`Request #${payload.request_id}`);
        if (payload.item_name) {
            const quantity = payload.quantity ?? payload.quantity_fulfilled ?? payload.quantity_received;
            details.push(quantity !== undefined ? `${payload.item_name} × ${quantity}` : String(payload.item_name));
        }
        if (payload.room_number) details.push(`Room ${payload.room_number}`);
        if (payload.department_name) details.push(String(payload.department_name));
        if (payload.amount !== undefined) details.push(`₱${Number(payload.amount).toLocaleString('en-PH', { minimumFractionDigits: 2 })}`);
        if (payload.reference) details.push(`Reference ${payload.reference}`);
        if (payload.status) details.push(`Status: ${payload.status}`);
        if (payload.purpose) details.push(String(payload.purpose));
        if (payload.intake_status) details.push(`Intake: ${payload.intake_status}`);
        if (payload.quantity_remaining !== undefined) details.push(`${payload.quantity_remaining} remaining`);
        return details.length ? `${details.join(' · ')}. ${baseMessage}` : baseMessage;
    }

    function addNotification(eventName, payload = {}) {
        const [title] = eventMessages[eventName] || ['Records updated', 'Hospital records were updated.'];
        notifications.unshift({
            id: `${Date.now()}-${Math.random().toString(36).slice(2)}`,
            event: eventName,
            payload,
            title,
            message: notificationMessage(eventName, payload),
            timestamp: new Date().toISOString(),
            read: false,
        });
        notifications = notifications.slice(0, 50);
        renderNotifications();
    }

    function refreshCurrentPage() {
        clearTimeout(refreshTimer);
        refreshTimer = setTimeout(() => {
            const refresh = typeof window.refreshCurrentPage === 'function'
                ? window.refreshCurrentPage()
                : Promise.resolve().then(() => location.reload());
            Promise.resolve(refresh).catch(error => {
                console.error('[ws] Could not refresh the current page.', error);
                location.reload();
            });
        }, 250);
    }

    notificationToggle?.addEventListener('click', () => {
        const isOpen = !notificationPanel.classList.contains('hidden');
        notificationPanel.classList.toggle('hidden', isOpen);
        notificationToggle.setAttribute('aria-expanded', String(!isOpen));
    });

    clearNotifications?.addEventListener('click', () => {
        notifications = [];
        renderNotifications();
    });

    markAllNotificationsRead?.addEventListener('click', () => {
        notifications = notifications.map(item => ({ ...item, read: true }));
        renderNotifications();
    });

    closeNotifications?.addEventListener('click', () => {
        notificationPanel?.classList.add('hidden');
        notificationToggle?.setAttribute('aria-expanded', 'false');
    });

    notificationList?.addEventListener('click', event => {
        const notificationLink = event.target.closest('[data-notification-link]');
        if (notificationLink) {
            const id = notificationLink.dataset.notificationLink;
            notifications = notifications.map(item =>
                item.id === id ? { ...item, read: true } : item
            );
            renderNotifications();
            return;
        }

        const button = event.target.closest('[data-notification-action]');
        if (!button) return;
        const id = button.dataset.notificationId;
        if (button.dataset.notificationAction === 'remove') {
            notifications = notifications.filter(item => item.id !== id);
        } else if (button.dataset.notificationAction === 'toggle-read') {
            notifications = notifications.map(item =>
                item.id === id ? { ...item, read: !item.read } : item
            );
        }
        renderNotifications();
    });

    window.addEventListener('storage', event => {
        if (event.key !== storageKey) return;
        notifications = loadNotifications();
        renderNotifications();
    });

    document.addEventListener('click', event => {
        if (notificationPanel && !notificationPanel.contains(event.target) && !notificationToggle?.contains(event.target)) {
            notificationPanel.classList.add('hidden');
            notificationToggle?.setAttribute('aria-expanded', 'false');
        }
    });

    renderNotifications();

    function connect() {
        const socketUrl = new URL(location.href);
        socketUrl.protocol = location.protocol === 'https:' ? 'wss:' : 'ws:';
        socketUrl.port = '8080';
        socketUrl.pathname = '/';
        socketUrl.search = '';
        socketUrl.hash = '';
        socket = new WebSocket(socketUrl.href);

        socket.onopen = () => {
            console.log('[ws] connected');
            if (notificationConnection) notificationConnection.textContent = 'Live';
        };

        socket.onmessage = (event) => {
            let data;
            try {
                data = JSON.parse(event.data);
            } catch (err) {
                console.warn('[ws] Ignoring malformed message.', err);
                return;
            }
            if (data.type !== 'event') return;
            if (userRole === 1 && !adminNotificationEvents.has(String(data.event || ''))) return;
            const payload = data.payload || {};
            if (Number(payload.sender_user_id || 0) === userId) return;

            const roles = Array.isArray(data.roles) ? data.roles.map(Number) : [];
            const targetUsers = Array.isArray(data.user_ids) ? data.user_ids.map(Number) : [];
            if (targetUsers.length) {
                if (!targetUsers.includes(userId)) return;
            } else if (!roles.includes(userRole)) {
                return;
            }
            const relevantRoles = eventRoles[data.event];
            if (data.event === 'master.updated') {
                const isAdminUpdate = userRole === 1 && adminNotificationEvents.has(String(data.event));
                const isDoctorDiagnosisUpdate = userRole === 2 && payload.resource === 'diagnoses';
                if (!isAdminUpdate && !isDoctorDiagnosisUpdate) return;
            } else if (!relevantRoles || !relevantRoles.includes(userRole)) {
                return;
            }

            const eventDepartment = Number(data.department || 0);
            if (!targetUsers.length && userRole === 5 && eventDepartment && departmentId !== eventDepartment) {
                return;
            }

            const destination = notificationUrl(String(data.event || ''), payload);
            if (!destination) return;

            document.dispatchEvent(new CustomEvent('ws:' + data.event, { detail: payload }));
            addNotification(String(data.event || 'update'), payload);
            if (window.Swal) {
                const [title] = eventMessages[data.event] || ['Records updated', 'Hospital records were updated.'];
                window.Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'info',
                    title,
                    text: notificationMessage(String(data.event || 'update'), data.payload || {}),
                    showConfirmButton: false,
                    timer: 4500,
                    timerProgressBar: true,
                });
            }
            refreshCurrentPage();
        };

        socket.onclose = () => {
            if (notificationConnection) notificationConnection.textContent = 'Offline — reconnecting';
            scheduleReconnect();
        };

        socket.onerror = () => {
            if (notificationConnection) notificationConnection.textContent = 'Connection error';
            socket.close();
        };
    }

    function scheduleReconnect() {
        if (reconnectTimer) return;
        reconnectTimer = setTimeout(() => {
            reconnectTimer = null;
            connect();
        }, 3000);
    }

    connect();
})();