<?php

class FloorLevelController
{
    private PDO $pdo;

    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function getAll(): array
    {
        return $this->pdo->query(
            'SELECT fl.floor_level_id, fl.building_id, fl.floor_level_name,
                    b.building_name,
                    (SELECT COUNT(*) FROM `room` r
                     WHERE r.floor_level_id = fl.floor_level_id AND r.is_active = 1) AS room_count
             FROM `floor_level` fl
             INNER JOIN `building` b ON b.building_id = fl.building_id
             ORDER BY b.building_name, fl.floor_level_name'
        )->fetchAll();
    }

    public function getByBuilding(int $buildingId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT floor_level_id, building_id, floor_level_name
             FROM `floor_level`
             WHERE building_id = ?
             ORDER BY floor_level_name'
        );
        $stmt->execute([$buildingId]);
        return $stmt->fetchAll();
    }

    public function create(): void
    {
        $data   = $this->input();
        $errors = $this->validate($data);
        if ($errors) $this->json(422, ['success' => false, 'message' => 'Please fix the highlighted fields.', 'errors' => $errors]);

        if ($this->exists($data['building_id'], $data['floor_level_name'])) {
            $this->json(409, ['success' => false, 'errors' => ['floor_level_name' => 'This floor already exists in this building.']]);
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO `floor_level` (building_id, floor_level_name) VALUES (?, ?)'
        );
        $stmt->execute([
            (int)$data['building_id'],
            $data['floor_level_name'],
        ]);

        $this->json(201, [
            'success' => true,
            'message' => 'Floor level created successfully.',
            'id'      => (int)$this->pdo->lastInsertId(),
        ]);
    }

    public function update(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) $this->json(400, ['success' => false, 'message' => 'Missing floor level id.']);

        $data   = $this->input();
        $errors = $this->validate($data);
        if ($errors) $this->json(422, ['success' => false, 'message' => 'Please fix the highlighted fields.', 'errors' => $errors]);

        if ($this->exists($data['building_id'], $data['floor_level_name'], $id)) {
            $this->json(409, ['success' => false, 'errors' => ['floor_level_name' => 'This floor already exists in this building.']]);
        }

        $stmt = $this->pdo->prepare(
            'UPDATE `floor_level` SET building_id = ?, floor_level_name = ? WHERE floor_level_id = ?'
        );
        $stmt->execute([
            (int)$data['building_id'],
            $data['floor_level_name'],
            $id,
        ]);

        $this->json(200, ['success' => true, 'message' => 'Floor level updated successfully.']);
    }

    public function delete(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) $this->json(400, ['success' => false, 'message' => 'Missing floor level id.']);

        $stmt = $this->pdo->prepare('SELECT floor_level_id FROM `floor_level` WHERE floor_level_id = ? LIMIT 1');
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            $this->json(404, ['success' => false, 'message' => 'Floor level not found.']);
        }

        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM `room` WHERE floor_level_id = ?');
        $stmt->execute([$id]);
        $refCount = (int)$stmt->fetchColumn();

        if ($refCount > 0) {
            $this->json(409, [
                'success'    => false,
                'message'    => 'This item is currently being used and cannot be deleted.',
                'references' => $refCount,
            ]);
        }

        $stmt = $this->pdo->prepare('DELETE FROM `floor_level` WHERE floor_level_id = ?');
        $stmt->execute([$id]);

        $this->json(200, ['success' => true, 'message' => 'Floor level deleted.']);
    }

    private function input(): array
    {
        $raw = file_get_contents('php://input');
        return json_decode($raw, true) ?: $_POST;
    }

    private function validate(array $data): array
    {
        $errors = [];

        $buildingId = (int)($data['building_id'] ?? 0);
        if ($buildingId <= 0) {
            $errors['building_id'] = 'Please select a building.';
        } else {
            $stmt = $this->pdo->prepare('SELECT building_id FROM `building` WHERE building_id = ? LIMIT 1');
            $stmt->execute([$buildingId]);
            if (!$stmt->fetch()) {
                $errors['building_id'] = 'Selected building is not valid.';
            }
        }

        $name = trim((string)($data['floor_level_name'] ?? ''));
        if ($name === '') {
            $errors['floor_level_name'] = 'Floor level name is required.';
        } elseif (strlen($name) > 255) {
            $errors['floor_level_name'] = 'Floor level name must be 255 characters or fewer.';
        }

        return $errors;
    }

    private function exists(int $buildingId, string $name, ?int $ignoreId = null): bool
    {
        $sql  = 'SELECT floor_level_id FROM `floor_level` WHERE building_id = ? AND floor_level_name = ?';
        $args = [$buildingId, $name];
        if ($ignoreId !== null) {
            $sql .= ' AND floor_level_id <> ?';
            $args[] = $ignoreId;
        }
        $sql .= ' LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
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