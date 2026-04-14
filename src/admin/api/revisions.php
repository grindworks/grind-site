<?php

/**
 * revisions.php
 *
 * API endpoint to fetch post revision history.
 */
require_once __DIR__ . '/api_bootstrap.php';

// Check permissions
if (!current_user_can('manage_posts')) {
    json_response(['success' => false, 'error' => 'Forbidden'], 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['success' => false, 'error' => 'Method Not Allowed'], 405);
}

try {
    $postId = isset($_GET['post_id']) ? (int)$_GET['post_id'] : 0;
    $revId = isset($_GET['rev_id']) ? (int)$_GET['rev_id'] : 0;

    if ($postId <= 0) {
        throw new Exception("Invalid Post ID");
    }

    if ($revId > 0) {
        // Fetch specific revision content to restore
        $stmt = $pdo->prepare("SELECT title, content, hero_settings, meta_data FROM post_revisions WHERE id = ? AND post_id = ?");
        $stmt->execute([$revId, $postId]);
        $rev = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$rev) throw new Exception("Revision not found.");

        // Decode contents for the editor
        if (!empty($rev['content'])) {
            $viewContent = grinds_url_to_view($rev['content']);
            $decoded = json_decode($viewContent, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $rev['content'] = $decoded;
            } else {
                // Fallback for legacy raw HTML content to prevent blank editor on restore
                $rev['content'] = [
                    'blocks' => [
                        [
                            'id' => uniqid(),
                            'type' => 'html',
                            'data' => ['code' => $viewContent],
                            'collapsed' => false
                        ]
                    ]
                ];
            }
        }
        if (!empty($rev['hero_settings'])) {
            $rev['hero_settings'] = json_decode(grinds_url_to_view($rev['hero_settings']), true);
        }
        if (!empty($rev['meta_data'])) {
            $rev['meta_data'] = json_decode(grinds_url_to_view($rev['meta_data']), true);
        }

        json_response(['success' => true, 'data' => $rev]);
    } else {
        // Fetch list of revisions
        $stmt = $pdo->prepare("SELECT id, created_at FROM post_revisions WHERE post_id = ? ORDER BY id DESC");
        $stmt->execute([$postId]);
        $list = $stmt->fetchAll(PDO::FETCH_ASSOC);

        json_response(['success' => true, 'list' => $list]);
    }
} catch (Exception $e) {
    json_response(['success' => false, 'error' => $e->getMessage()], 500);
}
