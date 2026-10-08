<?php


require_once __DIR__ . '/../../controllers/NurseController.php';

$controller = new NurseController($pdo);
$searchQuery = $_GET['q'] ?? '';
$patients = $searchQuery ? $controller->searchPatients($searchQuery) : [];
?>

<div class="mb-8">
    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Find Patient</h1>
    <p class="mt-1 text-sm text-slate-500">Search for existing patient records by name, contact, or email.</p>
</div>


<div class="bg-white rounded-xl border border-slate-200 p-4 mb-6">
    <form method="GET" action="<?= BASE_URL ?>/index.php" class="flex flex-col sm:flex-row gap-3">
        <input type="hidden" name="page" value="nurse-patient-search">
        <div class="flex-1">
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" name="q" value="<?= htmlspecialchars($searchQuery) ?>"
                       placeholder="Search by name, contact number, or email…"
                       class="w-full rounded-lg border border-slate-300 pl-9 pr-3 py-2.5 text-sm
                              focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>
        <button type="submit"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
            Search
        </button>
        <a href="<?= BASE_URL ?>/index.php?page=nurse-patient-register"
           class="inline-flex items-center justify-center gap-2 rounded-lg border border-emerald-300 bg-emerald-50 px-5 py-2.5 text-sm font-semibold text-emerald-700 hover:bg-emerald-100">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
            </svg>
            Register New Patient
        </a>
    </form>
</div>


<?php if ($searchQuery): ?>
    <div class="mb-4 flex items-center justify-between">
        <p class="text-sm text-slate-500">
            Found <strong class="text-slate-900"><?= count($patients) ?></strong> result(s) for "<?= htmlspecialchars($searchQuery) ?>"
        </p>
    </div>

    <?php if (empty($patients)): ?>
        <div class="bg-white rounded-xl border border-slate-200 p-12 text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-100 text-slate-400 mb-4">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <p class="text-lg font-medium text-slate-700">No patients found</p>
            <p class="text-sm text-slate-500 mt-1">Try a different search term or register a new patient.</p>
            <a href="<?= BASE_URL ?>/index.php?page=nurse-patient-register"
               class="inline-flex items-center gap-2 mt-6 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
                Register New Patient
            </a>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="text-left px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Patient</th>
                            <th class="text-left px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Contact</th>
                            <th class="text-left px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Admission Status</th>
                            <th class="text-right px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($patients as $p): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-slate-100 text-slate-700 flex items-center justify-center font-semibold text-sm shrink-0">
                                            <?= strtoupper(substr($p['first_name'], 0, 1) . substr($p['last_name'], 0, 1)) ?>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-semibold text-slate-900 truncate">
                                                <?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?>
                                            </p>
                                            <p class="text-xs text-slate-500">
                                                <?= htmlspecialchars($p['gender_name']) ?> · <?= htmlspecialchars($p['birth_date'] ?? '—') ?>
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-slate-700 text-xs"><?= htmlspecialchars($p['email'] ?? '—') ?></p>
                                    <p class="text-xs text-slate-500"><?= htmlspecialchars($p['contact_number'] ?? '') ?></p>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if (!empty($p['admission_id'])): ?>
                                        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium border"
                                              style="background-color: <?= htmlspecialchars($p['admission_status_color'] ?? '#6b7280') ?>1A;
                                                     color: <?= htmlspecialchars($p['admission_status_color'] ?? '#6b7280') ?>;
                                                     border-color: <?= htmlspecialchars($p['admission_status_color'] ?? '#6b7280') ?>40;">
                                            <span class="w-1.5 h-1.5 rounded-full" style="background-color: <?= htmlspecialchars($p['admission_status_color'] ?? '#6b7280') ?>"></span>
                                            <?= htmlspecialchars($p['admission_status_name'] ?? '—') ?>
                                        </span>
                                        <p class="text-xs text-slate-500 mt-1">
                                            <?= htmlspecialchars(date('M j, Y', strtotime($p['admission_datetime']))) ?>
                                        </p>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-400 italic">Not admitted</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-2">
                                        <?php if (empty($p['admission_id'])): ?>
                                            <a href="<?= BASE_URL ?>/index.php?page=nurse-admission-create&patient_id=<?= (int)$p['patient_id'] ?>"
                                               class="inline-flex items-center gap-1.5 rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-100">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                                </svg>
                                                Admit
                                            </a>
                                        <?php else: ?>
                                            <a href="<?= BASE_URL ?>/index.php?page=nurse-room-assignments&admission_id=<?= (int)$p['admission_id'] ?>"
                                               class="inline-flex items-center gap-1.5 rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-700 hover:bg-amber-100">
                                                Manage Admission
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
<?php else: ?>
    <div class="bg-white rounded-xl border border-slate-200 p-12 text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-blue-50 text-blue-500 mb-4">
            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>
        <p class="text-lg font-medium text-slate-700">Search for a patient</p>
        <p class="text-sm text-slate-500 mt-1">Enter a name, contact number, or email above to find existing records.</p>
    </div>
<?php endif; ?>