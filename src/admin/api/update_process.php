<?php

/**
 * update_process.php
 *
 * Asynchronous Update API Endpoint.
 * Executes the update process step-by-step to prevent timeouts and ensure atomic recovery.
 */
require_once __DIR__ . '/api_bootstrap.php';

/** @var \PDO $pdo */
// Relax resource limits
if (function_exists('grinds_set_high_load_mode')) {
    grinds_set_high_load_mode();
}

// Check permissions
if (!current_user_can('manage_settings')) {
    json_response(['success' => false, 'error' => 'Permission denied'], 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'error' => 'Method Not Allowed'], 405);
}

check_csrf_token();

// Load Updater Class
require_once ROOT_PATH . '/lib/updater.php';

$step = is_scalar($_POST['step'] ?? null) ? (string)$_POST['step'] : '';
$rawPostData = is_scalar($_POST['data'] ?? null) ? (string)$_POST['data'] : '{}';
$inputData = json_decode($rawPostData, true) ?: [];

// Release session lock to prevent blocking
session_write_close();

$updater = new GrindsUpdater($pdo);

// Prepare temporary directories
$tmpBase = ROOT_PATH . '/data/tmp';
$zipFilePath = $tmpBase . '/update_package.zip';
$extractDir  = $tmpBase . '/update_extract';
$backupDir   = $tmpBase . '/update_backup';

$exclude = [
    'config.php',
    'install.php',
    'data',
    'assets/uploads',
    '.git',
    '.github',
    'LICENSE.txt',
    'update.json',
    'README.md',
    '.idea',
    '.vscode',
    '.svn',
    '.hg',
    '*.htaccess',
    '*.gitignore',
    '*.DS_Store',
    '*Thumbs.db',
    '*.log',
    '.php-cs-fixer.cache'
];

$skipThemeSkin = !empty($inputData['skip_theme_skin']);
if ($skipThemeSkin) {
    $exclude[] = 'theme';
    $exclude[] = 'admin/skins';
}

// Update bundled themes and skins the site still has, without restoring removed ones
$withMissingAssets = function (array $exclude, string $sourceDir) use ($updater, $skipThemeSkin): array {
    return $skipThemeSkin ? $exclude : array_merge($exclude, $updater->getMissingBundledAssets($sourceDir, ROOT_PATH));
};

