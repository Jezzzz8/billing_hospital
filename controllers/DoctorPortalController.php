<?php

require_once __DIR__ . '/BillingHook.php';

class DoctorPortalController
{
    private PDO $pdo;
    private int $doctorId;

    public function __construct(PDO $pdo, int $doctorId)
    {
        $this->pdo = $pdo;
        $this->doctorId = $doctorId;
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

    private function tableExists(string $table): bool
    {
        $stmt = $this->pdo->prepare('SHOW TABLES LIKE ?');
        $stmt->execute([$table]);
        return (bool)$stmt->fetchColumn();
    }

    private function hasServiceRequestItemTable(): bool
    {
        return $this->tableExists('service_request_item');
    }

    public function getDashboardStats(): array
    {
        $activeIds = $this->activeStatusIds();
        $placeholders = implode(',', array_fill(0, count($activeIds), '?'));

        $stmt = $this->pdo->prepare(
            "SELECT COUNT(DISTINCT ad.admission_id)
             FROM `admission_doctor` ad
             INNER JOIN `admission` a ON a.admission_id = ad.admission_id
             WHERE ad.doctor_id = ? AND ad.ended_datetime IS NULL
               AND a.status_id IN ($placeholders)"
        );
        $stmt->execute(array_merge([$this->doctorId], $activeIds));
        $assignedAdmissions = (int)$stmt->fetchColumn();

        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM `consultation`
             WHERE doctor_id = ? AND DATE(consultation_datetime) = CURDATE()'
        );
        $stmt->execute([$this->doctorId]);
        $todayConsultations = (int)$stmt->fetchColumn();

        $pendingRequests = 0;
        if ($this->hasServiceRequestItemTable()) {
            $stmt = $this->pdo->prepare(
                'SELECT COUNT(*) FROM `service_request`
                 WHERE doctor_id = ? AND status IN ("Pending", "In Progress")'
            );
            $stmt->execute([$this->doctorId]);
            $pendingRequests = (int)$stmt->fetchColumn();
        } else {
            $stmt = $this->pdo->prepare(
                'SELECT COUNT(*) FROM `service_request`
                 WHERE doctor_id = ? AND status = "Pending"'
            );
            $stmt->execute([$this->doctorId]);
            $pendingRequests = (int)$stmt->fetchColumn();
        }

        $readyId = $this->readyStatusId();
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(DISTINCT ad.admission_id)
             FROM `admission_doctor` ad
             INNER JOIN `admission` a ON a.admission_id = ad.admission_id
             WHERE ad.doctor_id = ? AND ad.ended_datetime IS NULL
               AND a.status_id = ?'
        );
        $stmt->execute([$this->doctorId, $readyId]);
        $readyForDischarge = (int)$stmt->fetchColumn();

