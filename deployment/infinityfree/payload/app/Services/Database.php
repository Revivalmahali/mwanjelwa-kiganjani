<?php

declare(strict_types=1);

/**
 * Mwanjelwa Kiganjani - Database Connection Service
 *
 * Provides a single, reusable PDO connection to the database.
 *
 * Usage:
 *   use App\Services\Database;
 *
 *   $pdo = Database::connect();
 *   $stmt = $pdo->query('SELECT 1');
 *
 * The connection is created once and cached in a static property. Calling
 * connect() again returns the same instance, so the application never opens
 * more than one connection.
 */

namespace App\Services;

use Config\DatabaseConfig;
use PDO;
use PDOException;
use RuntimeException;

use function file_put_contents;
use function mkdir;
use function getenv;

/**
 * Reusable PDO connection service for Mwanjelwa Kiganjani.
 */
class Database
{
    private static ?PDO $connection = null;

    /**
     * Get (or create) the single shared PDO connection.
     *
     * @throws RuntimeException If the connection cannot be established.
     */
    public static function connect(): PDO
    {
        if (self::$connection !== null) {
            return self::$connection;
        }

        $config = configDatabase();

        $dsn = $config->getDsn();
        $user = $config->getUsername();
        $pass = $config->getPassword();

        // PDO options:
        // - ERRMODE_EXCEPTION  : throw on database errors (no silent failures)
        // - EMULATE_PREPARES => false : real prepared statements (better security)
        // - FETCH_ASSOC        : return rows as associative arrays
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        try {
            $pdo = new PDO($dsn, $user, $pass, $options);

            // Keep the connection alive for the whole request / script run.
            $pdo->setAttribute(PDO::ATTR_PERSISTENT, false);

            self::$connection = $pdo;

            return $pdo;
        } catch (PDOException $e) {
            // Log the technical error to storage/logs/database.log so it is
            // available for debugging, but never shown to end users.
            self::logConnectionError($e, $config);

            // Re-throw as a plain RuntimeException so callers do not see PDO
            // internals, credentials, or server details.
            throw new RuntimeException(
                'Unable to connect to the database. Please check your '
                . 'configuration and try again.',
            );
        }
    }

    /**
     * Get the currently active PDO connection.
     *
     * @throws RuntimeException If no connection has been established yet.
     */
    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            self::connect();
        }

        return self::$connection;
    }

    /**
     * Close the connection (useful in tests or when a fresh connection is
     * needed later in the same request).
     */
    public static function disconnect(): void
    {
        self::$connection = null;
    }

    /**
     * Log a technical connection error to storage/logs/database.log.
     *
     * The log entry contains the real error so developers can diagnose it,
     * but the message shown to the user is safe.
     */
    private static function logConnectionError(PDOException $e, DatabaseConfig $config): void
    {
        $logDir = __DIR__ . '/../../storage/logs';
        $logFile = $logDir . '/database.log';

        // Ensure the logs directory exists (XAMPP may not have created it).
        if (!is_dir($logDir)) {
            mkdir($logDir, 0775, true);
        }

        $timestamp = date('Y-m-d H:i:s');
        $message = sprintf(
            "[%s] DB connection failed (host=%s, port=%d, db=%s)\nMessage: %s\nFile: %s\nLine: %s\n\n",
            $timestamp,
            $config->getHost(),
            $config->getPort(),
            $config->getName(),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
        );

        // Ignore write errors; we never want a logging problem to mask the
        // real connection failure.
        @file_put_contents($logFile, $message, FILE_APPEND | LOCK_EX);
    }

    private function __construct()
    {
        // The constructor is private. Use connect() / getConnection() instead.
    }
}
