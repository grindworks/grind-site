<?php

/**
 * widget-search.php
 * Render search widget.
 */
if (!defined('GRINDS_APP')) exit;
$searchAction = (defined('GRINDS_IS_SSG') && GRINDS_IS_SSG) ? 'search.html' : resolve_url('/');
?>
<div class="bg-white shadow-sm mb-8 p-6 border border-gray-200 rounded-lg widget-search">
    <?php if (!empty($title)): ?>
        <h3 class="flex items-center mb-4 pb-2 border-gray-100 border-b font-bold text-lg">
            <span class="bg-grinds-red mr-2 rounded-full w-1 h-4"></span><?= h($title) ?>
        </h3>
    <?php endif; ?>
    <form action="<?= $searchAction ?>" method="get" class="flex grinds-search-form">
        <input type="text" name="q" value="<?= h($_GET['q'] ?? '') ?>" placeholder="<?= h(theme_t('search')) ?>" aria-label="<?= h(theme_t('search')) ?>" class="px-3 py-2 border border-gray-300 focus:border-grinds-red rounded-l focus:outline-none w-full text-sm placeholder-gray-500">
        <button type="submit" aria-label="<?= h(theme_t('search')) ?>" class="bg-grinds-red hover:bg-blue-700 px-3 py-2 rounded-r text-white transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
        </button>
    </form>
</div>