try {
    switch ($step) {
        case 'init':
            // Check for updates and verify disk space
            $info = $updater->check();
            if (!$info['has_update']) {
                throw new Exception(function_exists('_t') ? _t('st_up_to_date') : 'System is already up to date.');
            }
            if (function_exists('disk_free_space')) {
                $freeSpace = @disk_free_space(ROOT_PATH);
                // Estimate: 50MB for operations
                if ($freeSpace !== false && $freeSpace < 50 * 1024 * 1024) {
                    throw new Exception("Insufficient disk space. At least 50MB of free space is required.");
                }
            }
            // Cleanup previous failed attempts
            if (is_dir($extractDir)) $updater->cleanupDir($extractDir);
            if (is_dir($backupDir))  $updater->cleanupDir($backupDir);
            if (file_exists($zipFilePath)) @unlink($zipFilePath);

            json_response(['success' => true, 'version' => $info['remote']['version'], 'url' => $info['remote']['download_url'], 'sha256' => $info['remote']['sha256'] ?? '']);
            break;

        case 'download':
            // Memory-safe stream download
            $url = $inputData['url'] ?? '';
            $expectedHash = $inputData['sha256'] ?? '';
            if (empty($url)) throw new Exception("Download URL is missing.");

            // ↓ 【セキュリティ強化】公式のアップデート情報と照合し、不正なURLからのダウンロードを防止
            $info = $updater->check();
            $officialUrl = $info['remote']['download_url'] ?? '';
            $officialHash = $info['remote']['sha256'] ?? '';

            if (empty($officialUrl) || $url !== $officialUrl) {
                throw new Exception("Security Error: Invalid or untrusted download URL. Update aborted.");
            }
            if (!is_string($officialHash) || !preg_match('/^[a-f0-9]{64}$/i', $officialHash)) {
                throw new Exception("Security Error: Update information has no valid checksum. Update aborted.");
            }
            if (!hash_equals(strtolower($officialHash), strtolower((string)$expectedHash))) {
                throw new Exception("Security Error: Hash mismatch detected before download. Update aborted.");
            }
            // ↑ ここまで

            if (!is_dir($tmpBase)) @mkdir($tmpBase, 0775, true);

            $success = $updater->downloadDirect($url, $zipFilePath);
            if (!$success) throw new Exception("Failed to download update package.");

            $actualHash = hash_file('sha256', $zipFilePath);
            if ($actualHash === false || !hash_equals(strtolower($officialHash), $actualHash)) {
                @unlink($zipFilePath);
                throw new Exception("Security Error: Update package hash mismatch.");
            }
            json_response(['success' => true]);
            break;

        case 'extract':
            // Extract ZIP safely
            if (!file_exists($zipFilePath)) throw new Exception("Update package not found.");
            $sourceDir = $updater->extractPackage($zipFilePath, $extractDir);
            if (!$sourceDir) throw new Exception("Failed to extract update package or locate source directory.");

            // Store source directory relative path in session/response
            $relSource = str_replace(str_replace('\\', '/', ROOT_PATH), '', str_replace('\\', '/', $sourceDir));
            json_response(['success' => true, 'source_dir' => ltrim($relSource, '/')]);
            break;

        case 'dry_run':
            // Check write permissions of all target paths before making any changes
            $relSource = $inputData['source_dir'] ?? '';
            if (empty($relSource) || strpos($relSource, '..') !== false) {
                throw new Exception("Invalid or missing source directory.");
            }
            $sourceDir = rtrim(ROOT_PATH, '/') . '/' . ltrim($relSource, '/');
            if (!is_dir($sourceDir)) throw new Exception("Extracted source directory not found.");
            $updater->dryRun($sourceDir, ROOT_PATH, $withMissingAssets($exclude, $sourceDir));
            json_response(['success' => true]);
            break;

        case 'backup':
            // Backup existing files to allow atomic rollback
            $relSource = $inputData['source_dir'] ?? '';
            if (empty($relSource) || strpos($relSource, '..') !== false) {
                throw new Exception("Invalid or missing source directory.");
            }
            $sourceDir = rtrim(ROOT_PATH, '/') . '/' . ltrim($relSource, '/');
            if (!is_dir($sourceDir)) throw new Exception("Extracted source directory not found.");
            $stepExclude = $withMissingAssets($exclude, $sourceDir);
            $updater->backupCoreFiles($sourceDir, ROOT_PATH, $backupDir, $stepExclude);

            $preserved = ['count' => 0, 'path' => ''];
            if (!$skipThemeSkin) {
                $preserveBase = ROOT_PATH . '/data/theme_backups';
                if (!grinds_secure_dir($preserveBase)) {
                    throw new Exception("Failed to create data/theme_backups.");
                }
                $preserveName = date('Y-m-d_His');
                $preserveDir = $preserveBase . '/' . $preserveName;
                $preserved['count'] = $updater->preserveChangedThemeFiles($sourceDir, ROOT_PATH, $preserveDir, $stepExclude);
                if ($preserved['count'] > 0) {
                    $preserved['path'] = 'data/theme_backups/' . $preserveName;
                    $updater->rotateDirs($preserveBase, 5);
                }
            }
            json_response(['success' => true, 'preserved' => $preserved]);
            break;

        case 'apply':
            // Apply update (Overwrite files)
            $relSource = $inputData['source_dir'] ?? '';
            if (empty($relSource) || strpos($relSource, '..') !== false) {
                throw new Exception("Invalid or missing source directory.");
            }
            $sourceDir = rtrim(ROOT_PATH, '/') . '/' . ltrim($relSource, '/');
            if (!is_dir($sourceDir)) throw new Exception("Extracted source directory not found.");

            try {
                $updater->applyUpdate($sourceDir, ROOT_PATH, $withMissingAssets($exclude, $sourceDir));
            } catch (Exception $e) {
                // ATOMIC ROLLBACK ON FAILURE
                try {
                    $updater->rollback($backupDir, ROOT_PATH);
                } catch (Exception $rollbackEx) {
                    throw new Exception("CRITICAL ERROR: Update failed and rollback failed! " . $e->getMessage() . " | Rollback Error: " . $rollbackEx->getMessage());
                }
                throw new Exception("Update failed: " . $e->getMessage() . " (System successfully rolled back to previous state)");
            }
            json_response(['success' => true]);
            break;

        case 'cleanup':
            // Remove temporary files, run OPcache reset
            if (is_dir($extractDir)) $updater->cleanupDir($extractDir);
            if (is_dir($backupDir))  $updater->cleanupDir($backupDir);
            if (file_exists($zipFilePath)) @unlink($zipFilePath);

            // Cleanup Windows locked files
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $updater->cleanupWindowsLockedFiles(ROOT_PATH);
            }

            if (function_exists('opcache_reset')) {
                @opcache_reset();
            }
            if (function_exists('clear_page_cache')) {
                clear_page_cache();
            }

            json_response(['success' => true]);
            break;

        default:
            throw new Exception("Invalid update step: " . htmlspecialchars($step));
    }
} catch (Exception $e) {
    json_response(['success' => false, 'error' => $e->getMessage()], 500);
}
