<?php

/**
 * GrindsSSG.php
 *
 * Core Static Site Generation (SSG) Engine.
 * Handles HTML rendering, asset extraction, and packaging.
 * Can be invoked via Web API or CLI.
 */
if (!defined('GRINDS_APP')) exit;

class GrindsSSG
{
    private $pdo;
    private $config;
    private $buildId;
    private $exportDir;
    private $rootDir;
    private $excludeExts = [
        'php',
        'phtml',
        'php3',
        'php4',
        'php5',
        'phps',
        'sh',
        'bash',
        'bat',
        'cmd',
        'ps1',
        'sql',
        'sqlite',
        'db',
        'mdb',
        'log',
        'ini',
        'env',
        'htpasswd',
        'lock',
        'git',
        'gitignore',
        'gitmodules',
        'bak',
        'old',
        'swp',
        'tmp',
        'temp',
        'scss',
        'sass',
        'less',
        'styl',
        'ts',
        'coffee',
        'twig',
        'tpl',
        'md',
        'markdown',
        'map'
    ];

    public function __construct($pdo, $inputData)
    {
        $this->pdo = $pdo;
        $this->rootDir = $this->normalizePath(ROOT_PATH);

        // Initialize Config
        if (isset($inputData['step']) && $inputData['step'] === 'init') {
            $this->initSessionConfig($inputData);
        } else {
            // Release session lock for heavy steps to prevent blocking other requests (Safe for CLI)
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }
        }

