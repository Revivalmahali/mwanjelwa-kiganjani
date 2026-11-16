<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\Csrf;
use App\Services\Database;
use App\Services\DriverAuth;
use App\Services\DriverDashboard;
use App\Services\DriverDeliveryManagement;
use Throwable;

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../Services/Database.php';
require_once __DIR__ . '/../Services/Auth.php';
require_once __DIR__ . '/../Services/Csrf.php';
require_once __DIR__ . '/../Services/DriverAuth.php';
require_once __DIR__ . '/../Services/DriverDashboard.php';
require_once __DIR__ . '/../Services/DriverDeliveryManagement.php';

class DriverController
{
    public function dashboard(): void
    {
        $path = 'driver/dashboard.php';
        $driver = $this->loadDriver($path);
        $viewData = [];

        try {
            $deliveries = new DriverDeliveryManagement(Database::connect());
            $viewData['activeDelivery'] = $deliveries->findActiveDelivery(
                (int) ($driver['driver_profile_id'] ?? 0)
            );
            $viewData['availableDeliveryCount'] = $deliveries->countAvailableDeliveries();
        } catch (Throwable $e) {
            $this->logError($e);
            $this->abort(500, 'Your delivery activity could not be loaded. Please try again.');
        }

        $this->renderPage(
            'Driver Dashboard',
            'Dashboard',
            'dashboard.php',
            $path,
            $viewData,
            $driver
        );
    }

    public function profile(): void
    {
        $this->renderPage(
            'My Profile',
            'My Profile',
            'profile.php',
            'driver/profile.php'
        );
    }

    public function availableDeliveries(): void
    {
        $path = 'driver/available-deliveries.php';
        $driver = $this->loadDriver($path);
        $viewData = [];

        try {
            $deliveries = new DriverDeliveryManagement(Database::connect());
            $viewData['availableDeliveries'] = $deliveries->listAvailableDeliveries();
        } catch (Throwable $e) {
            $this->logError($e);
            $this->abort(500, 'Available deliveries could not be loaded. Please try again.');
        }

        $this->renderPage(
            'Available Deliveries',
            'Available Deliveries',
            'available_deliveries.php',
            $path,
            $viewData,
            $driver
        );
    }

    public function activeDelivery(): void
    {
        $path = 'driver/active-delivery.php';
        $driver = $this->loadDriver($path);
        $viewData = [];

        try {
            $deliveries = new DriverDeliveryManagement(Database::connect());
            $viewData['activeDelivery'] = $deliveries->findActiveDelivery(
                (int) ($driver['driver_profile_id'] ?? 0)
            );
        } catch (Throwable $e) {
            $this->logError($e);
            $this->abort(500, 'Your active delivery could not be loaded. Please try again.');
        }

        $this->renderPage(
            'My Active Delivery',
            'My Active Delivery',
            'active_delivery.php',
            $path,
            $viewData,
            $driver
        );
    }

    public function acceptDelivery(): void
    {
        $this->loadDriver('driver/available-deliveries.php');

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->redirectToAvailableDeliveries();
        }

        $token = isset($_POST['_csrf_token']) ? (string) $_POST['_csrf_token'] : null;

        if (!Csrf::verify($token)) {
            $this->abort(403, 'Invalid security token. Please go back and try again.');
        }

        $deliveryId = filter_var(
            $_POST['delivery_id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($deliveryId === false) {
            $this->setFlash('error', 'Choose a valid delivery before trying to accept it.');
            $this->redirectToAvailableDeliveries();
        }

        try {
            $deliveries = new DriverDeliveryManagement(Database::connect());
            $result = $deliveries->acceptDelivery(
                (int) $deliveryId,
                (int) ($_SESSION['user_id'] ?? 0)
            );

            if ($result['outcome'] === 'accepted') {
                $this->setFlash(
                    'success',
                    'Delivery #' . $result['delivery_id'] . ' was accepted and is now in transit.'
                );
                $this->redirectToActiveDelivery();
            }

            if ($result['outcome'] === 'already_active') {
                $this->setFlash(
                    'error',
                    'You already have an active delivery. Complete it before accepting another.'
                );
                $this->redirectToActiveDelivery();
            }

            if ($result['outcome'] === 'not_available') {
                $this->setFlash('error', 'This delivery is no longer available.');
                $this->redirectToAvailableDeliveries();
            }

            if ($result['outcome'] === 'not_eligible') {
                $this->setFlash(
                    'error',
                    'Your driver account is not eligible to accept deliveries right now.'
                );
                $this->redirectToAvailableDeliveries();
            }

            $this->setFlash('error', 'Delivery not found.');
            $this->redirectToAvailableDeliveries();
        } catch (Throwable $e) {
            $this->logError($e);
            $this->setFlash('error', 'The delivery could not be accepted. Please try again.');
            $this->redirectToAvailableDeliveries();
        }
    }

    public function completeDelivery(): void
    {
        $this->loadDriver('driver/active-delivery.php');

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->redirectToActiveDelivery();
        }

        $token = isset($_POST['_csrf_token']) ? (string) $_POST['_csrf_token'] : null;

        if (!Csrf::verify($token)) {
            $this->abort(403, 'Invalid security token. Please go back and try again.');
        }

