<?php

class PosController
{
    private PDO $pdo;

    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function getCatalog(): array
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

    public function getPaymentTypes(): array
    {
        return $this->pdo->query(
            'SELECT payment_type_id, type_name FROM `payment_type` ORDER BY type_name'
        )->fetchAll();
    }

    public function getRecentSales(int $limit = 20): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT bs.statement_id AS pos_sale_id,
                    bs.admission_id,
                    bs.statement_date AS created_at,
                    bs.total_amount AS total,
                    bs.amount_paid,
                    bs.balance_amount,
                    bst.status_name,
                    bst.color_code,
                    p.first_name,
                    p.last_name,
                    p.contact_number,
                    a.chief_complaint AS customer_name,
                    u.first_name AS cashier_first,
                    u.last_name AS cashier_last
             FROM `billing_statement` bs
             INNER JOIN `admission` a ON a.admission_id = bs.admission_id
             INNER JOIN `patient` p ON p.patient_id = a.patient_id
             INNER JOIN `billing_status` bst ON bst.status_id = bs.status_id
             LEFT JOIN `user` u ON u.user_id = bs.created_by_user_id
             WHERE a.admission_type = "POS"
             ORDER BY bs.statement_id DESC
             LIMIT ' . (int)$limit
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getSaleDetails(int $saleId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT bs.*, bst.status_name, bst.color_code, bst.is_paid_status,
                    a.admission_id, a.admission_type, a.admission_datetime,
                    a.chief_complaint AS customer_name, a.notes AS pos_notes,
                    p.first_name, p.last_name, p.contact_number, p.email,
                    u.first_name AS cashier_first, u.last_name AS cashier_last
             FROM `billing_statement` bs
             INNER JOIN `admission` a ON a.admission_id = bs.admission_id
             INNER JOIN `patient` p ON p.patient_id = a.patient_id
             INNER JOIN `billing_status` bst ON bst.status_id = bs.status_id
             LEFT JOIN `user` u ON u.user_id = bs.created_by_user_id
             WHERE bs.statement_id = ? AND a.admission_type = "POS"
             LIMIT 1'
        );
        $stmt->execute([$saleId]);
        $sale = $stmt->fetch();
        if (!$sale) return null;

        $stmt = $this->pdo->prepare(
            'SELECT c.charge_id, c.charge_item_id, c.quantity, c.actual_price,
                    c.charge_datetime, c.notes,
                    ci.item_code, ci.item_name, ci.unit_of_measure,
                    cc.category_name,
                    (c.quantity * c.actual_price) AS line_total
             FROM `charge` c
             INNER JOIN `charge_item` ci ON ci.charge_item_id = c.charge_item_id
             INNER JOIN `charge_category` cc ON cc.category_id = ci.category_id
             WHERE c.statement_id = ?
             ORDER BY c.charge_id'
        );
        $stmt->execute([$saleId]);
        $sale['items'] = $stmt->fetchAll();

        $stmt = $this->pdo->prepare(
            'SELECT p.payment_id, p.amount, p.payment_datetime,
                    p.transaction_reference, p.notes,
                    pt.type_name
             FROM `payment` p
             INNER JOIN `payment_type` pt ON pt.payment_type_id = p.payment_type_id
             WHERE p.statement_id = ?
             ORDER BY p.payment_id'
        );
        $stmt->execute([$saleId]);
        $sale['payments'] = $stmt->fetchAll();

