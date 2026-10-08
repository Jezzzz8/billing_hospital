<?php

require_once __DIR__ . '/BillingHook.php';

class DepartmentController
{
    private PDO $pdo;
    private int $departmentId;

    public function __construct(PDO $pdo, int $departmentId)
    {
        $this->pdo = $pdo;
        $this->departmentId = $departmentId;
    }

    private function tableExists(string $table): bool
    {
        $stmt = $this->pdo->prepare('SHOW TABLES LIKE ?');
        $stmt->execute([$table]);
        return (bool)$stmt->fetchColumn();
    }

    public function getDashboardStats(): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM `service_request`
             WHERE department_id = ? AND status IN ("Pending", "In Progress")'
        );
        $stmt->execute([$this->departmentId]);
        $pending = (int)$stmt->fetchColumn();

        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM `service_request`
             WHERE department_id = ? AND status = "Completed"'
        );
        $stmt->execute([$this->departmentId]);
        $completed = (int)$stmt->fetchColumn();

        return [
            'pending_requests' => $pending,
            'completed_requests' => $completed,
            'department_id' => $this->departmentId,
        ];
    }

    public function getQueue(): array
    {
        $sql = 'SELECT sr.request_id, sr.department_id, sr.priority, sr.status, sr.request_datetime,
                       sr.completed_datetime, sr.cancelled_datetime,
                       a.admission_id, a.chief_complaint,
                       p.patient_id, p.first_name, p.last_name,
                       u.first_name AS doctor_first, u.last_name AS doctor_last,
                       d.department_name,
                       COUNT(sri.request_item_id) AS item_count,
                       COALESCE(SUM(sri.quantity_requested), 0) AS total_requested
                 FROM `service_request` sr
                 INNER JOIN `admission` a ON a.admission_id = sr.admission_id
                 INNER JOIN `patient` p ON p.patient_id = a.patient_id
                 LEFT JOIN `doctor` doc ON doc.doctor_id = sr.doctor_id
                 LEFT JOIN `user` u ON u.user_id = doc.user_id
                 LEFT JOIN `department` d ON d.department_id = sr.department_id
                 LEFT JOIN `service_request_item` sri ON sri.request_id = sr.request_id
                 WHERE sr.department_id = ?
                 GROUP BY sr.request_id
                 ORDER BY sr.request_datetime DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$this->departmentId]);
        return $stmt->fetchAll();
    }

    public function getRequestDetails(int $requestId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT sr.request_id, sr.department_id, sr.priority, sr.status, sr.request_datetime,
                    sr.instructions, sr.notes,
                    a.admission_id, a.chief_complaint,
                    p.patient_id, p.first_name, p.last_name,
                    u.first_name AS doctor_first, u.last_name AS doctor_last,
                    d.department_name
             FROM `service_request` sr
             INNER JOIN `admission` a ON a.admission_id = sr.admission_id
             INNER JOIN `patient` p ON p.patient_id = a.patient_id
             LEFT JOIN `doctor` doc ON doc.doctor_id = sr.doctor_id
             LEFT JOIN `user` u ON u.user_id = doc.user_id
             LEFT JOIN `department` d ON d.department_id = sr.department_id
             WHERE sr.request_id = ? AND sr.department_id = ? LIMIT 1'
        );
        $stmt->execute([$requestId, $this->departmentId]);
        $request = $stmt->fetch();
        if (!$request) {
            return null;
        }

        $stmt = $this->pdo->prepare(
            'SELECT sri.request_item_id, sri.charge_item_id, sri.quantity_requested,
                    sri.quantity_fulfilled, sri.instructions AS item_instructions, sri.notes AS item_notes,
                    ci.item_name, ci.default_price, ci.unit_of_measure, cc.category_name
             FROM `service_request_item` sri
             INNER JOIN `charge_item` ci ON ci.charge_item_id = sri.charge_item_id
             INNER JOIN `charge_category` cc ON cc.category_id = ci.category_id
             WHERE sri.request_id = ?
             ORDER BY sri.request_item_id'
        );
        $stmt->execute([$requestId]);
        $request['items'] = $stmt->fetchAll();

        return $request;
    }

    public function acceptRequest(int $requestId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT request_id, status, department_id FROM `service_request` WHERE request_id = ? LIMIT 1'
        );
        $stmt->execute([$requestId]);
        $request = $stmt->fetch();
        if (!$request) {
            return ['success' => false, 'message' => 'Request not found.'];
        }
        if ((int)$request['department_id'] !== $this->departmentId) {
            return ['success' => false, 'message' => 'This request is not assigned to your department.'];
        }
        if (!in_array($request['status'], ['Pending', 'In Progress'], true)) {
            return ['success' => false, 'message' => 'Only pending or in-progress requests can be accepted.'];
        }

        $status = $request['status'] === 'Pending' ? 'In Progress' : $request['status'];
        $stmt = $this->pdo->prepare(
            'UPDATE `service_request` SET status = ?, completed_datetime = NULL WHERE request_id = ?'
        );
        $stmt->execute([$status, $requestId]);

        return ['success' => true, 'message' => 'Request accepted and moved into processing.', 'status' => $status];
    }

    public function processRequest(int $requestId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT request_id, status, department_id FROM `service_request` WHERE request_id = ? LIMIT 1'
        );
        $stmt->execute([$requestId]);
        $request = $stmt->fetch();
        if (!$request) {
            return ['success' => false, 'message' => 'Request not found.'];
        }
        if ((int)$request['department_id'] !== $this->departmentId) {
            return ['success' => false, 'message' => 'This request is not assigned to your department.'];
        }
        if (!in_array($request['status'], ['Pending', 'In Progress'], true)) {
            return ['success' => false, 'message' => 'Only pending or in-progress requests can be processed.'];
        }

        $stmt = $this->pdo->prepare(
            'UPDATE `service_request` SET status = "In Progress" WHERE request_id = ?'
        );
        $stmt->execute([$requestId]);

        return ['success' => true, 'message' => 'Request is now in progress.'];
    }

    public function recordFulfillment(int $requestId, array $items, ?string $notes = null): array
    {
        if ($requestId <= 0) {
            return ['success' => false, 'message' => 'Request id is required.'];
        }
        if (!is_array($items) || empty($items)) {
            return ['success' => false, 'message' => 'At least one fulfilled item is required.'];
        }

        if (!$this->tableExists('service_fulfillment')) {
            return ['success' => false, 'message' => 'Fulfillment tracking is not available in the database schema.'];
        }

        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare(
                'SELECT request_id, status, department_id, admission_id
                 FROM `service_request` WHERE request_id = ? LIMIT 1 FOR UPDATE'
            );
            $stmt->execute([$requestId]);
            $request = $stmt->fetch();
            if (!$request) {
                throw new RuntimeException('Request not found.');
            }
            if ((int)$request['department_id'] !== $this->departmentId) {
                throw new RuntimeException('This request is not assigned to your department.');
            }
            if ($request['status'] === 'Cancelled') {
                throw new RuntimeException('Cancelled requests cannot be fulfilled.');
            }
            if ($request['status'] === 'Pending') {
                throw new RuntimeException('Accept the request before recording a fulfillment.');
            }
            if ($request['status'] === 'Completed') {
                throw new RuntimeException('This request has already been fully fulfilled.');
            }

            $insertFulfillment = $this->pdo->prepare(
                'INSERT INTO `service_fulfillment` (request_id, department_id, fulfilled_by_user_id, fulfillment_datetime, status, notes)
                 VALUES (?, ?, ?, NOW(), "Completed", ?)'
            );
            $insertFulfillment->execute([
                $requestId,
                $this->departmentId,
                (int)($_SESSION['user']['user_id'] ?? 0),
                $notes ?: null,
            ]);
            $fulfillmentId = (int)$this->pdo->lastInsertId();

            $totalFulfilled = 0;
            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $requestItemId = (int)($item['request_item_id'] ?? 0);
                $quantity = (int)($item['quantity_fulfilled'] ?? 0);
                if ($requestItemId <= 0 || $quantity <= 0) {
                    continue;
                }

                $checkItem = $this->pdo->prepare(
                    'SELECT request_item_id, quantity_requested, quantity_fulfilled, charge_item_id
                     FROM `service_request_item`
                     WHERE request_item_id = ? AND request_id = ? LIMIT 1 FOR UPDATE'
                );
                $checkItem->execute([$requestItemId, $requestId]);
                $requestedItem = $checkItem->fetch();
                if (!$requestedItem) {
                    throw new RuntimeException('One of the requested items could not be found.');
                }

                $remaining = max(0, (int)$requestedItem['quantity_requested'] - (int)$requestedItem['quantity_fulfilled']);
                if ($quantity > $remaining) {
                    throw new RuntimeException('Fulfilled quantity cannot exceed the remaining requested quantity.');
                }

                $chargeItemId = (int)($requestedItem['charge_item_id'] ?? 0);
                $unitPrice = 0.00;
                if ($chargeItemId > 0) {
                    $price = $this->pdo->prepare(
                        'SELECT default_price FROM `charge_item` WHERE charge_item_id = ? LIMIT 1'
                    );
                    $price->execute([$chargeItemId]);
                    $storedPrice = $price->fetchColumn();
                    if ($storedPrice === false) {
                        throw new RuntimeException('The requested charge item no longer exists.');
                    }
                    $unitPrice = (float)$storedPrice;
                }

                $insertLine = $this->pdo->prepare(
                    'INSERT INTO `service_fulfillment_item`
                        (fulfillment_id, request_item_id, charge_item_id, quantity_fulfilled, unit_price, notes, fulfilled_datetime)
                     VALUES (?, ?, ?, ?, ?, ?, NOW())'
                );
                $insertLine->execute([
                    $fulfillmentId,
                    $requestItemId,
                    $chargeItemId,
                    $quantity,
                    $unitPrice,
                    !empty($item['notes']) ? trim((string)$item['notes']) : null,
                ]);

                $totalFulfilled += $quantity;

                $updateItem = $this->pdo->prepare(
                    'UPDATE `service_request_item`
                     SET quantity_fulfilled = quantity_fulfilled + ?
                     WHERE request_item_id = ?'
                );
                $updateItem->execute([$quantity, $requestItemId]);
            }

            if ($totalFulfilled <= 0) {
                throw new RuntimeException('Enter an actual fulfilled quantity for at least one item.');
            }

            $requestStatus = $this->getRequestStatusAfterFulfillment($requestId);
            $updateRequest = $this->pdo->prepare(
                'UPDATE `service_request` SET status = ?, completed_datetime = NOW() WHERE request_id = ?'
            );
            $updateRequest->execute([$requestStatus, $requestId]);

            $this->pdo->commit();
            BillingHook::emit($this->pdo, (int)$request['admission_id']);

            return [
                'success' => true,
                'message' => 'Fulfillment recorded successfully.',
                'fulfillment_id' => $fulfillmentId,
                'status' => $requestStatus,
                'fulfilled_quantity' => $totalFulfilled,
            ];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['success' => false, 'message' => 'Could not record fulfillment: ' . $e->getMessage()];
        }
    }

    private function getRequestStatusAfterFulfillment(int $requestId): string
    {
        $stmt = $this->pdo->prepare(
            'SELECT SUM(quantity_requested) AS total_requested, SUM(quantity_fulfilled) AS total_fulfilled
             FROM `service_request_item`
             WHERE request_id = ?'
        );
        $stmt->execute([$requestId]);
        $row = $stmt->fetch();

        if (!$row) {
            return 'Completed';
        }

        $requested = (int)($row['total_requested'] ?? 0);
        $fulfilled = (int)($row['total_fulfilled'] ?? 0);

        return ($requested > 0 && $fulfilled >= $requested) ? 'Completed' : 'In Progress';
    }
}