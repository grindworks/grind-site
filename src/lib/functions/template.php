<?php

/**
 * Provide template helpers
 * Render content, media, and template parts.
 */
if (!defined('GRINDS_APP'))
    exit;

/** Render block content to HTML. */
if (!function_exists('render_content')) {
    function render_content($content)
    {
        $html = '';
        $renderer = new BlockRenderer(true);
        $html = $renderer->render($content);
        return apply_filters('grinds_the_content', $html);
    }
}

/**
 * Generate link attributes with auto-detection for external domains.
 * Useful for internal_card or button blocks that might point externally.
 *
 * @param string $url Target URL.
 * @param array $overrides Optional attribute overrides (target, rel, class).
 * @return string HTML attributes string (href="..." ...).
 */
if (!function_exists('grinds_get_link_attributes')) {
    function grinds_get_link_attributes($url, $overrides = [])
    {
        $url = trim((string)$url);
        $isExternal = false;

        if (preg_match('/^(https?:)?\/\//i', $url)) {
            $host = parse_url($url, PHP_URL_HOST);
            $baseHost = parse_url(defined('BASE_URL') ? BASE_URL : '', PHP_URL_HOST);

            if ($host && $baseHost && strcasecmp($host, $baseHost) !== 0) {
                $isExternal = true;
            }
        }

        $attrs = [
            'href' => $url,
            'target' => $isExternal ? '_blank' : '',
            'rel' => $isExternal ? 'noopener noreferrer external' : ''
        ];

        foreach ($overrides as $k => $v) {
            $attrs[$k] = $v;
        }

        $html = '';
        foreach ($attrs as $k => $v) {
            if ($v !== '' && $v !== null) {
                $html .= ' ' . $k . '="' . h($v) . '"';
            }
        }
        return $html;
    }
}

/** Format date string. */
if (!function_exists('the_date')) {
    function the_date($dateStr, $format = null)
    {
        // DRY: Call get_the_date internally to share the localization logic
        $date = get_the_date($format, ['published_at' => $dateStr]);
        return apply_filters('grinds_the_date', $date, $dateStr);
    }
}

/** Generate text excerpt. */
if (!function_exists('get_excerpt')) {
    function get_excerpt($content, $length = 100)
    {
        $text = grinds_extract_text_from_content($content);
        $excerpt = mb_strimwidth($text, 0, $length, '...', 'UTF-8');
        return apply_filters('grinds_get_excerpt', $excerpt);
    }
}

/** Generate responsive image tag with WebP support. */
if (!function_exists('get_image_html')) {
    function get_image_html($src, $attributes = [])
    {
        $pdo = App::db();
        global $grinds_image_meta_cache;
        if (!isset($grinds_image_meta_cache))
            $grinds_image_meta_cache = [];

        if (empty($src))
            return '';

        $src = grinds_url_to_view($src);
        $baseUrl = rtrim(BASE_URL, '/') . '/';
        $relativePath = str_replace($baseUrl, '', $src);
        $relativePath = ltrim($relativePath, '/');
        // Remove query parameters (e.g. ?v=123) for file system/DB lookups
        $relativePath = preg_replace('/\?.*$/', '', $relativePath);

        $meta = [];

        if (array_key_exists($relativePath, $grinds_image_meta_cache)) {
            $meta = $grinds_image_meta_cache[$relativePath];
        } elseif ($pdo) {
            try {
                $stmt = $pdo->prepare("SELECT metadata FROM media WHERE filepath = ?");
                $stmt->execute([$relativePath]);
                $jsonMeta = $stmt->fetchColumn();
                if ($jsonMeta) {
                    $meta = json_decode($jsonMeta, true);
                }
            } catch (Exception $e) {
            }
            $grinds_image_meta_cache[$relativePath] = $meta;
        }

        $default_attrs = ['alt' => '', 'loading' => 'lazy', 'decoding' => 'async'];

        if (!empty($meta['is_ai'])) {
            $default_attrs['data-ai-generated'] = 'true';
            if (!empty($meta['source'])) {
                $default_attrs['data-ai-source'] = $meta['source'];
            }
        }

        $attrs = array_merge($default_attrs, $attributes);
        // Optimize LCP: If loading is eager, disable async decoding to prevent render delay
        if (isset($attrs['loading']) && $attrs['loading'] === 'eager' && !isset($attributes['decoding'])) {
            $attrs['decoding'] = 'auto';
        }

        if (!isset($attributes['alt'])) {
            if (isset($meta['alt']) && $meta['alt'] !== '') {
                $attrs['alt'] = $meta['alt'];
            } elseif (!empty($meta['title'])) {
                $attrs['alt'] = $meta['title'];
            }
        }
        if (!empty($meta['width']) && !empty($meta['height'])) {
            $attrs['width'] = $meta['width'];
            $attrs['height'] = $meta['height'];
        }

        $attr_str = '';
        foreach ($attrs as $key => $val) {
            $attr_str .= ' ' . h($key) . '="' . h($val) . '"';
        }

        $pathInfo = pathinfo($relativePath);
        $dirPrefix = ($pathInfo['dirname'] === '.' || $pathInfo['dirname'] === '') ? '' : $pathInfo['dirname'] . '/';
        $webpRelativePath = $dirPrefix . $pathInfo['filename'] . '.webp';
        $ext = isset($pathInfo['extension']) ? strtolower($pathInfo['extension']) : '';

        $hasWebp = false;
        if (isset($meta['has_webp'])) {
            $hasWebp = $meta['has_webp'];
        } elseif ($ext !== 'webp') {
            $hasWebp = file_exists(ROOT_PATH . '/' . $webpRelativePath);
        }

        if ($ext !== 'webp' && $hasWebp) {
            $webpSrc = resolve_url($webpRelativePath);

            $parsedOriginal = parse_url($src);
            if (!empty($parsedOriginal['query'])) {
                $webpSrc .= '?' . $parsedOriginal['query'];
            }

            $html = '<picture>';
            $html .= '<source srcset="' . h($webpSrc) . '" type="image/webp">';
            $html .= '<img src="' . h($src) . '"' . $attr_str . '>';
            $html .= '</picture>';
            return apply_filters('grinds_get_image_html', $html, $src, $attributes);
        }

        $html = '<img src="' . h($src) . '"' . $attr_str . '>';
        return apply_filters('grinds_get_image_html', $html, $src, $attributes);
    }
}

