<?php

/**
 * create_migration_package.php
 *
 * Create a migration package (ZIP) containing the database and uploads.
 */
require_once __DIR__ . '/api_bootstrap.php';

// Set runtime limits
if (function_exists('grinds_set_high_load_mode')) {
  grinds_set_high_load_mode();
}

// Check permissions
if (!current_user_can('manage_tools')) {
  $action = $_POST['action'] ?? $_GET['action'] ?? '';
  if ($action === 'download') {
    grinds_clean_output_buffer();
    http_response_code(403);
    exit('Permission denied');
  }
  json_response(['success' => false, 'error' => 'Permission denied'], 403);
}

$uid = substr(hash('sha256', session_id() . ($_SESSION['user_id'] ?? '')), 0, 16);
$zipFile = ROOT_PATH . "/data/tmp/migration_package_{$uid}.zip";
$safeDbFile = ROOT_PATH . "/data/tmp/migration_safe_{$uid}.db";
$fileListPath = ROOT_PATH . "/data/tmp/migration_files_{$uid}.json";
$uploadDir = ROOT_PATH . '/assets/uploads';

// Handle download
$action = $_POST['action'] ?? $_GET['action'] ?? '';
if ($action === 'download') {
  $csrf_token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
  if (!validate_csrf_token($csrf_token)) {
    grinds_clean_output_buffer();
    http_response_code(403);
    exit(_t('err_invalid_csrf_token'));
  }

  if (file_exists($zipFile)) {
    grinds_clean_output_buffer();
    header('Content-Type: application/zip');
    header('Content-disposition: attachment; filename=grinds_migration_' . date('Ymd_His') . '.zip');
    header('Content-Length: ' . filesize($zipFile));
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Stream output using chunks to prevent memory exhaustion (OOM) on huge ZIP files
    if (function_exists('set_time_limit')) @set_time_limit(0);
    $handle = @fopen($zipFile, 'rb');
    if ($handle) {
      while (!feof($handle)) {
        echo fread($handle, 8192);
        flush();
      }
      fclose($handle);
    }

    // Cleanup
    grinds_force_unlink($zipFile);
    exit;
  } else {
    grinds_clean_output_buffer();
    http_response_code(404);
    exit('File not found');
  }
}

// Validate CSRF token
check_csrf_token();

$step = $_POST['step'] ?? 'init';

// Keep session open
$currentCsrfToken = $_SESSION['csrf_token'] ?? '';

$data = json_decode($_POST['data'] ?? '{}', true);
if (!is_array($data)) {
  $data = [];
}

// Release session lock to prevent blocking other requests
session_write_close();

