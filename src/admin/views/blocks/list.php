<?php

/** List Block View */
if (!defined('GRINDS_APP')) exit; ?>
<div x-init="if(!block.data.items) block.data.items = ['']; if(!block.data.style) block.data.style = 'unordered'">
  <!-- Style selector -->
  <div class="flex items-center gap-3 mb-3">
    <select x-model="block.data.style" class="text-xs cursor-pointer form-control-sm">
      <option value="unordered"><?= _t('list_unordered') ?></option>
      <option value="ordered"><?= _t('list_ordered') ?></option>
    </select>
  </div>
  <!-- Items container -->
  <div class="space-y-2 pl-2">
    <!-- Loop items -->
    <template x-for="(item, i) in block.data.items">
      <div class="group/item flex items-center gap-2">
        <span class="opacity-50 font-mono text-theme-text text-xs" x-text="block.data.style === 'ordered' ? (i+1)+'.' : '•'"></span>
        <input type="text" x-model="block.data.items[i]" class="flex-1 bg-transparent py-1 border-theme-border focus:border-theme-primary border-b focus:outline-none text-theme-text text-sm placeholder-theme-text/30" @keydown.enter.prevent="addListItem(index, i+1); $nextTick(() => { $el.closest('.space-y-2').querySelectorAll('input[type=text]')[i+1]?.focus() })">
        <button type="button" @click="removeListItem(index, i)" class="opacity-40 hover:opacity-100 p-2 min-w-[40px] min-h-[40px] flex items-center justify-center text-xl text-theme-text hover:text-theme-danger transition-opacity" x-show="block.data.items.length > 1">&times;</button>
      </div>
    </template>
    <!-- Add item button -->
    <button type="button" @click="addListItem(index); $nextTick(() => { $el.closest('.space-y-2').querySelectorAll('input[type=text]')[block.data.items.length-1]?.focus() })" class="flex items-center gap-1 mt-2 font-bold text-theme-primary text-xs hover:underline">
      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-plus"></use>
      </svg>
      <?= _t('add_item') ?>
    </button>
  </div>
</div>
