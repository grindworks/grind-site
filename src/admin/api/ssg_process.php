<?php

/**
 * ssg_process.php
 *
 * Handle Static Site Generation (SSG) tasks.
 * Refactored for better stability and Windows compatibility.
 */
// Define SSG flag
define('GRINDS_IS_SSG', true);

require_once __DIR__ . '/api_bootstrap.php';

require_once __DIR__ . '/../../lib/front.php';

// Ensure PDO available
/** @var PDO $pdo */
$pdo = App::db();
if (!$pdo) {
    json_response(['success' => false, 'error' => 'Database connection failed']);
}

// Check permissions
if (!function_exists('current_user_can') || !current_user_can('manage_tools')) {
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    if ($action === 'download') {
        http_response_code(403);
        exit('Permission denied');
    }
    json_response(['success' => false, 'error' => 'Permission denied'], 403);
}

if (function_exists('grinds_set_high_load_mode')) {
    grinds_set_high_load_mode();
}

// Handle download
$action = $_POST['action'] ?? $_GET['action'] ?? '';
if ($action === 'download') {
    $csrf_token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    if (!validate_csrf_token($csrf_token)) {
        ob_clean();
        http_response_code(403);
        exit('Invalid CSRF Token');
    }

    $buildId = $_POST['build_id'] ?? $_GET['build_id'] ?? '';
    $zipFile = ROOT_PATH . '/data/tmp/static_site.zip';

    if (!empty($buildId) && preg_match('/^[a-zA-Z0-9_]+$/', $buildId)) {
        $zipFile = ROOT_PATH . '/data/tmp/static_site_' . $buildId . '.zip';
    }

    if (file_exists($zipFile)) {

        while (ob_get_level())
            ob_end_clean();
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="static_site_' . date('Ymd_His') . '.zip"');
        header('Content-Length: ' . filesize($zipFile));
        header('Pragma: no-cache');
        header('Expires: 0');

        // Stream output using readfile() for better performance and cleaner code
        if (function_exists('set_time_limit')) @set_time_limit(0);
        @readfile($zipFile);
        grinds_force_unlink($zipFile);
        exit;
    } else {
        ob_clean();
        http_response_code(404);
        exit(_t('err_file_not_found'));
    }
}