try {
  if ($step === 'init') {
    // Cleanup old migration packages
    $tmpDir = dirname($zipFile);
    if (is_dir($tmpDir)) {
      $patterns = [
        $tmpDir . '/migration_package_*.zip',
        $tmpDir . '/migration_safe_*.db',
        $tmpDir . '/migration_files_*.json'
      ];
      foreach ($patterns as $pattern) {
        foreach (glob($pattern) as $f) {
          if (is_file($f) && (filemtime($f) < time() - 3600 || $f === $zipFile || $f === $safeDbFile || $f === $fileListPath)) {
            grinds_force_unlink($f);
          }
        }
      }
    }

    // Prepare DB snapshot
    if (!is_dir(dirname($safeDbFile))) @mkdir(dirname($safeDbFile), 0775, true);
    if (file_exists($safeDbFile)) {
      grinds_force_unlink($safeDbFile);
    }

    // Scan uploads and calculate size
    $fp = @fopen($fileListPath, 'w');
    if (!$fp) {
      throw new Exception(function_exists('_t') ? _t('err_create_temp_dir_failed') : 'Failed to create temporary file for migration.');
    }

    $uploadsSize = 0;
    $totalFiles = 0;

    clearstatcache();
    if (is_dir($uploadDir) && class_exists('FileManager')) {
      try {
        // Use FileManager::scanDirectory to standardize exclusion rules
        $files = FileManager::scanDirectory($uploadDir);
        foreach ($files as $filePath) {
          $size = @filesize($filePath);
          if ($size !== false) {
            fwrite($fp, json_encode($filePath, JSON_INVALID_UTF8_IGNORE) . "\n");
            $uploadsSize += $size;
            $totalFiles++;
          }
        }
      } catch (Exception $e) {
        // Skip unreadable directories
      }
    }

    // Resolve absolute DB path
    $db_path = grinds_get_db_path();

    // Check disk space
    if (file_exists($db_path) && function_exists('disk_free_space')) {
      clearstatcache(true, $db_path);
      $dbSize = filesize($db_path);

      $freeSpace = @disk_free_space(dirname($safeDbFile));
      // Estimate: Snapshot (DB) + ZIP (DB + Uploads) + Buffer
      $requiredSpace = ($dbSize * 2) + ($uploadsSize * 1.2);

      if ($freeSpace !== false && $freeSpace < $requiredSpace) {
        $reqMB = round($requiredSpace / 1024 / 1024, 2);
        $freeMB = round($freeSpace / 1024 / 1024, 2);
        throw new Exception("Insufficient disk space. Required: {$reqMB}MB, Free: {$freeMB}MB.");
      }
    }

    grinds_db_snapshot($safeDbFile);

    fclose($fp);

    if (!class_exists('ZipArchive')) {
      throw new Exception(function_exists('_t') ? _t('err_zip_extension_missing') : 'PHP Zip extension is missing.');
    }

    // Initialize ZIP with DB
    $zip = new ZipArchive();
    if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
      throw new Exception("Cannot create zip file.");
    }

    $zipPassword = function_exists('get_option') ? get_option('backup_zip_password', '') : '';
    if ($zipPassword !== '') {
      $zip->setPassword($zipPassword);
    }

    // Add DB file
    $dbName = basename(DB_FILE);
    $zip->addFile($safeDbFile, 'data/' . $dbName);
    if ($zipPassword !== '') $zip->setEncryptionName('data/' . $dbName, ZipArchive::EM_AES_256);

    if (file_exists(ROOT_PATH . '/config.php')) {
      $origConfig = file_get_contents(ROOT_PATH . '/config.php');
      $configContent = grinds_prepare_migration_config($origConfig, $dbName);
      $zip->addFromString('config.php', $configContent);
      if ($zipPassword !== '') $zip->setEncryptionName('config.php', ZipArchive::EM_AES_256);
    }
    $zip->close();

    // Clean up DB
    grinds_force_unlink($safeDbFile);

    json_response([
      'success' => true,
      'total_files' => $totalFiles,
      'csrf_token' => $currentCsrfToken
    ]);
  } elseif ($step === 'archive_batch') {
    $offset = $data['offset'] ?? 0;
    $startTime = microtime(true);
    $timeLimit = 20;

    if (!file_exists($fileListPath)) {
      throw new Exception("File list not found. Please restart.");
    }

    $zip = new ZipArchive();
    if ($zip->open($zipFile) !== TRUE) {
      throw new Exception("Cannot open zip file.");
    }

    $zipPassword = function_exists('get_option') ? get_option('backup_zip_password', '') : '';
    if ($zipPassword !== '') {
      $zip->setPassword($zipPassword);
    }

    $fp = fopen($fileListPath, 'r');
    if ($offset > 0) fseek($fp, $offset);

    $count = 0;
    while (($line = fgets($fp)) !== false) {
      $line = trim($line);
      if (empty($line)) continue;

      $filePath = json_decode($line);
      if (!$filePath) continue;

      $realFilePath = realpath($filePath);
      $realUploadDir = realpath($uploadDir);

      // Ensure both paths exist and are fully resolved to absolute paths
      if ($realFilePath && $realUploadDir) {
        // Normalize paths for reliable comparison across OS (especially Windows)
        $normRealFilePath = str_replace('\\', '/', $realFilePath);
        $normRealUploadDir = rtrim(str_replace('\\', '/', $realUploadDir), '/') . '/';

        // Strictly verify that the file is located inside the upload directory
        if (stripos($normRealFilePath, $normRealUploadDir) === 0) {
          $relativePath = 'assets/uploads/' . ltrim(substr($normRealFilePath, strlen($normRealUploadDir)), '/');
          if (is_readable($realFilePath)) {
            $zip->addFile($realFilePath, $relativePath);
            if ($zipPassword !== '') {
              $zip->setEncryptionName($relativePath, ZipArchive::EM_AES_256);
            }
          }
        }
      }
      $count++;

      if ($count % 10 === 0 && (microtime(true) - $startTime >= $timeLimit)) {
        break;
      }
    }

    $nextOffset = ftell($fp);
    $isEof = feof($fp);
    fclose($fp);
    $zip->close();

    json_response([
      'success' => true,
      'processed' => $count,
      'next_offset' => $nextOffset,
      'done' => $isEof,
      'csrf_token' => $currentCsrfToken
    ]);
  } elseif ($step === 'finalize') {
    if (file_exists($fileListPath)) {
      grinds_force_unlink($fileListPath);
    }

    json_response([
      'success' => true,
      'url' => 'api/create_migration_package.php?action=download&csrf_token=' . $currentCsrfToken,
      'csrf_token' => $currentCsrfToken
    ]);
  }
} catch (Exception $e) {
  json_response(['success' => false, 'error' => $e->getMessage()], 500);
}
