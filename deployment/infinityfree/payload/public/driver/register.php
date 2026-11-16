<?php

declare(strict_types=1);

/**
 * Mwanjelwa Kiganjani - Driver Registration Page
 *
 * Entry point for http://localhost/mwanjelwa-kiganjani/public/driver/register.php
 */

require_once __DIR__ . '/../../app/Controllers/DriverAuthController.php';

$controller = new App\Controllers\DriverAuthController();
echo $controller->register();
