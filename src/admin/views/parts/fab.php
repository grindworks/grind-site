<?php

/**
 * fab.php
 * Renders the mobile floating action button.
 */
if (!defined('GRINDS_APP')) exit; ?>
<?php if (isset($href)): ?>
    <a href="<?= h($href) ?>"
        class="lg:hidden right-6 bottom-6 z-40 fixed flex justify-center items-center bg-theme-primary shadow-theme rounded-full w-14 h-14 text-theme-on-primary transition-transform hover:scale-110 active:scale-95" aria-label="<?= h(_t('add')) ?>">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-plus"></use>
        </svg>
    </a>
<?php else: ?>
    <button @click="<?= $clickAction ?? 'mobileFormOpen = true' ?>"
        class="lg:hidden right-6 bottom-6 z-40 fixed flex justify-center items-center bg-theme-primary shadow-theme rounded-full w-14 h-14 text-theme-on-primary transition-transform hover:scale-110 active:scale-95" aria-label="<?= h(_t('add')) ?>">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-plus"></use>
        </svg>
    </button>
<?php endif; ?>
