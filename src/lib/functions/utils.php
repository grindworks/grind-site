<?php

/**
 * Provide utility functions
 * Handle string manipulation, file operations, and URL abstraction.
 */
if (!defined('GRINDS_APP'))
    exit;

/** Sanitize string for slug usage. */
if (!function_exists('sanitize_slug')) {
    function sanitize_slug(string $slug): string
    {
        // Trim whitespace.
        $slug = trim($slug);

        // Replace whitespace and dangerous chars with hyphens.
        $slug = str_replace(['/', '\\', '.'], '-', $slug);
        $slug = preg_replace('/[\s　]+/u', '-', $slug);

        // Check if ASCII slugs are enforced
        $enforceAscii = defined('GRINDS_ENFORCE_ASCII_SLUGS') && constant('GRINDS_ENFORCE_ASCII_SLUGS');

        // Sanitize characters.
        $pattern = $enforceAscii ? '/[^a-zA-Z0-9\-\_]+/' : '/[^\p{L}\p{N}\-\_]+/u';
        $slug = preg_replace($pattern, '', $slug);

        // Collapse consecutive hyphens.
        $slug = preg_replace('/-{2,}/', '-', $slug);

        // Convert to lowercase.
        $slug = mb_strtolower($slug, 'UTF-8');

        // Trim length to prevent extremely long URLs
        $slug = mb_substr($slug, 0, 100, 'UTF-8');

        // Trim leading/trailing hyphens.
        $slug = trim($slug, '-');

        return $slug;
    }
}

/**
 * Get list of reserved slugs.
 *
 * @return array
 */
if (!function_exists('grinds_get_reserved_slugs')) {
    function grinds_get_reserved_slugs(): array
    {
        return [
            'admin',
            'api',
            'assets',
            'lib',
            'plugins',
            'theme',
            'data',
            'search',
            'category',
            'tag',
            'rss',
            'sitemap',
            'robots',
            'index',
            'login',
            'logout',
            'install',
            'config',
            '404'
        ];
    }
}

/** Generate URL-friendly slug. */
if (!function_exists('generate_slug')) {
    function generate_slug(string $title, ?string $id = null, string $prefix = 'post-'): string
    {
        $slug = sanitize_slug($title);

        // Fallback for empty slugs.
        if ($slug === '') {
            $slug = $prefix . ($id ?? bin2hex(random_bytes(6)));
        }

        // Check for reserved slugs
        $reserved = function_exists('grinds_get_reserved_slugs') ? grinds_get_reserved_slugs() : [];
        if (in_array(strtolower($slug), $reserved)) {
            $slug = $prefix . bin2hex(random_bytes(6));
        }

        return (string)apply_filters('grinds_generate_slug', $slug, $title, $id);
    }
}

/**
 * Parse tag string into array.
 * Handles comma and Japanese delimiters (、, 　).
 * Note: Tags cannot contain commas as they are used as delimiters.
 *
 * @param string $input
 * @return array List of tag names.
 */
if (!function_exists('grinds_parse_tag_string')) {
    function grinds_parse_tag_string(string $input): array
    {
        if (empty($input)) {
            return [];
        }

        // Users can now use full-width spaces inside a single tag name.
        $normalized = str_replace(['、', '，'], ',', $input);

        // Split and clean
        $tags = explode(',', $normalized);
        $tags = array_map(function ($v) {
            return preg_replace('/^[\s　]+|[\s　]+$/u', '', $v);
        }, $tags);
        $tags = array_filter($tags, function ($v) {
            return strlen($v) > 0;
        });

        return array_values(array_unique($tags));
    }
}

/**
 * Normalize slug for SSG usage.
 *
 * @param string $slug
 * @return string
 */
if (!function_exists('grinds_ssg_normalize_slug')) {
    function grinds_ssg_normalize_slug(string $slug): string
    {
        return mb_strtolower($slug, 'UTF-8');
    }
}

/** Convert HEX/RGBA to RGB string. */
if (!function_exists('hex2rgb')) {
    function hex2rgb($hex)
    {
        if (!is_string($hex) || empty($hex))
            return "0 0 0";

        static $cache = [];
        if (isset($cache[$hex])) {
            return $cache[$hex];
        }

        $key = $hex;
        $hex = trim($hex);

        // Check for rgba() or rgb() strings
        if (preg_match('/^rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)(?:\s*,\s*([\d\.]+))?\s*\)$/i', $hex, $m)) {
            $r = (int)$m[1];
            $g = (int)$m[2];
            $b = (int)$m[3];
            $a = isset($m[4]) ? (float)$m[4] : null;
            if ($a !== null) {
                return $cache[$key] = "$r $g $b / $a";
            }
            return $cache[$key] = "$r $g $b";
        }

        $hex = str_replace('#', '', $hex);
        if (!preg_match('/^[a-fA-F0-9]+$/', $hex)) {
            return $cache[$key] = "0 0 0";
        }

        if (strlen($hex) == 3 || strlen($hex) == 4) {
            $r = hexdec($hex[0] . $hex[0]);
            $g = hexdec($hex[1] . $hex[1]);
            $b = hexdec($hex[2] . $hex[2]);
            if (strlen($hex) == 4) {
                $a = round(hexdec($hex[3] . $hex[3]) / 255, 3);
                return $cache[$key] = "$r $g $b / $a";
            }
        } elseif (strlen($hex) == 6 || strlen($hex) == 8) {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
            if (strlen($hex) == 8) {
                $a = round(hexdec(substr($hex, 6, 2)) / 255, 3);
                return $cache[$key] = "$r $g $b / $a";
            }
        } else {
            return $cache[$key] = "0 0 0";
        }
        return $cache[$key] = "$r $g $b";
    }
}

