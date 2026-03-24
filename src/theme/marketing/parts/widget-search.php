<?php

/**
 * widget-search.php
 * Render search widget.
 */
if (!defined('GRINDS_APP')) exit;
?>
<div class="bg-white shadow-lg mb-12 p-8 border border-slate-100 rounded-3xl widget widget-search">
    <?php if (!empty($title)): ?>
        <h3 class="flex items-center mb-6 font-bold text-slate-800 text-xl widget-title">
            <span class="block bg-brand-600 mr-3 rounded-full w-2 h-6"></span>
            <?= h($title) ?>
        </h3>
    <?php endif; ?>

    <!-- Render search. -->
    <form action="<?= h(resolve_url('/')) ?>" method="get" class="relative flex w-full grinds-search-form">
        <input type="text" name="q" placeholder="<?= h(theme_t('search_placeholder', 'Search...')) ?>"
            class="bg-slate-50 py-3 pr-12 pl-4 border border-slate-200 focus:border-brand-500 rounded-xl outline-none focus:ring-2 focus:ring-brand-200 w-full text-slate-700 transition-all placeholder-slate-400">
        <button type="submit" class="top-1/2 right-2 absolute bg-brand-600 hover:bg-brand-700 shadow-md hover:shadow-lg p-2 rounded-lg text-white transition-colors -translate-y-1/2 transform" aria-label="<?= h(theme_t('search')) ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
        </button>
    </form>
</div>
