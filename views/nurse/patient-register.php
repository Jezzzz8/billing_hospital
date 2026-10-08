<?php


require_once __DIR__ . '/../../controllers/NurseController.php';

$controller = new NurseController($pdo);
$genders = $controller->getGenders();
?>

<div class="mb-8">
    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Register New Patient</h1>
    <p class="mt-1 text-sm text-slate-500">Enter the patient's personal information to create a new record.</p>
</div>

<div id="alert" class="hidden mb-5 rounded-lg px-4 py-3 text-sm border"></div>

<div class="bg-white rounded-xl border border-slate-200 p-6 max-w-3xl">
    <form id="registerForm" method="POST" novalidate>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">First Name <span class="text-rose-500">*</span></label>
                <input type="text" id="first_name" name="first_name" required
                       class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <p class="mt-1.5 text-xs text-red-600 hidden" data-error-for="first_name"></p>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Last Name <span class="text-rose-500">*</span></label>
                <input type="text" id="last_name" name="last_name" required
                       class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <p class="mt-1.5 text-xs text-red-600 hidden" data-error-for="last_name"></p>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Gender <span class="text-rose-500">*</span></label>
                <select id="gender_id" name="gender_id" required
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">— Select gender —</option>
                    <?php foreach ($genders as $g): ?>
                        <option value="<?= (int)$g['gender_id'] ?>"><?= htmlspecialchars($g['gender_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="mt-1.5 text-xs text-red-600 hidden" data-error-for="gender_id"></p>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Birth Date</label>
                <input type="date" id="birth_date" name="birth_date"
                       class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
                <input type="email" id="email" name="email"
                       class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <p class="mt-1.5 text-xs text-red-600 hidden" data-error-for="email"></p>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Contact Number</label>
                <input type="text" id="contact_number" name="contact_number"
                       class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Address</label>
                <input type="text" id="address" name="address"
                       class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Emergency Contact</label>
                <input type="text" id="emergency_contact" name="emergency_contact"
                       class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Emergency Contact Number</label>
                <input type="text" id="emergency_contact_number" name="emergency_contact_number"
                       class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Medical History</label>
                <textarea id="medical_history" name="medical_history" rows="3"
                          class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
            </div>
        </div>

        <div class="mt-6 flex items-center justify-end gap-3">
            <a href="<?= BASE_URL ?>/index.php?page=nurse-patient-search"
               class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Cancel
            </a>
            <button type="submit" id="submitBtn"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60">
                <span id="submitLabel">Register Patient</span>
            </button>
        </div>
    </form>
</div>

<script>
document.getElementById('registerForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const btn = document.getElementById('submitBtn');
    const label = document.getElementById('submitLabel');
    const alertEl = document.getElementById('alert');

    btn.disabled = true;
    label.textContent = 'Registering…';
    alertEl.classList.add('hidden');

    document.querySelectorAll('[data-error-for]').forEach(el => {
        el.textContent = '';
        el.classList.add('hidden');
    });

    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());

    try {
        const baseUrl = document.body.dataset.baseUrl;
        const response = await axios.post(`${baseUrl}/api/nurses/patient-register.php`, data, {
            headers: { 'Content-Type': 'application/json' }
        });

        if (response.data.success) {
            alertEl.className = 'mb-5 rounded-lg px-4 py-3 text-sm border border-emerald-200 bg-emerald-50 text-emerald-800';
            alertEl.textContent = response.data.message;
            alertEl.classList.remove('hidden');

            setTimeout(() => {
                window.location.href = `${baseUrl}/index.php?page=nurse-admission-create&patient_id=${response.data.patient_id}`;
            }, 1000);
        }
    } catch (err) {
        const res = err.response?.data;
        if (res?.errors) {
            Object.entries(res.errors).forEach(([field, msg]) => {
                const el = document.querySelector(`[data-error-for="${field}"]`);
                if (el) { el.textContent = msg; el.classList.remove('hidden'); }
            });
        }
        alertEl.className = 'mb-5 rounded-lg px-4 py-3 text-sm border border-rose-200 bg-rose-50 text-rose-800';
        alertEl.textContent = res?.message || 'Something went wrong.';
        alertEl.classList.remove('hidden');
    } finally {
        btn.disabled = false;
        label.textContent = 'Register Patient';
    }
});
</script>