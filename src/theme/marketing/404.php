<?php

if (!defined('GRINDS_APP')) exit;

/**
 * 404.php
 * Display 404 Not Found error.
 */
?>
<div class="flex justify-center items-center py-20 text-center">
  <div class="w-full max-w-lg">
    <div class="mb-4 font-heading font-black text-brand-100 text-9xl select-none">404</div>
    <h1 class="mb-4 font-bold text-slate-900 text-3xl md:text-4xl"><?= theme_t('page_not_found') ?></h1>
    <p class="mb-10 text-slate-500 leading-relaxed">
      <?= theme_t('page_not_found_desc') ?>
    </p>

    <!-- Render search. -->
    <form action="<?= h(resolve_url('/')) ?>" method="get" class="relative mb-10 grinds-search-form">
      <input type="text" name="q" placeholder="<?= h(theme_t('search_placeholder')) ?>" class="shadow-sm py-4 pr-14 pl-6 border border-slate-300 focus:border-transparent rounded-full focus:outline-none focus:ring-2 focus:ring-brand-500 w-full">
      <button type="submit" class="top-2 right-2 absolute bg-brand-600 hover:bg-brand-700 p-2 rounded-full text-white transition" aria-label="<?= h(theme_t('search')) ?>">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
      </button>
    </form>

    <a href="<?= h(resolve_url('/')) ?>" class="inline-flex justify-center items-center bg-slate-900 hover:bg-slate-800 shadow-lg px-8 py-3 rounded-full font-bold text-white text-base transition">
      &larr; <?= theme_t('back_to_home') ?>
    </a>
  </div>
</div>
