<?php

declare(strict_types=1);

/**
 * Manage dependency injection container.
 *
 * A simple service container for managing global application state and dependencies.
 *
 * @package GrindSite
 */

if (!defined('GRINDS_APP')) {
    exit;
}

final class App
{
    /**
     * The array of registered services.
     *
     * @var array<string, mixed>
     */
    private static array $container = [];

    /**
     * Prevent instantiation of this utility class.
     */
    private function __construct() {}

    /**
     * Bind a service to the container.
     *
     * @param string $key   The service identifier.
     * @param mixed  $value The service instance or value.
     * @return void
     */
    public static function bind(string $key, mixed $value): void
    {
        self::$container[$key] = $value;
    }

    /**
     * Resolve a service from the container.
     *
     * @param string $key The service identifier.
     * @return mixed|null The resolved service or null if not found.
     */
    public static function make(string $key): mixed
    {
        return self::$container[$key] ?? null;
    }

    /**
     * Get the Database connection instance.
     *
     * @return PDO|null The PDO instance or null if not initialized.
     */
    public static function db(): ?PDO
    {
        $db = self::make('db');
        return ($db instanceof PDO) ? $db : null;
    }

    /**
     * Get the current logged-in user context.
     *
     * @return array{id: int, username: string, role: string, avatar: string}|null
     */
    public static function user(): ?array
    {
        if (isset($_SESSION) && !empty($_SESSION['admin_logged_in'])) {
            return [
                'id'       => (int)($_SESSION['user_id'] ?? 0),
                'username' => (string)($_SESSION['username'] ?? ''),
                'role'     => (string)($_SESSION['user_role'] ?? 'editor'),
                'avatar'   => (string)($_SESSION['user_avatar'] ?? ''),
            ];
        }
        return null;
    }
}
