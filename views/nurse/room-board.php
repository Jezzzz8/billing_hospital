<?php


require_once __DIR__ . '/../../controllers/NurseController.php';

$controller = new NurseController($pdo);
$rooms = $controller->getAllRooms();
$roomTypes = $controller->getRoomsByType();


$roomsByType = [];
foreach ($rooms as $r) {
    $roomsByType[$r['room_type_name']][] = $r;
}


$statusCounts = [];
foreach ($rooms as $r) {
    $status = $r['status_name'];
    $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;
}
?>

<div class="mb-8">
    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Room Status Board</h1>
    <p class="mt-1 text-sm text-slate-500">Visual overview of all rooms and their current status.</p>
</div>


<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
    <?php foreach ($statusCounts as $status => $count): ?>
        <?php
        $colorClass = match($status) {
            'Available' => 'bg-emerald-50 border-emerald-200 text-emerald-700',
            'Occupied' => 'bg-rose-50 border-rose-200 text-rose-700',
            'Maintenance' => 'bg-amber-50 border-amber-200 text-amber-700',
            'Reserved' => 'bg-blue-50 border-blue-200 text-blue-700',
            default => 'bg-slate-50 border-slate-200 text-slate-700',
        };
        ?>
        <div class="rounded-xl border p-5 <?= $colorClass ?>">
            <p class="text-xs font-medium uppercase tracking-wide opacity-80"><?= htmlspecialchars($status) ?></p>
            <p class="mt-2 text-3xl font-bold"><?= $count ?></p>
        </div>
    <?php endforeach; ?>
</div>


<?php foreach ($roomsByType as $typeName => $typeRooms): ?>
    <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-slate-900"><?= htmlspecialchars($typeName) ?></h3>
            <span class="text-sm text-slate-500"><?= count($typeRooms) ?> room(s)</span>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3">
            <?php foreach ($typeRooms as $r): ?>
                <div class="rounded-xl border-2 p-4 transition hover:shadow-md"
                     style="background-color: <?= htmlspecialchars($r['color_code'] ?? '#6b7280') ?>0D;
                            border-color: <?= htmlspecialchars($r['color_code'] ?? '#6b7280') ?>40;">
                    <div class="flex items-center justify-between mb-2">
                        <p class="font-bold text-slate-900"><?= htmlspecialchars($r['room_number']) ?></p>
                        <span class="w-2.5 h-2.5 rounded-full" style="background-color: <?= htmlspecialchars($r['color_code'] ?? '#6b7280') ?>"></span>
                    </div>
                    <p class="text-xs font-medium" style="color: <?= htmlspecialchars($r['color_code'] ?? '#6b7280') ?>">
                        <?= htmlspecialchars($r['status_name']) ?>
                    </p>
                    <p class="text-xs text-slate-500 mt-1">
                        <?php if (!empty($r['floor_level_name'])): ?>
                            Floor <?= htmlspecialchars($r['floor_level_name']) ?>
                        <?php endif; ?>
                        <?php if (!empty($r['floor_level_name']) && !empty($r['building_name'])): ?> · <?php endif; ?>
                        <?= htmlspecialchars($r['building_name'] ?? '') ?>
                    </p>
                    <p class="text-xs text-slate-600 mt-2 font-medium">₱<?= number_format((float)$r['rate_per_day'], 2) ?>/day</p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endforeach; ?>