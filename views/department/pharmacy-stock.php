<?php

require_once __DIR__ . '/../../controllers/PharmacyStockController.php';

$controller = new PharmacyStockController(
    $pdo,
    (int)($_SESSION['user']['department_id'] ?? 0)
);
$stockItems = $controller->getStockItems();
$totalStock = array_sum(array_map(static fn(array $item): int => (int)$item['stock_quantity'], $stockItems));
$lowStockCount = count(array_filter($stockItems, static fn(array $item): bool => (int)$item['stock_quantity'] <= 5));
?>

<div class="mb-8">
    <h1 class="text-2xl font-bold tracking-tight text-slate-900">Pharmacy Stock</h1>
    <p class="mt-1 text-sm text-slate-500">Review available stock and record receipts or corrections. Positive adjustments add stock; negative adjustments remove stock.</p>
</div>

<div id="pharmacyStockAlert" class="hidden mb-5 rounded-lg border px-4 py-3 text-sm"></div>

<div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
    <div class="rounded-xl border border-slate-200 bg-white p-5">
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Stock items</p>
        <p class="mt-2 text-2xl font-bold text-slate-900"><?= count($stockItems) ?></p>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-5">
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Total units</p>
        <p class="mt-2 text-2xl font-bold text-blue-700" data-total-stock><?= number_format($totalStock) ?></p>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-5">
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Low stock (5 or fewer)</p>
        <p class="mt-2 text-2xl font-bold text-amber-700" data-low-stock-count><?= $lowStockCount ?></p>
    </div>
</div>

<div class="mb-4 rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">
    Stock changes are current on-hand adjustments, not a movement ledger. Enable stock tracking for Pharmacy items in Charge Items before they appear here.
</div>

<div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
    <?php if (!$stockItems): ?>
        <p class="p-10 text-center text-sm text-slate-500">No active Pharmacy stock items are configured.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Item</th>
                        <th class="px-5 py-3">Category</th>
                        <th class="px-5 py-3 text-right">On hand</th>
                        <th class="px-5 py-3">Adjustment</th>
                        <th class="px-5 py-3">Guidance</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($stockItems as $item): ?>
                        <?php $quantity = (int)$item['stock_quantity']; ?>
                        <tr data-stock-row="<?= (int)$item['charge_item_id'] ?>">
                            <td class="px-5 py-4">
                                <p class="font-semibold text-slate-900"><?= htmlspecialchars($item['item_name']) ?></p>
                                <p class="text-xs text-slate-500"><?= htmlspecialchars($item['item_code']) ?></p>
                            </td>
                            <td class="px-5 py-4 text-slate-600"><?= htmlspecialchars($item['category_name']) ?></td>
                            <td class="px-5 py-4 text-right font-semibold <?= $quantity <= 5 ? 'text-amber-700' : 'text-slate-900' ?>">
                                <span data-stock-quantity><?= number_format($quantity) ?></span>
                                <?= htmlspecialchars($item['unit_of_measure'] ?: 'units') ?>
                            </td>
                            <td class="px-5 py-4">
                                <input type="number" step="1" data-stock-adjustment
                                       placeholder="+10 / -2"
                                       aria-label="Stock adjustment for <?= htmlspecialchars($item['item_name']) ?>"
                                       class="w-32 rounded-lg border border-slate-300 px-3 py-2">
                            </td>
                            <td class="px-5 py-4 text-xs text-slate-500">Positive adds; negative removes</td>
                            <td class="px-5 py-4 text-right">
                                <button type="button" data-stock-save="<?= (int)$item['charge_item_id'] ?>"
                                        class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700">
                                    Save adjustment
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
