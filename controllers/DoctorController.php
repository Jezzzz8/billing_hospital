<?php

require_once __DIR__ . '/../includes/ws_notify.php';

class DoctorController
{
    private PDO $pdo;

    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function getAll(): array
    {
        $doctors = $this->pdo->query(
            'SELECT d.doctor_id, d.user_id, d.license_number, d.consultation_fee,
                    d.is_active AS doctor_active,
                    u.username, u.first_name, u.last_name, u.email,
                    u.contact_number,
                    CASE WHEN d.is_active = 1 AND u.is_active = 1 THEN 1 ELSE 0 END AS is_active
             FROM `doctor` d
             INNER JOIN `user` u ON u.user_id = d.user_id
             ORDER BY d.doctor_id'
        )->fetchAll();

        if ($doctors) {
            $ids = array_column($doctors, 'doctor_id');
            $placeholders = implode(',', array_fill(0, count($ids), '?'));

            $stmt = $this->pdo->prepare(
                "SELECT ds.doctor_id, ds.specialization_id, ds.is_primary, s.specialization_name
                 FROM `doctor_specialization` ds
                 INNER JOIN `specialization` s ON s.specialization_id = ds.specialization_id
                 WHERE ds.doctor_id IN ($placeholders)
                 ORDER BY ds.is_primary DESC, s.specialization_name"
            );
            $stmt->execute($ids);
            $rows = $stmt->fetchAll();

            $byDoctor = [];
            foreach ($rows as $r) {
                $byDoctor[(int)$r['doctor_id']][] = $r;
            }

            foreach ($doctors as &$d) {
                $d['specializations'] = $byDoctor[(int)$d['doctor_id']] ?? [];
            }
            unset($d);
        }

        return $doctors;
    }

    public function getSpecializations(): array
    {
        return $this->pdo->query(
            'SELECT specialization_id, specialization_name
             FROM `specialization`
             ORDER BY specialization_name'
        )->fetchAll();
    }

    public function create(): void
    {
        $data   = $this->input();
        $errors = $this->validate($data, null, true);
        if ($errors) $this->json(422, ['success' => false, 'message' => 'Please fix the highlighted fields.', 'errors' => $errors]);

        if ($this->usernameExists($data['username']))       $this->json(409, ['success' => false, 'errors' => ['username' => 'Username is already in use.']]);
        if ($this->emailExists($data['email']))             $this->json(409, ['success' => false, 'errors' => ['email' => 'Email is already registered.']]);
        if ($this->licenseExists($data['license_number']))  $this->json(409, ['success' => false, 'errors' => ['license_number' => 'License number is already registered.']]);

        try {
            $this->pdo->beginTransaction();

            $hash = password_hash((string)$data['password'], PASSWORD_BCRYPT);
            $stmt = $this->pdo->prepare(
                'INSERT INTO `user`
                    (role_id, username, password_hash, first_name, last_name, email, contact_number, is_active)
                 VALUES (2, ?, ?, ?, ?, ?, ?, 1)'
            );
            $stmt->execute([
                $data['username'],
                $hash,
                $data['first_name'],
                $data['last_name'],
                $data['email'],
                !empty($data['contact_number']) ? $data['contact_number'] : null,
            ]);
            $userId = (int)$this->pdo->lastInsertId();

            $stmt = $this->pdo->prepare(
                'INSERT INTO `doctor` (user_id, license_number, consultation_fee, is_active)
                 VALUES (?, ?, ?, 1)'
            );
            $stmt->execute([
                $userId,
                $data['license_number'],
                (float)$data['consultation_fee'],
            ]);
            $doctorId = (int)$this->pdo->lastInsertId();

            $this->saveSpecializations($doctorId, $data['specializations'] ?? [], (int)($data['primary_specialization_id'] ?? 0));

            $this->pdo->commit();

            ws_broadcast('doctor.profile.created', ['doctor_id' => $doctorId, 'user_id' => $userId], [1]);

            $this->json(201, [
                'success'   => true,
                'message'   => 'Doctor created successfully.',
                'user_id'   => $userId,
                'doctor_id' => $doctorId,
            ]);
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            error_log('[DoctorController::create] ' . $e->getMessage());
            $this->json(500, ['success' => false, 'message' => 'Could not create doctor.']);
        }
    }

