<?php

require_once __DIR__ . '/../../controllers/CashierController.php';
require_once __DIR__ . '/../../controllers/ChargeSyncService.php';

$controller = new CashierController($pdo);
$statementId = (int)($_GET['id'] ?? 0);

if ($statementId > 0 && empty($patientPortalMode)) {
    try {
        (new ChargeSyncService($pdo))->syncStatement($statementId);
    } catch (Throwable $e) {
        error_log('[receipt] recompute failed: ' . $e->getMessage());
        echo '<div class="mx-auto max-w-3xl rounded-xl border border-rose-200 bg-rose-50 p-6 text-sm text-rose-800">'
            . 'The billing statement could not be refreshed. Please try again or contact an administrator.'
            . '</div>';
        return;
    }
}

$statement = $statementId ? $controller->getStatementDetails($statementId) : null;

if (!$statement) {
    echo '<div class="p-12 text-center"><p class="text-lg font-semibold">Statement not found.</p></div>';
    return;
}

$patientName   = trim(($statement['first_name'] ?? '') . ' ' . ($statement['last_name'] ?? ''));
$roomNumber    = $statement['room_number'] ?? 'N/A';
$roomRate      = (float)($statement['room_rate'] ?? 0);
$caseType      = $statement['case_type'] ?? ($statement['admission_type'] ?? 'In Patient');
$patientType   = $statement['patient_type'] ?? 'In Patient';
$phicStatus    = $statement['phic_status'] ?? 'Non-Member';
$hmoName       = $statement['hmo_name'] ?? '';
$companyName   = $statement['company_name'] ?? '';
$age           = (int)($statement['patient_age'] ?? ($statement['age'] ?? 0));

$attendingPhysicians = $pdo->prepare(
    'SELECT DISTINCT CONCAT("Dr. ", u.first_name, " ", u.last_name) AS name
     FROM `admission_doctor` ad
     INNER JOIN `doctor` d ON d.doctor_id = ad.doctor_id
     INNER JOIN `user` u ON u.user_id = d.user_id
     WHERE ad.admission_id = ? AND ad.doctor_role = "Attending"
     ORDER BY ad.assigned_datetime'
);
$attendingPhysicians->execute([(int)$statement['admission_id']]);
$attendingList = implode(', ', array_column($attendingPhysicians->fetchAll(), 'name'));

$admittedByName = trim(
    ($statement['admitted_by_first'] ?? '') . ' ' . ($statement['admitted_by_last'] ?? '')
);
if ($admittedByName === '') {
    $admittedByName = 'Unknown';
}

$allCategories = $pdo->query(
    'SELECT category_id, category_name
     FROM `charge_category`
     ORDER BY category_name'
)->fetchAll();

$categoryLookup = [];
foreach ($allCategories as $cat) {
    $categoryLookup[strtoupper(trim($cat['category_name']))] = $cat['category_name'];
}

$chargeGroups = [];
foreach ($statement['charges'] as $charge) {
    $lineTotal = (float)($charge['line_total'] ?? 0);
    $category  = strtoupper(trim($charge['category_name'] ?? 'OTHER'));

    if (!isset($chargeGroups[$category])) {
        $chargeGroups[$category] = [
            'category_name' => $charge['category_name'] ?? 'OTHER',
            'is_prof_fee'   => false,
            'items'         => [],
            'total'         => 0.0,
            'detail_items'  => [],
        ];
    }

    $isProfFee = str_contains($category, 'PROFESSIONAL')
        || str_contains($category, 'DOCTOR')
        || str_contains($category, 'ATTENDING');

    if ($isProfFee) {
        $chargeGroups[$category]['is_prof_fee'] = true;
    }

    $chargeGroups[$category]['total'] += $lineTotal;

    if ($lineTotal == 0.0) {
        continue;
    }

    if ($isProfFee) {
        $doctorName = $charge['item_name'];
        $note = trim((string)($charge['notes'] ?? ''));
        if (preg_match('/Consultation\s*[—-]\s*(Dr\.\s*[^—]+?)(?:\s*[—-]\s*source=|$)/i', $note, $m)) {
            $doctorName = trim($m[1]);
        } elseif (preg_match('/Dr\.\s*([A-Za-z\-\'\. ]+)/i', $note, $m2)) {
            $doctorName = 'Dr. ' . trim($m2[1]);
        }

        $key = strtolower($doctorName);
        if (!isset($chargeGroups[$category]['items'][$key])) {
            $chargeGroups[$category]['items'][$key] = [
                'description' => $doctorName,
                'item_code'   => $charge['item_code'] ?? '',
                'quantity'    => 0,
                'line_total'  => 0.0,
            ];
        }
        $chargeGroups[$category]['items'][$key]['quantity']   += (int)$charge['quantity'];
        $chargeGroups[$category]['items'][$key]['line_total'] += $lineTotal;

        $detailKey = strtolower($charge['item_name']);
        if (!isset($chargeGroups[$category]['detail_items'][$detailKey])) {
            $chargeGroups[$category]['detail_items'][$detailKey] = [
                'description' => $charge['item_name'],
                'item_code'   => $charge['item_code'] ?? '',
                'quantity'    => 0,
                'line_total'  => 0.0,
            ];
        }
        $chargeGroups[$category]['detail_items'][$detailKey]['quantity']   += (int)$charge['quantity'];
        $chargeGroups[$category]['detail_items'][$detailKey]['line_total'] += $lineTotal;
        continue;
    }

    $displayName = $charge['item_name'];
    $key = strtolower($displayName);
    if (!isset($chargeGroups[$category]['items'][$key])) {
        $chargeGroups[$category]['items'][$key] = [
            'description' => $displayName,
            'item_code'   => $charge['item_code'] ?? '',
            'quantity'    => 0,
            'line_total'  => 0.0,
        ];
    }

    $chargeGroups[$category]['items'][$key]['quantity']   += (int)$charge['quantity'];
    $chargeGroups[$category]['items'][$key]['line_total'] += $lineTotal;
}

