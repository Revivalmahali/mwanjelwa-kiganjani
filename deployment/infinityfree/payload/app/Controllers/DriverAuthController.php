<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\Csrf;
use App\Services\Database;
use App\Services\DriverManagement;
use InvalidArgumentException;
use Throwable;

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../Services/Database.php';
require_once __DIR__ . '/../Services/Csrf.php';
require_once __DIR__ . '/../Services/DriverManagement.php';

/**
 * Mwanjelwa Kiganjani - Driver Authentication Controller
 *
 * Phase 5.1 implements driver self-registration only. The controller creates
 * a pending driver account and never logs the new driver in automatically.
 */
class DriverAuthController
{
    public function register(): string
    {
        if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->showForm();
        }

        return $this->processRegistration();
    }

    private function processRegistration(): string
    {
        $token = isset($_POST['_csrf_token']) ? (string) $_POST['_csrf_token'] : null;

        if (!Csrf::verify($token)) {
            http_response_code(403);

            return $this->showForm(
                $this->formInput($_POST),
                ['_form' => 'Invalid security token. Please go back and try again.']
            );
        }

        try {
            $management = new DriverManagement(Database::connect());
            $validation = $management->validateRegistration($_POST);

            if ($validation['errors'] !== []) {
                http_response_code(422);

                return $this->showForm(
                    $this->formInput($_POST),
                    $validation['errors']
                );
            }

            $management->registerDriver($validation['data']);

            return $this->showForm(
                [],
                [],
                'Registration successful! Your driver account is pending approval. '
                . 'You can log in after an administrator activates it.'
            );
        } catch (InvalidArgumentException $e) {
            http_response_code(422);

            return $this->showForm(
                $this->formInput($_POST),
                ['_form' => $e->getMessage()]
            );
        } catch (Throwable $e) {
            $this->logError($e);
            http_response_code(500);

            return $this->showForm(
                $this->formInput($_POST),
                ['_form' => 'Something went wrong. Please try again later.']
            );
        }
    }

    /**
     * @param array<string, string> $values
     * @param array<string, string> $errors
     */
    private function showForm(
        array $values = [],
        array $errors = [],
        string $success = ''
    ): string {
        $values = array_merge(
            [
                'first_name' => '',
                'middle_name' => '',
                'last_name' => '',
                'phone' => '',
                'email' => '',
                'residential_address' => '',
            ],
            $values
        );

        return $this->renderView(
            'driver/register.php',
            [
                'values' => $values,
                'errors' => $errors,
                'success' => $success,
            ],
            'Driver Registration'
        );
    }

    /**
     * @param array<string, mixed> $source
     * @return array<string, string>
     */
    private function formInput(array $source): array
    {
        return [
            'first_name' => $this->inputString($source['first_name'] ?? ''),
            'middle_name' => $this->inputString($source['middle_name'] ?? ''),
            'last_name' => $this->inputString($source['last_name'] ?? ''),
            'phone' => $this->inputString($source['phone'] ?? ''),
            'email' => $this->inputString($source['email'] ?? ''),
            'residential_address' => $this->inputString($source['residential_address'] ?? ''),
        ];
    }

    private function inputString(mixed $value): string
    {
        return is_string($value) ? trim($value) : '';
    }

    /**
     * @param array<string, mixed> $data
     */
    private function renderView(string $view, array $data, string $title): string
    {
        extract($data);

        ob_start();
        include __DIR__ . '/../Views/' . $view;
        $content = (string) ob_get_clean();

        return $this->wrapLayout($content, $title);
    }

    private function wrapLayout(string $content, string $title): string
    {
        ob_start();
        include __DIR__ . '/../Views/layouts/driver_auth.php';

        return (string) ob_get_clean();
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
            "[%s] Driver registration error: %s in %s on line %s\n",
            $timestamp,
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
        );

        @file_put_contents($logFile, $message, FILE_APPEND | LOCK_EX);
    }
}
