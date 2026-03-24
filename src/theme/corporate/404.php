<?php

/**
 * 404.php
 * Display 404 Not Found error.
 */
if (!defined('GRINDS_APP')) exit;
?>
<div class="flex flex-col justify-center items-center px-4 py-20 min-h-[60vh] text-center">
  <div class="relative mb-8">
    <div class="font-bold text-gray-100 text-9xl select-none">404</div>
    <div class="absolute inset-0 flex justify-center items-center">
      <svg class="opacity-80 w-20 h-20 text-corp-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
      </svg>
    </div>
  </div>

  <h1 class="mb-4 font-bold text-gray-800 text-2xl md:text-3xl"><?= theme_t('page_not_found') ?></h1>
  <p class="mx-auto mb-10 max-w-md text-gray-500 leading-relaxed">
    <?= theme_t('page_not_found_desc') ?>
  </p>

  <div class="flex sm:flex-row flex-col gap-4">
    <a href="<?= h(resolve_url('/')) ?>" class="inline-flex justify-center items-center bg-corp-main hover:bg-gray-800 shadow-lg px-8 py-3 rounded-full font-bold text-white text-sm transition">
      <svg class="mr-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
      </svg>
      <?= theme_t('back_to_home') ?>
    </a>
  </div>
</div>