$allCategoryNames = $categoryLookup;
foreach ($chargeGroups as $key => $group) {
    if (!isset($allCategoryNames[$key])) {
        $allCategoryNames[$key] = $group['category_name'];
    }
}
ksort($allCategoryNames);

$hospitalBillRows = [];
$professionalFeeRows = [];

foreach ($allCategoryNames as $key => $displayName) {
    $isProfFee = str_contains($key, 'PROFESSIONAL')
        || str_contains($key, 'DOCTOR')
        || str_contains($key, 'ATTENDING');

    if (!isset($chargeGroups[$key])) {
        if ($isProfFee) {
            $professionalFeeRows[] = [
                'category_name' => $displayName,
                'description'   => $displayName,
                'amount'        => 0.0,
                'discount'      => 0.0,
                'phic'          => 0.0,
                'hmo'           => 0.0,
                'net'           => 0.0,
                'items'         => [],
            ];
        } else {
            $hospitalBillRows[$displayName] = [
                'category_name' => $displayName,
                'amount'        => 0.0,
                'discount'      => 0.0,
                'philhealth'    => 0.0,
                'guarantor'     => 0.0,
                'due'           => 0.0,
                'items'         => [],
            ];
        }
        continue;
    }

    $group = $chargeGroups[$key];
    $items = [];
    foreach ($group['items'] as $item) {
        $items[] = $item;
    }

    if ($group['is_prof_fee']) {
        foreach ($items as $doctorItem) {
            $professionalFeeRows[] = [
                'category_name' => $displayName,
                'description'   => $doctorItem['description'],
                'amount'        => $doctorItem['line_total'],
                'discount'      => 0.0,
                'phic'          => 0.0,
                'hmo'           => 0.0,
                'net'           => $doctorItem['line_total'],
                'items'         => [$doctorItem],
            ];
        }
    } else {
        $hospitalBillRows[$displayName] = [
            'category_name' => $displayName,
            'amount'        => $group['total'],
            'discount'      => 0.0,
            'philhealth'    => 0.0,
            'guarantor'     => 0.0,
            'due'           => 0.0,
            'items'         => $items,
        ];
    }
}

$statementTax        = (float)($statement['tax_amount'] ?? 0);
$statementSubtotal   = (float)($statement['subtotal_amount'] ?? 0);
$hasTax              = $statementTax > 0;

if ($hasTax) {
    $hospitalBillRows['TAX (12% VAT)'] = [
        'category_name' => 'TAX (12% VAT)',
        'amount'        => $statementTax,
        'discount'      => 0.0,
        'philhealth'    => 0.0,
        'guarantor'     => 0.0,
        'due'           => $statementTax,
        'items'         => [[
            'description' => 'Value-Added Tax',
            'item_code'   => '',
            'quantity'    => 1,
            'line_total'  => $statementTax,
        ]],
    ];
}

$totalGrossHospital = 0.0;
foreach ($hospitalBillRows as $row) {
    $totalGrossHospital += (float)$row['amount'];
}

