<?php

/**
 * Provide security functions
 * Handle escaping, CSRF protection, and session management.
 */
if (!defined('GRINDS_APP'))
    exit;

/** Escape string for HTML output. */
if (!function_exists('h')) {
    function h($s)
    {
        // 第4引数を false にすることで、既にエスケープされている文字（&amp;など）の二重エスケープを防ぐ
        return htmlspecialchars((string)$s, ENT_QUOTES | ENT_HTML5, 'UTF-8', false);
    }
}

/** Generate cryptographically secure pseudo-random bytes safely. */
if (!function_exists('grinds_random_bytes')) {
    function grinds_random_bytes($length)
    {
        try {
            return random_bytes($length);
        } catch (\Exception $e) {
            http_response_code(500);
            if (function_exists('grinds_render_error_page')) {
                $errTitle = function_exists('_t') ? _t('js_system_error') : 'System Error';
                $errMsg = function_exists('_t') ? _t('err_random_bytes') : 'Cannot generate a secure random token. Please check your server environment.';
                grinds_render_error_page($errTitle, $errMsg, 'Security Error', 500);
            } else {
                die('System Error: Cannot generate a secure random token.');
            }
        }
    }
}

/** Generate CSRF token. */
if (!function_exists('generate_csrf_token')) {
    function generate_csrf_token()
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(grinds_random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

/**
 * Validate CSRF token.
 */
if (!function_exists('validate_csrf_token')) {
    function validate_csrf_token($token)
    {
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }

        // Use hash_equals for timing attack resistance
        return hash_equals($_SESSION['csrf_token'], (string)$token);
    }
}

/**
 * Enforce CSRF token check.
 */
if (!function_exists('check_csrf_token')) {
    function check_csrf_token()
    {
        // 1. Check POST data
        $token = $_POST['csrf_token'] ?? '';

        // 2. Check JSON body
        if (empty($token)) {
            $input = get_json_input();
            if (is_array($input)) {
                $token = $input['csrf_token'] ?? '';
            }
        }
        // 3. Check request header (Common practice for AJAX)
        if (empty($token)) {
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        }

        if (!validate_csrf_token($token)) {
            http_response_code(403);
            $msg = function_exists('_t') ? _t('err_invalid_csrf_token') : 'Error: Invalid CSRF Token.';

            // Detect AJAX
            if (function_exists('is_ajax_request') && is_ajax_request() && function_exists('json_response')) {
                json_response(['success' => false, 'error' => $msg], 403);
            }

            die($msg);
        }
    }
}

/** Get the base path for cookies. */
if (!function_exists('_grinds_get_cookie_path')) {
    function _grinds_get_cookie_path()
    {
        if (defined('COOKIE_PATH')) return constant('COOKIE_PATH');
        $path = parse_url(BASE_URL, PHP_URL_PATH) ?: '/';
        return rtrim($path, '/') . '/';
    }
}

/** Set flash message. */
if (!function_exists('set_flash')) {
    function set_flash($msg, $type = 'success')
    {
        $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
    }
}

/** Retrieve and clear flash message. */
if (!function_exists('get_flash')) {
    function get_flash()
    {
        if (isset($_SESSION['flash'])) {
            $msg = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $msg;
        }
        return null;
    }
}

/** Start secure session. */
if (!function_exists('_safe_session_start')) {
    function _safe_session_start()
    {
        if (session_status() === PHP_SESSION_ACTIVE)
            return;

        // Configure portable session directory.
        $root = defined('ROOT_PATH') ? ROOT_PATH : str_replace('\\', '/', realpath(__DIR__ . '/../../'));
        $sessionDir = $root . '/data/sessions';

        if (!is_dir($sessionDir)) {
            if (@mkdir($sessionDir, 0775, true)) {
                // Secure session directory.
                @file_put_contents($sessionDir . '/.htaccess', "Require all denied\n");
            }
        }

        if (is_dir($sessionDir) && is_writable($sessionDir)) {
            @session_save_path($sessionDir);
        }

        // Apply security settings.
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_samesite', 'Lax');
        ini_set('session.use_strict_mode', 1);

        // Configure garbage collection.
        $gc_lifetime = 86400;
        if (function_exists('get_option')) {
            $val = get_option('session_timeout');
            if ($val)
                $gc_lifetime = (int)$val;
        }
        ini_set('session.gc_maxlifetime', $gc_lifetime);
        ini_set('session.gc_probability', 0);
        ini_set('session.gc_divisor', 100);

        $is_https = function_exists('is_ssl') ? is_ssl() : false;

        $cookieParams = session_get_cookie_params();
        $cookiePath = _grinds_get_cookie_path();

        session_set_cookie_params([
            // Use 0 (session) or explicit lifetime if needed, but inheriting php.ini is usually safer
            'lifetime' => $cookieParams['lifetime'],
            'path' => $cookiePath,
            'domain' => $cookieParams['domain'],
            'secure' => $is_https,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        // Set unique session name.
        $appKey = defined('APP_KEY') ? constant('APP_KEY') : $root;
        session_name('grinds_' . md5($appKey));

        session_start();

        // Periodic session ID regeneration to prevent hijacking
        // Regenerate ID every 15 minutes (900 seconds)
        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
        } elseif (time() - $_SESSION['last_regeneration'] > 900) {
            // Skip regeneration on AJAX requests to prevent race conditions (concurrent requests failing)
            $is_ajax = function_exists('is_ajax_request') && is_ajax_request();

            if (!$is_ajax) {
                session_regenerate_id(false);
                $_SESSION['last_regeneration'] = time();
            }
        }
    }
}

/**
 * Get encryption key.
 */
if (!function_exists('_grinds_get_encryption_key')) {
    function _grinds_get_encryption_key()
    {
        $key = defined('APP_KEY') ? constant('APP_KEY') : '';
        return hash('sha256', $key, true);
    }
}

/**
 * Encrypt data.
 */
if (!function_exists('grinds_encrypt')) {
    function grinds_encrypt($data)
    {
        if (empty($data))
            return $data;
        // Check if already encrypted
        if (str_starts_with($data, 'ENCv4:') || str_starts_with($data, 'ENCv3:') || str_starts_with($data, 'ENCv2:'))
            return $data;

        $key = _grinds_get_encryption_key();
        $cipher = 'aes-256-gcm';
        $iv = grinds_random_bytes(openssl_cipher_iv_length($cipher));
        $tag = "";
        // Use AES-256-GCM (AEAD) for authenticated encryption
        $encrypted = openssl_encrypt($data, $cipher, $key, OPENSSL_RAW_DATA, $iv, $tag);
        return 'ENCv4:' . base64_encode($iv . $tag . $encrypted);
    }
}

/**
 * Decrypt data.
 */
if (!function_exists('grinds_decrypt')) {
    function grinds_decrypt($data)
    {
        if (empty($data))
            return $data;

        $key = _grinds_get_encryption_key();

        // Handle v4 (AES-256-GCM)
        if (str_starts_with($data, 'ENCv4:')) {
            $payload = base64_decode(substr($data, 6));
            $cipher = 'aes-256-gcm';
            $iv_len = openssl_cipher_iv_length($cipher);
            $tag_len = 16;
            $iv = substr($payload, 0, $iv_len);
            $tag = substr($payload, $iv_len, $tag_len);
            $encrypted_data = substr($payload, $iv_len + $tag_len);

            $decrypted = openssl_decrypt($encrypted_data, $cipher, $key, OPENSSL_RAW_DATA, $iv, $tag);
            return $decrypted !== false ? $decrypted : '';
        }

        // Handle v3 (Binary key - Correct AES-256)
        if (str_starts_with($data, 'ENCv3:')) {
            $payload = base64_decode(substr($data, 6));
            $iv_len = openssl_cipher_iv_length('aes-256-cbc');
            $iv = substr($payload, 0, $iv_len);
            $encrypted_data = substr($payload, $iv_len);
            $decrypted = openssl_decrypt($encrypted_data, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
            return $decrypted !== false ? $decrypted : '';
        }

        // Handle v2 (Legacy Hex key)
        if (str_starts_with($data, 'ENCv2:')) {
            $hexKey = bin2hex($key);
            $payload = base64_decode(substr($data, 6));
            $iv_len = openssl_cipher_iv_length('aes-256-cbc');
            $iv = substr($payload, 0, $iv_len);
            $encrypted_data = substr($payload, $iv_len);
            $decrypted = openssl_decrypt($encrypted_data, 'aes-256-cbc', $hexKey, OPENSSL_RAW_DATA, $iv);
            return $decrypted !== false ? $decrypted : '';
        }

        return $data;
    }
}

/**
 * Check if request exceeds post_max_size.
 * Throws Exception if exceeded.
 */
if (!function_exists('grinds_check_post_max_size')) {
    function grinds_check_post_max_size()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && empty($_FILES) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {

            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            if (str_contains(strtolower($contentType), 'application/json')) {
                if (file_get_contents('php://input', false, null, 0, 1) !== '') {
                    return;
                }
            }

            $maxSize = ini_get('post_max_size');
            $msg = function_exists('_t') ? _t('err_upload_server_limit', $maxSize) : "Request exceeds server limit (post_max_size: {$maxSize}).";
            throw new Exception($msg);
        }
    }
}

/**
 * Check if a URL is trusted based on allowed domains.
 *
 * @param string $url
 * @param string $type 'iframe' or 'script'
 * @return bool
 */
if (!function_exists('grinds_is_trusted_url')) {
    function grinds_is_trusted_url($url, $type = 'iframe')
    {
        $allowed = [];
        if ($type === 'iframe') {
            // Default trusted domains (hardcoded for base security)
            // Added: Google/Office Forms, Spotify, SoundCloud, TikTok, Pinterest, Twitch, Speaker Deck, CodePen
            $default_domains_str = "youtube.com\nwww.youtube.com\nyoutube-nocookie.com\nwww.youtube-nocookie.com\nplayer.vimeo.com\ninstagram.com\nwww.instagram.com\ntwitter.com\nwww.twitter.com\nfacebook.com\nwww.facebook.com\nmaps.google.com\nwww.google.com\nopenstreetmap.org\nwww.openstreetmap.org\ncanva.com\nwww.canva.com\nfigma.com\nwww.figma.com\ndocs.google.com\nforms.gle\ncalendar.google.com\ndrive.google.com\nforms.office.com\nopen.spotify.com\nw.soundcloud.com\ntiktok.com\nwww.tiktok.com\npinterest.com\nplayer.twitch.tv\nspeakerdeck.com\ncodepen.io";
            $default_domains = array_filter(array_map('trim', explode("\n", $default_domains_str)));

            // Get additional domains from admin settings
            $additional_domains_raw = function_exists('get_option') ? get_option('iframe_allowed_domains', '') : '';
            $additional_domains = array_filter(array_map('trim', explode("\n", (string)$additional_domains_raw)));

            // Merge and deduplicate
            $allowed = array_unique(array_merge($default_domains, $additional_domains));
        } elseif ($type === 'script') {
            // Default allowed domains for scripts
            $allowed = ['platform.twitter.com', 'connect.facebook.net', 'www.instagram.com'];
        }

        if (empty($allowed)) return false;

        $parsed = parse_url($url);
        if (!isset($parsed['host'])) return false;
        $host = strtolower($parsed['host']);

        foreach ($allowed as $domain) {
            if ($host === $domain || str_ends_with($host, '.' . $domain)) {
                return true;
            }
        }

        return false;
    }
}





/**
 * Validate content security.
 * Throws Exception if malicious or untrusted content is found.
 */
if (!function_exists('grinds_validate_content_security')) {
    function grinds_validate_content_security($content)
    {
        $json = json_decode($content, true);
        if (is_array($json) && isset($json['blocks'])) {
            foreach ($json['blocks'] as $block) {
                $type = $block['type'] ?? '';
                if (isset($block['data']) && is_array($block['data'])) {
                    array_walk_recursive($block['data'], function ($value, $key) use ($type) {
                        if (!is_string($value)) return;
                        if (in_array($type, ['code', 'math']) && $key === 'code') return;
                        _grinds_validate_string_security($value);
                    });
                }
            }
            return;
        }
        _grinds_validate_string_security($content);
    }
}

if (!function_exists('_grinds_validate_string_security')) {
    function _grinds_validate_string_security($str)
    {
        $checkStr = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($match) {
            return json_decode('"\u' . $match[1] . '"');
        }, $str);

        if (preg_match('/<\s*\/?\s*(object|embed|applet|form|link|meta)\b/i', $checkStr)) {
            throw new Exception(function_exists('_t') ? _t('err_security_malicious_code') : 'Security Error: Restricted tags detected.');
        }
        if (preg_match('/(javascript:|vbscript:|data:text\/html)/i', $checkStr) || preg_match('/\bon[a-z]+\s*=/i', $checkStr)) {
            throw new Exception(function_exists('_t') ? _t('err_security_malicious_code') : 'Security Error: Malicious code detected.');
        }
        if (preg_match_all('/<\s*(iframe|script)\b[^>]*+src\s*=\s*["\']([^"\']+)["\'][^>]*+>/i', $checkStr, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $tag = strtolower($match[1]);
                $src = $match[2];
                if (!grinds_is_trusted_url($src, $tag)) {
                    throw new Exception(function_exists('_t') ? _t('err_security_malicious_code') : "Security Error: Untrusted domain ($src).");
                }
            }
        }
        if (preg_match('/<\s*script\b(?![^>]*+\bsrc\s*=)/i', $checkStr)) {
            throw new Exception(function_exists('_t') ? _t('err_security_malicious_code') : 'Security Error: Inline scripts are not allowed.');
        }
    }
}

/**
 * Sanitize post content (JSON or HTML) for restricted users.
 * Prevents JSON corruption by DOMDocument when using Block Editor.
 *
 * @param string $content
 * @return string
 */
if (!function_exists('grinds_sanitize_post_content')) {
    function grinds_sanitize_post_content($content)
    {
        // 1. Try to decode as JSON (Block Editor content)
        $json = json_decode($content, true);
        if (is_array($json) && isset($json['blocks'])) {
            if (function_exists('grinds_sanitize_post_content_array')) {
                $json = grinds_sanitize_post_content_array($json);
            }
            // Re-encode without escaping Unicode/Slashes to preserve format
            return json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        // 2. Not JSON (Classic Editor or raw HTML) - Fallback to standard sanitization
        if (function_exists('grinds_sanitize_html') && preg_match('/<[a-zA-Z\/!]/', (string)$content)) {
            return grinds_sanitize_html($content);
        }

        return (string)$content;
    }
}

/**
 * Sanitize decoded JSON array structure directly to optimize performance.
 */
if (!function_exists('grinds_sanitize_post_content_array')) {
    function grinds_sanitize_post_content_array(array $json)
    {
        if (isset($json['blocks']) && is_array($json['blocks'])) {
            foreach ($json['blocks'] as &$block) {
                $type = $block['type'] ?? '';
                if (isset($block['data']) && is_array($block['data'])) {
                    array_walk_recursive($block['data'], function (&$value, $key) use ($type) {
                        if (!is_string($value)) return;

                        if (in_array($type, ['code', 'math']) && $key === 'code') return;

                        if (in_array($key, ['url', 'image', 'thumbnail', 'link', 'citeUrl', 'href', 'src'])) {
                            $value = strip_tags($value);
                            if (preg_match('/^\s*(javascript|vbscript|data(?!:image)):/i', $value)) {
                                $value = '#';
                            }
                            return;
                        }

                        if (function_exists('grinds_sanitize_html') && preg_match('/<[a-zA-Z\/!]/', $value)) {
                            $value = grinds_sanitize_html($value);
                        }
                    });
                }
            }
        }
        return $json;
    }
}
