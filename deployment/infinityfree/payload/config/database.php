<?php

declare(strict_types=1);

/**
 * Mwanjelwa Kiganjani - InfinityFree Database Configuration
 *
 * Loads deployment credentials from the private database.private.php file.
 * This deployment copy has no local XAMPP credential defaults.
 */

/**
 * Read a single environment variable.
 *
 * PHP's getenv() returns false when the variable is not set. It also returns
 * an empty string when the variable is set but empty, which is what we want
 * for missing credentials. We normalise the result to an empty string here so
 * the rest of the code can treat "not set" and "set to empty" uniformly.
 *
 * This function is global (no namespace) so it can be used anywhere in the
 * application without a namespace prefix.
 */
function env(string $key, string $default = ''): string
{
    $value = getenv($key);

    return false === $value ? $default : (string) $value;
}

/**
 * Database connection settings consumed by the PDO service.
 */
class DatabaseConfig
{
    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly string $name,
        private readonly string $username,
        private readonly string $password,
    ) {
    }

    public function getDsn(): string
    {
        return sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $this->host,
            $this->port,
            $this->name,
        );
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }
}

/**
 * Build production settings from the deployment-only private config file.
 */
function configDatabase(): DatabaseConfig
{
    $privateConfigPath = __DIR__ . '/database.private.php';

    if (!is_file($privateConfigPath)) {
        throw new RuntimeException(
            'Production database configuration is missing. Install config/database.private.php.'
        );
    }

    $settings = require $privateConfigPath;

    if (!is_array($settings)) {
        throw new RuntimeException(
            'Production database configuration must return an array.'
        );
    }

    $requiredSettings = [
        'host' => 'DB_HOST',
        'name' => 'DB_DATABASE (or DB_NAME)',
        'username' => 'DB_USERNAME',
        'password' => 'DB_PASSWORD',
    ];
    $missingSettings = [];

    foreach ($requiredSettings as $key => $environmentName) {
        if (!isset($settings[$key]) || !is_string($settings[$key]) || trim($settings[$key]) === '') {
            $missingSettings[] = $environmentName;
        }
    }

    $port = filter_var($settings['port'] ?? null, FILTER_VALIDATE_INT);
    if ($port === false || $port < 1 || $port > 65535) {
        $missingSettings[] = 'DB_PORT (valid TCP port)';
    }

    if ($missingSettings !== []) {
        throw new RuntimeException(
            'Production database configuration is incomplete. Set: '
            . implode(', ', array_unique($missingSettings))
            . '.'
        );
    }

    return new DatabaseConfig(
        host: trim($settings['host']),
        port: $port,
        name: trim($settings['name']),
        username: trim($settings['username']),
        password: $settings['password'],
    );
}