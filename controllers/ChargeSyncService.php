<?php

class ChargeSyncService
{
    private PDO $pdo;

    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    private function tableExists(string $table): bool
    {
        $stmt = $this->pdo->prepare('SHOW TABLES LIKE ?');
        $stmt->execute([$table]);
        return (bool)$stmt->fetchColumn();
    }

    public function syncStatement(int $statementId): void
    {
        $stmt = $this->pdo->prepare(
            'SELECT admission_id FROM `billing_statement`
             WHERE statement_id = ? LIMIT 1'
        );
        $stmt->execute([$statementId]);
        $admissionId = (int)$stmt->fetchColumn();
        if ($admissionId <= 0) return;

        $ownTransaction = !$this->pdo->inTransaction();
        if ($ownTransaction) $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare(
                'SELECT statement_id FROM `billing_statement`
                 WHERE statement_id = ? FOR UPDATE'
            );
            $stmt->execute([$statementId]);
            if (!$stmt->fetchColumn()) {
                if ($ownTransaction) $this->pdo->commit();
                return;
            }

            $this->syncLiveRooms($statementId, $admissionId);
            $this->syncLiveServices($statementId, $admissionId);
            $this->syncLiveDoctors($statementId, $admissionId);
            $this->recomputeStatement($statementId);
            if ($ownTransaction) $this->pdo->commit();
        } catch (Throwable $e) {
            if ($ownTransaction && $this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }

    private function syncLiveRooms(int $statementId, int $admissionId): void
    {
        $stmt = $this->pdo->prepare(
            'SELECT ra.room_assignment_id, ra.start_datetime, ra.end_datetime,
                    ra.daily_rate_at_assignment,
                    r.room_number, rt.room_type_id, rt.room_type_name
             FROM `room_assignment` ra
             INNER JOIN `room` r ON r.room_id = ra.room_id
             INNER JOIN `room_type` rt ON rt.room_type_id = r.room_type_id
             WHERE ra.admission_id = ?
             ORDER BY ra.start_datetime ASC'
        );
        $stmt->execute([$admissionId]);
        $assignments = $stmt->fetchAll();

        foreach ($assignments as $a) {
            $marker = 'source=room:' . $a['room_assignment_id'];

            $itemId = $this->findRoomChargeItem($a['room_type_name']);
            if ($itemId <= 0) {
                throw new RuntimeException('No active Room Charges item is configured for room type "' . $a['room_type_name'] . '".');
            }

            $chk = $this->pdo->prepare(
                'SELECT charge_id FROM `charge`
                 WHERE statement_id = ? AND source_type = "ROOM"
                   AND room_assignment_id = ?
                 LIMIT 1'
            );
            $chk->execute([$statementId, (int)$a['room_assignment_id']]);
            $existing = $chk->fetch();

            $start = new DateTimeImmutable($a['start_datetime']);
            $end = new DateTimeImmutable($a['end_datetime'] ?: 'now');
            $startDay = $start->setTime(0, 0);
            $endDay = $end->setTime(0, 0);
            $days = max(1, (int)$startDay->diff($endDay)->days + 1);

            if ($existing) {
                $upd = $this->pdo->prepare(
                    'UPDATE `charge`
                     SET charge_item_id = ?, quantity = ?, actual_price = ?,
                         service_end_date = ?
                     WHERE charge_id = ?'
                );
                $upd->execute([
                    $itemId,
                    $days,
                    (float)$a['daily_rate_at_assignment'],
                    $a['end_datetime'] ? $end->format('Y-m-d') : null,
                    (int)$existing['charge_id'],
                ]);
            } else {
                $ins = $this->pdo->prepare(
                    'INSERT INTO `charge`
                        (statement_id, charge_item_id, quantity, actual_price,
                         charge_datetime, processed_by_user_id, notes,
                         service_start_date, service_end_date, source_type,
                         room_assignment_id)
                     VALUES (?, ?, ?, ?, NOW(), ?, ?, ?, ?, "ROOM", ?)'
                );
                $ins->execute([
                    $statementId,
                    $itemId,
                    $days,
                    (float)$a['daily_rate_at_assignment'],
                    (int)($_SESSION['user']['user_id'] ?? 0),
                    'Room ' . $a['room_number'] . ' (' . $a['room_type_name'] . ') — ' . $marker,
                    $start->format('Y-m-d'),
                    $a['end_datetime'] ? $end->format('Y-m-d') : null,
                    (int)$a['room_assignment_id'],
                ]);
            }
        }
    }

    private function syncLiveServices(int $statementId, int $admissionId): void
    {
        if ($this->tableExists('service_fulfillment_item')) {
            $stmt = $this->pdo->prepare(
                'SELECT sfi.fulfillment_item_id, sfi.charge_item_id, sfi.quantity_fulfilled,
                        sfi.unit_price, sri.request_item_id, sr.request_id,
                        sr.admission_id, sr.notes AS request_notes, ci.item_name
                 FROM `service_fulfillment_item` sfi
                 INNER JOIN `service_request_item` sri ON sri.request_item_id = sfi.request_item_id
                 INNER JOIN `service_request` sr ON sr.request_id = sri.request_id
                 INNER JOIN `charge_item` ci ON ci.charge_item_id = sfi.charge_item_id
                 WHERE sr.admission_id = ?'
            );
            $stmt->execute([$admissionId]);
            $fulfillmentItems = $stmt->fetchAll();

            if ($fulfillmentItems) {
                $insert = $this->pdo->prepare(
                    'INSERT INTO `charge`
                        (statement_id, charge_item_id, quantity, actual_price, amount,
                         charge_datetime, processed_by_user_id, notes, fulfillment_item_id, source_type)
                     VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?)'
                );
                $userId = (int)($_SESSION['user']['user_id'] ?? 0);

                foreach ($fulfillmentItems as $item) {
                    $isMedicineReturn = preg_match('/^MED_RETURN:(\d+)\|/', (string)$item['request_notes'], $returnMatch) === 1;
                    $sourceType = $isMedicineReturn ? 'MEDICINE_RETURN' : 'FULFILLMENT';
                    $fulfilledQuantity = (int)$item['quantity_fulfilled'];
                    if ($isMedicineReturn) {
                        $fulfilledQuantity *= -1;
                    }
                    $chk = $this->pdo->prepare(
                        'SELECT charge_id FROM `charge`
                         WHERE statement_id = ? AND source_type = ?
                           AND fulfillment_item_id = ?'
                    );
                    $chk->execute([$statementId, $sourceType, (int)$item['fulfillment_item_id']]);
                    $existingChargeId = (int)$chk->fetchColumn();

                    if ($fulfilledQuantity <= 0) {
                        if ($isMedicineReturn && $fulfilledQuantity === 0) continue;
                        if (!$isMedicineReturn && $existingChargeId > 0) {
                            $delete = $this->pdo->prepare(
                                'DELETE FROM `charge` WHERE charge_id = ?'
                            );
                            $delete->execute([$existingChargeId]);
                        }
                        if (!$isMedicineReturn) continue;
                    }

                    if ($existingChargeId > 0) {
                        $update = $this->pdo->prepare(
                            'UPDATE `charge`
                             SET charge_item_id = ?, quantity = ?, actual_price = ?, amount = ?
                             WHERE charge_id = ?'
                        );
                        $update->execute([
                            (int)$item['charge_item_id'],
                            $fulfilledQuantity,
                            (float)$item['unit_price'],
                            $fulfilledQuantity * (float)$item['unit_price'],
                            $existingChargeId,
                        ]);
                        continue;
                    }

                    $notes = $isMedicineReturn
                        ? 'medicine-return-origin=' . (int)$returnMatch[1] . '; '
                            . 'Medicine return fulfillment item #' . (int)$item['fulfillment_item_id']
                        : 'Fulfillment item #' . $item['fulfillment_item_id'] . ' (' . $item['item_name'] . ') — source=fulfillment:' . $item['fulfillment_item_id'];
                    $insert->execute([
                        $statementId,
                        (int)$item['charge_item_id'],
                        $fulfilledQuantity,
                        (float)$item['unit_price'],
                        $fulfilledQuantity * (float)$item['unit_price'],
                        $userId,
                        $notes,
                        (int)$item['fulfillment_item_id'],
                        $sourceType,
                    ]);
                }
            }
            return;
        }
    }

