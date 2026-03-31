<?php

if (!defined('GRINDS_APP')) exit;

/**
 * home.php
 * Render home page and search results.
 */
?>

<div class="mb-10">
  <?php if (isset($pageType) && $pageType === 'search'): ?>
    <!-- Search results header -->
    <h2 class="mb-6 pl-4 border-grinds-red border-l-4 font-bold text-3xl">
      <?= theme_t('Search results for: %s', h($_GET['q'] ?? '')) ?>
    </h2>

    <?php if (!empty($pageData['posts'])): ?>
      <!-- Post count -->
      <p class="mb-8 font-bold text-gray-700">
        <?= theme_t('%s posts found.', h($pageData['total_count'] ?? count($pageData['posts']))) ?>
      </p>
    <?php else: ?>
      <!-- No search results -->
      <div class="flex flex-col justify-center items-center py-16 px-4 bg-theme-bg/30 border-2 border-theme-border border-dashed rounded-theme text-center mb-10">
        <div class="flex justify-center items-center w-16 h-16 mb-4 rounded-full bg-theme-surface shadow-sm text-theme-text opacity-50">
          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= resolve_url('assets/img/sprite.svg') ?>#outline-magnifying-glass"></use>
          </svg>
        </div>
        <h3 class="mb-1 font-bold text-theme-text text-lg"><?= theme_t('No posts found.') ?></h3>
        <p class="mb-6 text-sm text-theme-text opacity-60"><?= theme_t('Please try again with different keywords.') ?></p>
        <a href="<?= h(resolve_url('/')) ?>" class="inline-block hover:bg-theme-surface px-6 py-2.5 border border-theme-border rounded-full text-theme-text text-sm transition"><?= theme_t('Back to Home') ?></a>
      </div>
    <?php endif; ?>

  <?php else: ?>
    <!-- Latest posts header -->
    <h2 class="mb-6 pl-4 border-grinds-red border-l-4 font-bold text-3xl">
      <?= theme_t('Latest Posts') ?>
    </h2>
  <?php endif; ?>

  <?php if (!empty($pageData['posts'])): ?>
    <!-- Posts grid -->
    <div class="gap-8 grid grid-cols-1 md:grid-cols-2">
      <?php foreach ($pageData['posts'] as $index => $post): ?>
        <?php get_template_part('parts/card-post', null, ['post' => $post, 'index' => $index]); ?>
      <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php the_pagination(); ?>

  <?php elseif (!isset($pageType) || $pageType !== 'search'): ?>
    <!-- No posts view -->
    <div class="flex flex-col justify-center items-center py-16 px-4 bg-theme-bg/30 border-2 border-theme-border border-dashed rounded-theme text-center">
      <div class="flex justify-center items-center w-16 h-16 mb-4 rounded-full bg-theme-surface shadow-sm text-theme-text opacity-50">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <use href="<?= resolve_url('assets/img/sprite.svg') ?>#outline-document-text"></use>
        </svg>
      </div>
      <h3 class="mb-1 font-bold text-theme-text text-lg"><?= theme_t('No posts yet') ?></h3>
      <p class="mb-6 text-sm text-theme-text opacity-60"><?= theme_t('Please wait for content updates.') ?></p>

      <?php if (isset($_SESSION['admin_logged_in'])): ?>
        <!-- Admin create post link -->
        <a href="<?= h(resolve_url('admin/posts.php?action=new')) ?>" class="inline-flex items-center bg-theme-primary hover:opacity-90 shadow-sm px-6 py-2.5 rounded-full font-bold text-white text-sm hover:-translate-y-0.5 transition transform">
          <svg class="mr-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= resolve_url('assets/img/sprite.svg') ?>#outline-plus"></use>
          </svg>
          <?= theme_t('Write First Post') ?>
        </a>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>
