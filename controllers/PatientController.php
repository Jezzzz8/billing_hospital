<?php

require_once __DIR__ . '/BillingHook.php';

class PatientController
{
    private PDO $pdo;

    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function getAll(): array
    {
        $rows = $this->pdo->query(
            'SELECT p.patient_id, p.gender_id, p.first_name, p.last_name, p.birth_date,
                    p.contact_number, p.address, p.email,
                    p.emergency_contact, p.emergency_contact_number,
                    p.medical_history, p.is_active,
                    g.gender_name,
                    a.admission_id, a.status_id AS admission_status_id,
                    a.admission_datetime, a.discharge_datetime,
                    a.chief_complaint, a.admission_type,
                    ast.status_name AS admission_status_name,
                    ast.color_code  AS admission_status_color,
                    r.room_id, r.room_number,
                    rt.room_type_name,
                    ra.is_active AS room_assignment_is_active,
                    ra.end_datetime AS room_end_datetime
            FROM `patient` p
            INNER JOIN `gender` g ON g.gender_id = p.gender_id
            LEFT JOIN `admission` a
                    ON a.admission_id = (
                        SELECT a2.admission_id
                        FROM `admission` a2
                        WHERE a2.patient_id = p.patient_id
                        ORDER BY a2.admission_datetime DESC, a2.admission_id DESC
                        LIMIT 1
                    )
            LEFT JOIN `admission_status` ast ON ast.status_id = a.status_id
            LEFT JOIN `room_assignment` ra
                    ON ra.room_assignment_id = (
                        SELECT ra2.room_assignment_id
                        FROM `room_assignment` ra2
                        WHERE ra2.admission_id = a.admission_id
                        ORDER BY ra2.is_active DESC, ra2.start_datetime DESC
                        LIMIT 1
                    )
            LEFT JOIN `room` r ON r.room_id = ra.room_id
            LEFT JOIN `room_type` rt ON rt.room_type_id = r.room_type_id
            ORDER BY p.patient_id'
        )->fetchAll();

        $byId = [];
        foreach ($rows as $row) {
            $byId[(int)$row['patient_id']] = $row;
        }
        return array_values($byId);
    }

    public function getDetails(int $patientId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT p.*,
                    a.admission_id, a.status_id AS admission_status_id,
                    a.admission_datetime, a.discharge_datetime,
                    a.chief_complaint, a.admission_type, a.notes AS admission_notes,
                    a.total_room_transfers
            FROM `patient` p
            LEFT JOIN `admission` a
                    ON a.admission_id = (
                        SELECT a2.admission_id
                        FROM `admission` a2
                        WHERE a2.patient_id = p.patient_id
                        ORDER BY a2.admission_datetime DESC, a2.admission_id DESC
                        LIMIT 1
                    )
            WHERE p.patient_id = ?
            LIMIT 1'
        );
        $stmt->execute([$patientId]);
        $patient = $stmt->fetch();
        if (!$patient) return null;

        $admissionId = (int)($patient['admission_id'] ?? 0);

        $patient['room_assignment'] = null;
        $patient['doctors']         = [];
        $patient['diagnoses']       = [];

        if ($admissionId > 0) {
            $stmt = $this->pdo->prepare(
                'SELECT ra.room_assignment_id, ra.room_id, ra.start_datetime,
                        ra.end_datetime, ra.is_active,
                        r.room_number, rt.room_type_name
                FROM `room_assignment` ra
                INNER JOIN `room` r ON r.room_id = ra.room_id
                INNER JOIN `room_type` rt ON rt.room_type_id = r.room_type_id
                WHERE ra.admission_id = ?
                ORDER BY ra.is_active DESC, ra.start_datetime DESC
                LIMIT 1'
            );
            $stmt->execute([$admissionId]);
            $patient['room_assignment'] = $stmt->fetch() ?: null;

            $stmt = $this->pdo->prepare(
                'SELECT ad.admission_doctor_id, ad.doctor_id, ad.doctor_role,
                        ad.consultation_fee_charged,
                        d.consultation_fee AS doctor_default_fee,
                        u.first_name, u.last_name
                FROM `admission_doctor` ad
                INNER JOIN `doctor` d ON d.doctor_id = ad.doctor_id
                INNER JOIN `user` u ON u.user_id = d.user_id
                WHERE ad.admission_id = ?
                AND ad.ended_datetime IS NULL
                ORDER BY ad.assigned_datetime ASC'
            );
            $stmt->execute([$admissionId]);
            $patient['doctors'] = $stmt->fetchAll();

            $stmt = $this->pdo->prepare(
                'SELECT adi.admission_diagnosis_id, adi.diagnosis_id, adi.diagnosis_type,
                        adi.diagnosed_by_doctor_id,
                        d.diagnosis_name, d.icd_code
                FROM `admission_diagnosis` adi
                INNER JOIN `diagnosis` d ON d.diagnosis_id = adi.diagnosis_id
                WHERE adi.admission_id = ?
                ORDER BY adi.diagnosed_datetime ASC'
            );
            $stmt->execute([$admissionId]);
            $patient['diagnoses'] = $stmt->fetchAll();
        }

