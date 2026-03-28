<?php

/**
 * widget-tags.php
 * Render tags widget.
 */
if (!defined('GRINDS_APP')) exit;

$pdo = App::db();
if (!$pdo) {
    return;
}
$tags = $pdo->query("SELECT t.* FROM tags t WHERE EXISTS (SELECT 1 FROM post_tags pt JOIN posts p ON pt.post_id = p.id WHERE pt.tag_id = t.id AND p.status='published' AND (p.type='post' OR p.type IS NULL) AND p.deleted_at IS NULL) ORDER BY t.id DESC LIMIT 20")->fetchAll();
?>
<div class="bg-white shadow-sm mb-8 p-6 border border-gray-200 rounded-lg widget-tags">
    <?php if (!empty($title)): ?>
        <h3 class="flex items-center mb-4 pb-2 border-gray-100 border-b font-bold text-lg">
            <span class="bg-grinds-red mr-2 rounded-full w-1 h-4"></span><?= h($title) ?>
        </h3>
    <?php endif; ?>
    <div class="flex flex-wrap gap-2">
        <?php foreach ($tags as $t): ?>
            <a href="<?= h(resolve_url('tag/' . $t['slug'])) ?>" class="bg-gray-100 hover:bg-grinds-red px-2 py-1 rounded text-gray-600 hover:text-white text-xs transition">#<?= h($t['name']) ?></a>
        <?php endforeach; ?>
    </div>
</div>