/**
 * Extract TOC headers from content blocks.
 *
 * @param array $contentData Decoded content JSON.
 * @return array List of headers with level, text, and id.
 */
if (!function_exists('get_post_toc')) {
    function get_post_toc($contentData)
    {
        $headers = [];
        if (is_array($contentData) && !empty($contentData['blocks'])) {
            $index = 0;
            foreach ($contentData['blocks'] as $block) {
                if (($block['type'] ?? '') === 'password_protect') {
                    break;
                }
                if (($block['type'] ?? '') === 'header') {
                    $lvl = (int)str_replace('h', '', $block['data']['level'] ?? '2');
                    $text = strip_tags($block['data']['text'] ?? '');
                    if ($text !== '') {
                        // Semantic ID Generation: sec-{index}-{slug}
                        $safeText = mb_substr(preg_replace('/[^a-zA-Z0-9\p{L}\p{N}]+/u', '-', mb_strtolower($text, 'UTF-8')), 0, 30);
                        $safeText = trim($safeText, '-');
                        $id = 'sec-' . $index . ($safeText ? '-' . $safeText : '');
                        $headers[] = ['level' => $lvl, 'text' => $text, 'id' => $id];
                    }
                }
                $index++;
            }
        }
        return $headers;
    }
}

/** Get URL for system asset with versioning. */
if (!function_exists('grinds_asset_url')) {
    function grinds_asset_url($path)
    {
        static $cache = [];
        if (isset($cache[$path])) {
            return $cache[$path];
        }

        $cleanPath = ltrim($path, '/');
        $fullPath = ROOT_PATH . '/' . $cleanPath;
        $ver = defined('CMS_VERSION') ? CMS_VERSION : '';

        if (file_exists($fullPath)) {
            $ver = @filemtime($fullPath) ?: $ver;
        }

        if (basename($cleanPath) === 'sprite.svg') {
            $url = resolve_url($cleanPath);
        } else {
            $url = resolve_url($cleanPath) . '?v=' . $ver;
        }
        $cache[$path] = $url;
        return $url;
    }
}

/** Get URL for theme asset with fallback to default theme. */
if (!function_exists('grinds_theme_asset_url')) {
    function grinds_theme_asset_url($path)
    {
        $theme = grinds_get_active_theme();

        $cleanPath = ltrim($path, '/');
        $activeThemePath = 'theme/' . $theme . '/' . $cleanPath;
        $defaultThemePath = 'theme/default/' . $cleanPath;

        if ($theme !== 'default' && !file_exists(ROOT_PATH . '/' . $activeThemePath)) {
            return grinds_asset_url($defaultThemePath);
        }

        return grinds_asset_url($activeThemePath);
    }
}


/**
 * Generate body classes based on current page context.
 */
if (!function_exists('get_body_class')) {
    function get_body_class($class = '')
    {
        global $pageType, $pageData;

        $classes = [];

        if (!empty($pageType)) {
            $classes[] = 'page-' . $pageType;
        }

        if ($pageType === 'single' && !empty($pageData['post'])) {
            $classes[] = 'post-id-' . ($pageData['post']['id'] ?? 0);
            if (!empty($pageData['post']['slug'])) {
                $classes[] = 'post-slug-' . $pageData['post']['slug'];
            }
        }

        if ($pageType === 'category' && !empty($pageData['category'])) {
            $classes[] = 'category-' . ($pageData['category']['slug'] ?? '');
        }

        if ($pageType === 'tag' && !empty($pageData['tag'])) {
            $classes[] = 'tag-' . ($pageData['tag']['slug'] ?? '');
        }

        if (!empty($class)) {
            if (!is_array($class)) {
                // Split string by whitespace to handle multiple classes like "foo bar"
                $class = preg_split('#\s+#', $class, -1, PREG_SPLIT_NO_EMPTY);
            }
            $classes = array_merge($classes, $class);
        }

        return array_unique(apply_filters('grinds_body_class', $classes, $class));
    }
}

/**
 * Generate body classes based on current page context.
 */
if (!function_exists('body_class')) {
    function body_class($class = '')
    {
        echo 'class="' . implode(' ', array_map('h', get_body_class($class))) . '"';
    }
}

/**
 * Output system required head tags.
 */
