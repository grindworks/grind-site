<?php

/**
 * widget-profile.php
 * Render profile widget.
 */
if (!defined('GRINDS_APP')) exit;

// Ensure variables.
$title = $title ?? '';
$settings = $settings ?? [];
$content = $content ?? '';
?>
<div class="bg-white shadow-sm mb-8 p-6 border border-slate-200 rounded-lg">
  <h3 class="flex items-center gap-2 mb-5 pb-3 border-slate-100 border-b font-bold text-slate-900 text-lg">
    <svg class="w-5 h-5 text-sky-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
    </svg>
    <?= h($title) ?>
  </h3>

  <div class="flex items-center gap-4 mb-4">
    <div class="flex justify-center items-center bg-slate-100 border border-slate-200 rounded-full w-16 h-16 overflow-hidden shrink-0">
      <?php if (!empty($settings['image'])): ?>
        <?= get_image_html($settings['image'], ['alt' => h($settings['name'] ?? theme_t('profile_image')), 'class' => 'w-full h-full object-cover']) ?>
      <?php else: ?>
        <span class="text-2xl">🏢</span>
      <?php endif; ?>
    </div>
    <div>
      <p class="font-bold text-slate-900 text-base"><?= h($settings['name'] ?? '') ?></p>
    </div>
  </div>

  <?php if (!empty($content)): ?>
    <div class="text-slate-600 text-sm leading-relaxed">
      <?= render_content($content) ?>
    </div>
  <?php endif; ?>
</div>
