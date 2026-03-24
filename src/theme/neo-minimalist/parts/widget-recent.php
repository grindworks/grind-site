<?php

/**
 * widget-recent.php
 * Render recent posts widget.
 */
if (!defined('GRINDS_APP')) exit;

$pdo = App::db();
if (!$pdo) {
    return;
}
$limit = (int)($settings['limit'] ?? 5);
$repo = new PostRepository($pdo);
$recents = $repo->fetch(
    ['status' => 'published', 'type' => 'post'],
    $limit,
    0,
    'p.published_at DESC',
    'p.title, p.slug, p.published_at, p.thumbnail'
);
?>
<div class="bg-white shadow-sm mb-8 p-6 border border-gray-200 rounded-lg widget-recent">
    <?php if (!empty($title)): ?>
        <h3 class="flex items-center mb-4 pb-2 border-gray-100 border-b font-bold text-lg">
            <span class="bg-grinds-red mr-2 rounded-full w-1 h-4"></span><?= h($title) ?>
        </h3>
    <?php endif; ?>
    <ul class="space-y-4">
        <?php foreach ($recents as $r): ?>
            <li class="flex gap-3">
                <?php if (!empty($r['thumbnail'])): ?>
                    <a href="<?= h(resolve_url($r['slug'])) ?>" class="bg-gray-100 rounded w-16 h-16 overflow-hidden shrink-0"><?= get_image_html($r['thumbnail'], ['alt' => h($r['title']), 'class' => 'hover:opacity-80 w-full h-full object-cover transition', 'loading' => 'lazy']) ?></a>
                <?php endif; ?>
                <div><a href="<?= h(resolve_url($r['slug'])) ?>" class="font-bold hover:text-grinds-red text-sm line-clamp-2 leading-snug transition"><?= h($r['title']) ?></a>
                    <?php if ($r['published_at']): ?>
                        <span class="block mt-1 text-gray-400 text-xs"><?= the_date($r['published_at']) ?></span>
                    <?php endif; ?>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
