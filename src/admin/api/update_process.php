<?php

/**
 * update_process.php
 *
 * Asynchronous Update API Endpoint.
 * Executes the update process step-by-step to prevent timeouts and ensure atomic recovery.
 */
require_once __DIR__ . '/api_bootstrap.php';

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

$step = $_POST['step'] ?? '';
$inputData = json_decode($_POST['data'] ?? '{}', true) ?: [];

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
    '.htaccess',
    'data',
    'assets/uploads',
    '.git',
    '.github',
    '.gitignore',
    '.DS_Store',
    'LICENSE.txt',
    'update.json',
    'README.md',
    '.idea',
    '.vscode',
    '.svn',
    '.hg',
    'Thumbs.db',
    '*.log',
    '.php-cs-fixer.cache'
];

if (!empty($inputData['skip_theme_skin'])) {
    $exclude[] = 'theme';
    $exclude[] = 'admin/skins';
}

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
            if (!is_dir($tmpBase)) @mkdir($tmpBase, 0775, true);

            $success = $updater->downloadDirect($url, $zipFilePath);
            if (!$success) throw new Exception("Failed to download update package.");

            if ($expectedHash) {
                $actualHash = hash_file('sha256', $zipFilePath);
                if (!hash_equals($expectedHash, $actualHash)) {
                    @unlink($zipFilePath);
                    throw new Exception("Security Error: Update package hash mismatch.");
                }
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
            $updater->dryRun($sourceDir, ROOT_PATH, $exclude);
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
            $updater->backupCoreFiles($sourceDir, ROOT_PATH, $backupDir, $exclude);
            json_response(['success' => true]);
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
                $updater->applyUpdate($sourceDir, ROOT_PATH, $exclude);
            } catch (Exception $e) {
                // ATOMIC ROLLBACK ON FAILURE
                try {
                    $updater->rollback($backupDir, ROOT_PATH);
                    throw new Exception("Update failed: " . $e->getMessage() . " (System successfully rolled back to previous state)");
                } catch (Exception $rollbackEx) {
                    throw new Exception("CRITICAL ERROR: Update failed and rollback failed! " . $e->getMessage() . " | Rollback Error: " . $rollbackEx->getMessage());
                }
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
