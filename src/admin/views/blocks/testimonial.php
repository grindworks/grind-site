<?php

/** Testimonial Block View */
if (!defined('GRINDS_APP')) exit; ?>
<div class="bg-theme-bg/40 p-4 border border-theme-border rounded-theme" x-init="if(typeof block.data.comment === 'undefined') block.data.comment = ''; if(typeof block.data.name === 'undefined') block.data.name = ''; if(typeof block.data.role === 'undefined') block.data.role = '';" x-data="{ isUploading: false }">
  <div class="flex sm:flex-row flex-col items-start gap-4">
    <!-- Avatar uploader -->
    <div class="w-full sm:w-auto text-center shrink-0">
      <div class="relative inline-block group mx-auto">
        <div class="relative bg-theme-surface border border-theme-border rounded-full w-20 h-20 overflow-hidden cursor-pointer" @click="openMediaLibrary(index, null, 'image')">
          <img :src="resolvePreviewUrl(block.data.image)" x-show="block.data.image" class="w-full h-full object-cover" @error="$el.src = <?= htmlspecialchars(json_encode(PLACEHOLDER_IMG), ENT_QUOTES) ?>">
          <div class="absolute inset-0 flex justify-center items-center skin-modal-overlay opacity-0 group-hover:opacity-100 text-[10px] text-white transition-opacity"><?= _t('btn_change') ?></div>
          <div x-show="!block.data.image" class="flex justify-center items-center w-full h-full text-theme-text/20 text-xs"><?= _t('lbl_user') ?></div>
        </div>
        <button type="button" @click.stop="block.data.image = ''" x-show="block.data.image" class="absolute -top-1 -right-1 bg-theme-danger text-white rounded-full p-0.5 shadow-theme opacity-0 group-hover:opacity-100 transition-opacity z-10" title="<?= h(_t('delete')) ?>">
          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-x-mark"></use>
          </svg>
        </button>
      </div>
      <label class="block mt-1 text-[9px] text-theme-primary hover:underline cursor-pointer" :class="{'opacity-50 cursor-not-allowed': isUploading}">
        <span x-text="isUploading ? '...' : '<?= _t('upload') ?>'"></span>
        <input type="file" class="hidden" accept="image/*" @change="isUploading = true; await uploadImage($event, index, 'image'); isUploading = false" :disabled="isUploading">
      </label>
    </div>
    <!-- Content inputs -->
    <div class="flex-1 space-y-3 w-full">
      <textarea x-model="block.data.comment" :id="'block-' + block.id + '-comment'" rows="2"
        x-init="$nextTick(() => { $el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px' })"
        @input="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'"
        class="w-full text-sm form-control-sm resize-none overflow-hidden" placeholder="<?= _t('ph_comment') ?>"></textarea>
      <div class="flex sm:flex-row flex-col gap-3">
        <div class="flex-1">
          <input type="text" x-model="block.data.name" :id="'block-' + block.id + '-name'" class="w-full font-bold text-xs form-control-sm" placeholder="<?= _t('ph_name') ?>">
        </div>
        <div class="flex-1">
          <input type="text" x-model="block.data.role" :id="'block-' + block.id + '-role'" class="w-full text-xs form-control-sm" placeholder="<?= _t('ph_role') ?>">
        </div>
      </div>
    </div>
  </div>
</div>
