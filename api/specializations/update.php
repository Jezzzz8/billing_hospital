<?php
require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/../../controllers/SpecializationController.php';
(new SpecializationController($pdo))->update();