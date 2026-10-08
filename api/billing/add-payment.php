<?php
require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/../../controllers/BillingController.php';
(new BillingController($pdo))->addPayment();