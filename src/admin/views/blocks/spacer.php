<?php

/** Spacer Block View */
if (!defined('GRINDS_APP')) exit; ?>
<div class="flex items-center gap-4 bg-theme-bg/40 p-4 border border-theme-border border-dashed rounded-theme h-16" x-init="if(!block.data.height) block.data.height = 50" x-data="{ previewHeight: block.data.height || 50 }" x-effect="if(block.data.height) previewHeight = block.data.height">
  <!-- Icon -->
  <div class="flex items-center gap-1 opacity-50 font-bold text-theme-text text-xs shrink-0">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-chevron-up-down"></use>
    </svg>
  </div>
  <!-- Height slider -->
  <div class="flex-1">
    <input type="range" x-model="block.data.height" @input="previewHeight = $event.target.value" min="10" max="200" step="10" class="bg-theme-border rounded-theme w-full h-2 accent-theme-primary appearance-none cursor-pointer">
  </div>
  <!-- Height display -->
  <div class="w-16 font-mono text-theme-text text-xs text-right shrink-0">
    <span x-text="previewHeight"></span> px
  </div>
</div>
