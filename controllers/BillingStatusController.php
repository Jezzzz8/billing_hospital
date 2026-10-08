<?php

class BillingStatusController
{
    private PDO $pdo;

    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function getAll(): array
    {
        return $this->pdo->query(
            'SELECT status_id, status_name, color_code, is_paid_status
             FROM `billing_status`
             ORDER BY status_id'
        )->fetchAll();
    }

    public function create(): void
    {
        $data   = $this->input();
        $errors = $this->validate($data);
        if ($errors) $this->json(422, ['success' => false, 'message' => 'Please fix the highlighted fields.', 'errors' => $errors]);

        if ($this->nameExists($data['status_name'])) {
            $this->json(409, ['success' => false, 'errors' => ['status_name' => 'This status name already exists.']]);
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO `billing_status` (status_name, color_code, is_paid_status)
             VALUES (?, ?, ?)'
        );
        $stmt->execute([
            $data['status_name'],
            !empty($data['color_code']) ? $data['color_code'] : null,
            (int)(!empty($data['is_paid_status']) ? 1 : 0),
        ]);

        $this->json(201, [
            'success' => true,
            'message' => 'Billing status created successfully.',
            'id'      => (int)$this->pdo->lastInsertId(),
        ]);
    }

    public function update(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) $this->json(400, ['success' => false, 'message' => 'Missing status id.']);

        $data   = $this->input();
        $errors = $this->validate($data);
        if ($errors) $this->json(422, ['success' => false, 'message' => 'Please fix the highlighted fields.', 'errors' => $errors]);

        if ($this->nameExists($data['status_name'], $id)) {
            $this->json(409, ['success' => false, 'errors' => ['status_name' => 'This status name already exists.']]);
        }

        $stmt = $this->pdo->prepare(
            'UPDATE `billing_status`
             SET status_name = ?, color_code = ?, is_paid_status = ?
             WHERE status_id = ?'
        );
        $stmt->execute([
            $data['status_name'],
            !empty($data['color_code']) ? $data['color_code'] : null,
            (int)(!empty($data['is_paid_status']) ? 1 : 0),
            $id,
        ]);

        $this->json(200, ['success' => true, 'message' => 'Billing status updated successfully.']);
    }

    public function delete(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) $this->json(400, ['success' => false, 'message' => 'Missing status id.']);

        $stmt = $this->pdo->prepare('SELECT status_id FROM `billing_status` WHERE status_id = ? LIMIT 1');
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            $this->json(404, ['success' => false, 'message' => 'Billing status not found.']);
        }

        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM `billing_statement` WHERE status_id = ?');
        $stmt->execute([$id]);
        $refCount = (int)$stmt->fetchColumn();

        if ($refCount > 0) {
            $this->json(409, [
                'success'    => false,
                'message'    => 'This item is currently being used and cannot be deleted.',
                'references' => $refCount,
            ]);
        }

        $stmt = $this->pdo->prepare('DELETE FROM `billing_status` WHERE status_id = ?');
        $stmt->execute([$id]);

        $this->json(200, ['success' => true, 'message' => 'Billing status deleted.']);
    }

    private function input(): array
    {
        $raw = file_get_contents('php://input');
        return json_decode($raw, true) ?: $_POST;
    }

    private function validate(array $data): array
    {
        $errors = [];

        $name = trim((string)($data['status_name'] ?? ''));
        if ($name === '') {
            $errors['status_name'] = 'Status name is required.';
        } elseif (strlen($name) > 50) {
            $errors['status_name'] = 'Status name must be 50 characters or fewer.';
        }

        $color = trim((string)($data['color_code'] ?? ''));
        if ($color !== '' && !preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
            $errors['color_code'] = 'Color must be a hex code like #28a745.';
        }

        return $errors;
    }

    private function nameExists(string $name, ?int $ignoreId = null): bool
    {
        $sql  = 'SELECT status_id FROM `billing_status` WHERE status_name = ?';
        $args = [$name];
        if ($ignoreId !== null) {
            $sql .= ' AND status_id <> ?';
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