    public function update(): void
    {
        $doctorId = (int)($_GET['id'] ?? 0);
        if ($doctorId <= 0) $this->json(400, ['success' => false, 'message' => 'Missing doctor id.']);

        $stmt = $this->pdo->prepare('SELECT doctor_id, user_id FROM `doctor` WHERE doctor_id = ? LIMIT 1');
        $stmt->execute([$doctorId]);
        $doctor = $stmt->fetch();
        if (!$doctor) $this->json(404, ['success' => false, 'message' => 'Doctor not found.']);
        $userId = (int)$doctor['user_id'];

        $data   = $this->input();
        $errors = $this->validate($data, $userId, false);
        if ($errors) $this->json(422, ['success' => false, 'message' => 'Please fix the highlighted fields.', 'errors' => $errors]);

        if ($this->usernameExists($data['username'], $userId))       $this->json(409, ['success' => false, 'errors' => ['username' => 'Username is already in use.']]);
        if ($this->emailExists($data['email'], $userId))             $this->json(409, ['success' => false, 'errors' => ['email' => 'Email is already registered.']]);
        if ($this->licenseExists($data['license_number'], $doctorId)) $this->json(409, ['success' => false, 'errors' => ['license_number' => 'License number is already registered.']]);

        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare(
                'UPDATE `user`
                 SET username = ?, first_name = ?, last_name = ?, email = ?, contact_number = ?
                 WHERE user_id = ?'
            );
            $stmt->execute([
                $data['username'],
                $data['first_name'],
                $data['last_name'],
                $data['email'],
                !empty($data['contact_number']) ? $data['contact_number'] : null,
                $userId,
            ]);

            $stmt = $this->pdo->prepare(
                'UPDATE `doctor` SET license_number = ?, consultation_fee = ? WHERE doctor_id = ?'
            );
            $stmt->execute([
                $data['license_number'],
                (float)$data['consultation_fee'],
                $doctorId,
            ]);

            $stmt = $this->pdo->prepare('DELETE FROM `doctor_specialization` WHERE doctor_id = ?');
            $stmt->execute([$doctorId]);
            $this->saveSpecializations($doctorId, $data['specializations'] ?? [], (int)($data['primary_specialization_id'] ?? 0));

            $this->pdo->commit();

            ws_broadcast('doctor.profile.updated', ['doctor_id' => $doctorId, 'user_id' => $userId], [1, 2]);

            $this->json(200, ['success' => true, 'message' => 'Doctor updated successfully.']);
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            error_log('[DoctorController::update] ' . $e->getMessage());
            $this->json(500, ['success' => false, 'message' => 'Could not update doctor.']);
        }
    }

    public function changePassword(): void
    {
        $doctorId = (int)($_GET['id'] ?? 0);
        if ($doctorId <= 0) $this->json(400, ['success' => false, 'message' => 'Missing doctor id.']);

        $stmt = $this->pdo->prepare('SELECT user_id FROM `doctor` WHERE doctor_id = ? LIMIT 1');
        $stmt->execute([$doctorId]);
        $row = $stmt->fetch();
        if (!$row) $this->json(404, ['success' => false, 'message' => 'Doctor not found.']);
        $userId = (int)$row['user_id'];

        $data     = $this->input();
        $password = (string)($data['password'] ?? '');
        $confirm  = (string)($data['password_confirm'] ?? '');

        $errors = [];
        if ($password === '')                $errors['password'] = 'Password is required.';
        elseif (strlen($password) < 6)       $errors['password'] = 'Password must be at least 6 characters.';
        if ($confirm === '')                 $errors['password_confirm'] = 'Please confirm.';
        elseif ($password !== $confirm)      $errors['password_confirm'] = 'Passwords do not match.';

        if ($errors) $this->json(422, ['success' => false, 'message' => 'Please fix the highlighted fields.', 'errors' => $errors]);

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->pdo->prepare('UPDATE `user` SET password_hash = ? WHERE user_id = ?');
        $stmt->execute([$hash, $userId]);

        $this->json(200, ['success' => true, 'message' => 'Password updated successfully.']);
    }

    public function toggleActive(): void
    {
        $doctorId = (int)($_GET['id'] ?? 0);
        if ($doctorId <= 0) $this->json(400, ['success' => false, 'message' => 'Missing doctor id.']);

        $stmt = $this->pdo->prepare(
            'SELECT d.user_id, d.is_active AS doctor_active, u.is_active AS user_active
             FROM `doctor` d
             INNER JOIN `user` u ON u.user_id = d.user_id
             WHERE d.doctor_id = ? LIMIT 1'
        );
        $stmt->execute([$doctorId]);
        $row = $stmt->fetch();
        if (!$row) $this->json(404, ['success' => false, 'message' => 'Doctor not found.']);
        $userId = (int)$row['user_id'];

        if ($userId === (int)$_SESSION['user']['user_id']) {
            $this->json(409, ['success' => false, 'message' => 'You cannot archive your own account.']);
        }

        $newState = (int)$row['doctor_active'] === 1 && (int)$row['user_active'] === 1 ? 0 : 1;

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare('UPDATE `user` SET is_active = ? WHERE user_id = ?');
            $stmt->execute([$newState, $userId]);

            $stmt = $this->pdo->prepare('UPDATE `doctor` SET is_active = ? WHERE doctor_id = ?');
            $stmt->execute([$newState, $doctorId]);

            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            $this->json(500, ['success' => false, 'message' => 'Could not update doctor.']);
        }

        ws_broadcast('doctor.profile.toggled', ['doctor_id' => $doctorId, 'is_active' => $newState], [1]);

        $this->json(200, [
            'success'   => true,
            'message'   => $newState === 1 ? 'Doctor reactivated.' : 'Doctor archived.',
            'is_active' => $newState,
        ]);
    }

    private function saveSpecializations(int $doctorId, array $specIds, int $primaryId): void
    {
        $specIds = array_values(array_unique(array_filter(array_map('intval', $specIds), fn($v) => $v > 0)));

        if (!$specIds) return;

        $placeholders = implode(',', array_fill(0, count($specIds), '?'));
        $stmt = $this->pdo->prepare("SELECT specialization_id FROM `specialization` WHERE specialization_id IN ($placeholders)");
        $stmt->execute($specIds);
        $valid = array_column($stmt->fetchAll(), 'specialization_id');

        $stmt = $this->pdo->prepare(
            'INSERT INTO `doctor_specialization` (doctor_id, specialization_id, is_primary)
             VALUES (?, ?, ?)'
        );

        foreach ($valid as $sid) {
            $isPrimary = (int)$sid === $primaryId ? 1 : 0;
            $stmt->execute([$doctorId, (int)$sid, $isPrimary]);
        }
    }

    private function input(): array
    {
        $raw = file_get_contents('php://input');
        return json_decode($raw, true) ?: $_POST;
    }

    private function validate(array $data, ?int $ignoreUserId, bool $requirePassword): array
    {
        $errors = [];

        if (trim((string)($data['first_name'] ?? '')) === '') $errors['first_name'] = 'First name is required.';
        if (trim((string)($data['last_name']  ?? '')) === '') $errors['last_name']  = 'Last name is required.';

        $username = trim((string)($data['username'] ?? ''));
        if ($username === '')                                        $errors['username'] = 'Username is required.';
        elseif (!preg_match('/^[a-zA-Z0-9._-]{3,50}$/', $username))  $errors['username'] = 'Username must be 3–50 chars.';

        $email = trim((string)($data['email'] ?? ''));
        if ($email === '')                                       $errors['email'] = 'Email is required.';
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL))      $errors['email'] = 'Email is not valid.';

        if (trim((string)($data['license_number'] ?? '')) === '') $errors['license_number'] = 'License number is required.';

        $fee = $data['consultation_fee'] ?? '';
        if ($fee === '' || !is_numeric($fee) || (float)$fee < 0) $errors['consultation_fee'] = 'Enter a valid consultation fee.';

        $primaryId = (int)($data['primary_specialization_id'] ?? 0);
        $specIds   = array_map('intval', $data['specializations'] ?? []);
        if ($primaryId > 0 && !in_array($primaryId, $specIds, true)) {
            $errors['primary_specialization_id'] = 'Primary must be one of the selected specializations.';
        }

        if ($requirePassword) {
            $password = (string)($data['password'] ?? '');
            if ($password === '')          $errors['password'] = 'Password is required.';
            elseif (strlen($password) < 6) $errors['password'] = 'Password must be at least 6 characters.';
        }

        return $errors;
    }

    private function usernameExists(string $username, ?int $ignoreUserId = null): bool
    {
        $sql = 'SELECT user_id FROM `user` WHERE username = ?';
        $args = [$username];
        if ($ignoreUserId !== null) { $sql .= ' AND user_id <> ?'; $args[] = $ignoreUserId; }
        $stmt = $this->pdo->prepare($sql . ' LIMIT 1');
        $stmt->execute($args);
        return (bool)$stmt->fetch();
    }

    private function emailExists(string $email, ?int $ignoreUserId = null): bool
    {
        $sql = 'SELECT user_id FROM `user` WHERE email = ?';
        $args = [$email];
        if ($ignoreUserId !== null) { $sql .= ' AND user_id <> ?'; $args[] = $ignoreUserId; }
        $stmt = $this->pdo->prepare($sql . ' LIMIT 1');
        $stmt->execute($args);
        return (bool)$stmt->fetch();
    }

    private function licenseExists(string $license, ?int $ignoreDoctorId = null): bool
    {
        $sql = 'SELECT doctor_id FROM `doctor` WHERE license_number = ?';
        $args = [$license];
        if ($ignoreDoctorId !== null) { $sql .= ' AND doctor_id <> ?'; $args[] = $ignoreDoctorId; }
        $stmt = $this->pdo->prepare($sql . ' LIMIT 1');
        $stmt->execute($args);
        return (bool)$stmt->fetch();
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