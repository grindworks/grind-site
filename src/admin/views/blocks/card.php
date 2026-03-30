<?php

/** External Link Card Block View */
if (!defined('GRINDS_APP')) exit; ?>
<div class="space-y-3 bg-theme-bg/40 p-4 border border-theme-border rounded-theme" x-data="{ isUploading: false }">
  <!-- URL input and fetch button -->
  <div class="flex items-center gap-2">
    <span class="opacity-50 font-bold text-theme-text text-xs"><?= _t('col_url') ?>:</span>
    <input type="text" x-model="block.data.url" :id="'block-' + block.id + '-url'" @blur="block.data.url = normalizeUrl(block.data.url)" class="flex-1 text-xs form-control-sm" placeholder="<?= h(_t('ph_card_url')) ?>">
    <button type="button" @click="fetchMeta(index)" class="px-3 py-1 text-xs whitespace-nowrap btn-secondary"><?= _t('btn_fetch_meta') ?></button>
  </div>
  <!-- Content editor -->
  <div class="flex items-start gap-4 pt-2 border-theme-border/50 border-t">
    <!-- Image preview -->
    <div class="w-24 shrink-0">
      <div class="group relative bg-theme-bg border border-theme-border rounded-theme aspect-square overflow-hidden cursor-pointer" @click="openMediaLibrary(index, null, 'image')">
        <img :src="resolvePreviewUrl(block.data.image)" x-show="block.data.image" class="w-full h-full object-cover" loading="lazy" @error="$el.src = <?= htmlspecialchars(json_encode(PLACEHOLDER_IMG), ENT_QUOTES) ?>">
        <div x-show="!block.data.image" class="flex justify-center items-center w-full h-full text-theme-text/20 text-xs"><?= _t('no_img') ?></div>
        <div class="absolute inset-0 flex justify-center items-center skin-modal-overlay opacity-0 group-hover:opacity-100 text-[9px] text-white transition"><?= _t('btn_change') ?></div>
      </div>
      <label class="block mt-1 text-center cursor-pointer" :class="{'opacity-50 cursor-not-allowed': isUploading}">
        <span class="text-[9px] text-theme-primary hover:underline" x-text="isUploading ? '...' : '<?= _t('upload') ?>'"></span>
        <input type="file" class="hidden" accept="image/*" @change="isUploading = true; await uploadImage($event, index, 'image'); isUploading = false" :disabled="isUploading">
      </label>
      <input type="text" x-model="block.data.image" :id="'block-' + block.id + '-image'" @blur="block.data.image = normalizeUrl(block.data.image)" class="mt-1 w-full text-[10px] form-control-sm" placeholder="<?= h(_t('ph_card_img')) ?>">
    </div>
    <!-- Title and description -->
    <div class="flex-1 space-y-2">
      <input type="text" x-model="block.data.title" :id="'block-' + block.id + '-title'" class="w-full font-bold text-sm form-control-sm" placeholder="<?= h(_t('ph_card_title')) ?>">
      <textarea x-model="block.data.description" :id="'block-' + block.id + '-description'" rows="2" class="opacity-80 w-full text-xs form-control-sm" placeholder="<?= h(_t('ph_card_desc')) ?>"></textarea>
    </div>
  </div>
</div>