if (!function_exists('grinds_head')) {
    function grinds_head()
    {
        $favicon = function_exists('get_favicon_url') ? get_favicon_url() : '';
        if ($favicon) {
            echo "<link rel=\"apple-touch-icon\" href=\"" . h($favicon) . "\">\n";
        }

        // Google Analytics
        if (function_exists('get_option')) {
            $gaId = get_option('google_analytics_id');
            if (!empty($gaId)) {
                echo "\n<!-- Google Analytics -->\n" .
                    "<script async src=\"https://www.googletagmanager.com/gtag/js?id=" . htmlspecialchars($gaId, ENT_QUOTES, 'UTF-8') . "\"></script>\n" .
                    "<script>\n" .
                    "  window.dataLayer = window.dataLayer || [];\n" .
                    "  function gtag(){dataLayer.push(arguments);}\n" .
                    "  gtag('js', new Date());\n" .
                    "  gtag('config', '" . htmlspecialchars($gaId, ENT_QUOTES, 'UTF-8') . "');\n" .
                    "</script>";
            }
        }

        // Output global JS variables for frontend scripts (SSG search, etc.)
        if (!defined('GRINDS_IS_SSG') || !GRINDS_IS_SSG) {
            echo '<script>';
            echo 'window.grindsBaseUrl = ' . json_encode(resolve_url('/'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) . ';';
            echo '</script>';
        }

        // Output custom head scripts
        if (function_exists('get_option')) {
            $scripts = get_option('custom_head_scripts');
            if ($scripts) {
                echo grinds_url_to_view($scripts);
            }
        }

        if (!get_option('site_noindex')) {
            $rssUrl = resolve_url('/rss.xml');
            $llmsUrl = resolve_url('/llms.txt');

            $siteTitle = h(get_option('site_name', SITE_NAME));
            echo "\n<!-- RSS Feed Auto-Discovery -->\n";
            echo "<link rel=\"alternate\" type=\"application/rss+xml\" title=\"{$siteTitle} RSS Feed\" href=\"{$rssUrl}\">\n";

            // AI Crawler Discovery
            echo "<!-- AI Crawler Discovery -->\n";
            echo "<link rel=\"llms-txt\" href=\"{$llmsUrl}\">\n";
        }

        // Hook point for plugins or system styles
        do_action('grinds_head');
    }
}

/**
 * Output system required footer tags.
 */
if (!function_exists('grinds_footer')) {
    function grinds_footer()
    {
        // Output custom footer scripts
        if (function_exists('get_option')) {
            $scripts = get_option('custom_footer_scripts');
            if ($scripts) {
                echo grinds_url_to_view($scripts);
            }
        }

        do_action('grinds_footer');

        // Output debug info if enabled
        if ((defined('DEBUG_MODE') && constant('DEBUG_MODE')) || (function_exists('get_option') && get_option('debug_mode'))) {
            echo '<!-- GrindSite Debug: ' . round(memory_get_peak_usage() / 1024 / 1024, 2) . 'MB used -->';
        }
    }
}

/**
 * Preload image metadata to reduce N+1 queries.
 */
if (!function_exists('grinds_preload_image_meta')) {
    function grinds_preload_image_meta($urls)
    {
        global $grinds_image_meta_cache;
        if (!isset($grinds_image_meta_cache))
            $grinds_image_meta_cache = [];
        $pdo = App::db();
        if (empty($urls) || !$pdo)
            return;

        $pathsToFetch = [];
        $baseUrl = rtrim(BASE_URL, '/') . '/';

        foreach ($urls as $url) {
            if (empty($url))
                continue;
            $viewUrl = grinds_url_to_view($url);
            $relPath = str_replace($baseUrl, '', $viewUrl);
            $relPath = ltrim($relPath, '/');
            $relPath = preg_replace('/\?.*$/', '', $relPath);

            if (!array_key_exists($relPath, $grinds_image_meta_cache)) {
                $pathsToFetch[$relPath] = $relPath;
            }
        }

        if (empty($pathsToFetch))
            return;

        $chunks = array_chunk($pathsToFetch, 100);
        foreach ($chunks as $chunk) {
            $placeholders = implode(',', array_fill(0, count($chunk), '?'));
            try {
                $stmt = $pdo->prepare("SELECT filepath, metadata FROM media WHERE filepath IN ($placeholders)");
                $stmt->execute(array_values($chunk));
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $grinds_image_meta_cache[$row['filepath']] = json_decode($row['metadata'], true);
                }
                // Mark missing as empty to prevent retry
                foreach ($chunk as $p)
                    if (!isset($grinds_image_meta_cache[$p]))
                        $grinds_image_meta_cache[$p] = [];
            } catch (Exception $e) {
            }
        }
    }
}

/**
 * Generate breadcrumb HTML.
 */
if (!function_exists('get_breadcrumb_html')) {
    function get_breadcrumb_html($options = [])
    {
        global $pageType, $pageData, $pageTitle;

        $defaults = [
            'home_label' => _t('home'),
            'separator' => '&rsaquo;',
            'wrapper_class' => 'breadcrumb',
            'item_class' => 'breadcrumb-item',
            'link_class' => 'breadcrumb-link',
            'active_class' => 'active'
        ];
        $opts = array_merge($defaults, $options);

        $links = [];
        $links[] = ['url' => resolve_url('/'), 'text' => $opts['home_label']];

        if ($pageType === 'category' && isset($pageData['category'])) {
            $links[] = ['text' => $pageData['category']['name']];
        } elseif ($pageType === 'tag' && isset($pageData['tag'])) {
            $links[] = ['text' => _t('tag') . ': ' . $pageData['tag']['name']];
        } elseif ($pageType === 'search') {
            $links[] = ['text' => $pageTitle];
        } elseif ($pageType === 'single' && isset($pageData['post'])) {
            if (!empty($pageData['post']['category_id']) && !empty($pageData['post']['category_name'])) {
                $catPath = 'category/' . rawurlencode($pageData['post']['category_slug']);
                if (defined('GRINDS_IS_SSG') && GRINDS_IS_SSG) {
                    $catPath .= '.html';
                }
                $catUrl = resolve_url($catPath);
                $links[] = ['url' => $catUrl, 'text' => $pageData['post']['category_name']];
            }
            $links[] = ['text' => $pageData['post']['title']];
        } elseif ($pageType === 'page') {
            $links[] = ['text' => $pageTitle];
        } elseif ($pageType === '404') {
            $links[] = ['text' => '404 Not Found'];
        }

        $html = '<nav aria-label="Breadcrumb"><ol class="' . h($opts['wrapper_class']) . '">';
        foreach ($links as $i => $link) {
            $isLast = ($i === count($links) - 1);
            $html .= '<li class="' . h($opts['item_class']) . ($isLast ? ' ' . h($opts['active_class']) : '') . '">';

            if (!$isLast && isset($link['url'])) {
                $html .= '<a href="' . h($link['url']) . '" class="' . h($opts['link_class']) . '">' . h($link['text']) . '</a>';
                $html .= ' <span class="separator">' . $opts['separator'] . '</span> ';
            } else {
                $html .= '<span aria-current="page">' . h($link['text']) . '</span>';
            }
            $html .= '</li>';
        }
        $html .= '</ol></nav>';

        return apply_filters('grinds_get_breadcrumb', $html, $options);
    }
}

