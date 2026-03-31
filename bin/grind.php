#!/usr/bin/env php
<?php

/**
 * GrindSite CLI Tool
 *
 * A command-line interface for managing the GrindSite CMS.
 * Runs independently without external dependencies.
 */

if (version_compare(PHP_VERSION, '8.3.0', '<')) {
    echo "\033[0;31mError: GrindSite CLI requires PHP 8.3.0 or higher. Your server is running PHP " . PHP_VERSION . ".\033[0m\n";
    exit(1);
}

if (php_sapi_name() !== 'cli') {
    echo "\033[0;31mError: This script can only be run from the command line.\033[0m\n";
    exit(1);
}

define('GRINDS_APP', true);

// --- Project Root Detection ---
// CLIツールが `bin/grind` に配置されている前提で `src` ディレクトリを解決します
$basePath = dirname(__DIR__);
$srcPath = $basePath . '/src';

if (!file_exists($srcPath . '/config.php')) {
    echo "\033[0;31mError: config.php not found in '{$srcPath}'.\033[0m\n";
    echo "\033[0;33mPlease ensure GrindSite is installed and this tool is placed in the 'bin' directory at the project root.\033[0m\n";
    exit(1);
}

// --- Load Core System ---
require_once $srcPath . '/config.php';
require_once $srcPath . '/lib/info.php';
require_once $srcPath . '/lib/functions.php';
require_once $srcPath . '/lib/db.php';

/**
 * Helper class for terminal output coloring.
 */
class ConsoleColor
{
    public static function green(string $text): string
    {
        return "\033[0;32m{$text}\033[0m";
    }
    public static function red(string $text): string
    {
        return "\033[0;31m{$text}\033[0m";
    }
    public static function yellow(string $text): string
    {
        return "\033[0;33m{$text}\033[0m";
    }
    public static function blue(string $text): string
    {
        return "\033[0;34m{$text}\033[0m";
    }
    public static function bold(string $text): string
    {
        return "\033[1m{$text}\033[0m";
    }
    public static function gray(string $text): string
    {
        return "\033[90m{$text}\033[0m";
    }
}

/**
 * Displays the main help message.
 */
function displayHelp(): void
{
    $version = defined('CMS_VERSION') ? CMS_VERSION : 'N/A';
    $cmsName = defined('CMS_NAME') ? CMS_NAME : 'GrindSite';

    echo ConsoleColor::bold("{$cmsName} CLI Tool ") . ConsoleColor::green("v{$version}") . "\n\n";
    echo ConsoleColor::yellow("Usage:\n");
    echo "  php bin/grind <command> [arguments]\n\n";
    echo ConsoleColor::yellow("Available commands:\n");
    echo "  " . ConsoleColor::green('status') . "                Display system status and configuration.\n";
    echo "  " . ConsoleColor::green('cache:clear') . "           Clear the page cache and temporary files.\n";
    echo "  " . ConsoleColor::green('user:reset-password') . "   Reset a user's password.\n";
    echo "  " . ConsoleColor::green('help') . "                  Display this help message.\n\n";
    echo ConsoleColor::yellow("Examples:\n");
    echo ConsoleColor::gray("  # Show system status\n");
    echo "  php bin/grind status\n\n";
    echo ConsoleColor::gray("  # Reset password for user 'admin'\n");
    echo "  php bin/grind user:reset-password admin newS3cretP@ssw0rd\n";
}

/**
 * Handles the 'status' command.
 */
function handleStatus(string $srcPath): void
{
    $version = defined('CMS_VERSION') ? CMS_VERSION : 'N/A';
    $cmsName = defined('CMS_NAME') ? CMS_NAME : 'GrindSite';

    echo ConsoleColor::bold(ConsoleColor::blue("{$cmsName} System Status")) . "\n";
    echo str_repeat('-', 45) . "\n";
    echo ConsoleColor::green("Version") . "        : " . ConsoleColor::bold($version) . "\n";
    echo ConsoleColor::green("PHP Version") . "    : " . ConsoleColor::bold(PHP_VERSION) . "\n";
    echo ConsoleColor::green("Debug Mode") . "     : " . ((defined('DEBUG_MODE') && DEBUG_MODE) ? ConsoleColor::yellow('On') : 'Off') . "\n";
    echo ConsoleColor::green("Database File") . "  : " . (defined('DB_FILE') ? DB_FILE : ConsoleColor::red('Not Defined')) . "\n";
    echo ConsoleColor::green("Source Path") . "    : " . $srcPath . "\n";

    // DB Connection & Basic Stats Check
    try {
        $pdo = App::db();
        if ($pdo) {
            $stmt = $pdo->query("SELECT COUNT(*) FROM posts");
            $postCount = $stmt->fetchColumn();

            $stmtUser = $pdo->query("SELECT COUNT(*) FROM users");
            $userCount = $stmtUser->fetchColumn();

            echo ConsoleColor::green("Database") . "       : " . ConsoleColor::bold("Connected") . " (Posts: {$postCount}, Users: {$userCount})\n";
        } else {
            echo ConsoleColor::green("Database") . "       : " . ConsoleColor::red("Connection Failed") . "\n";
        }
    } catch (Exception $e) {
        echo ConsoleColor::green("Database") . "       : " . ConsoleColor::red("Error - " . $e->getMessage()) . "\n";
    }

    echo str_repeat('-', 45) . "\n";
}

