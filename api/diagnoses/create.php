<?php
require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/../../controllers/DiagnosisController.php';
(new DiagnosisController($pdo))->create();