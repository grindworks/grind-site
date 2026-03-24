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
        <?php
        $contentData = $pageData['post']['content_decoded'] ?? [];
        $headers = get_post_toc($contentData);
        ?>
        <?php if (!empty($headers)): ?>
          <details id="toc" open class="mb-10 p-5 border border-gray-200 rounded-lg bg-gray-50 shadow-sm">
            <summary class="font-bold cursor-pointer text-gray-800 mb-3 text-lg outline-none">
              <?= h($pageData['post']['toc_title'] ?: theme_t('Contents')) ?>
            </summary>
            <nav role="navigation" aria-label="<?= theme_t('Contents') ?>">
              <ul class="space-y-2 list-none m-0 p-0">
                <?php foreach ($headers as $h): ?>
                  <?php
                  $indentClass = 'ml-0 font-bold';
                  if ($h['level'] === 3) $indentClass = 'ml-4 font-normal';
                  elseif ($h['level'] === 4) $indentClass = 'ml-8 font-normal text-sm';
                  elseif ($h['level'] >= 5) $indentClass = 'ml-12 font-normal text-xs';
                  ?>
                  <li class="<?= $indentClass ?>">
                    <a href="#<?= $h['id'] ?>" class="hover:underline hover:text-grinds-red text-gray-700 block py-0.5 transition-colors">
                      <?= h($h['text']) ?>
                    </a>
                  </li>
                <?php endforeach; ?>
              </ul>
            </nav>
          </details>
        <?php endif; ?>
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
