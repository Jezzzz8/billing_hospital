<?php


require_once __DIR__ . '/../../controllers/NurseController.php';

$controller = new NurseController($pdo);
$admissions = $controller->getAdmissions();

$activeCount     = count(array_filter($admissions, fn($a) => (int)$a['admission_id'] && (int)$a['admission_status_id'] !== 2));
$dischargedCount = count($admissions) - $activeCount;
?>

<div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Admissions</h1>
        <p class="mt-1 text-sm text-slate-500">All patient admissions and their current status.</p>
    </div>
    <a href="<?= BASE_URL ?>/index.php?page=nurse-admission-create"
       class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        New Admission
    </a>
</div>


<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6 max-w-xl">
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Active Admissions</p>
        <p class="mt-2 text-2xl font-bold text-emerald-600"><?= $activeCount ?></p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Discharged</p>
        <p class="mt-2 text-2xl font-bold text-slate-400"><?= $dischargedCount ?></p>
    </div>
</div>


<div class="bg-white rounded-xl border border-slate-200 p-4 mb-4">
    <div class="flex flex-col sm:flex-row sm:items-end gap-3">
        <div class="flex-1">
            <label class="block text-xs font-medium text-slate-500 mb-1.5">Search</label>
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" id="filterSearch" placeholder="Search patient name, room number…"
                       class="w-full rounded-lg border border-slate-300 pl-9 pr-3 py-2 text-sm
                              focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>

        <button type="button" id="filterClear"
                class="hidden items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 self-start sm:self-auto">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
            Clear
        </button>
    </div>

    <div id="filterSummary" class="hidden mt-3 pt-3 border-t border-slate-100 text-xs text-slate-500">
        Showing <strong id="filteredCount" class="text-slate-900">0</strong> of <strong id="totalCount" class="text-slate-900">0</strong> admissions
    </div>
</div>


<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="text-left px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Patient</th>
                    <th class="text-left px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Room</th>
                    <th class="text-left px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Admitted</th>
                    <th class="text-left px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Status</th>
                    <th class="text-center px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Transfers</th>
                    <th class="text-right px-6 py-3.5 font-semibold text-slate-700 text-xs uppercase tracking-wide">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($admissions as $a):
                    $isDischarged = (int)$a['admission_status_id'] === 2;
                ?>
                    <tr class="hover:bg-slate-50 admission-row"
                        data-search="<?= htmlspecialchars(strtolower($a['first_name'] . ' ' . $a['last_name'] . ' ' . ($a['room_number'] ?? ''))) ?>">

                        <td class="px-6 py-4">
                            <p class="font-semibold text-slate-900"><?= htmlspecialchars($a['first_name'] . ' ' . $a['last_name']) ?></p>
                            <p class="text-xs text-slate-500">#<?= (int)$a['admission_id'] ?></p>
                        </td>

                        <td class="px-6 py-4">
                            <?php if (!empty($a['room_number'])): ?>
                                <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-medium"
                                      style="background-color: <?= htmlspecialchars($a['room_status_color'] ?? '#6b7280') ?>1A;
                                             color: <?= htmlspecialchars($a['room_status_color'] ?? '#6b7280') ?>;
                                             border-color: <?= htmlspecialchars($a['room_status_color'] ?? '#6b7280') ?>40;">
                                    <span class="w-1.5 h-1.5 rounded-full" style="background-color: <?= htmlspecialchars($a['room_status_color'] ?? '#6b7280') ?>"></span>
                                    <?= htmlspecialchars($a['room_number']) ?>
                                </span>
                                <p class="text-xs text-slate-500 mt-1"><?= htmlspecialchars($a['room_type_name'] ?? '') ?></p>
                            <?php else: ?>
                                <span class="text-xs text-amber-600">No room assigned</span>
                            <?php endif; ?>
                        </td>

                        <td class="px-6 py-4 text-slate-600">
                            <?= htmlspecialchars(date('M j, Y g:i A', strtotime($a['admission_datetime']))) ?>
                        </td>

                        <td class="px-6 py-4">
                            <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-medium"
                                  style="background-color: <?= htmlspecialchars($a['admission_status_color'] ?? '#6b7280') ?>1A;
                                         color: <?= htmlspecialchars($a['admission_status_color'] ?? '#6b7280') ?>;
                                         border-color: <?= htmlspecialchars($a['admission_status_color'] ?? '#6b7280') ?>40;">
                                <?= htmlspecialchars($a['admission_status_name'] ?? '—') ?>
                            </span>
                        </td>

                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-slate-100 text-slate-700 text-xs font-semibold">
                                <?= (int)$a['total_room_transfers'] ?>
                            </span>
                        </td>

                        <td class="px-6 py-4 text-right whitespace-nowrap">
                            <div class="inline-flex items-center gap-1.5">
                                <?php if (!$isDischarged): ?>
                                    <a href="<?= BASE_URL ?>/index.php?page=nurse-room-assignments&admission_id=<?= (int)$a['admission_id'] ?>"
                                       class="inline-flex items-center gap-1.5 rounded-lg border border-blue-200 bg-blue-50 px-2.5 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-100">
                                        Manage
                                    </a>
                                <?php else: ?>
                                    <span class="text-xs text-slate-400">
                                        Discharged <?= htmlspecialchars(date('M j, Y', strtotime($a['discharge_datetime'] ?? 'now'))) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div id="emptyState" class="hidden text-center py-16">
        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-slate-100 text-slate-400 mb-3">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
        </div>
        <p class="text-sm font-medium text-slate-700">No admissions match your search</p>
        <p class="text-xs text-slate-500 mt-1">Try adjusting your search.</p>
    </div>
</div>

<script>
(function() {
    const searchInput = document.getElementById('filterSearch');
    const clearBtn = document.getElementById('filterClear');
    const rows = document.querySelectorAll('.admission-row');
    const summary = document.getElementById('filterSummary');
    const filteredCount = document.getElementById('filteredCount');
    const totalCount = document.getElementById('totalCount');
    const emptyState = document.getElementById('emptyState');

    function filterRows() {
        const q = searchInput.value.toLowerCase().trim();
        let visible = 0;

        rows.forEach(row => {
            const search = row.dataset.search || '';
            if (!q || search.includes(q)) {
                row.style.display = '';
                visible++;
            } else {
                row.style.display = 'none';
            }
        });

        if (q) {
            summary.classList.remove('hidden');
            clearBtn.classList.remove('hidden');
            clearBtn.classList.add('flex');
            filteredCount.textContent = visible;
            totalCount.textContent = rows.length;
        } else {
            summary.classList.add('hidden');
            clearBtn.classList.remove('hidden');
            clearBtn.classList.remove('flex');
        }

        emptyState.classList.toggle('hidden', visible > 0);
    }

    searchInput.addEventListener('input', filterRows);
    clearBtn.addEventListener('click', () => {
        searchInput.value = '';
        filterRows();
    });
})();
</script>