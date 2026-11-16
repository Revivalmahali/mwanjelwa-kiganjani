<?php

declare(strict_types=1);

/**
 * Mwanjelwa Kiganjani - Logout Script
 *
 * Destroys the authenticated session and redirects to the login page.
 *
 * This script has no HTML output; it only ends the session and redirects.
 */

// Start the session only if it has not already been started by the
// caller (dashboard.php does this with its own session_start() call).
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load the authentication controller (its class is only needed for
// type-safety, but the script is not routed through the router).
require_once __DIR__ . '/../app/Controllers/AuthController.php';

// Use the controller's logout method.
$controller = new App\Controllers\AuthController();
$controller->logout();
?>
