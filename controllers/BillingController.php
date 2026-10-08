<?php

require_once __DIR__ . '/ChargeSyncService.php';

class BillingController
{
    private PDO $pdo;

    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function getAll(): array
    {
        return $this->pdo->query(
            'SELECT bs.statement_id, bs.admission_id, bs.status_id, bs.statement_date,
                    bs.due_date, bs.subtotal_amount, bs.philhealth_deduction,
                    bs.insurance_coverage_amount, bs.hmo_discount,
                    bs.government_discount, bs.tax_amount, bs.total_amount,
                    bs.amount_paid, bs.balance_amount, bs.created_at, bs.notes,
                    bst.status_name, bst.color_code, bst.is_paid_status,
                    p.patient_id, p.first_name, p.last_name,
                    a.admission_datetime
             FROM `billing_statement` bs
             INNER JOIN `billing_status` bst ON bst.status_id = bs.status_id
             INNER JOIN `admission` a ON a.admission_id = bs.admission_id
             INNER JOIN `patient` p ON p.patient_id = a.patient_id
             ORDER BY bs.statement_id DESC'
        )->fetchAll();
    }

    public function getDetails(int $statementId): ?array
    {
        (new ChargeSyncService($this->pdo))->syncStatement($statementId);
        $stmt = $this->pdo->prepare(
            'SELECT bs.*, bst.status_name, bst.color_code, bst.is_paid_status,
                    p.first_name, p.last_name, p.patient_id,
                    a.admission_datetime, a.chief_complaint
             FROM `billing_statement` bs
             INNER JOIN `billing_status` bst ON bst.status_id = bs.status_id
             INNER JOIN `admission` a ON a.admission_id = bs.admission_id
             INNER JOIN `patient` p ON p.patient_id = a.patient_id
             WHERE bs.statement_id = ?
             LIMIT 1'
        );
        $stmt->execute([$statementId]);
        $statement = $stmt->fetch();
        if (!$statement) return null;

        $stmt = $this->pdo->prepare(
            'SELECT c.charge_id, c.charge_item_id, c.quantity, c.actual_price, c.source_type,
                    c.charge_datetime, c.notes,
                    ci.item_code, ci.item_name, ci.unit_of_measure,
                    (c.quantity * c.actual_price) AS line_total
             FROM `charge` c
             INNER JOIN `charge_item` ci ON ci.charge_item_id = c.charge_item_id
             WHERE c.statement_id = ?
             ORDER BY c.charge_id'
        );
        $stmt->execute([$statementId]);
        $statement['charges'] = $stmt->fetchAll();

        $stmt = $this->pdo->prepare(
            'SELECT p.payment_id, p.payment_type_id, p.amount, p.payment_datetime,
                    p.transaction_reference, p.notes,
                    pt.type_name
             FROM `payment` p
             INNER JOIN `payment_type` pt ON pt.payment_type_id = p.payment_type_id
             WHERE p.statement_id = ?
             ORDER BY p.payment_id'
        );
        $stmt->execute([$statementId]);
        $statement['payments'] = $stmt->fetchAll();

        return $statement;
    }

    public function getBillingStatuses(): array
    {
        return $this->pdo->query('SELECT status_id, status_name, color_code FROM `billing_status` ORDER BY status_id')->fetchAll();
    }

    public function getPaymentTypes(): array
    {
        return $this->pdo->query('SELECT payment_type_id, type_name FROM `payment_type` ORDER BY type_name')->fetchAll();
    }

    public function getChargeItems(): array
    {
        return $this->pdo->query(
            'SELECT ci.charge_item_id, ci.item_code, ci.item_name, ci.default_price,
                    ci.is_taxable, ci.unit_of_measure, cc.category_name
             FROM `charge_item` ci
             INNER JOIN `charge_category` cc ON cc.category_id = ci.category_id
             WHERE ci.is_active = 1
             ORDER BY ci.item_name'
        )->fetchAll();
    }

    public function create(): void
    {
        $this->json(409, [
            'success' => false,
            'message' => 'Billing statements are created automatically when an admission is opened.',
        ]);
    }

    public function update(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) $this->json(400, ['success' => false, 'message' => 'Missing statement id.']);

        $stmt = $this->pdo->prepare('SELECT * FROM `billing_statement` WHERE statement_id = ? LIMIT 1');
        $stmt->execute([$id]);
        $existingStatement = $stmt->fetch();
        if (!$existingStatement) $this->json(404, ['success' => false, 'message' => 'Statement not found.']);

