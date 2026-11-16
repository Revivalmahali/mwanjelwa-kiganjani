<?php

declare(strict_types=1);

use App\Controllers\DriverController;

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../app/Services/Database.php';
require_once __DIR__ . '/../../app/Services/Auth.php';
require_once __DIR__ . '/../../app/Services/DriverAuth.php';
require_once __DIR__ . '/../../app/Services/DriverDashboard.php';
require_once __DIR__ . '/../../app/Services/DriverDeliveryManagement.php';
require_once __DIR__ . '/../../app/Controllers/DriverController.php';

$controller = new DriverController();
$controller->history();
