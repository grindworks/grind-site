<?php

/**
 * Detect and define BASE_URL constant.
 *
 * Check if request is SSL (Defined early for URL bootstrapping).
 */
if (!function_exists('is_ssl')) {
    function is_ssl()
    {
        static $is_ssl = null;
        if ($is_ssl !== null) return $is_ssl;

        // 1. Check standard server variables (Direct SSL)
        if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') return $is_ssl = true;
        if (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) return $is_ssl = true;
        if (isset($_SERVER['REQUEST_SCHEME']) && strtolower($_SERVER['REQUEST_SCHEME']) === 'https') return $is_ssl = true;

        // 2. Check Proxy Headers (Requires TRUST_PROXIES)
        // Security: Only trust proxy headers if explicitly configured to prevent Host Header Injection.
        $trustProxies = defined('TRUST_PROXIES') && TRUST_PROXIES;

        if ($trustProxies) {
            if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && str_contains(strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']), 'https')) return $is_ssl = true;
            if (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on') return $is_ssl = true;
            if (isset($_SERVER['HTTP_X_FORWARDED_PORT']) && (int)$_SERVER['HTTP_X_FORWARDED_PORT'] === 443) return $is_ssl = true;
            if (isset($_SERVER['HTTP_CF_VISITOR'])) {
                $visitor = json_decode($_SERVER['HTTP_CF_VISITOR']);
                if (is_object($visitor) && isset($visitor->scheme) && $visitor->scheme === 'https') return $is_ssl = true;
            }
        }
        return $is_ssl = false;
    }
}

/** Resolve temporary DB path early in the bootstrap process */
if (!function_exists('_grinds_get_temp_db_path')) {
    function _grinds_get_temp_db_path()
    {
        if (!defined('DB_FILE')) return false;

        $dbPath = DB_FILE;
        if (function_exists('grinds_get_db_path')) {
            $dbPath = grinds_get_db_path();
        } elseif (!preg_match('/^(\/|[a-zA-Z]:\\\\)/', $dbPath)) {
            // Resolve relative path assuming root is parent of this file's dir
            $dbPath = dirname(__DIR__) . '/' . ltrim($dbPath, '/\\');
        }
        return file_exists($dbPath) ? $dbPath : false;
    }
}

// Return if defined
if (defined('BASE_URL')) return;

// Check forced URL
if (defined('FORCE_BASE_URL')) {
    define('BASE_URL', rtrim(constant('FORCE_BASE_URL'), '/'));
    return;
}

// Handle CLI
if (php_sapi_name() === 'cli') {
    // Check environment variables
    $envUrl = getenv('CMS_URL') ?: getenv('GRINDS_URL');
    if ($envUrl !== false && $envUrl !== '') {
        define('BASE_URL', rtrim($envUrl, '/'));
        return;
    }

    // Check CLI arguments
    if (isset($_SERVER['argv'])) {
        foreach ($_SERVER['argv'] as $arg) {
            if (str_starts_with($arg, '--url=')) {
                $cliUrl = substr($arg, 6);
                if (!empty($cliUrl)) {
                    define('BASE_URL', rtrim($cliUrl, '/'));
                    return;
                }
            }
        }
    }

    // Load config
    if (!defined('DB_FILE') && file_exists(dirname(__DIR__) . '/config.php')) {
        require_once dirname(__DIR__) . '/config.php';
        if (defined('BASE_URL')) return;
    }

    // Fetch from database
    if (defined('DB_FILE')) {
        $dbPath = _grinds_get_temp_db_path();

        if ($dbPath !== false) {
            try {
                $tempPdo = new PDO('sqlite:' . $dbPath);
                $stmt = $tempPdo->query("SELECT value FROM settings WHERE key = 'system_base_url'");
                if ($stmt) {
                    $dbUrl = $stmt->fetchColumn();
                    if ($dbUrl) {
                        define('BASE_URL', rtrim($dbUrl, '/'));
                        return;
                    }
                }
            } catch (Exception $e) {
            }
        }
    }

    // Fallback to localhost
    define('BASE_URL', 'http://localhost');
    return;
}

// Load TRUST_PROXIES from DB if not defined (Required for correct SSL detection behind proxies)
if (!defined('TRUST_PROXIES') && defined('DB_FILE')) {
    $dbPath = _grinds_get_temp_db_path();

    if ($dbPath !== false) {
        try {
            $tempPdo = new PDO('sqlite:' . $dbPath);
            $stmt = $tempPdo->query("SELECT value FROM settings WHERE key = 'trust_proxies'");
            if ($stmt) {
                $val = $stmt->fetchColumn();
                define('TRUST_PROXIES', (bool)$val);
            }
            unset($tempPdo);
        } catch (Exception $e) {
        }
    }
}

// Detect HTTPS
$is_https = function_exists('is_ssl') ? is_ssl() : false;

$protocol = $is_https ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

// Security: Sanitize Host header to prevent Host Header Injection
$host = preg_replace('/[^a-zA-Z0-9.:_-]/', '', $host);

// Normalize path
$normalizePath = function ($p) {
    return str_replace('\\', '/', $p);
};

// Get CMS root path
$rawRoot = realpath(dirname(__DIR__));
$cmsRootPhysical = $normalizePath($rawRoot ?: dirname(__DIR__));

// Get script path
$rawScript = realpath(dirname($_SERVER['SCRIPT_FILENAME']));
$scriptPhysical = $normalizePath($rawScript ?: dirname($_SERVER['SCRIPT_FILENAME']));

// Get script URL
$scriptUrlPath = $normalizePath(dirname($_SERVER['SCRIPT_NAME']));

$basePath = '/';

// Check path containment
if (str_starts_with(strtolower($scriptPhysical), strtolower($cmsRootPhysical))) {
    $subPath = substr($scriptPhysical, strlen($cmsRootPhysical));
    $subPath = trim($subPath, '/');

    $depth = 0;
    if ($subPath !== '') {
        $depth = substr_count($subPath, '/') + 1;
    }

    $basePath = $scriptUrlPath;
    for ($i = 0; $i < $depth; $i++) {
        $basePath = $normalizePath(dirname($basePath));
    }
} else {
    // Handle symlinks
    $docRoot = $normalizePath($_SERVER['DOCUMENT_ROOT']);

    if (str_starts_with(strtolower($cmsRootPhysical), strtolower($docRoot))) {
        $basePath = substr($cmsRootPhysical, strlen($docRoot));
    } else {
        $basePath = $scriptUrlPath;
    }
}

// Normalize base path
$basePath = rtrim($basePath, '/');

// Define BASE_URL
if (!defined('BASE_URL')) {
    define('BASE_URL', $protocol . $host . $basePath);
}

// Fallback to simple root
if (!defined('BASE_URL') && isset($_SERVER['HTTP_HOST'])) {
    define('BASE_URL', $protocol . $host);
}
