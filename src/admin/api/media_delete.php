<?php

/**
 * media_delete.php
 *
 * Delete media files and their associated database records.
 */
require_once __DIR__ . '/api_bootstrap.php';

// Check permissions
if (!current_user_can('manage_media')) {
  json_response(['success' => false, 'error' => 'Forbidden'], 403);
}

// Configure execution limits
if (function_exists('grinds_set_high_load_mode')) {
  grinds_set_high_load_mode();
}

// Enforce POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  json_response(['success' => false, 'error' => 'Method Not Allowed'], 405);
}

try {
  // Parse JSON input
  $input = get_json_input();

  if (!is_array($input)) {
    throw new Exception(_t('err_invalid_json'));
  }

  // Verify CSRF token
  check_csrf_token();

  // Release session lock to prevent blocking other requests
  session_write_close();

  // Extract media IDs
  $ids = [];
  if (isset($input['ids']) && is_array($input['ids'])) {
    $ids = $input['ids'];
  } elseif (isset($input['id'])) {
    $ids[] = $input['id'];
  }

  $ids = array_unique($ids);
  $force = $input['force'] ?? false;

  if (empty($ids)) {
    throw new Exception(_t('err_id_required'));
  }

  // Prepare delete statements
  $stmtDel = $pdo->prepare("DELETE FROM media WHERE id = ?");

  $deletedCount = 0;
  $skippedCount = 0;
  $lastError = null;
  $filesToDelete = [];
  $idsToDelete = [];

  // Fetch all file paths first
  $filesMap = [];
  $pathChunks = array_chunk($ids, 500); // Larger chunks for DB query
  foreach ($pathChunks as $chunk) {
    $placeholders = implode(',', array_fill(0, count($chunk), '?'));
    $stmtFiles = $pdo->prepare("SELECT id, filepath FROM media WHERE id IN ($placeholders)");
    $stmtFiles->execute($chunk);
    $filesMap += $stmtFiles->fetchAll(PDO::FETCH_KEY_PAIR);
  }

  // Bulk check usage if not forced
  $usageMap = [];
  if (!$force && !empty($filesMap)) {
    if (class_exists('FileManager')) {
      $usageMap = FileManager::getBulkFileUsage($pdo, array_values($filesMap));
    }
  }

  foreach ($ids as $id) {
    if (!isset($filesMap[$id]))
      continue;
    $filePath = $filesMap[$id];

    try {
      if (!$force) {
        if (isset($usageMap[$filePath])) {
          $usageType = $usageMap[$filePath];
          $errKey = 'err_media_in_use_' . $usageType;
          throw new Exception(_t('msg_cant_delete') . " " . _t($errKey), 409);
        }
      }

      // Mark for deletion
      $idsToDelete[] = $id;
      $filesToDelete[] = $filePath;
    } catch (Exception $eCheck) {
      $skippedCount++;
      if ($lastError === null)
        $lastError = $eCheck;
    }
  }

  // Delete from DB FIRST (Transaction) to prevent zombie records
  // If file deletion fails later, we have orphaned files (cleanable) but consistent DB.
  if (!empty($idsToDelete)) {
    $pdo->beginTransaction();
    try {
      foreach ($idsToDelete as $id) {
        $stmtDel->execute([$id]);
      }
      $pdo->commit();
      $deletedCount = count($idsToDelete);
    } catch (Exception $e) {
      if ($pdo->inTransaction()) {
        $pdo->rollBack();
      }
      throw $e;
    }
  }

  // Delete physical files AFTER DB commit
  if ($deletedCount > 0) {
    foreach ($filesToDelete as $path) {
      if (class_exists('FileManager')) {
        // If deletion fails (e.g. permission), it's an orphaned file, not a broken link.
        if (!FileManager::delete($path)) {
          error_log("GrindSite Warning: Failed to delete physical file: " . $path);
        }
      }
    }
  }

  // Handle single failure
  if (count($ids) === 1 && $skippedCount === 1) {
    throw $lastError ?? new Exception(_t('err_file_in_use'));
  }

  // Clear media months cache if items were deleted
  if ($deletedCount > 0) {
    grinds_clear_media_cache();

    if (function_exists('clear_page_cache')) {
      clear_page_cache();
    }
  }

  // Return success response
  json_response([
    'success' => true,
    'deleted' => $deletedCount,
    'skipped' => $skippedCount,
    'error' => $lastError ? $lastError->getMessage() : null
  ]);
} catch (Exception $e) {
  // Rollback transaction
  if ($pdo->inTransaction()) {
    $pdo->rollBack();
  }

  // Return error response
  $code = $e->getCode();
  if (!is_numeric($code) || $code < 400 || $code > 599)
    $code = 500;
  json_response(['success' => false, 'error' => $e->getMessage(), 'code' => (int)$code], (int)$code);
}
