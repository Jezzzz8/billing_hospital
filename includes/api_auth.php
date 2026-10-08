<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function api_json(int $status, array $payload): void
{
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

function require_api_user(): void
{
    if (empty($_SESSION['user'])) {
        api_json(401, ['success' => false, 'message' => 'Not authenticated.']);
    }
}

function require_api_role(int ...$allowedRoles): void
{
    require_api_user();

    if (!in_array((int)$_SESSION['user']['role_id'], $allowedRoles, true)) {
        api_json(403, ['success' => false, 'message' => 'Access denied.']);
    }
}

if (!function_exists('requireRole')) {
    function requireRole(array $allowedRoles): void
    {
        require_api_user();
        if (!in_array((int)$_SESSION['user']['role_id'], $allowedRoles, true)) {
            api_json(403, ['success' => false, 'message' => 'Access denied.']);
        }
    }
}

if (!function_exists('require_role_api')) {
    function require_role_api(int ...$allowedRoles): void
    {
        require_api_role(...$allowedRoles);
    }
}