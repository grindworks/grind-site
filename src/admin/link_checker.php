<?php

/**
 * link_checker.php
 *
 * Scan posts for broken internal links.
 */
require_once __DIR__ . '/bootstrap.php';

/** @var \PDO $pdo */
if (!isset($pdo)) {
    exit;
}

$params = Routing::getParams();

// Check permissions
if (!current_user_can('manage_tools')) {
    // Allow single scan
    $is_single_scan = (isset($params['action']) && $params['action'] === 'scan' && !empty($params['id']));
    if (!($is_single_scan && current_user_can('manage_posts'))) {
        if (isset($params['action'])) {
            json_response(['success' => false, 'error' => 'Access Denied'], 403);
        }
        redirect('admin/index.php');
    }
}

// Extract URLs handled by utils.php (grinds_extract_urls)

// Validate internal link
if (!function_exists('check_internal_link')) {
    function check_internal_link($url, $pdo)
    {
        // Skip empty/anchors
        if (empty($url) || $url === '#' || strpos($url, '#') === 0) return ['isValid' => true];
        if (preg_match('/^(mailto|tel|sms|javascript|line|viber|whatsapp|data):/i', $url)) return ['isValid' => true];

        // Skip PHP tags
        if (strpos($url, '<?') === 0) return ['isValid' => true];

        // Check external
        if (preg_match('/^https?:\/\//', $url)) {
            // Check local URL
            if (defined('BASE_URL')) {
                $baseUrl = rtrim(BASE_URL, '/');
                if (strpos($url, $baseUrl . '/') === 0 || $url === $baseUrl) {
                    $url = substr($url, strlen($baseUrl));
                } else {
                    return ['isValid' => true];
                }
            } else {
                return ['isValid' => true];
            }
        }

        // Handle placeholder
        if (strpos($url, '{{CMS_URL}}') !== false) {
            $url = str_replace('{{CMS_URL}}', '', $url);
        }

        $isValid = true;
        $reason = '';

        // Check query params
        $query = parse_url($url, PHP_URL_QUERY);
        if ($query) {
            parse_str($query, $params);
            if (isset($params['id']) && is_numeric($params['id'])) {
                $repo = new PostRepository($pdo);
                $posts = $repo->fetch(['ids' => [$params['id']], 'status' => 'all']);
                $info = $posts[0] ?? null;

                if (!$info) {
                    return ['isValid' => false, 'reason' => _t('link_err_id_not_found')];
                }

                if ($info['status'] !== 'published') {
                    return ['isValid' => false, 'reason' => _t('link_err_not_published')];
                }
                if (!empty($info['published_at']) && strtotime($info['published_at']) > time()) {
                    return ['isValid' => false, 'reason' => _t('link_err_scheduled')];
                }

                return ['isValid' => true];
            }
        }

        // Normalize URL
        $parsedPath = parse_url($url, PHP_URL_PATH);

        if ($parsedPath === null || $parsedPath === '') {
            return ['isValid' => true];
        }

        $path = urldecode($parsedPath);

        // Ensure leading slash
        if (substr($path, 0, 1) !== '/') {
            $path = '/' . $path;
        }

        // Handle subdirectory
        if (defined('BASE_URL')) {
            $basePath = parse_url(BASE_URL, PHP_URL_PATH);
            if ($basePath && $basePath !== '/') {
                $basePath = rtrim(urldecode($basePath), '/');
                // Strip base path
                if (strpos($path, $basePath) === 0) {
                    // Check boundary
                    $after = substr($path, strlen($basePath), 1);
                    if ($after === '' || $after === '/') {
                        $path = substr($path, strlen($basePath));
                    }
                }
            }
        }

        // Handle index.php
        if (strpos($path, '/index.php/') === 0) {
            $path = substr($path, 11);
        } elseif ($path === '/index.php') {
            $path = '';
        }

        $path = trim($path, '/');

        // Validate root
        if ($path === '' || $path === 'index.php') return ['isValid' => true];

        // Validate special routes
        if ($path === 'search' || strpos($path, 'search/') === 0 || $path === '404' || $path === 'contact') return ['isValid' => true];

        // Check known routes
        // Check category
        if (preg_match('/^category\/([^\/]+)/', $path, $m)) {
            $stmt = $pdo->prepare("SELECT 1 FROM categories WHERE slug = ?");
            $stmt->execute([$m[1]]);
            if (!$stmt->fetchColumn()) {
                $isValid = false;
                $reason = _t('link_err_cat_not_found');
            }
        }
        // Check tag
        elseif (preg_match('/^tag\/([^\/]+)/', $path, $m)) {
            $stmt = $pdo->prepare("SELECT 1 FROM tags WHERE slug = ?");
            $stmt->execute([$m[1]]);
            if (!$stmt->fetchColumn()) {
                $isValid = false;
                $reason = _t('link_err_tag_not_found');
            }
        }
        // Check post slug
        else {
            // Ignore admin
            if ($path === 'admin' || strpos($path, 'admin/') === 0) {
                return ['isValid' => true];
            }

            // Check assets physical file existence
            if ($path === 'assets' || strpos($path, 'assets/') === 0) {
                $localFilePath = ROOT_PATH . '/' . $path;
                if (!file_exists($localFilePath)) {
                    return ['isValid' => false, 'reason' => _t('err_file_not_found')];
                }
                return ['isValid' => true];
            }

            // Check slug existence
            $repo = new PostRepository($pdo);
            $posts = $repo->fetch(['slug' => $path, 'status' => 'all']);
            $info = $posts[0] ?? null;

            if ($info) {
                if ($info['status'] !== 'published') {
                    $isValid = false;
                    $reason = _t('link_err_not_published');
                } elseif (!empty($info['published_at']) && strtotime($info['published_at']) > time()) {
                    $isValid = false;
                    $reason = _t('link_err_scheduled');
                }
            } else {
                // Try loose match
                $parts = explode('/', $path);
                $lastSegment = end($parts);

                $posts = $repo->fetch(['slug' => $lastSegment, 'status' => 'all']);
                $info = $posts[0] ?? null;

                if ($info) {
                    if ($info['status'] !== 'published') {
                        $isValid = false;
                        $reason = _t('link_err_not_published');
                    } elseif (!empty($info['published_at']) && strtotime($info['published_at']) > time()) {
                        $isValid = false;
                        $reason = _t('link_err_scheduled');
                    } else {
                        // Valid via fallback
                        return ['isValid' => true];
                    }
                } else {
                    // Prevent traversal
                    if (strpos($path, '..') === false && file_exists(ROOT_PATH . '/' . $path)) {
                        return ['isValid' => true];
                    }

                    $isValid = false;
                    $reason = _t('link_err_slug_not_found');
                }
            }
        }

        return ['isValid' => $isValid, 'reason' => $reason];
    }
}

