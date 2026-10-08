<?php
$doctorPage = $doctorPage ?? 'dashboard';
$doctorAdmissionId = (int)($_GET['admission_id'] ?? 0);
?>
<div id="doctor-app" data-page="<?= htmlspecialchars($doctorPage, ENT_QUOTES, 'UTF-8') ?>" data-api-url="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>/api/doctors.php" data-base-url="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>" data-admission-id="<?= $doctorAdmissionId ?>">
    <div class="rounded-xl border border-slate-200 bg-white p-8 text-center text-sm text-slate-500" role="status">Loading clinical data…</div>
</div>