        return $sale;
    }

    public function searchPatients(string $q): array
    {
        if (strlen($q) < 2) return [];
        $stmt = $this->pdo->prepare(
            'SELECT patient_id, first_name, last_name, contact_number, email
             FROM `patient`
             WHERE is_active = 1
               AND (first_name LIKE ? OR last_name LIKE ? OR contact_number LIKE ?)
             ORDER BY last_name, first_name
             LIMIT 10'
        );
        $like = '%' . $q . '%';
        $stmt->execute([$like, $like, $like]);
        return $stmt->fetchAll();
    }

    public function createSale(): void
    {
        $data           = $this->input();
        $items          = $data['items'] ?? [];
        $paymentTypeId  = (int)($data['payment_type_id'] ?? 0);
        $discount       = (float)($data['discount'] ?? 0);
        $amountTendered = (float)($data['amount_tendered'] ?? 0);
        $patientId      = (int)($data['patient_id'] ?? 0);
        $customerName   = trim((string)($data['customer_name'] ?? ''));
        $notes          = trim((string)($data['notes'] ?? ''));

        if (empty($items) || $paymentTypeId <= 0) {
            $this->json(422, ['success' => false, 'message' => 'Cart is empty or no payment type selected.']);
        }

        try {
            $this->pdo->beginTransaction();

            if ($patientId > 0) {
                $stmt = $this->pdo->prepare(
                    'SELECT patient_id FROM `patient` WHERE patient_id = ? LIMIT 1'
                );
                $stmt->execute([$patientId]);
                if (!$stmt->fetch()) {
                    $this->pdo->rollBack();
                    $this->json(422, ['success' => false, 'message' => 'Selected patient not found.']);
                }
            } else {
                $patientId = $this->getOrCreateWalkInPatient($customerName);
            }

            $admissionId = $this->createPosAdmission($patientId, $customerName, $notes);
            $statementId = $this->createPosStatement($admissionId);

            $userId = (int)$_SESSION['user']['user_id'];

            $insertCharge = $this->pdo->prepare(
                'INSERT INTO `charge`
                    (statement_id, charge_item_id, quantity, actual_price,
                    charge_datetime, processed_by_user_id, notes)
                VALUES (?, ?, ?, ?, NOW(), ?, ?)'
            );

            $resolvedItems = [];

            foreach ($items as $line) {
                $ciId = (int)($line['charge_item_id'] ?? 0);
                $qty  = (int)($line['quantity'] ?? 1);
                if ($ciId <= 0 || $qty <= 0) continue;

                $stmt = $this->pdo->prepare(
                    'SELECT charge_item_id, default_price, is_taxable, item_name
                    FROM `charge_item` WHERE charge_item_id = ? AND is_active = 1 LIMIT 1'
                );
                $stmt->execute([$ciId]);
                $ci = $stmt->fetch();
                if (!$ci) continue;

                $unitPrice = (float)$ci['default_price'];

                $insertCharge->execute([
                    $statementId,
                    $ciId,
                    $qty,
                    $unitPrice,
                    $userId,
                    'POS sale',
                ]);

                $resolvedItems[] = [
                    'charge_item_id' => $ciId,
                    'quantity'       => $qty,
                    'unit_price'     => $unitPrice,
                ];
            }

            if (empty($resolvedItems)) {
                $this->pdo->rollBack();
                $this->json(422, ['success' => false, 'message' => 'No valid items in cart.']);
            }

            if ($discount > 0) {
                $stmt = $this->pdo->prepare(
                    'UPDATE `billing_statement`
                    SET government_discount = ?
                    WHERE statement_id = ?'
                );
                $stmt->execute([$discount, $statementId]);
            }

            require_once __DIR__ . '/ChargeSyncService.php';
            (new ChargeSyncService($this->pdo))->recomputeStatement($statementId);

            $stmt = $this->pdo->prepare(
                'SELECT total_amount, balance_amount FROM `billing_statement`
                WHERE statement_id = ? LIMIT 1'
            );
            $stmt->execute([$statementId]);
            $totals = $stmt->fetch();
            $total = round((float)$totals['total_amount'], 2);

            $tendered = $amountTendered > 0 ? $amountTendered : $total;
            $change = round(max(0, $tendered - $total), 2);

            $reference = null;
            if ($total > 0) {
                $reference = $this->generatePaymentReference();

                $stmt = $this->pdo->prepare(
                    'INSERT INTO `payment`
                        (statement_id, payment_type_id, amount, payment_datetime,
                        transaction_reference, received_by_user_id, notes)
                    VALUES (?, ?, ?, NOW(), ?, ?, ?)'
                );
                $stmt->execute([
                    $statementId,
                    $paymentTypeId,
                    $total,
                    $reference,
                    $userId,
                    'POS counter sale',
                ]);

                (new ChargeSyncService($this->pdo))->recomputeStatement($statementId);
            }

            $stmt = $this->pdo->prepare('SELECT status_id FROM `admission_status` WHERE status_name = "Discharged" LIMIT 1');
            $stmt->execute();
            $dischargedId = (int)$stmt->fetchColumn();
            if ($dischargedId <= 0) $dischargedId = 2;

            $stmt = $this->pdo->prepare(
                'UPDATE `admission`
                SET status_id = ?,
                    discharge_datetime = NOW(),
                    discharged_by_user_id = ?
                WHERE admission_id = ?'
            );
            $stmt->execute([$dischargedId, $userId, $admissionId]);

            $this->pdo->commit();

            $this->json(201, [
                'success'      => true,
                'message'      => 'Sale recorded.',
                'statement_id' => $statementId,
                'admission_id' => $admissionId,
                'total'        => $total,
                'tendered'     => $tendered,
                'change'       => $change,
                'reference'    => $reference,
            ]);
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            error_log('[PosController::createSale] ' . $e->getMessage());
            $this->json(500, ['success' => false, 'message' => 'Could not record sale: ' . $e->getMessage()]);
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

    private function getOrCreateWalkInPatient(string $customerName): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT patient_id FROM `patient`
             WHERE first_name = "Walk-in" AND last_name = "Customer"
             LIMIT 1'
        );
        $stmt->execute();
        $existing = $stmt->fetchColumn();
        if ($existing) return (int)$existing;

        $stmt = $this->pdo->prepare(
            'INSERT INTO `patient`
                (gender_id, first_name, last_name, is_active)
             VALUES (1, "Walk-in", "Customer", 1)'
        );
        $stmt->execute();
        return (int)$this->pdo->lastInsertId();
    }

    private function createPosAdmission(int $patientId, string $customerName, string $notes): int
    {
        $stmt = $this->pdo->prepare('SELECT status_id FROM `admission_status` WHERE status_name = "Admitted" LIMIT 1');
        $stmt->execute();
        $statusId = (int)$stmt->fetchColumn();
        if ($statusId <= 0) $statusId = 1;

        $chief = $customerName !== '' ? 'POS: ' . $customerName : 'POS: Walk-in';

        $stmt = $this->pdo->prepare(
            'INSERT INTO `admission`
                (patient_id, status_id, admission_datetime, chief_complaint,
                 admission_type, total_room_transfers, admitted_by_user_id, notes)
             VALUES (?, ?, NOW(), ?, "POS", 0, ?, ?)'
        );
        $stmt->execute([
            $patientId,
            $statusId,
            $chief,
            (int)$_SESSION['user']['user_id'],
            $notes !== '' ? $notes : null,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    private function createPosStatement(int $admissionId): int
    {
        $statusId = (int)$this->pdo->query(
            'SELECT status_id FROM `billing_status`
             WHERE is_paid_status = 0 ORDER BY status_id LIMIT 1'
        )->fetchColumn();
        if ($statusId <= 0) $statusId = 1;

        $stmt = $this->pdo->prepare(
            'INSERT INTO `billing_statement`
                (admission_id, status_id, statement_date, due_date,
                 subtotal_amount, insurance_coverage_amount, government_discount,
                 philhealth_deduction, hmo_discount,
                 applied_philhealth, applied_insurance, applied_hmo, applied_discount,
                 tax_amount, total_amount, amount_paid, balance_amount,
                 created_by_user_id, notes)
             VALUES (?, ?, NOW(), NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, ?, ?)'
        );
        $stmt->execute([
            $admissionId,
            $statusId,
            (int)$_SESSION['user']['user_id'],
            'POS counter sale',
        ]);

        return (int)$this->pdo->lastInsertId();
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