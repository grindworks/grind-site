<?php

/** Step Block View */
if (!defined('GRINDS_APP')) exit;
$step_styles = $block_config['library']['design']['items']['step']['styles'] ?? [];
?>
<div class="space-y-4"
  x-init="if(!block.data.items) block.data.items = [{id: Math.random().toString(36).substr(2, 9), title:<?= htmlspecialchars(json_encode(_t('def_step') . ' 1'), ENT_QUOTES) ?>, desc:''}]; block.data.items.forEach(i => { if(!i.id) i.id = Math.random().toString(36).substr(2, 9); })"
  x-data="{ styles: <?= htmlspecialchars(json_encode($step_styles), ENT_QUOTES) ?> }">
  <!-- Loop steps -->
  <template x-for="(item, i) in block.data.items" :key="item.id">
    <div class="flex gap-4">
      <!-- Step indicator -->
      <div class="flex flex-col items-center">
        <div class="flex justify-center items-center rounded-full w-6 h-6 font-bold text-xs shrink-0" :class="styles.dot.class" x-text="i+1"></div>
        <div class="flex-grow my-1 bg-theme-border w-0.5" x-show="i !== block.data.items.length - 1"></div>
      </div>
      <!-- Content card -->
      <div class="relative flex-grow space-y-2 bg-theme-bg/40 mb-4 p-4 pr-8 border border-theme-border rounded-theme">
        <!-- Delete step -->
        <button type="button" @click="block.data.items.splice(i, 1)" class="top-2 right-2 absolute hover:bg-theme-danger/10 p-1 rounded-theme text-theme-text/40 hover:text-theme-danger transition-colors" title="<?= h(_t('delete')) ?>">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-x-mark"></use>
          </svg>
        </button>
        <!-- Title input -->
        <div>
          <label class="block opacity-50 mb-1 font-bold text-[10px] text-theme-text"><?= _t('lbl_step_title') ?></label>
          <input type="text" x-model="item.title" class="w-full font-bold form-control-sm" placeholder="<?= _t('ph_step_title') ?>">
        </div>
        <!-- Description input -->
        <div>
          <label class="block opacity-50 mb-1 font-bold text-[10px] text-theme-text"><?= _t('lbl_step_desc') ?></label>
          <textarea x-model="item.desc" rows="3" class="w-full text-xs form-control-sm" placeholder="<?= _t('ph_step_desc') ?>"></textarea>
        </div>
      </div>
    </div>
  </template>
  <!-- Add step button -->
  <button type="button" @click="block.data.items.push({id: Math.random().toString(36).substr(2, 9), title:'', desc:''})" class="ml-10 px-3 py-2 border-2 hover:border-theme-primary/50 border-dashed w-auto text-xs btn-secondary">+ <?= _t('btn_add_step') ?></button>
</div>
