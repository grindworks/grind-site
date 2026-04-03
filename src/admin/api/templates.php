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
    $rawContent = $input['content'] ?? ['blocks' => []];
    $contentString = is_array($rawContent) ? json_encode($rawContent, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE) : (string)$rawContent;

    $postData = [
      'title' => $title,
      'content' => $contentString,
      'content_is_base64' => !empty($input['content_is_base64']) ? '1' : '0',
      'type' => 'template',
      'status' => 'private',
      'slug' => 'tpl-' . bin2hex(random_bytes(8))
    ];

    $result = grinds_save_post($pdo, $postData, [], 'new');

    json_response(['success' => true, 'id' => $result['id']]);
  }
  // Reject unsupported methods
  else {
    json_response(['success' => false, 'error' => 'Method Not Allowed'], 405);
  }
} catch (Exception $e) {
  json_response(['success' => false, 'error' => $e->getMessage()], 500);
}