    private function syncLiveDoctors(int $statementId, int $admissionId): void
    {
        $stmt = $this->pdo->prepare(
            'SELECT ad.admission_doctor_id, ad.consultation_fee_charged,
                    u.first_name, u.last_name
             FROM `admission_doctor` ad
             INNER JOIN `doctor` d ON d.doctor_id = ad.doctor_id
             INNER JOIN `user` u ON u.user_id = d.user_id
             WHERE ad.admission_id = ?
               AND ad.ended_datetime IS NULL
               AND ad.consultation_fee_charged > 0'
        );
        $stmt->execute([$admissionId]);
        $doctors = $stmt->fetchAll();

        if (!$doctors) return;

        $itemId = $this->findConsultChargeItem();
        if ($itemId <= 0) {
            throw new RuntimeException('No active Professional Fee charge item is configured.');
        }

        $insert = $this->pdo->prepare(
            'INSERT INTO `charge`
                (statement_id, charge_item_id, quantity, actual_price,
                 charge_datetime, processed_by_user_id, notes,
                 source_type, admission_doctor_id)
             VALUES (?, ?, 1, ?, NOW(), ?, ?, "PROFESSIONAL_FEE", ?)'
        );
        $userId = (int)($_SESSION['user']['user_id'] ?? 0);

        foreach ($doctors as $d) {
            $marker = 'source=doctor:' . $d['admission_doctor_id'];

            $chk = $this->pdo->prepare(
                'SELECT COUNT(*) FROM `charge`
                 WHERE statement_id = ? AND source_type = "PROFESSIONAL_FEE"
                   AND admission_doctor_id = ?'
            );
            $chk->execute([$statementId, (int)$d['admission_doctor_id']]);
            if ((int)$chk->fetchColumn() > 0) continue;

            $insert->execute([
                $statementId,
                $itemId,
                (float)$d['consultation_fee_charged'],
                $userId,
                'Consultation — Dr. ' . $d['first_name'] . ' ' . $d['last_name'] . ' — ' . $marker,
                (int)$d['admission_doctor_id'],
            ]);
        }
    }

