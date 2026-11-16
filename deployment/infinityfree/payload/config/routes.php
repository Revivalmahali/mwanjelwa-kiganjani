<?php

declare(strict_types=1);

/**
 * Mwanjelwa Kiganjani - Route Definitions
 *
 * Map URL paths to controller actions.
 * This project does not use a mature router yet, so we use a short
 * switch statement. Add new routes here as you add pages.
 *
 * Usage:
 *   require __DIR__ . '/../config/routes.php';
 *   $router = new App\Routes\Router();
 *   $router->dispatch();
 */

namespace App\Routes;

/** @internal */
class Router
{
    private array $routes = [];

    public function __construct()
    {
        $this->registerRoutes();
    }

    /** Register every route the application serves. */
    private function registerRoutes(): void
    {
        $this->routes = [
            // ----- Auth pages ----- //
            '' => ['controller' => 'AuthController', 'action' => 'home'],
            'register.php' => ['controller' => 'AuthController', 'action' => 'register'],
            'login.php' => ['controller' => 'AuthController', 'action' => 'login'],
            'forgot_password.php' => ['controller' => 'AuthController', 'action' => 'forgotPassword'],
            'reset_password.php' => ['controller' => 'AuthController', 'action' => 'resetPassword'],

            // ----- Protected pages ----- //
            'dashboard.php' => ['controller' => 'AuthController', 'action' => 'dashboard'],

            // ----- Auth actions ----- //
            'logout.php' => ['controller' => 'AuthController', 'action' => 'logout'],

            // ----- Seller ----- //
            'seller/profile.php' => ['controller' => 'SellerController', 'action' => 'profile'],
            'seller/verify' => ['controller' => 'SellerController', 'action' => 'verify'],

            // ----- Driver ----- //
            'driver/profile.php' => ['controller' => 'DriverController', 'action' => 'profile'],
            'driver/verify' => ['controller' => 'DriverController', 'action' => 'verify'],

            // ----- Customer ----- //
            'customer/my-deliveries.php' => ['controller' => 'CustomerController', 'action' => 'myDeliveries'],

            // ----- Admin panel (placeholder) ----- //
            'admin' => ['controller' => 'AdminController', 'action' => 'dashboard'],
        ];
    }

    /**
     * Find the controller + action for the current request.
     *
     * @return array{class-string, string} [controller class, action name]
     */
    public function dispatch(): array
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $route = $this->routes[$path] ?? null;

        if ($route === null) {
            http_response_code(404);
            $controller = 'AuthController';
            $action = 'notFound';
        } else {
            $controller = $route['controller'];
            $action = $route['action'];
        }

        $controllerClass = 'App\\Controllers\\' . $controller;

        if (!class_exists($controllerClass)) {
            http_response_code(500);
            $controller = 'AuthController';
            $action = 'notFound';
        }

        return [$controllerClass, $action];
    }
}
