<?php

/**
 * sidebar_footer.php
 * Renders the sidebar footer with license and user info.
 */
if (!defined('GRINDS_APP'))
    exit;

// Set configuration variables
$text_class = $text_class ?? 'skin-sidebar-text';
$border_class = $border_class ?? 'skin-sidebar-border';
$logout_btn_class = $logout_btn_class ?? 'hover:bg-theme-danger/10 opacity-60 hover:opacity-100 p-2 rounded-theme hover:text-theme-danger transition-colors skin-sidebar-text';
$logout_with_text = $logout_with_text ?? false;

// Set status dot color
if (!isset($statusDot) && isset($sysStatus)) {
    $statusDot = match ($sysStatus['status']) {
        'danger' => 'bg-theme-danger',
        'warning' => 'bg-theme-warning',
        default => 'bg-theme-success',
    };
}
?>

<?php if (current_user_can('manage_settings')): ?>
    <?php if (!in_array($licStatus, ['pro', 'agency'])):
        $licenseWidgetClass = 'bg-theme-info/10 border-theme-info/30 hover:bg-theme-info/20';
    ?>
        <a href="settings.php?tab=general"
            class="block p-3 rounded-theme border transition-colors group relative overflow-hidden <?= $licenseWidgetClass ?>">
            <div class="flex justify-between items-center mb-1">
                <span class="opacity-70 font-bold text-[10px] tracking-widest">
                    <?= _t('license') ?>
                </span>
                <span class="flex items-center gap-1 font-bold text-theme-info text-xs"><svg class="w-3 h-3" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-key"></use>
                    </svg>
                    <?= _t('trial') ?>
                </span>
            </div>
            <div class="opacity-80 text-[10px] leading-tight">
                <span class="text-theme-info">
                    <?php if ($licStatus === 'trial'): ?>
                        <?= _t('license_trial_message') ?>
                    <?php else: ?>
                        <?= _t('license_free_message') ?>
                    <?php endif; ?>
                </span>
            </div>
        </a>
    <?php
    endif; ?>

    <a href="settings.php?tab=system"
        class="group block relative bg-theme-bg/20 hover:bg-theme-bg/40 p-3 border <?= $border_class ?> hover:border-theme-primary/50 rounded-theme overflow-hidden transition-colors">
        <div class="flex justify-between items-center mb-1">
            <span class="opacity-50 font-bold text-[10px] tracking-widest <?= $text_class ?>">
                <?= _t('system') ?>
            </span>
            <div class="flex items-center gap-1.5">
                <span class="relative flex w-2 h-2">
                    <span
                        class="<?= $statusDot === 'bg-theme-success' ? 'animate-ping-slow' : 'animate-ping' ?> absolute inline-flex h-full w-full rounded-full opacity-75 <?= $statusDot ?>"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 <?= $statusDot ?>"></span>
                </span>
            </div>
        </div>
        <div class="flex justify-between items-center">
            <span class="max-w-[180px] font-bold text-xs truncate <?= $text_class ?>">
                <?= h($sysStatus['msg']) ?>
            </span>
            <?php if (in_array($licStatus, ['pro', 'agency'])): ?>
                <span class="flex items-center gap-1 font-mono text-[10px] <?= $text_class ?>">
                    <span
                        class="inline-flex items-center py-0.5 px-1 bg-theme-success/20 text-theme-success text-[9px] font-bold rounded-theme tracking-wide">PRO</span>
                    v
                    <?= h(CMS_VERSION) ?>
                </span>
                <?php if (!empty($hasUpdate)): ?>
                    <a href="settings.php?tab=update" class="ml-auto text-theme-primary font-bold text-[10px] hover:underline animate-pulse" title="<?= _t('st_update_available') ?>">
                        Update
                    </a>
                <?php endif; ?>
            <?php
            else: ?>
                <span class="opacity-60 font-mono text-[10px] <?= $text_class ?>">v
                    <?= h(CMS_VERSION) ?>
                </span>
                <?php if (!empty($hasUpdate)): ?>
                    <a href="https://github.com/grindworks/grind-site/releases" target="_blank" class="ml-auto text-theme-warning font-bold text-[10px] hover:underline" title="Manual Update Required">
                        Update (Manual)
                    </a>
                <?php endif; ?>
            <?php
            endif; ?>
        </div>
    </a>
<?php endif; ?>

<div class="flex justify-between items-center pt-1">
    <div class="flex items-center gap-3 min-w-0">
        <div
            class="flex flex-shrink-0 justify-center items-center bg-theme-bg/20 border <?= $border_class ?> rounded-full w-9 h-9 overflow-hidden font-bold text-sm <?= $text_class ?>">
            <?php if (!empty($currentUser['avatar'])): ?>
                <img src="<?= h(get_media_url($currentUser['avatar'])) ?>" alt="User"
                    class="w-full h-full object-cover">
            <?php
            else: ?>
                <?= strtoupper(substr($currentUser['username'], 0, 1)) ?>
            <?php
            endif; ?>
        </div>
        <div class="flex flex-col min-w-0">
            <span class="font-bold text-xs truncate <?= $text_class ?>">
                <?= h($_SESSION['username'] ?? 'Admin') ?>
            </span>
            <span class="opacity-50 text-[10px] truncate <?= $text_class ?>">
                <?= _t('administrator') ?>
            </span>
        </div>
    </div>

    <form method="post" action="logout.php">
        <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
        <button type="submit" class="<?= $logout_btn_class ?>" title="<?= h(_t('logout')) ?>">
            <svg class="w-5 h-5 <?= $logout_with_text ? 'mr-1.5' : '' ?>" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-right-on-rectangle"></use>
            </svg>
            <?php if ($logout_with_text): ?>
                <?= _t('logout') ?>
            <?php
            endif; ?>
        </button>
    </form>
</div>