        return [
            'assigned_admissions'  => $assignedAdmissions,
            'today_consultations'  => $todayConsultations,
            'pending_requests'     => $pendingRequests,
            'ready_for_discharge'  => $readyForDischarge,
        ];
    }

    public function getAssignedPatients(): array
    {
        $activeIds = $this->activeStatusIds();
        $placeholders = implode(',', array_fill(0, count($activeIds), '?'));

        $stmt = $this->pdo->prepare(
            "SELECT a.admission_id, a.admission_datetime, a.chief_complaint, a.admission_type,
                    p.patient_id, p.first_name, p.last_name, p.birth_date,
                    g.gender_name,
                    ast.status_name AS admission_status_name, ast.color_code AS admission_status_color,
                    r.room_number, rt.room_type_name,
                    ad.doctor_role
             FROM `admission_doctor` ad
             INNER JOIN `admission` a ON a.admission_id = ad.admission_id
             INNER JOIN `patient` p ON p.patient_id = a.patient_id
             INNER JOIN `gender` g ON g.gender_id = p.gender_id
             INNER JOIN `admission_status` ast ON ast.status_id = a.status_id
             LEFT JOIN `room_assignment` ra ON ra.admission_id = a.admission_id AND ra.is_active = 1
             LEFT JOIN `room` r ON r.room_id = ra.room_id
             LEFT JOIN `room_type` rt ON rt.room_type_id = r.room_type_id
             WHERE ad.doctor_id = ? AND ad.ended_datetime IS NULL
               AND a.status_id IN ($placeholders)
             ORDER BY a.admission_datetime DESC"
        );
        $stmt->execute(array_merge([$this->doctorId], $activeIds));
        return $stmt->fetchAll();
    }

    public function getAdmissionDetails(int $admissionId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT ad.admission_doctor_id, ad.doctor_role, ad.assigned_datetime,
                    ad.consultation_fee_charged
             FROM `admission_doctor` ad
             WHERE ad.admission_id = ? AND ad.doctor_id = ? AND ad.ended_datetime IS NULL
             LIMIT 1'
        );
        $stmt->execute([$admissionId, $this->doctorId]);
        $assignment = $stmt->fetch();
        if (!$assignment) return null;

        $stmt = $this->pdo->prepare(
            'SELECT a.admission_id, a.admission_datetime, a.discharge_datetime,
                    a.chief_complaint, a.admission_type, a.notes, a.total_room_transfers,
                    p.patient_id, p.first_name, p.last_name, p.birth_date,
                    p.contact_number, p.email, p.address, p.medical_history,
                    g.gender_name,
                    ast.status_name AS admission_status_name, ast.color_code AS admission_status_color
             FROM `admission` a
             INNER JOIN `patient` p ON p.patient_id = a.patient_id
             INNER JOIN `gender` g ON g.gender_id = p.gender_id
             INNER JOIN `admission_status` ast ON ast.status_id = a.status_id
             WHERE a.admission_id = ? LIMIT 1'
        );
        $stmt->execute([$admissionId]);
        $admission = $stmt->fetch();
        if (!$admission) return null;

        $admission['assignment'] = $assignment;

        $stmt = $this->pdo->prepare(
            'SELECT ra.room_assignment_id, ra.start_datetime, ra.daily_rate_at_assignment,
                    r.room_number, rt.room_type_name
             FROM `room_assignment` ra
             INNER JOIN `room` r ON r.room_id = ra.room_id
             INNER JOIN `room_type` rt ON rt.room_type_id = r.room_type_id
             WHERE ra.admission_id = ? AND ra.is_active = 1
             LIMIT 1'
        );
        $stmt->execute([$admissionId]);
        $admission['room'] = $stmt->fetch() ?: null;

        $stmt = $this->pdo->prepare(
            'SELECT adi.admission_diagnosis_id, adi.diagnosis_id, adi.diagnosis_type,
                    adi.diagnosed_datetime, adi.diagnosed_by_doctor_id,
                    d.diagnosis_name, d.icd_code, d.description,
                    u.first_name AS doctor_first, u.last_name AS doctor_last
             FROM `admission_diagnosis` adi
             INNER JOIN `diagnosis` d ON d.diagnosis_id = adi.diagnosis_id
             LEFT JOIN `doctor` doc ON doc.doctor_id = adi.diagnosed_by_doctor_id
             LEFT JOIN `user` u ON u.user_id = doc.user_id
             WHERE adi.admission_id = ?
             ORDER BY adi.diagnosed_datetime DESC'
        );
        $stmt->execute([$admissionId]);
        $admission['diagnoses'] = $stmt->fetchAll();

        if ($this->hasServiceRequestItemTable()) {
            $stmt = $this->pdo->prepare(
                'SELECT sr.request_id, sr.department_id, sr.doctor_id, sr.priority, sr.status, sr.request_datetime,
                        sr.completed_datetime, sr.cancelled_datetime, sr.instructions, sr.notes,
                        d.department_name,
                        COUNT(sri.request_item_id) AS item_count,
                        COALESCE(SUM(sri.quantity_requested), 0) AS quantity,
                        COALESCE(GROUP_CONCAT(DISTINCT ci.item_name ORDER BY ci.item_name SEPARATOR ", "), "Service request") AS item_name,
                        COALESCE(GROUP_CONCAT(DISTINCT cc.category_name ORDER BY cc.category_name SEPARATOR ", "), "") AS category_name,
                        COALESCE(GROUP_CONCAT(DISTINCT ci.item_code ORDER BY ci.item_code SEPARATOR ", "), "") AS item_code,
                        u.first_name AS doctor_first, u.last_name AS doctor_last
                FROM `service_request` sr
                LEFT JOIN `service_request_item` sri ON sri.request_id = sr.request_id
                LEFT JOIN `charge_item` ci ON ci.charge_item_id = sri.charge_item_id
                LEFT JOIN `charge_category` cc ON cc.category_id = ci.category_id
                LEFT JOIN `department` d ON d.department_id = sr.department_id
                LEFT JOIN `doctor` doc ON doc.doctor_id = sr.doctor_id
                LEFT JOIN `user` u ON u.user_id = doc.user_id
                WHERE sr.admission_id = ?
                GROUP BY sr.request_id
                ORDER BY sr.request_datetime DESC'
            );
            $stmt->execute([$admissionId]);
            $admission['service_requests'] = $stmt->fetchAll();
        } else {
            $stmt = $this->pdo->prepare(
                'SELECT sr.request_id, sr.charge_item_id, sr.request_datetime, sr.quantity,
                        sr.status, sr.doctor_id,
                        ci.item_code, ci.item_name, ci.default_price, ci.unit_of_measure,
                        cc.category_name,
                        u.first_name AS doctor_first, u.last_name AS doctor_last
                 FROM `service_request` sr
                 INNER JOIN `charge_item` ci ON ci.charge_item_id = sr.charge_item_id
                 INNER JOIN `charge_category` cc ON cc.category_id = ci.category_id
                 LEFT JOIN `doctor` doc ON doc.doctor_id = sr.doctor_id
                 LEFT JOIN `user` u ON u.user_id = doc.user_id
                 WHERE sr.admission_id = ?
                 ORDER BY sr.request_datetime DESC'
            );
            $stmt->execute([$admissionId]);
            $admission['service_requests'] = $stmt->fetchAll();
        }

        $stmt = $this->pdo->prepare(
            'SELECT c.consultation_id, c.consultation_datetime, c.purpose, c.status, c.notes
             FROM `consultation` c
             WHERE c.patient_id = ?
             ORDER BY c.consultation_datetime DESC
             LIMIT 10'
        );
        $stmt->execute([$admission['patient_id']]);
        $admission['consultations'] = $stmt->fetchAll();

        return $admission;
    }

    public function getActiveDiagnoses(): array
    {
        return $this->pdo->query(
            'SELECT diagnosis_id, icd_code, diagnosis_name, description
             FROM `diagnosis`
             WHERE is_active = 1
             ORDER BY diagnosis_name'
        )->fetchAll();
    }

    public function saveDiagnosis(): void
    {
        $data = $this->input();
        $admissionId = (int)($data['admission_id'] ?? 0);
        $diagnosisId = (int)($data['diagnosis_id'] ?? 0);
        $diagnosisType = $data['diagnosis_type'] ?? 'Primary';

        if ($admissionId <= 0 || $diagnosisId <= 0) {
            $this->json(422, ['success' => false, 'message' => 'Admission and diagnosis are required.']);
        }

        $stmt = $this->pdo->prepare(
            'SELECT admission_doctor_id FROM `admission_doctor`
             WHERE admission_id = ? AND doctor_id = ? AND ended_datetime IS NULL LIMIT 1'
        );
        $stmt->execute([$admissionId, $this->doctorId]);
        if (!$stmt->fetch()) {
            $this->json(403, ['success' => false, 'message' => 'You are not assigned to this admission.']);
        }

        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO `admission_diagnosis`
                    (admission_id, diagnosis_id, diagnosis_type, diagnosed_datetime, diagnosed_by_doctor_id)
                 VALUES (?, ?, ?, NOW(), ?)'
            );
            $stmt->execute([$admissionId, $diagnosisId, $diagnosisType, $this->doctorId]);

            $this->json(201, [
                'success' => true,
                'message' => 'Diagnosis recorded successfully.',
                'id'      => (int)$this->pdo->lastInsertId(),
            ]);
        } catch (Throwable $e) {
            error_log('[DoctorPortalController::saveDiagnosis] ' . $e->getMessage());
            $this->json(500, ['success' => false, 'message' => 'Could not save diagnosis.']);
        }
    }

    public function removeDiagnosis(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) $this->json(400, ['success' => false, 'message' => 'Missing diagnosis id.']);

        $stmt = $this->pdo->prepare(
            'SELECT adi.admission_diagnosis_id
             FROM `admission_diagnosis` adi
             INNER JOIN `admission_doctor` ad ON ad.admission_id = adi.admission_id
             WHERE adi.admission_diagnosis_id = ? AND ad.doctor_id = ? AND ad.ended_datetime IS NULL
             LIMIT 1'
        );
        $stmt->execute([$id, $this->doctorId]);
        if (!$stmt->fetch()) {
            $this->json(403, ['success' => false, 'message' => 'Access denied.']);
        }

        $stmt = $this->pdo->prepare('DELETE FROM `admission_diagnosis` WHERE admission_diagnosis_id = ?');
        $stmt->execute([$id]);

        $this->json(200, ['success' => true, 'message' => 'Diagnosis removed.']);
    }

    public function getChargeItems(): array
    {
        return $this->pdo->query(
            'SELECT ci.charge_item_id, ci.item_code, ci.item_name, ci.default_price,
                    ci.is_taxable, ci.unit_of_measure, ci.requires_doctor_order,
                    cc.category_id, cc.category_name
             FROM `charge_item` ci
             INNER JOIN `charge_category` cc ON cc.category_id = ci.category_id
             WHERE ci.is_active = 1
             ORDER BY cc.category_name, ci.item_name'
        )->fetchAll();
    }

    public function getDepartmentsWithCategories(): array
    {
        return $this->pdo->query(
            'SELECT d.department_id, d.department_name,
                    d.charge_category_id, cc.category_name AS charge_category_name
            FROM `department` d
            LEFT JOIN `charge_category` cc ON cc.category_id = d.charge_category_id
            WHERE d.is_active = 1
            AND COALESCE(d.department_code, "") NOT IN ("ADMIN", "CASHIER", "NURSING")
            ORDER BY d.department_name'
        )->fetchAll();
    }

    public function getChargeItemsForDepartment(int $departmentId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT d.department_id, d.charge_category_id
            FROM `department` d WHERE d.department_id = ? LIMIT 1'
        );
        $stmt->execute([$departmentId]);
        $dept = $stmt->fetch();
        if (!$dept || empty($dept['charge_category_id'])) return [];

        $stmt = $this->pdo->prepare(
            'SELECT ci.charge_item_id, ci.item_code, ci.item_name, ci.default_price,
                    ci.unit_of_measure, ci.is_taxable,
                    cc.category_id, cc.category_name
            FROM `charge_item` ci
            INNER JOIN `charge_category` cc ON cc.category_id = ci.category_id
            WHERE ci.is_active = 1 AND ci.category_id = ?
            ORDER BY ci.item_name'
        );
        $stmt->execute([(int)$dept['charge_category_id']]);
        return $stmt->fetchAll();
    }

    public function getDepartments(): array
    {
        return $this->pdo->query(
            'SELECT department_id, department_name
             FROM `department`
             WHERE is_active = 1
             ORDER BY department_name'
        )->fetchAll();
    }

    public function createServiceRequest(): void
    {
        $data = $this->input();
        $admissionId = (int)($data['admission_id'] ?? 0);
        $departmentId = (int)($data['department_id'] ?? 0);
        $priority = trim((string)($data['priority'] ?? 'Routine'));
        $instructions = trim((string)($data['instructions'] ?? ''));
        $notes = trim((string)($data['notes'] ?? ''));

        if ($admissionId <= 0) {
            $this->json(422, ['success' => false, 'message' => 'Admission is required.']);
        }

        $stmt = $this->pdo->prepare(
            'SELECT ad.admission_doctor_id, ast.status_name
            FROM `admission_doctor` ad
            INNER JOIN `admission` a ON a.admission_id = ad.admission_id
            INNER JOIN `admission_status` ast ON ast.status_id = a.status_id
            WHERE ad.admission_id = ? AND ad.doctor_id = ? AND ad.ended_datetime IS NULL
            LIMIT 1'
        );
        $stmt->execute([$admissionId, $this->doctorId]);
        $assignment = $stmt->fetch();
        if (!$assignment) {
            $this->json(403, ['success' => false, 'message' => 'You are not assigned to this admission.']);
        }
        if (!in_array($assignment['status_name'], ['Admitted', 'Transferred'], true)) {
            $this->json(409, ['success' => false, 'message' => 'New service requests cannot be submitted after discharge readiness or discharge.']);
        }

        $departmentStmt = $this->pdo->prepare(
            'SELECT department_id, charge_category_id FROM `department` WHERE department_id = ? AND is_active = 1 LIMIT 1'
        );
        $departmentStmt->execute([$departmentId]);
        $dept = $departmentStmt->fetch();
        if (!$dept) {
            $this->json(422, ['success' => false, 'message' => 'Please select an active department to fulfill the request.']);
        }

        $items = $data['items'] ?? null;
        if (!is_array($items) || empty($items)) {
            $this->json(422, ['success' => false, 'message' => 'At least one item is required.']);
        }

        $normalizedItems = [];
        foreach ($items as $item) {
            if (!is_array($item)) continue;
            $itemId = (int)($item['charge_item_id'] ?? 0);
            $quantity = (int)($item['quantity'] ?? 1);
            if ($itemId <= 0 || $quantity <= 0) continue;

            $stmt = $this->pdo->prepare(
                'SELECT ci.charge_item_id, ci.category_id
                FROM `charge_item` ci
                WHERE ci.charge_item_id = ? AND ci.is_active = 1 LIMIT 1'
            );
            $stmt->execute([$itemId]);
            $ci = $stmt->fetch();
            if (!$ci) {
                $this->json(404, ['success' => false, 'message' => 'One or more charge items are invalid or inactive.']);
            }

            if (!empty($dept['charge_category_id']) && (int)$ci['category_id'] !== (int)$dept['charge_category_id']) {
                $this->json(422, ['success' => false, 'message' => 'Selected item does not belong to the selected department\'s charge category.']);
            }

            $normalizedItems[] = [
                'charge_item_id' => $itemId,
                'quantity' => $quantity,
                'instructions' => trim((string)($item['instructions'] ?? '')),
                'notes' => trim((string)($item['notes'] ?? '')),
            ];
        }

        if (empty($normalizedItems)) {
            $this->json(422, ['success' => false, 'message' => 'At least one valid item is required.']);
        }

        try {
            $this->pdo->beginTransaction();

            $admissionLock = $this->pdo->prepare(
                'SELECT ast.status_name
                FROM `admission` a
                INNER JOIN `admission_status` ast ON ast.status_id = a.status_id
                WHERE a.admission_id = ? FOR UPDATE'
            );
            $admissionLock->execute([$admissionId]);
            $lockedStatus = $admissionLock->fetchColumn();
            if (!in_array($lockedStatus, ['Admitted', 'Transferred'], true)) {
                $this->pdo->rollBack();
                $this->json(409, ['success' => false, 'message' => 'New service requests cannot be submitted after discharge readiness or discharge.']);
            }

            $stmt = $this->pdo->prepare(
                'INSERT INTO `service_request`
                    (admission_id, doctor_id, department_id, priority, status, instructions, notes, request_datetime)
                VALUES (?, ?, ?, ?, "Pending", ?, ?, NOW())'
            );
            $stmt->execute([$admissionId, $this->doctorId, $departmentId, $priority ?: 'Routine', $instructions ?: null, $notes ?: null]);
            $requestId = (int)$this->pdo->lastInsertId();

            $itemStmt = $this->pdo->prepare(
                'INSERT INTO `service_request_item`
                    (request_id, charge_item_id, quantity_requested, quantity_fulfilled, instructions, notes)
                VALUES (?, ?, ?, 0, ?, ?)'
            );

            foreach ($normalizedItems as $item) {
                $itemStmt->execute([
                    $requestId,
                    $item['charge_item_id'],
                    $item['quantity'],
                    $item['instructions'] ?: null,
                    $item['notes'] ?: null,
                ]);
            }

            $this->pdo->commit();

            $this->json(201, [
                'success' => true,
                'message' => 'Service request created successfully.',
                'id'      => $requestId,
            ]);
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            error_log('[DoctorPortalController::createServiceRequest] ' . $e->getMessage());
            $this->json(500, ['success' => false, 'message' => 'Could not create service request.']);
        }
    }

    public function completeServiceRequest(): void
    {
        $this->json(409, [
            'success' => false,
            'message' => 'Service requests can only be completed by the department after recording an actual fulfillment.',
        ]);
    }

    public function cancelServiceRequest(): void
    {
        $this->json(409, [
            'success' => false,
            'message' => 'Service request status changes are handled by the assigned department.',
        ]);
    }

    public function getReadyForDischarge(): array
    {
        $activeIds = $this->activeStatusIds();
        $placeholders = implode(',', array_fill(0, count($activeIds), '?'));

        $stmt = $this->pdo->prepare(
            "SELECT a.admission_id, a.admission_datetime, a.chief_complaint,
                    p.patient_id, p.first_name, p.last_name,
                    r.room_number, rt.room_type_name,
                    ast.status_id AS status_id,
                    ast.status_name, ast.color_code,
                    ad.doctor_role,
                    (SELECT COUNT(*) FROM `service_request` sr
                     WHERE sr.admission_id = a.admission_id
                       AND sr.status IN ('Pending', 'In Progress')) AS active_count,
                    (SELECT COUNT(*) FROM `service_request` sr
                     WHERE sr.admission_id = a.admission_id
                       AND sr.status = 'Completed') AS completed_count
             FROM `admission_doctor` ad
             INNER JOIN `admission` a ON a.admission_id = ad.admission_id
             INNER JOIN `patient` p ON p.patient_id = a.patient_id
             INNER JOIN `admission_status` ast ON ast.status_id = a.status_id
             LEFT JOIN `room_assignment` ra ON ra.admission_id = a.admission_id AND ra.is_active = 1
             LEFT JOIN `room` r ON r.room_id = ra.room_id
             LEFT JOIN `room_type` rt ON rt.room_type_id = r.room_type_id
             WHERE ad.doctor_id = ? AND ad.ended_datetime IS NULL
               AND a.status_id IN ($placeholders)
             HAVING active_count = 0
             ORDER BY a.admission_datetime ASC"
        );
        $stmt->execute(array_merge([$this->doctorId], $activeIds));
        return $stmt->fetchAll();
    }

    public function confirmDischarge(): void
    {
        $data = $this->input();
        $admissionId = (int)($data['admission_id'] ?? 0);

        if ($admissionId <= 0) {
            $this->json(422, ['success' => false, 'message' => 'Admission id is required.']);
        }

        $stmt = $this->pdo->prepare(
            'SELECT admission_doctor_id FROM `admission_doctor`
             WHERE admission_id = ? AND doctor_id = ? AND ended_datetime IS NULL LIMIT 1'
        );
        $stmt->execute([$admissionId, $this->doctorId]);
        if (!$stmt->fetch()) {
            $this->json(403, ['success' => false, 'message' => 'You are not assigned to this admission.']);
        }

        $stmt = $this->pdo->prepare(
            'SELECT a.admission_id, a.status_id, ast.status_name
             FROM `admission` a
             INNER JOIN `admission_status` ast ON ast.status_id = a.status_id
             WHERE a.admission_id = ? LIMIT 1'
        );
        $stmt->execute([$admissionId]);
        $row = $stmt->fetch();
        if (!$row) {
            $this->json(404, ['success' => false, 'message' => 'Admission not found.']);
        }

        $allowedIds = [];
        foreach (['Admitted', 'Transferred'] as $n) {
            $id = $this->statusId($n);
            if ($id > 0) $allowedIds[] = $id;
        }
        if (!$allowedIds) $allowedIds = [1, 3];

        if (!in_array((int)$row['status_id'], $allowedIds, true)) {
            $this->json(409, [
                'success' => false,
                'message' => 'This admission cannot be cleared for discharge (current status: '
                    . $row['status_name'] . '). Only Admitted or Transferred patients can be cleared.',
            ]);
        }

        $readyId = $this->readyStatusId();
        if ($readyId <= 0) {
            $this->json(500, ['success' => false, 'message' => 'System configuration error: status "Ready for Discharge" is missing.']);
        }

        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare(
                'SELECT a.status_id, ast.status_name
                 FROM `admission` a
                 INNER JOIN `admission_status` ast ON ast.status_id = a.status_id
                 WHERE a.admission_id = ? FOR UPDATE'
            );
            $stmt->execute([$admissionId]);
            $lockedAdmission = $stmt->fetch();
            if (!$lockedAdmission || !in_array((int)$lockedAdmission['status_id'], $allowedIds, true)) {
                $this->pdo->rollBack();
                $this->json(409, [
                    'success' => false,
                    'message' => 'This admission is no longer eligible for discharge readiness.',
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
                    'message' => 'This patient has pending or in-progress service requests that must be resolved first.',
                ]);
            }

            $stmt = $this->pdo->prepare(
                'UPDATE `admission`
                 SET status_id = ?,
                     notes = CONCAT(COALESCE(notes, ""), ?)
                 WHERE admission_id = ?'
            );
            $note = "\n\n[" . date('Y-m-d H:i') . "] Doctor cleared patient for discharge. "
                  . (!empty($data['notes']) ? $data['notes'] : '');
            $stmt->execute([$readyId, $note, $admissionId]);

            $this->pdo->commit();

            $this->json(200, [
                'success' => true,
                'message' => 'Patient marked Ready for Discharge. Nursing staff and cashier have been notified.',
            ]);
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            error_log('[DoctorPortalController::confirmDischarge] ' . $e->getMessage());
            $this->json(500, ['success' => false, 'message' => 'Could not mark patient ready for discharge.']);
        }
    }

    public function getConsultations(): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT c.consultation_id, c.patient_id, c.consultation_datetime,
                    c.purpose, c.status, c.notes, c.created_at,
                    p.first_name, p.last_name, p.contact_number
             FROM `consultation` c
             INNER JOIN `patient` p ON p.patient_id = c.patient_id
             WHERE c.doctor_id = ?
             ORDER BY c.consultation_datetime DESC'
        );
        $stmt->execute([$this->doctorId]);
        return $stmt->fetchAll();
    }

    public function createConsultation(): void
    {
        $data = $this->input();
        $patientId = (int)($data['patient_id'] ?? 0);
        $datetime = $data['consultation_datetime'] ?? date('Y-m-d H:i:s');
        $purpose = trim($data['purpose'] ?? '');

        if ($patientId <= 0 || $purpose === '') {
            $this->json(422, ['success' => false, 'message' => 'Patient and purpose are required.']);
        }

        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO `consultation`
                    (patient_id, doctor_id, consultation_datetime, purpose, status, notes)
                 VALUES (?, ?, ?, ?, "Scheduled", ?)'
            );
            $stmt->execute([
                $patientId,
                $this->doctorId,
                $datetime,
                $purpose,
                !empty($data['notes']) ? $data['notes'] : null,
            ]);

            $this->json(201, [
                'success' => true,
                'message' => 'Consultation scheduled.',
                'id'      => (int)$this->pdo->lastInsertId(),
            ]);
        } catch (Throwable $e) {
            error_log('[DoctorPortalController::createConsultation] ' . $e->getMessage());
            $this->json(500, ['success' => false, 'message' => 'Could not create consultation.']);
        }
    }

    public function updateConsultationStatus(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $status = $_GET['status'] ?? '';

        $allowed = ['Scheduled', 'Completed', 'Cancelled'];
        if ($id <= 0 || !in_array($status, $allowed, true)) {
            $this->json(422, ['success' => false, 'message' => 'Invalid request.']);
        }

        $stmt = $this->pdo->prepare(
            'UPDATE `consultation` SET status = ?
             WHERE consultation_id = ? AND doctor_id = ?'
        );
        $stmt->execute([$status, $id, $this->doctorId]);

        $this->json(200, ['success' => true, 'message' => 'Consultation status updated.']);
    }

    public function getAllMyPatients(): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT DISTINCT p.patient_id, p.first_name, p.last_name, p.birth_date,
                    p.contact_number, p.email, g.gender_name,
                    (SELECT COUNT(*) FROM `admission` a2
                     WHERE a2.patient_id = p.patient_id) AS total_admissions,
                    (SELECT MAX(a3.admission_datetime) FROM `admission` a3
                     WHERE a3.patient_id = p.patient_id) AS last_admission
             FROM `admission_doctor` ad
             INNER JOIN `admission` a ON a.admission_id = ad.admission_id
             INNER JOIN `patient` p ON p.patient_id = a.patient_id
             INNER JOIN `gender` g ON g.gender_id = p.gender_id
             WHERE ad.doctor_id = ?
             ORDER BY p.last_name, p.first_name'
        );
        $stmt->execute([$this->doctorId]);
        return $stmt->fetchAll();
    }

    private function input(): array
    {
        $raw = file_get_contents('php://input');
        return json_decode($raw, true) ?: $_POST;
    }

    private function json(int $status, array $payload): void
    {
        if (defined('DOCTOR_API_RPC') && DOCTOR_API_RPC) {
            $payload['data'] = !empty($payload['success']) ? 1 : 0;
        }
        http_response_code($status);
        echo json_encode($payload);
        exit;
    }
}