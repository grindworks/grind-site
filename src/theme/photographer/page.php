<?php

if (!defined('GRINDS_APP')) exit;

/**
 * page.php
 * Display static page.
 */
$post = $pageData['post'];

$heroSettings = $post['hero_settings_decoded'] ?? json_decode($post['hero_settings'] ?? '{}', true);
$hasHero = !empty($post['hero_image']);
$hasHeroTitle = $hasHero && !empty($heroSettings['title']);
?>
<article class="mx-auto max-w-6xl">

  <!-- Include hero. -->
  <?php get_template_part('parts/hero'); ?>

  <div class="mb-8 text-center">
    <?= get_breadcrumb_html([
      'wrapper_class' => 'inline-flex flex-wrap text-xs uppercase tracking-widest text-gray-400',
      'link_class'    => 'hover:text-black transition-colors',
      'active_class'  => 'text-black border-b border-black pb-0.5',
      'separator'     => '<span class="mx-3 text-gray-300">/</span>'
    ]) ?>
  </div>

  <header class="mb-12 md:mb-16 text-center">
    <?php if (!$hasHeroTitle): ?>
      <h1 class="mb-6 font-serif font-medium text-3xl md:text-4xl">
        <?= h($post['title']) ?>
      </h1>
    <?php endif; ?>

    <?php if ($post['thumbnail'] && !$hasHero): ?>
      <?= get_image_html(resolve_url($post['thumbnail']), ['class' => 'w-full h-[400px] object-cover mb-8 grayscale hover:grayscale-0 transition duration-700', 'alt' => h($post['title']), 'loading' => 'eager', 'fetchpriority' => 'high']) ?>
    <?php endif; ?>
  </header>

  <div id="page-content" class="mx-auto max-w-5xl font-light text-gray-800 prose prose-lg">
    <?php if (!empty($post['show_toc'])): ?>
      <?php
      $contentData = $post['content_decoded'] ?? [];
      $headers = get_post_toc($contentData);
      ?>
      <?php if (!empty($headers)): ?>
        <div id="toc" class="mb-16 p-8 border-t border-b border-gray-100 bg-gray-50/50">
          <h3 class="font-serif text-xl text-center mb-6 italic text-gray-600"><?= h($post['toc_title'] ?: theme_t('Contents')) ?></h3>
          <ul class="space-y-3 list-none m-0 p-0 max-w-md mx-auto">
            <?php foreach ($headers as $h): ?>
              <li class="<?= ($h['level'] === 3) ? 'text-center text-sm' : 'text-center' ?>">
                <a href="#<?= $h['id'] ?>" class="text-gray-500 hover:text-black transition-colors no-underline border-b border-transparent hover:border-gray-300 pb-0.5">
                  <?= h($h['text']) ?>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>
    <?php endif; ?>
    <?= render_content($post['content_decoded'] ?? $post['content']) ?>
  </div>
</article>