/** Extract plain text from content. */
if (!function_exists('grinds_extract_text_from_content')) {
    function grinds_extract_text_from_content(?string $content): string
    {
        if (empty($content)) {
            return '';
        }

        // Helper to clean text: replace breaks with spaces before stripping tags
        $clean = function (?string $s): string {
            if ($s === null || $s === '') {
                return '';
            }

            // Optimization: If no tags are left, we can just collapse whitespace and return.
            if (!str_contains($s, '<')) {
                return trim(preg_replace('/\s+/u', ' ', html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
            }

            // Add spaces after block-level closing tags and break tags to prevent words from merging.
            $s = preg_replace('/<(br|hr)\s*\/?\s*>/i', ' ', $s) ?? $s;
            $s = preg_replace('/(<\/(p|div|h[1-6]|li|dt|dd|blockquote|td|th|section|article|aside|nav|details|summary)\s*>)/i', '$1 ', $s) ?? $s;
            // Add spaces before block-level opening tags to prevent words from merging (e.g. ...</span><p>... )
            $s = preg_replace('/(<(p|div|h[1-6]|li|dt|dd|blockquote|td|th|section|article|aside|nav|details|summary)\b[^>]*+>)/i', ' $1', $s) ?? $s;

            if (class_exists('DOMDocument')) {
                $dom = new DOMDocument();
                $internalErrors = libxml_use_internal_errors(true);

                if (@$dom->loadHTML('<meta http-equiv="Content-Type" content="text/html; charset=utf-8">' . $s, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD)) {
                    // Remove script and style tags to prevent their content from appearing in text.
                    $xpath = new DOMXPath($dom);
                    foreach ($xpath->query('//script|//style') as $node) {
                        $node->parentNode->removeChild($node);
                    }
                    $s = $dom->textContent;
                }
                libxml_clear_errors();
                libxml_use_internal_errors($internalErrors);
            } else {
                // Fallback for environments without DOMDocument.
                $s = strip_tags($s);
            }

            // Decode entities at the end to prevent "A < B" from being stripped as a tag
            $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            return trim(preg_replace('/\s+/u', ' ', $s) ?? $s);
        };

        // Attempt to decode JSON blocks (e.g., from Editor.js).
        $cleanContent = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        $data = json_validate($cleanContent) ? json_decode($cleanContent, true) : null;

        if (is_array($data) && isset($data['blocks'])) {
            $textParts = [];
            foreach ($data['blocks'] as $block) {
                // Exclude block types that are noise for search/excerpts.
                $type = $block['type'] ?? '';
                if (in_array($type, ['html', 'map', 'embed'])) {
                    continue;
                }

                if ($type === 'password_protect') {
                    break;
                }

                if (!empty($block['data']) && is_array($block['data'])) {
                    array_walk_recursive($block['data'], function ($value, $key) use (&$textParts, $clean) {
                        if (is_string($value) || is_numeric($value)) {
                            $stringValue = (string)$value;

                            $isNoise = preg_match('/^https?:\/\/[^\s]+$/i', $stringValue)
                                || preg_match('/^\/assets\/uploads\//i', $stringValue)
                                || preg_match('/^#[0-9a-fA-F]{3,8}$/i', $stringValue)
                                || preg_match('/^data:image\//i', $stringValue);

                            if ($stringValue !== '' && !$isNoise) {
                                $cleanedText = $clean($stringValue);
                                if ($cleanedText !== '') {
                                    $textParts[] = $cleanedText;
                                }
                            }
                        }
                    });
                }
            }
            return implode(' ', $textParts);
        }

        // Fallback to cleaning the content as raw HTML.
        return $clean($content);
    }
}

/**
 * Extract URLs from content (JSON blocks or HTML).
 *
 * @param string|array $content
 * @return array List of URLs.
 */
if (!function_exists('grinds_extract_urls')) {
    function grinds_extract_urls($content)
    {
        $urls = [];
        if (empty($content))
            return $urls;

        // Decode JSON
        $cleanContent = is_string($content) ? preg_replace('/^\xEF\xBB\xBF/', '', $content) : $content;
        $data = is_array($cleanContent) ? $cleanContent : (is_string($cleanContent) && json_validate($cleanContent) ? json_decode($cleanContent, true) : null);

        if (is_array($data) && isset($data['blocks'])) {
            // Search URLs recursively in Editor.js blocks
            $finder = function ($item, $depth = 0) use (&$finder, &$urls) {
                if ($depth > 50) {
                    return;
                }

                if (is_array($item)) {
                    foreach ($item as $key => $value) {
                        if (is_string($key) && preg_match('/(url|link|href|src|image)/i', $key) && is_string($value)) {
                            if (!preg_match('/^data:/i', $value)) {
                                $urls[] = $value;
                            }
                        }
                        // Check HTML content fields
                        if (is_string($value) && ($key === 'text' || $key === 'content' || $key === 'code' || $key === 'caption')) {
                            if (preg_match_all('/(href|src)\s*=\s*["\']([^"\']+)["\']/i', $value, $matches)) {
                                foreach ($matches[2] as $u) {
                                    if (!preg_match('/^data:/i', $u)) $urls[] = $u;
                                }
                            }
                            if (preg_match_all('/url\(\s*[\'"]?([^)\'"]+)[\'"]?\s*\)/i', $value, $cssMatches)) {
                                foreach ($cssMatches[1] as $u) {
                                    if (!preg_match('/^data:/i', $u)) $urls[] = $u;
                                }
                            }
                        }
                        if (is_array($value)) {
                            $finder($value, $depth + 1);
                        }
                    }
                }
            };
            $finder($data['blocks'], 0);
        } elseif (is_string($content)) {
            // Check raw HTML (exclude data: URIs to prevent memory bloat and regex backtrack limit errors)
            if (preg_match_all('/(?:href|src)\s*=\s*["\']([^"\']+)["\']/i', $content, $matches)) {
                foreach ($matches[1] as $u) {
                    if (!preg_match('/^data:/i', $u)) $urls[] = $u;
                }
            }
            if (preg_match_all('/url\(\s*[\'"]?([^)\'"]+)[\'"]?\s*\)/i', $content, $matches)) {
                foreach ($matches[1] as $u) {
                    if (!preg_match('/^data:/i', $u)) $urls[] = $u;
                }
            }
        }

        return array_unique($urls);
    }
}


/** Restore URL for view display. */
if (!function_exists('grinds_url_to_view')) {
    function grinds_url_to_view($content)
    {
        return Routing::restoreViewUrl($content);
    }
}

/** Resolve relative path to absolute URL. */
if (!function_exists('resolve_url')) {
    function resolve_url($path = '')
    {
        return Routing::resolveUrl($path);
    }
}

/** Convert shorthand byte notation. */
if (!function_exists('grinds_return_bytes')) {
    function grinds_return_bytes($val)
    {
        $val = trim($val);
        if ($val === '')
            return 0;

        $last = strtolower($val[strlen($val) - 1]);
        $val = (int)$val;

        switch ($last) {
            case 'g':
                return $val * (1024 ** 3);
            case 'm':
                return $val * (1024 ** 2);
            case 'k':
                return $val * 1024;
            default:
                return $val;
        }
    }
}

/** Determine maximum upload size. */
if (!function_exists('grinds_get_max_upload_size')) {
    function grinds_get_max_upload_size()
    {
        $max_upload = grinds_return_bytes(ini_get('upload_max_filesize'));
        $max_post = grinds_return_bytes(ini_get('post_max_size'));

        if ($max_upload === 0 && $max_post === 0) {
            return 1024 * 1024 * 1024;
        }
        if ($max_upload === 0) {
            return $max_post;
        }
        if ($max_post === 0) {
            return $max_upload;
        }
        return min($max_upload, $max_post);
    }
}

/**
 * Replace URLs in CSS content.
 *
 * @param string $content CSS content.
 * @param callable $callback Function to transform URL. Receives (string $url). Returns string.
 * @return string
 */
if (!function_exists('grinds_replace_css_urls')) {
    function grinds_replace_css_urls($content, $callback)
    {
        if (empty($content) || !is_string($content))
            return $content;

        $result = preg_replace_callback('/url\(\s*(?>([\'"]?))(?!data:|https?:|\/\/)([^\'")]*)\1\s*\)/i', function ($matches) use ($callback) {
            $quote = $matches[1] ?: '"';
            $url = trim($matches[2]);
            $newUrl = $callback($url);
            return "url({$quote}{$newUrl}{$quote})";
        }, $content);

        return $result !== null ? $result : $content;
    }
}

/** Check if IP is in CIDR range. */
if (!function_exists('grinds_ip_in_range')) {
    function grinds_ip_in_range(string $ip, string $range): bool
    {
        if (!str_contains($range, '/')) {
            return $ip === $range;
        }
        list($subnet, $bits) = explode('/', $range);

        // IPv4
        if (str_contains($ip, '.') && str_contains($subnet, '.')) {
            $ipLong = ip2long($ip);
            $subnetLong = ip2long($subnet);
            if ($ipLong === false || $subnetLong === false)
                return false;
            $mask = -1 << (32 - $bits);
            return ($ipLong & $mask & 0xFFFFFFFF) === ($subnetLong & $mask & 0xFFFFFFFF);
        }

        // IPv6
        if (str_contains($ip, ':') && str_contains($subnet, ':') && defined('AF_INET6')) {
            $ip = inet_pton($ip);
            $subnet = inet_pton($subnet);
            if ($ip === false || $subnet === false)
                return false;

            $bytes = (int)($bits / 8);
            $bits = $bits % 8;

            for ($i = 0; $i < $bytes; $i++)
                if ($ip[$i] != $subnet[$i])
                    return false;
            if ($bits > 0)
                return (ord($ip[$bytes]) >> (8 - $bits)) == (ord($subnet[$bytes]) >> (8 - $bits));
            return true;
        }
        return false;
    }
}

/** Check if proxy should be trusted. */
if (!function_exists('is_proxy_trusted')) {
    function is_proxy_trusted()
    {
        return defined('TRUST_PROXIES') && TRUST_PROXIES;
    }
}

/** Get client IP address. */
if (!function_exists('get_client_ip')) {
    function get_client_ip(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        // Check if proxy trust is enabled via config or settings
        $trust = is_proxy_trusted();

        // Validate against trusted proxy IPs if defined
        if ($trust) {
            $trustedIps = [];
            if (defined('TRUSTED_PROXY_IPS') && TRUSTED_PROXY_IPS !== '') {
                $trustedIps = is_array(TRUSTED_PROXY_IPS)
                    ? TRUSTED_PROXY_IPS
                    : explode(',', (string)TRUSTED_PROXY_IPS);
                $trustedIps = array_filter(array_map('trim', $trustedIps));
            }

            // Fail-Safe: If proxy trust is enabled but no trusted IPs are defined, do not trust headers.
            if (empty($trustedIps)) {
                $trust = false;
            } else {
                $isTrusted = false;
                foreach ($trustedIps as $range) {
                    if (grinds_ip_in_range($ip, trim($range))) {
                        $isTrusted = true;
                        break;
                    }
                }
                if (!$isTrusted) {
                    $trust = false;
                }
            }
        }

        if ($trust) {
            if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
                return $_SERVER['HTTP_CF_CONNECTING_IP'];
            }

            $headers = [
                'HTTP_CLIENT_IP',
                'HTTP_X_FORWARDED_FOR',
                'HTTP_X_FORWARDED',
                'HTTP_X_CLUSTER_CLIENT_IP',
                'HTTP_FORWARDED_FOR',
                'HTTP_FORWARDED'
            ];

            foreach ($headers as $key) {
                if (array_key_exists($key, $_SERVER) === true) {
                    $ips = array_map('trim', explode(',', $_SERVER[$key]));

                    $ips = array_reverse($ips);

                    foreach ($ips as $possible_ip) {
                        if (filter_var($possible_ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                            if (!empty($trustedIps)) {
                                $isProxyTrusted = false;
                                foreach ($trustedIps as $range) {
                                    if (grinds_ip_in_range($possible_ip, trim($range))) {
                                        $isProxyTrusted = true;
                                        break;
                                    }
                                }
                                if ($isProxyTrusted) {
                                    continue;
                                }
                            }

                            return $possible_ip;
                        }
                    }
                }
            }
        }

        return $ip;
    }
}

/** Redirect to specified URL. */
if (!function_exists('redirect')) {
    function redirect($path)
    {
        $url = resolve_url($path);

        if (!headers_sent()) {
            header("Location: $url");
            exit;
        }

        // Fallback for sent headers.
        echo "<script>window.location.href=" . json_encode($url, JSON_INVALID_UTF8_IGNORE) . ";</script>";
        echo "<noscript><meta http-equiv='refresh' content='0;url=" . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . "'></noscript>";
        exit;
    }
}

/**
 * Generate URL for current list view.
 *
 * @param string|null $scriptName
 * @return string
 */
if (!function_exists('grinds_get_current_list_url')) {
    function grinds_get_current_list_url($scriptName = null)
    {
        if ($scriptName === null) {
            $scriptName = basename($_SERVER['SCRIPT_NAME']);
        }

        $params = Routing::getParams();

        // Remove action-related parameters
        $exclude = [
            'action',
            'id',
            'edit_id',
            'target_id',
            'saved'
        ];

        foreach ($exclude as $key) {
            unset($params[$key]);
        }

        $query = http_build_query($params);
        return $scriptName . ($query ? '?' . $query : '');
    }
}

/**
 * Force delete file with retry logic.
 *
 * @param string $path Absolute path to file.
 * @return bool True on success.
 */
if (!function_exists('grinds_force_unlink')) {
    function grinds_force_unlink($path)
    {
        if (!file_exists($path))
            return true;

        if (function_exists('opcache_invalidate'))
            @opcache_invalidate($path, true);

        if (@unlink($path))
            return true;

        // Retry for Windows file locking
        usleep(100000);

        if (@unlink($path))
            return true;

        error_log("grinds_force_unlink - Failed to delete file (locked?): " . $path);
        return false;
    }
}

/**
 * Determine if color is dark.
 *
 * @param string $rgb_str Space-separated RGB values (e.g., "255 255 255").
 * @return bool True if dark, false if light.
 */
if (!function_exists('is_dark')) {
    function is_dark($rgb_str)
    {
        $rgb = explode(' ', $rgb_str);
        if (count($rgb) < 3)
            return false;
        $yiq = (((int)$rgb[0] * 299) + ((int)$rgb[1] * 587) + ((int)$rgb[2] * 114)) / 1000;
        return ($yiq < 128);
    }
}

/**
 * Send JSON response and exit.
 *
 * @param mixed $data Data to encode.
 * @param int $status HTTP status code.
 */
if (!function_exists('json_response')) {
    function json_response($data, $status = 200)
    {
        // Clear all output buffers to ensure clean JSON output
        while (ob_get_level())
            ob_end_clean();

        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_IGNORE);
        exit;
    }
}

/**
 * Decode Base64 encoded post content.
 *
 * @param string $content
 * @return string
 */
if (!function_exists('grinds_decode_post_content')) {
    function grinds_decode_post_content($content)
    {
        if (empty($content))
            return $content;

        // Optimization: If content starts with { or [, it is likely JSON and definitely not Base64.
        $firstChar = substr(ltrim($content), 0, 1);
        if ($firstChar === '{' || $firstChar === '[') {
            return $content;
        }

        $contentSafe = str_replace(' ', '+', $content);
        return base64_decode($contentSafe);
    }
}

/**
 * Resolve absolute path for DB_FILE.
 *
 * @return string Absolute path to database file.
 */
if (!function_exists('grinds_get_db_path')) {
    function grinds_get_db_path()
    {
        if (!defined('DB_FILE'))
            return '';
        return DB_FILE;
    }
}

/**
 * Create database snapshot.
 *
 * @param string $destination Destination file path.
 * @return bool True on success.
 * @throws Exception On failure.
 */
if (!function_exists('grinds_db_snapshot')) {
    function grinds_db_snapshot($destination)
    {
        if (file_exists($destination)) {
            grinds_force_unlink($destination);
        }

        $db_path = grinds_get_db_path();

        try {
            $pdo_backup = new PDO("sqlite:" . $db_path);
            $pdo_backup->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Set busy timeout to prevent locking errors during backup
            $pdo_backup->exec("PRAGMA busy_timeout = 60000;");

            // Checkpoint WAL to minimize contention and ensure clean backup
            try {
                $pdo_backup->exec("PRAGMA wal_checkpoint(TRUNCATE);");
            } catch (Exception $e) {
                // Continue even if checkpoint fails (e.g. locked by another process), VACUUM INTO might still work
            }

            $safe_dest = str_replace("'", "''", $destination);

            try {
                $pdo_backup->exec("VACUUM INTO '$safe_dest'");
            } catch (Exception $e) {
                // Fallback for SQLite < 3.27.0
                try {
                    $pdo_backup->exec("BEGIN EXCLUSIVE;");

                    if (!copy($db_path, $destination)) {
                        throw new Exception("Failed to copy main DB file.");
                    }
                    if (file_exists($db_path . '-wal')) {
                        @copy($db_path . '-wal', $destination . '-wal');
                    }
                    if (file_exists($db_path . '-shm')) {
                        @copy($db_path . '-shm', $destination . '-shm');
                    }

                    $pdo_backup->exec("COMMIT;");
                } catch (Exception $fallback_e) {
                    throw new Exception("VACUUM INTO failed: " . $e->getMessage() . " AND Fallback failed: " . $fallback_e->getMessage());
                }
            }
            $pdo_backup = null;
        } catch (Exception $e) {
            $pdo_backup = null;
            throw new Exception("Failed to create DB snapshot: " . $e->getMessage());
        }
        return true;
    }
}

/**
 * Rotate backup files.
 *
 * @param string $prefix File prefix (e.g. 'grinds_backup_')
 * @param int $limit Number of files to keep
 * @return void
 */
if (!function_exists('grinds_rotate_backups')) {
    function grinds_rotate_backups($prefix, $limit)
    {
        $backup_dir = ROOT_PATH . '/data/backups/';
        if (!is_dir($backup_dir))
            return;

        $backups = [];
        try {
            foreach (new DirectoryIterator($backup_dir) as $fileInfo) {
                if ($fileInfo->isFile()) {
                    if (str_starts_with($fileInfo->getFilename(), $prefix) && $fileInfo->getExtension() === 'db') {
                        $backups[] = $fileInfo->getPathname();
                    }
                }
            }
        } catch (Exception $e) {
            return;
        }

        if (count($backups) <= $limit)
            return;

        rsort($backups);
        $filesToDelete = array_slice($backups, $limit);

        foreach ($filesToDelete as $f) {
            if (is_file($f)) {
                grinds_force_unlink($f);
            }
        }
    }
}

/**
 * Create database backup.
 *
 * @param string $filename Backup filename
 * @return bool True on success
 * @throws Exception
 */
if (!function_exists('grinds_create_backup')) {
    function grinds_create_backup($filename)
    {
        // Disconnect global connection to release lock
        global $pdo;
        $pdo = null;
        if (class_exists('App')) {
            App::bind('db', null);
        }

        try {
            $backup_dir = ROOT_PATH . '/data/backups/';
            if (!function_exists('grinds_secure_dir')) {
                // Fallback if system.php not loaded yet (unlikely)
                if (!is_dir($backup_dir))
                    @mkdir($backup_dir, 0775, true);
            } else {
                if (!grinds_secure_dir($backup_dir)) {
                    throw new Exception("Failed to create backup directory.");
                }
            }

            $dest = $backup_dir . $filename;
            grinds_db_snapshot($dest);
        } finally {
            // Reconnect global connection
            if (function_exists('grinds_db_connect')) {
                $pdo = grinds_db_connect();
                if (class_exists('App')) {
                    App::bind('db', $pdo);
                }
            }
        }
        return true;
    }
}

/**
 * Recursively copy files and directories.
 *
 * @param string $src Source path.
 * @param string $dst Destination path.
 * @param array $exclude Items to exclude.
 * @return void
 */
if (!function_exists('grinds_recursive_copy')) {
    function grinds_recursive_copy($src, $dst, $exclude = [])
    {
        if (!is_dir($src))
            return;

        // Normalize source path to avoid case/separator issues on Windows
        $realSrc = realpath($src) ?: $src;

        if (!is_dir($dst) && !@mkdir($dst, 0775, true)) {
            throw new Exception("Failed to create destination directory: " . $dst);
        }

        // System files to always exclude by filename
        $system_excludes = ['.DS_Store', 'Thumbs.db'];

        // Default paths to exclude (relative to src root)
        $default_path_excludes = ['.git', 'node_modules'];
        $exclude = array_merge($default_path_excludes, $exclude);

        // Normalize exclude paths
        $exclude = array_map(function ($p) {
            return str_replace('\\', '/', trim($p, '/\\'));
        }, $exclude);

        $filter = new RecursiveCallbackFilterIterator(
            new RecursiveDirectoryIterator($realSrc, RecursiveDirectoryIterator::SKIP_DOTS),
            function ($current, $key, $iterator) use ($exclude, $system_excludes, $realSrc) {
                // Skip symlinks to avoid infinite loops or copying links as empty dirs
                if ($current->isLink())
                    return false;

                // Check system files by filename
                if (in_array($current->getFilename(), $system_excludes, true)) {
                    return false;
                }

                // Check exclusions by relative path
                $itemPath = str_replace('\\', '/', $current->getPathname());
                $srcPath = str_replace('\\', '/', $realSrc);
                $subPath = ltrim(substr($itemPath, strlen($srcPath)), '/');

                foreach ($exclude as $ex) {
                    if ($subPath === $ex || str_starts_with($subPath, $ex . '/')) {
                        return false;
                    }
                }
                return true;
            }
        );

        $iterator = new RecursiveIteratorIterator($filter, RecursiveIteratorIterator::SELF_FIRST);

        $normalizedSrcPath = str_replace('\\', '/', $realSrc);
        $normalizedSrcLen = strlen($normalizedSrcPath);

        foreach ($iterator as $item) {
            $normalizedItemPath = str_replace('\\', '/', $item->getPathname());
            $subPath = ltrim(substr($normalizedItemPath, $normalizedSrcLen), '/');
            $target = $dst . '/' . $subPath;
            if ($item->isDir()) {
                if (file_exists($target) && !is_dir($target)) {
                    grinds_force_unlink($target);
                }
                if (!is_dir($target) && !@mkdir($target, 0775, true)) {
                    throw new Exception("Failed to create directory: " . $target);
                }
            } else {
                $sourcePath = $item->getRealPath() ?: $item->getPathname();
                if (!@copy($sourcePath, $target)) {
                    throw new Exception("Failed to copy file: " . $sourcePath);
                }
            }
        }
    }
}

/**
 * Recursively delete directory and contents.
 *
 * @param string $dir Path to directory.
 * @return bool
 */
if (!function_exists('grinds_delete_tree')) {
    function grinds_delete_tree($dir)
    {
        if (!is_dir($dir))
            return false;

        // Safety lock: Prevent deleting root directory
        $realDir = rtrim(str_replace('\\', '/', (string)realpath($dir)), '/');
        $realRoot = defined('ROOT_PATH') ? rtrim(str_replace('\\', '/', (string)realpath(ROOT_PATH)), '/') : '';

        if (($realRoot !== '' && $realDir === $realRoot) || empty($realDir) || $realDir === '/') {
            error_log("CRITICAL SECURITY: Attempted to delete root directory! ($dir)");
            return false;
        }

        // Safety lock 2: Prevent deleting critical system directories
        if ($realRoot !== '') {
            $protectedDirs = [
                'assets',
                'data',
                'lib', // Legacy or alternate structure
                'theme',
                'plugins',
                'admin'
            ];
            foreach ($protectedDirs as $protectedDir) {
                $protectedPath = $realRoot . '/' . $protectedDir;
                if ($realDir === $protectedPath) {
                    error_log("CRITICAL SECURITY: Attempted to delete a protected directory! ($dir)");
                    return false;
                }
            }
        }

        try {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($files as $fileInfo) {
                if ($fileInfo->isDir() && !$fileInfo->isLink()) {
                    @rmdir($fileInfo->getPathname());
                } else {
                    grinds_force_unlink($fileInfo->getPathname());
                }
            }
        } catch (Exception $e) {
            return false;
        }
        if (@rmdir($dir))
            return true;

        usleep(100000);
        return @rmdir($dir);
    }
}

/**
 * Fetch remote URL content.
 *
 * @param string $url Target URL.
 * @param array $options Options (timeout, max_size, user_agent, verify_ssl, block_private_ip).
 * @return string|false Content or false on failure.
 */
if (!function_exists('grinds_fetch_url')) {
    function grinds_fetch_url($url, $options = [])
    {
        $timeout = $options['timeout'] ?? 10;
        $maxSize = $options['max_size'] ?? 2 * 1024 * 1024;
        $userAgent = $options['user_agent'] ?? 'GrindsCMS/' . (defined('CMS_VERSION') ? CMS_VERSION : '');
        $verifySsl = $options['verify_ssl'] ?? true;
        $blockPrivateIp = $options['block_private_ip'] ?? false;

        if ($blockPrivateIp) {
            $host = parse_url($url, PHP_URL_HOST);
            if (!$host)
                return false;
            $ips = gethostbynamel($host);
            if (!$ips)
                return false;
            foreach ($ips as $ip) {
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                    return false;
                }
            }
        }

        if (function_exists('curl_init')) {
            $maxRedirects = 3;
            $currentUrl = $url;
            $content = false;

            for ($i = 0; $i <= $maxRedirects; $i++) {
                $resolveRules = [];
                // IP validation (executed on every redirect)
                if ($blockPrivateIp) {
                    $host = parse_url($currentUrl, PHP_URL_HOST);
                    if (!$host)
                        return false;
                    $ips = gethostbynamel($host);
                    if (!$ips)
                        return false;
                    foreach ($ips as $ip) {
                        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                            return false; // Block private IP
                        }
                    }

                    // Prevent DNS Rebinding: Pin to the verified IP
                    if (isset($ips[0])) {
                        $scheme = parse_url($currentUrl, PHP_URL_SCHEME);
                        $port = parse_url($currentUrl, PHP_URL_PORT) ?: ($scheme === 'https' ? 443 : 80);
                        $resolveRules[] = "{$host}:{$port}:{$ips[0]}";
                    }
                }

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $currentUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                // Disable auto-follow, handle manually to check IPs
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
                curl_setopt($ch, CURLOPT_HEADER, true);
                if (!empty($resolveRules)) {
                    curl_setopt($ch, CURLOPT_RESOLVE, $resolveRules);
                }

                curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
                curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verifySsl);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $verifySsl ? 2 : 0);

                // Abort if too large
                curl_setopt($ch, CURLOPT_NOPROGRESS, false);
                curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, function ($ch, $downloadSize, $downloaded, $uploadSize, $uploaded) use ($maxSize) {
                    return ($downloaded > $maxSize) ? 1 : 0;
                });

                $response = curl_exec($ch);
                $error = curl_errno($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                curl_close($ch);

                if ($error && $error !== 42) { // 42 = CURLE_ABORTED_BY_CALLBACK
                    return false;
                }

                $headerStr = substr($response, 0, $headerSize);
                $body = substr($response, $headerSize);

                // Handle Redirects (301, 302, 303, 307, 308)
                if ($httpCode >= 300 && $httpCode < 400) {
                    if (preg_match('/^Location:\s*(.+?)$/im', $headerStr, $matches)) {
                        $nextUrl = trim($matches[1]);

                        // Resolve relative URLs
                        if (!preg_match('/^https?:\/\//i', $nextUrl)) {
                            $parsed = parse_url($currentUrl);
                            if (is_array($parsed)) {
                                $base = ($parsed['scheme'] ?? 'http') . '://' . ($parsed['host'] ?? '') . (isset($parsed['port']) ? ':' . $parsed['port'] : '');
                                if (substr($nextUrl, 0, 1) === '/') {
                                    $currentUrl = $base . $nextUrl;
                                } else {
                                    $path = str_replace('\\', '/', dirname($parsed['path'] ?? '/'));
                                    $currentUrl = $base . rtrim($path, '/') . '/' . $nextUrl;
                                }
                            }
                        } else {
                            $currentUrl = $nextUrl;
                        }
                        continue; // Next loop iteration
                    }
                }

                // Not a redirect, accept content
                $content = $body;
                break;
            }

            return $content;
        }

        if (ini_get('allow_url_fopen')) {
            // file_get_contents() is vulnerable to DNS Rebinding because it doesn't
            // support DNS pinning (like CURLOPT_RESOLVE). Fail safe if blocking is required.
            if ($blockPrivateIp) {
                return false;
            }

            // If blocking private IPs, disable auto-redirects to prevent SSRF bypass
            $followLocation = $blockPrivateIp ? 0 : 1;
            $context = stream_context_create([
                'http' => ['timeout' => $timeout, 'user_agent' => $userAgent, 'follow_location' => $followLocation, 'max_redirects' => 3],
                'ssl' => ['verify_peer' => $verifySsl, 'verify_peer_name' => $verifySsl]
            ]);
            return @file_get_contents($url, false, $context, 0, $maxSize);
        }
        return false;
    }
}

