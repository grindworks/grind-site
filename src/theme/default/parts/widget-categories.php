<?php

/**
 * widget-categories.php
 * Render categories widget.
 */
if (!defined('GRINDS_APP')) exit;

$pdo = App::db();
if (!$pdo) {
    return;
}
$cats = $pdo->query("SELECT c.* FROM categories c WHERE EXISTS (SELECT 1 FROM posts p WHERE p.category_id = c.id AND p.status='published' AND (p.type='post' OR p.type IS NULL) AND p.deleted_at IS NULL) ORDER BY c.sort_order ASC")->fetchAll();
?>
<div class="bg-white shadow-sm mb-8 p-6 border border-gray-200 rounded-lg widget-categories">
    <?php if (!empty($title)): ?>
        <h3 class="flex items-center mb-4 pb-2 border-gray-100 border-b font-bold text-lg">
            <span class="bg-grinds-red mr-2 rounded-full w-1 h-4"></span><?= h($title) ?>
        </h3>
    <?php endif; ?>
    <ul class="space-y-2">
        <?php foreach ($cats as $c): ?>
            <li><a href="<?= h(resolve_url('category/' . $c['slug'])) ?>" class="group flex justify-between items-center hover:text-grinds-red text-sm transition">
                    <span class="transition-transform group-hover:translate-x-1"><?= h($c['name']) ?></span>
                    <span class="text-gray-500 text-xs">&rsaquo;</span>
                </a></li>
        <?php endforeach; ?>
    </ul>
</div>
