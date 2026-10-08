<?php
require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/../../controllers/ChargeItemController.php';
(new ChargeItemController($pdo))->update();