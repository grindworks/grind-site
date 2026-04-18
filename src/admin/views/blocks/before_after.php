<?php

/** Before / After Block View */
if (!defined('GRINDS_APP')) exit; ?>
<div class="space-y-4 bg-theme-bg/40 p-4 border border-theme-border rounded-theme transition-colors"
  x-data="{ isUploadingBefore: false, isUploadingAfter: false, isDragging: false, dragCount: 0 }"
  @dragenter.prevent="dragCount++; isDragging = true"
  @dragover.prevent="isDragging = true"
  @dragleave.prevent="dragCount--; if (dragCount === 0) isDragging = false"
  @drop.prevent="dragCount = 0; isDragging = false;
    if($event.dataTransfer.files.length) {
      isUploadingBefore = true;
      await uploadImage({target: {files: [$event.dataTransfer.files[0]]}}, block.id, 'beforeUrl');
      isUploadingBefore = false;
      if($event.dataTransfer.files.length > 1) {
        isUploadingAfter = true;
        await uploadImage({target: {files: [$event.dataTransfer.files[1]]}}, block.id, 'afterUrl');
        isUploadingAfter = false;
      }
    }"
  :class="{'border-theme-primary bg-theme-primary/5': isDragging}">
  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <!-- Before -->
    <div class="space-y-2">
      <div class="flex justify-between items-center">
        <label class="font-bold text-theme-text text-xs opacity-70"><?= _t('lbl_before_img') ?></label>
        <input type="text" x-model="block.data.beforeLabel" :id="'block-' + block.id + '-beforeLabel'" class="w-24 text-xs form-control-sm text-center" placeholder="Before">
      </div>
      <div class="group relative bg-checker border border-theme-border rounded-theme aspect-video overflow-hidden">
        <img :src="resolvePreviewUrl(block.data.beforeUrl)" x-show="block.data.beforeUrl" class="w-full h-full object-cover">
        <div x-show="!block.data.beforeUrl" class="flex justify-center items-center w-full h-full text-theme-text/20 text-xs"><?= _t('no_img') ?></div>
      </div>
      <div class="flex gap-2">
        <input type="text" x-model="block.data.beforeUrl" :id="'block-' + block.id + '-beforeUrl'" @blur="block.data.beforeUrl = normalizeUrl(block.data.beforeUrl)" class="flex-1 font-mono text-xs form-control-sm" placeholder="URL">
        <button type="button" @click="openMediaLibrary(block.id, null, 'beforeUrl')" class="px-2 py-1 text-xs btn-secondary shrink-0" title="<?= h(_t('btn_select_library')) ?>">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-photo"></use>
          </svg>
        </button>
        <label class="px-2 py-1 text-xs cursor-pointer btn-secondary shrink-0" :class="{'opacity-50 cursor-not-allowed': isUploadingBefore}">
          <input type="file" class="hidden" accept="image/*" @change="isUploadingBefore = true; await uploadImage($event, block.id, 'beforeUrl'); isUploadingBefore = false" :disabled="isUploadingBefore">
          <svg x-show="!isUploadingBefore" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-up-tray"></use>
          </svg>
          <svg x-show="isUploadingBefore" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none;">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-path"></use>
          </svg>
        </label>
      </div>
    </div>
    <!-- After -->
    <div class="space-y-2">
      <div class="flex justify-between items-center">
        <label class="font-bold text-theme-text text-xs opacity-70"><?= _t('lbl_after_img') ?></label>
        <input type="text" x-model="block.data.afterLabel" :id="'block-' + block.id + '-afterLabel'" class="w-24 text-xs form-control-sm text-center" placeholder="After">
      </div>
      <div class="group relative bg-checker border border-theme-border rounded-theme aspect-video overflow-hidden">
        <img :src="resolvePreviewUrl(block.data.afterUrl)" x-show="block.data.afterUrl" class="w-full h-full object-cover">
        <div x-show="!block.data.afterUrl" class="flex justify-center items-center w-full h-full text-theme-text/20 text-xs"><?= _t('no_img') ?></div>
      </div>
      <div class="flex gap-2">
        <input type="text" x-model="block.data.afterUrl" :id="'block-' + block.id + '-afterUrl'" @blur="block.data.afterUrl = normalizeUrl(block.data.afterUrl)" class="flex-1 font-mono text-xs form-control-sm" placeholder="URL">
        <button type="button" @click="openMediaLibrary(block.id, null, 'afterUrl')" class="px-2 py-1 text-xs btn-secondary shrink-0" title="<?= h(_t('btn_select_library')) ?>">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-photo"></use>
          </svg>
        </button>
        <label class="px-2 py-1 text-xs cursor-pointer btn-secondary shrink-0" :class="{'opacity-50 cursor-not-allowed': isUploadingAfter}">
          <input type="file" class="hidden" accept="image/*" @change="isUploadingAfter = true; await uploadImage($event, block.id, 'afterUrl'); isUploadingAfter = false" :disabled="isUploadingAfter">
          <svg x-show="!isUploadingAfter" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-up-tray"></use>
          </svg>
          <svg x-show="isUploadingAfter" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none;">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-path"></use>
          </svg>
        </label>
      </div>
    </div>
  </div>
</div>
