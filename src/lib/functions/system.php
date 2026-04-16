<?php

/**
 * Manage system operations.
 */
if (!defined('GRINDS_APP'))
    exit;

/**
 * Initialize system.
 */
function init_system()
{
    // Block execution if PHP version is strictly below 8.3
    if (version_compare(PHP_VERSION, '8.3.0', '<')) {
        $msg = 'GrindSite requires PHP 8.3.0 or higher. Your server is running PHP ' . PHP_VERSION . '. Please upgrade your PHP version via your hosting control panel.';
        if (function_exists('grinds_render_error_page')) {
            grinds_render_error_page('PHP Version Error', $msg, 'System Error', 500);
        } else {
            http_response_code(500);
            die($msg);
        }
    }

    $pdo = App::db();

    $default_timezone = defined('DEFAULT_TIMEZONE') ? constant('DEFAULT_TIMEZONE') : 'UTC';
    date_default_timezone_set($default_timezone);

    $scriptPath = $_SERVER['SCRIPT_NAME'] ?? '';
    $isAdminRequest = str_contains(str_replace('\\', '/', $scriptPath), '/admin/');

    // Define proxy constants
    if (!defined('TRUST_PROXIES')) {
        define('TRUST_PROXIES', (bool)get_option('trust_proxies', 0));
    }
    if (!defined('TRUSTED_PROXY_IPS')) {
        define('TRUSTED_PROXY_IPS', get_option('trusted_proxy_ips', ''));
    }

    $lang = 'en';
    if ($pdo) {
        $lang = get_option('site_lang', 'en');
    } elseif (defined('SITE_LANG')) {
        $lang = constant('SITE_LANG');
    }

    if (class_exists('I18n')) {
        I18n::init($lang);
    }

    if (!defined('SITE_LANG')) {
        define('SITE_LANG', $lang);
    }

    ini_set('default_charset', 'UTF-8');

    if ($pdo) {
        try {
            $db_tz = get_option('timezone');

            if ($db_tz) {
                date_default_timezone_set($db_tz);
            }

            // Detect migration
            if ($isAdminRequest) {
                $storedUrl = get_option('system_base_url', false);

                $shouldMigrate = false;
                $urlChanged = false;

                if ($storedUrl === false) {
                    $shouldMigrate = true;
                    $urlChanged = true;
                } elseif ($storedUrl !== BASE_URL) {
                    $urlChanged = true;

                    $storedHost = parse_url($storedUrl, PHP_URL_HOST) ?: '';
                    $currentHost = parse_url(BASE_URL, PHP_URL_HOST) ?: '';
                    $storedPath = parse_url($storedUrl, PHP_URL_PATH) ?: '/';
                    $currentPath = parse_url(BASE_URL, PHP_URL_PATH) ?: '/';

                    if (strcasecmp($storedHost, $currentHost) !== 0 || rtrim($storedPath, '/') !== rtrim($currentPath, '/')) {
                        $shouldMigrate = true;
                    }
                }

                if ($urlChanged) {
                    update_option('system_base_url', BASE_URL);
                }

                if ($shouldMigrate) {
                    if ($storedUrl !== false) {
                        if (class_exists('GrindsLogger')) {
                            GrindsLogger::log("System migration detected: URL changed from [{$storedUrl}] to [" . BASE_URL . "]. Clearing cache.", 'INFO');
                        }

                        if (session_status() === PHP_SESSION_ACTIVE) {
                            $_SESSION['migration_alert'] = [
                                'old' => $storedUrl,
                                'new' => BASE_URL,
                                'timestamp' => time()
                            ];
                        }
                    }

                    // Clear caches
                    $cacheBase = ROOT_PATH . '/data/cache';
                    $pagesDir = $cacheBase . '/pages';

                    if (function_exists('clear_page_cache')) {
                        clear_page_cache();
                    }

                    grinds_secure_dir($pagesDir);
                }
            }
        } catch (Exception $e) {
            error_log("GrindsCMS Init Error: " . $e->getMessage());
        }
    }

    // Detect configuration mismatch
    if ($isAdminRequest && isset($_SERVER['HTTP_HOST']) && php_sapi_name() !== 'cli') {
        $currentHost = $_SERVER['HTTP_HOST'];

        // Check proxy trust
        $trustProxies = function_exists('is_proxy_trusted') ? is_proxy_trusted() : false;

        // Support X-Forwarded-Host
        if ($trustProxies && !empty($_SERVER['HTTP_X_FORWARDED_HOST'])) {
            $elements = explode(',', $_SERVER['HTTP_X_FORWARDED_HOST']);
            $currentHost = trim($elements[0]);
        }

        $hostParts = explode(':', $currentHost);
        $cleanCurrentHost = $hostParts[0];

        $configHost = parse_url(BASE_URL, PHP_URL_HOST);

        if ($configHost && strcasecmp($cleanCurrentHost, $configHost) !== 0) {
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['config_url_mismatch'] = [
                    'actual' => $cleanCurrentHost,
                    'config' => $configHost
                ];
            }
        } else {
            if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['config_url_mismatch'])) {
                unset($_SESSION['config_url_mismatch']);
            }
        }
    }
}

