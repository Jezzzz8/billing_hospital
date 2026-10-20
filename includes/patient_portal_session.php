<?php

require_once __DIR__ . '/../config/config.php';

ini_set('session.gc_maxlifetime', '300');
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');

session_name('billing_patient_portal');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => BASE_URL . '/',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax',
]);

session_start();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$now = time();
if (isset($_SESSION['patient_portal']['patient_id'])) {
    $lastActivity = (int)($_SESSION['patient_portal']['last_activity'] ?? 0);
    if ($lastActivity <= 0 || $now - $lastActivity >= 300) {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            setcookie(session_name(), '', [
                'expires' => $now - 42000,
                'path' => BASE_URL . '/',
                'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }
        session_destroy();
        session_start();
        $_SESSION['patient_portal_expired'] = true;
    } else {
        $_SESSION['patient_portal']['last_activity'] = $now;
    }
}