/**
 * Render pagination.
 */
if (!function_exists('the_pagination')) {
    function the_pagination()
    {
        global $pageData;
        if (isset($pageData['paginator']) && is_object($pageData['paginator']) && method_exists($pageData['paginator'], 'renderFrontend')) {
            $paginator = $pageData['paginator'];
            echo apply_filters('grinds_the_pagination', $paginator->renderFrontend());
        }
    }
}

/** Check if current page is home. */
if (!function_exists('is_home')) {
    function is_home()
    {
        global $pageType;
        return $pageType === 'home';
    }
}

/**
 * Safely get current post context to prevent fatal errors
 * if theme developers accidentally overwrite the global $post variable.
 */
if (!function_exists('grinds_get_current_post')) {
    function grinds_get_current_post()
    {
        global $post, $pageData;
        // Validate type to ensure it hasn't been polluted with a string/integer
        if (isset($post) && (is_array($post) || is_object($post))) return (array)$post;
        if (isset($pageData['post']) && (is_array($pageData['post']) || is_object($pageData['post']))) return (array)$pageData['post'];
        return [];
    }
}

/** Display or return post title. */
if (!function_exists('the_title')) {
    function the_title($echo = true)
    {
        global $pageTitle;
        $p = grinds_get_current_post();

        if (isset($p['title'])) {
            $title = $p['title'];
        } else {
            $title = $pageTitle ?? '';
        }

        $title = apply_filters('grinds_the_title', $title);

        if ($echo)
            echo h($title);
        else
            return $title;
    }
}

/** Display post content. */
if (!function_exists('the_content')) {
    function the_content()
    {
        $p = grinds_get_current_post();
        $content = '';
        $decoded = null;

        if (isset($p['content'])) {
            $content = $p['content'];
            $decoded = $p['content_decoded'] ?? null;
        }

        if ($decoded) {
            echo render_content($decoded);
        } elseif ($content) {
            echo render_content($content);
        }
    }
}

/** Get site URL. */
if (!function_exists('site_url')) {
    function site_url($path = '')
    {
        $url = resolve_url($path);
        return apply_filters('grinds_site_url', $url, $path);
    }
}

/** Display post category. */
if (!function_exists('the_category')) {
    function the_category($separator = ', ')
    {
        $p = grinds_get_current_post();
        $html = '';

        if (isset($p['category_id']) && isset($p['category_name'])) {
            $url = site_url('category/' . rawurlencode($p['category_slug']));
            $html = '<a href="' . h($url) . '" class="category-link">' . h($p['category_name']) . '</a>';
        } else {
            $html = '<span class="uncategorized">' . _t('uncategorized') . '</span>';
        }
        echo apply_filters('grinds_the_category', $html);
    }
}

