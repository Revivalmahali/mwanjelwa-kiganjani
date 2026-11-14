<?php

declare(strict_types=1);

/**
 * Mwanjelwa Kiganjani - Application URL Helper
 *
 * Builds URLs relative to the public/ directory. This keeps redirects and
 * links correct even when the project is served from a subdirectory such as
 * /mwanjelwa-kiganjani/public/, and it also works from nested folders such
 * as public/admin/.
 */

/**
 * Build an application URL from a path inside the public/ directory.
 *
 * Examples (when the app is at http://localhost/mwanjelwa-kiganjani/public):
 *   app_url('dashboard.php') => /mwanjelwa-kiganjani/public/dashboard.php
 *   app_url('login.php')     => /mwanjelwa-kiganjani/public/login.php
 *
 * If public/ is the Apache document root, the result is simply
 * /dashboard.php, /login.php, and so on.
 */
function app_url(string $path = ''): string
{
    $publicDir = realpath(__DIR__ . '/../public');
    $documentRoot = isset($_SERVER['DOCUMENT_ROOT'])
        ? realpath($_SERVER['DOCUMENT_ROOT'])
        : false;

    if ($publicDir !== false && $documentRoot !== false) {
        $publicDir = str_replace('\\', '/', $publicDir);
        $documentRoot = str_replace('\\', '/', $documentRoot);

        if (str_starts_with($publicDir, $documentRoot)) {
            $basePath = substr($publicDir, strlen($documentRoot));
        } else {
            $basePath = '';
        }
    } else {
        // Fallback for CLI or unusual server setups.
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $basePath = str_replace('\\', '/', dirname($scriptName));

        if ($basePath === '/' || $basePath === '.') {
            $basePath = '';
        }
    }

    $basePath = rtrim($basePath, '/');

    return $basePath . '/' . ltrim($path, '/');
}
