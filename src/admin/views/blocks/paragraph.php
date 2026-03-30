<?php

/** Paragraph Block View */
if (!defined('GRINDS_APP')) exit; ?>
<div>
  <!-- Toolbar -->
  <div class="flex items-center gap-1 opacity-50 focus-within:opacity-100 mb-1 pb-1 border-theme-border border-b text-theme-text transition-opacity">
    <button type="button" @click="insertTag(index, 'b')" class="hover:bg-theme-bg p-1 rounded-theme font-bold text-xs" title="<?= h(_t('fmt_bold')) ?>">B</button>
    <button type="button" @click="insertTag(index, 'i')" class="hover:bg-theme-bg p-1 rounded-theme text-xs italic" title="<?= h(_t('fmt_italic')) ?>">I</button>
    <button type="button" @click="insertTag(index, 's')" class="hover:bg-theme-bg p-1 rounded-theme text-xs line-through" title="<?= h(_t('fmt_strike')) ?>">S</button>
    <button type="button" @click="insertLink(index)" class="hover:bg-theme-bg p-1 rounded-theme text-theme-primary text-xs" title="<?= h(_t('fmt_link')) ?>">
      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-link"></use>
      </svg>
    </button>
  </div>
  <!-- Text input -->
  <textarea :id="'block-' + block.id + '-text'" x-model="block.data.text" rows="1" x-init="$nextTick(() => { $el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px' })" @input="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'" class="bg-transparent px-3 py-2 border-none focus:ring-0 w-full text-theme-text text-base leading-relaxed overflow-hidden resize-none placeholder-theme-text/30" placeholder="<?= _t('ph_enter_text') ?>"></textarea>
</div>
