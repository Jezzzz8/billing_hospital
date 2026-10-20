<?php

require_once __DIR__ . '/../includes/ws_notify.php';

class PharmacyStockController
{
    private const MAX_STOCK_QUANTITY = 4294967295;

    private PDO $pdo;
    private int $departmentId;

    public function __construct(PDO $pdo, int $departmentId)
    {
        $this->pdo = $pdo;
        $this->departmentId = $departmentId;
        $this->assertPharmacyDepartment();
    }

    private function assertPharmacyDepartment(): void
    {
        $stmt = $this->pdo->prepare(
            'SELECT department_code FROM `department` WHERE department_id = ? LIMIT 1'
        );
        $stmt->execute([$this->departmentId]);
        if ($stmt->fetchColumn() !== 'PHARM') {
            throw new RuntimeException('Pharmacy stock is available only to Pharmacy department accounts.', 403);
        }
    }

    public function getStockItems(): array
    {
        return $this->pdo->query(
            'SELECT ci.charge_item_id, ci.item_code, ci.item_name, ci.unit_of_measure,
                    ci.stock_quantity, cc.category_name
             FROM `charge_item` ci
             INNER JOIN `charge_category` cc ON cc.category_id = ci.category_id
             WHERE ci.is_stockable = 1 AND ci.is_active = 1
             ORDER BY cc.category_name, ci.item_name'
        )->fetchAll();
    }

    public function adjustStock(array $data): array
    {
        $itemId = filter_var($data['charge_item_id'] ?? null, FILTER_VALIDATE_INT);
        $adjustment = filter_var($data['adjustment'] ?? null, FILTER_VALIDATE_INT);
        if ($itemId === false || $itemId <= 0 || $adjustment === false || $adjustment === 0) {
            throw new RuntimeException('Select a stock item and enter a non-zero whole-number adjustment.', 422);
        }

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(
                'SELECT stock_quantity, item_name
                 FROM `charge_item`
                 WHERE charge_item_id = ? AND is_stockable = 1 AND is_active = 1
                 LIMIT 1 FOR UPDATE'
            );
            $stmt->execute([$itemId]);
            $item = $stmt->fetch();
            if (!$item) {
                throw new RuntimeException('Active stock item not found.', 404);
            }

            $newQuantity = (int)$item['stock_quantity'] + $adjustment;
            if ($newQuantity < 0) {
                throw new RuntimeException('Stock adjustment cannot reduce the available quantity below zero.', 422);
            }
            if ($newQuantity > self::MAX_STOCK_QUANTITY) {
                throw new RuntimeException('Stock quantity exceeds the supported maximum.', 422);
            }

            $stmt = $this->pdo->prepare(
                'UPDATE `charge_item` SET stock_quantity = ? WHERE charge_item_id = ? AND is_stockable = 1'
            );
            $stmt->execute([$newQuantity, $itemId]);
            $this->pdo->commit();

            ws_broadcast('pharmacy.stock.updated', [
                'charge_item_id' => $itemId,
                'stock_quantity' => $newQuantity,
            ], [1, 5], $this->departmentId);

            return [
                'success' => true,
                'message' => 'Stock updated for ' . $item['item_name'] . '.',
                'charge_item_id' => $itemId,
                'stock_quantity' => $newQuantity,
            ];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }
}
