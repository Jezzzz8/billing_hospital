<?php

class SpecializationController
{
    private PDO $pdo;

    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function getAll(): array
    {
        return $this->pdo->query(
            'SELECT specialization_id, specialization_name
             FROM `specialization`
             ORDER BY specialization_id'
        )->fetchAll();
    }

    public function create(): void
    {
        $data   = $this->input();
        $errors = $this->validate($data);
        if ($errors) $this->json(422, ['success' => false, 'message' => 'Please fix the highlighted fields.', 'errors' => $errors]);

        if ($this->nameExists($data['specialization_name'])) {
            $this->json(409, ['success' => false, 'errors' => ['specialization_name' => 'This specialization already exists.']]);
        }

        $stmt = $this->pdo->prepare('INSERT INTO `specialization` (specialization_name) VALUES (?)');
        $stmt->execute([$data['specialization_name']]);

        $this->json(201, [
            'success' => true,
            'message' => 'Specialization created successfully.',
            'id'      => (int)$this->pdo->lastInsertId(),
        ]);
    }

    public function update(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) $this->json(400, ['success' => false, 'message' => 'Missing specialization id.']);

        $data   = $this->input();
        $errors = $this->validate($data);
        if ($errors) $this->json(422, ['success' => false, 'message' => 'Please fix the highlighted fields.', 'errors' => $errors]);

        if ($this->nameExists($data['specialization_name'], $id)) {
            $this->json(409, ['success' => false, 'errors' => ['specialization_name' => 'This specialization already exists.']]);
        }

        $stmt = $this->pdo->prepare(
            'UPDATE `specialization` SET specialization_name = ? WHERE specialization_id = ?'
        );
        $stmt->execute([$data['specialization_name'], $id]);

        $this->json(200, ['success' => true, 'message' => 'Specialization updated successfully.']);
    }

    public function delete(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) $this->json(400, ['success' => false, 'message' => 'Missing specialization id.']);

        $stmt = $this->pdo->prepare('SELECT specialization_id FROM `specialization` WHERE specialization_id = ? LIMIT 1');
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            $this->json(404, ['success' => false, 'message' => 'Specialization not found.']);
        }

        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM `doctor_specialization` WHERE specialization_id = ?');
        $stmt->execute([$id]);
        $refCount = (int)$stmt->fetchColumn();

        if ($refCount > 0) {
            $this->json(409, [
                'success'    => false,
                'message'    => 'This item is currently being used and cannot be deleted.',
                'references' => $refCount,
            ]);
        }

        $stmt = $this->pdo->prepare('DELETE FROM `specialization` WHERE specialization_id = ?');
        $stmt->execute([$id]);

        $this->json(200, ['success' => true, 'message' => 'Specialization deleted.']);
    }

    private function input(): array
    {
        $raw = file_get_contents('php://input');
        return json_decode($raw, true) ?: $_POST;
    }

    private function validate(array $data): array
    {
        $errors = [];

        $name = trim((string)($data['specialization_name'] ?? ''));
        if ($name === '') {
            $errors['specialization_name'] = 'Specialization name is required.';
        } elseif (strlen($name) > 100) {
            $errors['specialization_name'] = 'Specialization name must be 100 characters or fewer.';
        }

        return $errors;
    }

    private function nameExists(string $name, ?int $ignoreId = null): bool
    {
        $sql  = 'SELECT specialization_id FROM `specialization` WHERE specialization_name = ?';
        $args = [$name];
        if ($ignoreId !== null) {
            $sql .= ' AND specialization_id <> ?';
            $args[] = $ignoreId;
        }
        $sql .= ' LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
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