$totalPhilhealth = (float)($statement['applied_philhealth'] ?? $statement['philhealth_deduction'] ?? 0);
$totalDiscount   = (float)($statement['applied_discount'] ?? $statement['government_discount'] ?? 0);
$totalGuarantor  = (float)($statement['applied_insurance'] ?? $statement['insurance_coverage_amount'] ?? 0)
                 + (float)($statement['applied_hmo'] ?? $statement['hmo_discount'] ?? 0);

$hospitalChargeBase = $totalGrossHospital;
if ($hasTax) {
    $hospitalChargeBase -= $statementTax;
}

foreach ($hospitalBillRows as $cat => &$row) {
    if ($cat === 'TAX (12% VAT)') {
        continue;
    }
    if ($hospitalChargeBase > 0 && (float)$row['amount'] > 0) {
        $weight = (float)$row['amount'] / $hospitalChargeBase;
        $row['discount']   = round($totalDiscount * $weight, 2);
        $row['philhealth'] = round($totalPhilhealth * $weight, 2);
        $row['guarantor']  = round($totalGuarantor * $weight, 2);
        $row['due']        = round(max(0, (float)$row['amount'] - $row['discount'] - $row['philhealth'] - $row['guarantor']), 2);
    }
}
unset($row);

$totalHospitalGross    = 0.0;
$totalHospitalDiscount = 0.0;
$totalHospitalPhic     = 0.0;
$totalHospitalHmo      = 0.0;
$totalHospitalDue      = 0.0;

foreach ($hospitalBillRows as $row) {
    $totalHospitalGross    += (float)$row['amount'];
    $totalHospitalDiscount += (float)$row['discount'];
    $totalHospitalPhic     += (float)$row['philhealth'];
    $totalHospitalHmo      += (float)$row['guarantor'];
    $totalHospitalDue      += (float)$row['due'];
}

$totalProfFeeAmount   = 0.0;
$totalProfFeeDiscount = 0.0;
$totalProfFeePhic     = 0.0;
$totalProfFeeHmo      = 0.0;
$totalProfFeeNet      = 0.0;

foreach ($professionalFeeRows as $pf) {
    $totalProfFeeAmount   += (float)$pf['amount'];
    $totalProfFeeDiscount += (float)$pf['discount'];
    $totalProfFeePhic     += (float)$pf['phic'];
    $totalProfFeeHmo      += (float)$pf['hmo'];
    $totalProfFeeNet      += (float)$pf['net'];
}

$netAmountDue = (float)($statement['total_amount'] ?? 0);
$amountPaid   = (float)($statement['amount_paid'] ?? 0);
$balanceDue   = (float)($statement['balance_amount'] ?? 0);

$statementPayments = $statement['payments'] ?? [];
$paymentsByRecency = $statementPayments;
usort($paymentsByRecency, function ($a, $b) {
    return strcmp($b['payment_datetime'] ?? '', $a['payment_datetime'] ?? '');
});
$latestPayment = $paymentsByRecency[0] ?? null;

$cashierName = '';
if ($latestPayment) {
    $first = trim((string)($latestPayment['receiver_first'] ?? ''));
    $last  = trim((string)($latestPayment['receiver_last']  ?? ''));
    $cashierName = trim($first . ' ' . $last);
    if ($cashierName === '' && !empty($latestPayment['receiver_username'])) {
        $cashierName = (string)$latestPayment['receiver_username'];
    }
}
if ($cashierName === '') {
    $cashierName = 'Authorized Cashier';
}

$detailRows = [];

foreach ($hospitalBillRows as $categoryName => $row) {
    foreach ($row['items'] as $item) {
        $detailRows[] = [
            'item_name'  => $item['description'],
            'item_code'  => $item['item_code'] ?? '',
            'line_total' => (float)$item['line_total'],
        ];
    }
}

foreach ($chargeGroups as $key => $group) {
    if (!$group['is_prof_fee']) continue;
    foreach ($group['detail_items'] as $item) {
        $detailRows[] = [
            'item_name'  => $item['description'],
            'item_code'  => $item['item_code'] ?? '',
            'line_total' => (float)$item['line_total'],
        ];
    }
}

usort($detailRows, function ($a, $b) {
    return strcasecmp($a['item_name'], $b['item_name']);
});
?>

