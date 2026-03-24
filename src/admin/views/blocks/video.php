<?php

/** Video Block View */
if (!defined('GRINDS_APP')) exit; ?>
<div class="bg-theme-bg/40 p-4 border border-theme-border rounded-theme" x-data="{ isUploading: false }">
  <div class="flex gap-2 mb-3">
    <input type="text" x-model="block.data.url" @blur="block.data.url = normalizeUrl(block.data.url)" class="flex-1 font-mono text-xs form-control-sm" placeholder="<?= _t('ph_video_url') ?>">

    <button type="button" @click="openMediaLibrary(index, null, 'url')" class="flex items-center gap-1 px-3 py-1 text-xs btn-secondary shrink-0">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-film"></use>
      </svg>
      <?= _t('btn_select_library') ?>
    </button>

    <label class="flex items-center gap-1 px-3 py-1 text-xs cursor-pointer btn-secondary shrink-0" :class="{'opacity-50 cursor-not-allowed': isUploading}">
      <svg x-show="!isUploading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-up-tray"></use>
      </svg>
      <svg x-show="isUploading" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-path"></use>
      </svg>
      <span x-text="isUploading ? '...' : '<?= _t('upload') ?>'"></span>
      <input type="file" class="hidden" accept="video/*" @change="isUploading = true; await uploadImage($event, index, 'url'); isUploading = false" :disabled="isUploading">
    </label>
  </div>
  <p class="mt-1 mb-3 text-xs opacity-70">
    <?= _t('msg_video_embed_notice') ?>
  </p>

  <div class="flex flex-wrap gap-4 text-xs text-theme-text mb-3">
    <label class="flex items-center cursor-pointer select-none">
      <input type="checkbox" x-model="block.data.autoplay" class="mr-1.5 bg-theme-bg border-theme-border rounded focus:ring-theme-primary w-4 h-4 text-theme-primary form-checkbox"> <?= _t('lbl_autoplay') ?>
    </label>
    <label class="flex items-center cursor-pointer select-none">
      <input type="checkbox" x-model="block.data.loop" class="mr-1.5 bg-theme-bg border-theme-border rounded focus:ring-theme-primary w-4 h-4 text-theme-primary form-checkbox"> <?= _t('lbl_loop') ?>
    </label>
    <label class="flex items-center cursor-pointer select-none">
      <input type="checkbox" x-model="block.data.muted" class="mr-1.5 bg-theme-bg border-theme-border rounded focus:ring-theme-primary w-4 h-4 text-theme-primary form-checkbox"> <?= _t('lbl_muted') ?>
    </label>
  </div>

  <div x-show="block.data.url" class="bg-black rounded-theme overflow-hidden max-h-48 flex justify-center border border-theme-border">
    <video :src="resolvePreviewUrl(block.data.url)" class="max-h-48 object-contain" controls></video>
  </div>
</div>