        $deliveryId = filter_var(
            $_POST['delivery_id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($deliveryId === false) {
            $this->setFlash('error', 'Choose a valid delivery before marking it as delivered.');
            $this->redirectToActiveDelivery();
        }

        if (($_POST['confirm_delivered'] ?? '') !== '1') {
            $this->setFlash('error', 'Confirm that the delivery has been delivered before continuing.');
            $this->redirectToActiveDelivery();
        }

        try {
            $deliveries = new DriverDeliveryManagement(Database::connect());
            $result = $deliveries->completeDelivery(
                (int) $deliveryId,
                (int) ($_SESSION['user_id'] ?? 0)
            );

            if ($result['outcome'] === 'completed') {
                $this->setFlash(
                    'success',
                    'Delivery #' . $result['delivery_id'] . ' was marked as delivered.'
                );
                $this->redirectToHistory();
            }

            if ($result['outcome'] === 'already_completed') {
                $this->setFlash('error', 'This delivery has already been marked as delivered.');
                $this->redirectToHistory();
            }

            if ($result['outcome'] === 'not_in_transit') {
                $this->setFlash(
                    'error',
                    'This delivery can no longer be completed because it is not in transit.'
                );
                $this->redirectToActiveDelivery();
            }

            if ($result['outcome'] === 'not_assigned') {
                $this->setFlash('error', 'This delivery is not your active delivery.');
                $this->redirectToActiveDelivery();
            }

            if ($result['outcome'] === 'not_eligible') {
                $this->setFlash(
                    'error',
                    'Your driver account is not eligible to complete deliveries right now.'
                );
                $this->redirectToActiveDelivery();
            }

            $this->setFlash('error', 'Delivery not found.');
            $this->redirectToActiveDelivery();
        } catch (Throwable $e) {
            $this->logError($e);
            $this->setFlash('error', 'The delivery could not be completed. Please try again.');
            $this->redirectToActiveDelivery();
        }
    }

    public function history(): void
    {
        $path = 'driver/history.php';
        $driver = $this->loadDriver($path);
        $viewData = [];

        try {
            $deliveries = new DriverDeliveryManagement(Database::connect());
            $viewData['completedDeliveries'] = $deliveries->listCompletedDeliveries(
                (int) ($driver['driver_profile_id'] ?? 0)
            );
        } catch (Throwable $e) {
            $this->logError($e);
            $this->abort(500, 'Your delivery history could not be loaded. Please try again.');
        }

        $this->renderPage(
            'Delivery History',
            'Delivery History',
            'history.php',
            $path,
            $viewData,
            $driver
        );
    }

    private function renderPage(
        string $pageTitle,
        string $activeNav,
        string $view,
        string $path,
        array $viewData = [],
        ?array $driver = null
    ): void {
        $driver = $driver ?? $this->loadDriver($path);
        $flash = $this->pullFlash();
        $navItems = $this->driverNav();
        $userName = (string) ($driver['full_name'] ?? 'Driver');

        extract($viewData, EXTR_SKIP);

        require __DIR__ . '/../Views/layouts/driver_header.php';
        require __DIR__ . '/../Views/driver/' . $view;
        require __DIR__ . '/../Views/layouts/driver_footer.php';
    }

    private function loadDriver(string $path): array
    {
        DriverAuth::requireActiveDriver(app_url($path));

        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $driver = null;

        try {
            $dashboard = new DriverDashboard(Database::connect());
            $driver = $dashboard->findDriver($userId);
        } catch (Throwable $e) {
            $this->logError($e);
            $this->abort(500, 'Your driver account could not be loaded. Please try again.');
        }

        if ($driver === null) {
            $this->abort(403, 'Your driver profile is not available. Please contact an administrator.');
        }

        return $driver;
    }

    private function driverNav(): array
    {
        return [
            'Dashboard' => app_url('driver/dashboard.php'),
            'Available Deliveries' => app_url('driver/available-deliveries.php'),
            'My Active Delivery' => app_url('driver/active-delivery.php'),
            'Delivery History' => app_url('driver/history.php'),
            'My Profile' => app_url('driver/profile.php'),
            'Logout' => app_url('logout.php'),
        ];
    }

    private function setFlash(string $type, string $message): void
    {
        $_SESSION['driver_flash'] = [
            'type' => $type,
            'message' => $message,
        ];
    }

    /** @return array<string, string>|null */
    private function pullFlash(): ?array
    {
        $flash = $_SESSION['driver_flash'] ?? null;
        unset($_SESSION['driver_flash']);

        if (!is_array($flash) || !isset($flash['type'], $flash['message'])) {
            return null;
        }

        return [
            'type' => (string) $flash['type'],
            'message' => (string) $flash['message'],
        ];
    }

    private function redirectToAvailableDeliveries(): void
    {
        header('Location: ' . app_url('driver/available-deliveries.php'));
        exit;
    }

    private function redirectToActiveDelivery(): void
    {
        header('Location: ' . app_url('driver/active-delivery.php'));
        exit;
    }

    private function redirectToHistory(): void
    {
        header('Location: ' . app_url('driver/history.php'));
        exit;
    }

    private function abort(int $status, string $message): void
    {
        http_response_code($status);
        header('Content-Type: text/html; charset=UTF-8');

        $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

        echo '<!DOCTYPE html>' . "\n"
            . '<html lang="en">' . "\n"
            . '<head>' . "\n"
            . '    <meta charset="UTF-8">' . "\n"
            . '    <title>Request rejected</title>' . "\n"
            . '</head>' . "\n"
            . '<body>' . "\n"
            . '    <h1>Request rejected</h1>' . "\n"
            . '    <p>' . $safeMessage . '</p>' . "\n"
            . '</body>' . "\n"
            . '</html>';

        exit;
    }

    private function logError(Throwable $e): void
    {
        $logDir = __DIR__ . '/../../storage/logs';
        $logFile = $logDir . '/errors.log';

        if (!is_dir($logDir)) {
            mkdir($logDir, 0775, true);
        }

        $timestamp = date('Y-m-d H:i:s');
        $message = sprintf(
            "[%s] Driver error: %s in %s on line %s\n",
            $timestamp,
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
        );

        @file_put_contents($logFile, $message, FILE_APPEND | LOCK_EX);
    }
}
