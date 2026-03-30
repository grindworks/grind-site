<?php

/** Progress Bar Block View */
if (!defined('GRINDS_APP')) exit;
$pb_colors = $block_config['library']['design']['items']['progress_bar']['colors'] ?? [];
?>
<div class="space-y-4 bg-theme-bg/40 p-4 border border-theme-border rounded-theme"
  x-init="if(!block.data.items) block.data.items =[{id: generateId(), label:'Skill', percentage:80, color:'primary'}]; block.data.items.forEach(i => { if(!i.id) i.id = generateId(); if(!i.color) i.color = 'primary'; })"
  x-data="{ colors: <?= htmlspecialchars(json_encode($pb_colors), ENT_QUOTES) ?> }">

  <div class="space-y-3">
    <template x-for="(item, i) in block.data.items" :key="item.id">
      <div class="relative bg-theme-surface p-3 pr-8 border border-theme-border rounded-theme flex flex-col gap-2">
        <button type="button" @click="block.data.items.splice(i, 1)" class="top-2 right-2 absolute hover:bg-theme-danger/10 p-1 rounded-theme text-theme-text/40 hover:text-theme-danger transition-colors" title="<?= h(_t('delete')) ?>">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-x-mark"></use>
          </svg>
        </button>
        <div class="flex items-center gap-3">
          <input type="text" x-model="item.label" :id="'block-' + block.id + '-item-' + i + '-label'" class="w-1/2 font-bold form-control-sm" placeholder="<?= _t('ph_skill_name') ?>">
          <select x-model="item.color" class="w-1/4 text-xs cursor-pointer form-control-sm">
            <?php foreach ($pb_colors as $key => $details): ?>
              <option value="<?= h($key) ?>"><?= h($details['label']) ?></option>
            <?php endforeach; ?>
          </select>
          <div class="flex items-center gap-2 w-1/4">
            <input type="number" x-model="item.percentage" :id="'block-' + block.id + '-item-' + i + '-percentage'" min="0" max="100" class="w-full text-center form-control-sm">
            <span class="text-xs text-theme-text opacity-70">%</span>
          </div>
        </div>
        <div class="w-full bg-theme-bg rounded-full h-2 overflow-hidden mt-1 border border-theme-border/50">
          <div class="h-2 rounded-full transition-all" :class="(colors[item.color] || {}).class" :style="'width: ' + item.percentage + '%'"></div>
        </div>
      </div>
    </template>
  </div>
  <button type="button" @click="block.data.items.push({id: generateId(), label:'', percentage:50, color:'primary'}); $nextTick(() => { $el.closest('.space-y-4').querySelectorAll('input[type=text]')[block.data.items.length-1]?.focus() })" class="w-full py-2 border-2 hover:border-theme-primary/50 border-dashed text-xs btn-secondary">+ <?= _t('btn_add_item') ?></button>
</div>
