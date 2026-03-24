<?php

if (!defined('GRINDS_APP'))
  exit;

/**
 * home.php
 * Render home page and search results.
 */
?>

<div class="mb-10">
  <?php if (isset($pageType) && $pageType === 'search'): ?>
  <!-- Search results header -->
  <h2
    class="mb-10 pb-4 border-b-2 border-slate-900 font-heading font-extrabold text-3xl md:text-4xl tracking-tight text-slate-900">
    <?= theme_t('Search results for: %s', h($_GET['q'] ?? ''))?>
  </h2>

  <?php if (!empty($pageData['posts'])): ?>
  <!-- Post count -->
  <p class="mb-8 font-bold text-gray-700">
    <?= theme_t('%s posts found.', h($pageData['total_count'] ?? count($pageData['posts'])))?>
  </p>
  <?php
  else: ?>
  <!-- No search results -->
  <div class="bg-white shadow mb-10 p-10 border border-gray-100 rounded text-center">
    <div class="mb-4 text-4xl">🤔</div>
    <h3 class="mb-2 font-bold text-gray-800 text-xl">
      <?= theme_t('No posts found.')?>
    </h3>
    <p class="mb-6 text-gray-600">
      <?= theme_t('Please try again with different keywords.')?>
    </p>
    <a href="<?= h(resolve_url('/'))?>"
      class="inline-block hover:bg-gray-100 px-6 py-2.5 border border-gray-300 rounded-full text-gray-700 text-sm transition">
      <?= theme_t('Back to Home')?>
    </a>
  </div>
  <?php
  endif; ?>

  <?php
else: ?>
  <!-- Latest posts header -->
  <h2
    class="mb-10 pb-4 border-b-2 border-slate-900 font-heading font-extrabold text-3xl md:text-4xl tracking-tight text-slate-900">
    <?= theme_t('Latest Posts')?>
  </h2>
  <?php
endif; ?>

  <?php if (!empty($pageData['posts'])): ?>
  <!-- Posts grid -->
  <div class="gap-8 grid grid-cols-1 md:grid-cols-2">
    <?php foreach ($pageData['posts'] as $post): ?>
    <?php get_template_part('parts/card-post', null, ['post' => $post]); ?>
    <?php
  endforeach; ?>
  </div>

  <!-- Pagination -->
  <?php the_pagination(); ?>

  <?php
elseif (!isset($pageType) || $pageType !== 'search'): ?>
  <!-- No posts view -->
  <div class="bg-white shadow-sm p-12 border border-gray-100 rounded-lg text-center">
    <div class="inline-flex justify-center items-center bg-gray-100 mb-4 rounded-full w-16 h-16 text-gray-400">
      <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z">
        </path>
      </svg>
    </div>
    <h3 class="mb-2 font-bold text-gray-700 text-lg">
      <?= theme_t('No posts yet')?>
    </h3>
    <p class="mb-6 text-gray-600 text-sm">
      <?= theme_t('Please wait for content updates.')?>
    </p>

    <?php if (isset($_SESSION['admin_logged_in'])): ?>
    <!-- Admin create post link -->
    <a href="<?= h(resolve_url('admin/posts.php?action=new'))?>"
      class="neo-btn neo-btn-primary mx-auto mt-4 px-8 text-sm">
      <svg class="mr-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
      </svg>
      <?= theme_t('Write First Post')?>
    </a>
    <?php
  endif; ?>
  </div>
  <?php
endif; ?>
</div>
