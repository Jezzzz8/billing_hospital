<?php
require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/../../controllers/RoleController.php';
(new RoleController($pdo))->create();