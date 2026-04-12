<?php

/** Pros & Cons Block View */
if (!defined('GRINDS_APP')) exit;
$proscons_styles = $block_config['library']['marketing']['items']['proscons']['styles'] ?? [];
?>
<div class="gap-4 grid grid-cols-1 sm:grid-cols-2" x-init="if(!block.data.pros_items) block.data.pros_items = ['']; if(!block.data.cons_items) block.data.cons_items = [''];" x-data="{ styles: <?= htmlspecialchars(json_encode($proscons_styles), ENT_QUOTES) ?> }">
  <!-- Pros column -->
  <div class="p-4 border rounded-theme" :class="styles.pros.class">
    <div class="flex items-center gap-2 mb-2 pb-2 border-theme-success/30 border-b">
      <svg class="w-5 h-5 text-theme-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-face-smile"></use>
      </svg>
      <input type="text" x-model="block.data.pros_title" :id="'block-' + block.id + '-pros_title'" class="bg-transparent p-1 focus:outline-none w-full font-bold text-inherit text-sm placeholder-current/50" placeholder="<?= _t('lbl_pros_title') ?>">
    </div>
    <div class="space-y-2">
      <!-- Loop pros -->
      <template x-for="(item, i) in block.data.pros_items" :key="i">
        <div class="group/item flex items-center gap-2">
          <span class="text-theme-success">✔</span>
          <input type="text" x-model="block.data.pros_items[i]" :id="'block-' + block.id + '-pros_item-' + i" class="flex-1 border border-theme-success/30 focus:border-theme-success form-control-sm" placeholder="<?= _t('ph_pros_item') ?>"
            @keydown.enter.prevent="if(!$event.isComposing && !isComposing) { block.data.pros_items.splice(i+1, 0, ''); $nextTick(() => { $el.closest('.space-y-2').querySelectorAll('input[type=text]')[i+1]?.focus() }) }"
            @keydown.backspace="if(!$event.isComposing && !isComposing && block.data.pros_items[i] === '' && block.data.pros_items.length > 1) { $event.preventDefault(); block.data.pros_items.splice(i, 1); $nextTick(() => { $el.closest('.space-y-2').querySelectorAll('input[type=text]')[Math.max(0, i-1)]?.focus() }) }">
          <button type="button" @click="block.data.pros_items.splice(i, 1)" class="px-1 text-theme-success/50 hover:text-theme-success transition-colors" x-show="block.data.pros_items.length > 1">&times;</button>
        </div>
      </template>
      <!-- Add pro button -->
      <button type="button" @click="block.data.pros_items.push(''); $nextTick(() => { $el.closest('.space-y-2').querySelectorAll('input[type=text]')[block.data.pros_items.length-1]?.focus() })" class="flex items-center gap-1 mt-1 text-theme-success text-xs hover:underline">
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-plus"></use>
        </svg>
        <?= _t('btn_add_item') ?>
      </button>
    </div>
  </div>

  <!-- Cons column -->
  <div class="p-4 border rounded-theme" :class="styles.cons.class">
    <div class="flex items-center gap-2 mb-2 pb-2 border-theme-danger/30 border-b">
      <svg class="w-5 h-5 text-theme-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-face-frown"></use>
      </svg>
      <input type="text" x-model="block.data.cons_title" :id="'block-' + block.id + '-cons_title'" class="bg-transparent p-1 focus:outline-none w-full font-bold text-inherit text-sm placeholder-current/50" placeholder="<?= _t('lbl_cons_title') ?>">
    </div>
    <div class="space-y-2">
      <!-- Loop cons -->
      <template x-for="(item, i) in block.data.cons_items" :key="i">
        <div class="group/item flex items-center gap-2">
          <span class="text-theme-danger">✖</span>
          <input type="text" x-model="block.data.cons_items[i]" :id="'block-' + block.id + '-cons_item-' + i" class="flex-1 border border-theme-danger/30 focus:border-theme-danger form-control-sm" placeholder="<?= _t('ph_cons_item') ?>"
            @keydown.enter.prevent="if(!$event.isComposing && !isComposing) { block.data.cons_items.splice(i+1, 0, ''); $nextTick(() => { $el.closest('.space-y-2').querySelectorAll('input[type=text]')[i+1]?.focus() }) }"
            @keydown.backspace="if(!$event.isComposing && !isComposing && block.data.cons_items[i] === '' && block.data.cons_items.length > 1) { $event.preventDefault(); block.data.cons_items.splice(i, 1); $nextTick(() => { $el.closest('.space-y-2').querySelectorAll('input[type=text]')[Math.max(0, i-1)]?.focus() }) }">
          <button type="button" @click="block.data.cons_items.splice(i, 1)" class="px-1 text-theme-danger/50 hover:text-theme-danger transition-colors" x-show="block.data.cons_items.length > 1">&times;</button>
        </div>
      </template>
      <!-- Add con button -->
      <button type="button" @click="block.data.cons_items.push(''); $nextTick(() => { $el.closest('.space-y-2').querySelectorAll('input[type=text]')[block.data.cons_items.length-1]?.focus() })" class="flex items-center gap-1 mt-1 text-theme-danger text-xs hover:underline">
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-plus"></use>
        </svg>
        <?= _t('btn_add_item') ?>
      </button>
    </div>
  </div>
</div>
