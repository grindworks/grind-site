<?php

/** Image Block View */
if (!defined('GRINDS_APP')) exit; ?>
<div class="space-y-3 bg-theme-bg/40 p-4 border border-theme-border rounded-theme" x-data="{ isUploading: false }">
  <!-- Source controls -->
  <div class="flex gap-2">
    <!-- URL input -->
    <input type="text" x-model="block.data.url" @blur="block.data.url = normalizeUrl(block.data.url)" class="flex-1 font-mono text-xs form-control-sm" placeholder="<?= _t('ph_image_url') ?>">
    <!-- Open media library -->
    <button type="button" @click="openMediaLibrary(index, null, 'url')" class="flex items-center gap-1 px-3 py-1 text-xs btn-secondary shrink-0">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-photo"></use>
      </svg>
      <?= _t('btn_select_library') ?>
    </button>
    <!-- Upload button -->
    <label class="flex items-center gap-1 px-3 py-1 text-xs cursor-pointer btn-secondary shrink-0" :class="{'opacity-50 cursor-not-allowed': isUploading}">
      <svg x-show="!isUploading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-up-tray"></use>
      </svg>
      <svg x-show="isUploading" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-path"></use>
      </svg>
      <span x-text="isUploading ? '...' : '<?= _t('upload') ?>'"></span>
      <input type="file" class="hidden" accept="image/*" @change="isUploading = true; await uploadImage($event, index, 'url'); isUploading = false" :disabled="isUploading">
    </label>
  </div>
  <!-- Preview -->
  <div x-show="block.data.url" class="relative bg-theme-bg p-2 border border-theme-border rounded-theme text-center">
    <img :src="resolvePreviewUrl(block.data.url)" class="shadow-theme mx-auto rounded-theme max-h-60" @error="$el.src = <?= htmlspecialchars(json_encode(PLACEHOLDER_IMG), ENT_QUOTES) ?>">
  </div>
  <!-- Caption input -->
  <input type="text" x-model="block.data.caption" class="w-full text-xs text-center transition-colors form-control-sm" placeholder="<?= _t('ph_caption') ?>">
  <!-- Alt text input -->
  <input type="text" x-model="block.data.alt" class="w-full text-xs text-center transition-colors form-control-sm" placeholder="<?= _t('ph_alt_text') ?>">
</div>
