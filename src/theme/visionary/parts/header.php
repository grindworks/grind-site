<?php
if (!defined('GRINDS_APP')) exit;
$headerMenus = function_exists('get_nav_menus') ? get_nav_menus('header') : [];
?>
<header class="top-0 z-40 sticky bg-white shadow-md border-b-4 border-theme-primary transition-all">
    <div class="mx-auto px-4 container max-w-6xl">
        <div class="flex justify-between items-center h-20">

            <a href="<?= h(resolve_url('/')) ?>" class="flex items-center gap-3">
                <?php if ($logo = get_option('site_logo')): ?>
                    <img src="<?= h(resolve_url($logo)) ?>" alt="<?= h(get_option('site_name')) ?>" class="h-12 w-auto">
                <?php else: ?>
                    <span class="font-serif font-bold text-2xl md:text-3xl tracking-widest text-theme-primary">
                        <?= h(get_option('site_name', theme_t('site_name_fallback'))) ?>
                    </span>
                <?php endif; ?>
            </a>

            <nav class="hidden md:flex items-center space-x-6 font-bold text-sm tracking-wide">
                <?php foreach ($headerMenus as $m): ?>
                    <a href="<?= h($m['url']) ?>" class="text-theme-text hover:text-theme-primary transition-colors py-2">
                        <?= h($m['label']) ?>
                    </a>
                <?php endforeach; ?>

                <a href="<?= h(resolve_url('contact')) ?>" class="btn-accent ml-4 text-xs py-2 px-6">
                    <?= theme_t('join_supporters_club') ?>
                </a>
            </nav>
        </div>
    </div>
</header>
