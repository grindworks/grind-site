<?php

/**
 * 404.php
 * Display custom 404 error page.
 */
if (!defined('GRINDS_APP'))
  exit;
?>
<div
  class="flex flex-col justify-center items-center px-6 py-24 min-h-[60vh] text-center bg-brand-50 border-2 border-slate-900 shadow-sharp my-10 relative overflow-hidden">
  <div class="relative mb-8 z-10">
    <div class="font-heading font-black text-slate-900 text-9xl select-none tracking-tighter">404</div>
    <div class="absolute inset-0 flex justify-center items-center">
      <svg class="w-16 h-16 text-brand-600 drop-shadow-md stroke-[3]" fill="none" stroke="currentColor"
        viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round"
          d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
      </svg>
    </div>
  </div>

  <h1 class="mb-4 font-heading font-extrabold text-slate-900 text-3xl md:text-5xl uppercase tracking-tight z-10">
    <?= theme_t('Page Not Found')?>
  </h1>
  <p class="mx-auto mb-10 max-w-md text-slate-700 leading-relaxed font-medium z-10">
    <?= theme_t('The page you are looking for does not exist.')?>
  </p>

  <form action="<?= h(resolve_url('/'))?>" method="get"
    class="flex flex-col sm:flex-row gap-3 mx-auto mb-10 w-full max-w-md z-10">
    <input type="text" name="q" placeholder="<?= h(theme_t('Search...'))?>"
      class="flex-1 px-5 py-3 border-2 border-slate-900 shadow-[2px_2px_0_0_rgba(15,23,42,1)] focus:shadow-[4px_4px_0_0_rgba(15,23,42,1)] rounded-none focus:outline-none transition-shadow font-bold text-slate-900 placeholder-slate-500">
    <button type="submit" class="neo-btn neo-btn-primary py-3">
      <?= theme_t('Search')?>
    </button>
  </form>

  <div class="flex sm:flex-row flex-col gap-4 z-10">
    <a href="<?= h(resolve_url('/'))?>" class="neo-btn neo-btn-dark py-3">
      <svg class="mr-2 w-5 h-5 stroke-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round"
          d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
        </path>
      </svg>
      <?= theme_t('Back to Home')?>
    </a>
  </div>
</div>
