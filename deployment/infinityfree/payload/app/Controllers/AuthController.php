<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\Database;
use RuntimeException;

require_once __DIR__ . '/../Services/Database.php';

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';

class AuthController
{
    public function register(): string
    {
        if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->showForm();
        }

        return $this->processRegistration();
    }

    public function home(): string
    {
        return $this->renderView(
            'home.php',
            [],
            'Mwanjelwa Kiganjani',
        );
    }

    public function login(): string
    {
        if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->showLoginForm();
        }

        return $this->processLogin();
    }

    public function logout(): void
    {
        $this->destroySession();

        header('Location: ' . app_url('login.php'));
        exit;
    }

    public function forgotPassword(): string
    {
        if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->showForgotPasswordForm();
        }

        return $this->processForgotPassword();
    }

    public function resetPassword(): string
    {
        if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->showResetPasswordForm();
        }

        return $this->processResetPassword();
    }

    public function notFound(): string
    {
        return $this->renderView(
            'not_found.php',
            [],
            'Page not found',
        );
    }

    private function showForm(): string
    {
        return $this->renderView(
            'register.php',
            [
                'full_name' => '',
                'phone' => '',
                'email' => '',
                'password' => '',
                'confirm_password' => '',
                'errors' => [],
                'success' => '',
            ],
            'Create your account',
        );
    }

    private function showLoginForm(): string
    {
        return $this->renderView(
            'login.php',
            [
                'email' => '',
                'errors' => [],
            ],
            'Login',
        );
    }

    /**
     * @param string[] $errors
     */
    private function showLoginFormWithErrors(array $errors): string
    {
        return $this->renderView(
            'login.php',
            [
                'email' => trim((string) ($_POST['email'] ?? '')),
                'errors' => $errors,
            ],
            'Login',
        );
    }

    private function processRegistration(): string
    {
        $fullName = $_POST['full_name'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (!is_array($_POST)) {
            return $this->showForm();
        }

        $errors = [];

        if ($fullName === '') {
            $errors[] = 'Full name is required.';
        }

        if ($phone === '') {
            $errors[] = 'Phone number is required.';
        }

        if ($password === '') {
            $errors[] = 'Password is required.';
        }

        if ($confirmPassword === '') {
            $errors[] = 'Please confirm your password.';
        }

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }

        if ($password !== '' && $confirmPassword !== '' && $password !== $confirmPassword) {
            $errors[] = 'Password and confirmation password do not match.';
        }

        if ($errors !== []) {
            return $this->renderView(
                'register.php',
                [
                    'full_name' => $fullName,
                    'phone' => $phone,
                    'email' => $email,
                    'password' => $password,
                    'confirm_password' => $confirmPassword,
                    'errors' => $errors,
                ],
                'Create your account',
            );
        }

        try {
            $pdo = Database::connect();

            $stmt = $pdo->prepare('SELECT id FROM users WHERE phone = :phone');
            $stmt->execute([':phone' => $phone]);
            if ($stmt->fetch()) {
                return $this->renderView(
                    'register.php',
                    [
                        'full_name' => $fullName,
                        'phone' => $phone,
                        'email' => $email,
                        'password' => $password,
                        'confirm_password' => $confirmPassword,
                        'errors' => ['A user with this phone number is already registered.'],
                    ],
                    'Create your account',
                );
            }

            if ($email !== '') {
                $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email');
                $stmt->execute([':email' => $email]);
                if ($stmt->fetch()) {
                    return $this->renderView(
                        'register.php',
                        [
                            'full_name' => $fullName,
                            'phone' => $phone,
                            'email' => $email,
                            'password' => $password,
                            'confirm_password' => $confirmPassword,
                            'errors' => ['A user with this email is already registered.'],
                        ],
                        'Create your account',
                    );
                }
            }

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                'INSERT INTO users (full_name, phone, email, password_hash, account_status)
                 VALUES (:full_name, :phone, :email, :password_hash, :account_status)'
            );
            $stmt->execute([
                ':full_name' => $fullName,
                ':phone' => $phone,
                ':email' => $email === '' ? null : $email,
                ':password_hash' => $passwordHash,
                ':account_status' => 'active',
            ]);
            $userId = (int) $pdo->lastInsertId();

            $stmt = $pdo->prepare('SELECT id FROM roles WHERE name = :name');
            $stmt->execute([':name' => 'customer']);
            $role = $stmt->fetch();

            if ($role === false) {
                throw new RuntimeException('The "customer" role does not exist in the roles table.');
            }

            $stmt = $pdo->prepare(
                'INSERT INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)'
            );
            $stmt->execute([
                ':user_id' => $userId,
                ':role_id' => (int) $role['id'],
            ]);

            $pdo->commit();

            return $this->renderView(
                'register.php',
                [
                    'success' => 'Registration successful! Your account is now active. You can log in now.',
                    'full_name' => '',
                    'phone' => '',
                    'email' => '',
                    'password' => '',
                    'confirm_password' => '',
                    'errors' => [],
                ],
                'Create your account',
            );

        } catch (\PDOException $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $this->logError($e);

            return $this->renderView(
                'register.php',
                [
                    'full_name' => $fullName,
                    'phone' => $phone,
                    'email' => $email,
                    'password' => $password,
                    'confirm_password' => $confirmPassword,
                    'errors' => ['Something went wrong. Please try again later.'],
                ],
                'Create your account',
            );
        }
    }

    private function renderView(string $view, array $data, string $title): string
    {
        extract($data);

        ob_start();
        include __DIR__ . '/../Views/' . $view;
        $html = ob_get_clean();

        return $this->wrapLayout($html, $title);
    }

    private function wrapLayout(string $content, string $title): string
    {
        $pageTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $stylesheetVersion = (string) (@filemtime(__DIR__ . '/../../public/assets/css/portal.css') ?: 0);
        $stylesheet = htmlspecialchars(
            app_url('assets/css/portal.css?v=' . rawurlencode($stylesheetVersion)),
            ENT_QUOTES,
            'UTF-8'
        );
        $script = htmlspecialchars(app_url('assets/js/ui.js'), ENT_QUOTES, 'UTF-8');
        $illustration = htmlspecialchars(app_url('assets/images/delivery-hero.svg'), ENT_QUOTES, 'UTF-8');

        return '<!DOCTYPE html>' . "\n"
            . '<html lang="en">' . "\n"
            . '<head>' . "\n"
            . '    <meta charset="UTF-8">' . "\n"
            . '    <meta name="viewport" content="width=device-width, initial-scale=1.0">' . "\n"
            . '    <meta name="theme-color" content="#f6f7f2">' . "\n"
            . '    <title>' . $pageTitle . ' - Mwanjelwa Kiganjani</title>' . "\n"
            . '    <link rel="stylesheet" href="' . $stylesheet . '">' . "\n"
            . '    <script src="' . $script . '" defer></script>' . "\n"
            . '</head>' . "\n"
            . '<body class="auth-body">' . "\n"
            . '    <div class="auth-page">' . "\n"
            . '        <aside class="auth-visual" aria-label="Mwanjelwa Kiganjani delivery service">' . "\n"
            . '            <a class="auth-brand" href="' . htmlspecialchars(app_url('index.php'), ENT_QUOTES, 'UTF-8') . '"><span class="auth-brand-mark" aria-hidden="true">MK</span><span>Mwanjelwa Kiganjani</span></a>' . "\n"
            . '            <div class="auth-visual-copy"><span class="auth-kicker">Moving Mbeya forward</span><p>From pickup to doorstep, we help every delivery find its way.</p></div>' . "\n"
            . '            <img class="auth-illustration" src="' . $illustration . '" alt="Illustration of a motorcycle courier carrying a parcel">' . "\n"
            . '            <p class="auth-visual-caption">Local service. Careful handover. Delivered with confidence.</p>' . "\n"
            . '        </aside>' . "\n"
            . '        <main class="auth-main">' . "\n"
            . '            <a class="auth-mobile-brand" href="' . htmlspecialchars(app_url('index.php'), ENT_QUOTES, 'UTF-8') . '"><span class="auth-brand-mark" aria-hidden="true">MK</span><span>Mwanjelwa Kiganjani</span></a>' . "\n"
            . '            <div class="auth-heading"><span class="auth-kicker">Mwanjelwa Kiganjani</span><h1>' . $pageTitle . '</h1></div>' . "\n"
            . '            <div class="auth-content">' . $content . '</div>' . "\n"
            . '            <footer class="auth-footer">Reliable deliveries, rooted in Mbeya.</footer>' . "\n"
            . '        </main>' . "\n"
            . '    </div>' . "\n"
            . '</body>' . "\n"
            . '</html>';
    }

    private function logError(\Throwable $e): void
    {
        $logDir = __DIR__ . '/../../storage/logs';
        $logFile = $logDir . '/errors.log';

        if (!is_dir($logDir)) {
            mkdir($logDir, 0775, true);
        }

        $timestamp = date('Y-m-d H:i:s');
        $message = sprintf(
            "[%s] Registration error: %s in %s on line %s\n",
            $timestamp,
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
        );

        @file_put_contents($logFile, $message, FILE_APPEND | LOCK_EX);
    }

    private function processLogin(): string
    {
        $emailOrPhone = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $errors = [];

        if ($emailOrPhone === '') {
            $errors[] = 'Phone number or email is required.';
        }

        if ($password === '') {
            $errors[] = 'Password is required.';
        }

        if ($errors !== []) {
            return $this->showLoginForm();
        }

        try {
            $pdo = Database::connect();

            $user = null;
            if (filter_var($emailOrPhone, FILTER_VALIDATE_EMAIL)) {
                $stmt = $pdo->prepare('SELECT id, full_name, email, phone, password_hash, account_status FROM users WHERE email = :email');
                $stmt->execute([':email' => $emailOrPhone]);
            } else {
                $stmt = $pdo->prepare('SELECT id, full_name, email, phone, password_hash, account_status FROM users WHERE phone = :phone');
                $stmt->execute([':phone' => $emailOrPhone]);
            }
            $user = $stmt->fetch();

            if ($user === false || !password_verify($password, $user['password_hash'])) {
                return $this->showLoginForm();
            }

            if ($user['account_status'] !== 'active') {
                $errors[] = 'This account is not active. Please contact an administrator.';
                return $this->showLoginFormWithErrors($errors);
            }

            $roles = [];
            $stmt = $pdo->prepare('SELECT r.name FROM user_roles ur JOIN roles r ON ur.role_id = r.id WHERE ur.user_id = :user_id');
            $stmt->execute([':user_id' => $user['id']]);
            while ($row = $stmt->fetch()) {
                $roles[] = $row['name'];
            }

            $this->startSession($user, $roles);

            $destination = 'dashboard.php';
            if (in_array('admin', $roles, true)) {
                $destination = 'admin/dashboard.php';
            }

            header('Location: ' . app_url($destination));
            exit;
        } catch (\PDOException $e) {
            $this->logError($e);

            return $this->showLoginForm();
        }
    }

    private function startSession(array $user, array $roles): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        unset($_SESSION['user_id']);
        unset($_SESSION['full_name']);
        unset($_SESSION['email']);
        unset($_SESSION['phone']);
        unset($_SESSION['roles']);

        session_regenerate_id(true);

        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['phone'] = $user['phone'];
        $_SESSION['roles'] = $roles;

        if (isset($_COOKIE[session_name()])) {
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'domain' => '',
                'secure' => false,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }

    }

    private function destroySession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_write_close();
    }
}
