<?php

/**
 * posts.php
 *
 * Deliver published post data in JSON format.
 */

// Define app constant
define('GRINDS_APP', true);

// Load bootstrap
if (!require_once __DIR__ . '/../lib/bootstrap_public.php') {
  http_response_code(404);
  exit;
}

// Set headers
$allowed_origin = defined('API_ALLOWED_ORIGIN') ? constant('API_ALLOWED_ORIGIN') : '*';
header("Access-Control-Allow-Origin: $allowed_origin");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit;
}

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-cache, must-revalidate");
header("X-Robots-Tag: noindex, nofollow");

// Disable errors
ini_set('display_errors', 0);
error_reporting(0);

try {
  $pdo = App::db();
  if (!$pdo) {
    throw new Exception('Database connection failed');
  }

  // Get parameters
  $limit = isset($_GET['limit']) ? max(1, min((int)$_GET['limit'], 100)) : 10;
  $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
  if ($page < 1)
    $page = 1;
  $slug = isset($_GET['slug']) ? $_GET['slug'] : null;
  $tag = isset($_GET['tag']) ? $_GET['tag'] : null;
  $category = isset($_GET['category']) ? $_GET['category'] : null;
  $q = isset($_GET['q']) ? trim($_GET['q']) : null;
  $type = isset($_GET['type']) && in_array($_GET['type'], ['post', 'page']) ? $_GET['type'] : 'post';

  // Fetch posts
  $repo = new PostRepository($pdo);
  $filters = [
    'status' => 'published',
    'type' => $type,
    'slug' => $slug,
    'category_slug' => $category,
    'tag_slug' => $tag,
    'search' => $q
  ];

  $result = $repo->paginate($filters, $page, $limit);
  $posts = $result['posts'];
  $total = $result['total'];

  // Fetch tags for all posts to avoid N+1
  if (function_exists('grinds_attach_tags')) {
    grinds_attach_tags($posts);
  }

  // Preload image metadata to avoid N+1 during render_content
  if (function_exists('grinds_preload_image_meta')) {
    $imageUrls = [];
    foreach ($posts as $p) {
      if (!empty($p['thumbnail'])) {
        $imageUrls[] = $p['thumbnail'];
      }
      $blocks = is_string($p['content']) ? json_decode($p['content'], true) : $p['content'];
      if (is_array($blocks) && isset($blocks['blocks'])) {
        if (class_exists('BlockRenderer')) {
          $extracted = BlockRenderer::extractImages($blocks['blocks']);
          $imageUrls = array_merge($imageUrls, $extracted);
        } else {
          foreach ($blocks['blocks'] as $block) {
            if (($block['type'] ?? '') === 'image' && !empty($block['data']['url'])) {
              $imageUrls[] = $block['data']['url'];
            }
          }
        }
      }
    }
    if (!empty($imageUrls)) {
      grinds_preload_image_meta($imageUrls);
    }
  }

  // Load theme functions to ensure custom blocks render correctly
  if (function_exists('grinds_get_active_theme') && function_exists('grinds_load_theme_functions')) {
    grinds_load_theme_functions(grinds_get_active_theme());
  }

  // Process posts
  $result = [];
  foreach ($posts as $post) {
    // Resolve URLs
    $contentResolved = function_exists('grinds_url_to_view') ? grinds_url_to_view($post['content']) : $post['content'];

    $blocks = json_decode($contentResolved, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
      $blocks = $contentResolved;
    } elseif (is_array($blocks) && isset($blocks['blocks'])) {
      $visibleBlocks = [];
      foreach ($blocks['blocks'] as $block) {
        if (($block['type'] ?? '') === 'password_protect') {
          if (isset($block['data']['password'])) {
            $block['data']['password'] = '***';
          }
          $visibleBlocks[] = $block;
          break; // パスワード保護ブロック以降のコンテンツは除外
        }
        $visibleBlocks[] = $block;
      }
      $blocks['blocks'] = $visibleBlocks;
      // plain_text等の生成でも機密データが漏洩しないように、フィルタリング済みの状態に上書きする
      $contentResolved = json_encode($blocks, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    $result[] = [
      'id' => (int)$post['id'],
      'title' => $post['title'],
      'slug' => $post['slug'],
      'date' => $post['published_at'] ?: $post['created_at'],
      'description' => $post['description'],
      // Resolve thumbnail
      'thumbnail' => !empty($post['thumbnail']) ? resolve_url(grinds_url_to_view((string)$post['thumbnail'])) : null,
      'content' => $blocks,
      'html' => function_exists('render_content') ? render_content($blocks) : '',
      'plain_text' => function_exists('grinds_extract_text_from_content') ? grinds_extract_text_from_content($contentResolved) : strip_tags($contentResolved),
      'features' => [
        'has_math' => strpos($contentResolved, '"type":"math"') !== false,
        'has_code' => strpos($contentResolved, '"type":"code"') !== false,
        'has_countdown' => strpos($contentResolved, '"type":"countdown"') !== false,
      ],
      'category' => [
        'name' => $post['category_name'],
        'slug' => $post['category_slug']
      ],
      'tags' => $post['tags'] ?? []
    ];
  }

  // Output response
  $meta = [
    'total' => $total,
    'limit' => $limit,
    'currentPage' => $page,
    'totalPages' => (int)ceil($total / $limit)
  ];

  // Clear buffer
  while (ob_get_level())
    ob_end_clean();

  echo json_encode([
    'success' => true,
    'data' => $slug ? ($result[0] ?? null) : $result,
    'meta' => $meta
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_IGNORE | JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['success' => false, 'error' => 'Server Error']);
}