/** Split search query into keywords. */
if (!function_exists('grinds_split_search_keywords')) {
    function grinds_split_search_keywords($query)
    {
        return preg_split('/[\s　]+/u', $query, -1, PREG_SPLIT_NO_EMPTY);
    }
}

/** Escape string for SQL LIKE clause. */
if (!function_exists('grinds_escape_like')) {
    function grinds_escape_like($term, $escapeChar = '\\')
    {
        return addcslashes($term, '%' . '_' . $escapeChar);
    }
}

/** Build search query conditions. */
if (!function_exists('grinds_build_search_query')) {
    function grinds_build_search_query($query, $callback)
    {
        $keywords = is_array($query) ? $query : grinds_split_search_keywords($query);
        $conditions = [];
        foreach ($keywords as $word) {
            if ($word === '')
                continue;
            $res = $callback($word);
            if ($res)
                $conditions[] = $res;
        }
        return empty($conditions) ? '' : '(' . implode(' AND ', $conditions) . ')';
    }
}



/** Sanitize HTML content. */
if (!function_exists('grinds_sanitize_html')) {
    function grinds_sanitize_html($text, $allowUnsafe = false)
    {
        if (empty($text) || !is_string($text))
            return '';
        if (!str_contains($text, '<'))
            return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

        $allowedTags = [
            'b',
            'strong',
            'i',
            'em',
            'u',
            's',
            'strike',
            'del',
            'a',
            'br',
            'span',
            'code',
            'mark',
            'small',
            'sub',
            'sup',
            'div',
            'p',
            'img',
            'ul',
            'ol',
            'li',
            'dl',
            'dt',
            'dd',
            'table',
            'thead',
            'tbody',
            'tfoot',
            'tr',
            'th',
            'td',
            'blockquote',
            'pre',
            'hr',
            'figure',
            'figcaption',
            'h1',
            'h2',
            'h3',
            'h4',
            'h5',
            'h6',
            'section',
            'article',
            'aside',
            'nav',
            'header',
            'footer',
            'main',
            'address',
            'time',
            'details',
            'summary',
            'iframe',
            'audio',
            'video',
            'source',
            'track',
            'picture',
            // Add semantic tags for SEO and context
            'cite',
            'q',
            'abbr',
            'kbd',
            'samp',
            'var',
            'dfn',
            'ruby',
            'rt',
            'rp'
        ];
        if ($allowUnsafe) {
            $allowedTags[] = 'script';
        }

        if (function_exists('apply_filters')) {
            $allowedTags = apply_filters('grinds_allowed_html_tags', $allowedTags);
        }

        if (!class_exists('DOMDocument')) {
            error_log('GrindSite Security Warning: DOM extension missing. Sanitize fallback triggered (stripping all tags).');
            return strip_tags($text);
        }

        $dom = new DOMDocument();
        $internalErrors = libxml_use_internal_errors(true);

        try {
            $loaded = $dom->loadHTML('<meta http-equiv="Content-Type" content="text/html; charset=utf-8"><div>' . $text . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

            if (!$loaded) {
                return strip_tags($text);
            }

            $container = $dom->getElementsByTagName('div')->item(0);
            if ($container) {
                _grinds_sanitize_clean_nodes($container, $allowedTags);
                $output = '';
                foreach ($container->childNodes as $child) {
                    $output .= $dom->saveHTML($child);
                }

                return $output;
            }

            return strip_tags($text);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($internalErrors);
        }
    }
}

if (!function_exists('_grinds_sanitize_clean_nodes')) {
    function _grinds_sanitize_clean_nodes($node, $allowedTags, $depth = 0)
    {
        if ($depth > 200) {
            if ($node->parentNode) {
                $node->parentNode->removeChild($node);
            }
            return;
        }

        $children = iterator_to_array($node->childNodes);
        foreach ($children as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE) {
                $tagName = strtolower($child->nodeName);
                if (!in_array($tagName, $allowedTags)) {
                    if (in_array($tagName, ['script', 'style', 'iframe', 'object', 'embed', 'applet', 'form', 'input', 'button', 'textarea', 'meta', 'link'])) {
                        $node->removeChild($child);
                    } else {
                        _grinds_sanitize_clean_nodes($child, $allowedTags, $depth + 1);
                        $fragment = $node->ownerDocument->createDocumentFragment();
                        while ($child->firstChild)
                            $fragment->appendChild($child->firstChild);
                        $node->replaceChild($fragment, $child);
                    }
                } else {
                    _grinds_sanitize_clean_attributes($child);
                    _grinds_sanitize_clean_nodes($child, $allowedTags, $depth + 1);
                }
            } elseif ($child->nodeType !== XML_TEXT_NODE && $child->nodeType !== XML_CDATA_SECTION_NODE) {
                $node->removeChild($child);
            }
        }
    }
}

