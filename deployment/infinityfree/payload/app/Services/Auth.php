<?php

declare(strict_types=1);

namespace App\Services;

require_once __DIR__ . '/../../config/app.php';

/**
 * Mwanjelwa Kiganjani - Authentication and Role Helper
 *
 * Small helper for checking the logged-in user and their roles. The
 * login process stores the user's role names in the session; this class
 * only reads that session data and never changes roles in the database.
 *
 * Role names are stored in lowercase in the roles table, so comparisons
 * here are normalised to lowercase as well.
 */
class Auth
{
    /** Is a user currently logged in? */
    public static function check(): bool
    {
        self::ensureSession();

        return isset($_SESSION['user_id']);
    }

    /**
     * Return the logged-in user's role names (lowercase, no duplicates).
     *
     * @return string[]
     */
    public static function roles(): array
    {
        self::ensureSession();

        $roles = $_SESSION['roles'] ?? [];

        if (!is_array($roles)) {
            return [];
        }

        $normalized = [];
        foreach ($roles as $role) {
            if (is_string($role) && trim($role) !== '') {
                $normalized[] = strtolower(trim($role));
            }
        }

        return array_values(array_unique($normalized));
    }

    /** Does the logged-in user have this role? */
    public static function hasRole(string $role): bool
    {
        return in_array(strtolower(trim($role)), self::roles(), true);
    }

    /**
     * Does the logged-in user have at least one of these roles?
     *
     * @param string[] $roles
     */
    public static function hasAnyRole(array $roles): bool
    {
        foreach ($roles as $role) {
            if (is_string($role) && self::hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Require a logged-in user, otherwise redirect to the login page.
     *
     * The optional destination is saved so the user can be returned there
     * after logging in.
     */
    public static function requireLogin(?string $intendedUrl = null): void
    {
        if (self::check()) {
            return;
        }

        if ($intendedUrl !== null && $intendedUrl !== '') {
            $_SESSION['redirect_after_login'] = $intendedUrl;
        }

        header('Location: ' . app_url('login.php'));
        exit;
    }

    /**
     * Require a logged-in user with at least one of the given roles.
     *
     * Guests are redirected to the login page. Logged-in users without a
     * matching role receive a simple 403 response.
     */
    public static function requireRole(string ...$roles): void
    {
        self::requireLogin();

        if (self::hasAnyRole($roles)) {
            return;
        }

        http_response_code(403);
        header('Content-Type: text/html; charset=UTF-8');

        echo '<!DOCTYPE html>' . "\n"
            . '<html lang="en">' . "\n"
            . '<head>' . "\n"
            . '    <meta charset="UTF-8">' . "\n"
            . '    <title>Access denied</title>' . "\n"
            . '</head>' . "\n"
            . '<body>' . "\n"
            . '    <h1>403 - Access Denied</h1>' . "\n"
            . '    <p>You do not have permission to view this page.</p>' . "\n"
            . '</body>' . "\n"
            . '</html>';

        exit;
    }

    /** Start the session if it has not been started yet. */
    private static function ensureSession(): void
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
    }
}
