<?php

/**
 * widget-profile.php
 * Render profile widget.
 */
if (!defined('GRINDS_APP')) exit;

$title = $title ?? '';
$settings = $settings ?? [];
$content = $content ?? '';
?>
<div class="bg-white shadow-lg mb-12 p-8 border border-slate-100 rounded-3xl widget widget-profile">
    <?php if (!empty($title)): ?>
        <h3 class="flex items-center mb-6 font-bold text-slate-800 text-xl widget-title">
            <span class="block bg-brand-600 mr-3 rounded-full w-2 h-6"></span>
            <?= h($title) ?>
        </h3>
    <?php endif; ?>

    <div class="flex items-center gap-4 mb-6">
        <?php if (!empty($settings['image'])): ?>
            <div class="shrink-0">
                <?= get_image_html($settings['image'], ['class' => 'w-20 h-20 rounded-full object-cover border-4 border-slate-50 shadow-sm', 'alt' => h($settings['name'] ?? ''), 'loading' => 'lazy']) ?>
            </div>
        <?php endif; ?>
        <div>
            <div class="font-bold text-slate-900 text-lg"><?= h($settings['name'] ?? '') ?></div>
        </div>
    </div>

    <?php if (!empty($content)): ?>
        <div class="text-slate-600 text-sm leading-relaxed">
            <?= render_content($content) ?>
        </div>
    <?php endif; ?>
</div>