        return $patient;
    }

    public function getGenders(): array
    {
        return $this->pdo->query('SELECT gender_id, gender_name FROM `gender` ORDER BY gender_id')->fetchAll();
    }

    public function getAdmissionStatuses(): array
    {
        return $this->pdo->query('SELECT status_id, status_name, color_code FROM `admission_status` ORDER BY status_id')->fetchAll();
    }

    public function getAvailableRooms(): array
    {
        return $this->pdo->query(
            'SELECT r.room_id, r.room_number,
                    rt.room_type_name, rt.rate_per_day,
                    rs.status_name, rs.color_code,
                    fl.floor_level_name,
                    b.building_name,
                    (
                        SELECT ra.admission_id
                        FROM `room_assignment` ra
                        WHERE ra.room_id = r.room_id
                          AND ra.is_active = 1
                          AND ra.end_datetime IS NULL
                        ORDER BY ra.start_datetime DESC
                        LIMIT 1
                    ) AS occupied_by_admission_id
             FROM `room` r
             INNER JOIN `room_type` rt ON rt.room_type_id = r.room_type_id
             INNER JOIN `room_status` rs ON rs.status_id = r.status_id
             INNER JOIN `floor_level` fl ON fl.floor_level_id = r.floor_level_id
             INNER JOIN `building` b ON b.building_id = fl.building_id
             WHERE r.is_active = 1
               AND rs.status_name IN ("Available", "Occupied")
             ORDER BY
               CASE WHEN rs.status_name = "Available" THEN 0 ELSE 1 END,
               b.building_name, fl.floor_level_name, r.room_number'
        )->fetchAll();
    }

    public function getDoctors(): array
    {
        return $this->pdo->query(
            'SELECT d.doctor_id, d.consultation_fee, u.first_name, u.last_name
             FROM `doctor` d
             INNER JOIN `user` u ON u.user_id = d.user_id
             WHERE d.is_active = 1 AND u.is_active = 1
             ORDER BY u.last_name'
        )->fetchAll();
    }

    public function getDiagnoses(): array
    {
        return $this->pdo->query(
            'SELECT diagnosis_id, icd_code, diagnosis_name
             FROM `diagnosis`
             WHERE is_active = 1
             ORDER BY diagnosis_name'
        )->fetchAll();
    }

    public function create(): void
    {
        $data = $this->input();
        $errors = $this->validate($data, null, true);
        if ($errors) $this->json(422, ['success' => false, 'message' => 'Please fix the highlighted fields.', 'errors' => $errors]);

        try {
            $this->pdo->beginTransaction();

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

            $admissionId = null;
            if (!empty($data['create_admission'])) {
                $admissionId = $this->createAdmission($patientId, $data);
                $this->saveRoomAssignment($admissionId, $data);
                $this->saveAdmissionDoctors($admissionId, $data['doctors'] ?? []);
                $this->saveAdmissionDiagnoses($admissionId, $data['diagnoses'] ?? []);
            }

            $this->pdo->commit();

            try { BillingHook::flushDeferred(); } catch (Throwable $e) {
                error_log('[PatientController::create::flushDeferred] ' . $e->getMessage());
            }

            $this->json(201, [
                'success'      => true,
                'message'      => 'Patient created successfully.',
                'patient_id'   => $patientId,
                'admission_id' => $admissionId,
            ]);
        } catch (RuntimeException $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            $this->json(409, [
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            error_log('[PatientController::create] ' . $e->getMessage());
            $this->json(500, [
                'success' => false,
                'message' => 'Could not create patient.',
                'debug'   => $e->getMessage(),
            ]);
        }
    }

    public function update(): void
    {
        $patientId = (int)($_GET['id'] ?? 0);
        if ($patientId <= 0) $this->json(400, ['success' => false, 'message' => 'Missing patient id.']);

        $stmt = $this->pdo->prepare('SELECT patient_id FROM `patient` WHERE patient_id = ? LIMIT 1');
        $stmt->execute([$patientId]);
        if (!$stmt->fetch()) $this->json(404, ['success' => false, 'message' => 'Patient not found.']);

        $data = $this->input();
        $errors = $this->validate($data, $patientId, false);
        if ($errors) $this->json(422, ['success' => false, 'message' => 'Please fix the highlighted fields.', 'errors' => $errors]);

        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare(
                'UPDATE `patient`
                 SET gender_id = ?, first_name = ?, last_name = ?, birth_date = ?,
                     contact_number = ?, address = ?, email = ?,
                     emergency_contact = ?, emergency_contact_number = ?, medical_history = ?
                 WHERE patient_id = ?'
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
                $patientId,
            ]);

            $admissionId = (int)($data['admission_id'] ?? 0);

            if ($admissionId > 0) {
                $statusId = (int)($data['admission_status_id'] ?? 0);
                $isDischarged = $this->isDischargedStatus($statusId);
                $stmt = $this->pdo->prepare(
                    'UPDATE `admission`
                     SET status_id = ?,
                         discharge_datetime = CASE WHEN ? = 1 THEN COALESCE(discharge_datetime, NOW()) ELSE NULL END,
                         chief_complaint = ?, admission_type = ?, notes = ?
                     WHERE admission_id = ?'
                );
                $stmt->execute([
                    $statusId,
                    $isDischarged ? 1 : 0,
                    !empty($data['chief_complaint']) ? $data['chief_complaint'] : null,
                    !empty($data['admission_type']) ? $data['admission_type'] : null,
                    !empty($data['admission_notes']) ? $data['admission_notes'] : null,
                    $admissionId,
                ]);

                $roomId = (int)($data['room_id'] ?? 0);

                $stmt = $this->pdo->prepare(
                    'SELECT room_assignment_id, room_id
                     FROM `room_assignment`
                     WHERE admission_id = ? AND is_active = 1
                     ORDER BY start_datetime DESC LIMIT 1'
                );
                $stmt->execute([$admissionId]);
                $current = $stmt->fetch();

                if ($isDischarged) {
                    $stmt = $this->pdo->prepare(
                        'SELECT DISTINCT room_id
                         FROM `room_assignment`
                         WHERE admission_id = ? AND is_active = 1'
                    );
                    $stmt->execute([$admissionId]);
                    $activeRoomIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
                    $stmt = $this->pdo->prepare(
                        'UPDATE `room_assignment`
                         SET is_active = 0, end_datetime = COALESCE(end_datetime, NOW())
                        WHERE admission_id = ? AND is_active = 1'
                    );
                    $stmt->execute([$admissionId]);
                    foreach ($activeRoomIds as $activeRoomId) {
                        $this->syncRoomStatus($activeRoomId);
                    }
                } elseif ($roomId > 0) {
                    if ($current && (int)$current['room_id'] === $roomId) {
                        $this->saveRoomAssignment($admissionId, $data);
                    } else {
                        if ($current) {
                            $stmt = $this->pdo->prepare(
                                'UPDATE `room_assignment`
                                 SET is_active = 0, end_datetime = NOW()
                                 WHERE room_assignment_id = ?'
                            );
                            $stmt->execute([$current['room_assignment_id']]);

                            $this->syncRoomStatus((int)$current['room_id']);
                        }
                        $this->saveRoomAssignment($admissionId, $data);
                    }
                } else {
                    if ($current) {
                        $stmt = $this->pdo->prepare(
                            'UPDATE `room_assignment`
                             SET is_active = 0, end_datetime = NOW()
                             WHERE room_assignment_id = ?'
                        );
                        $stmt->execute([$current['room_assignment_id']]);

                        $this->syncRoomStatus((int)$current['room_id']);
                    }
                }

                $stmt = $this->pdo->prepare('DELETE FROM `admission_doctor` WHERE admission_id = ?');
                $stmt->execute([$admissionId]);
                $this->saveAdmissionDoctors($admissionId, $data['doctors'] ?? []);

                $stmt = $this->pdo->prepare('DELETE FROM `admission_diagnosis` WHERE admission_id = ?');
                $stmt->execute([$admissionId]);
                $this->saveAdmissionDiagnoses($admissionId, $data['diagnoses'] ?? []);
            } elseif (!empty($data['create_admission'])) {
                $admissionId = $this->createAdmission($patientId, $data);
                if (!$this->isDischargedStatus((int)($data['admission_status_id'] ?? 0))) {
                    $this->saveRoomAssignment($admissionId, $data);
                }
                $this->saveAdmissionDoctors($admissionId, $data['doctors'] ?? []);
                $this->saveAdmissionDiagnoses($admissionId, $data['diagnoses'] ?? []);
            }

            $this->pdo->commit();

            try { BillingHook::flushDeferred(); } catch (Throwable $e) {
                error_log('[PatientController::update::flushDeferred] ' . $e->getMessage());
            }

            $this->json(200, ['success' => true, 'message' => 'Patient updated successfully.']);
        } catch (RuntimeException $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            $this->json(409, [
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            error_log('[PatientController::update] ' . $e->getMessage());
            $this->json(500, [
                'success' => false,
                'message' => 'Could not update patient.',
                'debug'   => $e->getMessage(),
            ]);
        }
    }

    public function toggleActive(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) $this->json(400, ['success' => false, 'message' => 'Missing patient id.']);

        $stmt = $this->pdo->prepare('SELECT is_active FROM `patient` WHERE patient_id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) $this->json(404, ['success' => false, 'message' => 'Patient not found.']);

        $newState = ((int)$row['is_active'] === 1) ? 0 : 1;

        $stmt = $this->pdo->prepare('UPDATE `patient` SET is_active = ? WHERE patient_id = ?');
        $stmt->execute([$newState, $id]);

        $this->json(200, [
            'success'   => true,
            'message'   => $newState === 1 ? 'Patient reactivated.' : 'Patient archived.',
            'is_active' => $newState,
        ]);
    }

    private function createAdmission(int $patientId, array $data): int
    {
        $statusId = (int)($data['admission_status_id'] ?? 0);
        if ($statusId <= 0) {
            $stmt = $this->pdo->prepare('SELECT status_id FROM `admission_status` WHERE status_name = "Admitted" LIMIT 1');
            $stmt->execute();
            $statusId = (int)$stmt->fetchColumn();
            if ($statusId <= 0) $statusId = 1;
        }

        $isDischarged = $this->isDischargedStatus($statusId);
        $dischargedId = 0;
        $stmt = $this->pdo->prepare('SELECT status_id FROM `admission_status` WHERE status_name = "Discharged" LIMIT 1');
        $stmt->execute();
        $dischargedId = (int)$stmt->fetchColumn();

        $stmt = $this->pdo->prepare(
            'INSERT INTO `admission`
                (patient_id, status_id, admission_datetime, discharge_datetime,
                 chief_complaint, admission_type, total_room_transfers,
                 admitted_by_user_id, discharged_by_user_id, notes)
             VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?, ?)'
        );
        $stmt->execute([
            $patientId,
            $statusId,
            !empty($data['admission_datetime']) ? $data['admission_datetime'] : date('Y-m-d H:i:s'),
            !empty($data['discharge_datetime'])
                ? $data['discharge_datetime']
                : ($isDischarged ? date('Y-m-d H:i:s') : null),
            !empty($data['chief_complaint']) ? $data['chief_complaint'] : null,
            !empty($data['admission_type']) ? $data['admission_type'] : null,
            (int)$_SESSION['user']['user_id'],
            ($dischargedId > 0 && $statusId === $dischargedId) ? (int)$_SESSION['user']['user_id'] : null,
            !empty($data['admission_notes']) ? $data['admission_notes'] : null,
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
                     total_amount, amount_paid, balance_amount, created_by_user_id)
                 VALUES (?, ?, NOW(), 0, 0, 0, 0, 0, 0, 0, 0, 0, ?)'
            );
            $createStatement->execute([
                $admissionId,
                $pendingBillingStatusId,
                (int)$_SESSION['user']['user_id'],
            ]);
        }

        return $admissionId;
    }

    private function saveRoomAssignment(int $admissionId, array $data): void
    {
        $roomId = (int)($data['room_id'] ?? 0);
        if ($roomId <= 0) return;

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
            'SELECT r.room_id, rt.rate_per_day
             FROM `room` r
             INNER JOIN `room_type` rt ON rt.room_type_id = r.room_type_id
             WHERE r.room_id = ? AND r.is_active = 1
             LIMIT 1'
        );
        $stmt->execute([$roomId]);
        $room = $stmt->fetch();
        if (!$room) {
            throw new RuntimeException('Selected room is not available.');
        }

        $stmt = $this->pdo->prepare(
            'SELECT ra.room_assignment_id, ra.admission_id
             FROM `room_assignment` ra
             WHERE ra.room_id = ?
               AND ra.is_active = 1
               AND ra.end_datetime IS NULL
               AND ra.admission_id <> ?
             LIMIT 1'
        );
        $stmt->execute([$roomId, $admissionId]);
        if ($stmt->fetch()) {
            throw new RuntimeException('This room is already occupied by another patient. Please choose a different room.');
        }

        $rate = (float)$room['rate_per_day'];

        $stmt = $this->pdo->prepare(
            'INSERT INTO `room_assignment`
                (admission_id, room_id, start_datetime, end_datetime,
                 daily_rate_at_assignment, transfer_reason, transferred_by_user_id, is_active)
             VALUES (?, ?, NOW(), NULL, ?, NULL, ?, 1)'
        );
        $stmt->execute([
            $admissionId,
            $roomId,
            $rate,
            (int)$_SESSION['user']['user_id'],
        ]);

        $this->syncRoomStatus($roomId);

        BillingHook::emit($this->pdo, $admissionId);
    }

    private function isDischargedStatus(int $statusId): bool
    {
        if ($statusId <= 0) {
            throw new RuntimeException('Please select a valid admission status.');
        }

        $stmt = $this->pdo->prepare(
            'SELECT status_name FROM `admission_status` WHERE status_id = ? LIMIT 1'
        );
        $stmt->execute([$statusId]);
        $statusName = $stmt->fetchColumn();
        if ($statusName === false) {
            throw new RuntimeException('Please select a valid admission status.');
        }

        return strcasecmp((string)$statusName, 'Discharged') === 0;
    }

    private function syncRoomStatus(int $roomId): void
    {
        $stmt = $this->pdo->prepare(
            'SELECT rs.status_name, rt.capacity,
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

    private function saveAdmissionDoctors(int $admissionId, array $doctors): void
    {
        if (!is_array($doctors) || !$doctors) return;

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

        foreach ($doctors as $d) {
            $doctorId = (int)($d['doctor_id'] ?? 0);
            if ($doctorId <= 0) continue;

            $exists->execute([$admissionId, $doctorId]);
            if ($exists->fetch()) continue;

            $role = !empty($d['doctor_role']) ? $d['doctor_role'] : 'Attending';

            $feeLookup->execute([$doctorId]);
            $fee = (float)($feeLookup->fetchColumn() ?: 0);

            $insert->execute([$admissionId, $doctorId, $role, $fee]);
        }

        BillingHook::emit($this->pdo, $admissionId);
    }

    private function saveAdmissionDiagnoses(int $admissionId, array $diagnoses): void
    {
        if (!is_array($diagnoses) || !$diagnoses) return;

        $insert = $this->pdo->prepare(
            'INSERT INTO `admission_diagnosis`
                (admission_id, diagnosis_id, diagnosis_type, diagnosed_datetime, diagnosed_by_doctor_id)
             VALUES (?, ?, ?, NOW(), ?)'
        );

        foreach ($diagnoses as $d) {
            $diagId = (int)($d['diagnosis_id'] ?? 0);
            if ($diagId <= 0) continue;

            $type = !empty($d['diagnosis_type']) ? $d['diagnosis_type'] : 'Primary';
            $doctorId = !empty($d['diagnosed_by_doctor_id']) ? (int)$d['diagnosed_by_doctor_id'] : null;

            $insert->execute([$admissionId, $diagId, $type, $doctorId]);
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

    private function validate(array $data, ?int $ignoreId, bool $requireAdmission): array
    {
        $errors = [];

        if (empty($data['gender_id']) || (int)$data['gender_id'] <= 0) $errors['gender_id'] = 'Please select a gender.';
        if (trim((string)($data['first_name'] ?? '')) === '') $errors['first_name'] = 'First name is required.';
        if (trim((string)($data['last_name']  ?? '')) === '') $errors['last_name']  = 'Last name is required.';

        $email = trim((string)($data['email'] ?? ''));
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email is not valid.';
        }

        if (!empty($data['create_admission'])) {
            if (empty($data['admission_status_id'])) $errors['admission_status_id'] = 'Please select an admission status.';
            if (empty($data['admission_type']))      $errors['admission_type']      = 'Please select an admission type.';
        }

        return $errors;
    }

    private function json(int $status, array $payload): void
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        if (isset($payload['success']) && !array_key_exists('data', $payload)) {
            $payload['data'] = $payload['success'] ? 1 : 0;
        }
        http_response_code($status);
        echo json_encode($payload);
        exit;
    }
}