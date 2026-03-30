<?php

/** Header Block View */
if (!defined('GRINDS_APP')) exit; ?>
<div class="flex items-center gap-3" x-init="if(!block.data.level) block.data.level = 'h2'">
  <!-- Level selector -->
  <div class="shrink-0">
    <select x-model="block.data.level" class="font-bold text-xs cursor-pointer form-control-sm">
      <template x-for="lvl in headingLevels" :key="lvl.value">
        <option :value="lvl.value" x-text="lvl.label"></option>
      </template>
    </select>
  </div>
  <!-- Header text -->
  <input type="text" x-model="block.data.text" :id="'block-' + block.id + '-text'" @keydown.enter.prevent class="bg-transparent px-2 py-1 border-none focus:ring-0 w-full font-bold text-theme-text text-2xl leading-tight transition-colors placeholder-theme-text/30" placeholder="<?= _t('ph_enter_header') ?>">
</div>
