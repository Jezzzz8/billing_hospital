<?php

require_once __DIR__ . '/BillingHook.php';

class NurseController
{
    private PDO $pdo;

    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function getPendingFulfillmentReceipts(): array
    {
        $stmt = $this->pdo->query(
            'SELECT sfi.fulfillment_item_id, sfi.quantity_fulfilled, sfi.unit_price,
                    sfi.fulfilled_datetime, ci.item_name, ci.item_code, ci.unit_of_measure,
                    cc.category_name, sf.fulfillment_id, sf.fulfillment_datetime,
                    sr.request_id, sr.admission_id, d.department_name,
                    p.first_name, p.last_name,
                    COALESCE(SUM(r.quantity_received), 0) AS quantity_received
             FROM `service_fulfillment_item` sfi
             INNER JOIN `service_fulfillment` sf ON sf.fulfillment_id = sfi.fulfillment_id
             INNER JOIN `service_request_item` sri ON sri.request_item_id = sfi.request_item_id
             INNER JOIN `service_request` sr ON sr.request_id = sri.request_id
             INNER JOIN `admission` a ON a.admission_id = sr.admission_id
             INNER JOIN `patient` p ON p.patient_id = a.patient_id
             INNER JOIN `charge_item` ci ON ci.charge_item_id = sfi.charge_item_id
             INNER JOIN `charge_category` cc ON cc.category_id = ci.category_id
             LEFT JOIN `department` d ON d.department_id = sf.department_id
             LEFT JOIN `service_fulfillment_receipt_item` r
                ON r.fulfillment_item_id = sfi.fulfillment_item_id
             WHERE sf.status = "Completed"
             GROUP BY sfi.fulfillment_item_id, sfi.quantity_fulfilled, sfi.unit_price,
                      sfi.fulfilled_datetime, ci.item_name, ci.item_code, ci.unit_of_measure,
                      cc.category_name, sf.fulfillment_id, sf.fulfillment_datetime,
                      sr.request_id, sr.admission_id, d.department_name,
                      p.first_name, p.last_name
             HAVING quantity_received < sfi.quantity_fulfilled
             ORDER BY sf.fulfillment_datetime ASC, sf.fulfillment_id ASC,
                      sfi.fulfillment_item_id ASC'
        );

        return $stmt->fetchAll();
    }

    public function receiveFulfillmentItem(): array
    {
        $data = $this->input();
        $fulfillmentItemId = (int)($data['fulfillment_item_id'] ?? 0);
        $quantity = filter_var($data['quantity_received'] ?? null, FILTER_VALIDATE_INT);
        $userId = (int)($_SESSION['user']['user_id'] ?? 0);

        if ($fulfillmentItemId <= 0 || $quantity === false || $quantity <= 0) {
            throw new RuntimeException('Select a fulfilled item and enter a positive received quantity.', 422);
        }
        if ($userId <= 0) {
            throw new RuntimeException('Not authenticated.', 403);
        }

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(
                'SELECT sfi.fulfillment_item_id, sfi.quantity_fulfilled,
                        sfi.unit_price, sfi.charge_item_id,
                        sr.admission_id, ci.item_name
                 FROM `service_fulfillment_item` sfi
                 INNER JOIN `service_fulfillment` sf ON sf.fulfillment_id = sfi.fulfillment_id
                 INNER JOIN `service_request_item` sri ON sri.request_item_id = sfi.request_item_id
                 INNER JOIN `service_request` sr ON sr.request_id = sri.request_id
                 INNER JOIN `charge_item` ci ON ci.charge_item_id = sfi.charge_item_id
                 WHERE sfi.fulfillment_item_id = ? AND sf.status = "Completed"
                 LIMIT 1 FOR UPDATE'
            );
            $stmt->execute([$fulfillmentItemId]);
            $item = $stmt->fetch();
            if (!$item) {
                throw new RuntimeException('Fulfilled item not found.', 404);
            }

            $stmt = $this->pdo->prepare(
                'SELECT COALESCE(SUM(quantity_received), 0)
                 FROM `service_fulfillment_receipt_item`
                 WHERE fulfillment_item_id = ?'
            );
            $stmt->execute([$fulfillmentItemId]);
            $alreadyReceived = (int)$stmt->fetchColumn();
            $remaining = (int)$item['quantity_fulfilled'] - $alreadyReceived;
            if ($remaining <= 0) {
                throw new RuntimeException('This fulfilled item has already been fully received.', 409);
            }
            if ($quantity > $remaining) {
                throw new RuntimeException('Received quantity cannot exceed the outstanding quantity of ' . $remaining . '.', 422);
            }

            $stmt = $this->pdo->prepare(
                'INSERT INTO `service_fulfillment_receipt_item`
                    (fulfillment_item_id, quantity_received, received_by_user_id, received_datetime)
                 VALUES (?, ?, ?, NOW())'
            );
            $stmt->execute([$fulfillmentItemId, $quantity, $userId]);

            $stmt = $this->pdo->prepare(
                'SELECT statement_id FROM `billing_statement`
                 WHERE admission_id = ? LIMIT 1 FOR UPDATE'
            );
            $stmt->execute([(int)$item['admission_id']]);
            $statementId = (int)$stmt->fetchColumn();
            if ($statementId <= 0) {
                throw new RuntimeException('A billing statement is required before receiving a fulfilled item.', 409);
            }

            (new ChargeSyncService($this->pdo))->syncStatement($statementId);
            $this->pdo->commit();

            return [
                'success' => true,
                'message' => 'Received ' . $quantity . ' ' . $item['item_name'] . '; the billing statement has been updated.',
                'statement_id' => $statementId,
            ];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }

    private function statusId(string $name): int
    {
        $stmt = $this->pdo->prepare('SELECT status_id FROM `admission_status` WHERE status_name = ? LIMIT 1');
        $stmt->execute([$name]);
        return (int)$stmt->fetchColumn();
    }

    private function activeStatusIds(): array
    {
        $ids = [];
        foreach (['Admitted', 'Transferred', 'Ready for Discharge'] as $n) {
            $id = $this->statusId($n);
            if ($id > 0) $ids[] = $id;
        }
        return $ids ?: [1, 3, 4];
    }

    private function readyStatusId(): int
    {
        return $this->statusId('Ready for Discharge');
    }

    private function admittedStatusId(): int
    {
        return $this->statusId('Admitted');
    }

    private function dischargedStatusId(): int
    {
        return $this->statusId('Discharged');
    }

    public function getDashboardStats(): array
    {
        $activeIds = $this->activeStatusIds();
        $placeholders = implode(',', array_fill(0, count($activeIds), '?'));

        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM `admission` WHERE status_id IN ($placeholders)"
        );
        $stmt->execute($activeIds);
        $activeAdmissions = (int)$stmt->fetchColumn();

        $availableRooms = (int)$this->pdo->query(
            'SELECT COUNT(*) FROM `room` r
             INNER JOIN `room_status` rs ON rs.status_id = r.status_id
             WHERE r.is_active = 1 AND rs.status_name = "Available"'
        )->fetchColumn();