/** Display post tags. */
if (!function_exists('the_tags')) {
    function the_tags($before = '', $sep = ', ', $after = '')
    {
        $p = grinds_get_current_post();

        if (!isset($p['id']))
            return;

        $post_id = $p['id'];
        $html = '';

        $tags = [];
        if (isset($p['tags']) && is_array($p['tags'])) {
            $tags = $p['tags'];
        } else {
            $pdo = App::db();
            if ($pdo) {
                $stmt = $pdo->prepare("SELECT t.name, t.slug FROM tags t JOIN post_tags pt ON t.id = pt.tag_id WHERE pt.post_id = ?");
                $stmt->execute([$post_id]);
                $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }

        if ($tags) {
            $html .= $before;
            $links = [];
            foreach ($tags as $tag) {
                $url = site_url('tag/' . rawurlencode($tag['slug']));
                $links[] = '<a href="' . h($url) . '" rel="tag">' . h($tag['name']) . '</a>';
            }
            $html .= implode($sep, $links);
            $html .= $after;
        }
        echo apply_filters('grinds_the_tags', $html);
    }
}

/** Check if post has thumbnail. */
if (!function_exists('has_post_thumbnail')) {
    function has_post_thumbnail()
    {
        $p = grinds_get_current_post();
        return !empty($p['thumbnail']);
    }
}

/** Display post thumbnail. */
if (!function_exists('the_post_thumbnail')) {
    function the_post_thumbnail($attr = [])
    {
        $p = grinds_get_current_post();
        $html = '';
        if (!empty($p['thumbnail'])) {
            $html = get_image_html($p['thumbnail'], $attr);
        }
        echo apply_filters('grinds_post_thumbnail', $html);
    }
}

/** Display post time. */
if (!function_exists('the_time')) {
    function the_time($format = 'Y-m-d')
    {
        $p = grinds_get_current_post();
        $date = $p['published_at'] ?? $p['created_at'] ?? null;
        $output = '';
        if ($date) {
            $ts = strtotime($date);
            if ($ts !== false) {
                $output = date($format, $ts);
            }
        }
        echo apply_filters('grinds_the_time', $output);
    }
}

/**
 * Render single widget.
 */
if (!function_exists('render_widget')) {
    function render_widget($widget)
    {
        $type = $widget['type'];
        $title = h($widget['title']);
        $content = grinds_url_to_view($widget['content']);

        // Decode widget settings.
        $decoded = json_decode($widget['settings'] ?? '{}', true);
        $settings = is_array($decoded) ? $decoded : [];

        if (isset($settings['image']))
            $settings['image'] = resolve_url($settings['image']);
        if (isset($settings['link']))
            $settings['link'] = resolve_url($settings['link']);

        // Backward compatibility: Populate content from settings for profile widget
        if ($type === 'profile' && empty($content) && !empty($settings['text'])) {
            $content = grinds_url_to_view($settings['text']);
        }

        $currentTheme = grinds_get_active_theme();

        // Try active theme part
        if (preg_match('/^[a-zA-Z0-9_]+$/', $type)) {
            $themeWidgetPath = ROOT_PATH . "/theme/{$currentTheme}/parts/widget-{$type}.php";
            if (file_exists($themeWidgetPath)) {
                include $themeWidgetPath;
                return;
            }

            // Try default theme part as fallback
            if ($currentTheme !== 'default') {
                $defaultWidgetPath = ROOT_PATH . "/theme/default/parts/widget-{$type}.php";
                if (file_exists($defaultWidgetPath)) {
                    include $defaultWidgetPath;
                    return;
                }
            }
        }
    }
}

/**
 * Render dynamic sidebar widgets.
 */
if (!function_exists('dynamic_sidebar')) {
    function dynamic_sidebar($index = 1)
    {
        if (!function_exists('get_sidebar_widgets'))
            return false;
        $widgets = get_sidebar_widgets();

        if (empty($widgets))
            return false;

        foreach ($widgets as $widget) {
            $type = $widget['type'];
            $title = $widget['title'];
            $settings = json_decode($widget['settings'] ?? '{}', true);

            ob_start();

            echo '<div class="widget widget-' . h($type) . '">';
            if ($title) {
                echo '<h3 class="widget-title">' . h($title) . '</h3>';
            }
            echo '<div class="widget-content">';

            // Render widget content based on type
            render_widget($widget);

            echo '</div>';
            echo '</div>';

            $html = ob_get_clean();
            echo apply_filters('grinds_widget_output', $html, $widget);
        }

        return true;
    }
}

/** Get formatted date. */
if (!function_exists('get_the_date')) {
    function get_the_date($format = null, $post_obj = null)
    {
        $p = $post_obj ?? grinds_get_current_post();

        if (empty($p))
            return '';

        $dateStr = $p['published_at'] ?? $p['created_at'] ?? null;
        if (empty($dateStr))
            return '';

        if ($format === null) {
            $format = get_option('date_format', 'Y-m-d');
        }
        $ts = strtotime($dateStr);
        if ($ts === false)
            return '';
        $date = date($format, $ts);

        // Simple localization for Japanese
        if (function_exists('get_option') && get_option('site_lang') === 'ja') {
            $en = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
            $ja = ['1月', '2月', '3月', '4月', '5月', '6月', '7月', '8月', '9月', '10月', '11月', '12月', '1月', '2月', '3月', '4月', '5月', '6月', '7月', '8月', '9月', '10月', '11月', '12月', '月曜日', '火曜日', '水曜日', '木曜日', '金曜日', '土曜日', '日曜日', '月', '火', '水', '木', '金', '土', '日'];
            $date = str_replace($en, $ja, $date);
        }

        return apply_filters('grinds_get_the_date', $date, $format, $p);
    }
}

/** Get post permalink. */
if (!function_exists('get_permalink')) {
    function get_permalink($post_obj = null)
    {
        // Support passing slug string directly (legacy/internal usage)
        if (is_string($post_obj)) {
            $slug = $post_obj;
        } else {
            $p = $post_obj ?? grinds_get_current_post();
            if (empty($p))
                return '';
            $slug = is_array($p) ? ($p['slug'] ?? '') : ($p->slug ?? '');
        }

        if (empty($slug))
            return resolve_url('/');

        $path = $slug;
        if (defined('GRINDS_IS_SSG') && GRINDS_IS_SSG) {
            if ($path !== '' && pathinfo($path, PATHINFO_EXTENSION) === '') {
                $path .= '.html';
            }
        }

        $parts = explode('/', $path);
        $path = implode('/', array_map('rawurlencode', $parts));
        $url = resolve_url($path);
        return apply_filters('grinds_get_permalink', $url, $slug);
    }
}

/**
 * Render banners for specific position.
 *
 * @param string $position (e.g. 'header_top', 'footer')
 * @param string $wrapperClass CSS class for the wrapper div
 */
if (!function_exists('render_banners')) {
    function render_banners($position, $wrapperClass = 'banner-item my-4')
    {
        if (!function_exists('get_front_banners'))
            return;

        global $pageType, $pageData;
        $context = [
            'type' => $pageType ?? 'home',
            'data' => $pageData ?? []
        ];

        $allBanners = get_front_banners($context);
        if (empty($allBanners[$position]))
            return;

        foreach ($allBanners[$position] as $banner) {
            $bType = $banner['type'] ?? 'image';

            if ($bType === 'html' || !empty($banner['image_url'])) {
                $html = '<div class="' . h($wrapperClass) . '">';

                if ($bType === 'html') {
                    $html .= $banner['html_code'] ?? '';
                } else {
                    $link = $banner['link_url'] ?? '';
                    $img = $banner['image_url'] ?? '';
                    $target = (preg_match('/^https?:\/\//', $link) && !str_contains($link, BASE_URL)) ? 'target="_blank" rel="noopener noreferrer external"' : '';

                    if ($link)
                        $html .= '<a href="' . h($link) . '" ' . $target . '>';
                    $html .= get_image_html($img, ['class' => 'w-full h-auto rounded shadow-sm']);
                    if ($link)
                        $html .= '</a>';
                }

                $html .= '</div>';

                echo apply_filters('grinds_banner_output', $html, $banner);
            }
        }
    }
}

/**
 * Get site favicon URL.
 *
 * @return string The favicon URL.
 */
if (!function_exists('get_favicon_url')) {
    function get_favicon_url($default = null)
    {
        $faviconUrl = $default ?? DEFAULT_FAVICON_URI;
        $uploadedFavicon = get_option('site_favicon');
        if ($uploadedFavicon) {
            $faviconUrl = resolve_url($uploadedFavicon);
            $localPath = str_replace('{{CMS_URL}}', '', $uploadedFavicon);
            $localPath = ltrim($localPath, '/');
            $localFavicon = ROOT_PATH . '/' . $localPath;
            if (file_exists($localFavicon)) {
                $faviconUrl .= '?v=' . filemtime($localFavicon);
            }
        }
        return apply_filters('grinds_get_favicon_url', $faviconUrl);
    }
}

/**
 * Get list of available themes.
 *
 * @return array Associative array of theme slug => theme name.
 */
if (!function_exists('get_available_themes')) {
    function get_available_themes()
    {
        $themes = [];
        $theme_dir = ROOT_PATH . '/theme/';

        if (is_dir($theme_dir)) {
            $dirs = array_filter(glob($theme_dir . '*'), 'is_dir');
            foreach ($dirs as $dir) {
                $name = basename($dir);
                if (preg_match('/^[a-zA-Z0-9_-]+$/', $name)) {
                    $themes[$name] = ucfirst($name);
                }
            }
        }
        return $themes;
    }
}

/**
 * Render admin bar.
 */
if (!function_exists('grinds_admin_bar')) {
    function grinds_admin_bar()
    {
        $isAdmin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'];
        $isPreview = false;
        $previewData = null;
        $previewToken = $_GET['preview'] ?? '';

        // Check for preview mode
        if ($previewToken && preg_match('/^[a-f0-9]{32}$/', $previewToken)) {
            $previewFile = ROOT_PATH . '/data/tmp/preview/preview_' . $previewToken . '.json';
            if (file_exists($previewFile)) {
                $json = file_get_contents($previewFile);
                $data = json_decode($json, true);
                if ($data) {
                    $isPreview = true;
                    $previewData = $data;
                }
            }
        }

        // Show bar if Admin OR Preview (skip during SSG export)
        if ((!$isAdmin && !$isPreview) || (defined('GRINDS_IS_SSG') && GRINDS_IS_SSG)) {
            return;
        }

        global $pageData;

        // Admin Bar Skin Logic (Modern Glass)
        $barAccent = '#3b82f6';
        $barBg = '#ffffff';
        $barText = '#334155';

?>
        <style>
            :root {
                --grinds-bar-height: 48px;
                --grinds-bar-bg: <?= $barBg ?>;
                --grinds-bar-text: <?= $barText ?>;
                --grinds-bar-border: rgba(0, 0, 0, 0.06);
                --grinds-bar-shadow: 0 -4px 20px rgba(0, 0, 0, 0.08);
            }

            #grinds-admin-bar {
                position: fixed;
                bottom: 0;
                left: 0;
                width: 100%;
                height: var(--grinds-bar-height);
                background-color: var(--grinds-bar-bg);
                color: var(--grinds-bar-text);
                z-index: 9999;

                /* Glassmorphism */
                backdrop-filter: blur(12px);
                -webkit-backdrop-filter: blur(12px);
                border-top: 1px solid var(--grinds-bar-border);
                box-shadow: var(--grinds-bar-shadow);

                transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1);
                font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;

                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 0 16px;
                box-sizing: border-box;
            }

            #grinds-admin-bar * {
                box-sizing: border-box;
            }

            #grinds-admin-bar.minimized {
                transform: translateY(100%);
            }

            /* Safe area support */
            @supports (padding-bottom: env(safe-area-inset-bottom)) {
                #grinds-admin-bar {
                    padding-bottom: env(safe-area-inset-bottom);
                    height: calc(var(--grinds-bar-height) + env(safe-area-inset-bottom));
                }
            }

            #grinds-admin-toggle {
                position: absolute;
                top: -28px;
                right: 16px;
                width: 44px;
                height: 28px;
                background: var(--grinds-bar-bg);
                color: var(--grinds-bar-text);
                border-radius: 10px 10px 0 0;
                border: 1px solid var(--grinds-bar-border);
                border-bottom: none;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                box-shadow: 0 -4px 10px rgba(0, 0, 0, 0.03);
                padding: 0;
                z-index: 10000;
                backdrop-filter: blur(12px);
                -webkit-backdrop-filter: blur(12px);
                transition: all 0.3s ease;
            }

            #grinds-admin-toggle:hover {
                height: 34px;
                top: -34px;
            }

            #grinds-toggle-icon {
                width: 16px;
                height: 16px;
                transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
                opacity: 0.7;
            }

            #grinds-admin-bar.minimized #grinds-toggle-icon {
                transform: rotate(180deg);
            }

            .grinds-bar-btn {
                background: transparent;
                border: 1px solid rgba(0, 0, 0, 0.1);
                color: var(--grinds-bar-text) !important;
                padding: 6px 14px;
                border-radius: 999px;
                font-size: 12px;
                font-weight: 600;
                text-decoration: none;
                transition: all 0.2s;
                display: inline-flex;
                align-items: center;
                gap: 6px;
                cursor: pointer;
                line-height: 1;
            }

            .grinds-bar-btn:hover {
                background: rgba(0, 0, 0, 0.05);
                border-color: rgba(0, 0, 0, 0.2);
                transform: translateY(-1px);
            }

            .grinds-bar-badge {
                background: rgba(255, 251, 235, 0.8);
                border: 1px solid rgba(252, 211, 77, 0.5);
                color: #92400e;
                padding: 4px 12px;
                border-radius: 999px;
                font-size: 11px;
                display: flex;
                align-items: center;
                gap: 6px;
                white-space: nowrap;
            }

            .grinds-bar-dot {
                width: 6px;
                height: 6px;
                background-color: #f59e0b;
                border-radius: 50%;
                display: inline-block;
                box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.2);
            }

            /* Responsive Utilities */
            .grinds-hide-mobile {
                display: inline;
            }

            .grinds-show-mobile {
                display: none;
            }

            .grinds-bar-content {
                display: flex;
                justify-content: space-between;
                align-items: center;
                width: 100%;
                height: 100%;
            }

            .grinds-bar-group {
                display: flex;
                align-items: center;
                gap: 12px;
            }

            @media (max-width: 640px) {
                #grinds-admin-bar {
                    padding: 0 16px;
                    /* Disable scroll for App-like Dock feel */
                    overflow-x: visible;
                    justify-content: center;
                }

                .grinds-bar-content {
                    width: 100%;
                    gap: 8px;
                    justify-content: space-between;
                }

                /* Flatten groups on mobile to distribute icons evenly */
                .grinds-bar-group {
                    gap: 16px;
                }

                /* Center toggle on mobile like a handle */
                #grinds-admin-toggle {
                    right: 50%;
                    transform: translateX(50%);
                    width: 60px;
                    border-radius: 16px 16px 0 0;
                }

                .grinds-hide-mobile {
                    display: none !important;
                }

                .grinds-show-mobile {
                    display: inline !important;
                }

                /* Adjust buttons for touch targets */
                .grinds-bar-btn {
                    padding: 8px;
                    border-color: transparent;
                }

                .grinds-bar-btn:hover {
                    background-color: rgba(0, 0, 0, 0.05);
                }
            }
        </style>
        <div id="grinds-admin-bar">
            <button id="grinds-admin-toggle" onclick="toggleAdminBar()" aria-label="Toggle Admin Bar">
                <svg id="grinds-toggle-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </button>
            <div class="grinds-bar-content">
                <div class="grinds-bar-group">
                    <a href="<?= h(resolve_url('admin/')) ?>" class="flex items-center gap-2 hover:opacity-80 font-bold"
                        style="color: inherit; text-decoration: none;">
                        <span
                            style="width:8px; height:8px; background-color: <?= $barAccent ?>; border-radius: 1px; display:inline-block;"></span>
                        <span class="grinds-hide-mobile">GrindSite</span>
                        <span class="grinds-show-mobile">GS</span>
                    </a>

                    <?php if ($isPreview): ?>
                        <?php
                        $lang = function_exists('get_option') ? get_option('site_lang', 'en') : 'en';
                        $isJa = ($lang === 'ja');

                        $expires = $previewData['__expires_at'] ?? 0;
                        $timeLeft = $expires - time();

                        if ($isJa) {
                            $timeText = ($timeLeft > 0) ? round($timeLeft / 60) . ' 分' : '期限切れ';
                            if ($timeLeft > 3600)
                                $timeText = round($timeLeft / 3600) . ' 時間';
                        } else {
                            $timeText = ($timeLeft > 0) ? round($timeLeft / 60) . ' mins' : 'Expired';
                            if ($timeLeft > 3600)
                                $timeText = round($timeLeft / 3600) . ' hours';
                        }

                        $lblPreview = $isJa ? 'プレビューモード' : 'Preview Mode';
                        $lblExpires = $isJa ? '有効期限: ' : 'Expires: ';

                        // Responsive labels
                        $lblAdd1hLong = $isJa ? '1時間延長' : 'Extend 1h';
                        $lblAdd1hShort = '+1h';
                        $lblAdd24hLong = $isJa ? '24時間延長' : 'Extend 24h';
                        $lblAdd24hShort = '+24h';
                        ?>
                        <div class="grinds-bar-badge">
                            <span class="grinds-bar-dot"></span>
                            <span style="font-weight:600;" class="grinds-hide-mobile">
                                <?= $lblPreview ?>
                            </span>
                            <span style="font-weight:600;" class="grinds-show-mobile">Preview</span>
                            <span style="opacity:0.7; border-left:1px solid rgba(0,0,0,0.1); padding-left:8px; margin-left:8px;">
                                <?= $timeText ?>
                            </span>
                        </div>

                        <?php if ($isAdmin): ?>
                            <button type="button" class="grinds-bar-btn" onclick="copyPreviewUrl(this)" title="Copy URL">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z">
                                    </path>
                                </svg>
                                <span class="grinds-hide-mobile">
                                    <?= $isJa ? 'URLコピー' : 'Copy URL' ?>
                                </span>
                                <span class="grinds-show-mobile">Copy</span>
                            </button>
                            <form method="post" class="flex items-center gap-2 m-0">
                                <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token'] ?? '') ?>">
                                <button type="submit" name="grinds_preview_extend" value="3600" class="grinds-bar-btn"
                                    title="<?= $lblAdd1hLong ?>">
                                    <span class="grinds-hide-mobile">
                                        <?= $lblAdd1hLong ?>
                                    </span>
                                    <span class="grinds-show-mobile">
                                        <?= $lblAdd1hShort ?>
                                    </span>
                                </button>
                                <button type="submit" name="grinds_preview_extend" value="86400" class="grinds-bar-btn"
                                    title="<?= $lblAdd24hLong ?>">
                                    <span class="grinds-hide-mobile">
                                        <?= $lblAdd24hLong ?>
                                    </span>
                                    <span class="grinds-show-mobile">
                                        <?= $lblAdd24hShort ?>
                                    </span>
                                </button>
                            </form>
                        <?php
                        endif; ?>
                    <?php
                    endif; ?>

                    <?php if ($isAdmin && isset($pageData['post']) && isset($pageData['post']['id'])): ?>
                        <a href="<?= h(resolve_url('admin/posts.php?action=edit&id=' . $pageData['post']['id'])) ?>"
                            class="grinds-bar-btn">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                                </path>
                            </svg>
                            <span class="grinds-hide-mobile">
                                <?= _t('edit') ?>
                            </span>
                        </a>
                    <?php
                    endif; ?>
                </div>

                <?php if ($isAdmin): ?>
                    <div class="grinds-bar-group">
                        <!-- Cache Clear Button (Frontend) -->
                        <button onclick="clearCacheFrontend(this)" class="grinds-bar-btn group">
                            <svg class="w-3.5 h-3.5 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            <span class="grinds-hide-mobile">Clear Cache</span>
                        </button>

                        <form method="post" action="<?= h(resolve_url('admin/logout.php')) ?>" class="flex items-center m-0">
                            <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
                            <button type="submit" class="grinds-bar-btn" title="Logout">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1">
                                    </path>
                                </svg>
                                <span class="grinds-hide-mobile">Logout</span>
                            </button>
                        </form>
                    </div>
                <?php
                endif; ?>
            </div>

            <script>
                function toggleAdminBar() {
                    const bar = document.getElementById('grinds-admin-bar');
                    const icon = document.getElementById('grinds-toggle-icon');
                    const isMinimized = bar.classList.toggle('minimized');
                    localStorage.setItem('grinds_admin_bar_minimized', isMinimized);
                    if (isMinimized) {
                        document.body.style.paddingBottom = '0';
                    } else {
                        document.body.style.paddingBottom = 'calc(48px + env(safe-area-inset-bottom))';
                    }
                }
                const bar = document.getElementById('grinds-admin-bar');
                if (bar) {
                    const isMinimized = localStorage.getItem('grinds_admin_bar_minimized') === 'true';
                    if (isMinimized) {
                        bar.classList.add('minimized');
                    } else {
                        document.body.style.paddingBottom = 'calc(48px + env(safe-area-inset-bottom))';
                    }
                }

                function copyPreviewUrl(btn) {
                    const originalHtml = btn.innerHTML;
                    navigator.clipboard.writeText(window.location.href).then(() => {
                        btn.innerHTML = '<span style="color: #4ade80; font-weight: 600;">Copied!</span>';
                        setTimeout(() => {
                            btn.innerHTML = originalHtml;
                        }, 2000);
                    }).catch(err => {
                        console.error('Copy failed', err);
                    });
                }

                function clearCacheFrontend(btn) {
                    const originalText = btn.innerHTML;
                    btn.innerHTML = 'Cleaning...';
                    btn.disabled = true;

                    fetch(<?= json_encode(resolve_url('admin/api/clear_cache.php')) ?>, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            csrf_token: <?= json_encode(generate_csrf_token()) ?>
                        })
                    }).then(res => res.json()).then(data => {
                        if (data.success) {
                            btn.innerHTML = '<span style="color: #4ade80;">Done!</span>';
                            setTimeout(() => location.reload(), 500);
                        } else {
                            alert('Failed');
                            btn.innerHTML = originalText;
                            btn.disabled = false;
                        }
                    }).catch(e => {
                        alert('Error');
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                    });
                }
            </script>
        </div>
