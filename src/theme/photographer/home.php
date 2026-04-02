<?php

if (!defined('GRINDS_APP')) exit;

/**
 * home.php
 * Display homepage/portfolio.
 */
?>
<div class="gap-1 md:gap-4 lg:gap-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
  <?php if (empty($pageData['posts'])): ?>
    <p class="col-span-full py-20 text-gray-400 text-center"><?= theme_t('No posts found.') ?></p>
  <?php else: ?>
    <?php foreach ($pageData['posts'] as $post): ?>
      <a href="<?= site_url($post['slug']) ?>" class="group block relative bg-gray-100 aspect-[4/5] overflow-hidden">
        <?php if ($post['thumbnail']): ?>
          <?= get_image_html(resolve_url($post['thumbnail']), ['class' => 'w-full h-full object-cover transition duration-700 ease-out group-hover:scale-105 group-hover:opacity-90', 'alt' => h($post['title'])]) ?>
        <?php else: ?>
          <div class="flex justify-center items-center bg-photo-gray w-full h-full font-serif text-gray-300 text-2xl">
            <?= theme_t('No Image') ?>
          </div>
        <?php endif; ?>

        <!-- Display overlay. -->
        <div class="absolute inset-0 flex flex-col justify-center items-center bg-black/40 opacity-0 group-hover:opacity-100 p-6 text-white text-center transition duration-300">
          <span class="opacity-80 mb-2 text-xs uppercase tracking-widest">
            <?= h($post['category_name'] ?? theme_t('Uncategorized')) ?>
          </span>
          <h2 class="font-serif text-2xl md:text-3xl italic">
            <?= h($post['title']) ?>
          </h2>
        </div>
      </a>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<!-- Render pagination. -->
<div class="flex justify-center mt-16 photographer-pagination">
  <?php if (isset($pageData['paginator'])) {
    $paginator = $pageData['paginator'];
    get_template_part('parts/pagination');
  } ?>
</div>
