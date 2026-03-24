<?php

/**
 * clear_cache.php
 *
 * API endpoint to clear page cache and temporary preview files.
 */
require_once __DIR__ . '/api_bootstrap.php';

// Check permissions
if (!current_user_can('manage_settings') && !current_user_can('manage_tools')) {
  json_response(['success' => false, 'error' => 'Permission denied'], 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  json_response(['success' => false, 'error' => 'Method Not Allowed'], 405);
}

// Validate CSRF token
check_csrf_token();

// Clear page cache
clear_page_cache();

// Clear dangerous files cache
if (function_exists('update_option')) {
  update_option('grinds_dangerous_files_cache', '');
}

// Clear preview files
$previewDirs = [
  ROOT_PATH . '/data/tmp/preview',
  ROOT_PATH . '/assets/uploads/_preview'
];
foreach ($previewDirs as $pDir) {
  if (is_dir($pDir)) {
    try {
      foreach (new DirectoryIterator($pDir) as $fileInfo) {
        if ($fileInfo->isFile() && str_starts_with($fileInfo->getFilename(), 'preview_')) {
          if (function_exists('grinds_force_unlink')) {
            grinds_force_unlink($fileInfo->getPathname());
          } else {
            @unlink($fileInfo->getPathname());
          }
        }
      }
    } catch (Exception $e) { /* Ignore */
    }
  }
}

json_response(['success' => true]);
