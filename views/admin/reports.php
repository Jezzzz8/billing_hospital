<?php

$patientCount = (int)$pdo->query('SELECT COUNT(*) FROM `patient`')->fetchColumn();
$admissionCount = (int)$pdo->query('SELECT COUNT(*) FROM `admission`')->fetchColumn();
$roomCount = (int)$pdo->query('SELECT COUNT(*) FROM `room`')->fetchColumn();
$revenue = (float)$pdo->query('SELECT COALESCE(SUM(amount_paid), 0) FROM `billing_statement`')->fetchColumn();
?>

<div class="mb-8">
    <h2 class="text-xl font-bold text-slate-900">Reports</h2>
    <p class="mt-1 text-sm text-slate-500">Overview of hospital activity.</p>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Total Patients</p>
        <p class="mt-2 text-3xl font-bold text-slate-900"><?= $patientCount ?></p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Admissions</p>
        <p class="mt-2 text-3xl font-bold text-slate-900"><?= $admissionCount ?></p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Rooms</p>
        <p class="mt-2 text-3xl font-bold text-slate-900"><?= $roomCount ?></p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Revenue Collected</p>
        <p class="mt-2 text-2xl font-bold text-slate-900">₱<?= number_format($revenue, 2) ?></p>
    </div>
</div>