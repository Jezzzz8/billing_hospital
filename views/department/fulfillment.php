<?php
require_once __DIR__ . '/../../controllers/DepartmentController.php';

$departmentId = (int)($_SESSION['user']['department_id'] ?? 0);
$controller = new DepartmentController($pdo, $departmentId);
$queue = $controller->getQueue();
$requestId = (int)($_GET['request_id'] ?? 0);
$details = $requestId > 0 ? $controller->getRequestDetails($requestId) : null;
?>

<div class="mb-8">
    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Department Fulfillment</h1>
    <p class="mt-1 text-sm text-slate-500">Record the actual quantity fulfilled so charges are created only from delivered items.</p>
</div>

<?php if (empty($queue)): ?>
    <div class="rounded-xl border border-slate-200 bg-white p-10 text-center text-slate-500">
        No service requests are currently assigned to this department.
    </div>
<?php else: ?>
    <div class="grid grid-cols-1 xl:grid-cols-[1.05fr_1.35fr] gap-6">
        <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
            <div class="border-b border-slate-200 bg-slate-50 px-5 py-4">
                <h2 class="text-lg font-semibold text-slate-900">Assigned Requests</h2>
            </div>
            <div class="divide-y divide-slate-100">
                <?php foreach ($queue as $request): ?>
                    <?php $selected = $details && (int)$details['request_id'] === (int)$request['request_id']; ?>
                    <div class="px-5 py-4 <?= $selected ? 'bg-blue-50/60' : '' ?>">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="font-semibold text-slate-900">Request #<?= (int)$request['request_id'] ?> · <?= htmlspecialchars(trim((string)($request['first_name'] ?? '')) . ' ' . trim((string)($request['last_name'] ?? ''))) ?></p>
                                <p class="text-xs text-slate-500">Admission #<?= (int)$request['admission_id'] ?> · <?= htmlspecialchars((string)($request['priority'] ?? 'Routine')) ?> · <?= htmlspecialchars((string)($request['status'] ?? 'Pending')) ?></p>
                            </div>
                            <div class="flex items-center gap-2">
                                <a href="<?= BASE_URL ?>/index.php?page=department-fulfillment&request_id=<?= (int)$request['request_id'] ?>" class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-medium text-blue-700 hover:bg-blue-100">
                                    View
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
            <?php if (!$details): ?>
                <div class="p-10 text-center text-slate-500">Select a request to record fulfillment.</div>
            <?php else: ?>
                <div class="border-b border-slate-200 bg-slate-50 px-5 py-4">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">Request #<?= (int)$details['request_id'] ?></h2>
                            <p class="text-sm text-slate-500"><?= htmlspecialchars(trim((string)($details['first_name'] ?? '')) . ' ' . trim((string)($details['last_name'] ?? ''))) ?> · Chief complaint: <?= htmlspecialchars((string)($details['chief_complaint'] ?? '—')) ?></p>
                        </div>
                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium <?= in_array((string)($details['status'] ?? ''), ['Pending', 'In Progress'], true) ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700' ?>">
                            <?= htmlspecialchars((string)($details['status'] ?? 'Pending')) ?>
                        </span>
                    </div>
                </div>

                <div class="p-5">
                    <div class="mb-5 overflow-hidden rounded-xl border border-slate-200">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 text-left text-slate-600">
                                <tr>
                                    <th class="px-4 py-3 font-medium">Item</th>
                                    <th class="px-4 py-3 font-medium">Requested</th>
                                    <th class="px-4 py-3 font-medium">Fulfilled</th>
                                    <th class="px-4 py-3 font-medium">Unit Price</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($details['items'] as $item): ?>
                                    <tr>
                                        <td class="px-4 py-3">
                                            <p class="font-medium text-slate-800"><?= htmlspecialchars((string)($item['item_name'] ?? '—')) ?></p>
                                            <p class="text-xs text-slate-500"><?= htmlspecialchars((string)($item['category_name'] ?? '')) ?></p>
                                        </td>
                                        <td class="px-4 py-3 text-slate-700"><?= (int)($item['quantity_requested'] ?? 0) ?></td>
                                        <td class="px-4 py-3 text-slate-700"><?= (int)($item['quantity_fulfilled'] ?? 0) ?></td>
                                        <td class="px-4 py-3 text-slate-700">₱<?= number_format((float)($item['default_price'] ?? 0), 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if (($details['status'] ?? '') === 'Pending'): ?>
                        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                            <p class="text-sm text-amber-800">Accept this request to begin processing and record fulfillment.</p>
                            <button type="button" id="acceptDepartmentRequest" data-request-id="<?= (int)$details['request_id'] ?>"
                                    class="mt-3 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                                Accept request
                            </button>
                        </div>
                    <?php elseif (($details['status'] ?? '') === 'In Progress'): ?>
                    <form method="post" action="<?= BASE_URL ?>/api/department.php" class="space-y-4" id="departmentFulfillmentForm">
                        <input type="hidden" name="operation" value="fulfill">
                        <input type="hidden" name="request_id" value="<?= (int)$details['request_id'] ?>">

                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">Notes</label>
                            <textarea name="notes" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Any relevant fulfillment notes..."></textarea>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-600">Record fulfillment</h3>
                            <?php foreach ($details['items'] as $index => $item): ?>
                                <div class="mb-3 grid grid-cols-1 gap-3 rounded-lg border border-slate-200 bg-white p-3 md:grid-cols-[1.4fr_1fr_1fr]">
                                    <div>
                                        <p class="font-medium text-slate-800"><?= htmlspecialchars((string)($item['item_name'] ?? '—')) ?></p>
                                        <p class="text-xs text-slate-500">Requested: <?= (int)($item['quantity_requested'] ?? 0) ?></p>
                                    </div>
                                    <label class="block">
                                        <span class="mb-1 block text-xs font-medium text-slate-600">Actual quantity</span>
                                        <input type="number" min="0" max="<?= max(0, (int)($item['quantity_requested'] ?? 0) - (int)($item['quantity_fulfilled'] ?? 0)) ?>" value="0" name="items[<?= $index ?>][quantity_fulfilled]" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    </label>
                                    <input type="hidden" name="items[<?= $index ?>][request_item_id]" value="<?= (int)($item['request_item_id'] ?? 0) ?>">
                                    <div class="flex items-end">
                                        <span class="text-xs text-slate-500">Remaining: <?= max(0, (int)($item['quantity_requested'] ?? 0) - (int)($item['quantity_fulfilled'] ?? 0)) ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
                                Save Fulfillment
                            </button>
                        </div>
                    </form>
                    <?php else: ?>
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                            This request is <?= htmlspecialchars((string)$details['status']) ?> and cannot receive additional fulfillment.
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    const acceptButton = document.getElementById('acceptDepartmentRequest');
    if (acceptButton) {
      acceptButton.addEventListener('click', async () => {
        acceptButton.disabled = true;
        try {
          const payload = new URLSearchParams({
            operation: 'accept',
            request_id: acceptButton.dataset.requestId,
          });
          const response = await fetch('<?= BASE_URL ?>/api/department.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: payload.toString(),
          });
          const result = await response.json();
          if (!response.ok || !result.success) {
            throw new Error(result.message || 'Request could not be accepted.');
          }
          window.location.reload();
        } catch (error) {
          alert(error.message);
          acceptButton.disabled = false;
        }
      });
    }

    const form = document.getElementById('departmentFulfillmentForm');
    if (!form) return;

    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      const formData = new FormData(form);
      const payload = new URLSearchParams();
      for (const [key, value] of formData.entries()) {
        payload.append(key, value);
      }

      const response = await fetch('<?= BASE_URL ?>/api/department.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body: payload.toString(),
      });

      const result = await response.json();
      if (!response.ok || !result.success) {
        alert(result.message || 'Fulfillment could not be saved.');
        return;
      }

      alert('Fulfillment saved successfully.');
      window.location.href = '<?= BASE_URL ?>/index.php?page=department-fulfillment';
    });
  });
</script>