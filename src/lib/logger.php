<?php

declare(strict_types=1);

/**
 * Manage application logging and error handling.
 */
if (!defined('GRINDS_APP'))
    exit;

final class GrindsLogger
{
    private static string $logFile = '';
    private static bool $isDebug = false;

    // Log rotation settings
    const MAX_LOG_SIZE = 5242880;
    const MAX_GENERATIONS = 5;

    private function __construct() {}

    /** Initialize logger. */
    public static function init()
    {
        // Set log path
        $logDir = ROOT_PATH . '/data/logs';
        self::$logFile = $logDir . '/error.log';

        // Ensure log directory
        grinds_secure_dir($logDir);

        // Check debug mode
        try {
            $pdo = App::db();
            if ($pdo) {
                self::$isDebug = (bool)get_option('debug_mode', 0);
            }
        } catch (Exception $e) {
            self::$isDebug = false;
        }

        // Configure error reporting
        if (self::$isDebug || (defined('DEBUG_MODE') && DEBUG_MODE)) {
            ini_set('display_errors', 1);
            error_reporting(E_ALL);
        } else {
            ini_set('display_errors', 0);
            error_reporting(0);
        }

        // Register handlers
        set_error_handler([self::class, 'errorHandler']);
        set_exception_handler([self::class, 'exceptionHandler']);
        register_shutdown_function([self::class, 'shutdownHandler']);
    }

    /** Write log message. */
    public static function log($message, $level = 'ERROR')
    {
        // Security: Mask potentially sensitive session/cookie data in error messages
        $message = preg_replace('/(PHPSESSID|grinds_[a-zA-Z0-9_]+)=[^;\s]+/', '$1=***MASKED***', $message);

        // Check rotation
        if (file_exists(self::$logFile) && filesize(self::$logFile) > self::MAX_LOG_SIZE) {
            self::rotateLogs();
        }

        // Safety check: if rotation failed or file is still too large, abort
        if (file_exists(self::$logFile) && filesize(self::$logFile) > self::MAX_LOG_SIZE) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $formatted = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;

        error_log($formatted, 3, self::$logFile);
    }

    /**
     * Rotate log files.
     */
    private static function rotateLogs()
    {
        // Remove oldest file
        $oldestFile = self::$logFile . '.' . self::MAX_GENERATIONS;
        if (file_exists($oldestFile)) {
            if (function_exists('grinds_force_unlink')) {
                grinds_force_unlink($oldestFile);
            } else {
                @unlink($oldestFile);
            }
        }

        // Shift backups
        for ($i = self::MAX_GENERATIONS - 1; $i >= 1; $i--) {
            $current = self::$logFile . '.' . $i;
            $next = self::$logFile . '.' . ($i + 1);
            if (file_exists($current)) {
                @rename($current, $next);
            }
        }

        // Rename current log
        if (!@rename(self::$logFile, self::$logFile . '.1')) {
            // Handle Windows locking
            if (@copy(self::$logFile, self::$logFile . '.1')) {
                if ($fp = @fopen(self::$logFile, 'w')) {
                    fclose($fp);
                }
            }
        }
    }

    /** Handle errors. */
    public static function errorHandler($severity, $message, $file, $line)
    {
        if (!(error_reporting() & $severity)) {
            return;
        }
        $level = 'INFO';
        switch ($severity) {
            case E_WARNING:
            case E_USER_WARNING:
                $level = 'WARNING';
                break;
            case E_NOTICE:
            case E_USER_NOTICE:
                $level = 'NOTICE';
                break;
            default:
                $level = 'ERROR';
        }
        self::log("{$message} in {$file} on line {$line}", $level);
        return true;
    }

    /** Handle exceptions. */
    public static function exceptionHandler($e)
    {
        self::log("Uncaught Exception: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine(), 'CRITICAL');
        if (!(defined('DEBUG_MODE') && DEBUG_MODE) && !self::$isDebug) {
            self::renderErrorPage();
        }
        exit;
    }

    /** Handle shutdown. */
    public static function shutdownHandler()
    {
        $error = error_get_last();
        if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE || $error['type'] === E_CORE_ERROR || $error['type'] === E_COMPILE_ERROR)) {
            self::log("Fatal Error: {$error['message']} in {$error['file']} on line {$error['line']}", 'EMERGENCY');
            if (!(defined('DEBUG_MODE') && DEBUG_MODE) && !self::$isDebug) {
                self::renderErrorPage();
            }
            exit;
        }
    }

    /** Render error page. */
    private static function renderErrorPage()
    {
        // Ensure system functions are loaded
        if (!function_exists('grinds_render_error_page')) {
            $sysFile = __DIR__ . '/functions/system.php';
            if (file_exists($sysFile))
                require_once $sysFile;
        }

        $lang = function_exists('grinds_detect_language') ? grinds_detect_language() : 'en';

        if ($lang === 'ja') {
            $title = 'サービス利用不可';
            $status = 'システムエラー';
            $message = '予期せぬエラーが発生しました。<br>しばらく時間をおいてから、再度アクセスしてください。';
        } else {
            $title = 'Service Unavailable';
            $status = 'System Error';
            $message = 'An unexpected error has occurred. Please try again in a few minutes.';
        }

        if (function_exists('grinds_render_error_page')) {
            grinds_render_error_page($title, $message, $status, 500);
        } else {
            // Fallback if system functions failed to load
            if (!headers_sent())
                http_response_code(500);
            echo "<h1>{$title}</h1><p>{$message}</p>";
            exit;
        }
    }
}
