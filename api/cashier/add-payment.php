<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/../../includes/ws_notify.php';
require_once __DIR__ . '/../../controllers/CashierController.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user']) || !in_array((int)$_SESSION['user']['role_id'], [1, 4], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}

$controller = new CashierController($pdo);

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing statement id.']);
    exit;
}

try {
    $reference = $controller->addPayment($id, $controller->input());

    $stmt = $pdo->prepare(
        'SELECT balance_amount, amount_paid, total_amount
         FROM `billing_statement` WHERE statement_id = ? LIMIT 1'
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    $balance    = $row ? (float)$row['balance_amount'] : 0;
    $amountPaid = $row ? (float)$row['amount_paid']    : 0;
    $total      = $row ? (float)$row['total_amount']   : 0;

    ws_broadcast('billing.payment.added', [
        'statement_id' => $id,
        'reference'    => $reference,
        'new_balance'  => $balance,
        'amount_paid'  => $amountPaid,
    ], [1, 4]);

    echo json_encode([
        'success'      => true,
        'message'      => 'Payment recorded.',
        'reference'    => $reference,
        'new_balance'  => $balance,
        'amount_paid'  => $amountPaid,
        'total_amount' => $total,
    ]);
} catch (Throwable $e) {
    $status = in_array($e->getCode(), [400, 404, 409, 422], true) ? $e->getCode() : 500;
    if ($status === 500) {
        error_log('[cashier/add-payment] ' . $e->getMessage());
    }
    http_response_code($status);
    echo json_encode([
        'success' => false,
        'message' => $status === 500 ? 'Could not record payment.' : $e->getMessage(),
    ]);
}