<?php
if (!defined('GRINDS_APP')) exit;
$footerMenus = function_exists('get_nav_menus') ? get_nav_menus('footer') : [];
?>
<footer class="bg-theme-primary text-white py-12 mt-auto">
    <div class="mx-auto px-4 container max-w-6xl">
        <div class="flex flex-col md:flex-row justify-between items-center gap-6">
            <div class="font-serif font-bold text-2xl tracking-widest">
                <?= h(get_option('site_name', theme_t('site_name_fallback'))) ?>
            </div>
            <nav class="flex flex-wrap justify-center gap-6 text-sm">
                <?php foreach ($footerMenus as $m): ?>
                    <a href="<?= h($m['url']) ?>" class="hover:text-blue-200 transition-colors">
                        <?= h($m['label']) ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>
        <div class="text-center text-sm text-blue-200 mt-12 pt-8 border-t border-blue-800">
            &copy; <?= date('Y') ?> <?= h(get_option('site_name')) ?>. All Rights Reserved.
        </div>
    </div>
</footer>
