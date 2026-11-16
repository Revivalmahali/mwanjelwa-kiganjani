<?php

declare(strict_types=1);

use App\Controllers\AdminController;

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../app/Services/Database.php';
require_once __DIR__ . '/../../app/Services/Auth.php';
require_once __DIR__ . '/../../app/Services/Csrf.php';
require_once __DIR__ . '/../../app/Services/DriverAdminManagement.php';
require_once __DIR__ . '/../../app/Controllers/AdminController.php';

$controller = new AdminController();
$controller->driver();
