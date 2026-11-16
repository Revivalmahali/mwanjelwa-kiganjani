<?php

/**
 * Mwanjelwa Kiganjani - Login Page
 *
 * Entry point for http://localhost/mwanjelwa-kiganjani/public/login.php
 *
 * It boots the application, requires the authentication controller,
 * and sends the generated HTML to the browser.
 */

// Load the autoloader so the application classes are available.
require_once __DIR__ . '/../config/routes.php';

// Load the global configuration (env(), DatabaseConfig) and the
// Database service.
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Services/Database.php';

// Require the authentication controller and handle the request.
require_once __DIR__ . '/../app/Controllers/AuthController.php';

$controller = new App\Controllers\AuthController();
echo $controller->login();
?>
