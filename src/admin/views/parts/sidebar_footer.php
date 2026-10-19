<?php

/**
 * sidebar_footer.php
 * Renders the sidebar footer with system status and user info.
 *
 * @var array $sysStatus
 * @var array $currentUser
 * @var string $statusDot
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
            <span class="opacity-60 font-mono text-[10px] whitespace-nowrap <?= $text_class ?>">v<?= h(CMS_VERSION) ?></span>
            <?php if (!empty($hasUpdate)): ?>
                <a href="settings.php?tab=update" class="ml-auto text-theme-primary font-bold text-[10px] hover:underline animate-pulse" title="<?= h(_t('st_update_available')) ?>">
                    Update
                </a>
            <?php endif; ?>
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