/**
 * Check local environment.
 */
if (!function_exists('is_local_environment')) {
    function is_local_environment()
    {
        $host = isset($_SERVER['HTTP_HOST']) ? preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST']) : '';
        $local_domains = ['localhost'];
        if (in_array($host, $local_domains))
            return true;

        // Check private IPs
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            if (!filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return true;
            }
        }

        $local_tlds = ['.test', '.local', '.localhost', '.ddev.site', '.lando.site', '.example', '.invalid'];
        foreach ($local_tlds as $tld) {
            if (str_ends_with($host, $tld))
                return true;
        }
        return false;
    }
}

/**
 * Detect language.
 */
function grinds_detect_language()
{
    if (defined('SITE_LANG')) {
        return SITE_LANG;
    }

    static $lang = null;
    if ($lang !== null) {
        return $lang;
    }

    $browserLang = (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && str_contains(strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE']), 'ja')) ? 'ja' : 'en';

    if (function_exists('get_option')) {
        $lang = (string)get_option('site_lang', $browserLang);
    } else {
        $lang = $browserLang;
    }

    return $lang;
}

/**
 * Run garbage collection.
 */
function grinds_run_garbage_collection()
{
    $sessionPath = ROOT_PATH . '/data/sessions';
    if (is_dir($sessionPath)) {
        $lifetime = (int)ini_get('session.gc_maxlifetime');
        if ($lifetime < 1440)
            $lifetime = 1440;
        $expireTime = time() - $lifetime;
        if (function_exists('grinds_clean_directory_files')) {
            grinds_clean_directory_files($sessionPath, 'sess_', $expireTime);
        }
    }

    // Clean cache trash
    $cacheBase = ROOT_PATH . '/data/cache';
    if (is_dir($cacheBase) && function_exists('grinds_delete_tree')) {
        try {
            foreach (new DirectoryIterator($cacheBase) as $fileInfo) {
                if ($fileInfo->isDir() && str_starts_with($fileInfo->getFilename(), 'pages_trash_')) {
                    if ($fileInfo->getMTime() < time() - 3600) {
                        grinds_delete_tree($fileInfo->getPathname());
                    }
                }
            }
        } catch (Exception $e) {
        }
    }

    // Clean page cache
    $pagesDir = ROOT_PATH . '/data/cache/pages';
    if (is_dir($pagesDir)) {
        $cacheLifetime = 3600; // 1 hour
        $expireTime = time() - $cacheLifetime;

        try {
            foreach (new DirectoryIterator($pagesDir) as $fileInfo) {
                if ($fileInfo->isFile() && in_array($fileInfo->getExtension(), ['html', 'xml', 'txt'], true)) {
                    if ($fileInfo->getMTime() < $expireTime) {
                        grinds_force_unlink($fileInfo->getPathname());
                    }
                }
            }
        } catch (Exception $e) { /* Ignore */
        }
    }

    // Clean temporary uploads
    $tmpUploadsPath = ROOT_PATH . '/data/tmp/uploads';
    if (is_dir($tmpUploadsPath)) {
        $expireTime = time() - 3600;
        if (function_exists('grinds_clean_directory_files')) {
            grinds_clean_directory_files($tmpUploadsPath, '', $expireTime);
        }
    }

    // Clean preview files
    $previewDir = ROOT_PATH . '/data/tmp/preview';
    if (is_dir($previewDir)) {
        $expireTime = time() - 7200;
        if (function_exists('grinds_clean_directory_files')) {
            grinds_clean_directory_files($previewDir, 'preview_', $expireTime);
        }
    }

    // Clean preview uploaded images
    $previewImagesDir = ROOT_PATH . '/assets/uploads/_preview';
    if (is_dir($previewImagesDir)) {
        $expireTime = time() - 86400; // 24 hours
        if (function_exists('grinds_clean_directory_files')) {
            grinds_clean_directory_files($previewImagesDir, 'preview_', $expireTime);
        }
    }

    // Clean trash files
    $trashDir = ROOT_PATH . '/assets/uploads/_trash';
    if (is_dir($trashDir)) {
        $expireTime = time() - (30 * 86400);
        if (function_exists('grinds_clean_directory_files')) {
            grinds_clean_directory_files($trashDir, '', $expireTime, ['.htaccess']);
        }
    }

    // Clean temporary files
    $tmpDir = ROOT_PATH . '/data/tmp';
    if (is_dir($tmpDir) && function_exists('grinds_delete_tree')) {
        $expireTime = time() - 3600;
        try {
            foreach (new DirectoryIterator($tmpDir) as $fileInfo) {
                if ($fileInfo->isDot()) continue;
                if ($fileInfo->getMTime() < $expireTime) {
                    $file = $fileInfo->getFilename();
                    $path = $fileInfo->getPathname();
                    if (str_starts_with($file, 'static_export_') && is_dir($path)) {
                        grinds_delete_tree($path);
                    } elseif ((str_starts_with($file, 'static_site_') || str_starts_with($file, 'migration_package') || str_starts_with($file, 'ssg_assets_')) && is_file($path)) {
                        grinds_force_unlink($path);
                    }
                }
            }
        } catch (Exception $e) { /* Ignore */
        }
    }

    // Clean login attempts and tokens
    try {
        $pdo = App::db();
        if ($pdo) {
            $yesterday = gmdate('Y-m-d H:i:s', time() - 86400);
            $pdo->prepare("DELETE FROM login_attempts WHERE last_attempt_at < ?")->execute([$yesterday]);
            $pdo->prepare("DELETE FROM username_login_attempts WHERE last_attempt_at < ?")->execute([$yesterday]);

            // Clean up expired remember me tokens
            $pdo->prepare("DELETE FROM user_tokens WHERE expires_at < ?")->execute([date('Y-m-d H:i:s')]);
        }
    } catch (Exception $e) {
        error_log("GC Error (Login Attempts): " . $e->getMessage());
    }
}

/**
 * Send noindex headers.
 */
if (!function_exists('grinds_send_noindex_headers')) {
    function grinds_send_noindex_headers()
    {
        if (!headers_sent()) {
            header('X-Robots-Tag: noindex, nofollow, noarchive, nosnippet');
        }
    }
}

/**
 * Get license status.
 */
if (!function_exists('get_license_status')) {
    function get_license_status()
    {
        $key = get_option('license_key', '');

        if (empty($key)) {
            return is_local_environment() ? 'trial' : 'unregistered';
        }

        // Check cache
        $cache = get_license_check_cache();
        $hasValidCache = !empty($cache) &&
            isset($cache['key'], $cache['status'], $cache['expires']) &&
            $cache['key'] === $key;

        if (
            $hasValidCache &&
            $cache['expires'] > time()
        ) {
            return $cache['status'];
        }

        // Verify via Polar.sh API
        $orgId = '25291602-668f-4ed5-84e8-d966a5932049';
        $newStatus = 'unregistered';
        $apiSuccess = false;

        if (empty($orgId) || $orgId === 'YOUR_POLAR_ORG_ID' || !function_exists('curl_init')) {
            return $hasValidCache ? $cache['status'] : 'unregistered';
        }

        $ch = curl_init('https://api.polar.sh/v1/customer-portal/license-keys/validate');
        $payload = json_encode(['key' => $key, 'organization_id' => $orgId]);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_errno($ch);
        curl_close($ch);

        // Log API communication errors
        if ($httpCode !== 200 || $curlError !== 0) {
            error_log("Polar API Error - HTTP: {$httpCode}, cURL Error: {$curlError}, Response: {$response}");
        }

        if ($curlError === 0 && $httpCode === 200 && $response) {
            $apiSuccess = true;
            $data = json_decode($response, true);
            if (is_array($data) && (
                (isset($data['status']) && in_array($data['status'], ['granted', 'active', 'validated'])) ||
                (isset($data['id']) && !isset($data['status']))
            )) {
                // Determine status by key prefix
                if (str_starts_with($key, 'GRIND-AGENCY')) {
                    $newStatus = 'agency';
                } else {
                    $newStatus = 'pro';
                }
            }
        }

        // Update cache
        $cacheData = [
            'key' => $key,
            'status' => $newStatus,
            'expires' => time() + (7 * 86400)
        ];

        if ($apiSuccess) {
            update_option('license_check_cache', json_encode($cacheData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_IGNORE));
            return $newStatus;
        } else {
            if ($hasValidCache && in_array($cache['status'], ['pro', 'agency'])) {
                $cache['expires'] = time() + (30 * 86400);
                update_option('license_check_cache', json_encode($cache, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_IGNORE));
                return $cache['status'];
            }
        }

        return 'unregistered';
    }
}

if (!function_exists('get_license_check_cache')) {
    function get_license_check_cache()
    {
        $cacheRaw = get_option('license_check_cache', '[]');
        if (empty($cacheRaw)) {
            return [];
        }
        $cache = json_decode($cacheRaw, true);
        return is_array($cache) ? $cache : [];
    }
}

/**
 * Check license validity.
 */
if (!function_exists('is_licensed')) {
    function is_licensed()
    {
        return in_array(get_license_status(), ['pro', 'agency'], true);
    }
}

/**
 * Clear page cache.
 */
if (!function_exists('clear_page_cache')) {
    function clear_page_cache()
    {
        $cacheBase = ROOT_PATH . '/data/cache';
        $cacheDir = $cacheBase . '/pages';

        // Ensure directory
        if (is_dir($cacheDir)) {
            // Validate directory path
            $realCacheDir = rtrim(str_replace('\\', '/', (string)realpath($cacheDir)), '/');
            $realExpectedDir = rtrim(str_replace('\\', '/', (string)realpath(ROOT_PATH . '/data/cache/pages')), '/');

            if (empty($realCacheDir) || $realCacheDir !== $realExpectedDir) {
                return;
            }

            $trashDir = $cacheBase . '/pages_trash_' . uniqid() . '_' . time();

            if (@rename($cacheDir, $trashDir)) {
                grinds_secure_dir($cacheDir);
            } else {
                // Fallback to iteration
                try {
                    foreach (new DirectoryIterator($cacheDir) as $fileInfo) {
                        if ($fileInfo->isFile() && in_array($fileInfo->getExtension(), ['html', 'xml', 'txt'], true)) {
                            grinds_force_unlink($fileInfo->getPathname());
                        }
                    }
                } catch (Exception $e) {
                    // Ignore errors
                }
            }
        }

        // Clear PHP cache
        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }
    }
}

/**
 * Check system health.
 */
function check_system_health()
{
    return GrindsSystemCheck::getHealthReport();
}

/**
 * Get system status.
 */
function get_system_status()
{
    $checks = check_system_health();
    $status = 'ok';
    $danger_errors = [];
    $warning_errors = [];

    foreach ($checks as $chk) {
        if ($chk['status'] === 'danger') {
            $status = 'danger';
            $danger_errors[] = $chk['label'];
        } elseif ($chk['status'] === 'warning') {
            if ($status !== 'danger') {
                $status = 'warning';
            }
            $warning_errors[] = $chk['label'];
        }
    }

    $msg = _t('system_operational');

    if ($status === 'danger') {
        $msg = implode(', ', array_slice($danger_errors, 0, 2));
        if (count($danger_errors) > 2)
            $msg .= '...';
    } elseif ($status === 'warning') {
        $msg = implode(', ', array_slice($warning_errors, 0, 2));
        if (count($warning_errors) > 2)
            $msg .= '...';
    }

    return ['status' => $status, 'msg' => $msg];
}

/**
 * Scan dangerous files.
 */
if (!function_exists('grinds_scan_dangerous_files')) {
    function grinds_scan_dangerous_files()
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        // Check database cache
        $cache_key = 'grinds_dangerous_files_cache';
        $cached_data = function_exists('get_option') ? get_option($cache_key) : '';
        if ($cached_data) {
            $decoded = json_decode($cached_data, true);
            if (is_array($decoded) && isset($decoded['time'], $decoded['data']) && $decoded['time'] > time() - 3600) {
                return $cache = $decoded['data'];
            }
        }

        $detected_files = ['danger' => [], 'warning' => []];
        if (!defined('ROOT_PATH'))
            return $detected_files;

        $scan_dir = ROOT_PATH;

        if (is_dir($scan_dir)) {
            $files = scandir($scan_dir);

            // Define whitelist
            $whitelist = [
                '.',
                '..',
                '.htaccess',
                '.nginx_confirmed',
                '.maintenance',
                'index.php',
                'config.php',
                'install.php',
                'robots.txt',
                'ads.txt',
                'humans.txt',
                'security.txt',
                'llms.txt',
                'llms-full.txt',
                'sitemap.xml',
                'rss.xml',
                'crossdomain.xml',
                'favicon.ico',
                'LICENSE',
                'LICENSE.txt',
                'README.md',
                'README.txt',
                'CHANGELOG.md',
                'CONTRIBUTING.md',
                'update.json',
                'nginx.conf.sample'
            ];

            // Define danger list
            $danger_list = [
                'tool_fix_settings.php',
                'tool_reset_password.php',
                'tool_create_admin.php',
                'adminer.php',
                'dump.sql',
                'backup.sql',
                'users.sql',
                'data.sql',
                '.env',
                '.env.local',
                '.env.production',
                '.env.development',
                '.git',
                '.svn',
                '.hg',
                '.bzr',
                'php_error.log',
                'error_log',
                'debug.log',
                'id_rsa',
                'id_rsa.pub',
                'authorized_keys'
            ];

            // Define warning list
            $warning_list = [
                '.DS_Store',
                'Thumbs.db',
                '.idea',
                '.vscode',
                'composer.json',
                'composer.lock',
                'package.json',
                'package-lock.json',
                'yarn.lock',
                'php.ini',
                '.user.ini'
            ];

            // Define danger extensions
            $danger_extensions = [
                'bak',
                'old',
                'save',
                'tmp',
                'swp',
                'inc',
                'org',
                '001',
                'log',
                'sql',
                'sh',
                'bash',
                'zsh',
                'pem',
                'key',
                'zip',
                'tar',
                'gz',
                'rar',
                '7z',
                'bz2',
                'sqlite',
                'sqlite3',
                'db'
            ];

            // Define keywords
            $watch_keywords = ['config', 'install', 'rescue', 'reset', 'password', 'admin', 'backup', 'dump', 'secret'];

            foreach ($files as $file) {
                if (in_array($file, $whitelist))
                    continue;

                if (in_array($file, $danger_list)) {
                    $detected_files['danger'][] = $file;
                    continue;
                }
                if (in_array($file, $warning_list)) {
                    $detected_files['warning'][] = $file;
                    continue;
                }

                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

                // Check extensions
                if (in_array($ext, $danger_extensions) || str_contains($file, '~')) {
                    $detected_files['danger'][] = $file;
                    continue;
                }

                // Check keywords
                foreach ($watch_keywords as $keyword) {
                    if (str_contains(strtolower($file), $keyword)) {
                        if ($ext === 'php') {
                            $detected_files['danger'][] = $file;
                            continue 2;
                        }
                    }
                }
            }
        }

        // Check tools
        if (is_dir(ROOT_PATH . '/tools')) {
            $toolFiles = glob(ROOT_PATH . '/tools/tool_*.php');
            if (!empty($toolFiles)) {
                $detected_files['danger'][] = 'Files in /tools/';
            }
        }

        if (function_exists('update_option')) {
            update_option($cache_key, json_encode(['time' => time(), 'data' => $detected_files]), false);
        }

        return $cache = $detected_files;
    }
}

