<?php

require_once __DIR__ . '/../includes/ws_notify.php';

class DepartmentAdminController
{
    private PDO $pdo;

    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function getAll(): array
    {
        return $this->pdo->query(
            'SELECT d.department_id, d.department_name, d.department_code,
                    d.description, d.is_active, d.charge_category_id,
                    cc.category_name AS charge_category_name,
                    (SELECT COUNT(*) FROM `service_request` sr
                     WHERE sr.department_id = d.department_id
                       AND sr.status IN ("Pending", "In Progress")) AS pending_count,
                    (SELECT COUNT(*) FROM `user` u
                     WHERE u.department_id = d.department_id AND u.is_active = 1) AS staff_count
             FROM `department` d
             LEFT JOIN `charge_category` cc ON cc.category_id = d.charge_category_id
             ORDER BY d.department_id'
        )->fetchAll();
    }

    public function getChargeCategories(): array
    {
        return $this->pdo->query(
            'SELECT category_id, category_name FROM `charge_category` ORDER BY category_name'
        )->fetchAll();
    }

    public function create(): void
    {
        $data   = $this->input();
        $errors = $this->validate($data);
        if ($errors) $this->json(422, ['success' => false, 'message' => 'Please fix the highlighted fields.', 'errors' => $errors]);

        if ($this->nameExists($data['department_name'])) {
            $this->json(409, ['success' => false, 'errors' => ['department_name' => 'This department name already exists.']]);
        }
        if (!empty($data['department_code']) && $this->codeExists($data['department_code'])) {
            $this->json(409, ['success' => false, 'errors' => ['department_code' => 'This department code is already in use.']]);
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO `department` (department_name, department_code, charge_category_id, description, is_active)
             VALUES (?, ?, ?, ?, 1)'
        );
        $stmt->execute([
            $data['department_name'],
            !empty($data['department_code']) ? strtoupper($data['department_code']) : null,
            !empty($data['charge_category_id']) ? (int)$data['charge_category_id'] : null,
            !empty($data['description']) ? $data['description'] : null,
        ]);

        $id = (int)$this->pdo->lastInsertId();
        ws_broadcast('master.updated', ['resource' => 'departments', 'operation' => 'create', 'id' => $id], [1, 3]);

        $this->json(201, [
            'success' => true,
            'message' => 'Department created successfully.',
            'id'      => $id,
        ]);
    }

    public function update(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) $this->json(400, ['success' => false, 'message' => 'Missing department id.']);

        $stmt = $this->pdo->prepare('SELECT department_id FROM `department` WHERE department_id = ? LIMIT 1');
        $stmt->execute([$id]);
        if (!$stmt->fetch()) $this->json(404, ['success' => false, 'message' => 'Department not found.']);

        $data   = $this->input();
        $errors = $this->validate($data);
        if ($errors) $this->json(422, ['success' => false, 'message' => 'Please fix the highlighted fields.', 'errors' => $errors]);

        if ($this->nameExists($data['department_name'], $id)) {
            $this->json(409, ['success' => false, 'errors' => ['department_name' => 'This department name already exists.']]);
        }
        if (!empty($data['department_code']) && $this->codeExists($data['department_code'], $id)) {
            $this->json(409, ['success' => false, 'errors' => ['department_code' => 'This department code is already in use.']]);
        }

        $stmt = $this->pdo->prepare(
            'UPDATE `department`
             SET department_name = ?, department_code = ?, charge_category_id = ?, description = ?
             WHERE department_id = ?'
        );
        $stmt->execute([
            $data['department_name'],
            !empty($data['department_code']) ? strtoupper($data['department_code']) : null,
            !empty($data['charge_category_id']) ? (int)$data['charge_category_id'] : null,
            !empty($data['description']) ? $data['description'] : null,
            $id,
        ]);

        ws_broadcast('master.updated', ['resource' => 'departments', 'operation' => 'update', 'id' => $id], [1, 3]);

        $this->json(200, ['success' => true, 'message' => 'Department updated successfully.']);
    }

    public function toggleActive(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) $this->json(400, ['success' => false, 'message' => 'Missing department id.']);

        $stmt = $this->pdo->prepare('SELECT is_active FROM `department` WHERE department_id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) $this->json(404, ['success' => false, 'message' => 'Department not found.']);

        $newState = ((int)$row['is_active'] === 1) ? 0 : 1;

        if ($newState === 0) {
            $stmt = $this->pdo->prepare(
                'SELECT COUNT(*) FROM `service_request`
                 WHERE department_id = ? AND status IN ("Pending", "In Progress")'
            );
            $stmt->execute([$id]);
            if ((int)$stmt->fetchColumn() > 0) {
                $this->json(409, [
                    'success' => false,
                    'message' => 'Cannot archive — this department has active service requests that must be resolved first.',
                ]);
            }
        }

        $stmt = $this->pdo->prepare('UPDATE `department` SET is_active = ? WHERE department_id = ?');
        $stmt->execute([$newState, $id]);

        ws_broadcast('master.updated', ['resource' => 'departments', 'operation' => 'toggle-active', 'id' => $id, 'is_active' => $newState], [1, 3]);

        $this->json(200, [
            'success'   => true,
            'message'   => $newState === 1 ? 'Department reactivated.' : 'Department archived.',
            'is_active' => $newState,
        ]);
    }

    private function input(): array
    {
        $raw = file_get_contents('php://input');
        return json_decode($raw, true) ?: $_POST;
    }

    private function validate(array $data): array
    {
        $errors = [];

        $name = trim((string)($data['department_name'] ?? ''));
        if ($name === '') {
            $errors['department_name'] = 'Department name is required.';
        } elseif (strlen($name) > 100) {
            $errors['department_name'] = 'Department name must be 100 characters or fewer.';
        }

        $code = trim((string)($data['department_code'] ?? ''));
        if ($code !== '' && strlen($code) > 50) {
            $errors['department_code'] = 'Department code must be 50 characters or fewer.';
        }

        if (!empty($data['charge_category_id'])) {
            $stmt = $this->pdo->prepare('SELECT category_id FROM `charge_category` WHERE category_id = ? LIMIT 1');
            $stmt->execute([(int)$data['charge_category_id']]);
            if (!$stmt->fetch()) {
                $errors['charge_category_id'] = 'Selected charge category is not valid.';
            }
        }

        $desc = trim((string)($data['description'] ?? ''));
        if ($desc !== '' && strlen($desc) > 255) {
            $errors['description'] = 'Description must be 255 characters or fewer.';
        }

        return $errors;
    }

    private function nameExists(string $name, ?int $ignoreId = null): bool
    {
        $sql  = 'SELECT department_id FROM `department` WHERE department_name = ?';
        $args = [$name];
        if ($ignoreId !== null) { $sql .= ' AND department_id <> ?'; $args[] = $ignoreId; }
        $stmt = $this->pdo->prepare($sql . ' LIMIT 1');
        $stmt->execute($args);
        return (bool)$stmt->fetch();
    }

    private function codeExists(string $code, ?int $ignoreId = null): bool
    {
        $sql  = 'SELECT department_id FROM `department` WHERE department_code = ?';
        $args = [strtoupper($code)];
        if ($ignoreId !== null) { $sql .= ' AND department_id <> ?'; $args[] = $ignoreId; }
        $stmt = $this->pdo->prepare($sql . ' LIMIT 1');
        $stmt->execute($args);
        return (bool)$stmt->fetch();
    }

    private function json(int $status, array $payload): void
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        http_response_code($status);
        echo json_encode($payload);
        exit;
    }
}