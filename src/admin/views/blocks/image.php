<?php

/** Image Block View */
if (!defined('GRINDS_APP')) exit; ?>
<div class="space-y-3 bg-theme-bg/40 p-4 border border-theme-border rounded-theme transition-colors"
  x-init="if(block.data.width === undefined) block.data.width = 100"
  x-data="{ isUploading: false, isDragging: false, dragCount: 0, previewWidth: block.data.width || 100 }"
  x-effect="if (block.data.width) previewWidth = block.data.width"
  @dragenter.prevent="dragCount++; isDragging = true"
  @dragover.prevent="isDragging = true"
  @dragleave.prevent="dragCount--; if (dragCount === 0) isDragging = false"
  @drop.prevent="dragCount = 0; isDragging = false; if($event.dataTransfer.files.length) { isUploading = true; await uploadImage({target: {files: $event.dataTransfer.files}}, index, 'url'); isUploading = false; }"
  :class="{'border-theme-primary bg-theme-primary/5': isDragging}">
  <!-- Source controls -->
  <div class="flex gap-2">
    <!-- URL input -->
    <input type="text" x-model="block.data.url" :id="'block-' + block.id + '-url'" @blur="block.data.url = normalizeUrl(block.data.url)" class="flex-1 font-mono text-xs form-control-sm" placeholder="<?= _t('ph_image_url') ?>">
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
  <div x-show="block.data.url" class="relative bg-theme-bg p-2 border border-theme-border rounded-theme flex justify-center items-center">
    <img :src="resolvePreviewUrl(block.data.url)" :style="'width: ' + previewWidth + '%;'" class="shadow-theme rounded-theme max-h-60 object-contain transition-all duration-200" @error="$el.src = <?= htmlspecialchars(json_encode(PLACEHOLDER_IMG), ENT_QUOTES) ?>">
  </div>
  <!-- Width slider -->
  <div x-show="block.data.url" class="flex items-center gap-3 px-1">
    <label class="opacity-50 font-bold text-theme-text text-[10px] whitespace-nowrap"><?= _t('lbl_image_width') ?? 'Width' ?></label>
    <input type="range" x-model="block.data.width" @input="previewWidth = $event.target.value" min="10" max="100" step="5" class="bg-theme-border rounded-theme w-full h-2 accent-theme-primary appearance-none cursor-pointer">
    <span class="w-12 font-mono text-[10px] text-theme-text text-right"><span x-text="previewWidth"></span>%</span>
  </div>
  <!-- Caption input -->
  <input type="text" x-model="block.data.caption" :id="'block-' + block.id + '-caption'" class="w-full text-xs text-center transition-colors form-control-sm" placeholder="<?= _t('ph_caption') ?>">
  <!-- Alt text input -->
  <div class="relative">
    <input type="text" x-model="block.data.alt" :id="'block-' + block.id + '-alt'" class="w-full text-xs text-center transition-colors form-control-sm" placeholder="<?= _t('ph_alt_text') ?>">
    <p class="opacity-50 mt-1 text-[10px] text-theme-text text-center">
      <span><?= _t('msg_alt_help') ?></span>
    </p>
  </div>
</div>
