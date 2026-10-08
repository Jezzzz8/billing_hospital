<?php

class ChargeItemController
{
    private PDO $pdo;

    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function getAll(): array
    {
        return $this->pdo->query(
            'SELECT ci.charge_item_id, ci.category_id, ci.item_code, ci.item_name,
                    ci.default_price, ci.is_active, ci.is_taxable,
                    ci.requires_doctor_order, ci.unit_of_measure,
                    cc.category_name
             FROM `charge_item` ci
             INNER JOIN `charge_category` cc ON cc.category_id = ci.category_id
             ORDER BY ci.charge_item_id'
        )->fetchAll();
    }

    public function getCategories(): array
    {
        return $this->pdo->query(
            'SELECT category_id, category_name
             FROM `charge_category`
             ORDER BY category_name'
        )->fetchAll();
    }

    public function create(): void
    {
        $data   = $this->input();
        $errors = $this->validate($data);
        if ($errors) $this->json(422, ['success' => false, 'message' => 'Please fix the highlighted fields.', 'errors' => $errors]);

        if ($this->codeExists($data['item_code'])) {
            $this->json(409, ['success' => false, 'errors' => ['item_code' => 'This item code is already in use.']]);
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO `charge_item`
                (category_id, item_code, item_name, default_price,
                 is_active, is_taxable, requires_doctor_order, unit_of_measure)
             VALUES (?, ?, ?, ?, 1, ?, ?, ?)'
        );
        $stmt->execute([
            (int)$data['category_id'],
            $data['item_code'],
            $data['item_name'],
            (float)$data['default_price'],
            (int)(!empty($data['is_taxable']) ? 1 : 0),
            (int)(!empty($data['requires_doctor_order']) ? 1 : 0),
            !empty($data['unit_of_measure']) ? $data['unit_of_measure'] : null,
        ]);

        $this->json(201, [
            'success' => true,
            'message' => 'Charge item created successfully.',
            'id'      => (int)$this->pdo->lastInsertId(),
        ]);
    }

    public function update(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) $this->json(400, ['success' => false, 'message' => 'Missing charge item id.']);

        $data   = $this->input();
        $errors = $this->validate($data);
        if ($errors) $this->json(422, ['success' => false, 'message' => 'Please fix the highlighted fields.', 'errors' => $errors]);

        if ($this->codeExists($data['item_code'], $id)) {
            $this->json(409, ['success' => false, 'errors' => ['item_code' => 'This item code is already in use.']]);
        }

        $stmt = $this->pdo->prepare(
            'UPDATE `charge_item`
             SET category_id = ?, item_code = ?, item_name = ?, default_price = ?,
                 is_taxable = ?, requires_doctor_order = ?, unit_of_measure = ?
             WHERE charge_item_id = ?'
        );
        $stmt->execute([
            (int)$data['category_id'],
            $data['item_code'],
            $data['item_name'],
            (float)$data['default_price'],
            (int)(!empty($data['is_taxable']) ? 1 : 0),
            (int)(!empty($data['requires_doctor_order']) ? 1 : 0),
            !empty($data['unit_of_measure']) ? $data['unit_of_measure'] : null,
            $id,
        ]);

        $this->json(200, ['success' => true, 'message' => 'Charge item updated successfully.']);
    }

    public function toggleActive(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) $this->json(400, ['success' => false, 'message' => 'Missing charge item id.']);

        $stmt = $this->pdo->prepare('SELECT is_active FROM `charge_item` WHERE charge_item_id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) $this->json(404, ['success' => false, 'message' => 'Charge item not found.']);

        $newState = ((int)$row['is_active'] === 1) ? 0 : 1;

        $stmt = $this->pdo->prepare('UPDATE `charge_item` SET is_active = ? WHERE charge_item_id = ?');
        $stmt->execute([$newState, $id]);

        $this->json(200, [
            'success'   => true,
            'message'   => $newState === 1 ? 'Charge item reactivated.' : 'Charge item archived.',
            'is_active' => $newState,
        ]);
    }

    public function delete(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) $this->json(400, ['success' => false, 'message' => 'Missing charge item id.']);

        $stmt = $this->pdo->prepare('SELECT is_active FROM `charge_item` WHERE charge_item_id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) $this->json(404, ['success' => false, 'message' => 'Charge item not found.']);

        if ((int)$row['is_active'] === 1) {
            $this->json(409, ['success' => false, 'message' => 'Archive the charge item first before deleting.']);
        }

        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM `charge` WHERE charge_item_id = ?');
        $stmt->execute([$id]);
        $chargeRefs = (int)$stmt->fetchColumn();

        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM `service_request` WHERE charge_item_id = ?');
        $stmt->execute([$id]);
        $requestRefs = (int)$stmt->fetchColumn();

        $serviceRequestItemTable = $this->pdo->query("SHOW TABLES LIKE 'service_request_item'")->fetchColumn();
        if ($serviceRequestItemTable) {
            $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM `service_request_item` WHERE charge_item_id = ?');
            $stmt->execute([$id]);
            $requestRefs += (int)$stmt->fetchColumn();
        }

        $totalRefs = $chargeRefs + $requestRefs;

        if ($totalRefs > 0) {
            $this->json(409, [
                'success'    => false,
                'message'    => 'This item is currently being used and cannot be deleted.',
                'references' => $totalRefs,
            ]);
        }

        $stmt = $this->pdo->prepare('DELETE FROM `charge_item` WHERE charge_item_id = ?');
        $stmt->execute([$id]);

        $this->json(200, ['success' => true, 'message' => 'Charge item permanently deleted.']);
    }

    private function input(): array
    {
        $raw = file_get_contents('php://input');
        return json_decode($raw, true) ?: $_POST;
    }

    private function validate(array $data): array
    {
        $errors = [];

        $catId = (int)($data['category_id'] ?? 0);
        if ($catId <= 0) {
            $errors['category_id'] = 'Please select a category.';
        } else {
            $stmt = $this->pdo->prepare('SELECT category_id FROM `charge_category` WHERE category_id = ? LIMIT 1');
            $stmt->execute([$catId]);
            if (!$stmt->fetch()) {
                $errors['category_id'] = 'Selected category is not valid.';
            }
        }

        $code = trim((string)($data['item_code'] ?? ''));
        if ($code === '') {
            $errors['item_code'] = 'Item code is required.';
        } elseif (strlen($code) > 50) {
            $errors['item_code'] = 'Item code must be 50 characters or fewer.';
        }

        $name = trim((string)($data['item_name'] ?? ''));
        if ($name === '') {
            $errors['item_name'] = 'Item name is required.';
        } elseif (strlen($name) > 150) {
            $errors['item_name'] = 'Item name must be 150 characters or fewer.';
        }

        $price = $data['default_price'] ?? '';
        if ($price === '' || !is_numeric($price) || (float)$price < 0) {
            $errors['default_price'] = 'Enter a valid price (0 or higher).';
        }

        $unit = trim((string)($data['unit_of_measure'] ?? ''));
        if ($unit !== '' && strlen($unit) > 50) {
            $errors['unit_of_measure'] = 'Unit must be 50 characters or fewer.';
        }

        return $errors;
    }

    private function codeExists(string $code, ?int $ignoreId = null): bool
    {
        $sql  = 'SELECT charge_item_id FROM `charge_item` WHERE item_code = ?';
        $args = [$code];
        if ($ignoreId !== null) {
            $sql .= ' AND charge_item_id <> ?';
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