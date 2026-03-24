<?php

/**
 * user_dropdown.php
 * Renders the user dropdown menu.
 */
if (!defined('GRINDS_APP'))
    exit;
?>
<div class="p-1">
    <a href="settings.php?tab=profile"
        class="flex items-center hover:bg-theme-bg px-4 py-2 text-theme-text hover:text-theme-primary text-sm transition-colors">
        <svg class="opacity-70 mr-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-user-circle"></use>
        </svg>
        <?= _t('st_profile_title') ?>
    </a>

    <form method="post" action="logout.php" class="block w-full">
        <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
        <button type="submit"
            class="flex items-center hover:bg-theme-danger/10 px-4 py-2 w-full text-theme-danger text-sm text-left transition-colors">
            <svg class="opacity-70 mr-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-right-on-rectangle"></use>
            </svg>
            <?= _t('logout') ?>
        </button>
    </form>
</div>
