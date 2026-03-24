<?php

/**
 * media_tags.php
 *
 * API endpoint to retrieve tags for media files or tag suggestions.
 */
require_once __DIR__ . '/api_bootstrap.php';

// Check permissions
if (!current_user_can('manage_media')) {
  json_response(['success' => false, 'error' => 'Forbidden'], 403);
}

$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? 0;
$tags = [];

try {
  if ($action === 'suggestions') {
    if (class_exists('FileManager')) {
      $tags = FileManager::getTagSuggestions($pdo);
    }
  } elseif ($id) {
    if (class_exists('FileManager')) {
      $tags = FileManager::getMediaTags($pdo, $id);
    }
  }
} catch (Exception $e) {
  $tags = [];
}

json_response($tags);
