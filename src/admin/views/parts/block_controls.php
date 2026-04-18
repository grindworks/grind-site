<?php

/**
 * block_controls.php
 * Renders editor block controls for moving and deleting.
 */
if (!defined('GRINDS_APP')) exit; ?>

<div class="flex items-center justify-between w-full mb-3">
    <!-- Label and Summary (Left) -->
    <div class="flex items-center gap-2 pointer-events-none select-none min-w-0">
        <span class="bg-theme-bg opacity-60 px-2 py-0.5 border border-theme-border rounded-theme font-bold text-[10px] text-theme-text uppercase tracking-wider shrink-0" x-text="getBlockLabel(block.type)"></span>
        <span x-show="block.collapsed" class="opacity-50 max-w-[150px] sm:max-w-xs font-medium text-theme-text text-xs truncate" x-text="getBlockSummary(block)"></span>
    </div>

    <!-- Controls (Right) -->
    <!-- Opacity reduced slightly until hovered to keep focus on content, but always accessible without overlapping -->
    <div class="flex gap-0.5 bg-theme-surface p-1 border border-theme-border/50 rounded-theme transition-opacity shrink-0 ml-2 opacity-80 hover:opacity-100 shadow-sm">
        <button type="button" x-show="index > 0" @click="moveBlock(index, -1)" class="hover:bg-theme-bg p-1.5 rounded-theme text-theme-text hover:text-theme-primary transition-colors" title="<?= h(_t('btn_move_up')) ?>">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-chevron-up"></use>
            </svg>
        </button>
        <button type="button" x-show="index < blocks.length - 1" @click="moveBlock(index, 1)" class="hover:bg-theme-bg p-1.5 rounded-theme text-theme-text hover:text-theme-primary transition-colors" title="<?= h(_t('btn_move_down')) ?>">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-chevron-down"></use>
            </svg>
        </button>
        <button type="button" @click="duplicateBlock(index)" class="hover:bg-theme-bg p-1.5 rounded-theme text-theme-text hover:text-theme-primary transition-colors" title="<?= h(_t('btn_duplicate')) ?>">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-document-duplicate"></use>
            </svg>
        </button>
        <button type="button" @click="toggleCollapse(index)" class="hover:bg-theme-bg p-1.5 rounded-theme text-theme-text hover:text-theme-primary transition-colors" :title="block.collapsed ? <?= htmlspecialchars(json_encode(_t('btn_expand')), ENT_QUOTES) ?> : <?= htmlspecialchars(json_encode(_t('btn_collapse')), ENT_QUOTES) ?>">
            <svg class="w-4 h-4 transition-transform duration-200" :class="block.collapsed ? '-rotate-90' : 'rotate-0'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-chevron-down"></use>
            </svg>
        </button>

        <div class="mx-1 my-1 bg-theme-border/50 w-px"></div>

        <button type="button" @click="removeBlock(index)" class="hover:bg-theme-danger/10 p-1.5 rounded-theme text-theme-text hover:text-theme-danger transition-colors" title="<?= h(_t('delete')) ?>">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-trash"></use>
            </svg>
        </button>
    </div>
</div>
