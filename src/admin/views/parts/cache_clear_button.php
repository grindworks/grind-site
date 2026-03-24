<?php

/**
 * cache_clear_button.php
 * Renders the cache clear button with animation.
 */
if (!defined('GRINDS_APP')) exit;

$show_label = $show_label ?? false;
$btn_class = $btn_class ?? 'relative hover:bg-theme-primary/10 p-2 rounded-full text-theme-text/60 hover:text-theme-primary transition-colors';
$wrapper_class = $wrapper_class ?? '';
?>
<div x-data="{ clearing: false }" class="<?= $wrapper_class ?>">
    <button @click="clearing = true;
        const base = (window.grindsBaseUrl || '').replace(/\/$/, '');
        fetch(base + '/admin/api/clear_cache.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({csrf_token: <?= htmlspecialchars(json_encode(generate_csrf_token()), ENT_QUOTES) ?>})
        }).then(() => {
          setTimeout(() => { clearing = false; if (typeof window.showToast === 'function') { window.showToast(<?= htmlspecialchars(json_encode(_t('msg_cache_cleared')), ENT_QUOTES) ?>); } else { alert(<?= htmlspecialchars(json_encode(_t('msg_cache_cleared')), ENT_QUOTES) ?>); } }, 500);
        })"
        class="<?= $btn_class ?>"
        title="<?= h(_t('st_clear_cache_title')) ?>">

        <div class="relative w-5 h-5">
            <svg x-show="!clearing" class="absolute inset-0 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-bolt"></use>
            </svg>
            <svg x-show="clearing" x-cloak class="absolute inset-0 w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-path"></use>
            </svg>
        </div>

        <?php if ($show_label): ?>
            <span class="hidden lg:inline font-bold text-xs whitespace-nowrap ml-1.5"><?= _t('st_clear_cache_title') ?></span>
        <?php endif; ?>
    </button>
</div>
