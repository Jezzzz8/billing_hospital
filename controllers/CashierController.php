<?php

require_once __DIR__ . '/ChargeSyncService.php';
require_once __DIR__ . '/../includes/ws_notify.php';

class CashierController
{
    private PDO $pdo;

    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function prepareRunningBilling(): void
    {
        $pendingStatusId = (int)$this->pdo->query(
            'SELECT status_id FROM `billing_status`
             WHERE status_name = "Pending" AND is_paid_status = 0
             ORDER BY status_id LIMIT 1'
        )->fetchColumn();
        if ($pendingStatusId <= 0) {
            throw new RuntimeException('Billing status "Pending" is not configured.');
        }

        $stmt = $this->pdo->query(
            'SELECT a.admission_id
             FROM `admission` a
             INNER JOIN `admission_status` ast ON ast.status_id = a.status_id
             WHERE ast.status_name <> "Discharged"
               AND NOT EXISTS (
                   SELECT 1 FROM `billing_statement` bs
                   WHERE bs.admission_id = a.admission_id
               )
             ORDER BY a.admission_id'
        );
        $admissionIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

        if ($admissionIds) {
            $this->pdo->beginTransaction();
            try {
                $lockAdmission = $this->pdo->prepare(
                    'SELECT admission_id FROM `admission` WHERE admission_id = ? LIMIT 1 FOR UPDATE'
                );
                $findStatement = $this->pdo->prepare(
                    'SELECT statement_id FROM `billing_statement`
                     WHERE admission_id = ? LIMIT 1 FOR UPDATE'
                );
                $createStatement = $this->pdo->prepare(
                    'INSERT INTO `billing_statement`
                        (admission_id, status_id, statement_date, subtotal_amount,
                         insurance_coverage_amount, government_discount,
                         philhealth_deduction, hmo_discount, tax_amount,
                         total_amount, amount_paid, balance_amount, created_by_user_id)
                     VALUES (?, ?, NOW(), 0, 0, 0, 0, 0, 0, 0, 0, 0, ?)'
                );

                foreach ($admissionIds as $admissionId) {
                    $lockAdmission->execute([$admissionId]);
                    if (!$lockAdmission->fetchColumn()) continue;

                    $findStatement->execute([$admissionId]);
                    if ($findStatement->fetchColumn()) continue;

                    $createStatement->execute([
                        $admissionId,
                        $pendingStatusId,
                        (int)($_SESSION['user']['user_id'] ?? 0),
                    ]);
                }
                $this->pdo->commit();
            } catch (Throwable $e) {
                if ($this->pdo->inTransaction()) $this->pdo->rollBack();
                throw $e;
            }
        }

        $statementIds = $this->pdo->query(
            'SELECT statement_id FROM `billing_statement` ORDER BY statement_id'
        )->fetchAll(PDO::FETCH_COLUMN);
        $sync = new ChargeSyncService($this->pdo);
        foreach ($statementIds as $statementId) {
            $sync->syncStatement((int)$statementId);
        }
    }

    public function getDashboardStats(): array
    {
        $totalStatements = (int)$this->pdo->query(
            'SELECT COUNT(*) FROM `billing_statement`'
        )->fetchColumn();

        $pendingStatements = (int)$this->pdo->query(
            'SELECT COUNT(*) FROM `billing_statement` bs
             INNER JOIN `billing_status` bst ON bst.status_id = bs.status_id
             WHERE bs.balance_amount > 0 AND bst.is_paid_status = 0'
        )->fetchColumn();

        $overdue = (int)$this->pdo->query(
            'SELECT COUNT(*) FROM `billing_statement`
             WHERE balance_amount > 0
               AND due_date IS NOT NULL
               AND due_date < CURDATE()'
        )->fetchColumn();

        $collectedToday = (float)$this->pdo->query(
            'SELECT COALESCE(SUM(amount), 0) FROM `payment`
             WHERE DATE(payment_datetime) = CURDATE()'
        )->fetchColumn();

        $totalOutstanding = (float)$this->pdo->query(
            'SELECT COALESCE(SUM(balance_amount), 0) FROM `billing_statement`'
        )->fetchColumn();

        $totalCollected = (float)$this->pdo->query(
            'SELECT COALESCE(SUM(amount), 0) FROM `payment`'
        )->fetchColumn();

        return [
            'total_statements'   => $totalStatements,
            'pending_statements' => $pendingStatements,
            'overdue'            => $overdue,
            'collected_today'    => $collectedToday,
            'total_outstanding'  => $totalOutstanding,
            'total_collected'    => $totalCollected,
        ];
    }

