<?php
require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/../../controllers/PaymentTypeController.php';
(new PaymentTypeController($pdo))->update();