if (!function_exists('_grinds_sanitize_clean_attributes')) {
    function _grinds_sanitize_clean_attributes($element)
    {
        if (!($element instanceof DOMElement)) {
            return;
        }

        $allowedAttrs = [
            'id',
            'class',
            'title',
            'lang',
            'dir',
            'role',
            // Allow Microdata for structured data (SEO)
            'itemprop',
            'itemscope',
            'itemtype',
            'itemid',
            'itemref'
        ];
        $tagName = strtolower($element->nodeName);
        if ($tagName === 'a')
            array_push($allowedAttrs, 'href', 'target', 'rel');
        elseif (in_array($tagName, ['span', 'div', 'p', 'table', 'td', 'th', 'tr']))
            array_push($allowedAttrs, 'style');
        elseif ($tagName === 'img')
            // Add fetchpriority for LCP optimization
            array_push($allowedAttrs, 'src', 'alt', 'width', 'height', 'loading', 'decoding', 'srcset', 'sizes', 'fetchpriority');
        elseif ($tagName === 'iframe')
            array_push($allowedAttrs, 'src', 'width', 'height', 'frameborder', 'allow', 'allowfullscreen', 'loading', 'title', 'scrolling', 'style', 'referrerpolicy');
        elseif ($tagName === 'script')
            array_push($allowedAttrs, 'src', 'async', 'defer', 'type', 'charset');
        elseif (in_array($tagName, ['td', 'th']))
            array_push($allowedAttrs, 'colspan', 'rowspan', 'headers', 'scope');
        // Add attributes for semantic date and citation
        elseif ($tagName === 'time')
            array_push($allowedAttrs, 'datetime');
        elseif (in_array($tagName, ['blockquote', 'q', 'del', 'ins']))
            array_push($allowedAttrs, 'cite');
        elseif (in_array($tagName, ['audio', 'video']))
            array_push($allowedAttrs, 'src', 'controls', 'autoplay', 'loop', 'muted', 'poster', 'preload');
        elseif ($tagName === 'source' || $tagName === 'track')
            array_push($allowedAttrs, 'src', 'type', 'media', 'kind', 'srclang', 'label', 'srcset', 'sizes');

        if (function_exists('apply_filters')) {
            $allowedAttrs = apply_filters('grinds_allowed_html_attributes', $allowedAttrs, $tagName);
        }

        $attrsToRemove = [];
        if ($element->hasAttributes()) {
            foreach ($element->attributes as $attr) {
                $name = strtolower($attr->nodeName);
                $val = $attr->nodeValue;

                // Decode and clean value for security checks
                $decodedVal = html_entity_decode($val, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $valClean = strtolower(preg_replace('/[\s\x00-\x1f]/', '', $decodedVal));

                if (!in_array($name, $allowedAttrs) && !str_starts_with($name, 'aria-') && !str_starts_with($name, 'data-')) {
                    $attrsToRemove[] = $name;
                    continue;
                }
                if (($name === 'href' || $name === 'src') && _grinds_is_dangerous_url($val))
                    $attrsToRemove[] = $name;
                if ($name === 'style' && preg_match('/(expression|javascript|binding|behavior|@import)/i', $valClean))
                    $attrsToRemove[] = $name;
                if (str_starts_with($name, 'on'))
                    $attrsToRemove[] = $name;
                if ($name === 'src' && ($tagName === 'iframe' || $tagName === 'script') && !grinds_is_trusted_url($val, $tagName))
                    $attrsToRemove[] = $name;
            }
        }
        foreach ($attrsToRemove as $name)
            $element->removeAttribute($name);

        // Remove element if essential attributes are missing (e.g., iframe without src)
        if (in_array($tagName, ['iframe', 'script']) && !$element->hasAttribute('src')) {
            if ($element->parentNode) {
                $element->parentNode->removeChild($element);
            }
            return;
        }

        if ($tagName === 'a' && $element->hasAttribute('target') && strtolower($element->getAttribute('target')) === '_blank') {
            $rel = $element->getAttribute('rel');
            $parts = array_filter(explode(' ', $rel));
            if (!in_array('noopener', $parts))
                $parts[] = 'noopener';
            $element->setAttribute('rel', implode(' ', $parts));
        }
    }
}

if (!function_exists('_grinds_is_dangerous_url')) {
    function _grinds_is_dangerous_url($url)
    {
        $url = html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $url = preg_replace('/[\x00-\x1F\x7F]+/', '', $url);
        $url = trim($url);

        if (preg_match('/^data:image\//i', $url)) {
            return false;
        }

        return preg_match('/^(javascript|vbscript|data|file):/i', $url);
    }
}

/**
 * Get JSON input from request body.
 *
 * @return array
 */
if (!function_exists('get_json_input')) {
    function get_json_input()
    {
        static $cachedInput = null;
        if ($cachedInput !== null)
            return $cachedInput;

        $content = file_get_contents('php://input');
        $input = (is_string($content) && json_validate($content)) ? json_decode($content, true) : null;
        $cachedInput = is_array($input) ? $input : [];
        return $cachedInput;
    }
}

/**
 * Check if request is AJAX.
 * @return bool
 */
if (!function_exists('is_ajax_request')) {
    function is_ajax_request()
    {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            return true;
        }
        // Check if accessing API directory
        $scriptPath = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', $_SERVER['SCRIPT_NAME']) : '';
        if (str_contains($scriptPath, '/admin/api/')) {
            return true;
        }
        return false;
    }
}