// Stop if included
if (basename($_SERVER['SCRIPT_FILENAME']) !== basename(__FILE__)) {
    return;
}

// Handle API request
if (isset($params['action']) && $params['action'] === 'scan') {
    $type = $params['type'] ?? 'posts';
    $offset = (int)($params['offset'] ?? 0);
    $limit = (int)($params['limit'] ?? 20);
    $id = isset($params['id']) ? (int)$params['id'] : null;

    $brokenLinks = [];
    $checkedCount = 0;
    $total = 0;
    $nextOffset = $offset;
    $hasMore = false;

    try {
        if ($type === 'posts') {
            $repo = new PostRepository($pdo);
            if ($id) {
                $items = $repo->fetch(['ids' => [$id], 'status' => 'all']);
                $total = count($items);
            } else {
                $total = $repo->count(['status' => 'all']);
                $items = $repo->fetch(['status' => 'all'], $limit, $offset);
            }

            foreach ($items as $item) {
                $urls = grinds_extract_urls($item['content']);
                foreach ($urls as $url) {
                    $res = check_internal_link($url, $pdo);
                    $checkedCount++;
                    if (!$res['isValid']) {
                        $brokenLinks[] = [
                            'source_type' => 'Post',
                            'source_id' => $item['id'],
                            'source_title' => $item['title'],
                            'url' => $url,
                            'reason' => $res['reason']
                        ];
                    }
                }
            }
            $nextOffset += count($items);
            $hasMore = $nextOffset < $total;
        } elseif ($type === 'menus') {
            try {
                $total = $pdo->query("SELECT COUNT(*) FROM nav_menus")->fetchColumn();
                $stmt = $pdo->prepare("SELECT id, label, url FROM nav_menus LIMIT ? OFFSET ?");
                $stmt->bindValue(1, $limit, PDO::PARAM_INT);
                $stmt->bindValue(2, $offset, PDO::PARAM_INT);
                $stmt->execute();
                $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($items as $item) {
                    if (empty($item['url'])) continue;
                    $res = check_internal_link($item['url'], $pdo);
                    $checkedCount++;
                    if (!$res['isValid']) {
                        $brokenLinks[] = [
                            'source_type' => 'Menu',
                            'source_id' => $item['id'],
                            'source_title' => $item['label'],
                            'url' => $item['url'],
                            'reason' => $res['reason']
                        ];
                    }
                }
                $nextOffset += count($items);
                $hasMore = $nextOffset < $total;
            } catch (Exception $e) {
                // Handle missing table
            }
        } elseif ($type === 'widgets') {
            try {
                $total = $pdo->query("SELECT COUNT(*) FROM widgets WHERE is_active = 1")->fetchColumn();
                $stmt = $pdo->prepare("SELECT id, title, content, settings FROM widgets WHERE is_active = 1 LIMIT ? OFFSET ?");
                $stmt->bindValue(1, $limit, PDO::PARAM_INT);
                $stmt->bindValue(2, $offset, PDO::PARAM_INT);
                $stmt->execute();
                $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($items as $item) {
                    $textToScan = ($item['content'] ?? '') . ' ' . ($item['settings'] ?? '');
                    if (empty(trim($textToScan))) continue;
                    $urls = [];
                    if (preg_match_all('/href=["\']([^"\']+)["\']/', $textToScan, $matches)) {
                        foreach ($matches[1] as $u) $urls[] = $u;
                    }
                    $urls = array_unique($urls);

                    foreach ($urls as $url) {
                        $res = check_internal_link($url, $pdo);
                        $checkedCount++;
                        if (!$res['isValid']) {
                            $brokenLinks[] = [
                                'source_type' => 'Widget',
                                'source_id' => $item['id'],
                                'source_title' => $item['title'] ?: 'Widget #' . $item['id'],
                                'url' => $url,
                                'reason' => $res['reason']
                            ];
                        }
                    }
                }
                $nextOffset += count($items);
                $hasMore = $nextOffset < $total;
            } catch (Exception $e) {
                // Handle missing table
            }
        } elseif ($type === 'banners') {
            try {
                $total = $pdo->query("SELECT COUNT(*) FROM banners WHERE is_active = 1")->fetchColumn();
                $stmt = $pdo->prepare("SELECT id, link_url, type, html_code FROM banners WHERE is_active = 1 LIMIT ? OFFSET ?");
                $stmt->bindValue(1, $limit, PDO::PARAM_INT);
                $stmt->bindValue(2, $offset, PDO::PARAM_INT);
                $stmt->execute();
                $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($items as $item) {
                    $urls = [];
                    if (!empty($item['link_url'])) {
                        $urls[] = $item['link_url'];
                    }
                    if (($item['type'] ?? '') === 'html' && !empty($item['html_code'])) {
                        if (preg_match_all('/href=["\']([^"\']+)["\']/', $item['html_code'], $matches)) {
                            foreach ($matches[1] as $u) $urls[] = $u;
                        }
                    }
                    $urls = array_unique($urls);

                    foreach ($urls as $url) {
                        $res = check_internal_link($url, $pdo);
                        $checkedCount++;
                        if (!$res['isValid']) {
                            $brokenLinks[] = [
                                'source_type' => 'Banner',
                                'source_id' => $item['id'],
                                'source_title' => 'Banner #' . $item['id'],
                                'url' => $url,
                                'reason' => $res['reason']
                            ];
                        }
                    }
                }
                $nextOffset += count($items);
                $hasMore = $nextOffset < $total;
            } catch (Exception $e) {
                // Handle missing table
            }
        }

        json_response([
            'success' => true,
            'broken_links' => $brokenLinks,
            'checked_count' => $checkedCount,
            'has_more' => $hasMore,
            'next_offset' => $nextOffset,
            'total' => $total
        ]);
    } catch (Exception $e) {
        json_response(['success' => false, 'error' => $e->getMessage()]);
    }
}

