<?php

/**
 * Handle URL routing and request resolution.
 */
if (!defined('GRINDS_APP')) exit;

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

GrindsLogger::init();
require_once __DIR__ . '/paginator.php';

/**
 * Add llms.txt discovery link to head
 */
add_action('grinds_head', function () {
    echo '<link rel="llms-txt" href="' . resolve_url('/llms.txt') . '">' . "\n";
});

/** Resolve frontend request URL. */
function resolve_front_request($request_url)
{
    $pdo = App::db();
    $now = date('Y-m-d H:i:s');

    $isAdmin = !empty($_SESSION['admin_logged_in']);

    // Check maintenance mode
    if (file_exists(ROOT_PATH . '/.maintenance') && !$isAdmin && !(defined('GRINDS_IS_SSG') && GRINDS_IS_SSG)) {
        http_response_code(503);
        header('Retry-After: 300');

        $currentTheme = grinds_get_active_theme();
        $themePath = ROOT_PATH . '/theme/' . $currentTheme . '/';

        $skin = require_once ROOT_PATH . '/admin/load_skin.php';
        if (!is_array($skin)) $skin = [];

        if (file_exists($themePath . 'functions.php')) {
            require_once $themePath . 'functions.php';
        }

        if (file_exists($themePath . 'maintenance.php')) {
            include $themePath . 'maintenance.php';
        } elseif (file_exists(ROOT_PATH . '/theme/default/maintenance.php')) {
            include ROOT_PATH . '/theme/default/maintenance.php';
        } else {
            die('<h1>System Maintenance</h1><p>We are currently updating the system. Please check back in a few minutes.</p>');
        }
        exit;
    }

    $request_url = trim($request_url, '/');



    $segments = explode('/', $request_url);
    if (count($segments) === 1 && $segments[0] === '') {
        $segments = [];
    }

    $queryParams = Routing::getParams();
    $page = (int)Routing::getString($queryParams, 'page', '1');
    if ($page < 1) $page = 1;
    $limit = (int)get_option('posts_per_page', 10);

    $repo = new PostRepository($pdo);
    $result = [
        'type' => '404',
        'title' => '404 Not Found',
        'desc' => get_option('site_description'),
        'data' => [],
        'is_preview' => false
    ];

    // Handle search
    if ((!empty($segments[0]) && $segments[0] === 'search') || (empty($segments) && !empty($queryParams['q']))) {
        $query = trim(Routing::getString($queryParams, 'q'));
        $result['type'] = 'search';
        $result['title'] = _t('search') . ': ' . $query;

        if ($query !== '') {
            $fetched = $repo->paginate([
                'status' => 'published',
                'search' => $query
            ], $page, $limit, 'p.published_at DESC', 'p.id, p.title, p.slug, p.type, p.thumbnail, p.description, p.published_at, p.created_at, p.updated_at');

            $result['data'] = $fetched;
            $result['data']['total_count'] = $fetched['total'];
        } else {
            $result['data']['posts'] = [];
            $result['data']['total_count'] = 0;
        }
    }

    // Handle home
    elseif (empty($segments)) {
        $result['type'] = 'home';
        $result['title'] = get_option('site_description', 'Home');

        $result['data'] = $repo->paginate([
            'status' => 'published',
            'type' => 'post'
        ], $page, $limit, 'p.published_at DESC', 'p.id, p.title, p.slug, p.type, p.thumbnail, p.description, p.published_at, p.created_at, p.updated_at');
    }

    // Handle category
    elseif ($segments[0] === 'category' && !empty($segments[1])) {
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE slug = ?");
        $stmt->execute([$segments[1]]);
        $category = $stmt->fetch();

        if ($category) {
            $result['type'] = 'category';
            $result['title'] = $category['name'];
            $result['data']['category'] = $category;

            $fetched = $repo->paginate([
                'status' => 'published',
                'type' => 'post',
                'category_id' => $category['id']
            ], $page, $limit, 'p.published_at DESC', 'p.id, p.title, p.slug, p.type, p.thumbnail, p.description, p.published_at, p.created_at, p.updated_at');
            $result['data'] = array_merge($result['data'], $fetched);
        }
    }

    // Handle tag
    elseif ($segments[0] === 'tag' && !empty($segments[1])) {
        $stmt = $pdo->prepare("SELECT * FROM tags WHERE slug = ?");
        $stmt->execute([$segments[1]]);
        $tag = $stmt->fetch();

        if ($tag) {
            $result['type'] = 'tag';
            $result['title'] = '#' . $tag['name'];
            $result['data']['tag'] = $tag;

            $fetched = $repo->paginate([
                'status' => 'published',
                'type' => 'post',
                'tag_id' => $tag['id']
            ], $page, $limit, 'p.published_at DESC', 'p.id, p.title, p.slug, p.type, p.thumbnail, p.description, p.published_at, p.created_at, p.updated_at');
            $result['data'] = array_merge($result['data'], $fetched);
        }
    }

    // Handle single
    else {
        $post = null;
        if (count($segments) === 1) {
            $slug = $segments[0];
            $posts = $repo->fetch(['slug' => $slug, 'status' => 'all']);
            $post = $posts[0] ?? null;
        }

        if ($post) {
            // Optimize: Decode content once to avoid double decoding later (e.g. in OGP generation)
            $resolvedContent = grinds_url_to_view((string)$post['content']);
            $decoded = json_decode($resolvedContent, true);
            $post['content_decoded'] = is_array($decoded) ? $decoded : $resolvedContent;
            $decodedHero = json_decode($post['hero_settings'] ?? '{}', true);
            $post['hero_settings_decoded'] = is_array($decodedHero) ? $decodedHero : [];

            $isPublished = ($post['status'] === 'published' && ($post['published_at'] <= $now || $post['published_at'] === null));

            if ($isPublished || $isAdmin) {
                $result['type'] = 'single';
                $result['title'] = $post['title'];

                if (!empty($post['description'])) {
                    $result['desc'] = $post['description'];
                } else {
                    $result['desc'] = get_excerpt($post['content'], 120);
                }

                if (!$isPublished && $isAdmin) $result['is_preview'] = true;

                $stmtTags = $pdo->prepare("SELECT t.* FROM tags t JOIN post_tags pt ON t.id = pt.tag_id WHERE pt.post_id = ?");
                $stmtTags->execute([$post['id']]);
                $tags = $stmtTags->fetchAll();
                $post['tags'] = $tags;

                // Pre-calculate dates for templates
                $pubDateStr = $post['published_at'] ?? $post['created_at'];
                $modDateStr = $post['updated_at'] ?? $pubDateStr;
                $post['pub_ts'] = strtotime((string)$pubDateStr) ?: time();
                $post['mod_ts'] = strtotime((string)$modDateStr) ?: $post['pub_ts'];
                $dateFormat = get_option('date_format', 'Y.m.d');
                $post['formatted_pub_date'] = date($dateFormat, $post['pub_ts']);
                $post['formatted_mod_date'] = date($dateFormat, $post['mod_ts']);

                $result['data']['post'] = $post;
                $result['data']['tags'] = $tags;
            }
        }
    }

    return $result;
}

