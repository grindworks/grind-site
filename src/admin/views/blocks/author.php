<?php

/** Author Block View */
if (!defined('GRINDS_APP')) exit; ?>
<div class="bg-theme-bg/40 p-4 border border-theme-border rounded-theme flex sm:flex-row flex-col gap-4">
  <div class="shrink-0 text-center mx-auto sm:mx-0" x-data="{ isUploading: false }">
    <div class="relative inline-block group">
      <div class="w-16 h-16 rounded-full bg-theme-surface border border-theme-border overflow-hidden cursor-pointer relative" @click="openMediaLibrary(block.id, null, 'image')">
        <img :src="resolvePreviewUrl(block.data.image)" x-show="block.data.image" class="w-full h-full object-cover">
        <div x-show="!block.data.image" class="flex items-center justify-center w-full h-full text-theme-text/20">
          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-user-circle"></use>
          </svg>
        </div>
        <div class="absolute inset-0 skin-modal-overlay text-white text-[10px] flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity"><?= _t('btn_change') ?></div>
      </div>
      <button type="button" @click.stop="block.data.image = ''" x-show="block.data.image" class="absolute -top-1 -right-1 bg-theme-danger text-white rounded-full p-0.5 shadow-theme opacity-0 group-hover:opacity-100 transition-opacity z-10" title="<?= h(_t('delete')) ?>">
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-x-mark"></use>
        </svg>
      </button>
    </div>
    <label class="block mt-1 text-[9px] text-theme-primary hover:underline cursor-pointer" :class="{'opacity-50 cursor-not-allowed': isUploading}">
      <span x-text="isUploading ? '...' : '<?= _t('upload') ?>'"></span>
      <input type="file" class="hidden" accept="image/*" @change="isUploading = true; await uploadImage($event, block.id, 'image'); isUploading = false" :disabled="isUploading">
    </label>
  </div>
  <div class="flex-1 space-y-2 w-full">
    <div class="flex sm:flex-row flex-col gap-2">
      <input type="text" x-model="block.data.name" :id="'block-' + block.id + '-name'" class="font-bold form-control-sm flex-1" placeholder="<?= _t('ph_author_name') ?>">
      <input type="text" x-model="block.data.role" :id="'block-' + block.id + '-role'" class="form-control-sm flex-1" placeholder="<?= _t('ph_author_role') ?>">
    </div>
    <textarea x-model="block.data.bio" :id="'block-' + block.id + '-bio'" rows="2" class="form-control-sm w-full text-xs" placeholder="<?= _t('ph_bio') ?>"></textarea>
    <input type="text" x-model="block.data.link" :id="'block-' + block.id + '-link'" @blur="block.data.link = normalizeUrl(block.data.link)" class="form-control-sm w-full font-mono text-xs" placeholder="<?= _t('ph_sns_link') ?>">
  </div>
</div>
