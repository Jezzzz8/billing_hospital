<?php

require_once __DIR__ . '/includes/patient_portal_session.php';

$patientId = (int)($_SESSION['patient_portal']['patient_id'] ?? 0);
if ($patientId <= 0) {
    header('Location: ' . BASE_URL . '/patient-portal.php');
    exit;
}

require_once __DIR__ . '/config/connection.php';

$statementId = filter_input(INPUT_GET, 'statement_id', FILTER_VALIDATE_INT);
$statementCheck = $pdo->prepare(
    'SELECT 1
     FROM `billing_statement` bs
     INNER JOIN `admission` a ON a.admission_id = bs.admission_id
     WHERE bs.statement_id = ? AND a.patient_id = ?
     LIMIT 1'
);
$statementCheck->execute([(int)$statementId, $patientId]);
$statementOwned = (bool)$statementCheck->fetchColumn();

if (!$statementId || !$statementOwned) {
    http_response_code(404);
    $pageTitle = 'Statement not found';
} else {
    $pageTitle = 'Statement of Account';
}

$patientPortalMode = true;
$roleId = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= htmlspecialchars($pageTitle) ?> · Billing Hospital</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
</head>
<body class="min-h-screen bg-slate-100 font-sans antialiased">
    <main class="mx-auto max-w-5xl px-4 py-6 sm:px-6">
        <?php if (!$statementId || !$statementOwned): ?>
            <section class="mx-auto max-w-3xl rounded-xl border border-slate-200 bg-white p-8 text-center">
                <h1 class="text-lg font-semibold text-slate-900">Statement not found</h1>
                <p class="mt-2 text-sm text-slate-600">This statement is unavailable in your patient portal.</p>
                <a href="<?= BASE_URL ?>/patient-portal.php" class="mt-5 inline-flex rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white">Back to Patient Portal</a>
            </section>
        <?php else: ?>
            <?php $_GET['id'] = $statementId; ?>
            <?php require __DIR__ . '/views/cashier/receipt.php'; ?>
        <?php endif; ?>
    </main>
    <?php if ($statementId && $statementOwned): ?>
        <script>
            document.getElementById('downloadPatientStatement').addEventListener('click', async event => {
                const button = event.currentTarget;
                button.disabled = true;
                try {
                    const stylesheet = document.querySelector('link[rel="stylesheet"]');
                    const response = await fetch(stylesheet.href, { credentials: 'same-origin' });
                    if (!response.ok) throw new Error('Could not load the statement styles.');
                    const documentCopy = document.documentElement.cloneNode(true);
                    documentCopy.querySelector('#patientStatementActions').remove();
                    documentCopy.querySelectorAll('link[rel="stylesheet"], script').forEach(element => element.remove());
                    const embeddedStyles = document.createElement('style');
                    embeddedStyles.textContent = await response.text();
                    documentCopy.querySelector('head').append(embeddedStyles);
                    const file = new Blob(['<!doctype html>\n', documentCopy.outerHTML], { type: 'text/html;charset=utf-8' });
                    const url = URL.createObjectURL(file);
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = 'statement-of-account-<?= (int)$statementId ?>.html';
                    link.click();
                    setTimeout(() => URL.revokeObjectURL(url), 1000);
                } catch (error) {
                    window.alert(error.message || 'Could not download the statement. Please try again.');
                } finally {
                    button.disabled = false;
                }
            });
        </script>
    <?php endif; ?>
</body>
</html>
