<?php

/**
 * Handle URL parsing and resolution.
 */
if (!defined('GRINDS_APP')) exit;

class Routing
{
    private static $basePath = null;

    /**
     * Determines the logical request path from GET parameters or the URI.
     * This centralizes the logic from index.php and front.php.
     * Precedence:
     * 1. `q` GET parameter (for search).
     * 2. The server's request URI path.
     *
     * @return string The resolved logical path (e.g., 'posts/view/1', 'search', 'home').
     * Determine logical request path.
     */
    public static function getResolvedPath()
    {
        $params = self::getParams();
        if (!empty($params['q'])) return 'search';

        $path = self::getRelativePath($_SERVER['REQUEST_URI']);
        return trim($path, '/');
    }

    /**
     * Get current request path (absolute path component).
     */
    public static function getCurrentPath()
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH);
        $path = is_string($path) ? $path : '/';
        return rawurldecode($path);
    }

    /**
     * Get current query parameters.
     */
    public static function getParams()
    {
        return $_GET;
    }

    /**
     * Analyze query parameters to separate allowed (cache key), ignored (tracking), and unknown.
     *
     * @param array $allowedParams List of parameters to include in the normalized query.
     * @return array ['query' => array, 'hasUnknownParams' => bool]
     */
    public static function analyzeParams(array $allowedParams)
    {
        $params = self::getParams();
        $query = [];

        foreach ($params as $key => $val) {
            if (in_array($key, $allowedParams, true)) {
                $query[$key] = $val;
            }
        }

        ksort($query);
        return ['query' => $query];
    }

    /**
     * Get application base path.
     */
    public static function getBasePath()
    {
        if (self::$basePath === null) {
            $base = defined('BASE_URL') ? rtrim((string)BASE_URL, '/') : '';
            $path = parse_url($base, PHP_URL_PATH);
            $path = is_string($path) ? $path : '';
            self::$basePath = rtrim($path, '/');
        }
        return self::$basePath;
    }

    /**
     * Resolve relative request URI.
     */
    public static function getRelativePath($requestUri)
    {
        $requestPath = parse_url($requestUri, PHP_URL_PATH);
        $requestPath = is_string($requestPath) ? $requestPath : '';
        $requestPath = rawurldecode($requestPath);

        return self::stripBasePath($requestPath);
    }

    /**
     * Resolve relative path to absolute URL.
     */
    public static function resolveUrl($path = '')
    {
        if ($path === '' || $path === null || is_bool($path)) return '';

        $base = defined('BASE_URL') ? rtrim((string)BASE_URL, '/') : '';

        // Replace placeholder
        if (str_contains($path, '{{CMS_URL}}')) {
            $path = str_replace('{{CMS_URL}}', $base, $path);
        }

        // Skip absolute URLs
        if (preg_match('/^([a-z]+:|#|\/\/)/i', $path)) {
            return $path;
        }

        $cleanPath = '/' . ltrim($path, '/');

        // Prevent double prefixing if path already starts with base path
        $cleanPath = self::stripBasePath($cleanPath);

        return rtrim($base, '/') . '/' . ltrim($cleanPath, '/');
    }

    /**
     * Convert absolute URL to database placeholder.
     * Enhanced to catch root-relative paths in subdirectories.
     */
    public static function convertToDbUrl($content, $baseUrl = null)
    {
        if (empty($content) || !is_string($content)) return $content;

        // Try to handle as JSON first to prevent structure corruption
        // Only attempt decode if it looks like JSON
        $trimmed = trim($content);
        if ($trimmed === '') return $content;

        $firstChar = mb_substr($trimmed, 0, 1, 'UTF-8');
        $lastChar  = mb_substr($trimmed, -1, 1, 'UTF-8');

        if (($firstChar === '{' && $lastChar === '}') || ($firstChar === '[' && $lastChar === ']')) {
            $json = json_decode($content, true);
            if (is_array($json)) {
                $processed = self::recursiveUrlConvert($json, $baseUrl);
                return json_encode($processed, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_IGNORE);
            }
        }

        return self::processContentUrls($content, $baseUrl);
    }

    /**
     * Convert URLs directly within a decoded array structure.
     * Used for optimized data pipeline processing.
     */
    public static function convertArrayToDbUrl(array $data, $baseUrl = null)
    {
        return self::recursiveUrlConvert($data, $baseUrl);
    }

    private static function recursiveUrlConvert($data, $baseUrl, $depth = 0)
    {
        if ($depth > 50) {
            return $data;
        }

        if (is_string($data)) {
            $processedData = self::processContentUrls($data, $baseUrl);

            if (empty(trim($processedData))) return $processedData;

            if ($baseUrl === null) {
                $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
            } else {
                $baseUrl = rtrim($baseUrl, '/');
            }

            if ($baseUrl !== '' && stripos($processedData, $baseUrl) === 0) {
                return '{{CMS_URL}}' . substr($processedData, strlen($baseUrl));
            }

            $parsed = parse_url($baseUrl);
            $basePath = isset($parsed['path']) ? rtrim($parsed['path'], '/') : '';

            if ($basePath !== '') {
                if (stripos($processedData, $basePath . '/') === 0) {
                    return '{{CMS_URL}}' . substr($processedData, strlen($basePath));
                }
            } else {
                if (str_starts_with($processedData, '/') && !str_starts_with($processedData, '//')) {
                    return '{{CMS_URL}}' . $processedData;
                }
            }

            return $processedData;
        }
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = self::recursiveUrlConvert($value, $baseUrl, $depth + 1);
            }
        }
        return $data;
    }

    private static function processContentUrls($content, $baseUrl)
    {
        if (empty($content) || !is_string($content)) return $content;

        if ($baseUrl === null) {
            $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        } else {
            $baseUrl = rtrim($baseUrl, '/');
        }

        // Determine host and path
        $searchUrl = $baseUrl;
        if ($searchUrl === '' && isset($_SERVER['HTTP_HOST'])) {
            $scheme = (function_exists('is_ssl') && is_ssl()) ? 'https://' : 'http://';
            $searchUrl = $scheme . $_SERVER['HTTP_HOST'];
        }
        if ($searchUrl === '') return $content;

        $parsed = parse_url($searchUrl);
        $host = ($parsed && isset($parsed['host'])) ? $parsed['host'] : '';
        $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';
        $path = isset($parsed['path']) ? rtrim($parsed['path'], '/') : '';

        // Match absolute URLs
        if ($host) {
            $scheme = 'https?:';
            $safeBase = preg_quote($host . $port . $path, '/');
            $flexibleBase = str_replace('\/', '(?:\\\\\/|[\/\\\\])', $safeBase);
            $boundary = '(?=[\\\\\/?"\')\s<>;,\]}&`]|\.(?!\w)|$)';
            $pattern = '/(' . $scheme . ')?\\\\*+\/\\\\*+\/' . $flexibleBase . $boundary . '/i';

            $content = preg_replace($pattern, '{{CMS_URL}}', $content) ?? $content;
        }

        // Match root-relative paths
        $cleanPath = trim($path, '/');
        $flexiblePath = '';

        if ($cleanPath !== '') {
            $safePath = preg_quote($cleanPath, '/');
            $flexiblePath = '(?:\\\\\/|[\/\\\\])' . str_replace('\/', '(?:\\\\\/|[\/\\\\])', $safePath);
        }

        // 1. HTML attributes (href, src, action, data-*)
        $content = preg_replace_callback(
            '/(href|src|action|data-[a-z-]+)\s*=\s*(["\'])([^"\']++)\2/i',
            function ($matches) use ($flexiblePath) {
                $attr = $matches[1];
                $quote = $matches[2];
                $val = $matches[3];

                if ($flexiblePath !== '') {
                    $val = preg_replace('/^(' . $flexiblePath . '(?:\\\\*+[\/\\\\]))/', '{{CMS_URL}}/', $val);
                } else {
                    $val = preg_replace('/^((?:\\\\\/|[\/\\\\])(?!\\\\\/|[\/\\\\]))/', '{{CMS_URL}}$1', $val);
                }

                return $attr . '=' . $quote . $val . $quote;
            },
            $content
        ) ?? $content;

        // 2. Style attributes
        if (stripos($content, 'style') !== false) {
            $content = preg_replace_callback(
                '/(style)\s*=\s*(?:(")([^"]*+)"|(\')([^\']*+)\')/i',
                function ($matches) use ($flexiblePath) {
                    $attr = $matches[1];
                    $quote = !empty($matches[2]) ? $matches[2] : $matches[4];
                    $styleContent = !empty($matches[2]) ? $matches[3] : $matches[5];

                    if (empty(trim($styleContent))) return $matches[0];

                    $newStyleContent = function_exists('grinds_replace_css_urls')
                        ? grinds_replace_css_urls($styleContent, function ($urlValue) use ($flexiblePath) {
                            if ($flexiblePath !== '') {
                                $newUrlValue = preg_replace('/^(' . $flexiblePath . ')/', '{{CMS_URL}}', $urlValue);
                            } else {
                                $newUrlValue = preg_replace('/^((?:\\\\\/|[\/\\\\])(?!\\\\\/|[\/\\\\]))/', '{{CMS_URL}}$1', $urlValue);
                            }
                            return $newUrlValue ?? $urlValue;
                        })
                        : $styleContent;

                    return $attr . '=' . $quote . ($newStyleContent ?? $styleContent) . $quote;
                },
                $content
            ) ?? $content;
        }

        // 3. Srcset attributes
        if (stripos($content, 'srcset') !== false) {
            $content = preg_replace_callback(
                '/(srcset)\s*=\s*(?:(")([^"]*+)"|(\')([^\']*+)\')/i',
                function ($matches) use ($flexiblePath) {
                    $attr = $matches[1];
                    $quote = !empty($matches[2]) ? $matches[2] : $matches[4];
                    $value = !empty($matches[2]) ? $matches[3] : $matches[5];

                    if (empty(trim($value))) return $matches[0];

                    if ($flexiblePath !== '') {
                        $newValue = preg_replace(
                            '/(^|,\s*)(' . $flexiblePath . ')/',
                            '$1{{CMS_URL}}',
                            $value
                        );
                    } else {
                        $newValue = preg_replace(
                            '/(^|,\s*)((?:\\\\\/|[\/\\\\])(?!\\\\\/|[\/\\\\]))/',
                            '$1{{CMS_URL}}$2',
                            $value
                        );
                    }

                    return $attr . '=' . $quote . ($newValue ?? $value) . $quote;
                },
                $content
            ) ?? $content;
        }

        return $content;
    }

    /**
     * Restore URL from database placeholder.
     */
    public static function restoreViewUrl($content, $baseUrl = null)
    {
        if (empty($content) || !is_string($content)) return $content;

        // Get base URL
        if ($baseUrl === null) {
            $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        } else {
            $baseUrl = rtrim($baseUrl, '/');
        }

        // Handle standard placeholder
        // Note: This replaces all occurrences, including those in plain text.
        // To output "{{CMS_URL}}" literally, use HTML entities (&#123;&#123;CMS_URL}}).
        $content = str_replace('{{CMS_URL}}', $baseUrl, $content);

        return $content;
    }

    /**
     * Convert a root-relative path to a relative path based on depth.
     * Enhanced to handle SSG logic (pagination, slug normalization, .html extension).
     *
     * @param string $targetUrl The target URL (absolute or relative).
     * @param int $depth The depth of the current page.
     * @return string
     */
    public static function toRelative($targetUrl, $depth = 0)
    {
        if (empty($targetUrl)) return $targetUrl;

        // Skip already relative paths (e.g. in CSS)
        if (str_starts_with($targetUrl, './') || str_starts_with($targetUrl, '../')) {
            return $targetUrl;
        }

        // Handle {{CMS_URL}} placeholder
        $targetUrl = str_replace('{{CMS_URL}}', '', $targetUrl);

        $liveBaseUrl = rtrim(self::resolveUrl('/'), '/');
        $parsedPath = parse_url($liveBaseUrl, PHP_URL_PATH);
        $basePath = is_string($parsedPath) ? $parsedPath : '';
        $basePath = rtrim($basePath, '/');

        $path = $targetUrl;

        // Strip Base URL
        if (str_starts_with($path, $liveBaseUrl)) {
            $rest = substr($path, strlen($liveBaseUrl));
            if ($rest === '' || $rest[0] === '/' || $rest[0] === '?' || $rest[0] === '#') {
                $path = $rest;
            }
        } elseif ($basePath !== '') {
            $path = self::stripBasePath($path);
        }

        // Skip external/special links
        if (preg_match('/^([a-z]+:|#|\/\/|mailto:|tel:|javascript:|data:)/i', $path)) {
            return $targetUrl;
        }

        // Parse URL
        $parsed = parse_url($path);
        if ($parsed === false) return $targetUrl;

        $pathPart = $parsed['path'] ?? '';
        $queryPart = $parsed['query'] ?? '';
        $fragmentPart = $parsed['fragment'] ?? '';

        $cleanPath = ltrim($pathPart, '/');
        $cleanPath = str_replace('//', '/', $cleanPath);
        if ($cleanPath === '' || $cleanPath === 'index.php') {
            $cleanPath = 'index';
        }

        // Handle static files (check extension on PATH component only)
        if (preg_match('/\.(jpg|jpeg|png|gif|webp|svg|css|js|pdf|ico|json|xml|txt|woff|woff2|ttf|eot|mp4|webm|mp3|wav|zip|rar|7z|doc|docx|xls|xlsx|ppt|pptx|csv|html|htm)$/i', $cleanPath)) {
            $queryStr = ($queryPart !== '') ? '?' . $queryPart : '';

            // Strip query string for SSG to ensure local file resolution
            if (defined('GRINDS_IS_SSG') && GRINDS_IS_SSG) {
                $queryStr = '';
            }

            $fragmentStr = ($fragmentPart !== '') ? '#' . $fragmentPart : '';
            return self::calculateRelativePath($cleanPath . $queryStr . $fragmentStr, $depth);
        }

        // SSG Page Logic
        $queryStr = '';
        $pageNum = 1;
        $supportsPagination = ($cleanPath === 'index' || str_starts_with($cleanPath, 'category/') || str_starts_with($cleanPath, 'tag/'));

        if ($queryPart !== '') {
            parse_str($queryPart, $queryParams);
            if ($supportsPagination && isset($queryParams['page'])) {
                $pageNum = (int)$queryParams['page'];
                unset($queryParams['page']);
            }
            $queryStr = !empty($queryParams) ? '?' . http_build_query($queryParams) : '';
        }

        $fragmentStr = ($fragmentPart !== '') ? '#' . $fragmentPart : '';

        // Normalize slug
        if (function_exists('grinds_ssg_normalize_slug')) {
            $cleanPath = grinds_ssg_normalize_slug($cleanPath);
        } else {
            $cleanPath = mb_strtolower($cleanPath, 'UTF-8');
        }

        $cleanPath = rtrim($cleanPath, '/');

        $suffix = ($pageNum > 1) ? '_' . $pageNum : '';
        $finalPath = $cleanPath . $suffix . '.html' . $queryStr . $fragmentStr;

        return self::calculateRelativePath($finalPath, $depth);
    }

    /**
     * Internal helper to strip the base path from a given request path.
     *
     * @param string $requestPath The path to process.
     * @return string The path with the base path stripped, or the original path.
     */
    private static function stripBasePath($requestPath)
    {
        $basePath = self::getBasePath();
        if ($basePath !== '' && $basePath !== '/' && str_starts_with($requestPath, $basePath)) {
            $len = strlen($basePath);
            // Ensure it's a full path segment match (e.g., /base/page, not /basename/page)
            if (strlen($requestPath) === $len || $requestPath[$len] === '/') {
                $relativePath = substr($requestPath, $len);
                return !empty($relativePath) ? $relativePath : '/';
            }
        }
        return $requestPath;
    }

    /**
     * Internal helper to calculate relative path.
     */
    private static function calculateRelativePath($path, $depth)
    {
        $cleanPath = ltrim($path, '/');
        if ($depth <= 0) {
            return './' . $cleanPath;
        }

        return str_repeat('../', $depth) . $cleanPath;
    }

    /**
     * Safely retrieve a string parameter from an array.
     * Prevents TypeErrors in PHP 8+ when an array is passed.
     *
     * @param array $array Source array (e.g. $_GET, $_POST)
     * @param string $key Key to retrieve
     * @param string $default Default value
     * @return string
     */
    public static function getString(array $array, $key, $default = '')
    {
        return isset($array[$key]) && is_scalar($array[$key]) ? (string)$array[$key] : $default;
    }
}
