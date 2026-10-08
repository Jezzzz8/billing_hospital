<?php

class UserController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(): void
    {
        $data = $this->input();
        $errors = $this->validate($data, null);
        if ($errors) {
            $this->json(422, ['success' => false, 'message' => 'Please fix the highlighted fields.', 'errors' => $errors]);
        }

        if ($this->usernameExists($data['username'])) {
            $this->json(409, ['success' => false, 'errors' => ['username' => 'This username is already in use.']]);
        }
        if ($this->emailExists($data['email'])) {
            $this->json(409, ['success' => false, 'errors' => ['email' => 'This email is already registered.']]);
        }

        $hash = password_hash((string)$data['password'], PASSWORD_BCRYPT);
        $departmentId = !empty($data['department_id']) ? (int)$data['department_id'] : null;

        $stmt = $this->pdo->prepare(
            'INSERT INTO `user`
                (role_id, department_id, username, password_hash, first_name, last_name, email, contact_number, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)'
        );
        $stmt->execute([
            (int)$data['role_id'],
            $departmentId,
            $data['username'],
            $hash,
            $data['first_name'],
            $data['last_name'],
            $data['email'],
            !empty($data['contact_number']) ? $data['contact_number'] : null,
        ]);

        $this->json(201, [
            'success' => true,
            'message' => 'User created successfully.',
            'id'      => (int)$this->pdo->lastInsertId(),
        ]);
    }

    public function update(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) $this->json(400, ['success' => false, 'message' => 'Missing user id.']);

        $data = $this->input();
        $errors = $this->validate($data, $id, false);
        if ($errors) {
            $this->json(422, ['success' => false, 'message' => 'Please fix the highlighted fields.', 'errors' => $errors]);
        }

        if ($this->usernameExists($data['username'], $id)) {
            $this->json(409, ['success' => false, 'errors' => ['username' => 'This username is already in use.']]);
        }
        if ($this->emailExists($data['email'], $id)) {
            $this->json(409, ['success' => false, 'errors' => ['email' => 'This email is already registered.']]);
        }

        $departmentId = !empty($data['department_id']) ? (int)$data['department_id'] : null;

        $stmt = $this->pdo->prepare(
            'UPDATE `user`
             SET role_id = ?, department_id = ?, username = ?, first_name = ?,
                 last_name = ?, email = ?, contact_number = ?
             WHERE user_id = ?'
        );
        $stmt->execute([
            (int)$data['role_id'],
            $departmentId,
            $data['username'],
            $data['first_name'],
            $data['last_name'],
            $data['email'],
            !empty($data['contact_number']) ? $data['contact_number'] : null,
            $id,
        ]);

        $this->json(200, ['success' => true, 'message' => 'User updated successfully.']);
    }

    public function changePassword(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) $this->json(400, ['success' => false, 'message' => 'Missing user id.']);

        $data     = $this->input();
        $password = (string)($data['password'] ?? '');
        $confirm  = (string)($data['password_confirm'] ?? '');

        $errors = [];
        if ($password === '') {
            $errors['password'] = 'Password is required.';
        } elseif (strlen($password) < 6) {
            $errors['password'] = 'Password must be at least 6 characters.';
        }
        if ($confirm === '') {
            $errors['password_confirm'] = 'Please confirm the password.';
        } elseif ($password !== $confirm) {
            $errors['password_confirm'] = 'Passwords do not match.';
        }

        if ($errors) {
            $this->json(422, ['success' => false, 'message' => 'Please fix the highlighted fields.', 'errors' => $errors]);
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->pdo->prepare('UPDATE `user` SET password_hash = ? WHERE user_id = ?');
        $stmt->execute([$hash, $id]);

        $this->json(200, ['success' => true, 'message' => 'Password updated successfully.']);
    }

    public function toggleActive(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) $this->json(400, ['success' => false, 'message' => 'Missing user id.']);

        if ($id === (int)$_SESSION['user']['user_id']) {
            $this->json(409, ['success' => false, 'message' => 'You cannot archive your own account.']);
        }

        $stmt = $this->pdo->prepare('SELECT is_active FROM `user` WHERE user_id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) $this->json(404, ['success' => false, 'message' => 'User not found.']);

        $newState = ((int)$row['is_active'] === 1) ? 0 : 1;

        $stmt = $this->pdo->prepare('UPDATE `user` SET is_active = ? WHERE user_id = ?');
        $stmt->execute([$newState, $id]);

        $this->json(200, [
            'success' => true,
            'message' => $newState === 1 ? 'User reactivated.' : 'User archived.',
            'is_active' => $newState,
        ]);
    }

    private function input(): array
    {
        $raw = file_get_contents('php://input');
        return json_decode($raw, true) ?: $_POST;
    }

    private function validate(array $data, ?int $ignoreId = null, bool $requirePassword = true): array
    {
        $errors = [];

        if (trim((string)($data['first_name'] ?? '')) === '') $errors['first_name'] = 'First name is required.';
        if (trim((string)($data['last_name']  ?? '')) === '') $errors['last_name']  = 'Last name is required.';

        $username = trim((string)($data['username'] ?? ''));
        if ($username === '') {
            $errors['username'] = 'Username is required.';
        } elseif (!preg_match('/^[a-zA-Z0-9._-]{3,50}$/', $username)) {
            $errors['username'] = 'Username must be 3–50 chars (letters, digits, . _ -).';
        }

        $email = trim((string)($data['email'] ?? ''));
        if ($email === '') {
            $errors['email'] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email is not valid.';
        }

        $roleId = (int)($data['role_id'] ?? 0);
        if (!in_array($roleId, [1, 2, 3, 4, 5], true)) {
            $errors['role_id'] = 'Please select a valid role.';
        }

        if ($roleId === 5) {
            $departmentId = (int)($data['department_id'] ?? 0);
            if ($departmentId <= 0) {
                $errors['department_id'] = 'Department staff must be assigned to a department.';
            }
        }

        if ($requirePassword) {
            $password = (string)($data['password'] ?? '');
            if ($password === '') {
                $errors['password'] = 'Password is required.';
            } elseif (strlen($password) < 6) {
                $errors['password'] = 'Password must be at least 6 characters.';
            }
        }

        return $errors;
    }

    private function usernameExists(string $username, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT user_id FROM `user` WHERE username = ?';
        $args = [$username];
        if ($ignoreId !== null) {
            $sql .= ' AND user_id <> ?';
            $args[] = $ignoreId;
        }
        $stmt = $this->pdo->prepare($sql . ' LIMIT 1');
        $stmt->execute($args);
        return (bool)$stmt->fetch();
    }

    private function emailExists(string $email, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT user_id FROM `user` WHERE email = ?';
        $args = [$email];
        if ($ignoreId !== null) {
            $sql .= ' AND user_id <> ?';
            $args[] = $ignoreId;
        }
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