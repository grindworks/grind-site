<?php

if (!defined('GRINDS_APP'))
  exit;

/**
 * page.php
 * Render page layout.
 */
?>

<!-- Render page container -->
<div class="bg-white border-2 border-slate-900 shadow-sharp overflow-hidden mb-16">
  <header class="p-8 md:p-12 pb-0 text-center border-b-2 border-slate-900 mb-10">
    <!-- Breadcrumbs -->
    <div class="mb-8 flex justify-center">
      <?= get_breadcrumb_html([
  'wrapper_class' => 'flex flex-wrap text-sm text-slate-500 font-bold uppercase tracking-widest',
  'item_class' => 'flex items-center',
  'link_class' => 'hover:text-brand-600 transition-colors',
  'separator' => '<span class="mx-3 text-slate-300">/</span>',
  'active_class' => 'text-slate-900 font-bold'
])?>
    </div>
    <?php
$heroSettings = $pageData['post']['hero_settings_decoded'] ?? [];
$hasHero = !empty($pageData['post']['hero_image']);
$hasHeroTitle = $hasHero && !empty($heroSettings['title']);
?>

    <?php if (!$hasHeroTitle): ?>
    <!-- Render page title -->
    <h1
      class="text-4xl md:text-5xl lg:text-7xl font-heading font-extrabold leading-[1.1] tracking-tight mb-10 text-slate-900">
      <?= h($pageData['post']['title'])?>
    </h1>
    <?php
endif; ?>

    <!-- Render thumbnail if exists and no hero image -->
    <?php if ($pageData['post']['thumbnail'] && !$hasHero): ?>
    <div class="mb-8">
      <?= get_image_html(resolve_url($pageData['post']['thumbnail']), ['alt' => h($pageData['post']['title']), 'class' => 'w-full h-auto max-h-[500px] object-cover rounded-lg shadow-sm', 'loading' => 'eager', 'fetchpriority' => 'high'])?>
    </div>
    <?php
endif; ?>
  </header>

  <div class="px-6 md:px-10 pb-10">
    <!-- Render page content -->
    <div id="page-content" class="text-slate-800 leading-relaxed mx-auto max-w-4xl text-lg">
      <?php if (!empty($pageData['post']['show_toc'])): ?>
      <?php
  $contentData = $pageData['post']['content_decoded'] ?? [];
  $headers = get_post_toc($contentData);
?>
      <?php if (!empty($headers)): ?>
      <details id="toc" open class="mb-12 p-8 border-2 border-slate-900 bg-brand-50 shadow-sharp">
        <summary
          class="font-heading font-extrabold cursor-pointer text-slate-900 mb-4 text-xl outline-none tracking-tight">
          <?= h($pageData['post']['toc_title'] ?: theme_t('Contents'))?>
        </summary>
        <nav role="navigation" aria-label="<?= theme_t('Contents')?>">
          <ul class="space-y-3 list-none m-0 p-0 border-t-2 border-slate-900 pt-4">
            <?php foreach ($headers as $h): ?>
            <?php
      $indentClass = 'ml-0 font-bold';
      if ($h['level'] === 3)
        $indentClass = 'ml-6 font-medium text-base';
      elseif ($h['level'] === 4)
        $indentClass = 'ml-12 font-medium text-sm text-slate-600';
      elseif ($h['level'] >= 5)
        $indentClass = 'ml-16 font-normal text-sm text-slate-500';
?>
            <li class="<?= $indentClass?>">
              <a href="#<?= $h['id']?>"
                class="hover:underline hover:text-brand-600 text-slate-900 block py-0.5 transition-colors">
                <?= h($h['text'])?>
              </a>
            </li>
            <?php
    endforeach; ?>
          </ul>
        </nav>
      </details>
      <?php
  endif; ?>
      <?php
endif; ?>
      <?= render_content($pageData['post']['content_decoded'] ?? $pageData['post']['content'])?>
    </div>
  </div>

  <footer class="px-6 md:px-10 py-6 bg-gray-50 border-t border-gray-100">
    <?php
$post = $pageData['post'] ?? [];
if ((!isset($post['show_share_buttons']) || !empty($post['show_share_buttons'])) && function_exists('neo_minimalist_the_share_buttons')) {
  $shareUrl = resolve_url($post['slug']);
  $shareTitle = $post['title'];
  neo_minimalist_the_share_buttons($shareUrl, $shareTitle);
}
?>
  </footer>

</div>
