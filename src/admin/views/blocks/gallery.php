<?php

/** Gallery Block View */
if (!defined('GRINDS_APP')) exit; ?>
<div class="bg-theme-bg/40 p-4 border border-theme-border rounded-theme" x-init="if(!block.data.images) block.data.images = []; if(!block.data.columns) block.data.columns = '3'; $watch('block.data.images', v => v && v.forEach(i => { if(!i.id) i.id = Math.random().toString(36).substr(2, 9) })); block.data.images.forEach(i => { if(!i.id) i.id = Math.random().toString(36).substr(2, 9) })" x-data="{ isUploading: false }">
  <!-- Column selector -->
  <div class="flex items-center gap-4 mb-3">
    <label class="opacity-70 font-bold text-theme-text text-xs"><?= _t('lbl_columns') ?>:</label>
    <select x-model="block.data.columns" class="w-20 text-xs cursor-pointer form-control-sm">
      <option value="2">2</option>
      <option value="3">3</option>
      <option value="4">4</option>
    </select>
  </div>
  <!-- Image grid -->
  <div class="gap-2 grid" :class="{
      'grid-cols-2': block.data.columns == 2,
      'grid-cols-3': !block.data.columns || block.data.columns == 3,
      'grid-cols-4': block.data.columns == 4
  }">
    <!-- Loop images -->
    <template x-for="(img, i) in block.data.images" :key="img.id">
      <div class="group relative bg-theme-bg/40 border border-theme-border rounded-theme aspect-square overflow-hidden">
        <img :src="resolvePreviewUrl(img.url)" class="w-full h-full object-cover" @error="$el.src = <?= htmlspecialchars(json_encode(PLACEHOLDER_IMG), ENT_QUOTES) ?>">
        <!-- Delete image -->
        <button type="button" @click="block.data.images.splice(i, 1)" class="top-1 right-1 absolute bg-theme-surface/90 md:opacity-0 opacity-100 group-hover:opacity-100 shadow-theme p-2 min-w-[32px] min-h-[32px] flex items-center justify-center border border-theme-border rounded-full text-theme-danger transition-opacity">
          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-x-mark"></use>
          </svg>
        </button>
        <!-- Caption input -->
        <input type="text" x-model="img.caption" class="bottom-0 left-0 absolute skin-modal-overlay opacity-0 group-hover:opacity-100 p-1 border-none focus:ring-0 w-full text-[10px] text-white text-center transition-opacity" placeholder="<?= _t('ph_caption') ?>">
      </div>
    </template>
    <!-- Add from library -->
    <button type="button" @click="openMediaLibrary(index, 'add', 'url')" class="flex flex-col justify-center items-center bg-theme-bg opacity-50 border-2 border-theme-border hover:border-theme-primary/50 border-dashed rounded-theme aspect-square text-theme-text hover:text-theme-primary transition-colors" title="<?= h(_t('btn_select_library')) ?>">
      <svg class="mb-1 w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-photo"></use>
      </svg>
      <span class="font-bold text-[9px]"><?= _t('title_media_library') ?></span>
    </button>
    <!-- Upload images -->
    <label class="flex flex-col justify-center items-center bg-theme-bg opacity-50 border-2 border-theme-border hover:border-theme-primary/50 border-dashed rounded-theme aspect-square text-theme-text hover:text-theme-primary transition-colors cursor-pointer" title="<?= h(_t('upload')) ?>" :class="{'opacity-25 cursor-not-allowed': isUploading}">
      <input type="file" multiple accept="image/*" class="hidden" @change="isUploading = true; await uploadGalleryImages($event, index); isUploading = false" :disabled="isUploading">
      <svg x-show="!isUploading" class="mb-1 w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-up-tray"></use>
      </svg>
      <svg x-show="isUploading" class="mb-1 w-6 h-6 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-path"></use>
      </svg>
      <span class="font-bold text-[9px]" x-text="isUploading ? '...' : '<?= _t('upload') ?>'"></span>
    </label>
  </div>
</div>
