<?php

/** Timeline Block View */
if (!defined('GRINDS_APP'))
  exit;
$timeline_styles = $block_config['library']['design']['items']['timeline']['styles'] ?? [];
?>
<div class="space-y-4"
  x-init="if(!block.data.items) block.data.items = [{id: Math.random().toString(36).substr(2, 9), date:'', title:'', content:''}]; block.data.items.forEach(i => { if(!i.id) i.id = Math.random().toString(36).substr(2, 9); })"
  x-data="{ styles: <?= htmlspecialchars(json_encode($timeline_styles), ENT_QUOTES) ?> }">
  <!-- Loop items -->
  <template x-for="(item, i) in block.data.items" :key="item.id">
    <div class="flex gap-4">
      <!-- Line and dot -->
      <div class="flex flex-col items-center">
        <div class="mt-2 rounded-full w-3 h-3 shrink-0" :class="styles.dot.class"></div>
        <div class="flex-grow my-1 bg-theme-border w-0.5" x-show="i !== block.data.items.length - 1"></div>
      </div>
      <!-- Content card -->
      <div class="relative flex-grow space-y-2 bg-theme-bg/40 mb-4 p-4 pr-8 border border-theme-border rounded-theme">
        <!-- Delete item -->
        <button type="button" @click="block.data.items.splice(i, 1)"
          class="top-2 right-2 absolute hover:bg-theme-danger/10 p-1 rounded-theme text-theme-text/40 hover:text-theme-danger transition-colors"
          title="<?= h(_t('delete')) ?>">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-x-mark"></use>
          </svg>
        </button>

        <!-- Date and title -->
        <div class="flex sm:flex-row flex-col gap-2">
          <div class="w-full sm:w-1/3">
            <label class="block opacity-50 mb-1 font-bold text-[10px] text-theme-text">
              <?= _t('lbl_date') ?>
            </label>
            <input type="text" x-model="item.date" class="w-full font-bold form-control-sm"
              placeholder="<?= _t('ph_year_example') ?>">
          </div>
          <div class="w-full sm:w-2/3">
            <label class="block opacity-50 mb-1 font-bold text-[10px] text-theme-text">
              <?= _t('lbl_event') ?>
            </label>
            <input type="text" x-model="item.title" class="w-full font-bold form-control-sm"
              placeholder="<?= _t('ph_event_title') ?>">
          </div>
        </div>
        <!-- Details input -->
        <div>
          <textarea x-model="item.content" rows="2" class="w-full text-xs form-control-sm"
            placeholder="<?= _t('ph_details') ?>"></textarea>
        </div>
      </div>
    </div>
  </template>

  <!-- Add event button -->
  <button type="button"
    @click="block.data.items.push({id: Math.random().toString(36).substr(2, 9), date:'', title:'', content:''})"
    class="ml-7 px-3 py-2 border-2 hover:border-theme-primary/50 border-dashed w-auto text-xs btn-secondary">+
    <?= _t('btn_add_event') ?>
  </button>
</div>
