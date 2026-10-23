<?php

class AuthController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function login(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(405, ['success' => false, 'message' => 'Method not allowed.']);
        }

        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true) ?: $_POST;

        $email    = trim((string)($data['email'] ?? $data['username'] ?? ''));
        $password = (string)($data['password'] ?? '');
        $expectedRoleId = filter_var(
            $data['expected_role_id'] ?? $_GET['expected_role_id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1, 'max_range' => 5]]
        );

        if ($email === '' || $password === '' || $expectedRoleId === false) {
            $this->json(422, ['success' => false, 'message' => 'Choose a staff login and enter your email and password.']);
        }

        $sql = 'SELECT u.user_id, u.username, u.password_hash, u.first_name, u.last_name,
                       u.email, u.department_id, u.is_active, r.role_id, r.role_name
                FROM `user` u
                INNER JOIN `role` r ON r.role_id = u.role_id
                WHERE u.email = ?
                LIMIT 1';

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$email]);
            $user = $stmt->fetch();
        } catch (Throwable $e) {
            $this->json(500, ['success' => false, 'message' => 'Database error.']);
        }

        if (!$user) {
            $this->json(401, ['success' => false, 'message' => 'Invalid email or password.']);
        }

        $verified = password_verify($password, $user['password_hash']);

        if (!$verified) {
            $this->json(401, ['success' => false, 'message' => 'Invalid email or password.']);
        }

        if ((int)$user['is_active'] !== 1) {
            $this->json(403, ['success' => false, 'message' => 'Account is inactive. Contact an administrator.']);
        }

        if ((int)$user['role_id'] !== $expectedRoleId) {
            $this->json(403, ['success' => false, 'message' => 'These credentials do not belong to this staff login.']);
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_regenerate_id(true);

        $_SESSION['user'] = [
            'user_id'      => (int)$user['user_id'],
            'username'     => $user['username'],
            'email'        => $user['email'],
            'first_name'   => $user['first_name'],
            'last_name'    => $user['last_name'],
            'role_id'      => (int)$user['role_id'],
            'role_name'    => $user['role_name'],
            'department_id' => isset($user['department_id']) && $user['department_id'] !== null
                ? (int)$user['department_id']
                : null,
        ];

        $this->json(200, [
            'success'  => true,
            'message'  => 'Login successful.',
            'user'     => [
                'user_id'    => (int)$user['user_id'],
                'first_name' => $user['first_name'],
                'last_name'  => $user['last_name'],
                'role_name'  => $user['role_name'],
            ],
            'redirect' => $this->redirectForRole((int)$user['role_id']),
        ]);
    }

    private function redirectForRole(int $roleId): string
    {
        return match ($roleId) {
            1 => '/billing_hospital/index.php?page=dashboard',
            2 => '/billing_hospital/index.php?page=doctor',
            3 => '/billing_hospital/index.php?page=nurse',
            4 => '/billing_hospital/index.php?page=cashier',
            5 => '/billing_hospital/index.php?page=department',
            default => '/billing_hospital/index.php',
        };
    }

    private function json(int $status, array $payload): void
    {
        http_response_code($status);
        echo json_encode($payload);
        exit;
    }
}