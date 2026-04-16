<?php

/** Conversation Block View */
if (!defined('GRINDS_APP')) exit;
$conv_styles = $block_config['library']['layout']['items']['conversation']['styles'] ?? [];
?>
<div class="bg-theme-bg/40 p-4 border border-theme-border rounded-theme"
  x-init="if(!block.data.position) block.data.position = 'left'"
  x-data="{ styles: <?= htmlspecialchars(json_encode($conv_styles), ENT_QUOTES) ?>, isUploading: false }">
  <!-- Alignment and name -->
  <div class="flex items-center gap-4 mb-3">
    <div class="flex bg-theme-bg p-1 border border-theme-border rounded-theme">
      <button type="button" @click="block.data.position = 'left'" :class="{'bg-theme-primary text-theme-on-primary': block.data.position === 'left', 'text-theme-text hover:bg-theme-surface': block.data.position !== 'left'}" class="px-3 py-1 rounded-theme text-xs transition-colors"><?= _t('align_left') ?></button>
      <button type="button" @click="block.data.position = 'right'" :class="{'bg-theme-primary text-theme-on-primary': block.data.position === 'right', 'text-theme-text hover:bg-theme-surface': block.data.position !== 'right'}" class="px-3 py-1 rounded-theme text-xs transition-colors"><?= _t('align_right') ?></button>
    </div>
    <input type="text" x-model="block.data.name" :id="'block-' + block.id + '-name'" class="flex-1 font-bold text-xs form-control-sm" placeholder="<?= _t('ph_name') ?>">
  </div>

  <!-- Content area -->
  <div class="flex items-start gap-4" :class="{'flex-row-reverse': block.data.position === 'right'}">
    <!-- Avatar uploader -->
    <div class="text-center shrink-0">
      <div class="relative inline-block group">
        <div class="relative bg-theme-surface border border-theme-border rounded-full w-12 h-12 overflow-hidden cursor-pointer" @click="openMediaLibrary(block.id, null, 'image')">
          <img :src="resolvePreviewUrl(block.data.image)" x-show="block.data.image" class="w-full h-full object-cover">
          <div x-show="!block.data.image" class="flex justify-center items-center w-full h-full text-theme-text/20">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-user-circle"></use>
            </svg>
          </div>
          <div class="absolute inset-0 flex justify-center items-center skin-modal-overlay opacity-0 group-hover:opacity-100 text-[9px] text-white transition"><?= _t('btn_change') ?></div>
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

    <!-- Message content -->
    <div class="relative flex-1">
      <textarea x-model="block.data.text" :id="'block-' + block.id + '-text'" rows="3"
        x-init="$nextTick(() => { $el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px' })"
        @input="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'"
        class="w-full text-sm leading-relaxed form-control-sm overflow-hidden resize-none" :class="(styles[block.data.position] || {}).class"
        placeholder="<?= _t('ph_enter_text') ?>"></textarea>
    </div>
  </div>
</div>