    public function recomputeStatement(int $statementId): void
    {
        $stmt = $this->pdo->prepare(
            'SELECT
                COALESCE(SUM(c.quantity * c.actual_price), 0) AS subtotal,
                COALESCE(SUM(CASE WHEN ci.is_taxable = 1 THEN c.quantity * c.actual_price ELSE 0 END), 0) AS taxable
             FROM `charge` c
             INNER JOIN `charge_item` ci ON ci.charge_item_id = c.charge_item_id
             WHERE c.statement_id = ?'
        );
        $stmt->execute([$statementId]);
        $row = $stmt->fetch();

        $subtotal = round((float)$row['subtotal'], 2);
        $taxable  = round((float)$row['taxable'], 2);

        $stmt = $this->pdo->prepare(
            'SELECT philhealth_deduction, insurance_coverage_amount,
                    hmo_discount, government_discount, due_date
             FROM `billing_statement` WHERE statement_id = ? LIMIT 1'
        );
        $stmt->execute([$statementId]);
        $cur = $stmt->fetch();
        if (!$cur) return;

        $philhealthInput = max(0.0, (float)$cur['philhealth_deduction']);
        $insuranceInput  = max(0.0, (float)$cur['insurance_coverage_amount']);
        $hmoInput        = max(0.0, (float)$cur['hmo_discount']);
        $discountInput   = max(0.0, (float)$cur['government_discount']);
        $dueDate         = $cur['due_date'] ?? null;

        $seniorPwdDiscount = 0.0;
        $vatableSubtotal   = $subtotal;

        if ($discountInput > 0) {
            $seniorPwdDiscount = round($subtotal * 0.20, 2);
            $vatableSubtotal   = round($subtotal - $seniorPwdDiscount, 2);
        }

        $taxableForVat = min($vatableSubtotal, $taxable);
        $tax = round($taxableForVat * 0.12, 2);

        $gross = round($subtotal + $tax - $seniorPwdDiscount, 2);

        $remaining = $gross;
        $appliedPhilhealth = round(min($philhealthInput, $remaining), 2);
        $remaining = round(max(0.0, $remaining - $appliedPhilhealth), 2);

        $appliedInsurance = round(min($insuranceInput, $remaining), 2);
        $remaining = round(max(0.0, $remaining - $appliedInsurance), 2);

        $appliedHmo = round(min($hmoInput, $remaining), 2);
        $remaining = round(max(0.0, $remaining - $appliedHmo), 2);

        $total = round(max(0.0, $remaining), 2);

        $stmt = $this->pdo->prepare(
            'SELECT COALESCE(SUM(amount), 0) FROM `payment` WHERE statement_id = ?'
        );
        $stmt->execute([$statementId]);
        $paid = round((float)$stmt->fetchColumn(), 2);

        $balance = round(max(0, $total - $paid), 2);

        $stmtStatus = $this->pdo->query(
            'SELECT status_id, is_paid_status, status_name FROM `billing_status`'
        );
        $statuses = $stmtStatus->fetchAll();

        $paidId    = null;
        $partialId = null;
        $pendingId = null;
        $overdueId = null;

        foreach ($statuses as $s) {
            if ((int)$s['is_paid_status'] === 1) $paidId = (int)$s['status_id'];
            $name = strtolower(trim($s['status_name']));
            if ($name === 'partially paid' || $name === 'partial payment') {
                $partialId = (int)$s['status_id'];
            }
            if ($name === 'pending') $pendingId = (int)$s['status_id'];
            if ($name === 'overdue') $overdueId = (int)$s['status_id'];
        }

        $isOverdue = $dueDate && $dueDate < date('Y-m-d') && $balance > 0;

        $newStatusId = null;

        if ($balance <= 0.001 && $paidId && ($total > 0 || $paid > 0)) {
            $newStatusId = $paidId;
        } elseif ($isOverdue && $overdueId) {
            $newStatusId = $overdueId;
        } elseif ($paid > 0 && $balance > 0.001 && $partialId) {
            $newStatusId = $partialId;
        } elseif ($pendingId) {
            $newStatusId = $pendingId;
        }

        if ($newStatusId === null) {
            $upd = $this->pdo->prepare(
                'UPDATE `billing_statement`
                 SET subtotal_amount = ?, tax_amount = ?,
                     applied_philhealth = ?, applied_insurance = ?,
                     applied_hmo = ?, applied_discount = ?,
                     total_amount = ?, amount_paid = ?, balance_amount = ?
                 WHERE statement_id = ?'
            );
            $upd->execute([
                $subtotal,
                $tax,
                $appliedPhilhealth,
                $appliedInsurance,
                $appliedHmo,
                $seniorPwdDiscount,
                $total,
                $paid,
                $balance,
                $statementId,
            ]);
        } else {
            $upd = $this->pdo->prepare(
                'UPDATE `billing_statement`
                 SET subtotal_amount = ?, tax_amount = ?,
                     applied_philhealth = ?, applied_insurance = ?,
                     applied_hmo = ?, applied_discount = ?,
                     total_amount = ?, amount_paid = ?, balance_amount = ?, status_id = ?
                 WHERE statement_id = ?'
            );
            $upd->execute([
                $subtotal,
                $tax,
                $appliedPhilhealth,
                $appliedInsurance,
                $appliedHmo,
                $seniorPwdDiscount,
                $total,
                $paid,
                $balance,
                $newStatusId,
                $statementId,
            ]);
        }
    }

