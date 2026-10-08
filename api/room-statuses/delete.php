<?php
require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/../../controllers/RoomStatusController.php';
(new RoomStatusController($pdo))->delete();