/**
 * Set high load mode.
 */
if (!function_exists('grinds_set_high_load_mode')) {
    function grinds_set_high_load_mode()
    {
        // Set memory limit
        @ini_set('memory_limit', '512M');
        // Set time limit
        @set_time_limit(0);
    }
}

/**
 * Secure directory.
 */
function grinds_secure_dir($dir)
{
    if (!is_dir($dir)) {
        if (!@mkdir($dir, 0775, true))
            return false;
    }

    if (!file_exists($dir . '/.htaccess')) {
        @file_put_contents($dir . '/.htaccess', "Require all denied\n");
    }
    if (!file_exists($dir . '/index.php')) {
        @file_put_contents($dir . '/index.php', '<?php http_response_code(403); exit("Access Denied"); ?>');
    }

    return true;
}

/**
 * Optimize database.
 */
function grinds_optimize_database($switchToDeleteMode = false)
{
    ignore_user_abort(true);
    if (function_exists('grinds_set_high_load_mode')) {
        grinds_set_high_load_mode();
    }

    $pdo = App::db();

    // Ensure connection
    if (!$pdo || !($pdo instanceof PDO)) {
        if (function_exists('grinds_db_connect')) {
            $pdo = grinds_db_connect();
            if (class_exists('App')) {
                App::bind('db', $pdo);
            }
        }
    }

    if (!defined('DB_FILE')) {
        throw new Exception("DB_FILE not defined.");
    }

    // Check disk space
    if (file_exists(DB_FILE) && function_exists('disk_free_space')) {
        clearstatcache(true, DB_FILE);
        $dbSize = filesize(DB_FILE);
        $freeSpace = @disk_free_space(dirname(DB_FILE));
        if ($freeSpace !== false && $freeSpace < ($dbSize * 2)) {
            throw new Exception(_t('err_disk_full_vacuum'));
        }
    }

    // Execute VACUUM
    $pdo->exec("PRAGMA busy_timeout = 60000;");
    $pdo->exec("PRAGMA wal_checkpoint(TRUNCATE);");
    $pdo->exec("VACUUM;");

    if ($switchToDeleteMode) {
        $pdo->exec("PRAGMA journal_mode = DELETE;");
    }
}