/**
 * Delete records in bulk.
 *
 * @param PDO $pdo
 * @param string $table Table name.
 * @param array $ids IDs to delete.
 * @param array $options Options:
 *   - 'before_delete' => callable($id) : bool (return false to skip)
 *   - 'after_delete' => callable($id) : void
 *   - 'id_column' => string (default 'id')
 * @return int Count of deleted items.
 */
if (!function_exists('grinds_delete_records')) {
    function grinds_delete_records($pdo, $table, $ids, $options = [])
    {
        $count = 0;
        $col = $options['id_column'] ?? 'id';

        // Basic validation for table name to prevent SQL injection
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            return 0;
        }

        $stmt = $pdo->prepare("DELETE FROM $table WHERE $col = ?");

        foreach ($ids as $id) {
            if (isset($options['before_delete']) && is_callable($options['before_delete'])) {
                if (call_user_func($options['before_delete'], $id) === false) {
                    continue;
                }
            }

            $stmt->execute([$id]);
            $count++;

            if (isset($options['after_delete']) && is_callable($options['after_delete'])) {
                call_user_func($options['after_delete'], $id);
            }
        }
        return $count;
    }
}

/**
 * Get or create tags from a list of names.
 * Handles deduplication, slug generation, and bulk retrieval to avoid N+1 queries.
 *
 * @param PDO $pdo
 * @param array $tagNames List of tag names.
 * @return array List of tag IDs.
 */
