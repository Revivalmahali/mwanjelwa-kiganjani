<?php

/**
 * Mwanjelwa Kiganjani - Reset Password Page
 *
 * Entry point for http://localhost/mwanjelwa-kiganjani/public/reset_password.php
 *
 * It boots the application, requires the authentication controller,
 * and sends the generated HTML to the browser.
 */

// Load the global configuration and the Database service.
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Services/Database.php';

// Require the authentication controller and handle the request.
require_once __DIR__ . '/../app/Controllers/AuthController.php';

$controller = new App\Controllers\AuthController();
echo $controller->resetPassword();