/**
 * Check RewriteBase.
 */
function grinds_check_rewrite_base()
{
    // Use cache
    if (
        isset($_SESSION['rewrite_base_check']) &&
        $_SESSION['rewrite_base_check']['time'] > time() - 300
    ) {
        return $_SESSION['rewrite_base_check']['data'];
    }

    if (!file_exists(ROOT_PATH . '/.htaccess')) {
        $result = ['status' => 'ok'];
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['rewrite_base_check'] = ['time' => time(), 'data' => $result];
        }
        return $result;
    }

    $htContent = @file_get_contents(ROOT_PATH . '/.htaccess');

    $path = parse_url(defined('BASE_URL') ? BASE_URL : '/', PHP_URL_PATH);
    $path = is_string($path) ? $path : '/';
    $detectedBase = rtrim($path, '/') . '/';

    $hasActiveRewriteBase = false;
    $configuredBase = '';

    if ($htContent && preg_match('/^\s*RewriteBase\s+(.+?)\s*$/m', $htContent, $matches)) {
        $hasActiveRewriteBase = true;
        $configuredBase = trim($matches[1]);
    }

    if ($hasActiveRewriteBase && strcasecmp($configuredBase, $detectedBase) !== 0) {
        $result = ['status' => 'error', 'configured' => $configuredBase, 'detected' => $detectedBase];
    } else {
        $result = ['status' => 'ok'];
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION['rewrite_base_check'] = ['time' => time(), 'data' => $result];
    }
    return $result;
}

