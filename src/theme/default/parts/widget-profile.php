<?php

/**
 * widget-profile.php
 * Render profile widget.
 */
if (!defined('GRINDS_APP')) exit; ?>
<div class="bg-white shadow-sm mb-8 p-6 border border-gray-200 rounded-lg widget-profile">
    <?php if (!empty($title)): ?>
        <h3 class="flex items-center mb-4 pb-2 border-gray-100 border-b font-bold text-lg">
            <span class="bg-grinds-red mr-2 rounded-full w-1 h-4"></span><?= h($title) ?>
        </h3>
    <?php endif; ?>
    <div class="flex items-center mb-4">
        <div class="flex justify-center items-center bg-gray-100 mr-3 rounded-full w-12 h-12 overflow-hidden shrink-0">
            <?php if (!empty($settings['image'])): ?>
                <?= get_image_html($settings['image'], ['alt' => h($settings['name'] ?? 'Profile'), 'class' => 'w-full h-full object-cover', 'loading' => 'lazy']) ?>
            <?php else: ?>
                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <use href="<?= resolve_url('assets/img/sprite.svg') ?>#outline-user-circle"></use>
                </svg>
            <?php endif; ?>
        </div>
        <div>
            <p class="font-bold text-sm"><?= h($settings['name'] ?? '') ?></p>
        </div>
    </div>
    <?php if (!empty($content)): ?>
        <div class="text-gray-600 text-sm leading-relaxed"><?= nl2br(h($content)) ?></div>
    <?php endif; ?>
</div>
