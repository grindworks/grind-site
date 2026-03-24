<?php

/**
 * rebuild_index.php
 *
 * Rebuild the search index for posts.
 */
require_once __DIR__ . '/api_bootstrap.php';

// Configure execution limits for batch processing
if (function_exists('grinds_set_high_load_mode')) {
  grinds_set_high_load_mode();
}
// Set memory limit
@ini_set('memory_limit', '-1');

// Check permissions
if (!current_user_can('manage_tools')) {
  json_response(['success' => false, 'error' => 'Access Denied'], 403);
}

// Enforce POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  json_response(['success' => false, 'error' => 'Method Not Allowed'], 405);
}

// Check CSRF token
check_csrf_token();
$input = get_json_input();

// Release session lock to prevent blocking other requests
session_write_close();

try {
  $offset = isset($input['offset']) ? (int)$input['offset'] : 0;
  $startTime = microtime(true);
  $timeLimit = 20;
  $batchSize = 20;

  $count = 0;
  while (true) {
    $processed = grinds_rebuild_post_index($pdo, null, $batchSize, $offset + $count);
    if ($processed === 0) {
      break;
    }
    $count += $processed;

    if (microtime(true) - $startTime >= $timeLimit) {
      break;
    }
  }

  // Check progress
  $repo = new PostRepository($pdo);
  $total = $repo->count(['type' => ['post', 'page']]);
  $nextOffset = $offset + $count;
  if ($count === 0) {
    $hasMore = false;
  } else {
    $hasMore = $nextOffset < $total;
  }

  json_response([
    'success' => true,
    'processed' => $count,
    'next_offset' => $nextOffset,
    'has_more' => $hasMore,
    'total' => $total,
    'percentage' => $total > 0 ? min(100, round(($nextOffset / $total) * 100)) : 100
  ]);
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  json_response(['success' => false, 'error' => $e->getMessage()], 500);
}