$page_title = _t('link_checker_title');

// Start buffering
ob_start();
?>

<script>
    window.grindsTranslations = {
        ...window.grindsTranslations,
        js_scanning_type: <?= json_encode(_t('js_scanning_type') ?: 'Scanning %s...') ?>,
        type_posts: <?= json_encode(_t('menu_posts')) ?>,
        type_menus: <?= json_encode(_t('menu_menus')) ?>,
        type_widgets: <?= json_encode(_t('menu_widgets')) ?>,
        type_banners: <?= json_encode(_t('menu_banners')) ?>
    };
</script>

<!-- Init Alpine -->
<div x-data="linkChecker()" class="space-y-6">
    <div class="flex sm:flex-row flex-col justify-between sm:items-center gap-4">
        <div>
            <h2 class="flex items-center gap-2 font-bold text-theme-text text-xl">
                <svg class="w-6 h-6 text-theme-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-link"></use>
                </svg>
                <?= _t('link_checker_title') ?>
            </h2>
            <p class="opacity-60 mt-1 ml-8 text-theme-text text-sm">
                <span x-text="statusMsg"></span>
                <span x-show="!scanning && checkedCount > 0">
                    - <?= str_replace('%s', '<span x-text="checkedCount"></span>', _t('link_checker_count')) ?>
                    <span x-show="brokenLinks.length > 0" class="ml-1 font-bold text-theme-danger">⚠ <span x-text="brokenLinks.length"></span> <?= _t('error') ?></span>
                    <span x-show="brokenLinks.length === 0" class="ml-1 font-bold text-theme-success">✔ <?= _t('health_ok') ?></span>
                </span>
            </p>
        </div>
        <button @click="startScan()" :disabled="scanning" class="flex items-center gap-2 disabled:opacity-70 shadow-theme px-6 py-2.5 rounded-theme font-bold text-sm transition-all disabled:cursor-not-allowed btn-primary">

            <!-- Default Icon -->
            <svg x-show="!scanning" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-path"></use>
            </svg>

            <!-- Loading Spinner -->
            <svg x-show="scanning" x-cloak class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-path"></use>
            </svg>

            <span x-text="scanning ? <?= h(json_encode(_t('btn_scanning'))) ?> : (checkedCount > 0 ? <?= h(json_encode(_t('btn_rescan'))) ?> : <?= h(json_encode(_t('btn_scan'))) ?>)"></span>
        </button>
    </div>

    <div class="bg-theme-surface shadow-theme p-6 border border-theme-border rounded-theme">
        <!-- Progress Bar -->
        <div x-show="scanning" class="bg-theme-bg mb-6 rounded-full w-full h-2.5" x-cloak>
            <div class="bg-theme-primary rounded-full h-2.5 transition-all duration-300" :style="'width: ' + progress + '%'"></div>
        </div>

        <!-- Initial State -->
        <div x-show="!scanning && checkedCount === 0" class="flex flex-col justify-center items-center py-16 px-4 bg-theme-bg/30 border-2 border-theme-border border-dashed rounded-theme text-center" x-cloak>
            <div class="flex justify-center items-center w-16 h-16 mb-4 rounded-full bg-theme-surface shadow-sm text-theme-text opacity-50">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-magnifying-glass"></use>
                </svg>
            </div>
            <h3 class="mb-1 font-bold text-theme-text text-lg opacity-80"><?= _t('link_checker_init_title') ?></h3>
            <p class="text-sm text-theme-text opacity-60 mt-1"><?= _t('link_checker_init_desc') ?></p>
        </div>

        <!-- No Broken Links Message -->
        <div x-show="!scanning && checkedCount > 0 && brokenLinks.length === 0" class="flex flex-col justify-center items-center py-16 px-4 bg-theme-bg/30 border-2 border-theme-border border-dashed rounded-theme text-center" x-cloak>
            <div class="flex justify-center items-center w-16 h-16 mb-4 rounded-full bg-theme-surface shadow-sm text-theme-success opacity-80">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-check-circle"></use>
                </svg>
            </div>
            <h3 class="mb-1 font-bold text-theme-text text-lg opacity-80"><?= _t('msg_no_broken_links') ?></h3>
            <p class="text-sm text-theme-text opacity-60 mt-1"><?= _t('msg_all_links_valid') ?></p>
        </div>

        <!-- Results Table -->
        <div x-show="brokenLinks.length > 0" class="border border-theme-border rounded-theme overflow-x-auto" x-cloak>
            <table class="min-w-full leading-normal">
                <thead class="bg-theme-bg/50 border-theme-border border-b font-bold text-theme-text/60 text-xs text-left uppercase tracking-wider">
                    <tr>
                        <th class="px-6 py-4"><?= _t('col_source') ?></th>
                        <th class="px-6 py-4"><?= _t('col_broken_url') ?></th>
                        <th class="px-6 py-4"><?= _t('col_reason') ?></th>
                        <th class="px-6 py-4 text-right"><?= _t('col_action') ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-theme-border">
                    <template x-for="link in brokenLinks" :key="link.source_type + link.source_id + link.url">
                        <tr class="hover:bg-theme-bg/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex flex-col">
                                    <span class="font-bold text-theme-text text-sm" x-text="link.source_title"></span>
                                    <span class="opacity-50 text-theme-text text-xs" x-text="link.source_type + ' #' + link.source_id"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2 max-w-xs">
                                    <span class="font-mono text-theme-danger text-sm truncate" :title="link.url" x-text="link.url"></span>
                                    <a :href="link.url.match(/^\s*javascript:/i) ? '#' : link.url" target="_blank" class="opacity-30 hover:opacity-100 text-theme-text hover:text-theme-primary">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-top-right-on-square"></use>
                                        </svg>
                                    </a>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center bg-theme-danger/10 px-2 py-0.5 border border-theme-danger/20 rounded-theme font-medium text-theme-danger text-xs" x-text="link.reason"></span>
                            </td>
                            <td class="px-6 py-4 font-medium text-sm text-right whitespace-nowrap">
                                <a :href="getEditUrl(link)" target="_blank" rel="noopener noreferrer" class="font-bold text-theme-primary hover:text-theme-primary-dark hover:underline">
                                    <?= _t('edit') ?>
                                </a>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('linkChecker', () => ({
            scanning: false,
            progress: 0,
            statusMsg: <?= json_encode(_t('link_checker_desc', 0)) ?>,
            brokenLinks: [],
            checkedCount: 0,

            async startScan() {
                this.scanning = true;
                this.brokenLinks = [];
                this.checkedCount = 0;
                this.progress = 0;
                this.statusMsg = <?= json_encode(_t('js_initializing')) ?>;

                try {
                    await this.scanPaged('posts');
                    await this.scanPaged('menus');
                    await this.scanPaged('widgets');
                    await this.scanPaged('banners');

                    this.progress = 100;
                    this.statusMsg = <?= json_encode(_t('js_scan_complete')) ?>;
                    setTimeout(() => {
                        this.scanning = false;
                    }, 500);
                } catch (e) {
                    showToast('Error: ' + e.message, 'error');
                    this.scanning = false;
                }
            },

            async scanPaged(type) {
                let offset = 0;
                let limit = 20;
                let hasMore = true;

                while (hasMore) {
                    const typeLabel = window.grindsTranslations['type_' + type] || type;
                    const baseMsg = (window.grindsTranslations.js_scanning_type || 'Scanning %s...').replace('%s', typeLabel);
                    this.statusMsg = `${baseMsg} (${offset})`;
                    const res = await fetch(`?action=scan&type=${type}&offset=${offset}&limit=${limit}`);
                    if (!res.ok) throw new Error('Network error');

                    const data = await res.json();
                    if (!data.success) throw new Error(data.error);

                    if (data.broken_links.length > 0) {
                        this.brokenLinks.push(...data.broken_links);
                    }
                    this.checkedCount += data.checked_count;

                    hasMore = data.has_more;
                    offset = data.next_offset;

                    // Progress estimation
                    if (type === 'posts' && data.total > 0) {
                        this.progress = Math.min(70, (offset / data.total) * 70);
                    } else if (type === 'menus') {
                        this.progress = 75;
                    } else if (type === 'widgets') {
                        this.progress = 85;
                    } else if (type === 'banners') {
                        this.progress = 95;
                    }
                }
            },

            getEditUrl(link) {
                switch (link.source_type) {
                    case 'Post':
                        return 'posts.php?action=edit&id=' + link.source_id;
                    case 'Menu':
                        return 'menus.php';
                    case 'Widget':
                        return 'widgets.php?edit_id=' + link.source_id;
                    case 'Banner':
                        return 'banners.php?edit_id=' + link.source_id;
                    default:
                        return '#';
                }
            }
        }));
    });
</script>

<?php
$content = ob_get_clean();
$current_page = 'link_checker';
require_once __DIR__ . '/layout/loader.php';
?>
