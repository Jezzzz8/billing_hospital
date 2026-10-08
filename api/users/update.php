<?php
require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/../../controllers/UserController.php';
(new UserController($pdo))->update();