if (!function_exists('grinds_get_or_create_tags')) {
    function grinds_get_or_create_tags(PDO $pdo, array $tagNames)
    {
        $tagIds = [];
        if (empty($tagNames))
            return $tagIds;

        $reserved_slugs = function_exists('grinds_get_reserved_slugs') ? grinds_get_reserved_slugs() : [];

        // 1. Prepare map of slug => name to handle duplicates and normalization
        $slugMap = [];
        foreach ($tagNames as $name) {
            $name = trim($name);
            if ($name === '')
                continue;

            $slug = function_exists('generate_slug') ? generate_slug($name, null, 'tag-') : sanitize_slug($name);
            if (empty($slug))
                $slug = 'tag-' . uniqid();

            if (in_array(strtolower($slug), $reserved_slugs)) {
                $slug .= '-tag';
            }

            if (!isset($slugMap[$slug])) {
                $slugMap[$slug] = $name;
            }
        }

        if (empty($slugMap))
            return [];

        // 2. Bulk fetch existing tags
        $slugs = array_keys($slugMap);
        $placeholders = implode(',', array_fill(0, count($slugs), '?'));

        $stmt = $pdo->prepare("SELECT slug, id FROM tags WHERE slug IN ($placeholders)");
        $stmt->execute($slugs);
        $existingTags = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // 3. Process tags (use existing or create new)
        $stmtInsert = $pdo->prepare("INSERT OR IGNORE INTO tags (name, slug) VALUES (?, ?)");
        $stmtFind = $pdo->prepare("SELECT id FROM tags WHERE slug = ?");

        foreach ($slugMap as $slug => $name) {
            if (isset($existingTags[$slug])) {
                $tagIds[] = $existingTags[$slug];
            } else {
                // Create new with retry logic for race conditions
                $stmtInsert->execute([$name, $slug]);
                if ($stmtInsert->rowCount() > 0) {
                    $tagIds[] = $pdo->lastInsertId();
                } else {
                    // Race condition or collision
                    $stmtFind->execute([$slug]);
                    $existingId = $stmtFind->fetchColumn();

                    if ($existingId) {
                        $tagIds[] = $existingId;
                    } else {
                        // Collision with different name or other error. Retry with suffix.
                        $newSlug = $slug . '-' . mt_rand(1000, 9999);
                        $stmtInsert->execute([$name, $newSlug]);
                        if ($stmtInsert->rowCount() > 0) {
                            $tagIds[] = $pdo->lastInsertId();
                        }
                    }
                }
            }
        }

        return array_unique($tagIds);
    }
}