    public function getRecentStatements(int $limit = 10): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT bs.statement_id, bs.admission_id, bs.statement_date, bs.due_date,
                    bs.total_amount, bs.amount_paid, bs.balance_amount,
                    bst.status_name, bst.color_code, bst.is_paid_status,
                    p.first_name, p.last_name
             FROM `billing_statement` bs
             INNER JOIN `billing_status` bst ON bst.status_id = bs.status_id
             INNER JOIN `admission` a ON a.admission_id = bs.admission_id
             INNER JOIN `patient` p ON p.patient_id = a.patient_id
             ORDER BY bs.statement_id DESC
             LIMIT ' . (int)$limit
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getAllStatements(): array
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

    public function getUnpaidStatements(): array
    {
        return $this->pdo->query(
            'SELECT bs.statement_id, bs.admission_id, bs.statement_date, bs.due_date,
                    bs.subtotal_amount, bs.philhealth_deduction,
                    bs.insurance_coverage_amount, bs.hmo_discount, bs.government_discount,
                    bs.tax_amount, bs.total_amount, bs.amount_paid, bs.balance_amount,
                    bst.status_id, bst.status_name, bst.color_code, bst.is_paid_status,
                    p.patient_id, p.first_name, p.last_name, p.contact_number, p.email,
                    a.admission_datetime, ast.status_name AS admission_status_name
             FROM `billing_statement` bs
             INNER JOIN `billing_status` bst ON bst.status_id = bs.status_id
             INNER JOIN `admission` a ON a.admission_id = bs.admission_id
             INNER JOIN `admission_status` ast ON ast.status_id = a.status_id
             INNER JOIN `patient` p ON p.patient_id = a.patient_id
             WHERE (bs.balance_amount > 0 AND bst.is_paid_status = 0)
                OR ast.status_name <> "Discharged"
             ORDER BY (bs.due_date IS NULL) ASC, bs.due_date ASC, bs.statement_id ASC'
        )->fetchAll();
    }

