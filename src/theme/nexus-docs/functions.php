<?php
if (!defined('GRINDS_APP')) exit;

/**
 * Get category and post tree for docs
 */
function nexus_get_sidebar_tree()
{
    $pdo = App::db();
    if (!$pdo) return [];

    $cats = $pdo->query("SELECT id, name, slug FROM categories ORDER BY sort_order ASC")->fetchAll(PDO::FETCH_ASSOC);
    $posts = $pdo->query("SELECT id, title, slug, category_id FROM posts WHERE status='published' AND type='post' AND deleted_at IS NULL ORDER BY published_at DESC")->fetchAll(PDO::FETCH_ASSOC);

    $tree = [];
    foreach ($cats as $cat) {
        $catPosts = array_filter($posts, fn($p) => $p['category_id'] == $cat['id']);
        if (!empty($catPosts)) {
            $tree[] = [
                'category' => $cat,
                'posts' => array_values($catPosts)
            ];
        }
    }
    return $tree;
}