/**
 * Generic pagination helper.
 *
 * @param PDO $pdo
 * @param string $tableName
 * @param int $page
 * @param int $limit
 * @param Sorter|null $sorter
 * @param string $whereClause
 * @param array $params
 * @return array ['data' => array, 'paginator' => Paginator]
 */
if (!function_exists('grinds_paginate_query')) {
    function grinds_paginate_query(PDO $pdo, string $tableName, int $page, int $limit, ?Sorter $sorter = null, string $whereClause = '', array $params = [])
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $tableName)) {
            throw new InvalidArgumentException("Invalid table name provided.");
        }

        $countSql = "SELECT COUNT(*) FROM {$tableName} " . $whereClause;
        $stmtCount = $pdo->prepare($countSql);
        $stmtCount->execute($params);
        $total = (int)$stmtCount->fetchColumn();

        $paginator = new Paginator($total, $limit, $page);

        $orderClause = $sorter ? $sorter->getOrderClause() : 'ORDER BY id DESC';
        $dataSql = "SELECT * FROM {$tableName} {$whereClause} {$orderClause} LIMIT ? OFFSET ?";
        $stmtData = $pdo->prepare($dataSql);

        $bindIndex = 1;
        foreach ($params as $p) {
            $stmtData->bindValue($bindIndex++, $p);
        }
        $stmtData->bindValue($bindIndex++, $limit, PDO::PARAM_INT);
        $stmtData->bindValue($bindIndex, $paginator->getOffset(), PDO::PARAM_INT);

        $stmtData->execute();
        $data = $stmtData->fetchAll(PDO::FETCH_ASSOC);

        return ['data' => $data, 'paginator' => $paginator];
    }
}

/**
 * Extract target IDs for bulk actions from request data.
 *
 * @param array $data Request data (usually $_POST).
 * @return array List of unique IDs.
 * @throws Exception If no items selected.
 */
if (!function_exists('grinds_get_bulk_target_ids')) {
    function grinds_get_bulk_target_ids($data)
    {
        $targetIds = [];
        if (isset($data['ids']) && is_array($data['ids'])) {
            $targetIds = $data['ids'];
        } elseif (isset($data['target_id']) && !empty($data['target_id'])) {
            $targetIds[] = $data['target_id'];
        }

        $targetIds = array_values(array_unique(array_filter($targetIds, function ($v) {
            return $v !== null && $v !== '';
        })));

        if (empty($targetIds)) {
            $msg = function_exists('_t') ? _t('no_items_selected') : 'No items selected.';
            throw new Exception($msg);
        }

        return $targetIds;
    }
}

/**
 * Sync taxonomy URLs in nav_menus and post content after slug/name change.
 *
 * When a category or tag slug/name is updated, this function:
 * 1. Updates matching nav_menu entries (label and url).
 * 2. Replaces old URLs in post content (both relative and absolute, single/double-quoted).
 *
 * For tags, label matching also covers the '#name' prefix convention.
 *
 * @param PDO    $pdo     Database connection.
 * @param string $type    Taxonomy type: 'category' or 'tag'.
 * @param string $oldSlug Old slug value.
 * @param string $newSlug New slug value.
 * @param string $oldName Old display name.
 * @param string $newName New display name.
 * @return void
 */
