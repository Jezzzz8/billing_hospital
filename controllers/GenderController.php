<?php

class GenderController
{
    private PDO $pdo;

    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function getAll(): array
    {
        return $this->pdo->query(
            'SELECT gender_id, gender_name
             FROM `gender`
             ORDER BY gender_id'
        )->fetchAll();
    }

    public function create(): void
    {
        $data   = $this->input();
        $errors = $this->validate($data);
        if ($errors) $this->json(422, ['success' => false, 'message' => 'Please fix the highlighted fields.', 'errors' => $errors]);

        if ($this->nameExists($data['gender_name'])) {
            $this->json(409, ['success' => false, 'errors' => ['gender_name' => 'This gender already exists.']]);
        }

        $stmt = $this->pdo->prepare('INSERT INTO `gender` (gender_name) VALUES (?)');
        $stmt->execute([$data['gender_name']]);

        $this->json(201, [
            'success' => true,
            'message' => 'Gender created successfully.',
            'id'      => (int)$this->pdo->lastInsertId(),
        ]);
    }

    public function update(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) $this->json(400, ['success' => false, 'message' => 'Missing gender id.']);

        $data   = $this->input();
        $errors = $this->validate($data);
        if ($errors) $this->json(422, ['success' => false, 'message' => 'Please fix the highlighted fields.', 'errors' => $errors]);

        if ($this->nameExists($data['gender_name'], $id)) {
            $this->json(409, ['success' => false, 'errors' => ['gender_name' => 'This gender already exists.']]);
        }

        $stmt = $this->pdo->prepare('UPDATE `gender` SET gender_name = ? WHERE gender_id = ?');
        $stmt->execute([$data['gender_name'], $id]);

        $this->json(200, ['success' => true, 'message' => 'Gender updated successfully.']);
    }

    public function delete(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) $this->json(400, ['success' => false, 'message' => 'Missing gender id.']);

        $stmt = $this->pdo->prepare('SELECT gender_id FROM `gender` WHERE gender_id = ? LIMIT 1');
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            $this->json(404, ['success' => false, 'message' => 'Gender not found.']);
        }

        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM `patient` WHERE gender_id = ?');
        $stmt->execute([$id]);
        $refCount = (int)$stmt->fetchColumn();

        if ($refCount > 0) {
            $this->json(409, [
                'success'    => false,
                'message'    => 'This item is currently being used and cannot be deleted.',
                'references' => $refCount,
            ]);
        }

        $stmt = $this->pdo->prepare('DELETE FROM `gender` WHERE gender_id = ?');
        $stmt->execute([$id]);

        $this->json(200, ['success' => true, 'message' => 'Gender deleted.']);
    }

    private function input(): array
    {
        $raw = file_get_contents('php://input');
        return json_decode($raw, true) ?: $_POST;
    }

    private function validate(array $data): array
    {
        $errors = [];

        $name = trim((string)($data['gender_name'] ?? ''));
        if ($name === '') {
            $errors['gender_name'] = 'Gender name is required.';
        } elseif (strlen($name) > 50) {
            $errors['gender_name'] = 'Gender name must be 50 characters or fewer.';
        }

        return $errors;
    }

    private function nameExists(string $name, ?int $ignoreId = null): bool
    {
        $sql  = 'SELECT gender_id FROM `gender` WHERE gender_name = ?';
        $args = [$name];
        if ($ignoreId !== null) {
            $sql .= ' AND gender_id <> ?';
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