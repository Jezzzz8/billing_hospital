<?php

require_once __DIR__ . '/ChargeSyncService.php';

class BillingHook
{
    private static array $deferred = [];

    public static function emit(PDO $pdo, int $admissionId): void
    {
        if ($admissionId <= 0) return;

        try {
            $stmt = $pdo->prepare(
                'SELECT statement_id FROM `billing_statement`
                 WHERE admission_id = ? LIMIT 1'
            );
            $stmt->execute([$admissionId]);
            $statementId = (int)$stmt->fetchColumn();

            if ($statementId <= 0) return;

            if ($pdo->inTransaction()) {
                self::$deferred[$statementId] = $pdo;
                return;
            }

            (new ChargeSyncService($pdo))->syncStatement($statementId);
        } catch (Throwable $e) {
            error_log('[BillingHook::emit] ' . $e->getMessage());
        }
    }

    public static function flushDeferred(): void
    {
        if (empty(self::$deferred)) return;

        $queue = self::$deferred;
        self::$deferred = [];

        foreach ($queue as $statementId => $pdo) {
            try {
                (new ChargeSyncService($pdo))->syncStatement($statementId);
            } catch (Throwable $e) {
                error_log('[BillingHook::flushDeferred] statement ' . $statementId . ': ' . $e->getMessage());
            }
        }
    }
}