/**
 * Check system requirements.
 */
class GrindsSystemCheck
{
    private static $cachedReport = null;

    public static function getCriticalPaths()
    {
        return [
            '/../data',
            '/../data/cache',
            '/../data/logs',
            '/../data/tmp',
            '/../data/sessions',
            '/../assets/uploads',
        ];
    }

    public static function getRequiredPhpVersion()
    {
        return '8.3.0';
    }

    public static function getRequiredExtensions()
    {
        return ['mbstring', 'zip', 'gd', 'pdo_sqlite', 'dom', 'libxml', 'openssl', 'json'];
    }

    public static function checkDirectory($path)
    {
        $fullPath = realpath(dirname(__DIR__) . $path) ?: (dirname(__DIR__) . $path);

        // Create directory
        if (!file_exists($fullPath)) {
            $baseName = basename($fullPath);
            if (in_array($baseName, ['data', 'logs', 'cache', 'tmp', 'sessions'])) {
                if (!grinds_secure_dir($fullPath)) {
                    return ['status' => 'missing', 'path' => $fullPath];
                }
            } else {
                if (!@mkdir($fullPath, 0775, true)) {
                    return ['status' => 'missing', 'path' => $fullPath];
                }
            }
        }

        if (!is_writable($fullPath)) {
            return ['status' => 'unwritable', 'path' => $fullPath];
        }

        return ['status' => 'ok', 'path' => $fullPath];
    }

