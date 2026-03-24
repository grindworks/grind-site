<?php

/**
 * templates.php
 *
 * Manage content templates (fetch list, get single, create).
 */
require_once __DIR__ . '/api_bootstrap.php';

// Check permissions
if (!current_user_can('manage_posts')) {
  json_response(['success' => false, 'error' => 'Forbidden'], 403);
}

$method = $_SERVER['REQUEST_METHOD'];

try {
  // Validate CSRF token
  if ($method === 'POST') {
    check_csrf_token();
    $input = get_json_input();
  }

  // Handle GET request
  elseif ($method === 'GET') {
    $repo = new PostRepository($pdo);
    if (isset($_GET['id'])) {
      $templates = $repo->fetch([
        'ids' => [$_GET['id']],
        'type' => 'template'
      ]);
      $template = $templates[0] ?? null;

      if ($template && !empty($template['content'])) {
        $template['content'] = grinds_url_to_view($template['content']);
      }

      json_response(['success' => true, 'data' => $template]);
    } else {
      // Fetch all templates

      $list = $repo->fetch(['type' => 'template'], 0, 0, 'p.title ASC');
      json_response(['success' => true, 'list' => $list]);
    }
  }
  // Handle POST request
  elseif ($method === 'POST') {
    $title = $input['title'] ?? _t('tpl_untitled');
    // Encode content
    $rawContent = $input['content'] ?? ['blocks' => []];
    if (!empty($input['content_is_base64']) && is_string($rawContent)) {
      $json = base64_decode(str_replace(' ', '+', $rawContent));
      if ($json === false) {
        throw new Exception('Invalid Base64 content.');
      }
    } else {
      $json = is_array($rawContent) ? json_encode($rawContent, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE) : $rawContent;
    }

    // Sanitize content for users without unfiltered_html capability to prevent Stored XSS
    if (!current_user_can('unfiltered_html')) {
      $decodedArray = json_decode($json, true);
      if (is_array($decodedArray) && isset($decodedArray['blocks'])) {
        if (function_exists('grinds_sanitize_post_content_array')) {
          $decodedArray = grinds_sanitize_post_content_array($decodedArray);
        }
        $json = json_encode($decodedArray, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_IGNORE);
      } else {
        if (function_exists('grinds_sanitize_post_content')) {
          $json = grinds_sanitize_post_content($json);
        }
      }
    }

    $content = Routing::convertToDbUrl($json);

    // Generate slug
    $baseSlug = 'tpl-' . bin2hex(random_bytes(8));
    $slug = function_exists('grinds_get_unique_slug')
      ? grinds_get_unique_slug($pdo, 'posts', $baseSlug)
      : $baseSlug;

    // Insert template
    $now = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare("INSERT INTO posts (title, slug, content, type, status, created_at, updated_at) VALUES (?, ?, ?, 'template', 'private', ?, ?)");
    $stmt->execute([$title, $slug, $content, $now, $now]);

    json_response(['success' => true, 'id' => $pdo->lastInsertId()]);
  }
  // Reject unsupported methods
  else {
    json_response(['success' => false, 'error' => 'Method Not Allowed'], 405);
  }
} catch (Exception $e) {
  json_response(['success' => false, 'error' => $e->getMessage()], 500);
}
