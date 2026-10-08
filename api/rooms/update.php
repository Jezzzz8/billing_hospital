<?php

ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/../../includes/api_auth.php';

require_api_role(1);

require_once __DIR__ . '/../../controllers/RoomController.php';

(new RoomController($pdo))->update();