<?php

/**
 * media_usage.php
 *
 * Fetch detailed usage info for a media file.
 */
require_once __DIR__ . '/api_bootstrap.php';

// Check permissions
if (!current_user_can('manage_media')) {
  json_response(['success' => false, 'error' => 'Permission denied'], 403);
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
  json_response(['success' => false, 'error' => 'Invalid ID']);
}

try {
  $stmt = $pdo->prepare("SELECT filepath FROM media WHERE id = ?");
  $stmt->execute([$id]);
  $path = $stmt->fetchColumn();

  if (!$path) {
    json_response(['success' => false, 'error' => 'Not found'], 404);
  }

  $usageDetails = [];

  // Helper function to check path in content string
  $isPathInContent = function ($content, $filePath) {
    if (empty($filePath) || empty($content)) return false;

    // 1. Raw match (case-insensitive)
    if (stripos($content, $filePath) !== false) return true;

    // 2. JSON escaped match
    $jsonEscaped = str_replace('/', '\\/', $filePath);
    if (stripos($content, $jsonEscaped) !== false) return true;

    // 3. URL encoded match
    $parts = explode('/', $filePath);
    $encodedParts = array_map('rawurlencode', $parts);
    $urlEncoded = implode('/', $encodedParts);
    if ($urlEncoded !== $filePath) {
      if (stripos($content, $urlEncoded) !== false) return true;

      $urlJsonEscaped = str_replace('/', '\\/', $urlEncoded);
      if (stripos($content, $urlJsonEscaped) !== false) return true;
    }

    // 4. Fallback basename check (extremely robust)
    $pathParts = explode('/', str_replace('\\', '/', $filePath));
    $baseName = end($pathParts);
    if (strlen($baseName) >= 3) {
      if (stripos($content, $baseName) !== false) return true;
      $encodedBaseName = rawurlencode($baseName);
      if ($encodedBaseName !== $baseName && stripos($content, $encodedBaseName) !== false) return true;
    }

    return false;
  };

  // Multi-byte safe basename extraction
  $pathParts = explode('/', str_replace('\\', '/', $path));
  $basename = end($pathParts);
  $basenameForLike = grinds_escape_like($basename);
  $encodedBasename = rawurlencode($basename);
  $encodedBasenameForLike = grinds_escape_like($encodedBasename);

  $searchParams = ['%' . $basenameForLike . '%'];
  if ($basename !== $encodedBasename) {
    $searchParams[] = '%' . $encodedBasenameForLike . '%';
  }

  // Permission checks for generating edit URLs
  $can_manage_posts = current_user_can('manage_posts');
  $can_manage_banners = current_user_can('manage_banners');
  $can_manage_menus = current_user_can('manage_menus');
  $can_manage_widgets = current_user_can('manage_widgets');
  $can_manage_settings = current_user_can('manage_settings');

  // 1. Posts
  $whereClause = [];
  $execParams = [];
  $columns = ['thumbnail', 'hero_image', 'content', 'hero_settings'];
  foreach ($columns as $col) {
    foreach ($searchParams as $sp) {
      $whereClause[] = "$col LIKE ? ESCAPE '\\'";
      $execParams[] = $sp;
    }
  }

  $stmt = $pdo->prepare("SELECT id, title, thumbnail, hero_image, content, hero_settings FROM posts WHERE " . implode(' OR ', $whereClause));
  $stmt->execute($execParams);
  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $found = false;
    if (
      $isPathInContent((string)$row['thumbnail'], $path) ||
      $isPathInContent((string)$row['hero_image'], $path)
    ) {
      $found = true;
    }

    if (!$found) {
      foreach (['content', 'hero_settings'] as $col) {
        if ($isPathInContent((string)$row[$col], $path)) {
          $found = true;
          break;
        }
      }
    }

    if ($found) {
      $usageDetails[] = [
        'type' => 'post',
        'id' => $row['id'],
        'title' => $row['title'],
        'edit_url' => $can_manage_posts ? 'posts.php?action=edit&id=' . $row['id'] : null
      ];
    }
  }

  // Banners
  $whereClause = [];
  $execParams = [];
  foreach (['image_url', 'html_code'] as $col) {
    foreach ($searchParams as $sp) {
      $whereClause[] = "$col LIKE ? ESCAPE '\\'";
      $execParams[] = $sp;
    }
  }
  $stmt = $pdo->prepare("SELECT id, image_url, html_code FROM banners WHERE " . implode(' OR ', $whereClause));
  $stmt->execute($execParams);
  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if ($isPathInContent((string)$row['image_url'], $path) || $isPathInContent((string)($row['html_code'] ?? ''), $path)) {
      $usageDetails[] = [
        'type' => 'banner',
        'id' => $row['id'],
        'title' => 'Banner #' . $row['id'],
        'edit_url' => $can_manage_banners ? 'banners.php?edit_id=' . $row['id'] : null
      ];
    }
  }

  // Users
  $whereClause = [];
  $execParams = [];
  foreach ($searchParams as $sp) {
    $whereClause[] = "avatar LIKE ? ESCAPE '\\'";
    $execParams[] = $sp;
  }
  $stmt = $pdo->prepare("SELECT id, username, avatar FROM users WHERE " . implode(' OR ', $whereClause));
  $stmt->execute($execParams);
  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if ($isPathInContent((string)$row['avatar'], $path)) {
      $usageDetails[] = [
        'type' => 'user',
        'id' => $row['id'],
        'title' => $can_manage_settings ? $row['username'] : 'User',
        'edit_url' => $can_manage_settings ? 'settings.php?tab=users' : null
      ];
    }
  }

  // Settings
  $whereClause = [];
  $execParams = [];
  foreach ($searchParams as $sp) {
    $whereClause[] = "value LIKE ? ESCAPE '\\'";
    $execParams[] = $sp;
  }
  $stmt = $pdo->prepare("SELECT key, value FROM settings WHERE " . implode(' OR ', $whereClause));
  $stmt->execute($execParams);
  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if ($isPathInContent((string)$row['value'], $path)) {
      $usageDetails[] = [
        'type' => 'setting',
        'id' => $row['key'],
        'title' => $row['key'],
        'edit_url' => $can_manage_settings ? 'settings.php' : null
      ];
    }
  }

  // Nav menus
  $whereClause = [];
  $execParams = [];
  foreach ($searchParams as $sp) {
    $whereClause[] = "url LIKE ? ESCAPE '\\'";
    $execParams[] = $sp;
  }
  $stmt = $pdo->prepare("SELECT id, label, url FROM nav_menus WHERE " . implode(' OR ', $whereClause));
  $stmt->execute($execParams);
  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if ($isPathInContent((string)$row['url'], $path)) {
      $usageDetails[] = [
        'type' => 'menu',
        'id' => $row['id'],
        'title' => $row['label'],
        'edit_url' => $can_manage_menus ? 'menus.php?edit_id=' . $row['id'] : null
      ];
    }
  }

  // Widgets
  $whereClause = [];
  $execParams = [];
  foreach (['content', 'settings'] as $col) {
    foreach ($searchParams as $sp) {
      $whereClause[] = "$col LIKE ? ESCAPE '\\'";
      $execParams[] = $sp;
    }
  }
  $stmt = $pdo->prepare("SELECT id, title, content, settings FROM widgets WHERE " . implode(' OR ', $whereClause));
  $stmt->execute($execParams);
  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $found = false;
    foreach (['content', 'settings'] as $col) {
      if ($isPathInContent((string)$row[$col], $path)) {
        $found = true;
        break;
      }
    }
    if ($found) {
      $usageDetails[] = [
        'type' => 'widget',
        'id' => $row['id'],
        'title' => $can_manage_settings ? $row['title'] : 'Widget',
        'edit_url' => $can_manage_widgets ? 'widgets.php?edit_id=' . $row['id'] : null
      ];
    }
  }

  // Remove duplicates in usageDetails
  $uniqueUsage = [];
  $seen = [];
  foreach ($usageDetails as $u) {
    $key = $u['type'] . '_' . $u['id'];
    if (!isset($seen[$key])) {
      $seen[$key] = true;
      $uniqueUsage[] = $u;
    }
  }

  json_response(['success' => true, 'usage' => $uniqueUsage]);
} catch (Exception $e) {
  json_response(['success' => false, 'error' => $e->getMessage()], 500);
}