    public static function getSqliteVersion()
    {
        if (extension_loaded('pdo_sqlite')) {
            try {
                $pdo = new PDO('sqlite::memory:');
                return $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
            } catch (Exception $e) {
                return '0.0.0';
            }
        }
        return '0.0.0';
    }

    public static function getHealthReport()
    {
        // Skip cache if installer exists
        $installerExists = defined('ROOT_PATH') && file_exists(ROOT_PATH . '/install.php');
        if (!$installerExists && self::$cachedReport !== null) {
            return self::$cachedReport;
        }

        // Use cache
        if (
            session_status() === PHP_SESSION_ACTIVE &&
            isset($_SESSION['grinds_health_report_cache']) &&
            $_SESSION['grinds_health_report_cache']['time'] > time() - 300 &&
            ($_SESSION['grinds_health_report_cache']['installer_present'] ?? null) === $installerExists
        ) {
            return $_SESSION['grinds_health_report_cache']['data'];
        }

        $checks = [];

        // Check PHP
        $minPhp = self::getRequiredPhpVersion();
        $phpVersion = phpversion();
        $checks[] = [
            'label' => _t('chk_php_ver'),
            'value' => $phpVersion,
            'status' => version_compare($phpVersion, $minPhp, '>=') ? 'ok' : 'danger',
            'msg' => version_compare($phpVersion, $minPhp, '<') ? "PHP {$minPhp}+ Required" : ''
        ];

        // Check debug
        if ((defined('DEBUG_MODE') && DEBUG_MODE) || function_exists('get_option') && get_option('debug_mode')) {
            $checks[] = [
                'label' => _t('alert_security_title'),
                'value' => 'Debug Mode',
                'status' => 'danger',
                'msg' => _t('alert_security_msg')
            ];
        }

        // Check extensions
        $exts = self::getRequiredExtensions();
        $exts = array_diff($exts, ['gd']);
        foreach ($exts as $ext) {
            $loaded = extension_loaded($ext);
            $checks[] = [
                'label' => "Ext: $ext",
                'value' => $loaded ? _t('val_installed') : _t('val_missing'),
                'status' => $loaded ? 'ok' : 'danger',
                'msg' => !$loaded ? _t('val_missing') : ''
            ];
        }

        // Check images
        $hasGd = extension_loaded('gd');
        $hasImagick = extension_loaded('imagick');
        $checks[] = [
            'label' => 'Image Library',
            'value' => $hasImagick ? 'ImageMagick' : ($hasGd ? 'GD' : _t('val_missing')),
            'status' => ($hasGd || $hasImagick) ? 'ok' : 'danger',
            'msg' => (!$hasGd && !$hasImagick) ? _t('msg_req_img') : ''
        ];

        // Check permissions
        $paths = self::getCriticalPaths();
        foreach ($paths as $path) {
            $res = self::checkDirectory($path);
            $displayPath = str_replace('/../', '/', $path);
            $checks[] = [
                'label' => "Dir: $displayPath",
                'value' => $res['status'] === 'ok' ? _t('val_writable') : _t('val_readonly'),
                'status' => $res['status'] === 'ok' ? 'ok' : 'danger',
                'msg' => $res['status'] !== 'ok' ? 'Permission denied' : ''
            ];
        }

        // Check SQLite
        if (extension_loaded('pdo_sqlite')) {
            $sqliteVer = self::getSqliteVersion();
            $minSqlite = '3.9.0';
            $recSqlite = '3.27.0';

            $chkSqliteVer = function_exists('_t') ? _t('chk_sqlite_ver') : 'SQLite Version';

            if (version_compare($sqliteVer, $minSqlite, '<')) {
                $checks[] = [
                    'label' => $chkSqliteVer,
                    'value' => $sqliteVer,
                    'status' => 'warning',
                    'msg' => (function_exists('_t') ? _t('adv_sqlite_req') : "Running in Legacy Mode (FTS5 disabled).") . "<br><strong>Warning:</strong> Do NOT upload a database file created on a newer local environment directly. Use the Migration Tool instead."
                ];
            } elseif (version_compare($sqliteVer, $recSqlite, '<')) {
                $checks[] = [
                    'label' => $chkSqliteVer,
                    'value' => $sqliteVer,
                    'status' => 'warning',
                    'msg' => function_exists('_t') ? _t('adv_sqlite_rec') : "v{$recSqlite}+ recommended for safe backups."
                ];
            } else {
                $checks[] = [
                    'label' => $chkSqliteVer,
                    'value' => $sqliteVer,
                    'status' => 'ok',
                    'msg' => ''
                ];
            }
        }

        // Check FTS5 support
        if (function_exists('grinds_is_fts5_enabled') && !grinds_is_fts5_enabled()) {
            $checks[] = [
                'label' => _t('chk_fts5_support'),
                'status' => 'warning',
                'msg' => _t('adv_fts5_disabled')
            ];
        }

        // Check journal mode
        $pdo = App::db();
        if ($pdo && function_exists('grinds_get_db_journal_mode')) {
            $mode = grinds_get_db_journal_mode();
            $checks[] = [
                'label' => 'DB Journal Mode',
                'value' => $mode,
                'status' => ($mode === 'WAL') ? 'ok' : 'warning',
                'msg' => ($mode !== 'WAL') ? 'WAL mode recommended.' : ''
            ];
        }

        // Check installer
        if (defined('ROOT_PATH') && file_exists(ROOT_PATH . '/install.php')) {
            $checks[] = [
                'label' => _t('chk_install_file'),
                'value' => 'install.php',
                'status' => 'danger',
                'msg' => _t('adv_install_del')
            ];
        }

        // Check dangerous files
        if (defined('ROOT_PATH')) {
            $detectedScan = function_exists('grinds_scan_dangerous_files') ? grinds_scan_dangerous_files() : ['danger' => [], 'warning' => []];

            if (!empty($detectedScan['danger'])) {
                $safe_danger = array_map(fn($f) => htmlspecialchars((string)$f, ENT_QUOTES, 'UTF-8'), $detectedScan['danger']);
                $file_list = implode(', ', $safe_danger);
                $checks[] = [
                    'label' => _t('alert_danger_files_title'),
                    'value' => 'Danger Files',
                    'status' => 'danger',
                    'msg' => _t('alert_danger_files_msg', $file_list)
                ];
            }

            if (!empty($detectedScan['warning'])) {
                $safe_warning = array_map(fn($f) => htmlspecialchars((string)$f, ENT_QUOTES, 'UTF-8'), $detectedScan['warning']);
                $file_list = implode(', ', $safe_warning);
                $checks[] = [
                    'label' => _t('alert_warning_files_title'),
                    'value' => 'OS Meta Files',
                    'status' => 'warning',
                    'msg' => _t('alert_warning_files_msg', $file_list)
                ];
            }
        }

        // Check config
        if (defined('ROOT_PATH')) {
            $isConfigWritable = is_writable(ROOT_PATH . '/config.php');
            $checks[] = [
                'label' => _t('chk_config_perm'),
                'value' => $isConfigWritable ? _t('val_writable') : _t('val_readonly'),
                'status' => $isConfigWritable ? 'warning' : 'ok',
                'msg' => $isConfigWritable ? _t('adv_config_ro') : ''
            ];
        }

        // Check Nginx
        if (isset($_SERVER['SERVER_SOFTWARE']) && str_contains(strtolower($_SERVER['SERVER_SOFTWARE']), 'nginx')) {
            $nginxRules = '';
            $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
            $relPath = dirname($scriptDir);
            if ($relPath === '/' || $relPath === '\\')
                $relPath = '';

            if (file_exists(ROOT_PATH . '/lib/nginx_helper.php')) {
                require_once ROOT_PATH . '/lib/nginx_helper.php';
                $nginxRules = grinds_get_nginx_uploads_rules($relPath) . "\n" . grinds_get_nginx_plugins_rules($relPath) . "\n" . grinds_get_nginx_security_rules($relPath);
                $nginxRules = nl2br(htmlspecialchars($nginxRules));
            }

            $nginxConfirmFile = ROOT_PATH . '/data/.nginx_confirmed';
            if (!empty($nginxRules) && !file_exists($nginxConfirmFile)) {
                $checks[] = [
                    'label' => 'Nginx Config',
                    'value' => 'Review',
                    'status' => 'warning',
                    'msg' => 'Recommended Nginx rules:<br><div style="max-height:150px;overflow:auto;font-size:0.85em;background:#f5f5f5;padding:5px;">' . $nginxRules . '</div>'
                ];
            }
        }

        self::$cachedReport = $checks;

        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['grinds_health_report_cache'] = [
                'time' => time(),
                'data' => $checks,
                'installer_present' => $installerExists
            ];
        }

