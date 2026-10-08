<?php

class PaymentTypeController
{
    private PDO $pdo;

    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function getAll(): array
    {
        return $this->pdo->query(
            'SELECT payment_type_id, type_name, description
             FROM `payment_type`
             ORDER BY payment_type_id'
        )->fetchAll();
    }

    public function create(): void
    {
        $data   = $this->input();
        $errors = $this->validate($data);
        if ($errors) $this->json(422, ['success' => false, 'message' => 'Please fix the highlighted fields.', 'errors' => $errors]);

        if ($this->nameExists($data['type_name'])) {
            $this->json(409, ['success' => false, 'errors' => ['type_name' => 'This payment type already exists.']]);
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO `payment_type` (type_name, description)
             VALUES (?, ?)'
        );
        $stmt->execute([
            $data['type_name'],
            !empty($data['description']) ? $data['description'] : null,
        ]);

        $this->json(201, [
            'success' => true,
            'message' => 'Payment type created successfully.',
            'id'      => (int)$this->pdo->lastInsertId(),
        ]);
    }

    public function update(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) $this->json(400, ['success' => false, 'message' => 'Missing payment type id.']);

        $data   = $this->input();
        $errors = $this->validate($data);
        if ($errors) $this->json(422, ['success' => false, 'message' => 'Please fix the highlighted fields.', 'errors' => $errors]);

        if ($this->nameExists($data['type_name'], $id)) {
            $this->json(409, ['success' => false, 'errors' => ['type_name' => 'This payment type already exists.']]);
        }

        $stmt = $this->pdo->prepare(
            'UPDATE `payment_type`
             SET type_name = ?, description = ?
             WHERE payment_type_id = ?'
        );
        $stmt->execute([
            $data['type_name'],
            !empty($data['description']) ? $data['description'] : null,
            $id,
        ]);

        $this->json(200, ['success' => true, 'message' => 'Payment type updated successfully.']);
    }

    public function delete(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) $this->json(400, ['success' => false, 'message' => 'Missing payment type id.']);

        $stmt = $this->pdo->prepare('SELECT payment_type_id FROM `payment_type` WHERE payment_type_id = ? LIMIT 1');
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            $this->json(404, ['success' => false, 'message' => 'Payment type not found.']);
        }

        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM `payment` WHERE payment_type_id = ?');
        $stmt->execute([$id]);
        $refCount = (int)$stmt->fetchColumn();

        if ($refCount > 0) {
            $this->json(409, [
                'success'    => false,
                'message'    => 'This item is currently being used and cannot be deleted.',
                'references' => $refCount,
            ]);
        }

        $stmt = $this->pdo->prepare('DELETE FROM `payment_type` WHERE payment_type_id = ?');
        $stmt->execute([$id]);

        $this->json(200, ['success' => true, 'message' => 'Payment type deleted.']);
    }

    private function input(): array
    {
        $raw = file_get_contents('php://input');
        return json_decode($raw, true) ?: $_POST;
    }

    private function validate(array $data): array
    {
        $errors = [];

        $name = trim((string)($data['type_name'] ?? ''));
        if ($name === '') {
            $errors['type_name'] = 'Type name is required.';
        } elseif (strlen($name) > 50) {
            $errors['type_name'] = 'Type name must be 50 characters or fewer.';
        }

        $desc = trim((string)($data['description'] ?? ''));
        if ($desc !== '' && strlen($desc) > 255) {
            $errors['description'] = 'Description must be 255 characters or fewer.';
        }

        return $errors;
    }

    private function nameExists(string $name, ?int $ignoreId = null): bool
    {
        $sql  = 'SELECT payment_type_id FROM `payment_type` WHERE type_name = ?';
        $args = [$name];
        if ($ignoreId !== null) {
            $sql .= ' AND payment_type_id <> ?';
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