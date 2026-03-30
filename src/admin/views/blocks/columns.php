<?php

/** Columns Block View */
if (!defined('GRINDS_APP')) exit; ?>
<div class="space-y-3" x-init="if(!block.data.ratio) block.data.ratio = '1-1'">
  <!-- Ratio selector -->
  <div class="flex justify-end items-center mb-2">
    <label class="opacity-60 mr-2 text-[10px] text-theme-text"><?= _t('col_ratio') ?></label>
    <select x-model="block.data.ratio" class="w-auto text-xs cursor-pointer form-control-sm">
      <option value="1-1">1 : 1</option>
      <option value="1-2">1 : 2</option>
      <option value="2-1">2 : 1</option>
    </select>
  </div>
  <!-- Grid container -->
  <div class="gap-4 grid grid-cols-1" :class="{
        'md:grid-cols-2': block.data.ratio === '1-1',
        'md:grid-cols-[1fr_2fr]': block.data.ratio === '1-2',
        'md:grid-cols-[2fr_1fr]': block.data.ratio === '2-1'
    }">
    <div class="bg-theme-bg/40 p-4 border border-theme-border border-dashed rounded-theme">
      <div class="opacity-50 mb-1 font-bold text-[10px] text-theme-text"><?= _t('col_left') ?></div>
      <textarea x-model="block.data.leftText" :id="'block-' + block.id + '-leftText'" rows="4" class="bg-transparent p-1 border-none focus:ring-0 w-full text-theme-text text-sm placeholder-theme-text/30" placeholder="<?= _t('ph_enter_text') ?>"></textarea>
    </div>
    <div class="bg-theme-bg/40 p-4 border border-theme-border border-dashed rounded-theme">
      <div class="opacity-50 mb-1 font-bold text-[10px] text-theme-text"><?= _t('col_right') ?></div>
      <textarea x-model="block.data.rightText" :id="'block-' + block.id + '-rightText'" rows="4" class="bg-transparent p-1 border-none focus:ring-0 w-full text-theme-text text-sm placeholder-theme-text/30" placeholder="<?= _t('ph_enter_text') ?>"></textarea>
    </div>
  </div>
</div>
