<?php

/**
 * media_update.php
 *
 * Update metadata and tags for a specific media file.
 */
require_once __DIR__ . '/api_bootstrap.php';

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  json_response(['success' => false, 'error' => _t('err_method_not_allowed')], 405);
}

// Check permissions
if (!current_user_can('manage_media')) {
  json_response(['success' => false, 'error' => 'Forbidden'], 403);
}

try {
  // Decode JSON input
  $input = get_json_input();

  if (!is_array($input)) {
    throw new Exception(_t('err_invalid_json'));
  }

  // Validate CSRF token
  check_csrf_token();

  // Get parameters
  $id = $input['id'] ?? null;
  $metadata = $input['metadata'] ?? null;
  $tags = $input['tags'] ?? null;

  // Validate input
  if (empty($id) || !is_array($metadata)) {
    throw new Exception(_t('err_invalid_parameters'));
  }

  $pdo->beginTransaction();
  try {
    $jsonUpdateStr = json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE);
    if ($jsonUpdateStr === false) {
      throw new Exception(_t('err_failed_encode_metadata'));
    }

    try {
        // High performance update using SQLite native JSON functions
        $stmt = $pdo->prepare("UPDATE media SET metadata = json_patch(COALESCE(metadata, '{}'), ?) WHERE id = ?");
        $stmt->execute([$jsonUpdateStr, $id]);
    } catch (PDOException $e) {
        // Fallback for older SQLite versions without JSON1 support
        $stmtGet = $pdo->prepare("SELECT metadata FROM media WHERE id = ?");
        $stmtGet->execute([$id]);
        $existingMetaStr = $stmtGet->fetchColumn();
        $existingMeta = $existingMetaStr ? json_decode($existingMetaStr, true) : [];
        if (!is_array($existingMeta)) {
          $existingMeta = [];
        }

        $mergedMeta = array_merge($existingMeta, $metadata);
        $jsonMeta = json_encode($mergedMeta, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE);

        $stmt = $pdo->prepare("UPDATE media SET metadata = ? WHERE id = ?");
        $stmt->execute([$jsonMeta, $id]);
    }

    // Update tags
    if ($tags !== null && is_array($tags) && class_exists('FileManager')) {
      FileManager::updateMediaTags($pdo, $id, $tags);
    }
    $pdo->commit();

    if (function_exists('clear_page_cache')) {
      clear_page_cache();
    }
  } catch (Exception $e) {
    $pdo->rollBack();
    throw $e;
  }

  // Return success response
  json_response(['success' => true]);
} catch (Exception $e) {
  json_response(['success' => false, 'error' => $e->getMessage()], 500);
}
