<?php
require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/../../controllers/GenderController.php';
(new GenderController($pdo))->create();