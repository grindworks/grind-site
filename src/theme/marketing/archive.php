<?php

if (!defined('GRINDS_APP'))
  exit;

/**
 * archive.php
 * Display general archive.
 */
?>
<!-- Render header. -->
<?php if (isset($pageType) && $pageType === 'category'): ?>
  <div
    class="relative bg-gradient-to-r from-brand-600 to-brand-800 mb-12 py-16 rounded-3xl overflow-hidden text-white">
    <!-- Decorative background. -->
    <div class="top-0 right-0 absolute bg-white opacity-5 blur-3xl -mt-20 -mr-20 rounded-full w-64 h-64"></div>
    <div class="z-10 relative px-8 text-center">
      <span class="block mb-2 font-bold text-brand-200 text-sm uppercase tracking-widest">
        <?= theme_t('category') ?>
      </span>
      <h1 class="mb-3 font-heading text-white text-3xl md:text-5xl">
        <?= h($pageData['category']['name']) ?>
      </h1>
      <p class="text-brand-100 text-md">
        <?= theme_t('explore_updates') ?>
      </p>
    </div>
  </div>
<?php else: ?>
  <div class="mb-12 text-center">
    <span class="block mb-2 font-bold text-brand-600 text-sm uppercase tracking-widest">
      <?= isset($_GET['q']) ? theme_t('search_results') : theme_t('archive') ?>
    </span>
    <h1 class="font-heading font-black text-slate-900 text-3xl md:text-4xl">
      <?php if (isset($_GET['q'])): ?>
        "<?= h($_GET['q']) ?>"
      <?php else: ?>
        <?= theme_t('tag_archive') ?>
      <?php endif; ?>
    </h1>
  </div>
<?php endif; ?>

<!-- Display posts. -->
<?php if (empty($pageData['posts'])): ?>
  <div class="py-12 text-center">
    <p class="text-slate-500">
      <?= (isset($pageType) && $pageType === 'category') ? theme_t('no_posts_in_category') : theme_t('no_posts_found') ?>
    </p>
    <a href="<?= h(resolve_url('/')) ?>" class="inline-block mt-4 font-bold text-brand-600 hover:underline">
      <?= theme_t('back_to_home') ?>
    </a>
  </div>
<?php else: ?>
  <div class="gap-8 grid grid-cols-1 md:grid-cols-2">
    <?php foreach ($pageData['posts'] as $index => $post): ?>
      <a href="<?= h(resolve_url($post['slug'])) ?>"
        class="group flex flex-col bg-white shadow-lg hover:shadow-xl border border-slate-200 hover:border-brand-300 rounded-2xl overflow-hidden transition-all duration-300">

        <div class="relative bg-slate-100 aspect-video overflow-hidden shrink-0">
          <?php if ($post['thumbnail']): ?>
            <?php
            $imgAttrs = ['class' => 'w-full h-full object-cover group-hover:scale-105 transition-transform duration-500', 'alt' => h($post['title'])];
            if ($index < 2) {
              $imgAttrs['loading'] = 'eager';
              $imgAttrs['fetchpriority'] = 'high';
            } else {
              $imgAttrs['loading'] = 'lazy';
            }
            ?>
            <?= get_image_html($post['thumbnail'], $imgAttrs) ?>
          <?php else: ?>
            <div class="flex justify-center items-center w-full h-full text-slate-300">
              <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
            </div>
          <?php endif; ?>
          <div class="top-4 left-4 absolute bg-white/90 backdrop-blur px-3 py-1 rounded-full font-bold text-slate-900 text-xs">
            <?= the_date($post['created_at']) ?>
          </div>
        </div>

        <div class="flex flex-col flex-1 p-6">
          <div class="mb-2 font-bold text-brand-600 text-xs uppercase tracking-wide">
            <?= h($post['category_name'] ?? 'Blog') ?>
          </div>
          <h2 class="mb-3 font-bold text-slate-900 group-hover:text-brand-600 text-xl line-clamp-2 transition-colors">
            <?= h($post['title']) ?>
          </h2>
          <p class="flex-grow text-slate-500 text-sm line-clamp-3">
            <?php
            if (function_exists('marketing_get_highlighted_excerpt')) {
              echo marketing_get_highlighted_excerpt($post, 120);
            } else {
              echo (!empty($post['description'])) ? h($post['description']) : h(get_excerpt($post['content'], 120));
            }
            ?>
          </p>
        </div>
      </a>
    <?php endforeach; ?>
  </div>

  <!-- Render pagination. -->
  <div class="mt-12">
    <?php if (isset($pageData['paginator']))
      echo $pageData['paginator']->renderFrontend(); ?>
  </div>
<?php endif; ?>