// Helper functions

function get_nav_menus($location = 'header')
{
    global $activeTheme;
    $pdo = App::db();
    $currentTheme = $activeTheme ?? 'default';

    try {
        $sql = "SELECT * FROM nav_menus
                WHERE location = ?
                AND (target_theme = 'all' OR target_theme = ?)
                ORDER BY sort_order ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$location, $currentTheme]);
        $menus = $stmt->fetchAll();

        foreach ($menus as &$m) {
            if (!empty($m['url'])) {
                $m['url'] = resolve_url($m['url']);
            }
        }
        unset($m);

        return $menus;
    } catch (Exception $e) {
        return [];
    }
}

function get_front_banners($context = [])
{
    $pdo = App::db();
    $banners = [];

    $pageType = $context['type'] ?? 'home';
    $pageData = $context['data'] ?? [];

    $currentCatId = 0;
    $currentPostId = 0;

    if ($pageType === 'category' && isset($pageData['category']['id'])) {
        $currentCatId = $pageData['category']['id'];
    } elseif ($pageType === 'single' && isset($pageData['post'])) {
        $currentPostId = $pageData['post']['id'];
        $currentCatId = $pageData['post']['category_id'] ?? 0;
    }

    try {
        $stmt = $pdo->query("SELECT * FROM banners WHERE is_active = 1 ORDER BY sort_order ASC");
        $all_banners = $stmt->fetchAll();

        foreach ($all_banners as $row) {
            $show = false;
            $targetType = $row['target_type'] ?? 'all';
            $targetId = (int)($row['target_id'] ?? 0);

            switch ($targetType) {
                case 'all':
                    $show = true;
                    break;
                case 'home':
                    if ($pageType === 'home') $show = true;
                    break;
                case 'category':
                    if ($targetId > 0 && (int)$currentCatId === $targetId) $show = true;
                    break;
                case 'page':
                    if ($targetId > 0 && (int)$currentPostId === $targetId) $show = true;
                    break;
            }

            if ($show) {
                $bType = $row['type'] ?? 'image';
                if ($bType === 'html' && !empty($row['html_code'])) {
                    $row['html_code'] = grinds_url_to_view($row['html_code']);
                    $banners[$row['position']][] = $row;
                } elseif ($bType === 'image' && !empty($row['image_url'])) {
                    $row['image_url'] = resolve_url($row['image_url']);
                    $row['link_url'] = resolve_url($row['link_url']);

                    // Prepare display styles
                    $width = isset($row['image_width']) && $row['image_width'] > 0 ? (int)$row['image_width'] : 100;
                    $row['anchor_style'] = ($width < 100) ? "width: {$width}%; max-width: 100%; margin-left: auto; margin-right: auto;" : "";
                    $row['anchor_class'] = "block";
                    $row['image_class'] = "rounded w-full";

                    $banners[$row['position']][] = $row;
                }
            }
        }
    } catch (Exception $e) {
    }
    return $banners;
}