<style>
    .ink-title {
        font-weight: 800;
        letter-spacing: 0.12em;
        color: #111;
        text-shadow:
            0.35px 0 0 rgba(0, 0, 0, 0.55),
            -0.25px 0.2px 0 rgba(0, 0, 0, 0.35),
            0.15px -0.2px 0 rgba(0, 0, 0, 0.25);
    }

    .ink-heading {
        font-weight: 800;
        color: #0f172a;
        text-shadow:
            0.2px 0 0 rgba(0, 0, 0, 0.4),
            0 0.15px 0 rgba(0, 0, 0, 0.25);
    }

    .ink-strong {
        font-weight: 800;
        color: #0b1220;
        text-shadow: 0.15px 0 0 rgba(0, 0, 0, 0.45);
    }

    .ink-soft {
        color: #1f2937;
        text-shadow: 0 0.15px 0 rgba(0, 0, 0, 0.18);
    }

    .ink-label {
        font-weight: 700;
        color: #1e293b;
        text-shadow: 0.15px 0.1px 0 rgba(0, 0, 0, 0.3);
    }

    .ink-rule-thick {
        height: 3px;
        background-color: #0b1220;
        box-shadow: 0 1px 0 rgba(0, 0, 0, 0.35);
    }

    .ink-rule-thin {
        height: 1px;
        background-color: #0b1220;
        box-shadow: 0 0.5px 0 rgba(0, 0, 0, 0.3);
    }

    .ink-rule-double {
        border-top: 3px double #0b1220;
    }

    .ink-row td {
        border-color: rgba(15, 23, 42, 0.08);
    }

    .ink-sign-line {
        border-bottom: 1.5px solid #0b1220;
    }

    .ink-blue {
        color: #1e3a8a;
        text-shadow: 0.15px 0 0 rgba(30, 58, 138, 0.35);
    }

    .ink-stamp {
        color: #7f1d1d;
        text-shadow:
            0.4px 0 0 rgba(127, 29, 29, 0.6),
            0 0.3px 0 rgba(127, 29, 29, 0.4);
    }

    .payment-banner {
        background-color: #f0f9ff;
        border-left: 4px solid #1e3a8a;
        padding: 8px 12px;
        margin-bottom: 10px;
    }

    @media print {
        .ink-title,
        .ink-heading,
        .ink-strong,
        .ink-soft,
        .ink-label,
        .ink-blue,
        .ink-stamp {
            text-shadow: none !important;
        }

        .payment-banner {
            background-color: transparent !important;
        }

        .page-break {
            page-break-before: always;
        }
    }
</style>

<div <?= !empty($patientPortalMode) ? 'id="patientStatementActions"' : '' ?> class="mb-6 flex justify-between items-center max-w-4xl mx-auto print:hidden">
    <div>
        <h1 class="text-xl font-bold text-slate-800">Statement Preview</h1>
        <p class="text-xs text-slate-500">Statement #<?= (int)$statement['statement_id'] ?></p>
    </div>
    <div class="flex items-center gap-2">
        <?php if (!empty($patientPortalMode)): ?>
            <a href="<?= BASE_URL ?>/patient-portal.php"
               class="inline-flex items-center gap-2 rounded border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
                Back to Patient Portal
            </a>
        <?php elseif ((int)$roleId === 1): ?>
            <a href="<?= BASE_URL ?>/index.php?page=billing"
               class="inline-flex items-center gap-2 rounded border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
                Back to Billing
            </a>
        <?php else: ?>
            <a href="<?= BASE_URL ?>/index.php?page=cashier-statements"
               class="inline-flex items-center gap-2 rounded border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
                Back to Statements
            </a>
        <?php endif; ?>
        <?php if (!empty($patientPortalMode)): ?>
            <button id="downloadPatientStatement" type="button"
                    class="inline-flex items-center gap-2 rounded border border-blue-200 bg-white px-4 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-50">
                Download Statement
            </button>
        <?php endif; ?>
        <button type="button" onclick="window.print()"
                class="inline-flex items-center gap-2 rounded bg-slate-800 px-4 py-2 text-xs font-semibold text-white hover:bg-slate-700">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
            </svg>
            Print Statement
        </button>
    </div>
</div>

