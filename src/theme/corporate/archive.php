<?php

if (!defined('GRINDS_APP'))
  exit;

/**
 * archive.php
 * Display tag or search archive.
 */
$isSearch = (isset($pageType) && $pageType === 'search');
?>
<div class="mb-10">
  <!-- Render archive header -->
  <?php if (isset($pageType) && $pageType === 'category'): ?>
    <div class="mb-8 pb-4 border-slate-200 border-b">
      <span class="font-bold text-gray-500 text-xs uppercase tracking-wider">
        <?= theme_t('category') ?>
      </span>
      <h1 class="flex items-center gap-3 mt-2 font-bold text-slate-900 text-3xl">
        <span class="block bg-corp-accent rounded-full w-1.5 h-8"></span>
        <?= h($pageData['category']['name']) ?>
      </h1>
    </div>
  <?php
  elseif ($isSearch): ?>
    <div class="mb-8 pb-4 border-slate-200 border-b">
      <span class="font-bold text-gray-500 text-xs uppercase tracking-wider">
        <?= theme_t('search_results') ?>
      </span>
      <h1 class="flex items-center gap-3 mt-2 font-bold text-slate-900 text-3xl">
        <span class="block bg-corp-accent rounded-full w-1.5 h-8"></span>
        "<?= h($_GET['q'] ?? '') ?>"
      </h1>
    </div>
  <?php else: // Tag archive
  ?>
    <div class="mb-8 pb-4 border-slate-200 border-b">
      <span class="font-bold text-gray-500 text-xs uppercase tracking-wider">
        <?= theme_t('tag_archive') ?>
      </span>
      <h1 class="flex items-center gap-2 mt-2 font-bold text-slate-900 text-3xl">
        <span class="text-corp-accent text-4xl">#</span><?= h($pageData['tag']['name'] ?? '') ?>
      </h1>
    </div>
  <?php
  endif; ?>

  <!-- Display post list or empty state -->
  <?php if (empty($pageData['posts'])): ?>
    <div class="bg-white shadow p-10 border border-gray-100 rounded text-center">
      <p class="text-gray-600">
        <?= (isset($pageType) && $pageType === 'category') ? theme_t('no_posts_in_category') : theme_t('no_posts_for_tag') ?>
      </p>
    </div>
  <?php
  else: ?>
    <div class="gap-8 grid grid-cols-1 md:grid-cols-2">
      <?php foreach ($pageData['posts'] as $index => $post): ?>
        <article
          class="flex flex-col bg-white shadow-md hover:shadow-xl border border-gray-100 rounded-lg h-full overflow-hidden transition-shadow duration-300">
          <a href="<?= h(resolve_url($post['slug'])) ?>"
            class="group block relative bg-gray-200 h-48 overflow-hidden shrink-0" aria-label="<?= h($post['title']) ?>">
            <?php if ($post['thumbnail']): ?>
              <?php
              $imgAttrs = ['class' => 'w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-500', 'alt' => h($post['title'])];
              if ($index < 2) {
                $imgAttrs['loading'] = 'eager';
                $imgAttrs['fetchpriority'] = 'high';
              } else {
                $imgAttrs['loading'] = 'lazy';
              }
              ?>
              <?= get_image_html($post['thumbnail'], $imgAttrs) ?>
            <?php
            else: ?>
              <div class="flex justify-center items-center h-full text-gray-400">
                <?= theme_t('no_image') ?>
              </div>
            <?php
            endif; ?>
          </a>

          <div class="flex flex-col flex-grow p-6">
            <div class="flex items-center mb-2 text-gray-600 text-xs">
              <time datetime="<?= h($post['created_at']) ?>">
                <?= date('Y.m.d', strtotime($post['created_at'])) ?>
              </time>
            </div>

            <h2 class="mb-3 font-bold text-lg leading-snug">
              <a href="<?= h(resolve_url($post['slug'])) ?>" class="text-gray-900 hover:text-corp-accent transition-colors">
                <?= h($post['title']) ?>
              </a>
            </h2>

            <p class="flex-grow mb-4 text-gray-700 text-sm line-clamp-3 leading-relaxed">
              <?= (!empty($post['description'])) ? h($post['description']) : get_excerpt($post['content'], 80) ?>
            </p>

            <div class="mt-auto pt-4 border-gray-100 border-t">
              <a href="<?= h(resolve_url($post['slug'])) ?>"
                class="flex items-center font-bold text-corp-accent hover:text-black text-sm transition"
                aria-label="<?= h(sprintf(theme_t('read_more_aria'), $post['title'])) ?>">
                <?= theme_t('read_more') ?>
              </a>
            </div>
          </div>
        </article>
      <?php
      endforeach; ?>
    </div>
    <!-- Render pagination -->
    <?php if (isset($pageData['paginator']))
      echo $pageData['paginator']->renderFrontend(); ?>
  <?php
  endif; ?>

  <div class="mt-8 text-center">
    <a href="<?= h(resolve_url('/')) ?>"
      class="inline-block hover:bg-gray-100 px-6 py-2.5 border border-gray-300 rounded-full text-gray-700 text-sm transition">
      <?= theme_t('back_to_home') ?>
    </a>
  </div>
</div>
