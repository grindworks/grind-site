<?php

if (!defined('GRINDS_APP'))
  exit;

/**
 * archive.php
 * Display posts filtered by tag.
 */
$isSearch = (isset($pageType) && $pageType === 'search');
?>
<div class="mb-10">
  <?php if (isset($pageType) && $pageType === 'category'): ?>
    <div class="relative bg-grinds-dark shadow-md mb-8 p-8 rounded-lg overflow-hidden text-white">
      <div class="z-10 relative">
        <span class="font-bold text-grinds-red text-xs uppercase tracking-widest"><?= theme_t('Category') ?></span>
        <h1 class="mt-2 font-bold text-3xl">
          <?= h($pageData['category']['name']) ?>
        </h1>
        <p class="mt-2 text-gray-400 text-sm">
          <?php if (isset($pageData['paginator']))
            echo $pageData['paginator']->getNumPages() . ' Pages'; ?>
        </p>
      </div>
      <div class="top-1/2 right-4 absolute opacity-30 text-gray-800 -translate-y-1/2 transform">
        <svg class="w-24 h-24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
        </svg>
      </div>
    </div>
  <?php
  else: ?>
    <div class="flex justify-between items-center bg-white shadow-sm mb-8 p-6 border-grinds-red border-l-4 rounded-lg">
      <div>
        <span class="font-bold text-gray-500 text-xs uppercase tracking-wider">
          <?= $isSearch ? theme_t('Search Results') : theme_t('Tag Archive') ?>
        </span>
        <h1 class="mt-1 font-bold text-gray-900 text-2xl">
          <?php if ($isSearch): ?>
            <?= theme_t('Search: %s', h($_GET['q'] ?? '')) ?>
          <?php
          elseif (isset($pageData['tag'])): ?>
            # <?= h($pageData['tag']['name']) ?>
          <?php
          else: ?>
            <?= theme_t('Archive') ?>
          <?php
          endif; ?>
        </h1>
      </div>
      <div class="text-gray-300 text-3xl">
        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
        </svg>
      </div>
    </div>
  <?php
  endif; ?>

  <?php if (empty($pageData['posts'])): ?>
    <div class="flex flex-col justify-center items-center py-16 px-4 bg-theme-bg/30 border-2 border-theme-border border-dashed rounded-theme text-center">
      <div class="flex justify-center items-center w-16 h-16 mb-4 rounded-full bg-theme-surface shadow-sm text-theme-text opacity-50">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <use href="<?= resolve_url('assets/img/sprite.svg') ?>#outline-document-text"></use>
        </svg>
      </div>
      <p class="text-sm text-theme-text opacity-60"><?= theme_t('No posts found for this tag.') ?></p>
    </div>
  <?php
  else: ?>
    <div class="gap-8 grid grid-cols-1 md:grid-cols-2">
      <?php foreach ($pageData['posts'] as $post): ?>
        <?php get_template_part('parts/card-post', null, ['post' => $post]); ?>
      <?php
      endforeach; ?>
    </div>
    <?php the_pagination(); ?>
  <?php
  endif; ?>

  <div class="mt-8 text-center">
    <a href="<?= h(resolve_url('/')) ?>" class="inline-block hover:bg-gray-100 px-6 py-2.5 border border-gray-300 rounded-full text-gray-700 text-sm transition">
      <?= theme_t('Back to Home') ?>
    </a>
  </div>
</div>
