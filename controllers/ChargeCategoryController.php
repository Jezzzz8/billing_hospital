<?php

class ChargeCategoryController
{
    private PDO $pdo;

    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function getAll(): array
    {
        return $this->pdo->query(
            'SELECT category_id, category_name, description, calculation_type, is_recurring
             FROM `charge_category`
             ORDER BY category_id'
        )->fetchAll();
    }

    public function create(): void
    {
        $data   = $this->input();
        $errors = $this->validate($data);
        if ($errors) $this->json(422, ['success' => false, 'message' => 'Please fix the highlighted fields.', 'errors' => $errors]);

        if ($this->nameExists($data['category_name'])) {
            $this->json(409, ['success' => false, 'errors' => ['category_name' => 'This category name already exists.']]);
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO `charge_category`
                (category_name, description, calculation_type, is_recurring)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['category_name'],
            !empty($data['description']) ? $data['description'] : null,
            !empty($data['calculation_type']) ? $data['calculation_type'] : null,
            (int)(!empty($data['is_recurring']) ? 1 : 0),
        ]);

        $this->json(201, [
            'success' => true,
            'message' => 'Charge category created successfully.',
            'id'      => (int)$this->pdo->lastInsertId(),
        ]);
    }

    public function update(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) $this->json(400, ['success' => false, 'message' => 'Missing category id.']);

        $data   = $this->input();
        $errors = $this->validate($data);
        if ($errors) $this->json(422, ['success' => false, 'message' => 'Please fix the highlighted fields.', 'errors' => $errors]);

        if ($this->nameExists($data['category_name'], $id)) {
            $this->json(409, ['success' => false, 'errors' => ['category_name' => 'This category name already exists.']]);
        }

        $stmt = $this->pdo->prepare(
            'UPDATE `charge_category`
             SET category_name = ?, description = ?, calculation_type = ?, is_recurring = ?
             WHERE category_id = ?'
        );
        $stmt->execute([
            $data['category_name'],
            !empty($data['description']) ? $data['description'] : null,
            !empty($data['calculation_type']) ? $data['calculation_type'] : null,
            (int)(!empty($data['is_recurring']) ? 1 : 0),
            $id,
        ]);

        $this->json(200, ['success' => true, 'message' => 'Charge category updated successfully.']);
    }

    public function delete(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) $this->json(400, ['success' => false, 'message' => 'Missing category id.']);

        $stmt = $this->pdo->prepare('SELECT category_id FROM `charge_category` WHERE category_id = ? LIMIT 1');
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            $this->json(404, ['success' => false, 'message' => 'Charge category not found.']);
        }

        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM `charge_item` WHERE category_id = ?');
        $stmt->execute([$id]);
        $refCount = (int)$stmt->fetchColumn();

        if ($refCount > 0) {
            $this->json(409, [
                'success'    => false,
                'message'    => 'This item is currently being used and cannot be deleted.',
                'references' => $refCount,
            ]);
        }

        $stmt = $this->pdo->prepare('DELETE FROM `charge_category` WHERE category_id = ?');
        $stmt->execute([$id]);

        $this->json(200, ['success' => true, 'message' => 'Charge category deleted.']);
    }

    private function input(): array
    {
        $raw = file_get_contents('php://input');
        return json_decode($raw, true) ?: $_POST;
    }

    private function validate(array $data): array
    {
        $errors = [];

        $name = trim((string)($data['category_name'] ?? ''));
        if ($name === '') {
            $errors['category_name'] = 'Category name is required.';
        } elseif (strlen($name) > 100) {
            $errors['category_name'] = 'Category name must be 100 characters or fewer.';
        }

        $desc = trim((string)($data['description'] ?? ''));
        if ($desc !== '' && strlen($desc) > 255) {
            $errors['description'] = 'Description must be 255 characters or fewer.';
        }

        $calc = trim((string)($data['calculation_type'] ?? ''));
        $allowedCalc = ['Per Day', 'Per Item', 'Flat', ''];
        if (!in_array($calc, $allowedCalc, true)) {
            $errors['calculation_type'] = 'Please select a valid calculation type.';
        }

        return $errors;
    }

    private function nameExists(string $name, ?int $ignoreId = null): bool
    {
        $sql  = 'SELECT category_id FROM `charge_category` WHERE category_name = ?';
        $args = [$name];
        if ($ignoreId !== null) {
            $sql .= ' AND category_id <> ?';
            $args[] = $ignoreId;
        }
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