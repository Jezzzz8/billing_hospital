<?php

class BuildingController
{
    private PDO $pdo;

    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function getAll(): array
    {
        return $this->pdo->query(
            'SELECT b.building_id, b.building_name, b.description,
                    (SELECT COUNT(*) FROM `floor_level` fl WHERE fl.building_id = b.building_id) AS floor_count,
                    (SELECT COUNT(*) FROM `room` r
                     INNER JOIN `floor_level` fl ON fl.floor_level_id = r.floor_level_id
                     WHERE fl.building_id = b.building_id AND r.is_active = 1) AS room_count
             FROM `building` b
             ORDER BY b.building_name'
        )->fetchAll();
    }

    public function create(): void
    {
        $data   = $this->input();
        $errors = $this->validate($data);
        if ($errors) $this->json(422, ['success' => false, 'message' => 'Please fix the highlighted fields.', 'errors' => $errors]);

        if ($this->nameExists($data['building_name'])) {
            $this->json(409, ['success' => false, 'errors' => ['building_name' => 'This building name already exists.']]);
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO `building` (building_name, description) VALUES (?, ?)'
        );
        $stmt->execute([
            $data['building_name'],
            !empty($data['description']) ? $data['description'] : null,
        ]);

        $this->json(201, [
            'success' => true,
            'message' => 'Building created successfully.',
            'id'      => (int)$this->pdo->lastInsertId(),
        ]);
    }

    public function update(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) $this->json(400, ['success' => false, 'message' => 'Missing building id.']);

        $data   = $this->input();
        $errors = $this->validate($data);
        if ($errors) $this->json(422, ['success' => false, 'message' => 'Please fix the highlighted fields.', 'errors' => $errors]);

        if ($this->nameExists($data['building_name'], $id)) {
            $this->json(409, ['success' => false, 'errors' => ['building_name' => 'This building name already exists.']]);
        }

        $stmt = $this->pdo->prepare(
            'UPDATE `building` SET building_name = ?, description = ? WHERE building_id = ?'
        );
        $stmt->execute([
            $data['building_name'],
            !empty($data['description']) ? $data['description'] : null,
            $id,
        ]);

        $this->json(200, ['success' => true, 'message' => 'Building updated successfully.']);
    }

    public function delete(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) $this->json(400, ['success' => false, 'message' => 'Missing building id.']);

        $stmt = $this->pdo->prepare('SELECT building_id FROM `building` WHERE building_id = ? LIMIT 1');
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            $this->json(404, ['success' => false, 'message' => 'Building not found.']);
        }

        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM `floor_level` WHERE building_id = ?');
        $stmt->execute([$id]);
        $refCount = (int)$stmt->fetchColumn();

        if ($refCount > 0) {
            $this->json(409, [
                'success'    => false,
                'message'    => 'This item is currently being used and cannot be deleted.',
                'references' => $refCount,
            ]);
        }

        $stmt = $this->pdo->prepare('DELETE FROM `building` WHERE building_id = ?');
        $stmt->execute([$id]);

        $this->json(200, ['success' => true, 'message' => 'Building deleted.']);
    }

    private function input(): array
    {
        $raw = file_get_contents('php://input');
        return json_decode($raw, true) ?: $_POST;
    }

    private function validate(array $data): array
    {
        $errors = [];

        $name = trim((string)($data['building_name'] ?? ''));
        if ($name === '') {
            $errors['building_name'] = 'Building name is required.';
        } elseif (strlen($name) > 255) {
            $errors['building_name'] = 'Building name must be 255 characters or fewer.';
        }

        $desc = trim((string)($data['description'] ?? ''));
        if ($desc !== '' && strlen($desc) > 255) {
            $errors['description'] = 'Description must be 255 characters or fewer.';
        }

        return $errors;
    }

    private function nameExists(string $name, ?int $ignoreId = null): bool
    {
        $sql  = 'SELECT building_id FROM `building` WHERE building_name = ?';
        $args = [$name];
        if ($ignoreId !== null) {
            $sql .= ' AND building_id <> ?';
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