<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Mwanjelwa Kiganjani - CSRF Protection Helper
 *
 * Generates and checks a per-session token for forms that change data.
 * Every admin POST form must include the token and the server must verify
 * it before performing the action.
 */
class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    /** Get the current token, creating one if needed. */
    public static function token(): string
    {
        self::ensureSession();

        $token = $_SESSION[self::SESSION_KEY] ?? '';

        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            $_SESSION[self::SESSION_KEY] = $token;
        }

        return $token;
    }

    /** Hidden input HTML for a form. */
    public static function field(): string
    {
        return '<input type="hidden" name="_csrf_token" value="'
            . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8')
            . '">';
    }

    /** Check a submitted token in constant time. */
    public static function verify(?string $token): bool
    {
        self::ensureSession();

        $stored = $_SESSION[self::SESSION_KEY] ?? '';

        return is_string($token)
            && is_string($stored)
            && $stored !== ''
            && hash_equals($stored, $token);
    }

    private static function ensureSession(): void
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
    }
}
