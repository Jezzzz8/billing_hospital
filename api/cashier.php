<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../controllers/CashierController.php';
require_once __DIR__ . '/../controllers/ChargeSyncService.php';
require_once __DIR__ . '/../includes/ws_notify.php';

if (empty($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
    exit;
}

$roleId = (int)($_SESSION['user']['role_id'] ?? 0);
if ($roleId !== 4 && $roleId !== 1) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}

$controller = new CashierController($pdo);

$operation = $_GET['operation'] ?? '';

$input = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        $input = $decoded;
    } elseif (isset($_POST['json'])) {
        $input = json_decode((string)$_POST['json'], true) ?: [];
    } else {
        $input = $_POST;
    }
    if (isset($input['operation'])) {
        $operation = $input['operation'];
    }
}

try {
    if (in_array($operation, ['dashboard', 'list', 'unpaid', 'summary', 'details'], true)) {
        $controller->prepareRunningBilling();
    }

    switch ($operation) {
        case 'dashboard':
            echo json_encode([
                'success' => true,
                'data' => [
                    'stats' => $controller->getDashboardStats(),
                    'recent_statements' => $controller->getRecentStatements(8),
                    'recent_admissions' => $controller->getRecentAdmissions(8),
                ],
            ]);
            break;

        case 'options':
            echo json_encode([
                'success' => true,
                'data' => [
                    'billing_statuses' => $controller->getBillingStatuses(),
                    'payment_types' => $controller->getPaymentTypes(),
                    'charge_items' => $controller->getChargeItems(),
                ],
            ]);
            break;

        case 'list':
            $statements = $controller->getAllStatements();
            foreach ($statements as $s) {
                try {
                    $controller->syncStatement((int)$s['statement_id']);
                } catch (Throwable $e) {
                    error_log('[cashier.php] sync failed for #' . $s['statement_id'] . ': ' . $e->getMessage());
                }
            }
            echo json_encode(['success' => true, 'data' => $controller->getAllStatements()]);
            break;

        case 'unpaid':
            echo json_encode(['success' => true, 'data' => $controller->getUnpaidStatements()]);
            break;

        case 'summary':
            $id = (int)($_GET['statement_id'] ?? $input['statement_id'] ?? 0);
            $summary = $controller->getStatementSummary($id);
            if ($summary === null) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Statement not found.']);
                exit;
            }
            echo json_encode(['success' => true, 'data' => $summary]);
            break;

        case 'details':
            $id = (int)($_GET['statement_id'] ?? $_GET['id'] ?? $input['statement_id'] ?? 0);
            (new ChargeSyncService($pdo))->syncStatement($id);
            $details = $controller->getStatementDetails($id);
            if ($details === null) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Statement not found.']);
                exit;
            }
            echo json_encode(['success' => true, 'data' => $details]);
            break;

        case 'create-statement':
            $controller->createStatement();
            ws_broadcast('billing.statement.created', [], [1, 4]);
            break;

        case 'update-deductions':
            $id = (int)($input['statement_id'] ?? $_GET['statement_id'] ?? 0);
            $controller->updateStatementDeductions($id, $input);
            break;

        case 'sync-charges':
            $id = (int)($input['statement_id'] ?? $_GET['statement_id'] ?? 0);
            $controller->syncStatement($id);
            echo json_encode(['success' => true, 'message' => 'Charges synchronized.', 'data' => null]);
            break;

        case 'add-charge':
            if ($roleId !== 1) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Cashiers cannot add charges.']);
                break;
            }
            $id = (int)($input['statement_id'] ?? $_GET['statement_id'] ?? 0);
            try {
                $controller->addCharge($id, $input);
                ws_broadcast('billing.charge.added', ['statement_id' => $id], [1]);
                echo json_encode(['success' => true, 'message' => 'Charge added.', 'data' => null]);
            } catch (Throwable $e) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            break;

        case 'remove-charge':
            $statementId = (int)($input['statement_id'] ?? 0);
            $chargeId = (int)($input['charge_id'] ?? 0);
            try {
                $controller->removeCharge($statementId, $chargeId);
                ws_broadcast('billing.charge.removed', ['statement_id' => $statementId, 'charge_id' => $chargeId], [1, 4]);
                echo json_encode(['success' => true, 'message' => 'Charge removed.', 'data' => null]);
            } catch (Throwable $e) {
                http_response_code($e->getCode() === 409 ? 409 : 422);
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            break;

        case 'add-payment':
            $id = (int)($input['statement_id'] ?? $_GET['statement_id'] ?? $_GET['id'] ?? 0);
            try {
                $reference = $controller->addPayment($id, $input);
                $details = $controller->getStatementSummary($id);
                ws_broadcast('billing.payment.added', [
                    'statement_id' => $id,
                    'reference'    => $reference,
                    'new_balance'  => $details['balance_amount'] ?? 0,
                ], [1, 4]);
                echo json_encode([
                    'success' => true,
                    'message' => 'Payment recorded.',
                    'reference' => $reference,
                    'new_balance' => $details['balance_amount'] ?? 0,
                    'amount_paid' => (float)($input['amount'] ?? 0),
                    'data' => null,
                ]);
            } catch (Throwable $e) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            break;

        case 'remove-payment':
            $statementId = (int)($input['statement_id'] ?? 0);
            $paymentId = (int)($input['payment_id'] ?? 0);
            try {
                $controller->removePayment($statementId, $paymentId);
                ws_broadcast('billing.payment.removed', ['statement_id' => $statementId, 'payment_id' => $paymentId], [1, 4]);
                echo json_encode(['success' => true, 'message' => 'Payment removed.', 'data' => null]);
            } catch (Throwable $e) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            break;

        case 'reports':
            echo json_encode(['success' => true, 'data' => $controller->getReportsData()]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Unknown operation: ' . $operation]);
    }
} catch (Throwable $e) {
    error_log('[cashier.php] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}