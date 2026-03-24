<?php

if (!defined('GRINDS_APP')) exit;

/**
 * single.php
 * Display single post.
 */
$post = $pageData['post'];
$date = the_date($post['published_at'] ?? $post['created_at']);

$heroSettings = $post['hero_settings_decoded'] ?? json_decode($post['hero_settings'] ?? '{}', true);
$hasHero = !empty($post['hero_image']);
$hasHeroTitle = $hasHero && !empty($heroSettings['title']);
?>

<article class="mx-auto max-w-6xl animate-in duration-700 fade-in">

  <!-- Include hero. -->
  <?php include __DIR__ . '/parts/hero.php'; ?>

  <div class="mb-8 text-center">
    <?= get_breadcrumb_html([
      'wrapper_class' => 'inline-flex flex-wrap text-xs uppercase tracking-widest text-gray-400',
      'link_class'    => 'hover:text-black transition-colors',
      'active_class'  => 'text-black border-b border-black pb-0.5',
      'separator'     => '<span class="mx-3 text-gray-300">/</span>'
    ]) ?>
  </div>

  <header class="mb-12 md:mb-20 text-center">
    <div class="mb-4 font-bold text-gray-400 text-xs uppercase tracking-widest">
      <?php if (!empty($post['category_name'])): ?>
        <?= h($post['category_name']) ?> &mdash;
      <?php endif; ?>
      <?= $date ?>
    </div>

    <?php if (!$hasHeroTitle): ?>
      <h1 class="mb-8 font-serif font-medium text-4xl md:text-5xl lg:text-6xl leading-tight">
        <?= h($post['title']) ?>
      </h1>
    <?php endif; ?>
  </header>

  <?php if ($post['thumbnail'] && !$hasHero): ?>
    <div class="shadow-sm -mx-6 md:mx-0 mb-16">
      <?= get_image_html(resolve_url($post['thumbnail']), ['class' => 'w-full h-auto', 'alt' => h($post['title']), 'loading' => 'eager', 'fetchpriority' => 'high']) ?>
    </div>
  <?php endif; ?>

  <div id="post-content" class="mx-auto max-w-5xl font-light text-gray-800 leading-8 prose prose-lg">
    <?php if (!empty($post['show_toc'])): ?>
      <?php
      $contentData = $post['content_decoded'] ?? [];
      $headers = get_post_toc($contentData);
      ?>
      <?php if (!empty($headers)): ?>
        <div id="toc" class="mb-16 p-8 border-t border-b border-gray-100 bg-gray-50/50">
          <h3 class="font-serif text-xl text-center mb-6 italic text-gray-600">
            <?= h($post['toc_title'] ?: theme_t('Contents')) ?>
          </h3>
          <ul class="space-y-3 list-none m-0 p-0 max-w-md mx-auto">
            <?php foreach ($headers as $h): ?>
              <?php
              $indentClass = 'text-center';
              if ($h['level'] === 3) $indentClass = 'text-center text-sm';
              ?>
              <li class="<?= $indentClass ?>">
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

  <div class="mt-20 pt-10 border-gray-100 border-t text-center">
    <?php
    if ((!isset($post['show_share_buttons']) || !empty($post['show_share_buttons'])) && function_exists('photographer_the_share_buttons')) {
      $shareUrl = resolve_url($post['slug']);
      $shareTitle = $post['title'];
      photographer_the_share_buttons($shareUrl, $shareTitle);
    }
    ?>

    <div class="mt-12">
      <a href="<?= site_url() ?>" class="inline-block pb-0.5 border-black hover:border-gray-500 border-b hover:text-gray-500 text-sm uppercase tracking-widest transition">
        <?= theme_t('Back to Home') ?>
      </a>
    </div>
  </div>
</article>