if (!function_exists('grinds_sync_taxonomy_urls')) {
    function grinds_sync_taxonomy_urls(PDO $pdo, $type, $oldSlug, $newSlug, $oldName, $newName)
    {
        $relOld = '/' . $type . '/' . $oldSlug;
        $absOld = '{{CMS_URL}}' . $relOld;
        $relNew = '/' . $type . '/' . $newSlug;
        $absNew = '{{CMS_URL}}' . $relNew;

        // Update nav_menus: label and url
        if ($type === 'tag') {
            // Tags may use '#name' as label convention
            $sqlMenu = "UPDATE nav_menus SET
                label = CASE WHEN label = ? THEN ? WHEN label = ? THEN ? ELSE label END,
                url   = CASE WHEN url = ? THEN ? WHEN url = ? THEN ? ELSE url END
                WHERE url = ? OR url = ?";
            $pdo->prepare($sqlMenu)->execute([
                $oldName,
                $newName,
                '#' . $oldName,
                '#' . $newName,
                $relOld,
                $relNew,
                $absOld,
                $absNew,
                $relOld,
                $absOld,
            ]);
        } else {
            $sqlMenu = "UPDATE nav_menus SET
                label = CASE WHEN label = ? THEN ? ELSE label END,
                url   = CASE WHEN url = ? THEN ? WHEN url = ? THEN ? ELSE url END
                WHERE url = ? OR url = ?";
            $pdo->prepare($sqlMenu)->execute([
                $oldName,
                $newName,
                $relOld,
                $relNew,
                $absOld,
                $absNew,
                $relOld,
                $absOld,
            ]);
        }

        // Replace URLs in post content (relative and absolute, double- and single-quoted)
        if ($oldSlug !== $newSlug) {
            // Optimization: Fetch candidates first to avoid massive UDPATE queries on SQLite
            // Use a broad LIKE search to find potential matches
            $likeTerm = '%' . $type . '/' . $oldSlug . '%';
            $likeTermEnc = '%' . $type . '/' . rawurlencode($oldSlug) . '%';

            $stmt = $pdo->prepare("SELECT id, content, hero_settings FROM posts WHERE content LIKE ? OR hero_settings LIKE ? OR content LIKE ? OR hero_settings LIKE ?");
            $stmt->execute([$likeTerm, $likeTerm, $likeTermEnc, $likeTermEnc]);
            $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($candidates)) {
                return;
            }

            // Prepare replacement dictionary
            $map = [];

            // 1. Relative & Absolute (Raw)
            $rawTargets = [
                $relOld => $relNew,
                $absOld => $absNew
            ];

            // 2. Encoded (Multibyte)
            $relOldEnc = '/' . $type . '/' . rawurlencode($oldSlug);
            $relNewEnc = '/' . $type . '/' . rawurlencode($newSlug);
            if ($relOld !== $relOldEnc) {
                $rawTargets[$relOldEnc] = $relNewEnc;
                $rawTargets['{{CMS_URL}}' . $relOldEnc] = '{{CMS_URL}}' . $relNewEnc;
            }

            // Build dictionary with all quoting/escaping variations
            foreach ($rawTargets as $search => $replace) {
                // Regular versions
                foreach (['"', "'"] as $q) {
                    foreach (['', '?', '#', '/'] as $suffix) {
                        $termSuffix = ($suffix === '') ? $q : $suffix;
                        $map[$q . $search . $termSuffix] = $q . $replace . $termSuffix;
                    }
                }

                // Escaped versions (JSON)
                $searchEsc = str_replace('/', '\\/', $search);
                $replaceEsc = str_replace('/', '\\/', $replace);

                foreach (['"', "'"] as $q) {
                    foreach (['', '?', '#', '/'] as $suffix) {
                        $termSuffix = ($suffix === '') ? $q : $suffix;
                        $termSuffixEsc = str_replace('/', '\\/', $termSuffix);
                        $map[$q . $searchEsc . $termSuffixEsc] = $q . $replaceEsc . $termSuffixEsc;
                    }
                }
            }

            $searchKeys = array_keys($map);
            $replaceVals = array_values($map);

            $stmtUpdate = $pdo->prepare("UPDATE posts SET content = ?, hero_settings = ? WHERE id = ?");

            foreach ($candidates as $row) {
                $content = $row['content'];
                $hero = $row['hero_settings'];

                $newContent = str_replace($searchKeys, $replaceVals, $content);
                $newHero = str_replace($searchKeys, $replaceVals, $hero);

                if ($content !== $newContent || $hero !== $newHero) {
                    $stmtUpdate->execute([$newContent, $newHero, $row['id']]);
                }
            }
        }
    }
}

/**
 * Process image upload, library selection, or deletion.
 *
 * @param PDO $pdo
 * @param string $field_name Input field name (e.g. 'image', 'thumbnail').
 * @param string $current_value Current DB value.
 * @param array $options {
 *   'post_data' => array,   // Default $_POST
 *   'files_data' => array,  // Default $_FILES
 *   'url_field' => string,  // Default {$field_name}_url
 *   'delete_field' => string, // Default delete_{$field_name}
 *   'throw_error' => bool,  // Default true
 * }
 * @return string New image path/URL.
 * @throws Exception
 */
if (!function_exists('grinds_process_image_upload')) {
    function grinds_process_image_upload(PDO $pdo, $field_name, $current_value = '', $options = [])
    {
        $post = $options['post_data'] ?? $_POST;
        $files = $options['files_data'] ?? $_FILES;
        $url_field = $options['url_field'] ?? ($field_name . '_url');
        $delete_field = $options['delete_field'] ?? ('delete_' . $field_name);
        $throw_error = $options['throw_error'] ?? true;

        // 1. Handle File Upload
        if (isset($files[$field_name]['name']) && !empty($files[$field_name]['name'])) {
            try {
                $res = FileManager::handleUpload($files[$field_name], $pdo);
                if ($res) {
                    return Routing::convertToDbUrl($res);
                }
            } catch (Exception $e) {
                if ($throw_error) {
                    throw $e;
                }
                return $current_value;
            }

            if ($throw_error) {
                throw new Exception(function_exists('_t') ? _t('err_upload_failed') : 'Upload failed');
            }
            return $current_value;
        }

        // 2. Handle Library URL
        if (!empty($post[$url_field])) {
            return Routing::convertToDbUrl($post[$url_field]);
        }

        // 3. Handle Deletion
        if (isset($post[$delete_field]) && $post[$delete_field] === '1') {
            return '';
        }

        // 4. No change
        return $current_value;
    }
}

/**
 * Generate unique slug by appending number if exists.
 *
 * @param PDO $pdo
 * @param string $table Table name.
 * @param string $slug Base slug.
 * @param int $excludeId ID to exclude from check.
 * @return string Unique slug.
 */
if (!function_exists('grinds_get_unique_slug')) {
    function grinds_get_unique_slug(PDO $pdo, string $table, string $slug, int $excludeId = 0): string
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            return $slug;
        }

        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE slug = ? AND id != ?");
        $stmtCheck->execute([$slug, $excludeId]);

        if ($stmtCheck->fetchColumn() == 0) {
            return $slug;
        }

        $escapedSlug = grinds_escape_like($slug);
        $stmtSuffix = $pdo->prepare("SELECT slug FROM {$table} WHERE slug LIKE ? ESCAPE '\\' AND id != ?");
        $stmtSuffix->execute([$escapedSlug . '-%', $excludeId]);
        $existingSlugs = $stmtSuffix->fetchAll(PDO::FETCH_COLUMN);

        $maxSuffix = 0;

        foreach ($existingSlugs as $existing) {
            if (preg_match('/^' . preg_quote($slug, '/') . '-(\d+)$/u', $existing, $matches)) {
                $maxSuffix = max($maxSuffix, (int)$matches[1]);
            }
        }

        return $slug . '-' . ($maxSuffix + 1);
    }
}

/**
 * Generate Bigram text for search indexing.
 * Converts text into N-grams for CJK support while preserving alphanumeric words.
 *
 * @param string $text Input text.
 * @return string Space-separated tokens.
 */
if (!function_exists('grinds_get_bigram')) {
    function grinds_get_bigram(string $text): string
    {
        $text = mb_strtolower(strip_tags($text), 'UTF-8');
        $text = preg_replace('/[\s　]+/u', ' ', $text);
        $tokens = [];
        $parts = explode(' ', trim($text));

        foreach ($parts as $part) {
            if ($part === '') continue;

            // Keep alphanumeric words intact
            if (preg_match('/^[a-z0-9\-_]+$/u', $part)) {
                $tokens[] = $part;
            } else {
                // Bigram for others (CJK, mixed)
                $len = mb_strlen($part, 'UTF-8');

                if ($len > 5000) {
                    $len = 5000;
                    $part = mb_substr($part, 0, 5000, 'UTF-8');
                }

                if ($len === 1) {
                    $tokens[] = $part;
                } else {
                    for ($i = 0; $i < $len - 1; $i++) {
                        $tokens[] = mb_substr($part, $i, 2, 'UTF-8');
                    }
                }
            }
        }
        return implode(' ', $tokens);
    }
}