        $data = $this->input();
        foreach (['philhealth_deduction', 'insurance_coverage_amount', 'hmo_discount', 'government_discount'] as $field) {
            if (array_key_exists($field, $data) && (!is_numeric($data[$field]) || (float)$data[$field] < 0)) {
                $this->json(422, ['success' => false, 'message' => 'Deductions must be non-negative amounts.']);
            }
        }

        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare(
                'UPDATE `billing_statement`
                 SET philhealth_deduction      = ?,
                     insurance_coverage_amount = ?,
                     hmo_discount              = ?,
                     government_discount       = ?,
                     due_date                  = ?,
                     notes                     = ?
                 WHERE statement_id = ?'
            );
            $stmt->execute([
                (float)($data['philhealth_deduction'] ?? $existingStatement['philhealth_deduction']),
                (float)($data['insurance_coverage_amount'] ?? $existingStatement['insurance_coverage_amount']),
                (float)($data['hmo_discount'] ?? $existingStatement['hmo_discount']),
                (float)($data['government_discount'] ?? $existingStatement['government_discount']),
                !empty($data['due_date']) ? $data['due_date'] : null,
                !empty($data['notes']) ? $data['notes'] : null,
                $id,
            ]);

            (new ChargeSyncService($this->pdo))->syncStatement($id);

            $this->pdo->commit();

