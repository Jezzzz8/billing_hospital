<?php
require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/../../controllers/AdmissionStatusController.php';
(new AdmissionStatusController($pdo))->delete();