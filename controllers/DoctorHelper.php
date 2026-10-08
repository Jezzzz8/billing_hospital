<?php

class DoctorHelper
{
    public static function getCurrentDoctorId(PDO $pdo): ?int
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (empty($_SESSION['user'])) return null;

        $userId = (int)$_SESSION['user']['user_id'];
        $stmt = $pdo->prepare('SELECT doctor_id FROM `doctor` WHERE user_id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $id = $stmt->fetchColumn();

        return $id !== false ? (int)$id : null;
    }
}