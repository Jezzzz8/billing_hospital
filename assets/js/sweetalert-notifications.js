(function () {
    function showFallbackDialog(message, title = 'Notice') {
        const overlay = document.createElement('div');
        overlay.className = 'fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/50 p-4';
        overlay.setAttribute('role', 'alertdialog');
        overlay.setAttribute('aria-modal', 'true');
        overlay.setAttribute('aria-label', title);

        const dialog = document.createElement('div');
        dialog.className = 'w-full max-w-md rounded-2xl bg-white p-6 shadow-xl';

        const heading = document.createElement('h2');
        heading.className = 'text-lg font-semibold text-slate-900';
        heading.textContent = title;

        const body = document.createElement('p');
        body.className = 'mt-2 whitespace-pre-line text-sm text-slate-600';
        body.textContent = String(message);

        const button = document.createElement('button');
        button.className = 'mt-5 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700';
        button.textContent = 'OK';
        button.addEventListener('click', () => overlay.remove(), { once: true });

        dialog.append(heading, body, button);
        overlay.append(dialog);
        document.body.append(overlay);
        button.focus();
        return Promise.resolve({ isConfirmed: true, value: undefined });
    }

    window.showAppAlert = (message, options = {}) => {
        if (!window.Swal) {
            console.error('SweetAlert2 is unavailable; showing the application dialog fallback.');
            return showFallbackDialog(message, options.title || 'Notice');
        }
        return window.Swal.fire({
            icon: options.icon || (/could not|failed|invalid|error|unable|insufficient/i.test(String(message)) ? 'error' : 'info'),
            title: options.title || 'Notice',
            text: String(message),
            confirmButtonText: 'OK',
        });
    };

    window.confirmWithSweetAlert = async (message, options = {}) => {
        if (!window.Swal) {
            return window.confirm(String(message));
        }
        const result = await window.Swal.fire({
            icon: options.icon || 'warning',
            title: options.title || 'Please confirm',
            text: String(message),
            showCancelButton: true,
            confirmButtonText: options.confirmButtonText || 'Continue',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
        });
        return result.isConfirmed;
    };

    window.promptWithSweetAlert = async (message, options = {}) => {
        if (!window.Swal) {
            return window.prompt(String(message), options.inputValue || '');
        }
        const result = await window.Swal.fire({
            title: options.title || 'Please provide details',
            text: String(message),
            input: 'textarea',
            inputValue: options.inputValue || '',
            inputPlaceholder: options.placeholder || '',
            showCancelButton: true,
            confirmButtonText: options.confirmButtonText || 'Continue',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
        });
        return result.isConfirmed ? result.value : null;
    };

    window.alert = message => window.showAppAlert(message);
})();