<div class="bg-white p-8 max-w-4xl mx-auto text-black font-sans text-xs leading-tight shadow-sm border border-slate-200 print:border-none print:shadow-none print:p-0">

    <div class="flex items-center justify-center mb-6">
        <div class="text-center">
            <h2 class="ink-title text-sm tracking-[0.25em] uppercase">Statement of Account</h2>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-x-8 gap-y-1 mb-4 text-[11px] uppercase">
        <div class="space-y-1">
            <div class="flex"><span class="w-36 ink-label">CASE NO</span><span class="ink-soft">: <?= sprintf('%010d', $statement['admission_id']) ?></span></div>
            <?php if (!empty($patientPortalMode)): ?>
                <div class="flex"><span class="w-36 ink-label">DATE OF BIRTH</span><span class="ink-soft">: <?= !empty($statement['birth_date']) ? htmlspecialchars(date('M j Y', strtotime($statement['birth_date']))) : '—' ?></span></div>
            <?php else: ?>
                <div class="flex"><span class="w-36 ink-label">PATIENT NO</span><span class="ink-soft">: <?= sprintf('%010d', $statement['patient_id'] ?? $statement['statement_id']) ?></span></div>
            <?php endif; ?>
            <div class="flex text-sm leading-none py-0.5">
                <span class="w-36 ink-label">PATIENT NAME</span>
                <span class="ink-strong">: <?= htmlspecialchars($patientName) ?></span>
            </div>
            <div class="flex"><span class="w-36 ink-label">AGE</span><span class="ink-soft">: <?= (int)$age ?></span></div>
            <div class="flex"><span class="w-36 ink-label">ATT. PHYSICIAN</span><span class="ink-soft">: <?= htmlspecialchars($attendingList !== '' ? $attendingList : '—') ?></span></div>
            <div class="flex"><span class="w-36 ink-label">ADDRESS</span><span class="ink-soft">: <?= htmlspecialchars($statement['address'] ?? 'N/A') ?></span></div>
            <div class="flex pt-2"><span class="w-64 ink-label">PERSON RESPONSIBLE FOR THE ACCOUNT:</span><span></span></div>
        </div>

        <div class="space-y-1">
            <div class="flex"><span class="w-36 ink-label">ROOM/BED #</span><span class="ink-soft">: <?= htmlspecialchars($roomNumber) ?></span></div>
            <div class="flex"><span class="w-36 ink-blue border-b border-blue-900 inline-block">ROOM RATE</span><span class="ink-soft">: <?= number_format($roomRate, 2) ?></span></div>
            <div class="flex"><span class="w-36 ink-label">ADMITTED</span><span class="ink-soft">: <?= htmlspecialchars(date('M j Y g:iA', strtotime($statement['admission_datetime']))) ?></span></div>
            <div class="flex"><span class="w-36 ink-label">DISCHARGE</span><span class="ink-soft">: <?= !empty($statement['discharge_datetime']) ? htmlspecialchars(date('M j Y g:iA', strtotime($statement['discharge_datetime']))) : '' ?></span></div>
            <div class="flex"><span class="w-36 ink-label">PHIC</span><span class="ink-soft">: <?= htmlspecialchars($phicStatus) ?></span></div>
            <div class="flex"><span class="w-36 ink-label">HMO</span><span class="ink-soft">: <?= htmlspecialchars($hmoName) ?></span></div>
            <div class="flex"><span class="w-36 ink-label">COMPANY</span><span class="ink-soft">: <?= htmlspecialchars($companyName) ?></span></div>
            <div class="flex"><span class="w-36 ink-label">CASE TYPE</span><span class="ink-soft">: <?= htmlspecialchars($caseType) ?></span></div>
            <div class="flex"><span class="w-36 ink-label">PATIENT TYPE</span><span class="ink-soft">: <span class="ink-blue border-b border-blue-900 inline-block"><?= htmlspecialchars($patientType) ?></span></span></div>
        </div>
    </div>

    <table class="w-full text-left border-collapse my-2">
        <thead>
            <tr class="ink-heading text-[10px] uppercase">
                <th class="py-1 border-t-[3px] border-b-2 border-slate-900">HOSPITAL BILL</th>
                <th class="py-1 text-right border-t-[3px] border-b-2 border-slate-900">AMOUNT</th>
                <th class="py-1 text-right border-t-[3px] border-b-2 border-slate-900">DISCOUNT</th>
                <th class="py-1 text-right ink-blue border-t-[3px] border-b-2 border-slate-900">PHILHEALTH</th>
                <th class="py-1 text-right ink-blue border-t-[3px] border-b-2 border-slate-900">CORPORATE GUARANTOR</th>
                <th class="py-1 text-right border-t-[3px] border-b-2 border-slate-900">DUE FROM PATIENT</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-200/60 ink-soft text-[11px] uppercase">
            <?php if (empty($hospitalBillRows)): ?>
                <tr>
                    <td colspan="6" class="py-2 text-center text-slate-400">NO HOSPITAL CHARGES RECORDED</td>
                </tr>
            <?php else: ?>
                <?php foreach ($hospitalBillRows as $categoryName => $row): ?>
                    <tr>
                        <td class="py-0.5 ink-blue border-b border-blue-900 inline-block max-w-max"><?= htmlspecialchars($categoryName) ?></td>
                        <td class="py-0.5 text-right ink-soft"><?= number_format((float)$row['amount'], 2) ?></td>
                        <td class="py-0.5 text-right ink-soft"><?= (float)$row['discount'] > 0 ? number_format((float)$row['discount'], 2) : '-' ?></td>
                        <td class="py-0.5 text-right ink-soft"><?= (float)$row['philhealth'] > 0 ? number_format((float)$row['philhealth'], 2) : '-' ?></td>
                        <td class="py-0.5 text-right ink-soft"><?= (float)$row['guarantor'] > 0 ? number_format((float)$row['guarantor'], 2) : '-' ?></td>
                        <td class="py-0.5 text-right ink-soft"><?= number_format((float)$row['due'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr class="ink-strong text-[11px] uppercase">
                <td class="py-1 ink-blue border-t-2 border-b-2 border-slate-900">HOSPITAL GROSS CHARGES</td>
                <td class="py-1 text-right border-t-2 border-b-2 border-slate-900"><?= number_format($totalHospitalGross, 2) ?></td>
                <td class="py-1 text-right border-t-2 border-b-2 border-slate-900"><?= number_format($totalHospitalDiscount, 2) ?></td>
                <td class="py-1 text-right border-t-2 border-b-2 border-slate-900"><?= $totalHospitalPhic > 0 ? number_format($totalHospitalPhic, 2) : '-' ?></td>
                <td class="py-1 text-right border-t-2 border-b-2 border-slate-900"><?= $totalHospitalHmo > 0 ? number_format($totalHospitalHmo, 2) : '-' ?></td>
                <td class="py-1 text-right border-t-2 border-b-2 border-slate-900"><?= number_format($totalHospitalDue, 2) ?></td>
            </tr>
        </tfoot>
    </table>

    <div class="italic text-[11px] ink-label my-1">Less:</div>

    <div class="flex justify-between items-center ink-rule-thin pb-1 mb-4 ink-strong text-[11px] uppercase">
        <span class="ink-blue">NET HOSPITAL BILL</span>
        <span><?= number_format($totalHospitalDue, 2) ?></span>
    </div>

    <table class="w-full text-left border-collapse my-2">
        <thead>
            <tr class="ink-heading text-[10px] uppercase">
                <th class="py-1 ink-blue border-t-2 border-b-2 border-slate-900">PROFESSIONAL FEES</th>
                <th class="py-1 text-right border-t-2 border-b-2 border-slate-900">AMOUNT</th>
                <th class="py-1 text-right border-t-2 border-b-2 border-slate-900">DISCOUNT ESP/PWD/SC</th>
                <th class="py-1 text-right border-t-2 border-b-2 border-slate-900">PHIC</th>
                <th class="py-1 text-right border-t-2 border-b-2 border-slate-900">HMO</th>
                <th class="py-1 text-right border-t-2 border-b-2 border-slate-900">NET</th>
            </tr>
        </thead>
        <tbody class="ink-soft text-[11px] uppercase">
            <?php if (empty($professionalFeeRows)): ?>
                <tr>
                    <td class="py-1">—</td>
                    <td class="py-1 text-right">0.00</td>
                    <td class="py-1 text-right">-</td>
                    <td class="py-1 text-right">-</td>
                    <td class="py-1 text-right">-</td>
                    <td class="py-1 text-right">0.00</td>
                </tr>
            <?php else: ?>
                <?php foreach ($professionalFeeRows as $pf): ?>
                    <tr>
                        <td class="py-1 ink-soft">
                            <?= htmlspecialchars($pf['description']) ?>
                        </td>
                        <td class="py-1 text-right ink-soft"><?= number_format((float)$pf['amount'], 2) ?></td>
                        <td class="py-1 text-right ink-soft"><?= (float)$pf['discount'] > 0 ? number_format((float)$pf['discount'], 2) : '-' ?></td>
                        <td class="py-1 text-right ink-soft"><?= (float)$pf['phic'] > 0 ? number_format((float)$pf['phic'], 2) : '-' ?></td>
                        <td class="py-1 text-right ink-soft"><?= (float)$pf['hmo'] > 0 ? number_format((float)$pf['hmo'], 2) : '-' ?></td>
                        <td class="py-1 text-right ink-soft"><?= number_format((float)$pf['net'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr class="ink-strong text-[11px] uppercase">
                <td class="py-1 border-t-2 border-b-2 border-slate-900">TOTAL PROFESSIONAL FEES</td>
                <td class="py-1 text-right border-t-2 border-b-2 border-slate-900"><?= number_format($totalProfFeeAmount, 2) ?></td>
                <td class="py-1 text-right border-t-2 border-b-2 border-slate-900"><?= number_format($totalProfFeeDiscount, 2) ?></td>
                <td class="py-1 text-right border-t-2 border-b-2 border-slate-900" colspan="2"></td>
                <td class="py-1 text-right border-t-2 border-b-2 border-slate-900"><?= number_format($totalProfFeeNet, 2) ?></td>
            </tr>
        </tfoot>
    </table>

    <div class="flex justify-between items-center ink-rule-double py-1.5 my-4 ink-strong text-xs uppercase">
        <span class="ink-blue">NET AMOUNT DUE</span>
        <span class="text-sm border-b-4 border-double border-slate-900 pb-0.5"><?= number_format($netAmountDue, 2) ?></span>
    </div>

    <div class="mt-6 mb-2">
        <h3 class="ink-heading text-[11px] uppercase tracking-wide ink-blue border-b border-slate-900 pb-1 mb-2">
            PARTICULARS
        </h3>
        <table class="w-full text-left border-collapse text-[10px]">
            <thead>
                <tr class="border-b border-slate-400 text-[9px] font-bold uppercase">
                    <th class="py-1 pr-2 text-left ink-blue">DESCRIPTION</th>
                    <th class="py-1 text-right ink-blue">AMOUNT</th>
                </tr>
            </thead>
            <tbody class="ink-soft text-[10px]">
                <?php if (empty($detailRows)): ?>
                    <tr>
                        <td colspan="2" class="py-2 text-center text-slate-400">NO CHARGES RECORDED</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($detailRows as $detail): ?>
                        <?php if ($detail['line_total'] == 0.0) continue; ?>
                        <tr class="border-b border-slate-200/70">
                            <td class="py-1 pr-2 ink-soft">
                                <?= htmlspecialchars($detail['item_name']) ?>
                                <?php if (!empty($detail['item_code'])): ?>
                                    <span class="text-slate-400">(<?= htmlspecialchars($detail['item_code']) ?>)</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-1 text-right ink-strong"><?= number_format((float)$detail['line_total'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="grid grid-cols-12 gap-4 mt-8 pt-2 text-[10px]">
        <div class="col-span-6 space-y-1">
            <p class="ink-heading">Important:</p>
            <p class="ink-soft leading-tight">
                Please keep this patient's <span class="ink-blue border-b border-blue-900">SOA</span> for your copy and present, together with <span class="ink-blue border-b border-blue-900">PHIC</span> issued benefits Payment Notice. Upon refund of PHIC benefits (if any), you can claim your refund check at the Accounting Office 2 weeks after submission of complete documents.
            </p>
            <p class="ink-soft">Releasing of cheques for refund is schedule every Wednesday.</p>
        </div>

        <div class="col-span-6 flex flex-col justify-between pl-2">
            <div class="text-left">
                <span class="ink-label uppercase text-[9px]">REMARKS:</span>
            </div>

            <div class="grid grid-cols-2 gap-4 mt-12">
                <div class="flex flex-col text-center">
                    <div class="ink-sign-line pb-1 mb-1 ink-strong text-[10px] min-h-[16px] flex items-end justify-center">
                        <?= htmlspecialchars($admittedByName) ?>
                    </div>
                    <p class="ink-label uppercase text-[9px] leading-tight">
                        STAFF NURSE<br>&nbsp;
                    </p>
                </div>
                <div class="flex flex-col text-center">
                    <div class="ink-sign-line pb-1 mb-1 min-h-[16px]"></div>
                    <p class="ink-label uppercase text-[9px] leading-tight">
                        SIGNATURE / OVER PRINTED NAME<br>PATIENT / REPRESENTATIVE
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($statementPayments)): ?>

<div class="bg-white p-8 max-w-4xl mx-auto text-black font-sans text-xs leading-tight shadow-sm border border-slate-200 print:border-none print:shadow-none print:p-0 mt-8 page-break">

    <div class="flex items-center justify-center mb-6">
        <div class="text-center">
            <h2 class="ink-title text-sm tracking-[0.25em] uppercase">Payment Records</h2>
        </div>
    </div>

    <div class="payment-banner mb-4">
        <p class="text-[10px] ink-soft uppercase">
            <span class="ink-label">STATEMENT NO</span> : <?= (int)$statement['statement_id'] ?>
            &nbsp;&nbsp;·&nbsp;&nbsp;
            <span class="ink-label">PATIENT</span> : <?= htmlspecialchars($patientName) ?>
            &nbsp;&nbsp;·&nbsp;&nbsp;
            <span class="ink-label">CASE NO</span> : <?= sprintf('%010d', $statement['admission_id']) ?>
        </p>
    </div>

    <table class="w-full text-left border-collapse my-2">
        <thead>
            <tr class="ink-heading text-[10px] uppercase">
                <th class="py-1 border-t-[3px] border-b-2 border-slate-900">DATE</th>
                <th class="py-1 border-t-[3px] border-b-2 border-slate-900">TYPE</th>
                <th class="py-1 border-t-[3px] border-b-2 border-slate-900">REFERENCE</th>
                <th class="py-1 text-right border-t-[3px] border-b-2 border-slate-900">AMOUNT</th>
            </tr>
        </thead>
        <tbody class="ink-soft text-[11px] uppercase">
            <?php foreach ($statementPayments as $payment): ?>
                <tr class="border-b border-slate-200/70">
                    <td class="py-1 ink-soft"><?= htmlspecialchars(date('M j, Y', strtotime($payment['payment_datetime']))) ?></td>
                    <td class="py-1 ink-soft"><?= htmlspecialchars($payment['type_name'] ?? '—') ?></td>
                    <td class="py-1 ink-soft font-mono text-[10px]"><?= htmlspecialchars($payment['transaction_reference'] ?? '—') ?></td>
                    <td class="py-1 text-right ink-strong"><?= number_format((float)$payment['amount'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="ink-strong text-[11px] uppercase">
                <td class="py-1 border-t-2 border-b-2 border-slate-900" colspan="3">TOTAL PAID</td>
                <td class="py-1 text-right border-t-2 border-b-2 border-slate-900"><?= number_format($amountPaid, 2) ?></td>
            </tr>
        </tfoot>
    </table>

    <div class="mt-6 border-t-2 border-slate-900 pt-3">
        <div class="flex justify-between items-center text-[11px] ink-soft mb-1">
            <span class="ink-label uppercase">TOTAL AMOUNT BILLED</span>
            <span><?= number_format($netAmountDue, 2) ?></span>
        </div>
        <div class="flex justify-between items-center text-[11px] ink-soft mb-1">
            <span class="ink-label uppercase">LESS: TOTAL PAYMENTS</span>
            <span>(<?= number_format($amountPaid, 2) ?>)</span>
        </div>
    </div>

    <div class="flex justify-between items-center ink-rule-double py-2 my-4 ink-strong text-sm uppercase">
        <span class="ink-blue">BALANCE DUE</span>
        <span class="text-base border-b-4 border-double border-slate-900 pb-0.5"><?= number_format($balanceDue, 2) ?></span>
    </div>

    <div class="mt-8 grid grid-cols-12 gap-4 text-[10px]">
        <div class="col-span-6 space-y-1">
            <p class="ink-heading">Payment Acknowledgment:</p>
            <p class="ink-soft leading-tight">
                This document serves as the official record of payments received on the above statement. Please retain a copy for your records.
            </p>
        </div>

        <div class="col-span-6 flex flex-col justify-end pl-2">
            <div class="grid grid-cols-2 gap-4 text-center mt-12">
                <div class="flex flex-col text-center">
                    <div class="ink-sign-line pb-1 mb-1 ink-strong text-[10px] min-h-[16px] flex items-end justify-center">
                        <?= htmlspecialchars($cashierName) ?>
                    </div>
                    <p class="ink-label uppercase text-[9px] leading-tight">
                        CASHIER / AUTHORIZED SIGNATORY
                    </p>
                </div>
                <div class="flex flex-col text-center">
                    <div class="ink-sign-line pb-1 mb-1 ink-strong text-[10px] min-h-[16px] flex items-end justify-center">
                        <?= htmlspecialchars(date('M j, Y g:i A', strtotime($latestPayment['payment_datetime'] ?? 'now'))) ?>
                    </div>
                    <p class="ink-label uppercase text-[9px] leading-tight">
                        DATE & TIME RECEIVED
                    </p>
                </div>
            </div>
        </div>
    </div>

</div>

<?php endif; ?>

<style media="print">
    @page { size: portrait; margin: 10mm; }
    body { background: white !important; font-family: ui-sans-serif, system-ui, sans-serif !important; }
    .print\:hidden { display: none !important; }
    aside, header, nav, button, [data-close-manage], #sidebarToggle, #sidebarOverlay { display: none !important; }
    main { padding: 0 !important; max-width: none !important; }
</style>