<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/../../includes/ws_notify.php';
require_once __DIR__ . '/../../controllers/DoctorController.php';
(new DoctorController($pdo))->update();