    public function getStatementSummary(int $statementId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT bs.statement_id, bs.admission_id, bs.statement_date, bs.due_date,
                    bs.subtotal_amount, bs.tax_amount, bs.philhealth_deduction,
                    bs.insurance_coverage_amount, bs.hmo_discount,
                    bs.government_discount, bs.total_amount, bs.amount_paid, bs.balance_amount,
                    bst.status_name, bst.color_code, bst.is_paid_status,
                    p.patient_id, p.first_name, p.last_name, p.contact_number
             FROM `billing_statement` bs
             INNER JOIN `billing_status` bst ON bst.status_id = bs.status_id
             INNER JOIN `admission` a ON a.admission_id = bs.admission_id
             INNER JOIN `patient` p ON p.patient_id = a.patient_id
             WHERE bs.statement_id = ?
             LIMIT 1'
        );
        $stmt->execute([$statementId]);
        return $stmt->fetch() ?: null;
    }

    public function getStatementDetails(int $statementId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT bs.*, bst.status_name, bst.color_code, bst.is_paid_status,
                    p.first_name, p.last_name, p.patient_id, p.contact_number, p.email,
                    p.address AS patient_current_address, p.birth_date,
                    g.gender_name,
                    a.admission_id, a.admission_datetime, a.discharge_datetime,
                    a.chief_complaint, a.admission_type,
                    adm_u.first_name AS admitted_by_first,
                    adm_u.last_name  AS admitted_by_last,
                    dis_u.first_name AS discharged_by_first,
                    dis_u.last_name  AS discharged_by_last
            FROM `billing_statement` bs
            INNER JOIN `billing_status` bst ON bst.status_id = bs.status_id
            INNER JOIN `admission` a ON a.admission_id = bs.admission_id
            INNER JOIN `patient` p ON p.patient_id = a.patient_id
            INNER JOIN `gender` g ON g.gender_id = p.gender_id
            LEFT JOIN `user` adm_u ON adm_u.user_id = a.admitted_by_user_id
            LEFT JOIN `user` dis_u ON dis_u.user_id = a.discharged_by_user_id
            WHERE bs.statement_id = ?
            LIMIT 1'
        );
        $stmt->execute([$statementId]);
        $statement = $stmt->fetch();
        if (!$statement) return null;

        $statement['age'] = $statement['patient_age'] !== null
            ? (int)$statement['patient_age']
            : (int)(new DateTime($statement['birth_date'] ?? 'now'))->diff(new DateTime())->y;

        $statement['address'] = $statement['patient_address']
            ?? $statement['patient_current_address']
            ?? null;

        $statement['attending_physician'] = $statement['attending_physician']
            ?? $this->getAttendingPhysicianString((int)$statement['admission_id']);

        if (empty($statement['room_number']) || empty($statement['room_rate'])) {
            $room = $this->getActiveRoomSnapshot((int)$statement['admission_id']);
            $statement['room_number']    = $statement['room_number']    ?: ($room['room_number'] ?? null);
            $statement['room_type_name'] = $statement['room_type_name'] ?: ($room['room_type_name'] ?? null);
            $statement['room_rate']      = (float)($statement['room_rate'] ?? 0) ?: (float)($room['daily_rate_at_assignment'] ?? 0);
        }

        $statement['charges'] = $this->getStatementCharges($statementId);
        $statement['payments'] = $this->getStatementPayments($statementId);
        $statement['room_history'] = $this->getStatementRoomHistory((int)$statement['admission_id']);

        return $statement;
    }

    private function getAttendingPhysicianString(int $admissionId): string
    {
        $stmt = $this->pdo->prepare(
            'SELECT GROUP_CONCAT(
                DISTINCT CONCAT("Dr. ", u.first_name, " ", u.last_name)
                ORDER BY ad.assigned_datetime
                SEPARATOR ", "
             ) AS names
             FROM `admission_doctor` ad
             INNER JOIN `doctor` d ON d.doctor_id = ad.doctor_id
             INNER JOIN `user` u ON u.user_id = d.user_id
             WHERE ad.admission_id = ? AND ad.ended_datetime IS NULL'
        );
        $stmt->execute([$admissionId]);
        return (string)($stmt->fetchColumn() ?: '');
    }

    private function getActiveRoomSnapshot(int $admissionId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT r.room_number, rt.room_type_name, ra.daily_rate_at_assignment
             FROM `room_assignment` ra
             INNER JOIN `room` r ON r.room_id = ra.room_id
             INNER JOIN `room_type` rt ON rt.room_type_id = r.room_type_id
             WHERE ra.admission_id = ? AND ra.is_active = 1 AND ra.end_datetime IS NULL
             LIMIT 1'
        );
        $stmt->execute([$admissionId]);
        return $stmt->fetch() ?: null;
    }

    private function getStatementCharges(int $statementId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT c.charge_id, c.charge_item_id, c.quantity, c.actual_price,
                    c.charge_datetime, c.notes, c.source_type,
                    ci.item_code, ci.item_name, ci.unit_of_measure, ci.is_taxable,
                    cc.category_name,
                    (c.quantity * c.actual_price) AS line_total
             FROM `charge` c
             INNER JOIN `charge_item` ci ON ci.charge_item_id = c.charge_item_id
             INNER JOIN `charge_category` cc ON cc.category_id = ci.category_id
             WHERE c.statement_id = ?
             ORDER BY c.charge_id'
        );
        $stmt->execute([$statementId]);
        return $stmt->fetchAll();
    }

    private function getStatementPayments(int $statementId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT p.payment_id, p.payment_type_id, p.amount,
                    DATE_FORMAT(p.payment_datetime, "%Y-%m-%d %H:%i:%s") AS payment_datetime,
                    p.transaction_reference, p.notes,
                    p.received_by_user_id,
                    pt.type_name,
                    u.first_name AS receiver_first,
                    u.last_name  AS receiver_last,
                    u.username   AS receiver_username
             FROM `payment` p
             INNER JOIN `payment_type` pt ON pt.payment_type_id = p.payment_type_id
             LEFT JOIN `user` u ON u.user_id = p.received_by_user_id
             WHERE p.statement_id = ?
             ORDER BY p.payment_id DESC'
        );
        $stmt->execute([$statementId]);
        return $stmt->fetchAll();
    }

    private function getStatementRoomHistory(int $admissionId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT ra.room_assignment_id, ra.start_datetime, ra.end_datetime,
                    ra.daily_rate_at_assignment, ra.transfer_reason, ra.is_active,
                    r.room_number, rt.room_type_name
             FROM `room_assignment` ra
             INNER JOIN `room` r ON r.room_id = ra.room_id
             INNER JOIN `room_type` rt ON rt.room_type_id = r.room_type_id
             WHERE ra.admission_id = ?
             ORDER BY ra.start_datetime ASC'
        );
        $stmt->execute([$admissionId]);
        return $stmt->fetchAll();
    }

    public function getBillingStatuses(): array
    {
        return $this->pdo->query(
            'SELECT status_id, status_name, color_code, is_paid_status
             FROM `billing_status` ORDER BY status_id'
        )->fetchAll();
    }

    public function getPaymentTypes(): array
    {
        return $this->pdo->query(
            'SELECT payment_type_id, type_name, description
             FROM `payment_type` ORDER BY type_name'
        )->fetchAll();
    }

    public function getChargeItems(): array
    {
        return $this->pdo->query(
            'SELECT ci.charge_item_id, ci.item_code, ci.item_name, ci.default_price,
                    ci.is_taxable, ci.unit_of_measure,
                    cc.category_id, cc.category_name
             FROM `charge_item` ci
             INNER JOIN `charge_category` cc ON cc.category_id = ci.category_id
             WHERE ci.is_active = 1
             ORDER BY cc.category_name, ci.item_name'
        )->fetchAll();
    }

    public function getRecentAdmissions(int $limit = 10): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT a.admission_id, a.admission_datetime, a.chief_complaint,
                    p.first_name, p.last_name, p.patient_id,
                    ast.status_name AS admission_status_name,
                    bs.statement_id, bs.balance_amount, bst.status_name AS billing_status_name
             FROM `admission` a
             INNER JOIN `patient` p ON p.patient_id = a.patient_id
             INNER JOIN `admission_status` ast ON ast.status_id = a.status_id
             INNER JOIN `billing_statement` bs ON bs.admission_id = a.admission_id
             INNER JOIN `billing_status` bst ON bst.status_id = bs.status_id
             ORDER BY a.admission_datetime DESC
             LIMIT ' . (int)$limit
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function syncChargesFromAdmission(int $statementId): void
    {
        (new ChargeSyncService($this->pdo))->syncStatement($statementId);
    }

    public function addCharge(int $statementId, array $data): void
    {
        $itemId   = (int)($data['charge_item_id'] ?? 0);
        $quantity = (int)($data['quantity'] ?? 1);
        if ($itemId <= 0 || $quantity <= 0) throw new Exception('Invalid charge data.');

        $stmt = $this->pdo->prepare(
            'SELECT charge_item_id, default_price, item_name, is_stockable FROM `charge_item`
            WHERE charge_item_id = ? AND is_active = 1 LIMIT 1'
        );
        $stmt->execute([$itemId]);
        $item = $stmt->fetch();
        if (!$item) throw new Exception('Charge item not found or inactive.');
        if ((int)$item['is_stockable'] === 1) {
            throw new RuntimeException('Stockable medicine charges must be recorded through Pharmacy fulfillment or POS.', 409);
        }

        $price = isset($data['actual_price']) && is_numeric($data['actual_price'])
            ? (float)$data['actual_price']
            : (float)$item['default_price'];

        $note = 'manual-entry';
        if (!empty($data['notes'])) {
            $note .= ': ' . trim((string)$data['notes']);
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO `charge`
                (statement_id, charge_item_id, quantity, actual_price,
                charge_datetime, processed_by_user_id, notes, source_type)
            VALUES (?, ?, ?, ?, NOW(), ?, ?, "MANUAL")'
        );
        $stmt->execute([
            $statementId,
            $itemId,
            $quantity,
            $price,
            (int)($_SESSION['user']['user_id'] ?? 0),
            $note,
        ]);

        (new ChargeSyncService($this->pdo))->syncStatement($statementId);

        ws_broadcast('billing.charge.added', ['statement_id' => $statementId], [1, 4]);
    }

    public function removeCharge(int $statementId, int $chargeId): void
    {
        $stmt = $this->pdo->prepare(
            'SELECT charge_id, source_type FROM `charge`
            WHERE charge_id = ? AND statement_id = ? LIMIT 1'
        );
        $stmt->execute([$chargeId, $statementId]);
        $charge = $stmt->fetch();

        if (!$charge) {
            throw new Exception('Charge not found.');
        }

        if ($charge['source_type'] !== 'MANUAL') {
            throw new RuntimeException('This charge is system-generated and cannot be removed.', 409);
        }

        $stmt = $this->pdo->prepare(
            'DELETE FROM `charge` WHERE charge_id = ? AND statement_id = ?'
        );
        $stmt->execute([$chargeId, $statementId]);

        (new ChargeSyncService($this->pdo))->syncStatement($statementId);

        ws_broadcast('billing.charge.removed', ['statement_id' => $statementId, 'charge_id' => $chargeId], [1, 4]);
    }

    public function addPayment(int $statementId, array $data): string
    {
        $typeId = (int)($data['payment_type_id'] ?? 0);
        $amount = (float)($data['amount'] ?? 0);

        if ($typeId <= 0 || $amount <= 0) {
            throw new RuntimeException('Please provide a payment type and a positive amount.', 422);
        }

        $this->pdo->beginTransaction();
        try {
            (new ChargeSyncService($this->pdo))->syncStatement($statementId);

            $stmt = $this->pdo->prepare(
                'SELECT balance_amount FROM `billing_statement`
                 WHERE statement_id = ? LIMIT 1 FOR UPDATE'
            );
            $stmt->execute([$statementId]);
            $balanceValue = $stmt->fetchColumn();
            if ($balanceValue === false) throw new RuntimeException('Statement not found.', 404);
            $balance = (float)$balanceValue;
            if ($amount > $balance + 0.001) {
                throw new RuntimeException(sprintf(
                    'Amount exceeds the outstanding balance of ₱%s.',
                    number_format($balance, 2)
                ), 409);
            }

            $stmt = $this->pdo->prepare(
                'SELECT payment_id
                 FROM `payment`
                 WHERE statement_id = ?
                   AND payment_type_id = ?
                   AND amount = ?
                   AND payment_datetime >= (NOW() - INTERVAL 15 SECOND)
                 LIMIT 1'
            );
            $stmt->execute([$statementId, $typeId, $amount]);
            if ($stmt->fetch()) {
                throw new RuntimeException('This payment was just recorded. Please refresh if you do not see it.', 409);
            }

            $reference = $this->generatePaymentReference();

            $stmt = $this->pdo->prepare(
                'INSERT INTO `payment`
                    (statement_id, payment_type_id, amount, payment_datetime,
                     transaction_reference, received_by_user_id, notes)
                 VALUES (?, ?, ?, NOW(), ?, ?, ?)'
            );
            $stmt->execute([
                $statementId,
                $typeId,
                $amount,
                $reference,
                (int)($_SESSION['user']['user_id'] ?? 0),
                !empty($data['notes']) ? $data['notes'] : null,
            ]);

            (new ChargeSyncService($this->pdo))->syncStatement($statementId);

            $this->pdo->commit();

            ws_broadcast('billing.payment.added', [
                'statement_id' => $statementId,
                'reference'    => $reference,
                'amount'       => $amount,
            ], [1, 4]);

            return $reference;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function removePayment(int $statementId, int $paymentId): void
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM `payment` WHERE payment_id = ? AND statement_id = ?'
        );
        $stmt->execute([$paymentId, $statementId]);

        (new ChargeSyncService($this->pdo))->syncStatement($statementId);

        ws_broadcast('billing.payment.removed', ['statement_id' => $statementId, 'payment_id' => $paymentId], [1, 4]);
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

    public function getReportsData(): array
    {
        $byType = $this->pdo->query(
            'SELECT pt.type_name, COUNT(p.payment_id) AS count,
                    COALESCE(SUM(p.amount), 0) AS total
             FROM `payment` p
             INNER JOIN `payment_type` pt ON pt.payment_type_id = p.payment_type_id
             GROUP BY pt.payment_type_id
             ORDER BY total DESC'
        )->fetchAll();

        $daily = $this->pdo->query(
            'SELECT DATE(payment_datetime) AS day,
                    COUNT(*) AS count,
                    COALESCE(SUM(amount), 0) AS total
             FROM `payment`
             WHERE payment_datetime >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
             GROUP BY DATE(payment_datetime)
             ORDER BY day ASC'
        )->fetchAll();

        $topBalances = $this->pdo->query(
            'SELECT bs.statement_id, p.first_name, p.last_name,
                    bs.total_amount, bs.amount_paid, bs.balance_amount,
                    bst.status_name, bst.color_code
             FROM `billing_statement` bs
             INNER JOIN `admission` a ON a.admission_id = bs.admission_id
             INNER JOIN `patient` p ON p.patient_id = a.patient_id
             INNER JOIN `billing_status` bst ON bst.status_id = bs.status_id
             WHERE bs.balance_amount > 0
             ORDER BY bs.balance_amount DESC
             LIMIT 10'
        )->fetchAll();

        return [
            'by_type'       => $byType,
            'daily'         => $daily,
            'top_balances'  => $topBalances,
        ];
    }

    public function syncStatement(int $statementId): void
    {
        (new ChargeSyncService($this->pdo))->syncStatement($statementId);
    }

    public function createStatement(): void
    {
        $this->json(409, [
            'success' => false,
            'message' => 'Billing statements are created automatically when an admission is opened.',
        ]);
    }

    public function updateStatementDeductions(int $statementId, array $data): void
    {
        if ($statementId <= 0) {
            $this->json(422, ['success' => false, 'message' => 'Statement id is required.']);
        }

        $fields = [
            'philhealth_deduction',
            'insurance_coverage_amount',
            'hmo_discount',
            'government_discount',
        ];
        foreach ($fields as $field) {
            if (!isset($data[$field]) || !is_numeric($data[$field]) || (float)$data[$field] < 0) {
                $this->json(422, ['success' => false, 'message' => 'Enter non-negative amounts for all deductions.']);
            }
        }

        try {
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare(
                'SELECT statement_id FROM `billing_statement` WHERE statement_id = ? FOR UPDATE'
            );
            $stmt->execute([$statementId]);
            if (!$stmt->fetch()) {
                $this->pdo->rollBack();
                $this->json(404, ['success' => false, 'message' => 'Statement not found.']);
            }

            $stmt = $this->pdo->prepare(
                'UPDATE `billing_statement`
                 SET philhealth_deduction = ?, insurance_coverage_amount = ?,
                     hmo_discount = ?, government_discount = ?
                 WHERE statement_id = ?'
            );
            $stmt->execute([
                (float)$data['philhealth_deduction'],
                (float)$data['insurance_coverage_amount'],
                (float)$data['hmo_discount'],
                (float)$data['government_discount'],
                $statementId,
            ]);

            (new ChargeSyncService($this->pdo))->recomputeStatement($statementId);
            $this->pdo->commit();

            ws_broadcast('billing.statement.updated', ['statement_id' => $statementId], [1, 4]);

            $this->json(200, ['success' => true, 'message' => 'Statement deductions updated.']);
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            error_log('[CashierController::updateStatementDeductions] ' . $e->getMessage());
            $this->json(500, ['success' => false, 'message' => 'Could not update statement deductions.']);
        }
    }

    public function input(): array
    {
        if (isset($_POST['json'])) {
            $data = json_decode((string)$_POST['json'], true);
            return is_array($data) ? $data : [];
        }
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        return is_array($data) ? $data : $_POST;
    }

    public function json(int $status, array $payload): void
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