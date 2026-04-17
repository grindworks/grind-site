<?php

/** Gallery Block View */
if (!defined('GRINDS_APP')) exit; ?>
<div class="bg-theme-bg/40 p-4 border border-theme-border rounded-theme transition-colors" x-init="if(!block.data.images) block.data.images = []; if(!block.data.columns) block.data.columns = '3'; $watch('block.data.images', v => v && v.forEach(i => { if(!i.id) i.id = generateId() })); block.data.images.forEach(i => { if(!i.id) i.id = generateId() })"
  x-data="{ isUploading: false, isDragging: false, dragCount: 0 }"
  @dragenter.prevent="dragCount++; isDragging = true"
  @dragover.prevent="isDragging = true"
  @dragleave.prevent="dragCount--; if (dragCount === 0) isDragging = false"
  @drop.prevent="dragCount = 0; isDragging = false; if($event.dataTransfer.files.length) { isUploading = true; await uploadGalleryImages({target: {files: $event.dataTransfer.files}}, block.id); isUploading = false; }"
  :class="{'border-theme-primary bg-theme-primary/5': isDragging}">
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
        <!-- Action Buttons -->
        <div class="top-1 left-1 absolute flex items-center gap-1 md:opacity-0 opacity-100 group-hover:opacity-100 transition-opacity z-10">
          <!-- Move Left -->
          <button type="button" @click.prevent="if(i > 0) { const item = block.data.images.splice(i, 1)[0]; block.data.images.splice(i - 1, 0, item); }" x-show="i > 0" class="bg-theme-surface/90 shadow-theme p-1.5 min-w-[28px] min-h-[28px] flex items-center justify-center border border-theme-border rounded-full text-theme-text hover:text-theme-primary transition-colors">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-chevron-left"></use>
            </svg>
          </button>
          <!-- Move Right -->
          <button type="button" @click.prevent="if(i < block.data.images.length - 1) { const item = block.data.images.splice(i, 1)[0]; block.data.images.splice(i + 1, 0, item); }" x-show="i < block.data.images.length - 1" class="bg-theme-surface/90 shadow-theme p-1.5 min-w-[28px] min-h-[28px] flex items-center justify-center border border-theme-border rounded-full text-theme-text hover:text-theme-primary transition-colors">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-chevron-right"></use>
            </svg>
          </button>
          <!-- Delete image -->
          <button type="button" @click.prevent="if(!img.url || confirm(window.grindsTranslations?.confirm_delete || 'Are you sure?')) block.data.images.splice(i, 1)" class="bg-theme-surface/90 shadow-theme p-1.5 min-w-[28px] min-h-[28px] flex items-center justify-center border border-theme-border rounded-full text-theme-danger hover:bg-theme-danger/10 transition-colors" title="<?= h(_t('delete')) ?>">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-x-mark"></use>
            </svg>
          </button>
        </div>
        <!-- Caption and Alt inputs -->
        <div class="bottom-0 left-0 absolute w-full skin-modal-overlay opacity-0 group-hover:opacity-100 p-1.5 space-y-1 transition-opacity">
          <input type="text" x-model="img.caption" :id="'block-' + block.id + '-caption-' + i" class="bg-black/30 border-white/20 focus:ring-white/50 focus:border-white/50 w-full text-[10px] text-white text-center form-control-sm" placeholder="<?= _t('ph_caption') ?>">
          <input type="text" x-model="img.alt" :id="'block-' + block.id + '-alt-' + i" class="bg-black/30 border-white/20 focus:ring-white/50 focus:border-white/50 w-full text-[10px] text-white text-center form-control-sm" placeholder="<?= _t('ph_alt_text') ?>">
        </div>
      </div>
    </template>
    <!-- Add from library -->
    <button type="button" @click="openMediaLibrary(block.id, 'add', 'url')" class="flex flex-col justify-center items-center bg-theme-bg opacity-50 border-2 border-theme-border hover:border-theme-primary/50 border-dashed rounded-theme aspect-square text-theme-text hover:text-theme-primary transition-colors" title="<?= h(_t('btn_select_library')) ?>">
      <svg class="mb-1 w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-photo"></use>
      </svg>
      <span class="font-bold text-[9px]"><?= _t('title_media_library') ?></span>
    </button>
    <!-- Upload images -->
    <label class="flex flex-col justify-center items-center bg-theme-bg opacity-50 border-2 border-theme-border hover:border-theme-primary/50 border-dashed rounded-theme aspect-square text-theme-text hover:text-theme-primary transition-colors cursor-pointer" title="<?= h(_t('upload')) ?>" :class="{'opacity-25 cursor-not-allowed': isUploading}">
      <input type="file" multiple accept="image/*" class="hidden" @change="isUploading = true; await uploadGalleryImages($event, block.id); isUploading = false" :disabled="isUploading">
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