        $occupiedRooms = (int)$this->pdo->query(
            'SELECT COUNT(*) FROM `room` r
             INNER JOIN `room_status` rs ON rs.status_id = r.status_id
             WHERE r.is_active = 1 AND rs.status_name = "Occupied"'
        )->fetchColumn();

        $readyId = $this->readyStatusId();
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM `admission` WHERE status_id = ?');
        $stmt->execute([$readyId]);
        $readyForDischarge = (int)$stmt->fetchColumn();

        return [
            'active_admissions'   => $activeAdmissions,
            'available_rooms'     => $availableRooms,
            'occupied_rooms'      => $occupiedRooms,
            'ready_for_discharge' => $readyForDischarge,
        ];
    }

    public function getActiveAdmissions(): array
    {
        $activeIds = $this->activeStatusIds();
        $placeholders = implode(',', array_fill(0, count($activeIds), '?'));

        $stmt = $this->pdo->prepare(
            "SELECT a.admission_id, a.admission_datetime, a.chief_complaint,
                    p.first_name, p.last_name, p.patient_id,
                    ast.status_name, ast.color_code AS status_color,
                    r.room_id, r.room_number,
                    rt.room_type_name,
                    rs.status_name AS room_status_name, rs.color_code AS room_status_color
             FROM `admission` a
             INNER JOIN `patient` p ON p.patient_id = a.patient_id
             INNER JOIN `admission_status` ast ON ast.status_id = a.status_id
             LEFT JOIN `room_assignment` ra ON ra.admission_id = a.admission_id AND ra.is_active = 1
             LEFT JOIN `room` r ON r.room_id = ra.room_id
             LEFT JOIN `room_type` rt ON rt.room_type_id = r.room_type_id
             LEFT JOIN `room_status` rs ON rs.status_id = r.status_id
             WHERE a.status_id IN ($placeholders)
             ORDER BY a.admission_datetime DESC
             LIMIT 10"
        );
        $stmt->execute($activeIds);
        return $stmt->fetchAll();
    }

    public function getRoomsNeedingAttention(): array
    {
        return $this->pdo->query(
            'SELECT r.room_id, r.room_number, rs.status_name, rs.color_code
             FROM `room` r
             INNER JOIN `room_status` rs ON rs.status_id = r.status_id
             WHERE r.is_active = 1
               AND rs.status_name IN ("Maintenance", "Reserved")
             ORDER BY r.room_number'
        )->fetchAll();
    }

    public function searchPatients(string $query): array
    {
        $activeIds = $this->activeStatusIds();
        $placeholders = implode(',', array_fill(0, count($activeIds), '?'));

        $sql = "SELECT p.patient_id, p.first_name, p.last_name, p.birth_date,
                       p.contact_number, p.email, p.address,
                       g.gender_name,
                       a.admission_id, a.admission_datetime,
                       ast.status_name AS admission_status_name, ast.color_code AS admission_status_color
                FROM `patient` p
                INNER JOIN `gender` g ON g.gender_id = p.gender_id
                LEFT JOIN `admission` a ON a.patient_id = p.patient_id AND a.status_id IN ($placeholders)
                LEFT JOIN `admission_status` ast ON ast.status_id = a.status_id
                WHERE p.is_active = 1
                  AND (p.first_name LIKE ? OR p.last_name LIKE ? OR p.contact_number LIKE ? OR p.email LIKE ?)
                ORDER BY p.last_name, p.first_name
                LIMIT 50";

        $like = '%' . $query . '%';
        $params = array_merge($activeIds, [$like, $like, $like, $like]);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getPatientDetails(int $patientId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT p.*, g.gender_name
             FROM `patient` p
             INNER JOIN `gender` g ON g.gender_id = p.gender_id
             WHERE p.patient_id = ? LIMIT 1'
        );
        $stmt->execute([$patientId]);
        $patient = $stmt->fetch();
        if (!$patient) return null;

        $activeIds = $this->activeStatusIds();
        $placeholders = implode(',', array_fill(0, count($activeIds), '?'));

        $stmt = $this->pdo->prepare(
            "SELECT a.*, ast.status_name, ast.color_code
             FROM `admission` a
             INNER JOIN `admission_status` ast ON ast.status_id = a.status_id
             WHERE a.patient_id = ? AND a.status_id IN ($placeholders)
             ORDER BY a.admission_datetime DESC LIMIT 1"
        );
        $stmt->execute(array_merge([$patientId], $activeIds));
        $patient['current_admission'] = $stmt->fetch() ?: null;

        return $patient;
    }

    public function getPatientByAdmission(int $admissionId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT a.admission_id, a.status_id, a.admission_datetime, a.chief_complaint,
                    a.admission_type, a.notes, a.total_room_transfers,
                    p.patient_id, p.first_name, p.last_name, p.birth_date,
                    p.contact_number, p.email, p.address,
                    g.gender_name,
                    ast.status_name, ast.color_code
             FROM `admission` a
             INNER JOIN `patient` p ON p.patient_id = a.patient_id
             INNER JOIN `gender` g ON g.gender_id = p.gender_id
             INNER JOIN `admission_status` ast ON ast.status_id = a.status_id
             WHERE a.admission_id = ? LIMIT 1'
        );
        $stmt->execute([$admissionId]);
        return $stmt->fetch() ?: null;
    }

    public function registerPatient(): void
    {
        $data = $this->input();
        $errors = $this->validatePatient($data);
        if ($errors) $this->json(422, ['success' => false, 'message' => 'Please fix the highlighted fields.', 'errors' => $errors]);

        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO `patient`
                    (gender_id, first_name, last_name, birth_date, contact_number, address, email,
                     emergency_contact, emergency_contact_number, medical_history, is_active)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)'
            );
            $stmt->execute([
                (int)$data['gender_id'],
                $data['first_name'],
                $data['last_name'],
                !empty($data['birth_date']) ? $data['birth_date'] : null,
                !empty($data['contact_number']) ? $data['contact_number'] : null,
                !empty($data['address']) ? $data['address'] : null,
                !empty($data['email']) ? $data['email'] : null,
                !empty($data['emergency_contact']) ? $data['emergency_contact'] : null,
                !empty($data['emergency_contact_number']) ? $data['emergency_contact_number'] : null,
                !empty($data['medical_history']) ? $data['medical_history'] : null,
            ]);

            $patientId = (int)$this->pdo->lastInsertId();

            $this->json(201, [
                'success'    => true,
                'message'    => 'Patient registered successfully.',
                'patient_id' => $patientId,
            ]);
        } catch (Throwable $e) {
            error_log('[NurseController::registerPatient] ' . $e->getMessage());
            $this->json(500, ['success' => false, 'message' => 'Could not register patient.']);
        }
    }

    public function createAdmission(): void
    {
        $data = $this->input();
        $errors = $this->validateAdmission($data);
        if ($errors) $this->json(422, ['success' => false, 'message' => 'Please fix the highlighted fields.', 'errors' => $errors]);

        try {
            $this->pdo->beginTransaction();

            $patientId = (int)$data['patient_id'];
            $activeIds = $this->activeStatusIds();
            $placeholders = implode(',', array_fill(0, count($activeIds), '?'));

            $stmt = $this->pdo->prepare(
                "SELECT admission_id FROM `admission`
                WHERE patient_id = ? AND status_id IN ($placeholders) LIMIT 1"
            );
            $stmt->execute(array_merge([$patientId], $activeIds));
            if ($stmt->fetch()) {
                $this->pdo->rollBack();
                $this->json(409, ['success' => false, 'message' => 'This patient already has an active admission.']);
            }

            $preferredRoomTypeId = (int)$data['preferred_room_type_id'];
            $stmt = $this->pdo->prepare('SELECT room_type_name FROM `room_type` WHERE room_type_id = ? LIMIT 1');
            $stmt->execute([$preferredRoomTypeId]);
            $preferredRoomTypeName = (string)$stmt->fetchColumn();
            if ($preferredRoomTypeName === '') {
                $this->pdo->rollBack();
                $this->json(422, ['success' => false, 'message' => 'The preferred room type is not available.']);
            }

            $preferredRooms = $this->getAvailableRooms($preferredRoomTypeId);
            $selectedRoom = null;
            foreach ($preferredRooms as $room) {
                if ((int)$room['available_beds'] > 0) {
                    $selectedRoom = $room;
                    break;
                }
            }

            $temporaryPlacement = false;
            if (!$selectedRoom) {
                foreach ($this->getAvailableRooms() as $room) {
                    if ((int)$room['available_beds'] > 0) {
                        $selectedRoom = $room;
                        $temporaryPlacement = (int)$room['room_type_id'] !== $preferredRoomTypeId;
                        break;
                    }
                }
            }
            if (!$selectedRoom) {
                $this->pdo->rollBack();
                $this->json(409, ['success' => false, 'message' => 'No beds are currently available. The admission was not created.']);
            }

            $statusId = $this->admittedStatusId();
            if ($statusId <= 0) $statusId = 1;

            $admissionNotes = trim((string)($data['admission_notes'] ?? ''));
            $preferenceNote = 'Preferred room type: ' . $preferredRoomTypeName
                . '. [preferred_room_type_id=' . $preferredRoomTypeId . ']';
            $admissionNotes = $admissionNotes === ''
                ? $preferenceNote
                : $admissionNotes . "\n\n" . $preferenceNote;

            $stmt = $this->pdo->prepare(
                'INSERT INTO `admission`
                    (patient_id, status_id, admission_datetime, chief_complaint,
                    admission_type, total_room_transfers, admitted_by_user_id, notes)
                VALUES (?, ?, NOW(), ?, ?, 0, ?, ?)'
            );
            $stmt->execute([
                $patientId,
                $statusId,
                !empty($data['chief_complaint']) ? $data['chief_complaint'] : null,
                !empty($data['admission_type']) ? $data['admission_type'] : 'Emergency',
                (int)$_SESSION['user']['user_id'],
                $admissionNotes,
            ]);

            $admissionId = (int)$this->pdo->lastInsertId();

            $pendingBillingStatusId = (int)$this->pdo->query(
                'SELECT status_id FROM `billing_status`
                WHERE status_name = "Pending" AND is_paid_status = 0
                ORDER BY status_id LIMIT 1'
            )->fetchColumn();
            if ($pendingBillingStatusId <= 0) {
                throw new RuntimeException('Billing status "Pending" is not configured.');
            }

            $patientSnapshot = $this->pdo->prepare(
                'SELECT p.birth_date, p.address, g.gender_name
                FROM `patient` p INNER JOIN `gender` g ON g.gender_id = p.gender_id
                WHERE p.patient_id = ? LIMIT 1'
            );
            $patientSnapshot->execute([$patientId]);
            $patientRow = $patientSnapshot->fetch() ?: [];

            $patientAge = 0;
            if (!empty($patientRow['birth_date'])) {
                try { $patientAge = (new DateTime($patientRow['birth_date']))->diff(new DateTime())->y; }
                catch (Throwable $e) { $patientAge = 0; }
            }

            $existingStatement = $this->pdo->prepare(
                'SELECT statement_id FROM `billing_statement` WHERE admission_id = ? LIMIT 1'
            );
            $existingStatement->execute([$admissionId]);
            if (!$existingStatement->fetchColumn()) {
                $createStatement = $this->pdo->prepare(
                    'INSERT INTO `billing_statement`
                        (admission_id, status_id, statement_date, subtotal_amount,
                        insurance_coverage_amount, government_discount,
                        philhealth_deduction, hmo_discount, tax_amount,
                        total_amount, amount_paid, balance_amount, created_by_user_id,
                        case_type, patient_type, phic_status,
                        patient_age, patient_gender, patient_address,
                        room_number, room_type_name, room_rate)
                    VALUES (?, ?, NOW(), 0, 0, 0, 0, 0, 0, 0, 0, 0, ?,
                            ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $createStatement->execute([
                    $admissionId,
                    $pendingBillingStatusId,
                    (int)$_SESSION['user']['user_id'],
                    'In Patient',
                    'In Patient',
                    'Non-Member',
                    $patientAge,
                    $patientRow['gender_name'] ?? null,
                    $patientRow['address'] ?? null,
                    $selectedRoom['room_number'] ?? null,
                    $selectedRoom['room_type_name'] ?? null,
                    (float)($selectedRoom['rate_per_day'] ?? 0),
                ]);
            }

            $data['transfer_reason'] = $temporaryPlacement
                ? 'Temporary bed; preferred room type: ' . $preferredRoomTypeName . '.'
                : 'Initial placement; preferred room type: ' . $preferredRoomTypeName . '.';
            $this->assignRoom($admissionId, (int)$selectedRoom['room_id'], $data);

            $doctorIds   = $data['doctor_ids']   ?? [];
            $doctorRoles = $data['doctor_roles'] ?? [];

            if (is_array($doctorIds) && $doctorIds) {
                $this->saveAdmissionDoctors($admissionId, $doctorIds, $doctorRoles);
                BillingHook::emit($this->pdo, $admissionId);
            }

            $this->pdo->prepare(
                'UPDATE `billing_statement`
                SET attending_physician = (
                    SELECT GROUP_CONCAT(
                        DISTINCT CONCAT("Dr. ", u.first_name, " ", u.last_name)
                        ORDER BY ad.assigned_datetime SEPARATOR ", "
                    )
                    FROM `admission_doctor` ad
                    INNER JOIN `doctor` d ON d.doctor_id = ad.doctor_id
                    INNER JOIN `user` u ON u.user_id = d.user_id
                    WHERE ad.admission_id = ? AND ad.ended_datetime IS NULL
                )
                WHERE admission_id = ?'
            )->execute([$admissionId, $admissionId]);

            $this->pdo->commit();

            try { BillingHook::flushDeferred(); } catch (Throwable $e) {
                error_log('[NurseController::createAdmission::flushDeferred] ' . $e->getMessage());
            }

            $this->json(201, [
                'success'      => true,
                'message'      => 'Patient admitted successfully.',
                'admission_id' => $admissionId,
            ]);
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            error_log('[NurseController::createAdmission] ' . $e->getMessage());
            $status = stripos($e->getMessage(), 'available bed') !== false ? 409 : 500;
            $message = $status === 409
                ? 'The selected bed was just assigned. Please retry the admission.'
                : 'Could not create admission.';
            $this->json($status, [
                'success' => false,
                'message' => $message,
                'debug'   => $e->getMessage(),
            ]);
        }
    }

    public function getAdmissions(): array
    {
        return $this->pdo->query(
            'SELECT a.admission_id, a.admission_datetime, a.discharge_datetime,
                    a.chief_complaint, a.admission_type, a.total_room_transfers,
                    a.status_id AS admission_status_id,
                    p.patient_id, p.first_name, p.last_name,
                    ast.status_name AS admission_status_name, ast.color_code AS admission_status_color,
                    r.room_id, r.room_number,
                    rt.room_type_name,
                    rs.status_name AS room_status_name, rs.color_code AS room_status_color
            FROM `admission` a
            INNER JOIN `patient` p ON p.patient_id = a.patient_id
            INNER JOIN `admission_status` ast ON ast.status_id = a.status_id
            LEFT JOIN `room_assignment` ra ON ra.admission_id = a.admission_id AND ra.is_active = 1
            LEFT JOIN `room` r ON r.room_id = ra.room_id
            LEFT JOIN `room_type` rt ON rt.room_type_id = r.room_type_id
            LEFT JOIN `room_status` rs ON rs.status_id = r.status_id
            ORDER BY a.admission_datetime DESC'
        )->fetchAll();
    }

    public function getAvailableRooms(?int $roomTypeId = null): array
    {
        $sql = 'SELECT r.room_id, r.room_number,
                    rt.room_type_id, rt.room_type_name, rt.rate_per_day, rt.capacity,
                    rs.status_name, rs.color_code,
                    fl.floor_level_id,
                    fl.floor_level_name,
                    fl.floor_level_name AS floor_level,
                    b.building_id,
                    b.building_name,
                    COALESCE(occupancy.occupied_beds, 0) AS occupied_beds,
                    GREATEST(rt.capacity - COALESCE(occupancy.occupied_beds, 0), 0) AS available_beds
                FROM `room` r
                INNER JOIN `room_type` rt ON rt.room_type_id = r.room_type_id
                INNER JOIN `room_status` rs ON rs.status_id = r.status_id
                INNER JOIN `floor_level` fl ON fl.floor_level_id = r.floor_level_id
                INNER JOIN `building` b ON b.building_id = fl.building_id
                LEFT JOIN (
                    SELECT room_id, COUNT(*) AS occupied_beds
                    FROM `room_assignment`
                    WHERE is_active = 1 AND end_datetime IS NULL
                    GROUP BY room_id
                ) occupancy ON occupancy.room_id = r.room_id
                WHERE r.is_active = 1
                  AND rs.status_name IN ("Available", "Occupied")
                  AND rt.capacity > COALESCE(occupancy.occupied_beds, 0)';

        $params = [];

        if ($roomTypeId) {
            $sql .= ' AND rt.room_type_id = ?';
            $params[] = $roomTypeId;
        }

        $sql .= ' ORDER BY
                    CASE WHEN rs.status_name = "Available" THEN 0 ELSE 1 END,
                    b.building_name, fl.floor_level_name, rt.room_type_name, r.room_number';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getRoomsByType(): array
    {
        return $this->pdo->query(
            'SELECT rt.room_type_id, rt.room_type_name, rt.rate_per_day,
                    COUNT(r.room_id) AS total_rooms,
                    SUM(CASE WHEN rs.status_name = "Available" THEN 1 ELSE 0 END) AS available_rooms
             FROM `room_type` rt
             LEFT JOIN `room` r ON r.room_type_id = rt.room_type_id AND r.is_active = 1
             LEFT JOIN `room_status` rs ON rs.status_id = r.status_id
             GROUP BY rt.room_type_id
             ORDER BY rt.room_type_name'
        )->fetchAll();
    }

    public function getAllRooms(): array
    {
        return $this->pdo->query(
            'SELECT r.room_id, r.room_number, r.is_active,
                    rt.room_type_id, rt.room_type_name, rt.rate_per_day, rt.capacity,
                    rs.status_id, rs.status_name, rs.color_code,
                    fl.floor_level_id, fl.floor_level_name,
                    b.building_id, b.building_name
             FROM `room` r
             INNER JOIN `room_type` rt ON rt.room_type_id = r.room_type_id
             INNER JOIN `room_status` rs ON rs.status_id = r.status_id
             INNER JOIN `floor_level` fl ON fl.floor_level_id = r.floor_level_id
             INNER JOIN `building` b ON b.building_id = fl.building_id
             WHERE r.is_active = 1
             ORDER BY b.building_name, fl.floor_level_name, rt.room_type_name, r.room_number'
        )->fetchAll();
    }

    public function getRoomAssignments(?int $admissionId = null): array
    {
        $sql = 'SELECT ra.room_assignment_id, ra.admission_id, ra.start_datetime, ra.end_datetime,
                       ra.daily_rate_at_assignment, ra.transfer_reason, ra.is_active,
                       p.patient_id, p.first_name, p.last_name,
                       r.room_id, r.room_number,
                       rt.room_type_name, rt.rate_per_day,
                       rs.status_name AS room_status_name, rs.color_code AS room_status_color,
                       a.admission_datetime, a.chief_complaint,
                       ast.status_name AS admission_status_name,
                       fl.floor_level_name, b.building_name
                FROM `room_assignment` ra
                INNER JOIN `admission` a ON a.admission_id = ra.admission_id
                INNER JOIN `patient` p ON p.patient_id = a.patient_id
                INNER JOIN `room` r ON r.room_id = ra.room_id
                INNER JOIN `room_type` rt ON rt.room_type_id = r.room_type_id
                INNER JOIN `room_status` rs ON rs.status_id = r.status_id
                INNER JOIN `admission_status` ast ON ast.status_id = a.status_id
                INNER JOIN `floor_level` fl ON fl.floor_level_id = r.floor_level_id
                INNER JOIN `building` b ON b.building_id = fl.building_id';

        $params = [];

        if ($admissionId) {
            $sql .= ' WHERE ra.admission_id = ?';
            $params[] = $admissionId;
        }

        $sql .= ' ORDER BY ra.start_datetime DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getRoomTransferHistory(int $admissionId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT ra.room_assignment_id, ra.start_datetime, ra.end_datetime,
                    ra.daily_rate_at_assignment, ra.transfer_reason,
                    r.room_number, rt.room_type_name,
                    fl.floor_level_name, b.building_name
             FROM `room_assignment` ra
             INNER JOIN `room` r ON r.room_id = ra.room_id
             INNER JOIN `room_type` rt ON rt.room_type_id = r.room_type_id
             INNER JOIN `floor_level` fl ON fl.floor_level_id = r.floor_level_id
             INNER JOIN `building` b ON b.building_id = fl.building_id
             WHERE ra.admission_id = ?
             ORDER BY ra.start_datetime ASC'
        );
        $stmt->execute([$admissionId]);
        return $stmt->fetchAll();
    }

    public function assignRoom(int $admissionId, int $roomId, array $data = []): void
    {
        $stmt = $this->pdo->prepare(
            'SELECT room_assignment_id FROM `room_assignment`
             WHERE admission_id = ? AND room_id = ? AND is_active = 1 AND end_datetime IS NULL
             LIMIT 1'
        );
        $stmt->execute([$admissionId, $roomId]);
        if ($stmt->fetch()) {
            return;
        }

        $stmt = $this->pdo->prepare(
            'SELECT r.room_id, rt.rate_per_day, rt.capacity
             FROM `room` r
             INNER JOIN `room_type` rt ON rt.room_type_id = r.room_type_id
             INNER JOIN `room_status` rs ON rs.status_id = r.status_id
             WHERE r.room_id = ? AND r.is_active = 1
               AND rs.status_name IN ("Available", "Occupied")
             LIMIT 1
             FOR UPDATE'
        );
        $stmt->execute([$roomId]);
        $room = $stmt->fetch();

        if (!$room) {
            throw new Exception('This room has no available beds.');
        }

        $stmt = $this->pdo->prepare(
            'SELECT room_assignment_id FROM `room_assignment`
             WHERE room_id = ? AND is_active = 1 AND end_datetime IS NULL
             FOR UPDATE'
        );
        $stmt->execute([$roomId]);
        if (count($stmt->fetchAll()) >= (int)$room['capacity']) {
            throw new Exception('This room no longer has an available bed.');
        }

        $rate = (float)$room['rate_per_day'];

        $stmt = $this->pdo->prepare(
            'INSERT INTO `room_assignment`
                (admission_id, room_id, start_datetime, end_datetime,
                 daily_rate_at_assignment, transfer_reason, transferred_by_user_id, is_active)
             VALUES (?, ?, NOW(), NULL, ?, ?, ?, 1)'
        );
        $stmt->execute([
            $admissionId,
            $roomId,
            $rate,
            !empty($data['transfer_reason']) ? $data['transfer_reason'] : null,
            (int)$_SESSION['user']['user_id'],
        ]);

        $this->syncRoomStatus($roomId);
        BillingHook::emit($this->pdo, $admissionId);
    }

    private function syncRoomStatus(int $roomId): void
    {
        $stmt = $this->pdo->prepare(
            'SELECT r.status_id, rs.status_name, rt.capacity,
                    (SELECT COUNT(*) FROM `room_assignment` ra
                     WHERE ra.room_id = r.room_id
                       AND ra.is_active = 1
                       AND ra.end_datetime IS NULL) AS occupied_beds
             FROM `room` r
             INNER JOIN `room_type` rt ON rt.room_type_id = r.room_type_id
             INNER JOIN `room_status` rs ON rs.status_id = r.status_id
             WHERE r.room_id = ? LIMIT 1'
        );
        $stmt->execute([$roomId]);
        $room = $stmt->fetch();
        if (!$room || !in_array($room['status_name'], ['Available', 'Occupied'], true)) {
            return;
        }

        $statusName = (int)$room['occupied_beds'] >= (int)$room['capacity']
            ? 'Occupied'
            : 'Available';
        $stmt = $this->pdo->prepare(
            'UPDATE `room`
             SET status_id = (SELECT status_id FROM `room_status` WHERE status_name = ? LIMIT 1)
             WHERE room_id = ?'
        );
        $stmt->execute([$statusName, $roomId]);
    }

    public function assignRoomApi(): void
    {
        $data = $this->input();
        $admissionId = (int)($data['admission_id'] ?? 0);
        $roomId = (int)($data['room_id'] ?? 0);

        if ($admissionId <= 0 || $roomId <= 0) {
            $this->json(422, ['success' => false, 'message' => 'Admission and room are required.']);
        }

        try {
            $this->pdo->beginTransaction();

            $activeIds = $this->activeStatusIds();
            $placeholders = implode(',', array_fill(0, count($activeIds), '?'));

            $stmt = $this->pdo->prepare(
                "SELECT admission_id FROM `admission`
                 WHERE admission_id = ? AND status_id IN ($placeholders) LIMIT 1"
            );
            $stmt->execute(array_merge([$admissionId], $activeIds));
            if (!$stmt->fetch()) {
                $this->pdo->rollBack();
                $this->json(404, ['success' => false, 'message' => 'Active admission not found.']);
            }

            $stmt = $this->pdo->prepare(
                'SELECT room_assignment_id, room_id
                 FROM `room_assignment`
                 WHERE admission_id = ? AND is_active = 1 AND end_datetime IS NULL
                 LIMIT 1'
            );
            $stmt->execute([$admissionId]);
            $currentAssignment = $stmt->fetch();

            if ($currentAssignment && (int)$currentAssignment['room_id'] === $roomId) {
                $this->pdo->commit();
                $this->json(200, ['success' => true, 'message' => 'The patient is already assigned to that room.']);
            }

            if ($currentAssignment) {
                $stmt = $this->pdo->prepare(
                    'UPDATE `room_assignment`
                     SET is_active = 0, end_datetime = NOW()
                     WHERE room_assignment_id = ?'
                );
                $stmt->execute([$currentAssignment['room_assignment_id']]);

                $this->syncRoomStatus((int)$currentAssignment['room_id']);
            }

            $this->assignRoom($admissionId, $roomId, $data);

            $this->pdo->commit();
            try { BillingHook::flushDeferred(); } catch (Throwable $e) {
                error_log('[NurseController::assignRoomApi::flushDeferred] ' . $e->getMessage());
            }
            $this->json(200, ['success' => true, 'message' => 'Room assigned successfully.']);
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            error_log('[NurseController::assignRoomApi] ' . $e->getMessage());
            $status = stripos($e->getMessage(), 'available bed') !== false ? 409 : 500;
            $this->json($status, ['success' => false, 'message' => $e->getMessage() ?: 'Could not assign room.']);
        }
    }

    public function transferRoom(): void
    {
        $data = $this->input();
        $admissionId = (int)($data['admission_id'] ?? 0);
        $newRoomId = (int)($data['new_room_id'] ?? 0);

        if ($admissionId <= 0 || $newRoomId <= 0) {
            $this->json(422, ['success' => false, 'message' => 'Admission and new room are required.']);
        }

        try {
            $this->pdo->beginTransaction();

            $activeIds = $this->activeStatusIds();
            $placeholders = implode(',', array_fill(0, count($activeIds), '?'));

            $stmt = $this->pdo->prepare(
                "SELECT admission_id, notes FROM `admission`
                 WHERE admission_id = ? AND status_id IN ($placeholders) LIMIT 1"
            );
            $stmt->execute(array_merge([$admissionId], $activeIds));
            $activeAdmission = $stmt->fetch();
            if (!$activeAdmission) {
                $this->pdo->rollBack();
                $this->json(404, ['success' => false, 'message' => 'Active admission not found.']);
            }

            $stmt = $this->pdo->prepare(
                'SELECT room_assignment_id, room_id
                 FROM `room_assignment`
                 WHERE admission_id = ? AND is_active = 1 AND end_datetime IS NULL
                 LIMIT 1'
            );
            $stmt->execute([$admissionId]);
            $currentAssignment = $stmt->fetch();

            if ($currentAssignment && (int)$currentAssignment['room_id'] === $newRoomId) {
                $this->pdo->rollBack();
                $this->json(409, ['success' => false, 'message' => 'The patient is already assigned to that room.']);
            }

            if ($currentAssignment) {
                $stmt = $this->pdo->prepare(
                    'UPDATE `room_assignment`
                     SET is_active = 0, end_datetime = NOW(), transfer_reason = ?
                     WHERE room_assignment_id = ?'
                );
                $stmt->execute([
                    !empty($data['transfer_reason']) ? $data['transfer_reason'] : 'Room transfer',
                    $currentAssignment['room_assignment_id'],
                ]);

                $this->syncRoomStatus((int)$currentAssignment['room_id']);
                if (!empty($data['set_old_room_maintenance'])) {
                    $stmt = $this->pdo->prepare(
                        'UPDATE `room` SET status_id = (SELECT status_id FROM `room_status` WHERE status_name = "Maintenance" LIMIT 1)
                         WHERE room_id = ?'
                    );
                    $stmt->execute([$currentAssignment['room_id']]);
                }
            }

            if (empty($data['transfer_reason'])
                && preg_match('/preferred_room_type_id=(\d+)/', (string)$activeAdmission['notes'], $matches)) {
                $preferredRoomTypeId = (int)$matches[1];
                $stmt = $this->pdo->prepare(
                    'SELECT r.room_type_id, rt.room_type_name
                     FROM `room` r
                     INNER JOIN `room_type` rt ON rt.room_type_id = r.room_type_id
                     WHERE r.room_id = ? LIMIT 1'
                );
                $stmt->execute([$newRoomId]);
                $newRoomType = $stmt->fetch();
                if ($newRoomType) {
                    $data['transfer_reason'] = (int)$newRoomType['room_type_id'] === $preferredRoomTypeId
                        ? 'Transfer to preferred room type: ' . $newRoomType['room_type_name'] . '.'
                        : 'Temporary bed while waiting for preferred room type.';
                }
            }
            $this->assignRoom($admissionId, $newRoomId, $data);

            $stmt = $this->pdo->prepare(
                'UPDATE `admission` SET total_room_transfers = total_room_transfers + 1
                 WHERE admission_id = ?'
            );
            $stmt->execute([$admissionId]);

            BillingHook::emit($this->pdo, $admissionId);

            $this->pdo->commit();
            try { BillingHook::flushDeferred(); } catch (Throwable $e) {
                error_log('[NurseController::transferRoom::flushDeferred] ' . $e->getMessage());
            }
            $this->json(200, ['success' => true, 'message' => 'Room transfer completed successfully.']);
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            error_log('[NurseController::transferRoom] ' . $e->getMessage());
            $status = stripos($e->getMessage(), 'available bed') !== false ? 409 : 500;
            $this->json($status, ['success' => false, 'message' => $e->getMessage() ?: 'Could not transfer room.']);
        }
    }

    public function getDoctors(): array
    {
        return $this->pdo->query(
            'SELECT d.doctor_id, d.consultation_fee,
                    u.first_name, u.last_name
             FROM `doctor` d
             INNER JOIN `user` u ON u.user_id = d.user_id
             WHERE d.is_active = 1 AND u.is_active = 1
             ORDER BY u.last_name, u.first_name'
        )->fetchAll();
    }

    public function getAdmissionDoctors(int $admissionId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT ad.admission_doctor_id, ad.doctor_id, ad.doctor_role,
                    ad.consultation_fee_charged, ad.assigned_datetime,
                    u.first_name, u.last_name
             FROM `admission_doctor` ad
             INNER JOIN `doctor` d ON d.doctor_id = ad.doctor_id
             INNER JOIN `user` u ON u.user_id = d.user_id
             WHERE ad.admission_id = ? AND ad.ended_datetime IS NULL
             ORDER BY ad.assigned_datetime'
        );
        $stmt->execute([$admissionId]);
        return $stmt->fetchAll();
    }

    private function saveAdmissionDoctors(int $admissionId, array $doctorIds, array $doctorRoles): void
    {
        $insert = $this->pdo->prepare(
            'INSERT INTO `admission_doctor`
                (admission_id, doctor_id, doctor_role, assigned_datetime,
                 ended_datetime, consultation_fee_charged)
             VALUES (?, ?, ?, NOW(), NULL, ?)'
        );

        $feeLookup = $this->pdo->prepare(
            'SELECT consultation_fee FROM `doctor` WHERE doctor_id = ? LIMIT 1'
        );

        $exists = $this->pdo->prepare(
            'SELECT admission_doctor_id FROM `admission_doctor`
             WHERE admission_id = ? AND doctor_id = ? AND ended_datetime IS NULL
             LIMIT 1'
        );

        foreach ($doctorIds as $doctorId) {
            $doctorId = (int)$doctorId;
            if ($doctorId <= 0) continue;

            $exists->execute([$admissionId, $doctorId]);
            if ($exists->fetch()) continue;

            $role = $doctorRoles[$doctorId] ?? 'Attending';

            $feeLookup->execute([$doctorId]);
            $fee = (float)($feeLookup->fetchColumn() ?: 0);

            $insert->execute([$admissionId, $doctorId, $role, $fee]);
        }
    }

    public function assignDoctorApi(): void
    {
        $data = $this->input();
        $admissionId = (int)($data['admission_id'] ?? 0);
        $doctorId    = (int)($data['doctor_id'] ?? 0);
        $role        = !empty($data['doctor_role']) ? $data['doctor_role'] : 'Attending';

        if ($admissionId <= 0 || $doctorId <= 0) {
            $this->json(422, ['success' => false, 'message' => 'Admission and doctor are required.']);
        }

        try {
            $this->pdo->beginTransaction();

            $activeIds = $this->activeStatusIds();
            $placeholders = implode(',', array_fill(0, count($activeIds), '?'));

            $stmt = $this->pdo->prepare(
                "SELECT admission_id FROM `admission`
                 WHERE admission_id = ? AND status_id IN ($placeholders) LIMIT 1"
            );
            $stmt->execute(array_merge([$admissionId], $activeIds));
            if (!$stmt->fetch()) {
                $this->pdo->rollBack();
                $this->json(404, ['success' => false, 'message' => 'Active admission not found.']);
            }

            $stmt = $this->pdo->prepare(
                'SELECT d.doctor_id, d.consultation_fee
                 FROM `doctor` d
                 INNER JOIN `user` u ON u.user_id = d.user_id
                 WHERE d.doctor_id = ? AND d.is_active = 1 AND u.is_active = 1
                 LIMIT 1'
            );
            $stmt->execute([$doctorId]);
            $doctor = $stmt->fetch();
            if (!$doctor) {
                $this->pdo->rollBack();
                $this->json(404, ['success' => false, 'message' => 'Doctor not found or inactive.']);
            }

            $dup = $this->pdo->prepare(
                'SELECT admission_doctor_id FROM `admission_doctor`
                 WHERE admission_id = ? AND doctor_id = ? AND ended_datetime IS NULL
                 LIMIT 1'
            );
            $dup->execute([$admissionId, $doctorId]);
            if ($dup->fetch()) {
                $this->pdo->rollBack();
                $this->json(409, ['success' => false, 'message' => 'This doctor is already assigned to this admission.']);
            }

            $this->saveAdmissionDoctors($admissionId, [$doctorId], [$doctorId => $role]);

            BillingHook::emit($this->pdo, $admissionId);

            $this->pdo->commit();
            try { BillingHook::flushDeferred(); } catch (Throwable $e) {
                error_log('[NurseController::assignDoctorApi::flushDeferred] ' . $e->getMessage());
            }

            $this->json(200, ['success' => true, 'message' => 'Doctor assigned successfully.']);
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            error_log('[NurseController::assignDoctorApi] ' . $e->getMessage());
            $this->json(500, ['success' => false, 'message' => 'Could not assign doctor: ' . $e->getMessage()]);
        }
    }

    public function removeDoctorApi(): void
    {
        $admissionDoctorId = (int)($_GET['id'] ?? 0);
        if ($admissionDoctorId <= 0) {
            $this->json(400, ['success' => false, 'message' => 'Missing assignment id.']);
        }

        try {
            $stmt = $this->pdo->prepare(
                'UPDATE `admission_doctor`
                 SET ended_datetime = NOW()
                 WHERE admission_doctor_id = ? AND ended_datetime IS NULL'
            );
            $stmt->execute([$admissionDoctorId]);

            $this->json(200, ['success' => true, 'message' => 'Doctor removed from admission.']);
        } catch (Throwable $e) {
            error_log('[NurseController::removeDoctorApi] ' . $e->getMessage());
            $this->json(500, ['success' => false, 'message' => 'Could not remove doctor.']);
        }
    }

    public function getReadyForDischarge(): array
    {
        $readyId = $this->readyStatusId();

        $stmt = $this->pdo->prepare(
            'SELECT a.admission_id, a.admission_datetime, a.chief_complaint,
                    a.discharge_datetime,
                    p.patient_id, p.first_name, p.last_name,
                    r.room_number, rt.room_type_name,
                    ast.status_name AS admission_status_name,
                    ast.color_code  AS admission_status_color,
                    bs.statement_id,
                    bs.total_amount,
                    bs.amount_paid,
                    bs.balance_amount,
                    bs.due_date,
                    bst.status_name AS billing_status_name,
                    bst.color_code  AS billing_status_color
             FROM `admission` a
             INNER JOIN `patient` p ON p.patient_id = a.patient_id
             INNER JOIN `admission_status` ast ON ast.status_id = a.status_id
             LEFT JOIN `room_assignment` ra ON ra.admission_id = a.admission_id AND ra.is_active = 1
             LEFT JOIN `room` r ON r.room_id = ra.room_id
             LEFT JOIN `room_type` rt ON rt.room_type_id = r.room_type_id
             LEFT JOIN `billing_statement` bs ON bs.admission_id = a.admission_id
             LEFT JOIN `billing_status` bst ON bst.status_id = bs.status_id
             WHERE a.status_id = ?
             ORDER BY a.admission_datetime ASC'
        );
        $stmt->execute([$readyId]);
        return $stmt->fetchAll();
    }

    public function dischargePatient(): void
    {
        $data = $this->input();
        $admissionId = (int)($data['admission_id'] ?? 0);

        if ($admissionId <= 0) {
            $this->json(422, ['success' => false, 'message' => 'Admission ID is required.']);
        }

        $userId = (int)($_SESSION['user']['user_id'] ?? 0);
        if ($userId <= 0) {
            $this->json(403, ['success' => false, 'message' => 'Not authenticated.']);
        }

        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare(
                'SELECT a.admission_id, a.patient_id, a.status_id, ast.status_name
                 FROM `admission` a
                 INNER JOIN `admission_status` ast ON ast.status_id = a.status_id
                 WHERE a.admission_id = ? LIMIT 1 FOR UPDATE'
            );
            $stmt->execute([$admissionId]);
            $admission = $stmt->fetch();

            if (!$admission) {
                $this->pdo->rollBack();
                $this->json(404, ['success' => false, 'message' => 'Admission not found.']);
            }

            $readyId = $this->readyStatusId();
            if ($readyId <= 0 || (int)$admission['status_id'] !== $readyId) {
                $this->pdo->rollBack();
                $this->json(409, [
                    'success' => false,
                    'message' => 'This patient must be marked Ready for Discharge by the attending doctor first. '
                        . 'Current status: ' . $admission['status_name'] . '.',
                ]);
            }

            $stmt = $this->pdo->prepare(
                'SELECT COUNT(*) FROM `service_request`
                 WHERE admission_id = ? AND status IN ("Pending", "In Progress")'
            );
            $stmt->execute([$admissionId]);
            if ((int)$stmt->fetchColumn() > 0) {
                $this->pdo->rollBack();
                $this->json(409, [
                    'success' => false,
                    'message' => 'This patient still has service requests that are pending or in progress.',
                ]);
            }

            $stmt = $this->pdo->prepare(
                'SELECT COUNT(*)
                 FROM `service_fulfillment_item` sfi
                 INNER JOIN `service_fulfillment` sf ON sf.fulfillment_id = sfi.fulfillment_id
                 INNER JOIN `service_request_item` sri ON sri.request_item_id = sfi.request_item_id
                 INNER JOIN `service_request` sr ON sr.request_id = sri.request_id
                 LEFT JOIN (
                     SELECT fulfillment_item_id, SUM(quantity_received) AS quantity_received
                     FROM `service_fulfillment_receipt_item`
                     GROUP BY fulfillment_item_id
                 ) receipts ON receipts.fulfillment_item_id = sfi.fulfillment_item_id
                 WHERE sr.admission_id = ?
                   AND sf.status = "Completed"
                   AND sfi.quantity_fulfilled > COALESCE(receipts.quantity_received, 0)'
            );
            $stmt->execute([$admissionId]);
            if ((int)$stmt->fetchColumn() > 0) {
                $this->pdo->rollBack();
                $this->json(409, [
                    'success' => false,
                    'message' => 'Department-fulfilled items must be received by nursing before discharge.',
                ]);
            }

            $stmt = $this->pdo->prepare(
                'SELECT statement_id FROM `billing_statement` WHERE admission_id = ? LIMIT 1 FOR UPDATE'
            );
            $stmt->execute([$admissionId]);
            $statementId = (int)$stmt->fetchColumn();
            if ($statementId <= 0) {
                $this->pdo->rollBack();
                $this->json(409, [
                    'success' => false,
                    'message' => 'A billing statement must exist before discharge.',
                ]);
            }
            (new ChargeSyncService($this->pdo))->syncStatement($statementId);

            $stmt = $this->pdo->prepare(
                'SELECT bs.balance_amount, bs.due_date, bst.status_name
                 FROM `billing_statement` bs
                 INNER JOIN `billing_status` bst ON bst.status_id = bs.status_id
                 WHERE bs.statement_id = ? LIMIT 1'
            );
            $stmt->execute([$statementId]);
            $billing = $stmt->fetch();
            $isBillingStatusAllowed = $billing
                && in_array($billing['status_name'], ['Paid', 'Partially Paid'], true);
            $isOverdue = $billing
                && !empty($billing['due_date'])
                && $billing['due_date'] < date('Y-m-d')
                && (float)$billing['balance_amount'] > 0;
            if (!$isBillingStatusAllowed || $isOverdue) {
                $this->pdo->rollBack();
                $this->json(409, [
                    'success' => false,
                    'message' => $isOverdue
                        ? 'The billing balance is overdue. Resolve the overdue balance before discharge.'
                        : 'The billing statement must be Paid or Partially Paid before discharge.',
                ]);
            }

            $stmt = $this->pdo->prepare(
                'SELECT room_assignment_id, room_id
                 FROM `room_assignment`
                 WHERE admission_id = ? AND is_active = 1 AND end_datetime IS NULL
                 LIMIT 1'
            );
            $stmt->execute([$admissionId]);
            $currentAssignment = $stmt->fetch();

            if ($currentAssignment) {
                $stmt = $this->pdo->prepare(
                    'UPDATE `room_assignment`
                     SET is_active = 0, end_datetime = NOW()
                     WHERE room_assignment_id = ?'
                );
                $stmt->execute([$currentAssignment['room_assignment_id']]);

                $this->syncRoomStatus((int)$currentAssignment['room_id']);
            }

            $dischargedId = $this->dischargedStatusId();
            if ($dischargedId <= 0) $dischargedId = 2;

            $stmt = $this->pdo->prepare(
                'UPDATE `admission`
                 SET status_id = ?, discharge_datetime = NOW(),
                     discharged_by_user_id = ?, notes = CONCAT(COALESCE(notes, ""), ?)
                 WHERE admission_id = ?'
            );
            $stmt->execute([
                $dischargedId,
                $userId,
                !empty($data['discharge_notes']) ? "\n\nDischarge notes: " . $data['discharge_notes'] : '',
                $admissionId,
            ]);

            $this->pdo->commit();

            try {
                BillingHook::emit($this->pdo, $admissionId);
                BillingHook::flushDeferred();
            } catch (Throwable $e) {
                error_log('[NurseController::dischargePatient::billing] ' . $e->getMessage());
            }

            $this->json(200, ['success' => true, 'message' => 'Patient discharged successfully.']);

        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            error_log('[NurseController::dischargePatient] ' . $e->getMessage());
            $this->json(500, [
                'success' => false,
                'message' => 'Could not discharge patient: ' . $e->getMessage(),
            ]);
        }
    }

    public function getDischargeRecords(): array
    {
        $dischargedId = $this->dischargedStatusId();

        $stmt = $this->pdo->prepare(
            'SELECT a.admission_id, a.admission_datetime, a.discharge_datetime,
                    a.chief_complaint, a.notes,
                    p.patient_id, p.first_name, p.last_name,
                    r.room_number, rt.room_type_name,
                    u.first_name AS discharged_by_first, u.last_name AS discharged_by_last
             FROM `admission` a
             INNER JOIN `patient` p ON p.patient_id = a.patient_id
             LEFT JOIN `room_assignment` ra ON ra.admission_id = a.admission_id
             LEFT JOIN `room` r ON r.room_id = ra.room_id
             LEFT JOIN `room_type` rt ON rt.room_type_id = r.room_type_id
             LEFT JOIN `user` u ON u.user_id = a.discharged_by_user_id
             WHERE a.status_id = ?
             ORDER BY a.discharge_datetime DESC
             LIMIT 100'
        );
        $stmt->execute([$dischargedId]);
        return $stmt->fetchAll();
    }

    public function getGenders(): array
    {
        return $this->pdo->query('SELECT gender_id, gender_name FROM `gender` ORDER BY gender_id')->fetchAll();
    }

    public function getAdmissionStatuses(): array
    {
        return $this->pdo->query('SELECT status_id, status_name, color_code FROM `admission_status` ORDER BY status_id')->fetchAll();
    }

    public function getRoomTypes(): array
    {
        return $this->pdo->query('SELECT room_type_id, room_type_name, rate_per_day FROM `room_type` ORDER BY room_type_name')->fetchAll();
    }

    public function getBuildings(): array
    {
        return $this->pdo->query('SELECT building_id, building_name FROM `building` ORDER BY building_name')->fetchAll();
    }

    public function getFloorLevels(?int $buildingId = null): array
    {
        $sql = 'SELECT fl.floor_level_id, fl.building_id, fl.floor_level_name, b.building_name
                FROM `floor_level` fl
                INNER JOIN `building` b ON b.building_id = fl.building_id';
        $params = [];

        if ($buildingId) {
            $sql .= ' WHERE fl.building_id = ?';
            $params[] = $buildingId;
        }

        $sql .= ' ORDER BY b.building_name, fl.floor_level_name';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    private function input(): array
    {
        $raw = file_get_contents('php://input');
        return json_decode($raw, true) ?: $_POST;
    }

    private function validatePatient(array $data): array
    {
        $errors = [];

        if (empty($data['gender_id']) || (int)$data['gender_id'] <= 0) $errors['gender_id'] = 'Please select a gender.';
        if (trim((string)($data['first_name'] ?? '')) === '') $errors['first_name'] = 'First name is required.';
        if (trim((string)($data['last_name'] ?? '')) === '') $errors['last_name'] = 'Last name is required.';

        $email = trim((string)($data['email'] ?? ''));
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email is not valid.';
        }

        return $errors;
    }

    private function validateAdmission(array $data): array
    {
        $errors = [];

        if (empty($data['patient_id']) || (int)$data['patient_id'] <= 0) {
            $errors['patient_id'] = 'Please select a patient.';
        }

        if (empty($data['admission_type'])) {
            $errors['admission_type'] = 'Please select an admission type.';
        }

        if (empty($data['preferred_room_type_id']) || (int)$data['preferred_room_type_id'] <= 0) {
            $errors['preferred_room_type_id'] = 'Please select the patient\'s preferred room type.';
        }

        return $errors;
    }

    private function json(int $status, array $payload): void
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        if (array_key_exists('success', $payload)) {
            $payload['data'] = $payload['success'] ? 1 : 0;
        }
        http_response_code($status);
        echo json_encode($payload);
        exit;
    }
}