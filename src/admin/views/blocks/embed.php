<?php

/** Embed Block View */
if (!defined('GRINDS_APP')) exit; ?>
<div class="space-y-3 bg-theme-bg/40 p-4 border border-theme-border rounded-theme" x-init="if(!block.data.align) block.data.align = 'center'">
  <div class="flex gap-2">
    <!-- Alignment selector -->
    <select x-model="block.data.align" class="w-auto text-xs cursor-pointer form-control-sm shrink-0">
      <option value="left"><?= _t('align_left') ?></option>
      <option value="center"><?= _t('align_center') ?></option>
      <option value="right"><?= _t('align_right') ?></option>
    </select>
    <!-- URL input -->
    <input type="text" x-model="block.data.url" class="flex-1 text-xs form-control-sm" placeholder="<?= _t('ph_embed_url') ?>">
  </div>
  <!-- Helper text -->
  <div class="opacity-50 ml-1 text-[10px] text-theme-text">
    <?= _t('help_embed_url') ?>
  </div>
</div>
