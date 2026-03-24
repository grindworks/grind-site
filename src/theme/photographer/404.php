<?php

/**
 * 404.php
 * Display 404 error page.
 */
if (!defined('GRINDS_APP')) exit;
?>
<div class="flex flex-col justify-center items-center px-6 py-20 min-h-[60vh] text-center">
  <h1 class="mb-6 font-serif text-6xl md:text-8xl italic">404</h1>
  <p class="mb-12 font-serif text-gray-600 text-xl md:text-2xl">
    <?= theme_t('Page Not Found') ?>
  </p>
  <p class="mb-12 max-w-md font-sans text-gray-400 text-sm leading-relaxed">
    <?= theme_t('The page you are looking for does not exist.') ?>
  </p>

  <a href="<?= site_url() ?>" class="inline-block pb-0.5 border-black hover:border-gray-500 border-b hover:text-gray-500 text-sm uppercase tracking-widest transition">
    <?= theme_t('Back to Home') ?>
  </a>
</div>
