const baseUrl = () => document.body?.dataset?.baseUrl || '';

function openModal(el) {
    if (el) el.classList.remove('hidden');
}

function closeModal(el) {
    if (el) el.classList.add('hidden');
}

function capitalize(value) {
    if (!value) return '';
    return value.charAt(0).toUpperCase() + value.slice(1);
}

function showAlert(el, type, msg) {
    if (!el) return;
    const styles = {
        success: 'border-emerald-200 bg-emerald-50 text-emerald-800',
        error: 'border-rose-200 bg-rose-50 text-rose-800',
    };
    el.className = 'mb-5 rounded-lg px-4 py-3 text-sm border ' + (styles[type] || styles.error);
    el.textContent = msg;
    el.classList.remove('hidden');
    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function clearErrors(scope) {
    if (!scope) return;
    scope.querySelectorAll('[data-error-for]').forEach((el) => {
        el.textContent = '';
        el.classList.add('hidden');
    });
}

function setErrors(scope, errors) {
    if (!scope || !errors) return;
    Object.entries(errors).forEach(([field, message]) => {
        const el = scope.querySelector(`[data-error-for="${field}"]`);
        if (!el) return;
        el.textContent = message;
        el.classList.remove('hidden');
    });
}

export async function adminRpc(resource, operation, payload = {}, extra = {}) {
    const url = new URL(`${baseUrl()}/api/admin.php`, location.origin);
    url.searchParams.set('resource', resource);
    url.searchParams.set('operation', operation);

    Object.entries(extra).forEach(([key, value]) => {
        if (value === undefined || value === null || value === '') return;
        url.searchParams.set(key, value);
    });

    const method = ['create', 'update', 'delete', 'toggle-active', 'change-password'].includes(operation)
        ? 'POST'
        : 'GET';

    const config = {
        method,
        headers: { Accept: 'application/json' },
        withCredentials: true,
    };

    if (method === 'POST') {
        config.headers['Content-Type'] = 'application/json';
        config.data = payload ?? {};
    }

    const { data } = await axios(url.toString(), config);
    return data;
}

function normalizeConfig(config) {
    const resourceMap = {
        gender: 'genders',
        role: 'roles',
        department: 'departments',
        'room-status': 'room-statuses',
        'admission-status': 'admission-statuses',
        'billing-status': 'billing-statuses',
        'payment-type': 'payment-types',
        specialization: 'specializations',
        diagnosis: 'diagnoses',
        'charge-category': 'charge-categories',
        'charge-item': 'charge-items',
        building: 'buildings',
        'floor-level': 'floor-levels',
        'room-type': 'room-types',
        room: 'rooms',
        doctor: 'doctors',
        doctors: 'doctors',
        user: 'users',
        users: 'users',
    };

    if (typeof config === 'string') {
        const key = config;
        return {
            resource: resourceMap[key] ?? key,
            singular: key,
            hasActive: false,
            submitLabelCreate: 'Create',
            submitLabelUpdate: 'Save Changes',
        };
    }

    return {
        resource: config?.resource ?? '',
        singular: config?.singular ?? '',
        hasActive: Boolean(config?.hasActive),
        submitLabelCreate: config?.submitLabelCreate ?? 'Create',
        submitLabelUpdate: config?.submitLabelUpdate ?? 'Save Changes',
    };
}

export function mountAdminMasterPage(config) {
    const normalized = normalizeConfig(config);
    const {
        resource,
        singular,
        hasActive = false,
        submitLabelCreate,
        submitLabelUpdate,
    } = normalized;

    const alertEl = document.getElementById('alert');
    const modal = document.getElementById(`${singular}Modal`);
    const form = document.getElementById(`${singular}Form`);
    if (!alertEl || !modal || !form) {
        console.warn('[master] Missing modal/form for', singular || resource);
        return;
    }
    if (form.dataset.adminMasterMounted === 'true') return;
    form.dataset.adminMasterMounted = 'true';

    const modalTitle = document.getElementById(`${singular}ModalTitle`);
    const submitBtn = document.getElementById(`${singular}SubmitBtn`);
    const submitLabel = document.getElementById(`${singular}SubmitLabel`);
    const openCreateBtn = document.getElementById('openCreateBtn');
    const confirmModal = document.getElementById('confirmModal') || document.getElementById('deleteModal');
    const passwordModal = document.getElementById('pwModal');
    const passwordForm = document.getElementById('pwForm');

    function fieldName(element) {
        if (element.name) return element.name;
        if (element.classList.contains('spec-checkbox')) return 'specializations';
        return element.id || '';
    }

    function gatherFormData() {
        const data = {};
        form.querySelectorAll('input, select, textarea').forEach((element) => {
            const name = fieldName(element);
            if (!name) return;

            if (element.classList.contains('spec-checkbox')) {
                if (!Array.isArray(data.specializations)) data.specializations = [];
                if (element.checked) data.specializations.push(Number(element.value));
                return;
            }

            if (element.type === 'checkbox') {
                data[name] = element.checked ? 1 : 0;
                return;
            }

            if (element.type === 'radio') {
                if (element.checked) {
                    data[name] = element.value;
                }
                return;
            }

            data[name] = element.value;
        });
        return data;
    }

    function fillForm(row) {
        clearErrors(form);
        form.querySelectorAll('input, select, textarea').forEach((element) => {
            const name = fieldName(element);
            if (!name) return;

            if (element.classList.contains('spec-checkbox')) {
                const selected = Array.isArray(row.specializations)
                    ? row.specializations.some((specialization) =>
                        String(specialization.specialization_id ?? specialization) === String(element.value))
                    : false;
                element.checked = selected;
                return;
            }

            let value = row[name];
            if (name === 'primary_specialization_id' && value == null && Array.isArray(row.specializations)) {
                value = row.specializations.find((specialization) => Number(specialization.is_primary) === 1)?.specialization_id;
            }

            if (element.type === 'checkbox') {
                element.checked = value === 1 || value === '1' || value === true;
                return;
            }

            if (element.type === 'radio') {
                element.checked = String(value ?? '') === String(element.value);
                return;
            }

            element.value = value ?? '';
        });
    }

    if (openCreateBtn) {
        openCreateBtn.addEventListener('click', () => {
            form.reset();
            clearErrors(form);
            form.querySelectorAll('input[name$="_id"]').forEach((element) => {
                element.value = '';
            });
            if (modalTitle) modalTitle.textContent = 'New ' + capitalize(singular);
            if (submitLabel) submitLabel.textContent = submitLabelCreate;
            openModal(modal);
        });
    }

    modal.querySelectorAll('[data-close-modal]').forEach((element) => {
        element.addEventListener('click', () => closeModal(modal));
    });

    document.querySelectorAll('.edit-btn').forEach((button) => {
        button.addEventListener('click', () => {
            const raw = button.dataset[singular];
            if (!raw) return;

            let record = {};
            try {
                record = JSON.parse(raw);
            } catch (error) {
                return;
            }

            form.reset();
            clearErrors(form);
            fillForm(record);
            if (modalTitle) modalTitle.textContent = 'Edit ' + capitalize(singular);
            if (submitLabel) submitLabel.textContent = submitLabelUpdate;
            openModal(modal);
        });
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearErrors(form);

        const idField = form.querySelector('[name$="_id"]');
        const id = idField ? idField.value : '';
        const isEdit = id !== '' && id != null;
        const payload = gatherFormData();

        if (submitBtn) submitBtn.disabled = true;
        if (submitLabel) submitLabel.textContent = isEdit ? 'Saving…' : 'Creating…';

        try {
            const result = await adminRpc(
                resource,
                isEdit ? 'update' : 'create',
                payload,
                isEdit ? { id } : {}
            );

            if (result.success) {
                closeModal(modal);
                sessionStorage.setItem(`${resource}_flash`, result.message || 'Saved.');
                location.reload();
                return;
            }

            setErrors(form, result.errors || {});
            showAlert(alertEl, 'error', result.message || 'Save failed.');
        } catch (error) {
            const response = error.response?.data;
            setErrors(form, response?.errors || {});
            showAlert(alertEl, 'error', response?.message || 'Save failed.');
        } finally {
            if (submitBtn) submitBtn.disabled = false;
            if (submitLabel) submitLabel.textContent = isEdit ? submitLabelUpdate : submitLabelCreate;
        }
    });

    if (passwordModal && passwordForm) {
        const passwordSubmitBtn = document.getElementById('pwSubmitBtn');
        const passwordSubmitLabel = document.getElementById('pwSubmitLabel');
        const passwordName = document.getElementById(`pw${capitalize(singular)}Name`);
        let passwordRecordId = null;

        document.querySelectorAll('.pw-btn').forEach((button) => {
            button.addEventListener('click', () => {
                passwordRecordId = button.dataset[`${singular}Id`];
                if (!passwordRecordId) return;

                passwordForm.reset();
                clearErrors(passwordForm);
                if (passwordName) {
                    passwordName.textContent = button.dataset[`${singular}Name`] || '';
                }
                openModal(passwordModal);
            });
        });

        passwordModal.querySelectorAll('[data-close-pw]').forEach((element) => {
            element.addEventListener('click', () => closeModal(passwordModal));
        });

        passwordForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (!passwordRecordId) return;

            clearErrors(passwordForm);
            if (passwordSubmitBtn) passwordSubmitBtn.disabled = true;
            if (passwordSubmitLabel) passwordSubmitLabel.textContent = 'Updating…';

            const payload = {};
            passwordForm.querySelectorAll('input, select, textarea').forEach((element) => {
                if (!element.name && !element.id) return;
                const field = (element.name || element.id).replace(/^pw_/, '');
                payload[field] = element.value;
            });

            try {
                const result = await adminRpc(resource, 'change-password', payload, { id: passwordRecordId });
                if (result.success) {
                    closeModal(passwordModal);
                    sessionStorage.setItem(`${resource}_flash`, result.message || 'Password updated.');
                    location.reload();
                    return;
                }

                setErrors(passwordForm, result.errors || {});
                showAlert(alertEl, 'error', result.message || 'Password update failed.');
            } catch (error) {
                const response = error.response?.data;
                setErrors(passwordForm, response?.errors || {});
                showAlert(alertEl, 'error', response?.message || 'Password update failed.');
            } finally {
                if (passwordSubmitBtn) passwordSubmitBtn.disabled = false;
                if (passwordSubmitLabel) passwordSubmitLabel.textContent = 'Update Password';
            }
        });
    }

    if (confirmModal) {
        let pendingId = null;
        let pendingAction = null;

        document.querySelectorAll('.deactivate-btn, .delete-btn').forEach((button) => {
            button.addEventListener('click', () => {
                const id = button.dataset[`${singular}Id`] ?? button.dataset[`${singular}ID`];
                if (!id) return;

                pendingId = String(id);
                pendingAction = button.classList.contains('deactivate-btn') ? 'toggle-active' : 'delete';

                const nameEl =
                    document.getElementById(`confirm${capitalize(singular)}Name`) ||
                    document.getElementById(`delete${capitalize(singular)}Name`) ||
                    document.getElementById(`confirm${capitalize(singular)}Number`) ||
                    document.getElementById(`delete${capitalize(singular)}Number`);

                if (nameEl) {
                    nameEl.textContent = button.dataset[`${singular}Name`] || button.dataset[`${singular}Number`] || '';
                }

                openModal(confirmModal);
            });
        });

        confirmModal.querySelectorAll('[data-close-confirm], [data-close-delete]').forEach((element) => {
            element.addEventListener('click', () => closeModal(confirmModal));
        });

        const confirmBtn = document.getElementById('confirmDeactivateBtn') || document.getElementById('confirmDeleteBtn');
        const confirmLabel = document.getElementById('confirmDeactivateLabel') || document.getElementById('confirmDeleteLabel');

        if (confirmBtn) {
            confirmBtn.addEventListener('click', async () => {
                if (!pendingId || !pendingAction) return;

                confirmBtn.disabled = true;
                const originalText = confirmLabel ? confirmLabel.textContent : '';
                if (confirmLabel) {
                    confirmLabel.textContent = pendingAction === 'toggle-active' ? 'Archiving…' : 'Deleting…';
                }

                try {
                    const result = await adminRpc(resource, pendingAction, {}, { id: pendingId });
                    if (result.success) {
                        closeModal(confirmModal);
                        sessionStorage.setItem(`${resource}_flash`, result.message || 'Done.');
                        location.reload();
                        return;
                    }

                    closeModal(confirmModal);
                    showAlert(alertEl, 'error', result.message || 'Action failed.');
                } catch (error) {
                    closeModal(confirmModal);
                    showAlert(alertEl, 'error', error.response?.data?.message || 'Action failed.');
                } finally {
                    confirmBtn.disabled = false;
                    if (confirmLabel) confirmLabel.textContent = originalText;
                    pendingId = null;
                    pendingAction = null;
                }
            });
        }
    }

    document.querySelectorAll('.reactivate-btn').forEach((button) => {
        button.addEventListener('click', async () => {
            const id = button.dataset[`${singular}Id`] ?? button.dataset[`${singular}ID`];
            if (!id) return;

            button.disabled = true;
            try {
                const result = await adminRpc(resource, 'toggle-active', {}, { id });
                if (result.success) {
                    sessionStorage.setItem(`${resource}_flash`, result.message || 'Reactivated.');
                    location.reload();
                    return;
                }
                showAlert(alertEl, 'error', result.message || 'Action failed.');
            } catch (error) {
                showAlert(alertEl, 'error', error.response?.data?.message || 'Action failed.');
            } finally {
                button.disabled = false;
            }
        });
    });

    const flash = sessionStorage.getItem(`${resource}_flash`);
    if (flash) {
        sessionStorage.removeItem(`${resource}_flash`);
        showAlert(alertEl, 'success', flash);
    }
}