function display_banners($position, $context = [])
{
    $banners = get_front_banners($context);
    if (!empty($banners[$position])) {
        $position_banners = $banners[$position];
        $currentTheme = grinds_get_active_theme();
        $themePath = ROOT_PATH . '/theme/' . $currentTheme . '/';

        if (file_exists($themePath . 'parts/banners.php')) {
            include $themePath . 'parts/banners.php';
        } elseif (file_exists(ROOT_PATH . '/theme/default/parts/banners.php')) {
            include ROOT_PATH . '/theme/default/parts/banners.php';
        } else {
            echo '<div class="gap-4 grid mb-6">';
            foreach ($position_banners as $b) {
                $bType = $b['type'] ?? 'image';
                if ($bType === 'html') {
                    echo '<div class="banner-html">';
                    echo $b['html_code'];
                    echo '</div>';
                } else {
                    $imgUrl = resolve_url($b['image_url']);
                    $linkUrl = resolve_url($b['link_url']);
                    $anchorStyle = $b['anchor_style'] ?? '';
                    $anchorClass = $b['anchor_class'] ?? 'block';
                    $imgClass = $b['image_class'] ?? 'rounded w-full';

                    if (!empty($linkUrl)) {
                        echo '<a href="' . h($linkUrl) . '" target="_blank" class="' . $anchorClass . '" style="' . $anchorStyle . '">';
                    } else {
                        echo '<div class="' . $anchorClass . '" style="' . $anchorStyle . '">';
                    }
                    echo '<img src="' . h($imgUrl) . '" class="' . $imgClass . '" loading="lazy">';
                    if (!empty($linkUrl)) {
                        echo '</a>';
                    } else {
                        echo '</div>';
                    }
                }
            }
            echo '</div>';
        }
    }
}