    private function isOverdue(int $statementId, ?float $balance = null): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT due_date, balance_amount FROM `billing_statement`
             WHERE statement_id = ? LIMIT 1'
        );
        $stmt->execute([$statementId]);
        $row = $stmt->fetch();
        if (!$row) return false;

        $due = $row['due_date'] ?? null;
        if (!$due) return false;

        $balance ??= (float)$row['balance_amount'];
        return $due < date('Y-m-d') && $balance > 0;
    }

    private function findRoomChargeItem(string $roomTypeName): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT ci.charge_item_id
             FROM `charge_item` ci
             INNER JOIN `charge_category` cc ON cc.category_id = ci.category_id
             WHERE cc.category_name IN ("Room Charges", "Room & Board") AND ci.is_active = 1
               AND ci.item_name LIKE ?
             LIMIT 1'
        );
        $stmt->execute(['%' . $roomTypeName . '%']);
        $id = (int)$stmt->fetchColumn();
        if ($id > 0) return $id;

        $stmt = $this->pdo->prepare(
            'SELECT ci.charge_item_id
             FROM `charge_item` ci
             INNER JOIN `charge_category` cc ON cc.category_id = ci.category_id
             WHERE cc.category_name IN ("Room Charges", "Room & Board") AND ci.is_active = 1
             ORDER BY ci.charge_item_id ASC
             LIMIT 1'
        );
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    private function findConsultChargeItem(): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT ci.charge_item_id
             FROM `charge_item` ci
             LEFT JOIN `charge_category` cc ON cc.category_id = ci.category_id
             WHERE ci.is_active = 1
               AND (ci.item_code = "PROF-CONSULT" OR cc.category_name = "Professional Fee")
             ORDER BY (ci.item_code = "PROF-CONSULT") DESC, ci.charge_item_id
             LIMIT 1'
        );
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }
}