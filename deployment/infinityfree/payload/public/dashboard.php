<?php

declare(strict_types=1);

use App\Services\Auth;
use App\Services\DriverAuth;

/**
 * Mwanjelwa Kiganjani - Dashboard (Protected Placeholder Page)
 *
 * Entry point for http://localhost/mwanjelwa-kiganjani/public/dashboard.php
 *
 * This page is accessible only to authenticated users. It shows the
 * logged-in user's name and their assigned roles.
 *
 * No delivery business functionality is included in this version.
 */

// Load the global configuration (env(), DatabaseConfig), the URL helper,
// and the Database service.
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/Services/Database.php';
require_once __DIR__ . '/../app/Services/Auth.php';
require_once __DIR__ . '/../app/Services/DriverAuth.php';

// Load the authentication controller (its class is only needed for
// type-safety, but the script is not routed through the router).
require_once __DIR__ . '/../app/Controllers/AuthController.php';

// Start the session if it has not been started yet.
// Logout calls session_start() itself before leaving this script.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ----- Authentication check -----
// If the user is not logged in, redirect them to the login page and
// remember that they wanted to reach the dashboard.
Auth::requireLogin(app_url('dashboard.php'));

// Retrieve the authenticated user's data from the session.
$userId = (int) $_SESSION['user_id'];
$fullName = $_SESSION['full_name'] ?? 'Unknown User';
$roles = Auth::roles();
$driverState = DriverAuth::state();
$showDriverDashboard = $driverState['allowed'];

// Convert role names to a readable list.
$roleList = !empty($roles) ? implode(', ', $roles) : 'No roles assigned';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Mwanjelwa Kiganjani</title>
    <style>
        :root {
            --color-primary: #2563eb;
            --color-primary-hover: #1d4ed8;
            --color-success: #059669;
            --color-error: #dc2626;
            --color-text: #1e293b;
            --color-text-light: #64748b;
            --color-border: #e2e8f0;
            --color-bg: #f8fafc;
            --color-surface: #ffffff;
            --radius: 8px;
            --space: 1.5rem;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            background: var(--color-bg);
            color: var(--color-text);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .container {
            max-width: 640px;
            width: 100%;
            text-align: center;
            padding: 2rem;
            border: 1px solid var(--color-border);
            border-radius: var(--radius);
            background: var(--color-surface);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
        }

        h1 {
            font-size: 2rem;
            color: var(--color-primary);
            margin-bottom: 1rem;
        }

        .greeting {
            font-size: 1.125rem;
            color: var(--color-text);
            margin-bottom: 1.5rem;
        }

        .info-box {
            background: var(--color-bg);
            border: 1px solid var(--color-border);
            border-radius: var(--radius);
            padding: 1.25rem;
            margin-bottom: 1.5rem;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--color-border);
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .label {
            font-size: 0.875rem;
            color: var(--color-text-light);
            font-weight: 500;
        }

        .value {
            font-size: 0.9375rem;
            font-weight: 600;
            color: var(--color-text);
        }

        .role-list {
            display: inline-block;
            background: var(--color-primary);
            color: #fff;
            padding: 0.375rem 0.75rem;
            border-radius: 999px;
            font-size: 0.875rem;
            font-weight: 600;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .logout-btn {
            display: inline-block;
            background: var(--color-error);
            color: #fff;
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            font-weight: 600;
            border: none;
            border-radius: var(--radius);
            cursor: pointer;
            text-decoration: none;
            transition: background 0.15s;
            margin-top: 1rem;
        }

        .logout-btn:hover {
            background: #b91c1c;
        }

        footer {
            margin-top: 2rem;
            font-size: 0.8rem;
            color: var(--color-text-light);
        }
    </style>
    <link rel="stylesheet" href="<?php echo htmlspecialchars(app_url('assets/css/portal.css'), ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body class="generic-dashboard-body">
    <main class="container generic-dashboard-card">
        <h1>Dashboard</h1>

        <p class="greeting">
            Welcome, <span style="font-weight: 600;"><?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?></span>!
        </p>

        <div class="info-box">
            <div class="info-row">
                <span class="label">Role(s)</span>
                <span class="value">
                    <?php foreach ($roles as $role): ?>
                        <span class="role-list"><?php echo htmlspecialchars($role, ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php endforeach; ?>
                </span>
            </div>
        </div>

        <?php if ($showDriverDashboard): ?>
            <div class="info-box">
                <div class="info-row">
                    <span class="label">Driver Portal</span>
                    <span class="value">
                        <a class="role-list" href="<?php echo htmlspecialchars(app_url('driver/dashboard.php'), ENT_QUOTES, 'UTF-8'); ?>">Open Driver Dashboard</a>
                    </span>
                </div>
            </div>
        <?php endif; ?>

        <a class="logout-btn" href="<?php echo htmlspecialchars(app_url('logout.php'), ENT_QUOTES, 'UTF-8'); ?>">Log Out</a>
    </main>

    <footer>
        Mwanjelwa Kiganjani &mdash; Dashboard (Phase 1)
    </footer>
</body>
</html>
