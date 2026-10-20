<?php

require_once __DIR__ . '/BillingHook.php';
require_once __DIR__ . '/../includes/ws_notify.php';

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
        $sql = 'SELECT sr.request_id, sr.department_id, sr.priority, sr.status, sr.notes, sr.request_datetime,
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

    public function getRequestStatusPresentation(array $request): array
    {
        $status = (string)($request['status'] ?? 'Pending');
        $isMedicineReturn = str_starts_with((string)($request['notes'] ?? ''), 'MED_RETURN:')
            || !empty($request['medicine_return']);
        $label = $isMedicineReturn
            ? match ($status) {
                'Pending' => 'Return Requested',
                'In Progress' => 'Return Processing',
                'Completed' => 'Returned',
                'Cancelled' => 'Return Cancelled',
                default => $status,
            }
            : $status;
        $class = match ($status) {
            'Pending' => 'bg-amber-100 text-amber-700',
            'In Progress' => 'bg-blue-100 text-blue-700',
            'Completed' => 'bg-emerald-100 text-emerald-700',
            'Cancelled' => 'bg-rose-100 text-rose-700',
            default => 'bg-slate-100 text-slate-700',
        };

        return ['label' => $label, 'class' => $class];
    }

    public function getRequestDetails(int $requestId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT sr.request_id, sr.department_id, sr.priority, sr.status, sr.request_datetime, sr.notes,
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
        $request['medicine_return'] = null;
        if (preg_match('/^MED_RETURN:(\d+)\|(.*)$/s', (string)($request['notes'] ?? ''), $returnMatch)) {
            $request['medicine_return'] = [
                'origin_fulfillment_item_id' => (int)$returnMatch[1],
                'reason' => $returnMatch[2],
            ];
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
            'SELECT sr.request_id, sr.status, sr.department_id, sr.admission_id,
                    p.first_name, p.last_name, d.department_name
             FROM `service_request` sr
             INNER JOIN `admission` a ON a.admission_id = sr.admission_id
             INNER JOIN `patient` p ON p.patient_id = a.patient_id
             LEFT JOIN `department` d ON d.department_id = sr.department_id
             WHERE sr.request_id = ? LIMIT 1'
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

        ws_broadcast('service.request.accepted', [
            'request_id' => $requestId,
            'admission_id' => (int)$request['admission_id'],
            'patient_name' => trim($request['first_name'] . ' ' . $request['last_name']),
            'department_name' => $request['department_name'] ?? '',
        ], [1, 2, 5], $this->departmentId);

        return ['success' => true, 'message' => 'Request accepted and moved into processing.', 'status' => $status];
    }

    public function processRequest(int $requestId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT sr.request_id, sr.status, sr.department_id, sr.admission_id,
                    p.first_name, p.last_name, d.department_name
             FROM `service_request` sr
             INNER JOIN `admission` a ON a.admission_id = sr.admission_id
             INNER JOIN `patient` p ON p.patient_id = a.patient_id
             LEFT JOIN `department` d ON d.department_id = sr.department_id
             WHERE sr.request_id = ? LIMIT 1'
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

        ws_broadcast('service.request.processing', [
            'request_id' => $requestId,
            'admission_id' => (int)$request['admission_id'],
            'patient_name' => trim($request['first_name'] . ' ' . $request['last_name']),
            'department_name' => $request['department_name'] ?? '',
        ], [1, 2, 5], $this->departmentId);

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
                'SELECT sr.request_id, sr.status, sr.department_id, sr.admission_id,
                        sr.notes, d.department_code, d.department_name,
                        p.first_name, p.last_name
                 FROM `service_request` sr
                 LEFT JOIN `department` d ON d.department_id = sr.department_id
                 INNER JOIN `admission` a ON a.admission_id = sr.admission_id
                 INNER JOIN `patient` p ON p.patient_id = a.patient_id
                 WHERE sr.request_id = ? LIMIT 1 FOR UPDATE'
            );
            $stmt->execute([$requestId]);
            $request = $stmt->fetch();
            if (!$request) {
                throw new RuntimeException('Request not found.');
            }
            $returnOriginId = 0;
            if (preg_match('/^MED_RETURN:(\d+)\|/', (string)($request['notes'] ?? ''), $returnMatch)) {
                $returnOriginId = (int)$returnMatch[1];
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
                if ($requestItemId <= 0) {
                    continue;
                }
                $rawQuantity = $item['quantity_fulfilled'] ?? 0;
                $quantity = filter_var($rawQuantity === '' ? 0 : $rawQuantity, FILTER_VALIDATE_INT);
                if ($quantity === false || $quantity < 0) {
                    throw new RuntimeException('Fulfilled quantity must be a whole number of zero or greater.');
                }
                $rawRestocked = $item['quantity_restocked'] ?? 0;
                $restockedQuantity = filter_var($rawRestocked === '' ? 0 : $rawRestocked, FILTER_VALIDATE_INT);
                if ($restockedQuantity === false || $restockedQuantity < 0 || $restockedQuantity > $quantity) {
                    throw new RuntimeException('Restocked quantity must be between zero and the physically received quantity.');
                }
                if ($quantity === 0) continue;
                if ($returnOriginId === 0 && $restockedQuantity > 0) {
                    throw new RuntimeException('Only a medicine return can add returned items back to stock.');
                }

                $checkItem = $this->pdo->prepare(
                    'SELECT sri.request_item_id, sri.quantity_requested, sri.quantity_fulfilled,
                            sri.charge_item_id, ci.is_stockable, ci.item_name
                     FROM `service_request_item` sri
                     INNER JOIN `charge_item` ci ON ci.charge_item_id = sri.charge_item_id
                     WHERE sri.request_item_id = ? AND sri.request_id = ? LIMIT 1 FOR UPDATE'
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
                $isStockable = (int)$requestedItem['is_stockable'] === 1;
                if ($isStockable && ($request['department_code'] ?? '') !== 'PHARM') {
                    throw new RuntimeException('Stock-tracked items can only be fulfilled by the Pharmacy department.');
                }
                if ($returnOriginId > 0) {
                    $origin = $this->pdo->prepare(
                        'SELECT charge_item_id FROM `service_fulfillment_item`
                         WHERE fulfillment_item_id = ? LIMIT 1'
                    );
                    $origin->execute([$returnOriginId]);
                    if ((int)$origin->fetchColumn() !== $chargeItemId || !$isStockable) {
                        throw new RuntimeException('Returned item does not match the original stockable medicine.', 409);
                    }
                } elseif ($isStockable) {
                    $stock = $this->pdo->prepare(
                        'UPDATE `charge_item`
                         SET stock_quantity = stock_quantity - ?
                         WHERE charge_item_id = ? AND is_stockable = 1 AND stock_quantity >= ?'
                    );
                    $stock->execute([$quantity, $chargeItemId, $quantity]);
                    if ($stock->rowCount() !== 1) {
                        $available = $this->pdo->prepare('SELECT stock_quantity FROM `charge_item` WHERE charge_item_id = ?');
                        $available->execute([$chargeItemId]);
                        throw new RuntimeException(
                            'Insufficient pharmacy stock for ' . $requestedItem['item_name'] . '. Available: '
                            . (int)$available->fetchColumn() . ', required: ' . $quantity
                            . '. Add the available quantity on the Pharmacy Stock page, then retry fulfillment.',
                            409
                        );
                    }
                }

                $unitPrice = 0.00;
                if ($chargeItemId > 0) {
                    if ($returnOriginId > 0) {
                        $price = $this->pdo->prepare(
                            'SELECT COALESCE(c.actual_price, original.unit_price)
                             FROM `service_fulfillment_item` original
                             LEFT JOIN `charge` c
                               ON c.fulfillment_item_id = original.fulfillment_item_id
                              AND c.source_type = "FULFILLMENT"
                             WHERE original.fulfillment_item_id = ?
                             LIMIT 1'
                        );
                        $price->execute([$returnOriginId]);
                    } else {
                        $price = $this->pdo->prepare(
                            'SELECT default_price FROM `charge_item` WHERE charge_item_id = ? LIMIT 1'
                        );
                        $price->execute([$chargeItemId]);
                    }
                    $storedPrice = $price->fetchColumn();
                    if ($storedPrice === false) {
                        throw new RuntimeException('The requested charge item no longer exists.');
                    }
                    $unitPrice = (float)$storedPrice;
                }

                $insertLine = $this->pdo->prepare(
                    'INSERT INTO `service_fulfillment_item`
                        (fulfillment_id, request_item_id, charge_item_id, quantity_fulfilled,
                         quantity_restocked, unit_price, notes, fulfilled_datetime)
                     VALUES (?, ?, ?, ?, ?, ?, ?, NOW())'
                );
                $insertLine->execute([
                    $fulfillmentId,
                    $requestItemId,
                    $chargeItemId,
                    $quantity,
                    $returnOriginId > 0 ? $restockedQuantity : 0,
                    $unitPrice,
                    !empty($item['notes']) ? trim((string)$item['notes']) : null,
                ]);

                if ($returnOriginId > 0 && $restockedQuantity > 0) {
                    $stock = $this->pdo->prepare(
                        'UPDATE `charge_item`
                         SET stock_quantity = stock_quantity + ?
                         WHERE charge_item_id = ? AND is_stockable = 1'
                    );
                    $stock->execute([$restockedQuantity, $chargeItemId]);
                    if ($stock->rowCount() !== 1) {
                        throw new RuntimeException('Could not add the returned quantity back to stock.', 409);
                    }
                }
                if ($returnOriginId > 0) {
                    $returned = $this->pdo->prepare(
                        'UPDATE `service_fulfillment_item`
                         SET quantity_remaining = quantity_remaining - ?,
                             intake_status = CASE
                                 WHEN quantity_remaining - ? = 0 THEN "Completed"
                                 WHEN quantity_remaining - ? < (
                                     SELECT COALESCE(SUM(receipt.quantity_received), 0)
                                     FROM `service_fulfillment_receipt_item` receipt
                                     WHERE receipt.fulfillment_item_id = ?
                                 ) THEN "In Progress"
                                 ELSE "Not Started"
                             END
                         WHERE fulfillment_item_id = ? AND quantity_remaining >= ?'
                    );
                    $returned->execute([
                        $quantity,
                        $quantity,
                        $quantity,
                        $returnOriginId,
                        $returnOriginId,
                        $quantity,
                    ]);
                    if ($returned->rowCount() !== 1) {
                        throw new RuntimeException(
                            'The returned quantity exceeds the nurse-confirmed remaining quantity.',
                            409
                        );
                    }
                }

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

            $statementStmt = $this->pdo->prepare(
                'SELECT statement_id FROM `billing_statement`
                 WHERE admission_id = ? LIMIT 1 FOR UPDATE'
            );
            $statementStmt->execute([(int)$request['admission_id']]);
            $statementId = (int)$statementStmt->fetchColumn();
            if ($statementId > 0) {
                (new ChargeSyncService($this->pdo))->syncStatement($statementId);
            }

            $this->pdo->commit();

            $requestContext = [
                'request_id' => $requestId,
                'admission_id' => (int)$request['admission_id'],
                'patient_name' => trim($request['first_name'] . ' ' . $request['last_name']),
                'department_name' => $request['department_name'] ?? '',
                'status' => $requestStatus,
            ];
            ws_broadcast('service.request.fulfilled', $requestContext, [1, 2, 3, 4, 5], $this->departmentId);
            if ($statementId > 0) {
                ws_broadcast('billing.statement.updated', ['statement_id' => $statementId], [1, 4]);
            }
            if ($returnOriginId > 0) {
                ws_broadcast('medicine.return.received', [
                    'request_id' => $requestId,
                    'admission_id' => (int)$request['admission_id'],
                    'patient_name' => trim($request['first_name'] . ' ' . $request['last_name']),
                    'department_name' => $request['department_name'] ?? '',
                    'fulfillment_item_id' => $returnOriginId,
                    'statement_id' => $statementId ?: null,
                ], [1, 2, 4, 5], $this->departmentId);
            }

            return [
                'success' => true,
                'message' => 'Fulfillment recorded successfully.',
                'fulfillment_id' => $fulfillmentId,
                'status' => $requestStatus,
                'fulfilled_quantity' => $totalFulfilled,
                'statement_id' => $statementId ?: null,
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