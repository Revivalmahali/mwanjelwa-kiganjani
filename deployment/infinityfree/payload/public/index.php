<?php

declare(strict_types=1);

/**
 * Mwanjelwa Kiganjani - Project Entry Point
 *
 * This is the minimal PHP entry point for the application.
 * It loads the configuration, resolves the current route to a controller
 * and an action, and renders the resulting view.
 */

declare(strict_types=1);

// Load the configuration (env(), configDatabase(), DatabaseConfig) and the
// Database service.
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Services/Database.php';

// Load the route definitions. The router maps the current URL to a
// controller class and an action method.
require_once __DIR__ . '/../config/routes.php';

// Minimal PSR-4 style autoloader. This project does not use Composer, so
// the application registers its own small autoloader to map its namespaces
// to their file locations.
spl_autoload_register(static function (string $className): void {
    // Project root is one directory above the public/ folder.
    static $projectRoot = null;
    if ($projectRoot === null) {
        $projectRoot = realpath(__DIR__ . '/..');
    }

    $prefixes = [
        'App\\Controllers' => $projectRoot . '/app/Controllers',
        'App\\Services'     => $projectRoot . '/app/Services',
        'App\\Routes'       => $projectRoot . '/config',
        'App'               => $projectRoot . '/app',
        'Config'            => $projectRoot . '/config',
    ];

    foreach ($prefixes as $prefix => $baseDir) {
        if (str_starts_with($className, $prefix)) {
            $relativeClass = substr($className, strlen($prefix));
            // Convert namespace separators to directory separators.
            $filePath = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

            if (is_file($filePath)) {
                require_once $filePath;
                return;
            }
        }
    }
});

use App\Routes\Router;

$router = new Router();
list($controllerClass, $action) = $router->dispatch();

error_log('public/index.php: controllerClass=' . $controllerClass . ', action=' . $action);

$controller = new $controllerClass();

// If the route was not found, render the home page instead of the 404 page.
// The home page is the application entry point and shows Login/Register links.
if ($action === 'notFound') {
    error_log('public/index.php: notFound detected, rendering home');
    http_response_code(200);
    echo $controller->home();
    exit;
}

// getenv() returns false when a variable is not set (never null), so use
// a strict !== false check instead of the ?? operator, which returns false
// instead of the default value. Cast to string so the output is always
// a valid string for htmlspecialchars().
$appName = getenv('APP_NAME') === false ? 'Mwanjelwa Kiganjani' : (string) getenv('APP_NAME');
$appStatus = getenv('APP_STATUS') === false ? 'Foundation Initialized' : (string) getenv('APP_STATUS');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($appName, ENT_QUOTES, 'UTF-8'); ?></title>
    <style>
        :root {
            --color-primary: #2563eb;
            --color-text: #1e293b;
            --color-bg: #f8fafc;
            --color-surface: #ffffff;
            --color-border: #e2e8f0;
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
            max-width: 720px;
            width: 100%;
            text-align: center;
            padding: 2rem;
            border: 1px solid var(--color-border);
            border-radius: var(--radius);
            background: var(--color-surface);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
        }

        h1 {
            font-size: 2.25rem;
            color: var(--color-primary);
            margin-bottom: 0.5rem;
        }

        .status {
            display: inline-block;
            font-size: 0.875rem;
            font-weight: 600;
            color: #fff;
            background: var(--color-primary);
            padding: 0.375rem 0.75rem;
            border-radius: 999px;
            margin-bottom: 1.25rem;
        }

        .message {
            color: #475569;
            margin-bottom: 1.5rem;
        }

        .meta {
            font-size: 0.875rem;
            color: #64748b;
        }

        footer {
            margin-top: 2rem;
            font-size: 0.8rem;
            color: #94a3b8;
        }
    </style>
    <link rel="stylesheet" href="<?php echo htmlspecialchars(app_url('assets/css/portal.css'), ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body class="legacy-home-body">
    <main class="container legacy-home">
        <div class="legacy-home-copy">
            <a class="auth-brand legacy-home-brand" href="<?php echo htmlspecialchars(app_url('index.php'), ENT_QUOTES, 'UTF-8'); ?>"><span class="auth-brand-mark" aria-hidden="true">MK</span><span><?php echo htmlspecialchars($appName, ENT_QUOTES, 'UTF-8'); ?></span></a>
            <span class="legacy-home-status"><?php echo htmlspecialchars($appStatus, ENT_QUOTES, 'UTF-8'); ?></span>
            <h1>Local deliveries, made simple.</h1>
            <p>Connecting people, businesses, and trusted drivers across Mbeya with care from pickup to handover.</p>
            <div class="legacy-home-actions">
                <a class="submit-btn" href="<?php echo htmlspecialchars(app_url('login.php'), ENT_QUOTES, 'UTF-8'); ?>">Log in</a>
                <a class="submit-btn btn--outline" href="<?php echo htmlspecialchars(app_url('register.php'), ENT_QUOTES, 'UTF-8'); ?>">Create an account</a>
            </div>
            <p class="legacy-home-driver">Want to drive with us? <a class="link" href="<?php echo htmlspecialchars(app_url('driver/register.php'), ENT_QUOTES, 'UTF-8'); ?>">Register as a driver</a></p>
        </div>
        <div class="legacy-home-art">
            <img src="<?php echo htmlspecialchars(app_url('assets/images/delivery-hero.svg'), ENT_QUOTES, 'UTF-8'); ?>" alt="A local motorcycle courier carrying a parcel">
            <p>Local service. Careful handover. Delivered with confidence.</p>
        </div>
    </main>
</body>
</html>
