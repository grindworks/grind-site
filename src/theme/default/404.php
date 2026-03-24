<?php

/**
 * 404.php
 * Display custom 404 error page.
 */
if (!defined('GRINDS_APP')) exit;
?>
<div class="flex flex-col justify-center items-center px-4 py-20 min-h-[60vh] text-center">
  <div class="relative mb-8">
    <div class="font-bold text-gray-100 text-9xl select-none">404</div>
    <div class="absolute inset-0 flex justify-center items-center">
      <svg class="opacity-80 w-20 h-20 text-grinds-red" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
      </svg>
    </div>
  </div>

  <h1 class="mb-4 font-bold text-gray-800 text-2xl md:text-3xl"><?= theme_t('Page Not Found') ?></h1>
  <p class="mx-auto mb-10 max-w-md text-gray-500 leading-relaxed">
    <?= theme_t('The page you are looking for does not exist.') ?>
  </p>

  <?php
  $searchAction = (defined('GRINDS_IS_SSG') && GRINDS_IS_SSG) ? 'search.html' : resolve_url('/');
  ?>
  <form action="<?= h($searchAction) ?>" method="get" class="flex gap-2 mx-auto mb-8 w-full max-w-md grinds-search-form">
    <input type="text" name="q" placeholder="<?= h(theme_t('Search...')) ?>" class="flex-1 px-4 py-2 border border-gray-300 focus:border-grinds-red rounded-full focus:outline-none transition-colors">
    <button type="submit" class="bg-grinds-red hover:bg-red-700 shadow-sm px-6 py-2.5 rounded-full font-bold text-white transition-colors"><?= theme_t('Search') ?></button>
  </form>

  <div class="flex sm:flex-row flex-col gap-4">
    <a href="<?= h(resolve_url('/')) ?>" id="dynamic-back-btn" class="inline-flex justify-center items-center bg-grinds-dark hover:bg-gray-800 shadow-lg px-8 py-3 rounded-full font-bold text-white text-sm transition hover:-translate-y-1 transform">
      <svg class="mr-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
      </svg>
      <span><?= theme_t('Back to Home') ?></span>
    </a>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      var ref = document.referrer;
      if (ref && ref.indexOf(window.location.host) !== -1) {
        var btn = document.getElementById('dynamic-back-btn');
        if (btn) {
          btn.querySelector('span').innerText = <?= json_encode(theme_t('Go Back')) ?>;
          btn.querySelector('svg').innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>';
          btn.addEventListener('click', function(e) {
            e.preventDefault();
            window.history.back();
          });
        }
      }
    });
  </script>
</div>