// Handle API Actions
check_csrf_token();

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
            // Release session lock for heavy steps to prevent blocking other requests
            session_write_close();
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

        // Save persistent options
        if (isset($data['baseUrl']))
            update_option('ssg_base_url', $data['baseUrl']);
        if (isset($data['formEndpoint']))
            update_option('ssg_form_endpoint', $data['formEndpoint']);
        if (isset($data['maxResults']))
            update_option('ssg_max_results', (int)$data['maxResults']);
        if (isset($data['searchScope']))
            update_option('ssg_search_scope', $data['searchScope']);
        if (isset($data['searchChunkSize']))
            update_option('ssg_search_chunk_size', (int)$data['searchChunkSize']);

        session_write_close();
    }

    private function resolveBuildId($data)
    {
        $id = $data['buildId'] ?? $data['build_id'] ?? '';
        if (empty($id) || !preg_match('/^[a-zA-Z0-9_]+$/', $id)) {
            return 'build_' . bin2hex(random_bytes(8));
        }
        return $id;
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
        // Remove old exports
        if (function_exists('grinds_run_garbage_collection')) {
            grinds_run_garbage_collection();
        }

        if (is_dir($this->exportDir))
            grinds_delete_tree($this->exportDir);
        @mkdir($this->exportDir, 0775, true);

        $mode = $this->config['mode'];
        $updatedAfter = ($mode === 'diff') ? $this->config['last_export'] : null;
        $repo = new PostRepository($this->pdo);

        $limit = (int)get_option('posts_per_page', 10);
        if ($limit < 1)
            $limit = 10;
        $pages = [];

        // Add home pages
        $totalPosts = $repo->count(['status' => 'published', 'type' => 'post']);
        $numPages = ceil($totalPosts / $limit);
        if ($numPages < 1)
            $numPages = 1;
        for ($i = 1; $i <= $numPages; $i++) {
            $pages[] = ['url' => '', 'slug' => 'index', 'depth' => 0, 'page' => $i];
        }

        // Add single posts
        $slugsStmt = $repo->findSlugs([
            'status' => 'published',
            'updated_after' => $updatedAfter
        ]);
        foreach ($slugsStmt as $row) {
            $slug = $row['slug'];

            $depth = substr_count($slug, '/');
            $pages[] = ['url' => $slug, 'slug' => $slug, 'depth' => $depth, 'page' => 1];
        }

        // Add category pages
        $categories = $this->pdo->query("SELECT id, slug FROM categories")->fetchAll(PDO::FETCH_ASSOC);
        $catCounts = [];
        if (!empty($categories)) {
            $catIds = array_column($categories, 'id');
            foreach (array_chunk($catIds, 500) as $chunkIds) {
                $catCounts += $repo->getPostCountsForCategories($chunkIds, ['status' => 'published']);
            }
        }

        foreach ($categories as $row) {
            $catCount = $catCounts[$row['id']] ?? 0;
            $catPages = ceil($catCount / $limit);
            if ($catPages < 1)
                $catPages = 1;

            $slug = 'category/' . $row['slug'];
            $depth = substr_count($slug, '/');

            for ($i = 1; $i <= $catPages; $i++) {
                $pages[] = ['url' => $slug, 'slug' => $slug, 'depth' => $depth, 'page' => $i];
            }
        }

        // Add tag pages
        $tags = $this->pdo->query("SELECT id, slug FROM tags")->fetchAll(PDO::FETCH_ASSOC);
        $tagCounts = [];
        if (!empty($tags)) {
            $tagIds = array_column($tags, 'id');
            foreach (array_chunk($tagIds, 500) as $chunkIds) {
                $tagCounts += $repo->getPostCountsForTags($chunkIds, ['status' => 'published']);
            }
        }

        foreach ($tags as $row) {
            $tagCount = $tagCounts[$row['id']] ?? 0;
            $tagPages = ceil($tagCount / $limit);
            if ($tagPages < 1)
                $tagPages = 1;

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

        // Add standalone physical pages (like contact.php) from theme
        $activeTheme = function_exists('get_option') ? get_option('site_theme', 'default') : 'default';
        $themePath = $this->rootDir . '/theme/' . $activeTheme;

        $standalonePages = ['contact'];
        foreach ($standalonePages as $pSlug) {
            $alreadyAdded = false;
            foreach ($pages as $p) {
                if ($p['slug'] === $pSlug) {
                    $alreadyAdded = true;
                    break;
                }
            }
            if (!$alreadyAdded && file_exists($themePath . '/' . $pSlug . '.php')) {
                $pages[] = ['url' => $pSlug, 'slug' => $pSlug, 'depth' => 0, 'page' => 1];
            }
        }

        // Apply filters
        $pages = apply_filters('grinds_ssg_pages', $pages);

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
                } catch (Exception $e) {
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
        $jsToolPath = resolve_url('assets/js/static_tools.js');

        foreach ($pages as $page) {
            // Prevent path traversal
            if (strpos($page['slug'], '..') !== false || strpos($page['slug'], "\0") !== false) {
                continue;
            }

            $html = $this->renderPage($page, $config, $jsToolPath);

            // Determine filename
            $slug = grinds_ssg_normalize_slug(ltrim($page['slug'], '/\\'));
            $fileName = $slug . ($page['page'] > 1 ? '_' . $page['page'] : '') . '.html';

            $savePath = $this->exportDir . '/' . $fileName;
            $saveDir = dirname($savePath);
            if (!is_dir($saveDir))
                @mkdir($saveDir, 0775, true);

            file_put_contents($savePath, $html);
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

        // Scan assets (excluding uploads directory to prevent sensitive file leaks)
        $assetsScanOptions = $scanOptions;
        $assetsScanOptions['exclude_dirs'][] = 'uploads';
        foreach (FileManager::scanDirectory($this->rootDir . '/assets', $assetsScanOptions) as $file) {
            fwrite($fp, json_encode($file, JSON_INVALID_UTF8_IGNORE | JSON_THROW_ON_ERROR) . "\n");
            $totalFiles++;
        }

        // Extract explicitly used uploads from generated HTML to ensure only public files are included
        $usedUploads = [];
        try {
            $exportIterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($this->exportDir, RecursiveDirectoryIterator::SKIP_DOTS)
            );

            // Match href, src, srcset, url() containing assets/uploads/
            $uploadPattern = '/(?:href|src|srcset|url\s*\(\s*["\']?)[\s"\']*(?:https?:\/\/[^\/]+)?\/?(assets\/uploads\/[^\s"\'\)>,]+)/i';

            foreach ($exportIterator as $htmlFile) {
                if ($htmlFile->isFile() && in_array(strtolower($htmlFile->getExtension()), ['html', 'css', 'js'])) {
                    $content = file_get_contents($htmlFile->getRealPath());
                    if (preg_match_all($uploadPattern, $content, $matches)) {
                        foreach ($matches[1] as $match) {
                            $cleanPath = trim($match, '\'"\\ ');
                            $fullPath = $this->normalizePath($this->rootDir . '/' . $cleanPath);
                            if (file_exists($fullPath)) {
                                $usedUploads[$fullPath] = true;
                            }
                        }
                    }
                }
            }

            // Include global options (logo, favicon, etc.)
            $globalOptions = ['admin_logo', 'site_favicon', 'site_ogp_image'];
            foreach ($globalOptions as $opt) {
                $val = get_option($opt);
                if ($val && strpos($val, 'assets/uploads/') !== false) {
                    $parsedPath = parse_url($val, PHP_URL_PATH);
                    if ($parsedPath) {
                        $fullPath = $this->normalizePath($this->rootDir . '/' . ltrim($parsedPath, '/'));
                        if (file_exists($fullPath)) $usedUploads[$fullPath] = true;
                    }
                }
            }

            // Include thumbnails of published posts
            $stmt = $this->pdo->query("SELECT thumbnail, hero_image, hero_image_mobile FROM posts WHERE status = 'published'");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                foreach (['thumbnail', 'hero_image', 'hero_image_mobile'] as $key) {
                    if (!empty($row[$key]) && strpos($row[$key], 'assets/uploads/') !== false) {
                        $parsedPath = parse_url($row[$key], PHP_URL_PATH);
                        if ($parsedPath) {
                            $fullPath = $this->normalizePath($this->rootDir . '/' . ltrim($parsedPath, '/'));
                            if (file_exists($fullPath)) $usedUploads[$fullPath] = true;
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
        foreach (FileManager::scanDirectory($this->rootDir . '/plugins', $scanOptions) as $file) {
            fwrite($fp, json_encode($file, JSON_INVALID_UTF8_IGNORE | JSON_THROW_ON_ERROR) . "\n");
            $totalFiles++;
        }

        // Identify active themes
        $activeThemes = [get_option('site_theme', 'default'), 'default'];
        $stmtThemes = $this->pdo->query("SELECT DISTINCT page_theme FROM posts WHERE page_theme IS NOT NULL AND page_theme != '' AND status='published' AND deleted_at IS NULL");
        while ($row = $stmtThemes->fetch())
            $activeThemes[] = $row['page_theme'];
        $stmtCatThemes = $this->pdo->query("SELECT DISTINCT category_theme FROM categories WHERE category_theme IS NOT NULL AND category_theme != ''");
        while ($row = $stmtCatThemes->fetch())
            $activeThemes[] = $row['category_theme'];
        $activeThemes = array_unique($activeThemes);

        // Scan themes
        foreach ($activeThemes as $theme) {
            $safeTheme = basename($theme);
            if ($safeTheme === '.' || $safeTheme === '..')
                continue;
            $themePath = $this->rootDir . '/theme/' . $safeTheme;
            if (is_dir($themePath)) {
                foreach (FileManager::scanDirectory($themePath, $scanOptions) as $file) {
                    fwrite($fp, json_encode($file, JSON_INVALID_UTF8_IGNORE | JSON_THROW_ON_ERROR) . "\n");
                    $totalFiles++;
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
        if ($offset > 0)
            fseek($fp, $offset);

        $count = 0;
        while (($line = fgets($fp)) !== false) {
            $line = trim($line);
            if (empty($line))
                continue;

            $src = json_decode($line);
            if (!$src)
                continue;

            // Calculate relative path
            $srcPath = $this->normalizePath($src);

            // Filter system files
            $baseFileName = strtolower(basename($srcPath));
            if (in_array($baseFileName, ['.ds_store', 'thumbs.db', 'desktop.ini']))
                continue;

            // Prevent path traversal
            // Use case-insensitive check for Windows compatibility
            $realSrc = realpath($src);
            $realRoot = realpath($this->rootDir);

            if ($realSrc && $realRoot) {
                $realRootWithSlash = $realRoot . DIRECTORY_SEPARATOR;
                // Check if it is the root itself OR inside the root
                if ($realSrc !== $realRoot && stripos($realSrc, $realRootWithSlash) !== 0) {
                    continue;
                }
            } else {
                // Fallback if realpath fails (unlikely for existing files)
                $rootWithSlash = $this->rootDir . '/';
                if ($srcPath !== $this->rootDir && stripos($srcPath, $rootWithSlash) !== 0) {
                    continue;
                }
            }

            // Ensure paths are normalized for Windows compatibility before substring
            $srcPath = str_replace('\\', '/', $srcPath);
            $rootDirNorm = str_replace('\\', '/', $this->rootDir);
            $rel = ltrim(substr($srcPath, strlen($rootDirNorm)), '/');
            $dst = $this->exportDir . '/' . $rel;
            $dstDir = dirname($dst);
            if (!is_dir($dstDir))
                @mkdir($dstDir, 0775, true);
            if (file_exists($src))
                @copy($src, $dst);
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
        $config = $this->config;
        $searchScope = $config['search_scope'] ?? 'title_body';

        $offset = isset($data['search_offset']) ? (int)$data['search_offset'] : 0;
        $chunkIndex = isset($data['chunk_index']) ? (int)$data['chunk_index'] : 0;
        $manifest = isset($data['manifest']) ? $data['manifest'] : ['files' => []];

        // Create assets/data directory for search index
        $dataDir = $this->exportDir . '/assets/data';
        if (!is_dir($dataDir))
            @mkdir($dataDir, 0775, true);

        $repo = new PostRepository($this->pdo);

        $tempChunkFile = $this->exportDir . '/_temp_chunk.json';
        $currentChunkData = [];
        if ($offset > 0 && file_exists($tempChunkFile)) {
            $currentChunkData = json_decode(file_get_contents($tempChunkFile), true) ?: [];
        }

        $batchLimit = 20;

        $chunkSize = (int)get_option('ssg_search_chunk_size', 500);
        if ($chunkSize <= 0) $chunkSize = 500;

        $startTime = microtime(true);
        $timeLimit = 15; // 15秒で安全に中断
        $isFinished = false;

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

                // Construct search text
                $defaultLimit = defined('SEARCH_INDEX_LIMIT') ? (int)constant('SEARCH_INDEX_LIMIT') : 300;
                $limit = defined('GRINDS_SSG_SEARCH_LIMIT') ? (int)constant('GRINDS_SSG_SEARCH_LIMIT') : $defaultLimit;

                $bodyText = '';
                // Extract text once and reuse
                $fullBodyText = grinds_extract_text_from_content($row['content']);

                if ($searchScope === 'title_body') {
                    $bodyText = ($limit > 0) ? mb_substr($fullBodyText, 0, $limit, 'UTF-8') : $fullBodyText;
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

                $currentChunkData[] = [
                    't' => $cleanTitle,
                    'u' => grinds_ssg_normalize_slug($row['slug']) . '.html',
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

            // タイムリミットに達したら中断して状態を返す
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

        // Generate manifest file that lists all chunks
        $manifestContent = json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE | JSON_THROW_ON_ERROR);
        file_put_contents($dataDir . '/search_manifest.json', $manifestContent);

        // Process CSS files
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
            $searchContent = '<div class="mb-10"><h2 class="mb-6 pl-4 border-grinds-red border-l-4 font-bold text-3xl">Search</h2><div id="static-search-results" class="min-h-[200px]"></div></div>';
        }

        // Build search config script to inject into the search page
        // This provides localized labels and settings to static_tools.js
        $searchResultLimit = (int)get_option('ssg_max_results', 1000);
        $msgNoResult = _t('ssg_search_no_results');
        $msgReadMore = _t('ssg_search_read_more');
        $msgLoadMore = _t('ssg_search_load_more');

        $searchConfigScript = '<script>window.grindsSearchConfig = ' . json_encode([
            'noResults' => $msgNoResult,
            'readMore' => $msgReadMore,
            'loadMore' => $msgLoadMore,
            'limit' => $searchResultLimit,
            'cacheBust' => (string)time(),
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE | JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . '; window.grindsSearchLimit = ' . $searchResultLimit . ';</script>';

        // Prepend config script to search content so it is available before static_tools.js runs
        $searchContent = $searchConfigScript . "\n" . $searchContent;

        $searchPage = [
            'url' => 'search',
            'slug' => 'search',
            'depth' => 0,
            'page' => 1
        ];

        $jsToolPath = resolve_url('assets/js/static_tools.js');
        $html = $this->renderPage($searchPage, $config, $jsToolPath, $searchContent);
        file_put_contents($this->exportDir . '/search.html', $html);

        // Generate sitemap
        if (!empty($this->config['base_url'])) {
            $ssgBaseUrl = rtrim($this->config['base_url'], '/');
            ob_start();
            include $this->rootDir . '/sitemap.php';
            $sitemap = ob_get_clean();
            if (!empty($sitemap)) {
                file_put_contents($this->exportDir . '/sitemap.xml', $sitemap);
            }
        }

        if (!empty($this->config['base_url'])) {
            ob_start();
            $ssgBaseUrl = $this->config['base_url'] ?? '';
            include $this->rootDir . '/rss.php';
            $rssContent = ob_get_clean();
            if (!empty($rssContent)) {
                file_put_contents($this->exportDir . '/rss.xml', $rssContent);
            }
        }

        // Generate robots.txt
        ob_start();
        $ssgBaseUrl = $this->config['base_url'] ?? '';
        include $this->rootDir . '/robots.php';
        file_put_contents($this->exportDir . '/robots.txt', ob_get_clean());

        $noIndex = get_option('site_noindex');
        $blockAi = get_option('site_block_ai');
        if (!$noIndex && !$blockAi) {
            ob_start();
            include $this->rootDir . '/llms.php';
            file_put_contents($this->exportDir . '/llms.txt', ob_get_clean());

            ob_start();
            include $this->rootDir . '/llms-full.php';
            file_put_contents($this->exportDir . '/llms-full.txt', ob_get_clean());
        }

        return ['success' => true];
    }


    private function stepFinalize()
    {
        $zipPath = $this->rootDir . '/data/tmp/static_site.zip';
        if (!empty($this->buildId)) {
            $zipPath = $this->rootDir . '/data/tmp/static_site_' . $this->buildId . '.zip';
        }

        if (file_exists($zipPath))
            grinds_force_unlink($zipPath);

        $realExportDir = realpath($this->exportDir);
        if ($realExportDir === false) {
            throw new Exception("Failed to resolve export directory path.");
        }

        // Create README
        $readmeContent = _t('ssg_readme_title') . "\n\n"
            . _t('ssg_readme_thanks') . "\n\n"
            . "================================\n"
            . _t('ssg_readme_usage_title') . "\n"
            . "================================\n"
            . _t('ssg_readme_usage_desc') . "\n\n"
            . "================================\n"
            . _t('ssg_readme_404_title') . "\n"
            . "================================\n"
            . _t('ssg_readme_404_desc') . "\n\n"
            . _t('ssg_readme_404_apache') . "\n\n"
            . _t('ssg_readme_404_nginx') . "\n\n"
            . _t('ssg_readme_404_static') . "\n\n"
            . "================================\n"
            . _t('ssg_readme_search_title') . "\n"
            . "================================\n"
            . _t('ssg_readme_search_desc') . "\n\n"
            . "================================\n"
            . _t('ssg_readme_forms_title') . "\n"
            . "================================\n"
            . _t('ssg_readme_forms_desc');
        file_put_contents($realExportDir . '/README.txt', $readmeContent);

        // Create .nojekyll
        file_put_contents($realExportDir . '/.nojekyll', '');

        if (!class_exists('ZipArchive')) {
            throw new Exception(_t('err_zip_extension_missing'));
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
            // Finalize ZIP.
            $zip->close();
        } else {
            throw new Exception("Failed to create ZIP archive.");
        }
        grinds_delete_tree($this->exportDir);

        // Clean up temporary assets list
        $assetsListFile = $this->rootDir . '/data/tmp/ssg_assets_' . $this->buildId . '.json';
        if (file_exists($assetsListFile)) {
            grinds_force_unlink($assetsListFile);
        }

        update_option('last_ssg_export', date('Y-m-d H:i:s'));

        $downloadUrl = 'api/ssg_process.php?action=download&csrf_token=' . $_SESSION['csrf_token'];
        if (!empty($this->buildId)) {
            $downloadUrl .= '&build_id=' . $this->buildId;
        }

        return ['success' => true, 'url' => $downloadUrl];
    }

    /**
     * Process CSS files to fix paths.
     */
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
                    if ($file->getSize() > 500 * 1024) {
                        continue;
                    }

                    $content = file_get_contents($file->getRealPath());
                    $originalContent = $content;

                    if (stripos($content, 'url(') === false) {
                        continue;
                    }

                    $realPath = $this->normalizePath($file->getRealPath());
                    $exportRoot = $this->normalizePath($this->exportDir);

                    // Ensure file is within export directory
                    if (stripos($realPath, $exportRoot) !== 0)
                        continue;

                    $relativePath = substr($realPath, strlen($exportRoot));
                    $relativePath = ltrim($relativePath, '/');

                    $depth = substr_count($relativePath, '/');

                    $content = grinds_replace_css_urls($content, function ($url) use ($depth, $baseUrl) {
                        // Skip relative paths (not starting with /)
                        if (strpos($url, '/') !== 0) {
                            return $url;
                        }

                        if (!empty($baseUrl)) {
                            return rtrim($baseUrl, '/') . '/' . ltrim($url, '/');
                        }

                        // If base_url is not set (local viewing), convert to relative path based on CSS file depth
                        $prefix = ($depth > 0) ? str_repeat('../', $depth) : './';
                        return $prefix . ltrim($url, '/');
                    });

                    if ($content !== $originalContent) {
                        file_put_contents($file->getRealPath(), $content);
                    }
                }
            }
        } catch (Exception $e) {
            // Continue if CSS processing fails for some files
        }
    }

    /**
     * Mock environment and render page.
     */
    private function renderPage($page, $config, $jsToolPath, $customContent = null)
    {
        // Reset global loop state
        $GLOBALS['grinds_query'] = null;
        $GLOBALS['post'] = null;

        $requestUrl = $page['url'];
        $depth = $page['depth'];
        $pageNum = $page['page'] ?? 1;

        $mockUrl = ($page['slug'] === 'index') ? '/' : $requestUrl;
        if (strpos($mockUrl, '/') !== 0)
            $mockUrl = '/' . $mockUrl;

        // Backup global state
        $origReqUri = $_SERVER['REQUEST_URI'];
        $origReqMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $origScriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $origPhpSelf = $_SERVER['PHP_SELF'] ?? '';
        $origGet = $_GET;
        $origPost = $_POST;
        $origCookie = $_COOKIE;
        $origFiles = $_FILES;
        $origRequest = $_REQUEST;
        $origSession = $_SESSION;
        $origTheme = $GLOBALS['activeTheme'] ?? null;

        // Mock state
        $basePath = parse_url(resolve_url('/'), PHP_URL_PATH) ?? '/';
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
        if ($pageNum > 1)
            $_GET['page'] = $pageNum;

        // Disable admin login
        $_SESSION['admin_logged_in'] = false;
        unset($_SESSION['user_id']);
        unset($_SESSION['username']);
        unset($_SESSION['user_role']);

        ob_start();

        try {
            $html = grinds_render_page($page['url'], [
                'custom_content' => $customContent,
                'suppress_response_code' => true
            ]);
            ob_end_clean();
        } catch (Throwable $e) {
            ob_end_clean();
            $msg = str_replace(ROOT_PATH, '', $e->getMessage());
            $html = "<!-- SSG Render Error: " . htmlspecialchars($msg) . " -->";
            $html .= '<div style="text-align:center; padding:50px; color:#666;"><h1>Page Generation Failed</h1><p>An error occurred.</p></div>';
        } finally {
            // Restore global state
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
            if ($origTheme)
                $GLOBALS['activeTheme'] = $origTheme;
        }

        // Post-process HTML
        $liveBaseUrl = rtrim(resolve_url('/'), '/');

        if (!class_exists('DOMDocument')) {
            return $html;
        }

        // Protect Alpine.js attributes before loading into DOMDocument
        // DOMDocument fails on attributes with @ or : (unless namespaced)
        $protectedHtml = preg_replace_callback(
            '/(\s+)([a-zA-Z0-9\-_]*[:@][a-zA-Z0-9\.\-_:]*)=/',
            function ($matches) {
                $safeAttr = str_replace([':', '@'], ['--colon--', '--at--'], $matches[2]);
                return $matches[1] . 'data-grinds-safe-' . $safeAttr . '=';
            },
            $html
        );

        // This is reversed by mb_decode_numericentity at the end of this function.
        if (function_exists('mb_encode_numericentity')) {
            $protectedHtml = mb_encode_numericentity($protectedHtml, [0x80, 0x10FFFF, 0, 0x1FFFFF], 'UTF-8');
        }

        $dom = new DOMDocument();
        $internalErrors = libxml_use_internal_errors(true);
        if (!@$dom->loadHTML('<?xml encoding="UTF-8"?>' . $protectedHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD)) {
            libxml_clear_errors();
            libxml_use_internal_errors($internalErrors);
            return $html;
        }
        libxml_clear_errors();
        libxml_use_internal_errors($internalErrors);

        $processUrl = function ($url) use ($liveBaseUrl, $depth, $config) {
            if (strpos($url, $liveBaseUrl) === 0 || (strpos($url, '/') === 0 && strpos($url, '//') !== 0) || strpos($url, 'page=') !== false) {
                return Routing::toRelative($url, $depth);
            }

            if (strpos($url, '//') === 0) {
                return 'https:' . $url;
            }
            return $url;
        };

        $tagAttrs = [
            'a' => ['href'],
            'img' => ['src', 'data-src'],
            'script' => ['src'],
            'link' => ['href'],
            'video' => ['src', 'poster'],
            'audio' => ['src'],
            'source' => ['src'],
            'iframe' => ['src'],
        ];

        // Handle SEO tags
        if (!empty($config['base_url'])) {
            $prodBaseUrl = rtrim($config['base_url'], '/');
            $links = $dom->getElementsByTagName('link');
            $absoluteRels = ['canonical', 'alternate', 'llms-txt'];
            foreach ($links as $link) {
                $rel = strtolower($link->getAttribute('rel'));
                if (in_array($rel, $absoluteRels, true)) {
                    $url = $link->getAttribute('href');
                    if (strpos($url, $liveBaseUrl) === 0) {
                        $newUrl = str_replace($liveBaseUrl, $prodBaseUrl, $url);
                        $link->setAttribute('href', $newUrl);
                    }
                    $link->setAttribute('data-ssg-absolute', 'true');
                }
            }
            $metas = $dom->getElementsByTagName('meta');
            foreach ($metas as $meta) {
                $property = strtolower($meta->getAttribute('property'));
                $name = strtolower($meta->getAttribute('name'));
                if ($property === 'og:url' || $property === 'og:image' || $name === 'twitter:image') {
                    $url = $meta->getAttribute('content');
                    if (strpos($url, $liveBaseUrl) === 0) {
                        $newUrl = str_replace($liveBaseUrl, $prodBaseUrl, $url);
                        $meta->setAttribute('content', $newUrl);
                    }
                }
            }
        }

        $elementsToRemove = [];
        $elements = $dom->getElementsByTagName('*');

        foreach ($elements as $element) {
            $tagName = strtolower($element->tagName);

            if ($tagName === 'base') {
                $elementsToRemove[] = $element;
                continue;
            }

            if ($tagName === 'script' && strtolower($element->getAttribute('type')) === 'application/ld+json') {
                $json = $element->nodeValue;
                if (!empty($config['base_url'])) {
                    $prodBaseUrl = rtrim($config['base_url'], '/');
                    $json = str_replace($liveBaseUrl, $prodBaseUrl, $json);
                    $element->nodeValue = '';
                    $element->appendChild($dom->createTextNode($json));
                }
            }

            if ($tagName === 'style') {
                $originalContent = $element->nodeValue;
                if (stripos($originalContent, 'url(') !== false) {
                    $newContent = grinds_replace_css_urls($originalContent, function ($url) use ($processUrl) {
                        return $processUrl($url);
                    });
                    if ($newContent !== $originalContent)
                        $element->nodeValue = $newContent;
                }
                continue;
            }

            if ($element->hasAttribute('style')) {
                $originalValue = $element->getAttribute('style');
                if (stripos($originalValue, 'url(') !== false) {
                    $newValue = grinds_replace_css_urls($originalValue, function ($url) use ($processUrl) {
                        return $processUrl($url);
                    });
                    if ($newValue !== $originalValue)
                        $element->setAttribute('style', $newValue);
                }
            }

            if (isset($tagAttrs[$tagName])) {
                foreach ($tagAttrs[$tagName] as $attr) {
                    if ($element->hasAttribute($attr)) {
                        if ($element->hasAttribute('data-ssg-absolute')) {
                            $element->removeAttribute('data-ssg-absolute');
                            continue;
                        }

                        $originalValue = $element->getAttribute($attr);

                        // Remove admin links
                        if ($tagName === 'a' && $attr === 'href') {
                            if (strpos($originalValue, 'admin/') !== false || strpos($originalValue, 'login.php') !== false) {
                                if (preg_match('/(admin\/(index\.php|login\.php)?$|wp-admin|dashboard)/', $originalValue)) {
                                    $elementsToRemove[] = $element;
                                    continue;
                                }
                            }
                        }

                        $processedUrl = $processUrl($originalValue);
                        if ($processedUrl !== $originalValue)
                            $element->setAttribute($attr, $processedUrl);
                    }
                }
            }

            if (($tagName === 'img' || $tagName === 'source') && $element->hasAttribute('srcset')) {
                $srcset = $element->getAttribute('srcset');
                $parts = explode(',', $srcset);
                $newParts = [];
                foreach ($parts as $part) {
                    $part = trim($part);
                    $p = preg_split('/\s+/', $part, 2);
                    $url = $p[0];
                    $desc = $p[1] ?? '';
                    $processedUrl = $processUrl($url);
                    $newParts[] = $processedUrl . ($desc ? ' ' . $desc : '');
                }
                $element->setAttribute('srcset', implode(', ', $newParts));
            }

            if ($tagName === 'form') {
                $originalAction = $element->getAttribute('action');

                // 1. Detect if it's a search form. This should have the highest priority.
                $isSearch = false;
                $classes = explode(' ', strtolower($element->getAttribute('class')));
                $role = strtolower($element->getAttribute('role'));
                $method = strtolower($element->getAttribute('method'));
                $actionPath = parse_url($originalAction, PHP_URL_PATH) ?? '';

                if (in_array('grinds-search-form', $classes) || $role === 'search' || str_ends_with(rtrim($actionPath, '/'), '/search')) {
                    $isSearch = true;
                } elseif ($element->getElementsByTagName('input')->length > 0) {
                    foreach ($element->getElementsByTagName('input') as $input) {
                        $type = strtolower($input->getAttribute('type'));
                        if ($type === 'search') {
                            $isSearch = true;
                            break;
                        }
                    }
                }

                // Fallback: Check if submitting to homepage via GET with 's' or 'q' (common WordPress/CMS pattern)
                if (!$isSearch && ($method === 'get' || $method === '')) {
                    $livePath = parse_url($liveBaseUrl, PHP_URL_PATH) ?? '/';
                    $livePath = rtrim($livePath, '/');
                    $actionPathClean = rtrim($actionPath, '/');

                    if ($originalAction === '' || $originalAction === '/' || $actionPathClean === $livePath || rtrim($originalAction, '/') === rtrim($liveBaseUrl, '/')) {
                        foreach ($element->getElementsByTagName('input') as $input) {
                            $name = strtolower($input->getAttribute('name'));
                            if ($name === 's' || $name === 'q') {
                                $isSearch = true;
                                break;
                            }
                        }
                    }
                }

                // 2. Apply logic based on form type
                if ($isSearch) {
                    // Always use relative path for search.html and GET method
                    $searchPath = str_repeat('../', $depth) . 'search.html';
                    $element->setAttribute('action', $searchPath);
                    $element->setAttribute('method', 'get');
                } elseif (!empty($config['form_endpoint'])) {
                    // For all other forms, use the external endpoint if configured
                    $element->setAttribute('action', $config['form_endpoint']);
                    $element->setAttribute('method', 'post');
                } else {
                    // Fallback: Process as a normal link if no endpoint is set
                    $processedUrl = $processUrl($originalAction);
                    if ($processedUrl !== $originalAction) {
                        $element->setAttribute('action', $processedUrl);
                    }
                }
            }
        }

        foreach ($elementsToRemove as $elementToRemove) {
            if ($elementToRemove->parentNode)
                $elementToRemove->parentNode->removeChild($elementToRemove);
        }

        $relJsPath = $processUrl($jsToolPath);
        $body = $dom->getElementsByTagName('body')->item(0);
        if ($body) {
            // Calculate base URL for JS
            // Always use relative path for JS to ensure the static site is self-contained
            $relativePrefix = str_repeat('../', $depth);
            if ($relativePrefix === '')
                $relativePrefix = './';
            $finalBaseUrl = $relativePrefix;

            $configScript = $dom->createElement('script');
            $configScript->nodeValue = "window.grindsBaseUrl = '" . $finalBaseUrl . "';";
            $body->appendChild($configScript);

            $script = $dom->createElement('script');
            $script->setAttribute('src', $relJsPath);
            $body->appendChild($script);
        }

        $html = $dom->saveHTML();

        // Restore Alpine.js attributes
        $html = preg_replace_callback(
            '/data-grinds-safe-([a-zA-Z0-9\.\-_]+)=/',
            function ($matches) {
                return str_replace(['--colon--', '--at--'], [':', '@'], $matches[1]) . '=';
            },
            $html
        );

        $html = mb_decode_numericentity($html, [0x80, 0x10FFFF, 0, 0x1FFFFF], 'UTF-8');
        $html = preg_replace('/<\?xml.*?\?>\s*/', '', $html, 1);

        // Ensure DOCTYPE is present for Standards Mode
        if (stripos(trim($html), '<!DOCTYPE') !== 0) {
            $html = "<!DOCTYPE html>\n" . $html;
        }

        return $html;
    }
}

try {
    $inputData = json_decode($_POST['data'] ?? '{}', true);
    if (!is_array($inputData)) {
        $inputData = [];
    }
    $step = $_POST['step'] ?? '';

    $ssg = new GrindsSSG($pdo, array_merge($inputData, ['step' => $step]));
    $response = $ssg->run($step, $inputData);

    json_response($response);
} catch (Exception $e) {
    json_response(['success' => false, 'error' => $e->getMessage()]);
}
