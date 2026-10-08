<?php

class RoleController
{
    private PDO $pdo;

    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function getAll(): array
    {
        return $this->pdo->query(
            'SELECT role_id, role_name, description
             FROM `role`
             ORDER BY role_id'
        )->fetchAll();
    }

    public function create(): void
    {
        $data   = $this->input();
        $errors = $this->validate($data);
        if ($errors) $this->json(422, ['success' => false, 'message' => 'Please fix the highlighted fields.', 'errors' => $errors]);

        if ($this->nameExists($data['role_name'])) {
            $this->json(409, ['success' => false, 'errors' => ['role_name' => 'This role name already exists.']]);
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO `role` (role_name, description)
             VALUES (?, ?)'
        );
        $stmt->execute([
            $data['role_name'],
            !empty($data['description']) ? $data['description'] : null,
        ]);

        $this->json(201, [
            'success' => true,
            'message' => 'Role created successfully.',
            'id'      => (int)$this->pdo->lastInsertId(),
        ]);
    }

    public function update(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) $this->json(400, ['success' => false, 'message' => 'Missing role id.']);

        $data   = $this->input();
        $errors = $this->validate($data);
        if ($errors) $this->json(422, ['success' => false, 'message' => 'Please fix the highlighted fields.', 'errors' => $errors]);

        if ($this->nameExists($data['role_name'], $id)) {
            $this->json(409, ['success' => false, 'errors' => ['role_name' => 'This role name already exists.']]);
        }

        $stmt = $this->pdo->prepare(
            'UPDATE `role`
             SET role_name = ?, description = ?
             WHERE role_id = ?'
        );
        $stmt->execute([
            $data['role_name'],
            !empty($data['description']) ? $data['description'] : null,
            $id,
        ]);

        $this->json(200, ['success' => true, 'message' => 'Role updated successfully.']);
    }

    public function delete(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) $this->json(400, ['success' => false, 'message' => 'Missing role id.']);

        $stmt = $this->pdo->prepare('SELECT role_id FROM `role` WHERE role_id = ? LIMIT 1');
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            $this->json(404, ['success' => false, 'message' => 'Role not found.']);
        }

        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM `user` WHERE role_id = ?');
        $stmt->execute([$id]);
        $refCount = (int)$stmt->fetchColumn();

        if ($refCount > 0) {
            $this->json(409, [
                'success'    => false,
                'message'    => 'This item is currently being used and cannot be deleted.',
                'references' => $refCount,
            ]);
        }

        $stmt = $this->pdo->prepare('DELETE FROM `role` WHERE role_id = ?');
        $stmt->execute([$id]);

        $this->json(200, ['success' => true, 'message' => 'Role deleted.']);
    }

    private function input(): array
    {
        $raw = file_get_contents('php://input');
        return json_decode($raw, true) ?: $_POST;
    }

    private function validate(array $data): array
    {
        $errors = [];

        $name = trim((string)($data['role_name'] ?? ''));
        if ($name === '') {
            $errors['role_name'] = 'Role name is required.';
        } elseif (strlen($name) > 50) {
            $errors['role_name'] = 'Role name must be 50 characters or fewer.';
        } elseif (!preg_match('/^[a-z][a-z0-9_]*$/i', $name)) {
            $errors['role_name'] = 'Role name may only contain letters, numbers, and underscores.';
        }

        $desc = trim((string)($data['description'] ?? ''));
        if ($desc !== '' && strlen($desc) > 255) {
            $errors['description'] = 'Description must be 255 characters or fewer.';
        }

        return $errors;
    }

    private function nameExists(string $name, ?int $ignoreId = null): bool
    {
        $sql  = 'SELECT role_id FROM `role` WHERE role_name = ?';
        $args = [$name];
        if ($ignoreId !== null) {
            $sql .= ' AND role_id <> ?';
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