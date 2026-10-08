document.addEventListener('click', async (event) => {
    const button = event.target.closest('#logoutBtn');
    if (!button) {
        return;
    }

    event.preventDefault();
    button.disabled = true;

    const baseUrl = document.body?.dataset?.baseUrl || '';

    try {
        await axios.post(`${baseUrl}/api/logout.php`, {}, {
            headers: { 'Content-Type': 'application/json' },
            withCredentials: true,
        });
    } catch (error) {
        console.warn('[logout] request failed, redirecting anyway', error);
    } finally {
        window.location.href = `${baseUrl}/index.php`;
    }
});