        return $checks;
    }
}

/**
 * Render error page.
 */
if (!function_exists('grinds_render_error_page')) {
    function grinds_render_error_page($title, $message, $status = 'System Error', $responseCode = 500, $options = [])
    {
        if (!headers_sent()) {
            http_response_code($responseCode);
        }

        $lang = function_exists('grinds_detect_language') ? grinds_detect_language() : 'en';

        $defaults = [
            'reload' => ($lang === 'ja') ? 'ページを再読み込み' : 'Reload Page',
            'btn_back' => ($lang === 'ja') ? '前のページに戻る' : 'Return to Previous Page',
        ];

        $t = array_merge($defaults, $options);
        $t['title'] = $title;
        $t['heading'] = $title;
        $t['status'] = $status;
        $t['message'] = $message;

        $root = defined('ROOT_PATH') ? ROOT_PATH : dirname(dirname(__DIR__));
        $layoutFile = $root . '/lib/layout/error_config.php';

        if (file_exists($layoutFile)) {
            require $layoutFile;
        } else {
            echo "<h1>{$t['title']}</h1><p>{$t['message']}</p>";
        }
        exit;
    }
}

/**
 * Clear specific cache.
 */
if (!function_exists('grinds_clear_specific_cache')) {
    function grinds_clear_specific_cache(array $slugs)
    {
        $cacheDir = ROOT_PATH . '/data/cache/pages/';
        if (!is_dir($cacheDir)) return;

        foreach ($slugs as $slug) {
            $safeSlug = preg_replace('/[^a-zA-Z0-9_-]/', '_', $slug);

            grinds_force_unlink($cacheDir . $safeSlug . '.html');

            $files = glob($cacheDir . $safeSlug . '_*.html');
            if ($files) {
                foreach ($files as $file) {
                    grinds_force_unlink($file);
                }
            }
        }

        grinds_force_unlink($cacheDir . 'rss.xml');
        grinds_force_unlink($cacheDir . 'sitemap.xml');
        grinds_force_unlink($cacheDir . 'llms.txt');
        grinds_force_unlink($cacheDir . 'llms-full.txt');
    }
}

