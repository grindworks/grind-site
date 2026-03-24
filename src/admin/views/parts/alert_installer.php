<?php if (!empty($installer_exists)): ?>
    <div
        class="flex justify-center items-center gap-2 bg-theme-danger/10 shadow-theme border-b border-theme-danger/20 px-4 py-3 text-theme-danger text-sm font-bold shrink-0 z-50 relative">
        <span class="relative flex w-3 h-3">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-theme-danger opacity-75"></span>
            <span class="relative inline-flex rounded-full h-3 w-3 bg-theme-danger"></span>
        </span>
        <span>
            <?= _t('st_installer_present') ?>
        </span>
        <a href="settings.php?tab=system" class="ml-2 underline hover:opacity-80 transition-opacity">
            <?= _t('btn_delete_now') ?>
        </a>
    </div>
<?php endif; ?>