            $this->json(200, ['success' => true, 'message' => 'Statement updated successfully.']);
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            error_log('[BillingController::update] ' . $e->getMessage());
            $this->json(500, ['success' => false, 'message' => 'Could not update statement.']);
        }
    }

    public function delete(): void
    {
        $this->json(409, [
            'success' => false,
            'message' => 'Billing statements are part of the admission record and cannot be deleted.',
        ]);
    }

    public function addCharge(): void
    {
        $id   = (int)($_GET['id'] ?? 0);
        $data = $this->input();

        $itemId   = (int)($data['charge_item_id'] ?? 0);
        $quantity = (int)($data['quantity'] ?? 1);

        if ($id <= 0 || $itemId <= 0 || $quantity <= 0) {
            $this->json(422, ['success' => false, 'message' => 'Missing or invalid charge data.']);
        }

        $stmt = $this->pdo->prepare(
            'SELECT ci.charge_item_id, ci.default_price, ci.item_name, ci.is_taxable,
                    cc.is_recurring
             FROM `charge_item` ci
             INNER JOIN `charge_category` cc ON cc.category_id = ci.category_id
             WHERE ci.charge_item_id = ? AND ci.is_active = 1 LIMIT 1'
        );
        $stmt->execute([$itemId]);
        $item = $stmt->fetch();
        if (!$item) $this->json(404, ['success' => false, 'message' => 'Charge item not found or inactive.']);

        $price = isset($data['actual_price']) && is_numeric($data['actual_price'])
            ? (float)$data['actual_price']
            : (float)$item['default_price'];

        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare(
                'INSERT INTO `charge`
                    (statement_id, charge_item_id, quantity, actual_price,
                     charge_datetime, processed_by_user_id, notes,
                         service_start_date, service_end_date, source_type)
                     VALUES (?, ?, ?, ?, NOW(), ?, ?, ?, ?, "MANUAL")'
            );
            $stmt->execute([
                $id,
                $itemId,
                $quantity,
                $price,
                (int)$_SESSION['user']['user_id'],
                !empty($data['notes']) ? $data['notes'] : null,
                !empty($data['service_start_date']) ? $data['service_start_date'] : null,
                !empty($data['service_end_date'])   ? $data['service_end_date']   : null,
            ]);

            (new ChargeSyncService($this->pdo))->recomputeStatement($id);

            $this->pdo->commit();
            $this->json(201, ['success' => true, 'message' => 'Charge added.']);
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            error_log('[addCharge] ' . $e->getMessage());
            $this->json(500, ['success' => false, 'message' => 'Could not add charge.']);
        }
    }

    public function removeCharge(): void
    {
        $statementId = (int)($_GET['id'] ?? 0);
        $chargeId    = (int)($_GET['charge_id'] ?? 0);
        if ($statementId <= 0 || $chargeId <= 0) {
            $this->json(400, ['success' => false, 'message' => 'Missing ids.']);
        }

        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare(
                'SELECT source_type FROM `charge` WHERE charge_id = ? AND statement_id = ? LIMIT 1'
            );
            $stmt->execute([$chargeId, $statementId]);
            $sourceType = $stmt->fetchColumn();
            if ($sourceType === false) {
                $this->pdo->rollBack();
                $this->json(404, ['success' => false, 'message' => 'Charge not found.']);
            }
            if ($sourceType !== 'MANUAL') {
                $this->pdo->rollBack();
                $this->json(409, ['success' => false, 'message' => 'This charge is system-generated and cannot be removed.']);
            }

            $stmt = $this->pdo->prepare('DELETE FROM `charge` WHERE charge_id = ? AND statement_id = ?');
            $stmt->execute([$chargeId, $statementId]);

            (new ChargeSyncService($this->pdo))->recomputeStatement($statementId);

            $this->pdo->commit();
            $this->json(200, ['success' => true, 'message' => 'Charge removed.']);
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            $this->json(500, ['success' => false, 'message' => 'Could not remove charge.']);
        }
    }

    public function addPayment(): void
    {
        $id   = (int)($_GET['id'] ?? 0);
        $data = $this->input();

        $typeId = (int)($data['payment_type_id'] ?? 0);
        $amount = (float)($data['amount'] ?? 0);

        if ($id <= 0 || $typeId <= 0 || $amount <= 0) {
            $this->json(422, ['success' => false, 'message' => 'Please provide a payment type and a positive amount.']);
        }

        try {
            $this->pdo->beginTransaction();

            (new ChargeSyncService($this->pdo))->syncStatement($id);
            $stmt = $this->pdo->prepare(
                'SELECT balance_amount FROM `billing_statement`
                 WHERE statement_id = ? LIMIT 1 FOR UPDATE'
            );
            $stmt->execute([$id]);
            $balanceValue = $stmt->fetchColumn();
            if ($balanceValue === false) {
                $this->pdo->rollBack();
                $this->json(404, ['success' => false, 'message' => 'Billing statement not found.']);
            }
            $balance = (float)$balanceValue;
            if ($amount > $balance + 0.001) {
                $this->pdo->rollBack();
                $this->json(409, [
                    'success' => false,
                    'message' => 'Payment exceeds the outstanding balance of ₱' . number_format($balance, 2) . '.',
                ]);
            }

            $reference = $this->generatePaymentReference();

            $stmt = $this->pdo->prepare(
                'INSERT INTO `payment`
                    (statement_id, payment_type_id, amount, payment_datetime,
                    transaction_reference, received_by_user_id, notes)
                VALUES (?, ?, ?, NOW(), ?, ?, ?)'
            );
            $stmt->execute([
                $id,
                $typeId,
                $amount,
                $reference,
                (int)$_SESSION['user']['user_id'],
                !empty($data['notes']) ? $data['notes'] : null,
            ]);

            (new ChargeSyncService($this->pdo))->recomputeStatement($id);

            $this->pdo->commit();

            $this->json(201, [
                'success'   => true,
                'message'   => 'Payment recorded.',
                'reference' => $reference,
            ]);
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            error_log('[addPayment] ' . $e->getMessage());
            $this->json(500, ['success' => false, 'message' => 'Could not record payment.']);
        }
    }

    private function generatePaymentReference(): string
    {
        $date   = date('Ymd');
        $prefix = 'PAY-' . $date . '-';

        $stmt = $this->pdo->prepare(
            'SELECT transaction_reference
            FROM `payment`
            WHERE transaction_reference LIKE ?
            ORDER BY payment_id DESC
            LIMIT 1
            FOR UPDATE'
        );
        $stmt->execute([$prefix . '%']);
        $last = (string)$stmt->fetchColumn();

        $next = 1;
        if ($last !== '' && preg_match('/-(\d+)$/', $last, $m)) {
            $next = ((int)$m[1]) + 1;
        }

        return $prefix . str_pad((string)$next, 4, '0', STR_PAD_LEFT);
    }

    public function removePayment(): void
    {
        $statementId = (int)($_GET['id'] ?? 0);
        $paymentId   = (int)($_GET['payment_id'] ?? 0);
        if ($statementId <= 0 || $paymentId <= 0) {
            $this->json(400, ['success' => false, 'message' => 'Missing ids.']);
        }

        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare('DELETE FROM `payment` WHERE payment_id = ? AND statement_id = ?');
            $stmt->execute([$paymentId, $statementId]);

            (new ChargeSyncService($this->pdo))->recomputeStatement($statementId);

            $this->pdo->commit();
            $this->json(200, ['success' => true, 'message' => 'Payment removed.']);
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            $this->json(500, ['success' => false, 'message' => 'Could not remove payment.']);
        }
    }

    private function input(): array
    {
        if (isset($_POST['json'])) {
            $data = json_decode((string)$_POST['json'], true);
            return is_array($data) ? $data : [];
        }
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        return is_array($data) ? $data : $_POST;
    }

    private function json(int $status, array $payload): void
    {
        if (isset($payload['success']) && !array_key_exists('data', $payload)) {
            $payload['data'] = $payload['success'] ? 1 : 0;
        }
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        http_response_code($status);
        echo json_encode($payload);
        exit;
    }
}