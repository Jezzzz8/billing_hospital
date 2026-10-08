<?php

class DiagnosisController
{
    private PDO $pdo;

    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function getAll(): array
    {
        return $this->pdo->query(
            'SELECT diagnosis_id, icd_code, diagnosis_name, description, is_active
             FROM `diagnosis`
             ORDER BY diagnosis_id'
        )->fetchAll();
    }

    public function create(): void
    {
        $data   = $this->input();
        $errors = $this->validate($data);
        if ($errors) $this->json(422, ['success' => false, 'message' => 'Please fix the highlighted fields.', 'errors' => $errors]);

        if ($this->nameExists($data['diagnosis_name'])) {
            $this->json(409, ['success' => false, 'errors' => ['diagnosis_name' => 'A diagnosis with this name already exists.']]);
        }
        if (!empty($data['icd_code']) && $this->icdExists($data['icd_code'])) {
            $this->json(409, ['success' => false, 'errors' => ['icd_code' => 'This ICD code is already in use.']]);
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO `diagnosis` (icd_code, diagnosis_name, description, is_active)
             VALUES (?, ?, ?, 1)'
        );
        $stmt->execute([
            !empty($data['icd_code']) ? $data['icd_code'] : null,
            $data['diagnosis_name'],
            !empty($data['description']) ? $data['description'] : null,
        ]);

        $this->json(201, [
            'success' => true,
            'message' => 'Diagnosis created successfully.',
            'id'      => (int)$this->pdo->lastInsertId(),
        ]);
    }

    public function update(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) $this->json(400, ['success' => false, 'message' => 'Missing diagnosis id.']);

        $data   = $this->input();
        $errors = $this->validate($data);
        if ($errors) $this->json(422, ['success' => false, 'message' => 'Please fix the highlighted fields.', 'errors' => $errors]);

        if ($this->nameExists($data['diagnosis_name'], $id)) {
            $this->json(409, ['success' => false, 'errors' => ['diagnosis_name' => 'A diagnosis with this name already exists.']]);
        }
        if (!empty($data['icd_code']) && $this->icdExists($data['icd_code'], $id)) {
            $this->json(409, ['success' => false, 'errors' => ['icd_code' => 'This ICD code is already in use.']]);
        }

        $stmt = $this->pdo->prepare(
            'UPDATE `diagnosis`
             SET icd_code = ?, diagnosis_name = ?, description = ?
             WHERE diagnosis_id = ?'
        );
        $stmt->execute([
            !empty($data['icd_code']) ? $data['icd_code'] : null,
            $data['diagnosis_name'],
            !empty($data['description']) ? $data['description'] : null,
            $id,
        ]);

        $this->json(200, ['success' => true, 'message' => 'Diagnosis updated successfully.']);
    }

    public function toggleActive(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) $this->json(400, ['success' => false, 'message' => 'Missing diagnosis id.']);

        $stmt = $this->pdo->prepare('SELECT is_active FROM `diagnosis` WHERE diagnosis_id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) $this->json(404, ['success' => false, 'message' => 'Diagnosis not found.']);

        $newState = ((int)$row['is_active'] === 1) ? 0 : 1;

        $stmt = $this->pdo->prepare('UPDATE `diagnosis` SET is_active = ? WHERE diagnosis_id = ?');
        $stmt->execute([$newState, $id]);

        $this->json(200, [
            'success'   => true,
            'message'   => $newState === 1 ? 'Diagnosis reactivated.' : 'Diagnosis archived.',
            'is_active' => $newState,
        ]);
    }

    public function delete(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) $this->json(400, ['success' => false, 'message' => 'Missing diagnosis id.']);

        $stmt = $this->pdo->prepare('SELECT is_active FROM `diagnosis` WHERE diagnosis_id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) $this->json(404, ['success' => false, 'message' => 'Diagnosis not found.']);

        if ((int)$row['is_active'] === 1) {
            $this->json(409, ['success' => false, 'message' => 'Archive the diagnosis first before deleting.']);
        }

        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM `admission_diagnosis` WHERE diagnosis_id = ?');
        $stmt->execute([$id]);
        $refCount = (int)$stmt->fetchColumn();

        if ($refCount > 0) {
            $this->json(409, [
                'success'    => false,
                'message'    => 'This item is currently being used and cannot be deleted.',
                'references' => $refCount,
            ]);
        }

        $stmt = $this->pdo->prepare('DELETE FROM `diagnosis` WHERE diagnosis_id = ?');
        $stmt->execute([$id]);

        $this->json(200, ['success' => true, 'message' => 'Diagnosis permanently deleted.']);
    }

    private function input(): array
    {
        $raw = file_get_contents('php://input');
        return json_decode($raw, true) ?: $_POST;
    }

    private function validate(array $data): array
    {
        $errors = [];

        $name = trim((string)($data['diagnosis_name'] ?? ''));
        if ($name === '') {
            $errors['diagnosis_name'] = 'Diagnosis name is required.';
        } elseif (strlen($name) > 150) {
            $errors['diagnosis_name'] = 'Diagnosis name must be 150 characters or fewer.';
        }

        $icd = trim((string)($data['icd_code'] ?? ''));
        if ($icd !== '' && strlen($icd) > 50) {
            $errors['icd_code'] = 'ICD code must be 50 characters or fewer.';
        }

        $desc = trim((string)($data['description'] ?? ''));
        if ($desc !== '' && strlen($desc) > 255) {
            $errors['description'] = 'Description must be 255 characters or fewer.';
        }

        return $errors;
    }

    private function nameExists(string $name, ?int $ignoreId = null): bool
    {
        $sql  = 'SELECT diagnosis_id FROM `diagnosis` WHERE diagnosis_name = ?';
        $args = [$name];
        if ($ignoreId !== null) { $sql .= ' AND diagnosis_id <> ?'; $args[] = $ignoreId; }
        $stmt = $this->pdo->prepare($sql . ' LIMIT 1');
        $stmt->execute($args);
        return (bool)$stmt->fetch();
    }

    private function icdExists(string $code, ?int $ignoreId = null): bool
    {
        $sql  = 'SELECT diagnosis_id FROM `diagnosis` WHERE icd_code = ?';
        $args = [$code];
        if ($ignoreId !== null) { $sql .= ' AND diagnosis_id <> ?'; $args[] = $ignoreId; }
        $stmt = $this->pdo->prepare($sql . ' LIMIT 1');
        $stmt->execute($args);
        return (bool)$stmt->fetch();
    }

    private function json(int $status, array $payload): void
    {
        http_response_code($status);
        echo json_encode($payload);
        exit;
    }
}