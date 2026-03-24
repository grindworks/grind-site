<?php

/**
 * widget-recent.php
 * Render recent posts widget.
 */
if (!defined('GRINDS_APP')) exit;

$pdo = App::db();
$posts = [];
if ($pdo) {
    try {
        $limit = (int)($settings['limit'] ?? 5);
        $stmt = $pdo->prepare("SELECT * FROM posts WHERE status='published' AND type='post' AND (published_at <= ? OR published_at IS NULL) AND deleted_at IS NULL ORDER BY published_at DESC LIMIT ?");
        $stmt->bindValue(1, date('Y-m-d H:i:s'));
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $posts = $stmt->fetchAll();
    } catch (Exception $e) {
    }
}
?>
<div class="bg-white shadow-lg mb-12 p-8 border border-slate-100 rounded-3xl widget widget-recent">
    <?php if (!empty($title)): ?>
        <h3 class="flex items-center mb-6 font-bold text-slate-800 text-xl widget-title">
            <span class="block bg-brand-600 mr-3 rounded-full w-2 h-6"></span>
            <?= h($title) ?>
        </h3>
    <?php endif; ?>

    <ul class="space-y-6">
        <?php foreach ($posts as $p): ?>
            <li>
                <a href="<?= h(resolve_url($p['slug'])) ?>" class="group flex items-start gap-4">
                    <?php if (!empty($p['thumbnail'])): ?>
                        <div class="bg-slate-100 shadow-md rounded-lg w-16 h-16 overflow-hidden shrink-0">
                            <img src="<?= h(resolve_url($p['thumbnail'])) ?>" alt="<?= h($p['title']) ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300" loading="lazy">
                        </div>
                    <?php endif; ?>
                    <div>
                        <h4 class="mb-1 font-bold text-slate-700 group-hover:text-brand-600 text-sm line-clamp-2 leading-snug transition-colors">
                            <?= h($p['title']) ?>
                        </h4>
                        <div class="text-slate-400 text-xs">
                            <?= the_date($p['created_at']) ?>
                        </div>
                    </div>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
