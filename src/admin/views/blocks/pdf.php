<?php

/** PDF Block View */
if (!defined('GRINDS_APP')) exit; ?>
<div class="bg-theme-bg/40 p-4 border border-theme-border rounded-theme" x-data="{ isUploading: false }">
  <div class="flex flex-col gap-3">
    <!-- Document title -->
    <div>
      <label class="block opacity-50 mb-1 font-bold text-[10px] text-theme-text"><?= _t('lbl_file_title') ?></label>
      <input type="text" x-model="block.data.title" :id="'block-' + block.id + '-title'" class="w-full font-bold form-control-sm" placeholder="<?= _t('ph_doc_title') ?>">
    </div>
    <div class="flex gap-2">
      <!-- PDF URL -->
      <input type="text" x-model="block.data.url" :id="'block-' + block.id + '-url'" class="flex-1 font-mono text-xs form-control-sm" placeholder="<?= _t('ph_pdf_url') ?>">
      <!-- Open media library -->
      <button type="button" @click="openMediaLibrary(index, null, 'url')" class="flex items-center gap-1 px-3 py-1 text-xs btn-secondary shrink-0">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-up-tray"></use>
        </svg>
        <?= _t('select_file') ?>
      </button>
      <label class="flex items-center gap-1 px-3 py-1 text-xs cursor-pointer btn-secondary shrink-0" :class="{'opacity-50 cursor-not-allowed': isUploading}">
        <svg x-show="!isUploading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-up-tray"></use>
        </svg>
        <svg x-show="isUploading" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
          <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-path"></use>
        </svg>
        <span x-text="isUploading ? '...' : '<?= _t('upload') ?>'"></span>
        <input type="file" class="hidden" accept="application/pdf" @change="isUploading = true; await uploadImage($event, index, 'url'); isUploading = false" :disabled="isUploading">
      </label>
    </div>
  </div>
</div>
