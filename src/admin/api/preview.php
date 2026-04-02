<?php

/**
 * preview.php
 *
 * Handle temporary storage of post data for previewing.
 */
require_once __DIR__ . '/api_bootstrap.php';

// Check permissions
if (!current_user_can('manage_posts')) {
  json_response(['success' => false, 'error' => 'Forbidden'], 403);
}

// Relax resource limits
if (function_exists('grinds_set_high_load_mode')) {
  grinds_set_high_load_mode();
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  json_response(['success' => false, 'error' => _t('err_method_not_allowed')], 405);
}

try {
  // Validate CSRF token
  check_csrf_token();

  // Release session lock
  session_write_close();

  // Handle temporary uploads
  $previewFiles = [];
  foreach (['thumbnail', 'hero_image', 'hero_image_mobile'] as $key) {
    if (isset($_FILES[$key]) && $_FILES[$key]['error'] === UPLOAD_ERR_OK && is_uploaded_file($_FILES[$key]['tmp_name'])) {
      // Check file size
      $maxSize = function_exists('grinds_get_max_upload_size') ? grinds_get_max_upload_size() : 5 * 1024 * 1024;
      if ($_FILES[$key]['size'] > $maxSize) {
        $maxMB = round($maxSize / 1024 / 1024);
        throw new Exception(str_replace('%s', $maxMB . 'MB', _t('js_file_too_large')));
      }

      $finfo = new finfo(FILEINFO_MIME_TYPE);
      $mime = $finfo->file($_FILES[$key]['tmp_name']);
      if (strpos($mime, 'image/') === 0) {
        // Get safe extension
        if ($mime === 'image/svg+xml' && !current_user_can('manage_settings')) {
          throw new Exception('SVG uploads are restricted to Administrators.');
        }

        $safeExt = match ($mime) {
          'image/jpeg' => 'jpg',
          'image/png'  => 'png',
          'image/gif'  => 'gif',
          'image/webp' => 'webp',
          'image/avif' => 'avif',
          'image/svg+xml' => 'svg',
          default      => 'jpg'
        };

        $tmpFilename = 'preview_' . bin2hex(random_bytes(8)) . '.' . $safeExt;

        $tmpPreviewDir = ROOT_PATH . '/assets/uploads/_preview';
        if (!is_dir($tmpPreviewDir)) {
          @mkdir($tmpPreviewDir, 0775, true);
        }

        $tmpPath = $tmpPreviewDir . '/' . $tmpFilename;
        if (move_uploaded_file($_FILES[$key]['tmp_name'], $tmpPath)) {
          $baseUrl = rtrim(defined('BASE_URL') ? BASE_URL : '', '/');
          $previewFiles[$key] = $baseUrl . '/assets/uploads/_preview/' . $tmpFilename;
        }
      }
    }
  }

  // Resolve image source
  $getPreviewImage = function ($key) use ($previewFiles) {
    $sanitizeUrl = function ($url) {
      if (!is_string($url) || empty(trim($url))) return '';
      // Prevent XSS via javascript: or data: URIs. Allow data:image for base64 images.
      if (preg_match('/^\s*(javascript|vbscript|data(?!:image)):/i', trim($url))) {
        return '';
      }
      return filter_var($url, FILTER_SANITIZE_URL);
    };

    if (!empty($_POST["delete_{$key}"])) return '';
    if (isset($previewFiles[$key])) return $previewFiles[$key];
    if (!empty($_POST["{$key}_url"])) return $sanitizeUrl($_POST["{$key}_url"]);
    return $sanitizeUrl($_POST["current_{$key}"] ?? '');
  };

  // Construct hero settings
  $heroSettings = grinds_build_hero_settings($_POST, $getPreviewImage('hero_image_mobile'));
  $heroSettingsJson = json_encode($heroSettings, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE);

  // Construct tags array
  $tagsInput = $_POST['tags'] ?? '';
  $tagsPreview = [];
  if (!empty($tagsInput)) {
    $tagNames = grinds_parse_tag_string($tagsInput);

    foreach ($tagNames as $name) {
      $tagsPreview[] = [
        'name' => $name,
        'slug' => function_exists('generate_slug') ? generate_slug($name, null, 'tag-') : 'preview-tag',
      ];
    }
  }

  // Prepare post data
  $postData = grinds_prepare_post_data_from_request($_POST);

  // Security: Sanitize content for users without unfiltered_html capability to prevent XSS in previews
  if (!current_user_can('unfiltered_html')) {
    // To prevent discrepancies between preview and saved content,
    // apply the same sanitization process as when saving.
    // This strips unauthorized HTML tags silently, mirroring the save behavior.
    if (function_exists('grinds_sanitize_post_content')) {
      $postData['content'] = grinds_sanitize_post_content($postData['content'] ?? '');
    } else {
      grinds_validate_content_security($postData['content'] ?? '');
    }
  }

  // Structure preview data
  $previewData = array_merge($postData, [
    'id' => $_POST['id'] ?? 0,
    'hero_image' => $getPreviewImage('hero_image'),
    'hero_settings' => $heroSettingsJson,
    'thumbnail' => $getPreviewImage('thumbnail'),
    'updated_at' => date('Y-m-d H:i:s'),
    'created_at' => date('Y-m-d H:i:s'),
    '__tags_preview' => $tagsPreview,
    '__expires_at' => time() + 3600,
  ]);

  if (empty($previewData['slug'])) {
    $previewData['slug'] = 'preview';
  }

  // Set preview directory
  $previewDir = ROOT_PATH . '/data/tmp/preview';

  // Ensure directory exists
  if (!grinds_secure_dir($previewDir)) {
    $err = error_get_last();
    throw new Exception(_t('err_create_preview_dir') . ': ' . ($err['message'] ?? _t('js_unknown_error')));
  }

  // Check permissions
  if (!is_writable($previewDir)) {
    throw new Exception(_t('err_preview_dir_not_writable') . ': ' . $previewDir);
  }

  // Save preview data
  $token = bin2hex(random_bytes(16));
  $filename = 'preview_' . $token . '.json';
  $savePath = $previewDir . '/' . $filename;

  $jsonData = json_encode($previewData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_IGNORE);
  if ($jsonData === false) {
    throw new Exception(_t('err_encode_preview_data'));
  }

  // Check size limit
  $maxSize = defined('MAX_PREVIEW_SIZE') ? (int)MAX_PREVIEW_SIZE : 25 * 1024 * 1024;
  if (strlen($jsonData) > $maxSize) {
    $msg = str_replace('%s', round($maxSize / 1024 / 1024) . 'MB', _t('js_file_too_large'));
    throw new Exception($msg . " (Preview Data)");
  }

  if (file_put_contents($savePath, $jsonData) === false) {
    $err = error_get_last();
    throw new Exception(_t('err_write_preview_file') . ': ' . ($err['message'] ?? _t('js_unknown_error')));
  }

  // Return preview token
  json_response(['success' => true, 'token' => $token]);
} catch (Exception $e) {
  json_response(['success' => false, 'error' => $e->getMessage()], 500);
}
