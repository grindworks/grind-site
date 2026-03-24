<?php

/**
 * mobile_menu.php
 * Renders the mobile navigation menu.
 */
if (!defined('GRINDS_APP')) exit;
?>
<div class="flex-1 space-y-6 p-4 overflow-y-auto">
    <a href="<?= h(resolve_url('/')) ?>" target="_blank" class="group flex justify-between items-center bg-theme-bg hover:shadow-theme p-4 border border-theme-border hover:border-theme-primary/50 rounded-theme transition-all">
        <div class="flex items-center gap-3">
            <div class="bg-theme-surface p-2 border border-theme-border rounded-full text-theme-primary group-hover:scale-110 transition-transform">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-home"></use>
                </svg>
            </div>
            <div>
                <p class="opacity-50 font-bold text-theme-text text-xs uppercase tracking-wider"><?= _t('view_site') ?></p>
                <p class="max-w-[180px] font-bold text-theme-text text-sm truncate"><?= h(get_option('site_name', SITE_NAME)) ?></p>
            </div>
        </div>
        <svg class="opacity-30 w-5 h-5 text-theme-text group-hover:text-theme-primary transition-all group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-chevron-right"></use>
        </svg>
    </a>

    <div>
        <h3 class="opacity-40 mb-2 px-1 font-bold text-[10px] text-theme-text uppercase tracking-widest"><?= _t('lbl_menu') ?></h3>
        <div class="space-y-1">
            <?php foreach ($admin_menu as $key => $item):
                $isActive = (isset($current_page) && $current_page === $key); ?>
                <a href="<?= $item['url'] ?>" class="flex items-center px-4 py-3 rounded-theme text-sm font-bold transition-all <?= $isActive ? 'bg-theme-primary text-theme-on-primary shadow-theme' : 'text-theme-text hover:bg-theme-bg' ?>">
                    <svg class="w-5 h-5 mr-3 <?= $isActive ? 'opacity-100' : 'opacity-60' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <use href="<?= h(grinds_asset_url('assets/img/sprite.svg') . '#' . $item['icon']) ?>"></use>
                    </svg>
                    <?= h($item['label']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="safe-area-bottom flex-shrink-0 space-y-4 bg-theme-surface/50 backdrop-blur-sm p-4 pb-8 border-theme-border border-t">
    <?php
    $logout_btn_class = 'flex items-center gap-1.5 bg-theme-danger/10 hover:bg-theme-danger px-4 py-2.5 rounded-theme font-bold text-theme-danger hover:text-white text-xs transition-colors';
    $logout_with_text = true;
    $licStatus = $licStatus ?? (function_exists('get_license_status') ? get_license_status() : 'unregistered');
    require __DIR__ . '/sidebar_footer.php';
    ?>
</div>
