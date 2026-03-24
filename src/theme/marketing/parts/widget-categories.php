<?php

/**
 * widget-categories.php
 * Render category widget.
 */
if (!defined('GRINDS_APP')) exit;

// Fetch categories.
$pdo = App::db();
$cats = [];
if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT DISTINCT c.* FROM categories c JOIN posts p ON c.id = p.category_id WHERE p.status='published' AND (p.type='post' OR p.type IS NULL) AND p.deleted_at IS NULL ORDER BY c.sort_order ASC");
        $cats = $stmt ? $stmt->fetchAll() : [];
    } catch (Exception $e) {
    }
}
?>
<div class="bg-white shadow-lg mb-12 p-8 border border-slate-100 rounded-3xl widget widget-categories">
    <?php if (!empty($title)): ?>
        <h3 class="flex items-center mb-6 font-bold text-slate-800 text-xl widget-title">
            <span class="block bg-brand-600 mr-3 rounded-full w-2 h-6"></span>
            <?= h($title) ?>
        </h3>
    <?php endif; ?>
    <ul class="space-y-3">
        <?php foreach ($cats as $c): ?>
            <li>
                <a href="<?= h(resolve_url('category/' . $c['slug'])) ?>" class="group flex justify-between items-center font-medium text-slate-600 hover:text-brand-600 transition-colors">
                    <span class="transition-transform group-hover:translate-x-1"><?= h($c['name']) ?></span>
                    <span class="text-slate-300 text-xs">&rsaquo;</span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
