<?php

declare(strict_types=1);

/**
 * Mwanjelwa Kiganjani - Registration Page
 *
 * Entry point for http://localhost/mwanjelwa-kiganjani/public/register.php
 *
 * It bootstraps the application, requires the registration controller,
 * and sends the generated HTML to the browser.
 */

// Require the controller (the project root is one level above public/).
require_once __DIR__ . '/../app/Controllers/AuthController.php';

// Handle the request and capture the generated HTML.
$controller = new App\Controllers\AuthController();
$html = $controller->register();

// Send the response to the browser.
echo $html;
