<?php
/**
 * Mwanjelwa Kiganjani - Root Redirect
 *
 * Redirects all requests from the project root to the public/ directory,
 * where the application entry point lives.
 */

// Use the host from the request to build an absolute URL.
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptName = $_SERVER['PHP_SELF'] ?? '/index.php';
// The script is at /mwanjelwa-kiganjani/index.php, so the project root
// is the parent directory of public/.
$projectRoot = rtrim(dirname($scriptName), '/') . '/';

// Redirect to the public directory of the project.
$redirectUrl = 'http://' . $host . str_replace('index.php', 'public/', $scriptName);

header('Location: ' . $redirectUrl, true, 301);
exit;