        $this->config = $_SESSION['ssg_config'] ?? [];
        $this->buildId = $this->resolveBuildId($inputData);
        $this->exportDir = $this->rootDir . '/data/tmp/static_export_' . $this->buildId;
    }

    /**
     * Normalize path to use forward slashes and remove trailing slash.
     */
    private function normalizePath($path)
    {
        return rtrim(str_replace('\\', '/', $path), '/');
    }

    private function initSessionConfig($data)
    {
        $mode = $data['mode'] ?? 'full';
        $lastExport = ($mode === 'diff') ? get_option('last_ssg_export') : null;

        $_SESSION['ssg_config'] = [
            'base_url' => rtrim($data['baseUrl'] ?? '', '/'),
            'form_endpoint' => $data['formEndpoint'] ?? '',
            'mode' => $mode,
            'last_export' => $lastExport,
            'search_scope' => $data['searchScope'] ?? 'title_body'
        ];

        // Save persistent options (Only if running in a context where functions exist)
        if (function_exists('update_option')) {
            if (isset($data['baseUrl'])) update_option('ssg_base_url', $data['baseUrl']);
            if (isset($data['formEndpoint'])) update_option('ssg_form_endpoint', $data['formEndpoint']);
            if (isset($data['maxResults'])) update_option('ssg_max_results', (int)$data['maxResults']);
            if (isset($data['searchScope'])) update_option('ssg_search_scope', $data['searchScope']);
            if (isset($data['searchChunkSize'])) update_option('ssg_search_chunk_size', (int)$data['searchChunkSize']);
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
    }

    private function resolveBuildId($data)
    {
        $id = $data['buildId'] ?? $data['build_id'] ?? '';
        if (empty($id) || !preg_match('/^[a-zA-Z0-9_]+$/', $id)) {
            return 'build_' . bin2hex(random_bytes(8));
        }
        return $id;
    }

    /**
     * Build a single page for the Virtual Publish Queue.
     * This bypasses the ZIP creation and writes directly to a persistent directory.
     */
    public function buildSinglePage($url, $actionType = 'build')
    {
        $exportDir = $this->rootDir . '/data/ssg_output';
        if (!is_dir($exportDir)) {
            @mkdir($exportDir, 0775, true);
        }

        $cleanPath = ltrim($url, '/');
        // Prevent path traversal
        if (strpos($cleanPath, '..') !== false) return false;

        $filePath = $exportDir . '/' . $cleanPath;

        if ($actionType === 'delete') {
            if (file_exists($filePath)) @unlink($filePath);
            return true;
        }

        // Special handling for dynamic feeds
        if (in_array($cleanPath, ['sitemap.xml', 'rss.xml', 'robots.txt', 'llms.txt', 'llms-full.txt'])) {
            $ssgBaseUrl = rtrim($this->config['base_url'] ?? '', '/');
            if ($cleanPath === 'sitemap.xml') {
                require_once $this->rootDir . '/sitemap.php';
                (new SitemapGenerator($this->pdo, $ssgBaseUrl, true))->generateToFile($filePath);
            } elseif ($cleanPath === 'rss.xml') {
                require_once $this->rootDir . '/rss.php';
                (new RssGenerator($this->pdo, $ssgBaseUrl, true))->generateToFile($filePath);
            } elseif ($cleanPath === 'robots.txt') {
                require_once $this->rootDir . '/robots.php';
                (new RobotsGenerator($ssgBaseUrl, true))->generateToFile($filePath);
            } elseif ($cleanPath === 'llms.txt') {
                require_once $this->rootDir . '/llms.php';
                (new LlmsTxtGenerator($this->pdo, $ssgBaseUrl, true))->generateToFile($filePath);
            } elseif ($cleanPath === 'llms-full.txt') {
                require_once $this->rootDir . '/llms-full.php';
                (new LlmsFullGenerator($this->pdo, $ssgBaseUrl, true))->generateToFile($filePath);
            }
            return true;
        }

        // Define mock page structure for rendering
        $slug = preg_replace('/\.html$/', '', $cleanPath);
        if ($slug === 'index') $slug = '/';

        $page = [
            'url' => $slug,
            'slug' => $slug,
            'depth' => substr_count($cleanPath, '/'),
            'page' => 1
        ];

        // Render HTML
        $jsToolPath = function_exists('resolve_url') ? resolve_url('assets/js/static_tools.js') : '/assets/js/static_tools.js';
        $html = $this->renderPage($page, $this->config, $jsToolPath);

        $saveDir = dirname($filePath);
        if (!is_dir($saveDir)) @mkdir($saveDir, 0775, true);

        return file_put_contents($filePath, $html) !== false;
    }

    public function run($step, $data)
    {
        switch ($step) {
            case 'init':
                return $this->stepInit();
            case 'generate_pages':
                return $this->stepGeneratePages($data['pages'] ?? []);
            case 'scan_assets':
                return $this->stepScanAssets();
            case 'copy_assets':
                return $this->stepCopyAssets($data['offset'] ?? 0, $data['limit'] ?? 100);
            case 'generate_assets':
                return $this->stepGenerateAssets($data);
            case 'finalize':
                return $this->stepFinalize();
            default:
                throw new Exception("Invalid step: $step");
        }
    }

    private function stepInit()
    {
        // Increase resources for web environment
        if (function_exists('grinds_set_high_load_mode')) {
            grinds_set_high_load_mode();
        } else {
            @ini_set('memory_limit', '512M');
            if (function_exists('set_time_limit')) @set_time_limit(0);
        }

        // Remove old exports manually for more aggressive cleanup than the general system GC
        $tmpDir = $this->rootDir . '/data/tmp';
        if (is_dir($tmpDir) && function_exists('grinds_delete_tree')) {
            $expireTime = time() - 1200; // 20 minutes
            try {
                foreach (new DirectoryIterator($tmpDir) as $fileInfo) {
                    if ($fileInfo->isDot()) continue;
                    $filename = $fileInfo->getFilename();

                    // Clean up old export directories
                    if (str_starts_with($filename, 'static_export_build_') && $fileInfo->isDir()) {
                        if ($fileInfo->getMTime() < $expireTime) {
                            @grinds_delete_tree($fileInfo->getPathname());
                        }
                    }

                    // Clean up abandoned zip files and other temporary files to prevent disk space exhaustion
                    if ($fileInfo->isFile() && $fileInfo->getMTime() < $expireTime) {
                        if (
                            str_starts_with($filename, 'static_site_') ||
                            str_starts_with($filename, 'ssg_assets_') ||
                            str_starts_with($filename, 'ssg_detected_uploads_')
                        ) {
                            @unlink($fileInfo->getPathname());
                        }
                    }
                }
            } catch (Throwable $e) {
            }
        }

        if (function_exists('grinds_run_garbage_collection')) {
            grinds_run_garbage_collection();
        }

        if (is_dir($this->exportDir)) {
            if (function_exists('grinds_delete_tree')) {
                grinds_delete_tree($this->exportDir);
            }
        }
        @mkdir($this->exportDir, 0775, true);

        $mode = $this->config['mode'] ?? 'full';
        $updatedAfter = ($mode === 'diff') ? ($this->config['last_export'] ?? null) : null;

        $limit = function_exists('get_option') ? (int)get_option('posts_per_page', 10) : 10;
        if ($limit < 1) $limit = 10;
        $pages = [];

        // Add home pages
        $totalPosts = (int)$this->pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'published' AND type = 'post'")->fetchColumn();
        $numPages = ceil($totalPosts / $limit);
        if ($numPages < 1) $numPages = 1;
        for ($i = 1; $i <= $numPages; $i++) {
            $pages[] = ['url' => '', 'slug' => 'index', 'depth' => 0, 'page' => $i];
        }

        // Add single posts
        $sqlSlugs = "SELECT slug FROM posts WHERE status = 'published' AND type IN ('post', 'page')";
        if ($updatedAfter) {
            $sqlSlugs .= " AND updated_at >= " . $this->pdo->quote($updatedAfter);
        }
        $slugsStmt = $this->pdo->query($sqlSlugs);
        if ($slugsStmt) {
            foreach ($slugsStmt as $row) {
                $slug = $row['slug'];
                $depth = substr_count($slug, '/');
                $pages[] = ['url' => $slug, 'slug' => $slug, 'depth' => $depth, 'page' => 1];
            }
        }

        // Add category pages
        $categories = $this->pdo->query("SELECT id, slug FROM categories")->fetchAll(PDO::FETCH_ASSOC);
        $catCounts = [];
        $stmtCatCount = $this->pdo->query("SELECT category_id, COUNT(*) as c FROM posts WHERE status='published' GROUP BY category_id");
        if ($stmtCatCount) {
            while ($row = $stmtCatCount->fetch(PDO::FETCH_ASSOC)) {
                $catCounts[$row['category_id']] = (int)$row['c'];
            }
        }

        foreach ($categories as $row) {
            $catCount = $catCounts[$row['id']] ?? 0;
            $catPages = ceil($catCount / $limit);
            if ($catPages < 1) $catPages = 1;

            $slug = 'category/' . $row['slug'];
            $depth = substr_count($slug, '/');

            for ($i = 1; $i <= $catPages; $i++) {
                $pages[] = ['url' => $slug, 'slug' => $slug, 'depth' => $depth, 'page' => $i];
            }
        }

        // Add tag pages
        $tags = $this->pdo->query("SELECT id, slug FROM tags")->fetchAll(PDO::FETCH_ASSOC);
        $tagCounts = [];
        try {
            $stmtTagCount = $this->pdo->query("SELECT pt.tag_id, COUNT(*) as c FROM post_tags pt JOIN posts p ON pt.post_id = p.id WHERE p.status='published' GROUP BY pt.tag_id");
            if ($stmtTagCount) {
                while ($row = $stmtTagCount->fetch(PDO::FETCH_ASSOC)) {
                    $tagCounts[$row['tag_id']] = (int)$row['c'];
                }
            }
        } catch (Exception $e) {
        }

        foreach ($tags as $row) {
            $tagCount = $tagCounts[$row['id']] ?? 0;
            $tagPages = ceil($tagCount / $limit);
            if ($tagPages < 1) $tagPages = 1;

            $slug = 'tag/' . $row['slug'];
            $depth = substr_count($slug, '/');

            for ($i = 1; $i <= $tagPages; $i++) {
                $pages[] = ['url' => $slug, 'slug' => $slug, 'depth' => $depth, 'page' => $i];
            }
        }

        // Add 404 page
        if ($mode === 'full') {
            $pages[] = ['url' => '404', 'slug' => '404', 'depth' => 0, 'page' => 1];
        }

        // Add standalone physical pages (like contact.php) from theme dynamically
        $activeTheme = function_exists('get_option') ? get_option('site_theme', 'default') : 'default';
        $themePath = $this->rootDir . '/theme/' . $activeTheme;

        // Scan for standalone PHP pages in the theme root, excluding reserved template files.
        $reservedFiles = [
            'functions.php',
            'layout.php',
            '404.php',
            'home.php',
            'single.php',
            'page.php',
            'archive.php',
            'category.php',
            'tag.php'
        ];

        if (is_dir($themePath)) {
            foreach (glob($themePath . '/*.php') as $phpFile) {
                $basename = basename($phpFile);
                if (!in_array($basename, $reservedFiles, true)) {
                    $pSlug = basename($basename, '.php');

                    $alreadyAdded = false;
                    foreach ($pages as $p) {
                        if ($p['slug'] === $pSlug) {
                            $alreadyAdded = true;
                            break;
                        }
                    }
                    if (!$alreadyAdded) {
                        $pages[] = ['url' => $pSlug, 'slug' => $pSlug, 'depth' => 0, 'page' => 1];
                    }
                }
            }
        }

        // Apply filters
        if (function_exists('apply_filters')) {
            $pages = apply_filters('grinds_ssg_pages', $pages);
        }

        // Check disk space before proceeding
        if (function_exists('disk_free_space') && class_exists('FileManager')) {
            $tmpDir = $this->rootDir . '/data/tmp';
            $freeSpace = @disk_free_space($tmpDir);

            if ($freeSpace !== false) {
                $assetsSize = 0;
                try {
                    $assetsFiles = FileManager::scanDirectory($this->rootDir . '/assets');
                    foreach ($assetsFiles as $f) {
                        $assetsSize += (int)@filesize($f);
                    }
                } catch (\Throwable $e) {
                }

                // Estimate: Copied assets + ZIP file (approx 2x assets size) + buffer (50MB for HTML/JSON)
                $requiredSpace = ($assetsSize * 2) + (50 * 1024 * 1024);
                if ($freeSpace < $requiredSpace) {
                    $reqMB = round($requiredSpace / 1024 / 1024, 2);
                    $freeMB = round($freeSpace / 1024 / 1024, 2);
                    throw new Exception("Insufficient disk space for SSG. Required approx: {$reqMB}MB, Free: {$freeMB}MB.");
                }
            }
        }

        return ['success' => true, 'pages' => $pages, 'buildId' => $this->buildId];
    }

    private function stepGeneratePages($pages)
    {
        $config = $this->config;
        $jsToolPath = function_exists('resolve_url') ? resolve_url('assets/js/static_tools.js') : '/assets/js/static_tools.js';

        // Save extracted upload file paths to a temporary file on disk to prevent memory exhaustion
        $detectedUploadsFile = $this->rootDir . '/data/tmp/ssg_detected_uploads_' . $this->buildId . '.txt';
        $assetsFp = fopen($detectedUploadsFile, 'a');
        // Fast, ReDoS-safe regex pattern
        $uploadPattern = '/(assets\/uploads\/[a-zA-Z0-9_\-\.\/]+)/i';

        foreach ($pages as $page) {
            // Prevent path traversal
            if (strpos($page['slug'], '..') !== false || strpos($page['slug'], "\0") !== false) {
                continue;
            }

            $html = $this->renderPage($page, $config, $jsToolPath);

            // Optimize: Extract assets during HTML generation and append to temp file
            if ($assetsFp && strpos($html, 'assets/uploads/') !== false) {
                if (@preg_match_all($uploadPattern, $html, $matches)) {
                    $foundPaths = array_unique($matches[1]);
                    if (!empty($foundPaths)) {
                        fwrite($assetsFp, implode("\n", $foundPaths) . "\n");
                    }
                }
            }

            // Determine filename
            $slug = function_exists('grinds_ssg_normalize_slug') ? grinds_ssg_normalize_slug(ltrim($page['slug'], '/\\')) : strtolower(ltrim($page['slug'], '/\\'));
            $fileName = $slug . ($page['page'] > 1 ? '_' . $page['page'] : '') . '.html';

            $savePath = $this->exportDir . '/' . $fileName;
            $saveDir = dirname($savePath);
            if (!is_dir($saveDir))
                @mkdir($saveDir, 0775, true);

            $writeResult = file_put_contents($savePath, $html);

            // Free memory immediately
            unset($html);

            if ($writeResult === false) {
                if ($assetsFp) fclose($assetsFp);
                throw new Exception("Failed to write HTML file: " . $fileName);
            }
        }

        if ($assetsFp) {
            fclose($assetsFp);
        }

        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }

        return ['success' => true];
    }

    private function stepScanAssets()
    {
        $config = $this->config;
        $since = ($config['mode'] === 'diff' && !empty($config['last_export'])) ? strtotime($config['last_export']) : null;

        // Define scan options
        $scanOptions = [
            'exclude_exts' => $this->excludeExts,
            'since' => $since,
            'exclude_dirs' => ['.git', 'node_modules', '.idea', '.vscode', 'vendor', '__MACOSX', '_trash']
        ];

        if (!is_dir($this->rootDir . '/data/tmp')) {
            @mkdir($this->rootDir . '/data/tmp', 0775, true);
        }

        $assetsListFile = $this->rootDir . '/data/tmp/ssg_assets_' . $this->buildId . '.json';
        $fp = fopen($assetsListFile, 'w');
        if (!$fp) {
            throw new Exception("Failed to create assets list file. Check permissions for data/tmp.");
        }
        $totalFiles = 0;

        // Scan assets (excluding uploads directory)
        $assetsScanOptions = $scanOptions;
        $assetsScanOptions['exclude_dirs'][] = 'uploads';

        if (!class_exists('FileManager')) {
            $mediaFile = $this->rootDir . '/lib/functions/media.php';
            if (file_exists($mediaFile)) require_once $mediaFile;
        }

        if (class_exists('FileManager')) {
            try {
                foreach (FileManager::scanDirectory($this->rootDir . '/assets', $assetsScanOptions) as $file) {
                    fwrite($fp, json_encode($file, JSON_INVALID_UTF8_IGNORE | JSON_THROW_ON_ERROR) . "\n");
                    $totalFiles++;
                }
            } catch (\Throwable $e) {
            }
        }

        // Extract explicitly used uploads
        $usedUploads = [];
        try {
            $detectedUploadsFile = $this->rootDir . '/data/tmp/ssg_detected_uploads_' . $this->buildId . '.txt';
            if (file_exists($detectedUploadsFile)) {
                $handle = fopen($detectedUploadsFile, 'r');
                if ($handle) {
                    while (($line = fgets($handle)) !== false) {
                        $cleanPath = trim($line, "\n\r\t'\"\\ ");
                        if (strpos($cleanPath, '..') !== false || empty($cleanPath)) {
                            continue;
                        }
                        $fullPath = $this->normalizePath($this->rootDir . '/' . $cleanPath);
                        if (file_exists($fullPath)) {
                            $usedUploads[$fullPath] = true;
                            if (class_exists('FileManager')) {
                                foreach (FileManager::getDerivativePaths($fullPath) as $deriv) {
                                    if (file_exists($deriv)) $usedUploads[$this->normalizePath($deriv)] = true;
                                }
                            }
                        }
                    }
                    fclose($handle);
                }
                // Delete the used temporary file
                @unlink($detectedUploadsFile);
            }

            // Include global options (logo, favicon, etc.)
            if (function_exists('get_option')) {
                $globalOptions = ['admin_logo', 'site_favicon', 'site_ogp_image'];
                foreach ($globalOptions as $opt) {
                    $val = get_option($opt);
                    if ($val && strpos($val, 'assets/uploads/') !== false) {
                        $parsedPath = parse_url($val, PHP_URL_PATH);
                        if ($parsedPath) {
                            $startPos = strpos($parsedPath, 'assets/uploads/');
                            if ($startPos !== false) {
                                $relPath = substr($parsedPath, $startPos);
                                $fullPath = $this->normalizePath($this->rootDir . '/' . $relPath);
                                if (file_exists($fullPath)) {
                                    $usedUploads[$fullPath] = true;
                                    if (class_exists('FileManager')) {
                                        foreach (FileManager::getDerivativePaths($fullPath) as $deriv) {
                                            if (file_exists($deriv)) $usedUploads[$this->normalizePath($deriv)] = true;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // Collect all database image candidates to ensure block content is included
            $dbCandidates = [];

            // 1. Published Posts
            $stmt = $this->pdo->query("SELECT thumbnail, hero_image, hero_settings, content, meta_data FROM posts WHERE status = 'published' AND deleted_at IS NULL");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if (!empty($row['thumbnail'])) $dbCandidates[] = $row['thumbnail'];
                if (!empty($row['hero_image'])) $dbCandidates[] = $row['hero_image'];
                $hs = json_decode($row['hero_settings'] ?? '{}', true);
                if (is_array($hs) && !empty($hs['mobile_image'])) {
                    $dbCandidates[] = $hs['mobile_image'];
                }
                if (!empty($row['content']) && class_exists('FileManager')) {
                    FileManager::extractPathsFromContent($row['content'], $dbCandidates);
                }
                if (!empty($row['meta_data']) && class_exists('FileManager')) {
                    FileManager::extractPathsFromContent($row['meta_data'], $dbCandidates);
                }
            }

            // 2. Active Banners
            $stmtBanner = $this->pdo->query("SELECT image_url, html_code FROM banners WHERE is_active = 1");
            while ($row = $stmtBanner->fetch(PDO::FETCH_ASSOC)) {
                if (!empty($row['image_url'])) $dbCandidates[] = $row['image_url'];
                if (!empty($row['html_code']) && class_exists('FileManager')) {
                    FileManager::extractPathsFromContent($row['html_code'], $dbCandidates);
                }
            }

            // 3. Active Widgets
            $stmtWidget = $this->pdo->query("SELECT content, settings FROM widgets WHERE is_active = 1");
            while ($row = $stmtWidget->fetch(PDO::FETCH_ASSOC)) {
                $settings = json_decode($row['settings'] ?? '{}', true);
                if (is_array($settings) && !empty($settings['image'])) {
                    $dbCandidates[] = $settings['image'];
                }
                if (!empty($row['content']) && class_exists('FileManager')) {
                    FileManager::extractPathsFromContent($row['content'], $dbCandidates);
                }
            }

            // 4. Categories
            $stmtCat = $this->pdo->query("SELECT meta_data FROM categories");
            while ($row = $stmtCat->fetch(PDO::FETCH_ASSOC)) {
                if (!empty($row['meta_data']) && class_exists('FileManager')) {
                    FileManager::extractPathsFromContent($row['meta_data'], $dbCandidates);
                }
            }

            // Process all DB candidates
            foreach ($dbCandidates as $img) {
                if (strpos($img, 'assets/uploads/') !== false) {
                    $parsedPath = parse_url($img, PHP_URL_PATH);
                    if ($parsedPath) {
                        $startPos = strpos($parsedPath, 'assets/uploads/');
                        if ($startPos !== false) {
                            $relPath = substr($parsedPath, $startPos);
                            $fullPath = $this->normalizePath($this->rootDir . '/' . $relPath);
                            if (file_exists($fullPath)) {
                                $usedUploads[$fullPath] = true;
                                if (class_exists('FileManager')) {
                                    foreach (FileManager::getDerivativePaths($fullPath) as $deriv) {
                                        if (file_exists($deriv)) $usedUploads[$this->normalizePath($deriv)] = true;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // Write used uploads to the export list
            foreach (array_keys($usedUploads) as $uploadFile) {
                fwrite($fp, json_encode($uploadFile, JSON_INVALID_UTF8_IGNORE | JSON_THROW_ON_ERROR) . "\n");
                $totalFiles++;
            }
        } catch (Exception $e) {
            // Ignore and proceed
        }

        // Scan plugins
        if (class_exists('FileManager')) {
            try {
                foreach (FileManager::scanDirectory($this->rootDir . '/plugins', $scanOptions) as $file) {
                    fwrite($fp, json_encode($file, JSON_INVALID_UTF8_IGNORE | JSON_THROW_ON_ERROR) . "\n");
                    $totalFiles++;
                }
            } catch (\Throwable $e) {
            }
        }

        // Identify active themes
        $activeThemes = [function_exists('get_option') ? get_option('site_theme', 'default') : 'default', 'default'];
        $stmtThemes = $this->pdo->query("SELECT DISTINCT page_theme FROM posts WHERE page_theme IS NOT NULL AND page_theme != '' AND status='published' AND deleted_at IS NULL");
        while ($row = $stmtThemes->fetch()) $activeThemes[] = $row['page_theme'];
        $stmtCatThemes = $this->pdo->query("SELECT DISTINCT category_theme FROM categories WHERE category_theme IS NOT NULL AND category_theme != ''");
        while ($row = $stmtCatThemes->fetch()) $activeThemes[] = $row['category_theme'];
        $stmtWidgetThemes = $this->pdo->query("SELECT DISTINCT target_theme FROM widgets WHERE target_theme IS NOT NULL AND target_theme != 'all'");
        while ($row = $stmtWidgetThemes->fetch()) $activeThemes[] = $row['target_theme'];
        $stmtMenuThemes = $this->pdo->query("SELECT DISTINCT target_theme FROM nav_menus WHERE target_theme IS NOT NULL AND target_theme != 'all'");
        while ($row = $stmtMenuThemes->fetch()) $activeThemes[] = $row['target_theme'];
        $stmtBannerThemes = $this->pdo->query("SELECT DISTINCT target_theme FROM banners WHERE target_theme IS NOT NULL AND target_theme != 'all'");
        while ($row = $stmtBannerThemes->fetch()) $activeThemes[] = $row['target_theme'];

        $activeThemes = array_unique($activeThemes);

        // Scan themes
        if (class_exists('FileManager')) {
            foreach ($activeThemes as $theme) {
                $safeTheme = basename($theme);
                if ($safeTheme === '.' || $safeTheme === '..') continue;
                $themePath = $this->rootDir . '/theme/' . $safeTheme;
                if (is_dir($themePath)) {
                    try {
                        foreach (FileManager::scanDirectory($themePath, $scanOptions) as $file) {
                            fwrite($fp, json_encode($file, JSON_INVALID_UTF8_IGNORE | JSON_THROW_ON_ERROR) . "\n");
                            $totalFiles++;
                        }
                    } catch (\Throwable $e) {
                    }
                }
            }
        }
        fclose($fp);

        return ['success' => true, 'total' => $totalFiles];
    }

    private function stepCopyAssets($offset, $limit)
    {
        $startTime = microtime(true);
        $timeLimit = 20;

        $assetsListFile = $this->rootDir . '/data/tmp/ssg_assets_' . $this->buildId . '.json';
        if (!file_exists($assetsListFile)) {
            throw new Exception('Assets list not found');
        }

        $fp = fopen($assetsListFile, 'r');
        if ($offset > 0) fseek($fp, $offset);

        $count = 0;
        while (($line = fgets($fp)) !== false) {
            $line = trim($line);
            if (empty($line)) continue;

            $src = json_decode($line);
            if (!$src) continue;

            $srcPath = $this->normalizePath($src);
            $baseFileName = strtolower(basename($srcPath));
            if (in_array($baseFileName, ['.ds_store', 'thumbs.db', 'desktop.ini'])) continue;

            if (strpos($src, '..') !== false || strpos($srcPath, '..') !== false) {
                continue;
            }

            $realSrc = realpath($src);
            $realRoot = realpath($this->rootDir);

            if ($realSrc && $realRoot) {
                $realRootWithSlash = $realRoot . DIRECTORY_SEPARATOR;
                $isSafe = false;
                foreach (['assets', 'plugins', 'theme'] as $safeDir) {
                    $safePath = $realRootWithSlash . $safeDir . DIRECTORY_SEPARATOR;
                    if (stripos($realSrc, $safePath) === 0) {
                        $isSafe = true;
                        break;
                    }
                }
                if (!$isSafe) continue;
            } else {
                $rootWithSlash = $this->rootDir . '/';
                $isSafe = false;
                foreach (['assets', 'plugins', 'theme'] as $safeDir) {
                    $safePath = $rootWithSlash . $safeDir . '/';
                    if (stripos($srcPath, $safePath) === 0) {
                        $isSafe = true;
                        break;
                    }
                }
                if (!$isSafe) continue;
            }

            $realSrcNorm = str_replace('\\', '/', $realSrc ?: $srcPath);
            $rootDirNorm = str_replace('\\', '/', $realRoot ?: $this->rootDir);
            $rel = ltrim(substr($realSrcNorm, strlen($rootDirNorm)), '/');

            $dst = $this->exportDir . '/' . $rel;
            $dstDir = dirname($dst);
            if (!is_dir($dstDir)) @mkdir($dstDir, 0775, true);
            if (file_exists($src)) @copy($src, $dst);
            $count++;

            if ($count % 10 === 0 && (microtime(true) - $startTime >= $timeLimit)) {
                break;
            }
        }

        $nextOffset = ftell($fp);
        $isEof = feof($fp);
        fclose($fp);

        return [
            'success' => true,
            'processed' => $count,
            'next_offset' => $nextOffset,
            'done' => $isEof
        ];
    }

    private function stepGenerateAssets($data = [])
    {
        // Relax limits for intensive feed generation to prevent 500 timeout errors
        if (function_exists('grinds_set_high_load_mode')) {
            grinds_set_high_load_mode();
        } else {
            @ini_set('memory_limit', '512M');
            if (function_exists('set_time_limit')) @set_time_limit(0);
        }

        $config = $this->config;
        $searchScope = $config['search_scope'] ?? 'title_body';
        $offset = isset($data['search_offset']) ? (int)$data['search_offset'] : 0;
        $chunkIndex = isset($data['chunk_index']) ? (int)$data['chunk_index'] : 0;
        $manifest = isset($data['manifest']) ? $data['manifest'] : ['files' => []];

        $dataDir = $this->exportDir . '/assets/data';
        if (!is_dir($dataDir)) @mkdir($dataDir, 0775, true);

        if (!class_exists('PostRepository')) {
            $postsFile = $this->rootDir . '/lib/functions/posts.php';
            if (file_exists($postsFile)) require_once $postsFile;
        }

        $repo = new PostRepository($this->pdo);

        $tempChunkFile = $this->exportDir . '/_temp_chunk.json';
        $currentChunkData = [];
        if ($offset > 0 && file_exists($tempChunkFile)) {
            $currentChunkData = json_decode(file_get_contents($tempChunkFile), true) ?: [];
        }

        $batchLimit = 20;
        $chunkSize = function_exists('get_option') ? (int)get_option('ssg_search_chunk_size', 500) : 500;
        if ($chunkSize <= 0) $chunkSize = 500;

        $startTime = microtime(true);
        $timeLimit = 15;
        $isFinished = false;

        $defaultLimit = defined('SEARCH_INDEX_LIMIT') ? (int)constant('SEARCH_INDEX_LIMIT') : 300;
        $searchLimit = defined('GRINDS_SSG_SEARCH_LIMIT') ? (int)constant('GRINDS_SSG_SEARCH_LIMIT') : $defaultLimit;

        while (true) {
            $rows = $repo->fetch([
                'status' => 'published',
                'is_noindex' => 0
            ], $batchLimit, $offset, 'p.published_at DESC, p.id DESC');

            if (empty($rows)) {
                $isFinished = true;
                break;
            }

            if (function_exists('grinds_attach_tags')) {
                grinds_attach_tags($rows);
            }

            foreach ($rows as $row) {
                $tags = isset($row['tags']) ? array_column($row['tags'], 'name') : [];

                $bodyText = '';
                $fullBodyText = function_exists('grinds_extract_text_from_content') ? grinds_extract_text_from_content($row['content']) : strip_tags($row['content']);

                if ($searchScope === 'title_body') {
                    $bodyText = ($searchLimit > 0) ? mb_substr($fullBodyText, 0, $searchLimit, 'UTF-8') : $fullBodyText;
                }

                $searchParts = [
                    $row['title'],
                    $row['category_name'] ?? '',
                    !empty($tags) ? implode(' ', $tags) : '',
                    $row['description'] ?? '',
                    $bodyText
                ];

                $searchText = implode(' ', array_filter($searchParts, function ($v) {
                    return $v !== null && $v !== '';
                }));

                $searchText = str_replace(["\r", "\n", "\t"], ' ', $searchText);
                $searchText = preg_replace('/\s+/', ' ', $searchText);
                $searchText = trim($searchText);

                if (!empty($row['description'])) {
                    $cleanDesc = function_exists('grinds_extract_text_from_content') ? grinds_extract_text_from_content($row['description']) : html_entity_decode(strip_tags($row['description']), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $desc = mb_strimwidth($cleanDesc, 0, 120, '...', 'UTF-8');
                } else {
                    $desc = mb_strimwidth($fullBodyText, 0, 120, '...', 'UTF-8');
                }

                $cleanTitle = html_entity_decode(strip_tags((string)$row['title']), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $normSlug = function_exists('grinds_ssg_normalize_slug') ? grinds_ssg_normalize_slug($row['slug']) : mb_strtolower($row['slug'], 'UTF-8');

                $currentChunkData[] = [
                    't' => $cleanTitle,
                    'u' => $normSlug . '.html',
                    'd' => $desc,
                    'k' => $searchText
                ];

                if (count($currentChunkData) >= $chunkSize) {
                    $chunkFileName = "search_data_{$chunkIndex}.json";
                    file_put_contents($dataDir . '/' . $chunkFileName, json_encode($currentChunkData, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE | JSON_THROW_ON_ERROR));
                    $manifest['files'][] = $chunkFileName;
                    $currentChunkData = [];
                    $chunkIndex++;
                }
            }
            $offset += $batchLimit;

            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }

            if (microtime(true) - $startTime >= $timeLimit) {
                break;
            }
        }

        if (!$isFinished) {
            file_put_contents($tempChunkFile, json_encode($currentChunkData, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE | JSON_THROW_ON_ERROR));
            return [
                'success' => true,
                'in_progress' => true,
                'search_offset' => $offset,
                'chunk_index' => $chunkIndex,
                'manifest' => $manifest
            ];
        }

        if (file_exists($tempChunkFile)) @unlink($tempChunkFile);

        if (!empty($currentChunkData)) {
            $chunkFileName = "search_data_{$chunkIndex}.json";
            file_put_contents($dataDir . '/' . $chunkFileName, json_encode($currentChunkData, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE | JSON_THROW_ON_ERROR));
            $manifest['files'][] = $chunkFileName;
        }

        $manifestContent = json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE | JSON_THROW_ON_ERROR);
        file_put_contents($dataDir . '/search_manifest.json', $manifestContent);

        $this->processCssFiles($this->exportDir);

        // Render search page
        $searchContent = '';
        $activeTheme = function_exists('get_option') ? get_option('site_theme', 'default') : 'default';
        $themePath = $this->rootDir . '/theme/' . $activeTheme . '/parts/static-search.php';
        $defaultThemePath = $this->rootDir . '/theme/default/parts/static-search.php';

        if (file_exists($themePath)) {
            ob_start();
            include $themePath;
            $searchContent = ob_get_clean();
        } elseif (file_exists($defaultThemePath)) {
            ob_start();
            include $defaultThemePath;
            $searchContent = ob_get_clean();
        } else {
            $searchTitle = function_exists('h') ? h(str_replace('...', '', function_exists('_t') ? _t('search') : 'Search')) : 'Search';
            $searchContent = '<div class="mb-10"><h2 class="mb-6 pl-4 border-grinds-red border-l-4 font-bold text-3xl">' . $searchTitle . '</h2><div id="static-search-results" class="min-h-[200px]"></div></div>';
        }

        $searchResultLimit = function_exists('get_option') ? (int)get_option('ssg_max_results', 1000) : 1000;
        $msgNoResult = function_exists('_t') ? _t('ssg_search_no_results') : 'No results found.';
        $msgReadMore = function_exists('_t') ? _t('ssg_search_read_more') : 'Read more';
        $msgLoadMore = function_exists('_t') ? _t('ssg_search_load_more') : 'Load More';

        $searchConfigScript = '<script>window.grindsSearchConfig = ' . json_encode([
            'noResults' => $msgNoResult,
            'readMore' => $msgReadMore,
            'loadMore' => $msgLoadMore,
            'limit' => $searchResultLimit,
            'cacheBust' => (string)time(),
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE | JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . '; window.grindsSearchLimit = ' . $searchResultLimit . ';</script>';

        $searchContent = $searchConfigScript . "\n" . $searchContent;

        $searchPage = ['url' => 'search', 'slug' => 'search', 'depth' => 0, 'page' => 1];
        $jsToolPath = function_exists('resolve_url') ? resolve_url('assets/js/static_tools.js') : '/assets/js/static_tools.js';
        $html = $this->renderPage($searchPage, $config, $jsToolPath, $searchContent);
        file_put_contents($this->exportDir . '/search.html', $html);

        // Generate static feeds (sitemap, rss, robots, llms) using direct class invocation safely
        if (!empty($this->config['base_url'])) {
            $ssgBaseUrl = rtrim($this->config['base_url'], '/');

            require_once $this->rootDir . '/sitemap.php';
            $sitemapGen = new SitemapGenerator($this->pdo, $ssgBaseUrl, true);
            $sitemapGen->generateToFile($this->exportDir . '/sitemap.xml');

            require_once $this->rootDir . '/rss.php';
            $rssGen = new RssGenerator($this->pdo, $ssgBaseUrl, true);
            $rssGen->generateToFile($this->exportDir . '/rss.xml');
        }

        $ssgBaseUrl = $this->config['base_url'] ?? '';
        require_once $this->rootDir . '/robots.php';
        $robotsGen = new RobotsGenerator($ssgBaseUrl, true);
        $robotsGen->generateToFile($this->exportDir . '/robots.txt');

        $noIndex = function_exists('get_option') ? get_option('site_noindex') : false;
        $blockAi = function_exists('get_option') ? get_option('site_block_ai') : false;
        if (!$noIndex && !$blockAi) {
            require_once $this->rootDir . '/llms.php';
            $llmsGen = new LlmsTxtGenerator($this->pdo, $ssgBaseUrl, true);
            $llmsGen->generateToFile($this->exportDir . '/llms.txt');

            require_once $this->rootDir . '/llms-full.php';
            $llmsFullGen = new LlmsFullGenerator($this->pdo, $ssgBaseUrl, true);
            $llmsFullGen->generateToFile($this->exportDir . '/llms-full.txt');
        }

        return ['success' => true];
    }

    private function stepFinalize()
    {
        $zipPath = $this->rootDir . '/data/tmp/static_site.zip';
        if (!empty($this->buildId)) {
            $zipPath = $this->rootDir . '/data/tmp/static_site_' . $this->buildId . '.zip';
        }

        if (file_exists($zipPath)) {
            if (function_exists('grinds_force_unlink')) grinds_force_unlink($zipPath);
            else @unlink($zipPath);
        }

        $realExportDir = realpath($this->exportDir);
        if ($realExportDir === false) {
            throw new Exception("Failed to resolve export directory path.");
        }

        // Create README
        $_t = function_exists('_t') ? '_t' : fn($s) => $s;
        $readmeContent = $_t('ssg_readme_title') . "\n\n"
            . $_t('ssg_readme_thanks') . "\n\n"
            . "================================\n"
            . $_t('ssg_readme_usage_title') . "\n"
            . "================================\n"
            . $_t('ssg_readme_usage_desc') . "\n\n"
            . "================================\n"
            . $_t('ssg_readme_404_title') . "\n"
            . "================================\n"
            . $_t('ssg_readme_404_desc') . "\n\n"
            . $_t('ssg_readme_404_apache') . "\n\n"
            . $_t('ssg_readme_404_nginx') . "\n\n"
            . $_t('ssg_readme_404_static') . "\n\n"
            . "================================\n"
            . $_t('ssg_readme_search_title') . "\n"
            . "================================\n"
            . $_t('ssg_readme_search_desc') . "\n\n"
            . "================================\n"
            . $_t('ssg_readme_forms_title') . "\n"
            . "================================\n"
            . $_t('ssg_readme_forms_desc');
        file_put_contents($realExportDir . '/README.txt', $readmeContent);

        // Create .nojekyll
        file_put_contents($realExportDir . '/.nojekyll', '');

        if (!class_exists('ZipArchive')) {
            throw new Exception($_t('err_zip_extension_missing'));
        }
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            try {
                $files = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($realExportDir, FilesystemIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::LEAVES_ONLY
                );
                $fileCount = 0;
                $batchSize = 500;

                foreach ($files as $name => $file) {
                    if (!$file->isDir()) {
                        $filePath = $file->getRealPath();
                        $normFilePath = str_replace('\\', '/', $filePath);
                        $normExportDir = str_replace('\\', '/', $realExportDir);
                        $relativePath = ltrim(substr($normFilePath, strlen($normExportDir)), '/');
                        $zip->addFile($filePath, $relativePath);

                        $fileCount++;
                        if ($fileCount % $batchSize === 0) {
                            $zip->close();
                            if ($zip->open($zipPath) !== TRUE) {
                                throw new Exception("Failed to re-open ZIP archive during batch processing.");
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                $zip->close();
                throw $e;
            }
            $zip->close();
        } else {
            throw new Exception("Failed to create ZIP archive.");
        }

        if (function_exists('grinds_delete_tree')) {
            grinds_delete_tree($this->exportDir);
        }

        // Clean up temporary assets list
        $assetsListFile = $this->rootDir . '/data/tmp/ssg_assets_' . $this->buildId . '.json';
        if (file_exists($assetsListFile)) {
            if (function_exists('grinds_force_unlink')) grinds_force_unlink($assetsListFile);
            else @unlink($assetsListFile);
        }

        if (function_exists('update_option')) {
            update_option('last_ssg_export', date('Y-m-d H:i:s'));
        }

        $token = $_SESSION['csrf_token'] ?? '';
        $downloadUrl = 'api/ssg_process.php?action=download&csrf_token=' . $token;
        if (!empty($this->buildId)) {
            $downloadUrl .= '&build_id=' . $this->buildId;
        }

        return ['success' => true, 'url' => $downloadUrl];
    }

    private function processCssFiles($dir)
    {
        $baseUrl = $this->config['base_url'] ?? '';

        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && strtolower($file->getExtension()) === 'css') {
                    if ($file->getSize() > 500 * 1024) continue;

                    $content = file_get_contents($file->getRealPath());
                    $originalContent = $content;

                    if (stripos($content, 'url(') === false) continue;

                    $realPath = $this->normalizePath($file->getRealPath());
                    $exportRoot = $this->normalizePath($this->exportDir);

                    if (stripos($realPath, $exportRoot) !== 0) continue;

                    $relativePath = substr($realPath, strlen($exportRoot));
                    $relativePath = ltrim($relativePath, '/');

                    $depth = substr_count($relativePath, '/');

                    if (function_exists('grinds_replace_css_urls')) {
                        $content = grinds_replace_css_urls($content, function ($url) use ($depth, $baseUrl) {
                            if (strpos($url, '/') !== 0) return $url;
                            if (!empty($baseUrl)) return rtrim($baseUrl, '/') . '/' . ltrim($url, '/');
                            $prefix = ($depth > 0) ? str_repeat('../', $depth) : './';
                            return $prefix . ltrim($url, '/');
                        });
                    }

                    if ($content !== $originalContent) {
                        file_put_contents($file->getRealPath(), $content);
                    }
                }
            }
        } catch (Exception $e) {
        }
    }

    private function renderPage($page, $config, $jsToolPath, $customContent = null)
    {
        $GLOBALS['grinds_query'] = null;
        $GLOBALS['post'] = null;

        $requestUrl = $page['url'];
        $depth = $page['depth'];
        $pageNum = $page['page'] ?? 1;

        $mockUrl = ($page['slug'] === 'index') ? '/' : $requestUrl;
        if (strpos($mockUrl, '/') !== 0) $mockUrl = '/' . $mockUrl;

        $origReqUri = $_SERVER['REQUEST_URI'] ?? '';
        $origReqMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $origScriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $origPhpSelf = $_SERVER['PHP_SELF'] ?? '';
        $origGet = $_GET;
        $origPost = $_POST;
        $origCookie = $_COOKIE;
        $origFiles = $_FILES;
        $origRequest = $_REQUEST;
        $origSession = $_SESSION ?? [];
        $origTheme = $GLOBALS['activeTheme'] ?? null;

        $basePath = '';
        if (function_exists('resolve_url')) {
            $basePath = parse_url(resolve_url('/'), PHP_URL_PATH) ?? '/';
        }
        $basePath = rtrim($basePath, '/');

        if ($basePath !== '' && strpos($mockUrl, $basePath) !== 0) {
            $mockUrl = $basePath . ($mockUrl === '/' ? '' : $mockUrl);
        }

        $_SERVER['REQUEST_URI'] = $mockUrl;
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SCRIPT_NAME'] = $basePath . '/index.php';
        $_SERVER['PHP_SELF'] = $basePath . '/index.php';
        $_GET = [];
        $_POST = [];
        $_COOKIE = [];
        $_FILES = [];
        $_REQUEST = [];
        if ($pageNum > 1) $_GET['page'] = $pageNum;

        $_SESSION['admin_logged_in'] = false;
        unset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['user_role']);

        ob_start();
        try {
            if (function_exists('grinds_render_page')) {
                $html = grinds_render_page($page['url'], [
                    'custom_content' => $customContent,
                    'suppress_response_code' => true
                ]);
            } else {
                $html = "<!-- Error: render function not found -->";
            }
            ob_end_clean();
        } catch (Throwable $e) {
            ob_end_clean();
            $msg = str_replace(ROOT_PATH, '', $e->getMessage());
            $html = "<!-- SSG Render Error: " . htmlspecialchars($msg) . " -->";
            $html .= '<div style="text-align:center; padding:50px; color:#666;"><h1>Page Generation Failed</h1><p>An error occurred.</p></div>';
        } finally {
            $_SERVER['REQUEST_URI'] = $origReqUri;
            $_SERVER['REQUEST_METHOD'] = $origReqMethod;
            $_SERVER['SCRIPT_NAME'] = $origScriptName;
            $_SERVER['PHP_SELF'] = $origPhpSelf;
            $_GET = $origGet;
            $_POST = $origPost;
            $_COOKIE = $origCookie;
            $_FILES = $origFiles;
            $_REQUEST = $origRequest;
            $_SESSION = $origSession;
            if ($origTheme) $GLOBALS['activeTheme'] = $origTheme;
        }

        $liveBaseUrl = function_exists('resolve_url') ? rtrim(resolve_url('/'), '/') : '';

        // Safe ReDoS-free Replacement Helpers
        $safeReplaceCallback = function ($pattern, $callback, $subject) {
            if (!is_string($subject)) return '';
            $result = @preg_replace_callback($pattern, $callback, $subject);
            return ($result === null) ? $subject : $result;
        };

        $safeReplace = function ($pattern, $replacement, $subject) {
            if (!is_string($subject)) return '';
            $result = @preg_replace($pattern, $replacement, $subject);
            return ($result === null) ? $subject : $result;
        };

        $processUrl = function ($url) use ($liveBaseUrl, $depth, $config) {
            if (strpos($url, $liveBaseUrl) === 0 || (strpos($url, '/') === 0 && strpos($url, '//') !== 0) || strpos($url, 'page=') !== false) {
                return function_exists('Routing::toRelative') ? Routing::toRelative($url, $depth) : $url;
            }
            if (strpos($url, '//') === 0) return 'https:' . $url;
            return $url;
        };

        if (!empty($config['base_url'])) {
            $prodBaseUrl = rtrim($config['base_url'], '/');

            // Replace domains in link tags (canonical, alternate, etc.) - Fast & ReDoS safe
            $html = $safeReplaceCallback('/<link\b([^>]+)>/i', function ($matches) use ($liveBaseUrl, $prodBaseUrl) {
                $fullTag = $matches[0];
                if (stripos($fullTag, 'rel="canonical"') !== false || stripos($fullTag, 'rel="alternate"') !== false || stripos($fullTag, 'rel="llms-txt"') !== false) {
                    if (preg_match('/href=["\']([^"\']+)["\']/i', $fullTag, $hrefMatch)) {
                        $url = $hrefMatch[1];
                        if (strpos($url, $liveBaseUrl) === 0) {
                            $newUrl = str_replace($liveBaseUrl, $prodBaseUrl, $url);
                            return str_replace($url, $newUrl, $fullTag);
                        }
                    }
                }
                return $fullTag;
            }, $html);

            // Replace domains in meta tags (og:url, og:image, twitter:image) - Fast & ReDoS safe
            $html = $safeReplaceCallback('/<meta\b([^>]+)>/i', function ($matches) use ($liveBaseUrl, $prodBaseUrl) {
                $fullTag = $matches[0];
                if (stripos($fullTag, 'property="og:url"') !== false || stripos($fullTag, 'property="og:image"') !== false || stripos($fullTag, 'name="twitter:image"') !== false) {
                    if (preg_match('/content=["\']([^"\']+)["\']/i', $fullTag, $contentMatch)) {
                        $url = $contentMatch[1];
                        if (strpos($url, $liveBaseUrl) === 0) {
                            $newUrl = str_replace($liveBaseUrl, $prodBaseUrl, $url);
                            return str_replace($url, $newUrl, $fullTag);
                        }
                    }
                }
                return $fullTag;
            }, $html);

            // Replace domains in JSON-LD scripts - Fast & ReDoS safe
            $html = $safeReplaceCallback('/<script\b([^>]*+)>((?:[^<]++|<(?!\/script>))*+)<\/script>/is', function ($matches) use ($liveBaseUrl, $prodBaseUrl) {
                $attributes = $matches[1];
                $content = $matches[2];
                if (stripos($attributes, 'type="application/ld+json"') !== false || stripos($attributes, "type='application/ld+json'") !== false) {
                    $jsonContent = str_replace($liveBaseUrl, $prodBaseUrl, $content);
                    return '<script' . $attributes . '>' . $jsonContent . '</script>';
                }
                return $matches[0];
            }, $html);
        }

        // Remove base tags
        $html = $safeReplace('/<base\b[^>]*>/i', '', $html);

        // Replace URLs in inline styles
        $html = $safeReplaceCallback('/style=["\']([^"\']+)["\']/i', function ($matches) use ($processUrl) {
            $styleContent = $matches[1];
            if (stripos($styleContent, 'url(') !== false && function_exists('grinds_replace_css_urls')) {
                $newContent = grinds_replace_css_urls($styleContent, fn($url) => $processUrl($url));
                return str_replace($styleContent, $newContent, $matches[0]);
            }
            return $matches[0];
        }, $html);

        // Convert href, src, and poster attribute paths and hide admin links
        $html = $safeReplaceCallback('/<(a|img|script|link|video|audio|source|iframe)\b([^>]+)>/i', function ($matches) use ($processUrl, $safeReplaceCallback) {
            $tagName = strtolower($matches[1]);
            $attributes = $matches[2];

            $attributes = $safeReplaceCallback('/(href|src|poster)\s*=\s*(["\'])([^"\']*)\2/i', function ($attrMatches) use ($tagName, $processUrl) {
                $attrName = $attrMatches[1];
                $quote = $attrMatches[2];
                $url = $attrMatches[3];

                // Hide admin links to prevent broken layouts
                if ($tagName === 'a' && $attrName === 'href') {
                    if (preg_match('/(admin\/(index\.php|login\.php)?$|wp-admin|dashboard)/i', $url)) {
                        return 'href="javascript:void(0);" style="display:none;"';
                    }
                }

                $processedUrl = $processUrl($url);
                return $attrName . '=' . $quote . $processedUrl . $quote;
            }, $attributes);

            return '<' . $tagName . ' ' . $attributes . '>';
        }, $html);

        // Convert paths in srcset attributes
        $html = $safeReplaceCallback('/srcset\s*=\s*(["\'])([^"\']*)\1/i', function ($matches) use ($processUrl) {
            $quote = $matches[1];
            $srcset = $matches[2];

            $parts = explode(',', $srcset);
            $newParts = [];
            foreach ($parts as $part) {
                $part = trim($part);
                if (empty($part)) continue;
                $p = preg_split('/\s+/', $part, 2);
                $url = $p[0];
                $desc = $p[1] ?? '';
                $processedUrl = $processUrl($url);
                $newParts[] = $processedUrl . ($desc ? ' ' . $desc : '');
            }
            return 'srcset=' . $quote . implode(', ', $newParts) . $quote;
        }, $html);

        // Convert form actions
        $html = $safeReplaceCallback('/<form\b([^>]+)>/i', function ($matches) use ($processUrl, $config, $depth, $safeReplace) {
            $attributes = $matches[1];
            $isSearch = false;

            if (stripos($attributes, 'grinds-search-form') !== false || stripos($attributes, 'role="search"') !== false || stripos($attributes, 'action="search"') !== false) {
                $isSearch = true;
            }

            if ($isSearch) {
                $searchPath = str_repeat('../', $depth) . 'search.html';
                if ($searchPath === 'search.html') $searchPath = './search.html';

                $attributes = $safeReplace('/action\s*=\s*["\'][^"\']*["\']/i', 'action="' . $searchPath . '"', $attributes);
                $attributes = $safeReplace('/method\s*=\s*["\'][^"\']*["\']/i', 'method="get"', $attributes);
            } elseif (!empty($config['form_endpoint'])) {
                $safeEndpoint = htmlspecialchars($config['form_endpoint'], ENT_QUOTES, 'UTF-8');
                $attributes = $safeReplace('/action\s*=\s*["\'][^"\']*["\']/i', 'action="' . $safeEndpoint . '"', $attributes);
                $attributes = $safeReplace('/method\s*=\s*["\'][^"\']*["\']/i', 'method="post"', $attributes);
            } else {
                $attributes = @preg_replace_callback('/action\s*=\s*(["\'])([^"\']*)\1/i', function ($actMatches) use ($processUrl) {
                    $url = $actMatches[2];
                    $processedUrl = $processUrl($url);
                    return 'action=' . $actMatches[1] . $processedUrl . $actMatches[1];
                }, $attributes) ?? $attributes;
            }

            return '<form ' . $attributes . '>';
        }, $html);

        // Inject SSG specific JavaScript before closing body tag
        $relJsPath = $processUrl($jsToolPath);
        $relativePrefix = str_repeat('../', $depth);
        if ($relativePrefix === '') $relativePrefix = './';

        $scriptsToInject = "<script>window.grindsBaseUrl = '" . $relativePrefix . "';</script>\n";
        $scriptsToInject .= "<script src=\"" . $relJsPath . "\"></script>\n</body>";

        $html = str_ireplace('</body>', $scriptsToInject, $html);

        return $html;
    }
}