/**
 * Handles the 'cache:clear' command.
 */
function handleCacheClear(string $srcPath): void
{
    echo ConsoleColor::yellow("Clearing GrindSite cache...\n");

    $success = true;

    // 1. Clear Page Cache using core function
    if (function_exists('clear_page_cache')) {
        try {
            clear_page_cache();
            echo "  " . ConsoleColor::green("✓") . " Page cache cleared.\n";
        } catch (Exception $e) {
            echo "  " . ConsoleColor::red("✗") . " Failed to clear page cache: " . $e->getMessage() . "\n";
            $success = false;
        }
    } else {
        echo "  " . ConsoleColor::red("✗") . " Core function 'clear_page_cache' not found.\n";
        $success = false;
    }

    // 2. Clear Database Settings Cache
    if (function_exists('update_option')) {
        update_option('grinds_dangerous_files_cache', '', false);
        echo "  " . ConsoleColor::green("✓") . " Database system cache cleared.\n";
    }

    // 3. Clear PHP OPcache
    if (function_exists('opcache_reset')) {
        if (@opcache_reset()) {
            echo "  " . ConsoleColor::green("✓") . " PHP OPcache reset.\n";
        } else {
            echo "  " . ConsoleColor::gray("-") . " OPcache reset skipped (CLI may not have permission).\n";
        }
    }

    if ($success) {
        echo "\n" . ConsoleColor::bold(ConsoleColor::green("Cache cleared successfully!")) . "\n";
    } else {
        echo "\n" . ConsoleColor::bold(ConsoleColor::red("Cache clearing completed with errors.")) . "\n";
    }
}

/**
 * Handles the 'user:reset-password' command.
 * @param array $args
 */
function handleUserResetPassword(array $args): void
{
    if (count($args) < 2) {
        echo ConsoleColor::red("Error: Missing arguments.\n");
        echo ConsoleColor::yellow("Usage:\n");
        echo "  php bin/grind user:reset-password <username> <new_password>\n";
        exit(1);
    }

    $username = $args[0];
    $newPassword = $args[1];

    // GrindSiteのパスワード要件に準拠（8文字以上、英字と数字の両方を含む）
    if (strlen($newPassword) < 8 || !preg_match('/[A-Za-z]/', $newPassword) || !preg_match('/[0-9]/', $newPassword)) {
        echo ConsoleColor::red("Error: Password must be at least 8 characters long and contain both letters and numbers.\n");
        exit(1);
    }

    echo ConsoleColor::yellow("Attempting to reset password for user: ") . ConsoleColor::bold($username) . "\n";

    try {
        $pdo = App::db();
        if (!$pdo) {
            throw new Exception("Failed to connect to the database.");
        }

        // Check if user exists
        $stmt = $pdo->prepare("SELECT id, username FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            throw new Exception("User '{$username}' not found.");
        }

        // Hash the new password
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        if ($hash === false) {
            throw new Exception("Failed to hash the new password.");
        }

        // Update the password in a transaction
        $pdo->beginTransaction();
        try {
            $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $updateStmt->execute([$hash, $user['id']]);

            // Invalidate existing sessions/tokens to force re-login
            $pdo->prepare("DELETE FROM user_tokens WHERE user_id = ?")->execute([$user['id']]);

            // Clear login attempt lockouts for this specific username
            $pdo->prepare("DELETE FROM username_login_attempts WHERE username = ?")->execute([$user['username']]);

            $pdo->commit();

            echo ConsoleColor::green("\n✓ Password for user '{$user['username']}' has been successfully reset.\n");
            echo ConsoleColor::gray("  (Active sessions and login lockouts for this user have been cleared.)\n");
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    } catch (Exception $e) {
        echo ConsoleColor::red("\nAn error occurred: " . $e->getMessage() . "\n");
        exit(1);
    }
}

// --- Main Application Logic ---
$argv = $_SERVER['argv'];
array_shift($argv); // Remove script name
$command = $argv[0] ?? 'help';
array_shift($argv); // Remove command name
$args = $argv;

switch ($command) {
    case 'status':
        handleStatus($srcPath);
        break;

    case 'cache:clear':
        handleCacheClear($srcPath);
        break;

    case 'user:reset-password':
        handleUserResetPassword($args);
        break;

    case 'help':
        displayHelp();
        break;

    default:
        echo ConsoleColor::red("Error: Unknown command '{$command}'\n\n");
        displayHelp();
        exit(1);
}

echo "\n";
exit(0);
