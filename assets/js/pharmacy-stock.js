document.addEventListener('spa:page-loaded', event => {
    if (event.detail.page !== 'pharmacy-stock') return;

    const baseUrl = document.body.dataset.baseUrl || '';
    const alertBox = document.getElementById('pharmacyStockAlert');
    const showAlert = (message, success = false) => {
        alertBox.className = `mb-5 rounded-lg border px-4 py-3 text-sm ${success
            ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
            : 'border-rose-200 bg-rose-50 text-rose-800'}`;
        alertBox.textContent = message;
        alertBox.classList.remove('hidden');
    };

    document.querySelectorAll('[data-stock-save]').forEach(button => {
        button.addEventListener('click', async () => {
            const itemId = Number(button.dataset.stockSave);
            const row = document.querySelector(`[data-stock-row="${itemId}"]`);
            const input = row?.querySelector('[data-stock-adjustment]');
            const adjustment = Number(input?.value);
            if (!Number.isInteger(adjustment) || adjustment === 0) {
                showAlert('Enter a non-zero whole-number stock adjustment.');
                return;
            }

            button.disabled = true;
            try {
                const previousQuantity = Number(row.querySelector('[data-stock-quantity]').textContent.replace(/,/g, ''));
                const response = await fetch(`${baseUrl}/api/pharmacy-stock.php`, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                    body: JSON.stringify({
                        operation: 'adjust',
                        charge_item_id: itemId,
                        adjustment,
                    }),
                });
                const result = await response.json();
                if (!response.ok || !result.success) throw new Error(result.message || 'Stock adjustment failed.');
                const newQuantity = Number(result.stock_quantity);
                row.querySelector('[data-stock-quantity]').textContent = newQuantity.toLocaleString();
                const currentTotal = Number(document.querySelector('[data-total-stock]').textContent.replace(/,/g, ''));
                document.querySelector('[data-total-stock]').textContent = (currentTotal + newQuantity - previousQuantity).toLocaleString();
                const lowStockCount = document.querySelector('[data-low-stock-count]');
                const lowCount = Number(lowStockCount.textContent);
                if (previousQuantity <= 5 && newQuantity > 5) lowStockCount.textContent = String(Math.max(0, lowCount - 1));
                if (previousQuantity > 5 && newQuantity <= 5) lowStockCount.textContent = String(lowCount + 1);
                input.value = '';
                showAlert(result.message, true);
            } catch (error) {
                showAlert(error.message);
            } finally {
                button.disabled = false;
            }
        });
    });
});
