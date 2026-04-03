<?php

if (!defined('GRINDS_APP')) exit;

/**
 * page.php
 * Render page layout.
 */
?>

<!-- Render page container -->
<div class="bg-white rounded-lg shadow-lg overflow-hidden mb-10 border border-gray-100">
  <header class="p-6 md:p-10 pb-0 text-center">
    <!-- Breadcrumbs -->
    <div class="mb-6 flex justify-center">
      <?= get_breadcrumb_html([
        'wrapper_class' => 'flex flex-wrap text-sm text-gray-500',
        'item_class'    => 'flex items-center',
        'link_class'    => 'hover:text-grinds-red transition-colors',
        'separator'     => '<svg class="w-3 h-3 mx-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><use href="' . resolve_url('assets/img/sprite.svg') . '#outline-chevron-right"></use></svg>',
        'active_class'  => 'text-gray-800 font-bold'
      ]) ?>
    </div>
    <?php
    $heroSettings = $pageData['post']['hero_settings_decoded'] ?? [];
    $hasHero = !empty($pageData['post']['hero_image']);
    $hasHeroTitle = $hasHero && !empty($heroSettings['title']);
    ?>

    <?php if (!$hasHeroTitle): ?>
      <!-- Render page title -->
      <h1 class="text-3xl md:text-4xl font-bold leading-tight mb-6 text-grinds-dark">
        <?= h($pageData['post']['title']) ?>
      </h1>
    <?php endif; ?>

    <!-- Render thumbnail if exists and no hero image -->
    <?php if ($pageData['post']['thumbnail'] && !$hasHero): ?>
      <div class="mb-8">
        <?= get_image_html($pageData['post']['thumbnail'], ['alt' => h($pageData['post']['title']), 'class' => 'w-full h-auto max-h-[500px] object-cover rounded-lg shadow-sm', 'loading' => 'eager', 'fetchpriority' => 'high']) ?>
      </div>
    <?php endif; ?>
  </header>

  <div class="px-6 md:px-10 pb-10">
    <!-- Render page content -->
    <div id="page-content" class="text-gray-800 leading-relaxed mx-auto">
      <?php if (!empty($pageData['post']['show_toc'])): ?>
        <?php get_template_part('parts/toc'); ?>
      <?php endif; ?>
      <?= render_content($pageData['post']['content_decoded'] ?? $pageData['post']['content']) ?>
    </div>
  </div>

  <footer class="px-6 md:px-10 py-6 bg-gray-50 border-t border-gray-100">
    <?php
    $post = $pageData['post'] ?? [];
    if ((!isset($post['show_share_buttons']) || !empty($post['show_share_buttons'])) && function_exists('default_the_share_buttons')) {
      $shareUrl = resolve_url($post['slug']);
      $shareTitle = $post['title'];
      default_the_share_buttons($shareUrl, $shareTitle);
    }
    ?>
  </footer>

</div>
