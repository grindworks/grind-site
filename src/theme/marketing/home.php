<?php

if (!defined('GRINDS_APP')) exit;

/**
 * home.php
 * Display homepage or search.
 */
?>
<!-- Include hero -->
<?php get_template_part('parts/hero'); ?>

<div class="mx-auto px-6 py-20 container">
  <?php if (isset($pageType) && $pageType === 'search'): ?>
    <h2 class="mb-8 font-bold text-3xl"><?= theme_t('search_results') ?></h2>
    <?php include __DIR__ . '/archive.php'; ?>
  <?php else: ?>

    <div class="mb-16 text-center">
      <span class="font-bold text-brand-600 text-sm uppercase tracking-widest"><?= theme_t('updates') ?></span>
      <h2 class="mt-2 font-heading font-black text-slate-900 text-3xl md:text-4xl"><?= theme_t('latest_posts') ?></h2>
    </div>

    <?php if (!empty($pageData['posts'])): ?>
      <div class="gap-8 grid grid-cols-1 md:grid-cols-3">
        <?php foreach ($pageData['posts'] as $index => $post): ?>
          <a href="<?= h(resolve_url($post['slug'])) ?>" class="group block bg-white shadow-lg hover:shadow-xl border border-slate-200 rounded-2xl overflow-hidden transition-all duration-300">
            <div class="relative bg-slate-100 aspect-video overflow-hidden">
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
                <div class="flex justify-center items-center bg-slate-50 w-full h-full text-slate-300">
                  <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                </div>
              <?php endif; ?>
              <div class="top-4 left-4 absolute bg-white/90 backdrop-blur px-3 py-1 rounded-full font-bold text-slate-900 text-xs">
                <?= the_date($post['created_at']) ?>
              </div>
            </div>
            <div class="p-6">
              <div class="mb-2 font-bold text-brand-600 text-xs uppercase tracking-wide">
                <?= h($post['category_name'] ?? 'News') ?>
              </div>
              <h3 class="mb-3 font-bold text-slate-900 group-hover:text-brand-600 text-xl line-clamp-2 transition-colors">
                <?= h($post['title']) ?>
              </h3>
              <p class="text-slate-500 text-sm line-clamp-2">
                <?= (!empty($post['description'])) ? h($post['description']) : h(get_excerpt($post['content'], 80)) ?>
              </p>
            </div>
          </a>
        <?php endforeach; ?>
      </div>

      <div class="mt-12 text-center">
        <a href="<?= h(resolve_url('category/news')) ?>" class="inline-flex items-center font-bold text-brand-600 hover:text-brand-700">
          <?= theme_t('view_all_news') ?> <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
          </svg>
        </a>
      </div>
    <?php endif; ?>

  <?php endif; ?>
</div>

<!-- CTA section. -->
<section class="bg-brand-900 py-20 text-white text-center">
  <div class="mx-auto px-6 container">
    <h2 class="mb-6 font-heading font-black text-3xl md:text-4xl"><?= theme_t('cta_title') ?></h2>
    <p class="mx-auto mb-10 max-w-2xl text-brand-100 text-lg"><?= theme_t('cta_desc') ?></p>
    <a href="<?= h(resolve_url('contact')) ?>" class="inline-block bg-white hover:bg-brand-50 shadow-lg px-10 py-4 rounded-full font-bold text-brand-900 text-lg transition-colors">
      <?= theme_t('cta_btn') ?>
    </a>
  </div>
</section>