/**
 * Replace database file path in config.php content for migration.
 *
 * @param string $configContent Original content of config.php
 * @param string $newDbName New database filename
 * @return string Modified config content
 */
function grinds_prepare_migration_config(string $configContent, string $newDbName): string
{
    $safeDbName = addcslashes($newDbName, "'\\");
    $patternFile = '/^.*define\s*\(\s*[\'"]DB_FILE[\'"]\s*,.*?\)\s*;/m';
    $patternFilename = '/^.*define\s*\(\s*[\'"]DB_FILENAME[\'"]\s*,.*?\)\s*;/m';
    $replacementFilename = "if (!defined('DB_FILENAME')) define('DB_FILENAME', '" . $safeDbName . "');";
    $replacementFile = "if (!defined('DB_FILE')) define('DB_FILE', __DIR__ . '/data/' . DB_FILENAME);";

    // 1. Ensure DB_FILENAME is present (defined before DB_FILE)
    if (!preg_match($patternFilename, $configContent)) {
        if (preg_match($patternFile, $configContent)) {
            // If DB_FILE exists but DB_FILENAME does not, insert DB_FILENAME before DB_FILE
            $configContent = preg_replace($patternFile, addcslashes($replacementFilename . "\n", '\\$') . '$0', $configContent);
        } else {
            // If neither exists, append to the end
            $configContent = rtrim($configContent) . "\n\n" . $replacementFilename;
        }
    } else {
        $configContent = preg_replace($patternFilename, addcslashes($replacementFilename, '\\$'), $configContent);
    }

    // 2. Ensure DB_FILE is present/updated
    if (preg_match($patternFile, $configContent)) {
        $configContent = preg_replace($patternFile, addcslashes($replacementFile, '\\$'), $configContent);
    } else {
        $configContent = rtrim($configContent) . "\n" . $replacementFile . "\n";
    }

    return $configContent;
}

/**
 * Clear media cache.
 */
if (!function_exists('grinds_clear_media_cache')) {
    function grinds_clear_media_cache()
    {
        $session_started_here = false;

        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
            $session_started_here = true;
        }

        unset($_SESSION['grinds_media_months']);

        if ($session_started_here) {
            session_write_close();
        }
    }
}
