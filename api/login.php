<?php


require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../controllers/AuthController.php';

$controller = new AuthController($pdo);
$controller->login();