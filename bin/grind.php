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
    public static function cyan(string $text): string
    {
        return "\033[0;36m{$text}\033[0m";
    }
    public static function magenta(string $text): string
    {
        return "\033[0;35m{$text}\033[0m";
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
    $scriptName = basename($_SERVER['argv'][0] ?? 'grind');

    echo ConsoleColor::bold("{$cmsName} CLI Tool ") . ConsoleColor::green("v{$version}") . "\n\n";
    echo ConsoleColor::yellow("Usage:\n");
    echo "  php bin/{$scriptName} <command> [arguments]\n\n";

    echo ConsoleColor::yellow("System & Maintenance:\n");
    echo "  " . ConsoleColor::green('status') . "                Display system status and configuration.\n";
    echo "  " . ConsoleColor::green('cache:clear') . "           Clear the page cache and temporary files.\n";
    echo "  " . ConsoleColor::green('maintenance:on') . "        Enable maintenance mode.\n";
    echo "  " . ConsoleColor::green('maintenance:off') . "       Disable maintenance mode.\n";

    echo "\n" . ConsoleColor::yellow("Database & Data:\n");
    echo "  " . ConsoleColor::green('db:optimize') . "           Optimize the SQLite database (VACUUM).\n";
    echo "  " . ConsoleColor::green('post:index') . "            Rebuild the search index for all posts.\n";
    echo "  " . ConsoleColor::green('backup:create') . "         Create a database backup.\n";
    echo "  " . ConsoleColor::green('backup:list') . "           List available database backups.\n";
    echo "  " . ConsoleColor::green('backup:restore') . "        Restore database from a specific backup file.\n";
    echo "  " . ConsoleColor::green('migration:create') . "      Create a full migration package (DB + Uploads ZIP).\n";

    echo "\n" . ConsoleColor::yellow("User Management:\n");
    echo "  " . ConsoleColor::green('user:list') . "             List all registered users.\n";
    echo "  " . ConsoleColor::green('user:create') . "           Create a new user account.\n";
    echo "  " . ConsoleColor::green('user:reset-password') . "   Reset an existing user's password.\n";

    echo "\n" . ConsoleColor::yellow("Extensions:\n");
    echo "  " . ConsoleColor::green('plugin:list') . "           List all available plugins and their status.\n";
    echo "  " . ConsoleColor::green('plugin:enable') . "         Enable a plugin.\n";
    echo "  " . ConsoleColor::green('plugin:disable') . "        Disable a plugin.\n";
    echo "  " . ConsoleColor::green('theme:list') . "            List all available themes.\n";
    echo "  " . ConsoleColor::green('theme:activate') . "        Activate a theme.\n";

    echo "\n" . ConsoleColor::yellow("Other:\n");
    echo "  " . ConsoleColor::green('app:version') . "           Display the application version string.\n";
    echo "  " . ConsoleColor::green('help') . "                  Display this help message.\n\n";
}

/**
 * Handles the 'status' command.
 */
