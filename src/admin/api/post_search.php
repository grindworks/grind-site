<?php

/**
 * post_search.php
 *
 * Search for posts based on a query string.
 */
require_once __DIR__ . '/api_bootstrap.php';

// Check permissions
if (!current_user_can('manage_posts')) {
    json_response(['success' => false, 'error' => 'Forbidden'], 403);
}

$q = $_GET['q'] ?? '';
if (empty($q)) {
    json_response([]);
}

try {
    $repo = new PostRepository($pdo);
    $filters = ['status' => 'all'];

    if (str_starts_with(strtolower($q), 'id:') && is_numeric(substr($q, 3))) {
        $filters['ids'] = [(int)substr($q, 3)];
    } else {
        $filters['search'] = $q;
    }

    $posts = $repo->fetch($filters, 20, 0, 'p.published_at DESC', 'p.id, p.title, p.slug, p.type, p.published_at, p.updated_at', false);

    $results = [];
    foreach ($posts as $row) {
        $dateStr = $row['published_at'] ?: $row['updated_at'];
        $date = $dateStr ? date('Y/m/d', strtotime($dateStr)) : '';

        $results[] = [
            'title' => $row['title'],
            'url' => resolve_url($row['slug']),
            'type' => $row['type'],
            'id' => (int)$row['id'],
            'date' => $date
        ];
    }
    json_response($results);
} catch (Exception $e) {
    json_response([], 500);
}
