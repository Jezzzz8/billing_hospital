<?php

class RoomTypeController
{
    private PDO $pdo;

    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function getAll(): array
    {
        return $this->pdo->query(
            'SELECT room_type_id, room_type_name, description, rate_per_day,
                    capacity, created_at,
                    (SELECT COUNT(*) FROM `room` r WHERE r.room_type_id = rt.room_type_id AND r.is_active = 1) AS room_count
             FROM `room_type` rt
             ORDER BY room_type_id'
        )->fetchAll();
    }

    public function create(): void
    {
        $data   = $this->input();
        $errors = $this->validate($data);
        if ($errors) $this->json(422, ['success' => false, 'message' => 'Please fix the highlighted fields.', 'errors' => $errors]);

        if ($this->nameExists($data['room_type_name'])) {
            $this->json(409, ['success' => false, 'errors' => ['room_type_name' => 'This room type name already exists.']]);
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO `room_type`
                (room_type_name, description, rate_per_day, capacity)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['room_type_name'],
            !empty($data['description']) ? $data['description'] : null,
            (float)$data['rate_per_day'],
            (int)$data['capacity'],
        ]);

        $this->json(201, [
            'success' => true,
            'message' => 'Room type created successfully.',
            'id'      => (int)$this->pdo->lastInsertId(),
        ]);
    }

    public function update(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) $this->json(400, ['success' => false, 'message' => 'Missing room type id.']);

        $data   = $this->input();
        $errors = $this->validate($data);
        if ($errors) $this->json(422, ['success' => false, 'message' => 'Please fix the highlighted fields.', 'errors' => $errors]);

        if ($this->nameExists($data['room_type_name'], $id)) {
            $this->json(409, ['success' => false, 'errors' => ['room_type_name' => 'This room type name already exists.']]);
        }

        $stmt = $this->pdo->prepare(
            'UPDATE `room_type`
             SET room_type_name = ?, description = ?, rate_per_day = ?, capacity = ?
             WHERE room_type_id = ?'
        );
        $stmt->execute([
            $data['room_type_name'],
            !empty($data['description']) ? $data['description'] : null,
            (float)$data['rate_per_day'],
            (int)$data['capacity'],
            $id,
        ]);

        $this->json(200, ['success' => true, 'message' => 'Room type updated successfully.']);
    }

    public function delete(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) $this->json(400, ['success' => false, 'message' => 'Missing room type id.']);

        $stmt = $this->pdo->prepare('SELECT room_type_id FROM `room_type` WHERE room_type_id = ? LIMIT 1');
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            $this->json(404, ['success' => false, 'message' => 'Room type not found.']);
        }

        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM `room` WHERE room_type_id = ?');
        $stmt->execute([$id]);
        $refCount = (int)$stmt->fetchColumn();

        if ($refCount > 0) {
            $this->json(409, [
                'success'    => false,
                'message'    => 'This item is currently being used and cannot be deleted.',
                'references' => $refCount,
            ]);
        }

        $stmt = $this->pdo->prepare('DELETE FROM `room_type` WHERE room_type_id = ?');
        $stmt->execute([$id]);

        $this->json(200, ['success' => true, 'message' => 'Room type deleted.']);
    }

    private function input(): array
    {
        $raw = file_get_contents('php://input');
        return json_decode($raw, true) ?: $_POST;
    }

    private function validate(array $data): array
    {
        $errors = [];

        $name = trim((string)($data['room_type_name'] ?? ''));
        if ($name === '') {
            $errors['room_type_name'] = 'Room type name is required.';
        } elseif (strlen($name) > 100) {
            $errors['room_type_name'] = 'Room type name must be 100 characters or fewer.';
        }

        $rate = $data['rate_per_day'] ?? '';
        if ($rate === '' || !is_numeric($rate) || (float)$rate < 0) {
            $errors['rate_per_day'] = 'Enter a valid rate per day (0 or higher).';
        }

        $capacity = $data['capacity'] ?? '';
        if ($capacity === '' || !is_numeric($capacity) || (int)$capacity < 1) {
            $errors['capacity'] = 'Capacity must be at least 1.';
        }

        return $errors;
    }

    private function nameExists(string $name, ?int $ignoreId = null): bool
    {
        $sql  = 'SELECT room_type_id FROM `room_type` WHERE room_type_name = ?';
        $args = [$name];
        if ($ignoreId !== null) {
            $sql .= ' AND room_type_id <> ?';
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