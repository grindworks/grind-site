<?php

if (!defined('GRINDS_APP')) exit;

/**
 * tag.php
 * Display tag archive.
 */
?>
<!-- Render header. -->
<div class="mb-12 pb-4 border-slate-200 border-b">
  <span class="block mb-2 font-bold text-slate-400 text-xs uppercase tracking-widest"><?= theme_t('tag_archive') ?></span>
  <h1 class="flex items-center gap-2 font-heading font-black text-slate-900 text-3xl md:text-4xl">
    <span class="text-brand-500">#</span><?= h($pageData['tag']['name']) ?>
  </h1>
</div>

<!-- Display posts. -->
<?php if (empty($pageData['posts'])): ?>
  <div class="py-12 text-center">
    <p class="text-slate-500 text-lg"><?= theme_t('no_posts_for_tag') ?></p>
    <a href="<?= h(resolve_url('/')) ?>"
      class="inline-block bg-slate-900 hover:bg-slate-800 mt-6 px-6 py-2.5 rounded-full font-bold text-white transition-colors">
      <?= theme_t('back_to_home') ?>
    </a>
  </div>
<?php else: ?>
  <div class="gap-8 grid grid-cols-1 md:grid-cols-2">
    <?php foreach ($pageData['posts'] as $post): ?>
      <a href="<?= h(resolve_url($post['slug'])) ?>"
        class="group block bg-white hover:shadow-lg border border-slate-200 hover:border-brand-300 rounded-xl overflow-hidden transition-all duration-300">
        <div class="p-6">
          <div class="flex items-center mb-3 font-bold text-slate-400 text-xs">
            <time><?= the_date($post['created_at']) ?></time>
            <span class="mx-2">•</span>
            <span class="text-brand-600 uppercase"><?= h($post['category_name'] ?? 'Blog') ?></span>
          </div>
          <h3 class="mb-2 font-bold text-slate-900 group-hover:text-brand-600 text-lg line-clamp-2 transition-colors">
            <?= h($post['title']) ?>
          </h3>
          <p class="text-slate-500 text-sm line-clamp-2">
            <?= (!empty($post['description'])) ? h($post['description']) : h(get_excerpt($post['content'], 80)) ?>
          </p>
        </div>
      </a>
    <?php endforeach; ?>
  </div>

  <div class="mt-12">
    <?php if (isset($pageData['paginator'])) echo $pageData['paginator']->renderFrontend(); ?>
  </div>
<?php endif; ?>
