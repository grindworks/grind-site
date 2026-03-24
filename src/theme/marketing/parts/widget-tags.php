<?php

/**
 * widget-tags.php
 * Render tag cloud widget.
 */
if (!defined('GRINDS_APP')) exit;

// Fetch tags.
$pdo = App::db();
$tags = [];
if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT DISTINCT t.* FROM tags t JOIN post_tags pt ON t.id = pt.tag_id JOIN posts p ON pt.post_id = p.id WHERE p.status='published' AND (p.type='post' OR p.type IS NULL) AND p.deleted_at IS NULL ORDER BY t.id DESC LIMIT 20");
        $tags = $stmt ? $stmt->fetchAll() : [];
    } catch (Exception $e) {
    }
}
?>
<div class="bg-white shadow-lg mb-12 p-8 border border-slate-100 rounded-3xl widget widget-tags">
    <?php if (!empty($title)): ?>
        <h3 class="flex items-center mb-6 font-bold text-slate-800 text-xl widget-title">
            <span class="block bg-brand-600 mr-3 rounded-full w-2 h-6"></span>
            <?= h($title) ?>
        </h3>
    <?php endif; ?>
    <div class="flex flex-wrap gap-2">
        <?php foreach ($tags as $t): ?>
            <a href="<?= h(resolve_url('tag/' . $t['slug'])) ?>" class="bg-slate-100 hover:bg-brand-600 px-3 py-1.5 rounded-full font-bold text-slate-600 hover:text-white text-xs transition-colors">
                #<?= h($t['name']) ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>
