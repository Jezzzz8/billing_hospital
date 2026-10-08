<?php


require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/../../controllers/PosController.php';

$controller = new PosController($pdo);
$controller->createSale();