<?php
    }
}

// Auto-inject Admin Bar
add_action('grinds_footer', 'grinds_admin_bar');

/**
 * Load template part.
 *
 * @param string $slug The slug name for the generic template.
 * @param string|null $name The name of the specialized template.
 * @param array $args Additional arguments passed to the template part.
 * @return bool True on success, false on failure.
 */
if (!function_exists('get_template_part')) {
    function get_template_part($slug, $name = null, $args = [])
    {
        global $post, $pageData, $pageType, $pageTitle;

        static $resolvedPaths = [];

        $theme = $GLOBALS['activeTheme'] ?? get_option('site_theme', 'default');
        $cacheKey = $theme . '-' . $slug . ($name ? '-' . $name : '');

        if (array_key_exists($cacheKey, $resolvedPaths)) {
            $_grinds_sys_target_file = $resolvedPaths[$cacheKey];
            if ($_grinds_sys_target_file) {
                if (!empty($args) && is_array($args)) {
                    unset($args['_grinds_sys_target_file']);
                    extract($args, EXTR_OVERWRITE);
                }
                include $_grinds_sys_target_file;
                return true;
            }
            return false;
        }

        $themeDir = ROOT_PATH . '/theme/' . $theme;
        $defaultThemeDir = ROOT_PATH . '/theme/default';

        $templates = [];
        if ($name !== null) {
            $templates[] = "{$slug}-{$name}.php";
        }
        $templates[] = "{$slug}.php";

        // 1. Try active theme
        foreach ($templates as $template) {
            $_grinds_sys_target_file = $themeDir . '/' . $template;
            if (file_exists($_grinds_sys_target_file)) {
                $resolvedPaths[$cacheKey] = $_grinds_sys_target_file;
                if (!empty($args) && is_array($args)) {
                    // Prevent overwriting the include path
                    unset($args['_grinds_sys_target_file']);

                    extract($args, EXTR_OVERWRITE);
                }
                include $_grinds_sys_target_file;
                return true;
            }
        }

        // 2. Fallback to default theme
        if ($theme !== 'default') {
            foreach ($templates as $template) {
                $_grinds_sys_target_file = $defaultThemeDir . '/' . $template;
                if (file_exists($_grinds_sys_target_file)) {
                    $resolvedPaths[$cacheKey] = $_grinds_sys_target_file;
                    if (!empty($args) && is_array($args)) {
                        unset($args['_grinds_sys_target_file']);
                        extract($args, EXTR_OVERWRITE);
                    }
                    include $_grinds_sys_target_file;
                    return true;
                }
            }
        }

        $resolvedPaths[$cacheKey] = false;
        return false;
    }
}
