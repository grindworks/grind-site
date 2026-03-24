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
  <div class="relative bg-brand-500 border-2 border-slate-900 shadow-sharp mb-12 p-10 overflow-hidden text-slate-900">
    <div class="z-10 relative">
      <span
        class="font-heading font-bold text-slate-900 bg-white border-2 border-slate-900 px-3 py-1 text-xs uppercase tracking-widest shadow-[2px_2px_0_0_rgba(15,23,42,1)] inline-block mb-4">
        <?= theme_t('Category')?>
      </span>
      <h1 class="mt-2 font-heading font-black text-5xl md:text-6xl tracking-tight text-slate-900 drop-shadow-sm">
        <?= h($pageData['category']['name'])?>
      </h1>
      <p class="mt-4 text-slate-800 font-bold text-sm tracking-wider uppercase">
        <?php if (isset($pageData['paginator']))
    echo h($pageData['paginator']->getNumPages() . ' Pages'); ?>
      </p>
    </div>
    <div class="top-1/2 right-4 absolute opacity-20 text-slate-900 -translate-y-1/2 transform mix-blend-overlay">
      <svg class="w-24 h-24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
          d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
      </svg>
    </div>
  </div>
  <?php
else: ?>
  <div class="flex justify-between items-center bg-brand-50 shadow-sharp mb-12 p-8 border-2 border-slate-900 relative">
    <div>
      <span class="font-heading font-bold text-slate-500 text-xs uppercase tracking-widest mb-2 block">
        <?= $isSearch ? theme_t('Search Results') : theme_t('Tag Archive')?>
      </span>
      <h1 class="font-heading font-extrabold text-slate-900 text-3xl md:text-4xl tracking-tight">
        <?php if ($isSearch): ?>
        <?= theme_t('Search: %s', h($_GET['q'] ?? ''))?>
        <?php
  elseif (isset($pageData['tag'])): ?>
        #
        <?= h($pageData['tag']['name'])?>
        <?php
  else: ?>
        <?= theme_t('Archive')?>
        <?php
  endif; ?>
      </h1>
    </div>
    <div class="text-slate-900 text-3xl opacity-20 hidden sm:block">
      <svg class="w-16 h-16 stroke-[1.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
          d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z">
        </path>
      </svg>
    </div>
  </div>
  <?php
endif; ?>

  <?php if (empty($pageData['posts'])): ?>
  <div class="bg-white shadow-sharp p-12 border-2 border-slate-900 text-center relative overflow-hidden">
    <p class="text-slate-900 font-bold text-xl mb-4 font-heading">
      <?= theme_t('No posts found for this tag.')?>
    </p>
    <div class="text-6xl">🤔</div>
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

  <div class="mt-12 text-center">
    <a href="<?= h(resolve_url('/'))?>" class="neo-btn neo-btn-secondary px-8 py-3">
      <?= theme_t('Back to Home')?>
    </a>
  </div>
</div>