function handleStatus(string $srcPath): void
{
    $version = defined('CMS_VERSION') ? CMS_VERSION : 'N/A';
    $cmsName = defined('CMS_NAME') ? CMS_NAME : 'GrindSite';
    $isMaintenance = file_exists($srcPath . '/.maintenance');

    echo ConsoleColor::bold(ConsoleColor::blue("{$cmsName} System Status")) . "\n";
    echo str_repeat('-', 45) . "\n";
    echo ConsoleColor::green("Version") . "        : " . ConsoleColor::bold($version) . "\n";
    echo ConsoleColor::green("PHP Version") . "    : " . ConsoleColor::bold(PHP_VERSION) . "\n";
    echo ConsoleColor::green("Debug Mode") . "     : " . ((defined('DEBUG_MODE') && DEBUG_MODE) ? ConsoleColor::yellow('On') : 'Off') . "\n";
    echo ConsoleColor::green("Maintenance") . "    : " . ($isMaintenance ? ConsoleColor::yellow('Active') : 'Inactive') . "\n";
    echo ConsoleColor::green("Database File") . "  : " . (defined('DB_FILE') ? DB_FILE : ConsoleColor::red('Not Defined')) . "\n";
    echo ConsoleColor::green("Source Path") . "    : " . $srcPath . "\n";

    // DB Connection & Basic Stats Check
    try {
        $pdo = App::db();
        if ($pdo) {
            $postCount = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
            $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
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
 * Handles 'maintenance:on' and 'maintenance:off'
 */
function handleMaintenance(string $srcPath, bool $enable): void
{
    $file = $srcPath . '/.maintenance';
    if ($enable) {
        if (file_put_contents($file, 'Maintenance mode enabled via CLI at ' . date('Y-m-d H:i:s'))) {
            echo ConsoleColor::green("✓ Maintenance mode enabled.") . "\n";
        } else {
            echo ConsoleColor::red("✗ Failed to enable maintenance mode (Check permissions).") . "\n";
            exit(1);
        }
    } else {
        if (file_exists($file)) {
            if (@unlink($file)) {
                echo ConsoleColor::green("✓ Maintenance mode disabled.") . "\n";
            } else {
                echo ConsoleColor::red("✗ Failed to disable maintenance mode (Check permissions).") . "\n";
                exit(1);
            }
        } else {
            echo ConsoleColor::yellow("- Maintenance mode is already disabled.") . "\n";
        }
    }
}

/**
 * Handles the 'cache:clear' command.
 */
function handleCacheClear(string $srcPath): void
{
    echo ConsoleColor::yellow("Clearing GrindSite cache...\n");
    $success = true;

    if (function_exists('clear_page_cache')) {
        try {
            clear_page_cache();
            echo "  " . ConsoleColor::green("✓") . " Page cache cleared.\n";
        } catch (Exception $e) {
            echo "  " . ConsoleColor::red("✗") . " Failed to clear page cache: " . $e->getMessage() . "\n";
            $success = false;
        }
    }

    if (function_exists('update_option')) {
        update_option('grinds_dangerous_files_cache', '', false);
        echo "  " . ConsoleColor::green("✓") . " Database system cache cleared.\n";
    }

    if (function_exists('opcache_reset')) {
        if (@opcache_reset()) {
            echo "  " . ConsoleColor::green("✓") . " PHP OPcache reset.\n";
        } else {
            echo "  " . ConsoleColor::gray("-") . " OPcache reset skipped (CLI may not have permission).\n";
        }
    }

    $tempDirs = [
        $srcPath . '/data/tmp/uploads',
        $srcPath . '/data/tmp/preview',
        $srcPath . '/assets/uploads/_preview',
    ];

    if (function_exists('grinds_delete_tree') && function_exists('grinds_secure_dir')) {
        foreach ($tempDirs as $dir) {
            if (is_dir($dir)) {
                try {
                    grinds_delete_tree($dir);
                    grinds_secure_dir($dir);
                    $relativePath = str_replace(dirname($srcPath), '', $dir);
                    echo "  " . ConsoleColor::green("✓") . " Temporary directory cleared: {$relativePath}\n";
                } catch (Exception $e) {
                    echo "  " . ConsoleColor::red("✗") . " Failed to clear {$dir}: " . $e->getMessage() . "\n";
                    $success = false;
                }
            }
        }
    }

    if ($success) {
        echo "\n" . ConsoleColor::bold(ConsoleColor::green("Cache cleared successfully!")) . "\n";
    } else {
        echo "\n" . ConsoleColor::bold(ConsoleColor::red("Cache clearing completed with errors.")) . "\n";
        exit(1);
    }
}

/**
 * Handles the 'user:list' command.
 */
function handleUserList(): void
{
    try {
        $pdo = App::db();
        if (!$pdo) throw new Exception("Database connection failed.");

        $stmt = $pdo->query("SELECT id, username, email, role, created_at FROM users ORDER BY id ASC");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo ConsoleColor::bold(ConsoleColor::blue("Registered Users")) . "\n";
        echo str_repeat('-', 70) . "\n";
        printf("%-5s | %-20s | %-10s | %-25s\n", "ID", "Username", "Role", "Email");
        echo str_repeat('-', 70) . "\n";

        foreach ($users as $u) {
            $roleColor = $u['role'] === 'admin' ? ConsoleColor::red($u['role']) : ConsoleColor::green($u['role']);
            printf("%-5s | %-20s | %-19s | %-25s\n", $u['id'], $u['username'], $roleColor, $u['email']);
        }
        echo str_repeat('-', 70) . "\n";
    } catch (Exception $e) {
        echo ConsoleColor::red("Error: " . $e->getMessage() . "\n");
        exit(1);
    }
}

/**
 * Handles the 'user:create' command.
 */
function handleUserCreate(array $args): void
{
    if (count($args) < 3) {
        echo ConsoleColor::red("Error: Missing arguments.\n");
        echo ConsoleColor::yellow("Usage:\n");
        echo "  php bin/grind user:create <username> <email> <password> [role: admin|editor]\n";
        exit(1);
    }

    $username = $args[0];
    $email = $args[1];
    $password = $args[2];
    $role = $args[3] ?? 'editor'; // Default to editor for safety

    if (!in_array($role, ['admin', 'editor'])) {
        echo ConsoleColor::red("Error: Role must be 'admin' or 'editor'.\n");
        exit(1);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo ConsoleColor::red("Error: Invalid email format.\n");
        exit(1);
    }

    if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        echo ConsoleColor::red("Error: Password must be at least 8 characters long and contain both letters and numbers.\n");
        exit(1);
    }

    try {
        $pdo = App::db();
        if (!$pdo) throw new Exception("Database connection failed.");

        // Check duplicates
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Username or Email already exists.");
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $now = date('Y-m-d H:i:s');

        $stmt = $pdo->prepare("INSERT INTO users (username, password, email, role, created_at) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$username, $hash, $email, $role, $now]);

        echo ConsoleColor::green("✓ User '{$username}' ({$role}) created successfully.\n");
    } catch (Exception $e) {
        echo ConsoleColor::red("Error: " . $e->getMessage() . "\n");
        exit(1);
    }
}

/**
 * Handles the 'user:reset-password' command.
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

    if (strlen($newPassword) < 8 || !preg_match('/[A-Za-z]/', $newPassword) || !preg_match('/[0-9]/', $newPassword)) {
        echo ConsoleColor::red("Error: Password must be at least 8 characters long and contain both letters and numbers.\n");
        exit(1);
    }

    echo ConsoleColor::yellow("Attempting to reset password for user: ") . ConsoleColor::bold($username) . "\n";

    try {
        $pdo = App::db();
        if (!$pdo) throw new Exception("Database connection failed.");

        $stmt = $pdo->prepare("SELECT id, username FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            throw new Exception("User '{$username}' not found.");
        }

        $hash = password_hash($newPassword, PASSWORD_DEFAULT);

        $pdo->beginTransaction();
        try {
            $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hash, $user['id']]);
            $pdo->prepare("DELETE FROM user_tokens WHERE user_id = ?")->execute([$user['id']]);
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

/**
 * Handles the 'backup:create' command.
 */
function handleBackupCreate(string $srcPath): void
{
    echo ConsoleColor::yellow("Creating database backup...\n");

    $dbFile = defined('DB_FILE') ? DB_FILE : $srcPath . '/data/data.db';
    if (!file_exists($dbFile)) {
        echo ConsoleColor::red("Error: Database file not found at {$dbFile}\n");
        exit(1);
    }

    $backupDir = $srcPath . '/data/backups';
    if (!is_dir($backupDir)) {
        if (!@mkdir($backupDir, 0775, true)) {
            echo ConsoleColor::red("Error: Failed to create backup directory at {$backupDir}. Check permissions.\n");
            exit(1);
        }
    }

    $timestamp = date('Ymd_His');
    $filename = 'cli_backup_' . $timestamp . '.db';
    $fullPath = $backupDir . '/' . $filename;

    if (function_exists('grinds_create_backup')) {
        try {
            grinds_create_backup($filename);
            echo ConsoleColor::green("✓ Backup created successfully: \n  ") . ConsoleColor::gray($fullPath) . "\n";

            if (function_exists('get_option') && function_exists('grinds_rotate_backups')) {
                $limit = (int)get_option('backup_retention_limit', 10);
                if ($limit > 0) {
                    grinds_rotate_backups('cli_backup_', $limit);
                    echo ConsoleColor::gray("  (Rotation policy applied: Max {$limit} backups.)\n");
                }
            }
        } catch (Exception $e) {
            echo ConsoleColor::red("✗ Failed to create backup: " . $e->getMessage() . "\n");
            exit(1);
        }
    } else {
        // Disconnect global connection to release lock safely for fallback copy
        global $pdo;
        $pdo = null;
        App::bind('db', null);
        if (function_exists('gc_collect_cycles')) gc_collect_cycles();

        // Fallback
        if (copy($dbFile, $fullPath)) {
            echo ConsoleColor::green("✓ Backup created successfully (Fallback copy): \n  ") . ConsoleColor::gray($fullPath) . "\n";
        } else {
            echo ConsoleColor::red("✗ Failed to copy database file.\n");
            exit(1);
        }
    }
}

/**
 * Handles the 'backup:list' command.
 */
function handleBackupList(string $srcPath): void
{
    $backupDir = $srcPath . '/data/backups';
    if (!is_dir($backupDir)) {
        echo "No backup directory found.\n";
        return;
    }

    $files = glob($backupDir . '/*.db');
    if (empty($files)) {
        echo "No database backups found.\n";
        return;
    }

    // Sort by modification time, newest first
    usort($files, function ($a, $b) {
        return filemtime($b) - filemtime($a);
    });

    echo ConsoleColor::bold(ConsoleColor::blue("Database Backups")) . "\n";
    echo str_repeat('-', 70) . "\n";
    printf("%-35s | %-12s | %-19s\n", "Filename", "Size", "Date Created");
    echo str_repeat('-', 70) . "\n";

    foreach ($files as $file) {
        $filename = basename($file);
        $size = filesize($file);
        $sizeStr = ($size >= 1048576) ? round($size / 1048576, 2) . ' MB' : round($size / 1024, 2) . ' KB';
        $date = date('Y-m-d H:i:s', filemtime($file));

        $color = str_starts_with($filename, 'cli_backup') ? ConsoleColor::cyan($filename) : $filename;

        printf("%-44s | %-12s | %-19s\n", $color, $sizeStr, $date);
    }
    echo str_repeat('-', 70) . "\n";
    echo ConsoleColor::gray("Files highlighted in cyan were created via CLI.\n");
}

/**
 * Handles the 'backup:restore' command.
 */
function handleBackupRestore(array $args, string $srcPath): void
{
    if (count($args) < 1) {
        echo ConsoleColor::red("Error: Missing backup filename.\n");
        echo ConsoleColor::yellow("Usage:\n");
        echo "  php bin/grind backup:restore <filename>\n";
        exit(1);
    }

    $filename = basename($args[0]);
    $backupFile = $srcPath . '/data/backups/' . $filename;
    $dbFile = defined('DB_FILE') ? DB_FILE : $srcPath . '/data/data.db';

    if (!file_exists($backupFile)) {
        echo ConsoleColor::red("Error: Backup file '{$filename}' not found in data/backups/.\n");
        exit(1);
    }

    echo ConsoleColor::red(ConsoleColor::bold("WARNING: This will OVERWRITE your current database with the selected backup.")) . "\n";
    echo "Backup File : " . ConsoleColor::cyan($filename) . "\n";
    echo "Current DB  : " . ConsoleColor::yellow(basename($dbFile)) . "\n\n";

    echo "Are you sure you want to proceed? Type '" . ConsoleColor::bold("yes") . "' to confirm: ";
    $handle = fopen("php://stdin", "r");
    $line = trim(fgets($handle));
    if (strtolower($line) !== 'yes') {
        echo ConsoleColor::gray("Aborted.\n");
        exit(0);
    }

    echo "\n" . ConsoleColor::yellow("Restoring database...\n");

    // Close active connection
    global $pdo;
    $pdo = null;
    App::bind('db', null);
    if (function_exists('gc_collect_cycles')) gc_collect_cycles();
    usleep(200000); // Wait for locks to clear

    // Create a safety backup of the current DB just in case
    @copy($dbFile, $dbFile . '.emergency_bak');

    // Remove WAL and SHM files to prevent corruption after replacing main DB
    @unlink($dbFile . '-wal');
    @unlink($dbFile . '-shm');

    if (copy($backupFile, $dbFile)) {
        echo ConsoleColor::green("✓ Database restored successfully from {$filename}.\n");

        // Auto clear cache to reflect old data
        if (function_exists('clear_page_cache')) {
            clear_page_cache();
            echo ConsoleColor::gray("  (Page cache has been cleared automatically.)\n");
        }
    } else {
        // Rollback
        @copy($dbFile . '.emergency_bak', $dbFile);
        echo ConsoleColor::red("✗ Failed to restore database. Operation rolled back.\n");
        exit(1);
    }
}

/**
 * Handles the 'db:optimize' command.
 */
function handleDbOptimize(): void
{
    if (!function_exists('grinds_optimize_database')) {
        echo ConsoleColor::red("Error: Core function 'grinds_optimize_database' not found.\n");
        exit(1);
    }

    echo ConsoleColor::yellow("Optimizing database (VACUUM)... This may take a moment.\n");

    try {
        grinds_optimize_database(false);
        echo ConsoleColor::green("✓ Database optimized successfully.\n");
    } catch (Exception $e) {
        echo ConsoleColor::red("✗ Optimization failed: " . $e->getMessage() . "\n");
        exit(1);
    }
}

/**
 * Handles the 'post:index' command.
 */
function handlePostIndex(): void
{
    if (!function_exists('grinds_rebuild_post_index')) {
        echo ConsoleColor::red("Error: Core function 'grinds_rebuild_post_index' not found.\n");
        exit(1);
    }

    echo ConsoleColor::yellow("Rebuilding search index for all posts...\n");

    // Prevent timeouts
    @set_time_limit(0);
    @ini_set('memory_limit', '512M');

    try {
        $pdo = App::db();
        if (!$pdo) throw new Exception("Database connection failed.");

        $processed = grinds_rebuild_post_index($pdo);
        echo ConsoleColor::green("✓ Search index rebuilt successfully. ({$processed} posts processed)\n");
    } catch (Exception $e) {
        echo ConsoleColor::red("✗ Index rebuild failed: " . $e->getMessage() . "\n");
        exit(1);
    }
}

/**
 * Handles the 'plugin:list' command.
 */
function handlePluginList(string $srcPath): void
{
    echo ConsoleColor::bold("Plugin Status") . "\n";
    echo str_repeat('-', 45) . "\n";
    $pluginDir = $srcPath . '/plugins';

    if (!is_dir($pluginDir)) {
        echo ConsoleColor::red("Plugin directory not found at {$pluginDir}\n");
        return;
    }

    $plugins = glob($pluginDir . '/*.php');
    if (empty($plugins)) {
        echo "No plugins found in '{$pluginDir}'.\n";
        return;
    }

    $allPlugins = [];
    foreach ($plugins as $pluginFile) {
        $filename = basename($pluginFile);
        $allPlugins[$filename] = (strpos($filename, '_') === 0) ? 'inactive' : 'active';
    }

    ksort($allPlugins, SORT_NATURAL);

    foreach ($allPlugins as $plugin => $status) {
        if ($status === 'active') {
            echo ConsoleColor::green("  [✓] Active  : ") . $plugin . "\n";
        } else {
            echo ConsoleColor::gray("  [✗] Inactive: ") . $plugin . "\n";
        }
    }
    echo str_repeat('-', 45) . "\n";
    echo ConsoleColor::gray("To disable a plugin, prefix its filename with an underscore (_).\n");
}

/**
 * Handles enabling or disabling a plugin.
 * @param array $args
 * @param string $srcPath
 * @param string $mode 'enable' or 'disable'
 */
function handlePluginToggle(array $args, string $srcPath, string $mode): void
{
    if (count($args) < 1) {
        echo ConsoleColor::red("Error: Missing plugin name.\n");
        echo ConsoleColor::yellow("Usage:\n");
        echo "  php bin/grind plugin:{$mode} <plugin_filename.php>\n";
        exit(1);
    }

    $pluginName = $args[0];
    $pluginDir = $srcPath . '/plugins';

    if (strpos($pluginName, '/') !== false || strpos($pluginName, '\\') !== false || $pluginName === '.' || $pluginName === '..') {
        echo ConsoleColor::red("Error: Invalid plugin name '{$pluginName}'.\n");
        exit(1);
    }

    $activeName = ltrim($pluginName, '_');
    $inactiveName = '_' . $activeName;

    $activePath = $pluginDir . '/' . $activeName;
    $inactivePath = $pluginDir . '/' . $inactiveName;

    if ($mode === 'enable') {
        if (!file_exists($inactivePath)) {
            if (file_exists($activePath)) {
                echo ConsoleColor::yellow("Plugin '{$activeName}' is already enabled.\n");
            } else {
                echo ConsoleColor::red("Error: Plugin '{$pluginName}' not found or is not in a disabled state.\n");
            }
            exit(1);
        }

        if (@rename($inactivePath, $activePath)) {
            echo ConsoleColor::green("✓ Plugin '{$activeName}' enabled successfully.\n");
        } else {
            echo ConsoleColor::red("✗ Failed to enable plugin. Check file permissions for the 'plugins' directory.\n");
            exit(1);
        }
    } elseif ($mode === 'disable') {
        if (!file_exists($activePath)) {
            if (file_exists($inactivePath)) {
                echo ConsoleColor::yellow("Plugin '{$activeName}' is already disabled.\n");
            } else {
                echo ConsoleColor::red("Error: Plugin '{$pluginName}' not found or is not in an active state.\n");
            }
            exit(1);
        }

        if (@rename($activePath, $inactivePath)) {
            echo ConsoleColor::green("✓ Plugin '{$activeName}' disabled successfully.\n");
        } else {
            echo ConsoleColor::red("✗ Failed to disable plugin. Check file permissions for the 'plugins' directory.\n");
            exit(1);
        }
    }
}

/**
 * Handles the 'theme:list' command.
 */
function handleThemeList(): void
{
    if (!function_exists('get_available_themes') || !function_exists('get_option')) {
        echo ConsoleColor::red("Error: Core functions for theme management not found.\n");
        exit(1);
    }

    echo ConsoleColor::bold("Available Themes") . "\n";
    echo str_repeat('-', 45) . "\n";

    $availableThemes = get_available_themes();
    $activeTheme = get_option('site_theme', 'default');

    if (empty($availableThemes)) {
        echo "No themes found in the 'theme' directory.\n";
        return;
    }

    ksort($availableThemes, SORT_NATURAL);

    foreach ($availableThemes as $slug => $name) {
        if ($slug === $activeTheme) {
            echo ConsoleColor::green("  [✓] Active: ") . ConsoleColor::bold($name) . ConsoleColor::gray(" ({$slug})") . "\n";
        } else {
            echo "        - " . $name . ConsoleColor::gray(" ({$slug})") . "\n";
        }
    }
}

/**
 * Handles the 'theme:activate' command.
 */
function handleThemeActivate(array $args): void
{
    if (count($args) < 1) {
        echo ConsoleColor::red("Error: Missing theme slug.\n");
        echo ConsoleColor::yellow("Usage:\n");
        echo "  php bin/grind theme:activate <theme_slug>\n";
        exit(1);
    }

    $slug = $args[0];

    if (!function_exists('get_available_themes') || !function_exists('update_option') || !function_exists('get_option')) {
        echo ConsoleColor::red("Error: Core functions for theme management not found.\n");
        exit(1);
    }

    $availableThemes = get_available_themes();

    if (!array_key_exists($slug, $availableThemes)) {
        echo ConsoleColor::red("Error: Theme '{$slug}' not found.\n");
        echo ConsoleColor::yellow("Available themes:\n");
        foreach ($availableThemes as $s => $n) {
            echo "  - {$s}\n";
        }
        exit(1);
    }

    if (get_option('site_theme') === $slug) {
        echo ConsoleColor::yellow("Theme '{$slug}' is already active.\n");
        exit(0);
    }

    update_option('site_theme', $slug);
    echo ConsoleColor::green("✓ Theme '{$slug}' activated successfully.\n");

    if (function_exists('clear_page_cache')) {
        clear_page_cache();
        echo ConsoleColor::gray("  (Page cache has been cleared.)\n");
    }
}

/**
 * Handles the 'app:version' command.
 */
function handleAppVersion(): void
{
    $version = defined('CMS_VERSION') ? CMS_VERSION : 'N/A';
    echo $version;
}

/**
 * Handles the 'migration:create' command.
 */
function handleMigrationCreate(string $srcPath): void
{
    echo ConsoleColor::yellow("Creating migration package...\n");

    if (!class_exists('ZipArchive')) {
        echo ConsoleColor::red("Error: PHP Zip extension is required but not installed.\n");
        exit(1);
    }
    if (!function_exists('grinds_db_snapshot') || !class_exists('FileManager')) {
        echo ConsoleColor::red("Error: Core functions for migration are missing.\n");
        exit(1);
    }
    if (function_exists('grinds_set_high_load_mode')) {
        grinds_set_high_load_mode();
    }

    $uid = substr(hash('sha256', uniqid((string)rand(), true)), 0, 16);

    $tmpDir = $srcPath . '/data/tmp';
    if (!is_dir($tmpDir)) {
        @mkdir($tmpDir, 0775, true);
    }
    $zipFile = "{$tmpDir}/migration_package_{$uid}.zip";
    $safeDbFile = "{$tmpDir}/migration_safe_{$uid}.db";
    $uploadDir = $srcPath . '/assets/uploads';

    echo "  " . ConsoleColor::gray("1/4") . " Creating database snapshot...\n";
    try {
        grinds_db_snapshot($safeDbFile);
    } catch (Exception $e) {
        echo ConsoleColor::red("✗ Failed to create database snapshot: " . $e->getMessage() . "\n");
        exit(1);
    }

    echo "  " . ConsoleColor::gray("2/4") . " Initializing archive...\n";
    $zip = new ZipArchive();
    if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
        echo ConsoleColor::red("✗ Cannot create zip file at {$zipFile}. Check permissions.\n");
        @unlink($safeDbFile);
        exit(1);
    }

    $dbName = basename(DB_FILE);
    $zip->addFile($safeDbFile, 'data/' . $dbName);

    $configPath = $srcPath . '/config.php';
    if (file_exists($configPath)) {
        $origConfig = file_get_contents($configPath);
        $configContent = grinds_prepare_migration_config($origConfig, $dbName);
        $zip->addFromString('config.php', $configContent);
    }

    echo "  " . ConsoleColor::gray("3/4") . " Archiving uploaded files...\n";
    $files = [];
    if (is_dir($uploadDir)) {
        $files = iterator_to_array(FileManager::scanDirectory($uploadDir));
    }

    $totalFiles = count($files);
    $processed = 0;
    foreach ($files as $filePath) {
        $realFilePath = realpath($filePath);
        $realUploadDir = realpath($uploadDir);
        if ($realFilePath && $realUploadDir) {
            $normRealFilePath = str_replace('\\', '/', $realFilePath);
            $normRealUploadDir = rtrim(str_replace('\\', '/', $realUploadDir), '/') . '/';
            if (stripos($normRealFilePath, $normRealUploadDir) === 0) {
                $relativePath = 'assets/uploads/' . ltrim(substr($normRealFilePath, strlen($normRealUploadDir)), '/');
                if (is_readable($realFilePath)) {
                    $zip->addFile($realFilePath, $relativePath);
                }
            }
        }
        $processed++;
        $percent = $totalFiles > 0 ? floor(($processed / $totalFiles) * 100) : 100;
        echo "\r      " . ConsoleColor::green(str_pad("{$processed}/{$totalFiles}", 10)) . " [" . str_pad(str_repeat('=', (int)($percent / 2)), 50) . "] {$percent}%";
    }
    echo "\n";

    echo "  " . ConsoleColor::gray("4/4") . " Finalizing package...\n";
    $zip->close();
    @unlink($safeDbFile);

    echo ConsoleColor::green("\n✓ Migration package created successfully: \n  ") . ConsoleColor::gray($zipFile) . "\n";
    echo ConsoleColor::yellow("  Please download this file via FTP. (Do not leave it on the server.)\n");
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

    case 'maintenance:on':
        handleMaintenance($srcPath, true);
        break;

    case 'maintenance:off':
        handleMaintenance($srcPath, false);
        break;

    case 'cache:clear':
        handleCacheClear($srcPath);
        break;

    case 'user:list':
        handleUserList();
        break;

    case 'user:create':
        handleUserCreate($args);
        break;

    case 'user:reset-password':
        handleUserResetPassword($args);
        break;

    case 'db:optimize':
        handleDbOptimize();
        break;

    case 'post:index':
        handlePostIndex();
        break;

    case 'plugin:list':
        handlePluginList($srcPath);
        break;

    case 'plugin:enable':
        handlePluginToggle($args, $srcPath, 'enable');
        break;

    case 'plugin:disable':
        handlePluginToggle($args, $srcPath, 'disable');
        break;

    case 'theme:list':
        handleThemeList();
        break;

    case 'theme:activate':
        handleThemeActivate($args);
        break;

    case 'app:version':
        handleAppVersion();
        break;

    case 'migration:create':
        handleMigrationCreate($srcPath);
        break;

    case 'backup:create':
        handleBackupCreate($srcPath);
        break;

    case 'backup:list':
        handleBackupList($srcPath);
        break;

    case 'backup:restore':
        handleBackupRestore($args, $srcPath);
        break;

    case 'help':
        displayHelp();
        break;

    default:
        echo ConsoleColor::red("Error: Unknown command '{$command}'\n\n");
        displayHelp();
        exit(1);
}

if ($command !== 'app:version') {
    echo "\n";
}
exit(0);