function get_sidebar_widgets()
{
    static $cachedWidgets = null;
    if ($cachedWidgets !== null) {
        return $cachedWidgets;
    }

    global $activeTheme;
    $pdo = App::db();
    $currentTheme = $activeTheme ?? 'default';

    try {
        $sql = "SELECT * FROM widgets
                WHERE is_active = 1
                AND (target_theme = 'all' OR target_theme = ?)
                ORDER BY sort_order ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$currentTheme]);
        $cachedWidgets = $stmt->fetchAll();
        return $cachedWidgets;
    } catch (Exception $e) {
        $cachedWidgets = [];
        return $cachedWidgets;
    }
}

/**
 * Render a full page based on request URL.
 *
 * @param string $requestUrl
 * @param array $options Options: 'custom_content', 'preview_data', 'suppress_response_code'
 * @return string Rendered HTML
 */
function grinds_render_page($requestUrl, $options = [])
{
    $pdo = App::db();

    // Resolve route
    if (isset($options['custom_content'])) {
        $result = ['title' => _t('search'), 'desc' => _t('search'), 'type' => 'search', 'data' => []];
    } elseif (!empty($options['preview_data'])) {
        $previewData = $options['preview_data'];
        $categoryName = '';
        $categorySlug = '';
        $categoryTheme = '';

        if (!empty($previewData['category_id'])) {
            if ($pdo) {
                $stmtCat = $pdo->prepare("SELECT name, slug, category_theme FROM categories WHERE id = ?");
                $stmtCat->execute([$previewData['category_id']]);
                $cat = $stmtCat->fetch();
                if ($cat) {
                    $categoryName = $cat['name'];
                    $categorySlug = $cat['slug'];
                    $categoryTheme = $cat['category_theme'];
                }
            }
        }

        $previewPost = array_merge($previewData, [
            'id' => $previewData['id'] ?? 0,
            'category_name' => $categoryName,
            'category_slug' => $categorySlug,
            'category_theme' => $categoryTheme,
            'content_decoded' => isset($previewData['content']) ? json_decode($previewData['content'], true) : [],
            'hero_settings_decoded' => isset($previewData['hero_settings']) ? json_decode($previewData['hero_settings'], true) : [],
        ]);

        // Pre-calculate dates for templates (Preview)
        $pubDateStr = $previewPost['published_at'] ?? $previewPost['created_at'] ?? date('Y-m-d H:i:s');
        $modDateStr = $previewPost['updated_at'] ?? $pubDateStr;
        $previewPost['pub_ts'] = strtotime((string)$pubDateStr) ?: time();
        $previewPost['mod_ts'] = strtotime((string)$modDateStr) ?: $previewPost['pub_ts'];
        $dateFormat = get_option('date_format', 'Y.m.d');
        $previewPost['formatted_pub_date'] = date($dateFormat, $previewPost['pub_ts']);
        $previewPost['formatted_mod_date'] = date($dateFormat, $previewPost['mod_ts']);

        $result = [
            'type' => 'single',
            'title' => $previewPost['title'],
            'desc' => $previewPost['description'],
            'data' => [
                'post' => $previewPost,
                'tags' => $previewData['__tags_preview'] ?? []
            ],
            'is_preview' => true
        ];
    } else {
        $result = resolve_front_request($requestUrl);
    }

    $pageType  = $result['type'];
    $pageTitle = $result['title'];
    $pageData  = $result['data'];
    $pageDesc  = $result['desc'] ?? '';
    $isPreview = $result['is_preview'] ?? false;

    // Expose context
    $GLOBALS['pageType'] = $pageType;
    $GLOBALS['pageData'] = $pageData;
    $GLOBALS['pageTitle'] = $pageTitle;
    $GLOBALS['pageDesc'] = $pageDesc;

    // Determine theme
    $activeTheme = grinds_get_active_theme();
    $customTheme = '';
    if (isset($pageData['post'])) {
        if (!empty($pageData['post']['page_theme'])) {
            $customTheme = $pageData['post']['page_theme'];
        } elseif (!empty($pageData['post']['category_theme'])) {
            $customTheme = $pageData['post']['category_theme'];
        }
    } elseif ($pageType === 'category' && isset($pageData['category'])) {
        if (!empty($pageData['category']['category_theme'])) {
            $customTheme = $pageData['category']['category_theme'];
        }
    }

    if ($customTheme && preg_match('/^[a-zA-Z0-9_-]+$/', $customTheme) && is_dir(ROOT_PATH . '/theme/' . $customTheme)) {
        $activeTheme = $customTheme;
    }

    $filteredTheme = apply_filters('grinds_active_theme', $activeTheme, $pageType, $pageData);
    if (preg_match('/^[a-zA-Z0-9_-]+$/', $filteredTheme) && is_dir(ROOT_PATH . '/theme/' . $filteredTheme)) {
        $activeTheme = $filteredTheme;
    }
    $GLOBALS['activeTheme'] = $activeTheme;
    $themePath = ROOT_PATH . '/theme/' . $activeTheme . '/';

    grinds_load_theme_functions($activeTheme);



    // Render content
    ob_start();

    if (isset($options['custom_content'])) {
        echo $options['custom_content'];
    } else {
        $defaultThemePath = ROOT_PATH . '/theme/default/';

        $getTemplatePath = function ($files) use ($themePath, $defaultThemePath) {
            if (!is_array($files)) $files = [$files];
            foreach ($files as $file) {
                if (file_exists($themePath . $file)) return $themePath . $file;
            }
            foreach ($files as $file) {
                if (file_exists($defaultThemePath . $file)) return $defaultThemePath . $file;
            }
            return false;
        };

        switch ($pageType) {
            case '404':
                if (empty($options['suppress_response_code'])) http_response_code(404);
                $targetFile = $getTemplatePath('404.php');
                if ($targetFile) {
                    include $targetFile;
                } else {
                    echo '<div style="text-align:center; padding:100px 20px;"><h1>404</h1><p>Page Not Found</p></div>';
                }
                break;
            case 'home':
            case 'search':
                $targetFile = $getTemplatePath('home.php');
                if ($targetFile) include $targetFile;
                break;
            case 'category':
                $targetFile = $getTemplatePath(['category.php', 'archive.php']);
                if ($targetFile) include $targetFile;
                break;
            case 'tag':
                $targetFile = $getTemplatePath(['tag.php', 'archive.php']);
                if ($targetFile) include $targetFile;
                break;
            case 'single':
                $filesToTry = [];
                if (isset($pageData['post']['slug']) && preg_match('/^[a-zA-Z0-9_-]+$/', $pageData['post']['slug'])) {
                    if (!in_array($pageData['post']['slug'], ['functions', 'layout', '404', 'index'])) {
                        $filesToTry[] = $pageData['post']['slug'] . '.php';
                    }
                }
                if (isset($pageData['post']['type']) && $pageData['post']['type'] === 'page') {
                    $filesToTry[] = 'page.php';
                }
                $filesToTry[] = 'single.php';

                $targetFile = $getTemplatePath($filesToTry);
                if ($targetFile) include $targetFile;
                break;
            default:
                $targetFile = $getTemplatePath('home.php');
                if ($targetFile) include $targetFile;
                break;
        }
    }
    $content = ob_get_clean();

    // Render layout
    ob_start();
    if (file_exists($themePath . 'layout.php')) {
        include $themePath . 'layout.php';
    } elseif (file_exists(ROOT_PATH . '/theme/default/layout.php')) {
        include ROOT_PATH . '/theme/default/layout.php';
    } else echo $content;

    return ob_get_clean();
}
