<?php
require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/../../controllers/BillingStatusController.php';
(new BillingStatusController($pdo))->create();