<?php

/**
 * upload.php
 *
 * Handle file uploads for the media library.
 */
require_once __DIR__ . '/api_bootstrap.php';

// Check permissions
if (!current_user_can('manage_media')) {
  json_response(['success' => false, 'error' => 'Forbidden'], 403);
}

// Extend execution time for large uploads
if (function_exists('grinds_set_high_load_mode')) {
  grinds_set_high_load_mode();
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  json_response(['success' => false, 'error' => 'Method Not Allowed'], 405);
}

try {
  // Validate CSRF token
  check_csrf_token();

  // Release session lock to prevent blocking other requests during file processing
  session_write_close();

  // Validate upload
  if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    $errorCode = $_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE;

    $errorMsg = match ($errorCode) {
      UPLOAD_ERR_INI_SIZE   => _t('err_upload_ini_size'),
      UPLOAD_ERR_FORM_SIZE  => _t('err_upload_form_size'),
      UPLOAD_ERR_PARTIAL    => _t('err_upload_partial'),
      UPLOAD_ERR_NO_FILE    => _t('err_upload_no_file'),
      UPLOAD_ERR_NO_TMP_DIR => _t('err_upload_no_tmp_dir'),
      UPLOAD_ERR_CANT_WRITE => _t('err_upload_cant_write'),
      UPLOAD_ERR_EXTENSION  => _t('err_upload_extension'),
      default               => _t('err_upload_unknown'),
    };

    throw new Exception($errorMsg . " (Code: $errorCode)");
  }

  $file = $_FILES['image'];

  // Process upload
  $relativePath = FileManager::handleUpload($file, $pdo);

  if (!$relativePath) {
    throw new Exception(_t('err_unknown_upload_error'));
  }

  // Get media ID
  $mediaId = $pdo->lastInsertId();

  // Fetch media details
  $stmt = $pdo->prepare("SELECT * FROM media WHERE id = ?");
  $stmt->execute([$mediaId]);
  $mediaRow = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$mediaRow) {
    throw new Exception(_t('err_database_error'));
  }

  $meta = json_decode($mediaRow['metadata'] ?? '{}', true);
  $isImage = (strpos($mediaRow['file_type'] ?? '', 'image/') === 0);
  $thumbnailUrl = ($isImage && !empty($meta['thumbnail'])) ? resolve_url($meta['thumbnail']) : null;

  // Clear media months cache to reflect new upload
  grinds_clear_media_cache();

  // Return success response
  json_response([
    'success' => true,
    'file' => [
      'id' => $mediaId,
      'url' => resolve_url($relativePath),
      'thumbnail_url' => $thumbnailUrl,
      'filePath' => $relativePath,
      'filename' => basename($relativePath),
      'file_type' => $mediaRow['file_type'],
      'file_size' => (int)$mediaRow['file_size'],
      'is_image' => $isImage,
      'metadata' => $meta
    ]
  ]);
} catch (Exception $e) {
  json_response(['success' => false, 'error' => $e->getMessage()], 500);
}
