<?php

/** Button Block View */
if (!defined('GRINDS_APP')) exit;

// Load color configuration
$btn_colors = $block_config['library']['design']['items']['button']['colors'] ?? [];
?>
<div class="flex items-start gap-3 bg-theme-bg/40 p-4 border border-theme-border rounded-theme"
  x-init="if(!block.data.color) block.data.color = 'primary'; if(block.data.external === undefined) block.data.external = true"
  x-data="{ colors: <?= htmlspecialchars(json_encode($btn_colors), ENT_QUOTES) ?> }">
  <!-- Text and URL inputs -->
  <div class="flex-1 space-y-2">
    <div class="flex gap-2">
      <input type="text" x-model="block.data.text" class="flex-1 font-bold form-control-sm" placeholder="<?= _t('ph_btn_text') ?>">
      <!-- Style preview -->
      <div class="flex justify-center items-center px-3 py-1 border rounded-theme font-bold text-xs transition-colors shrink-0"
        :class="(colors[block.data.color] || {}).class"
        :style="(colors[block.data.color] || {}).style">
        <?= _t('preview') ?>
      </div>
    </div>
    <input type="text" x-model="block.data.url" @blur="block.data.url = normalizeUrl(block.data.url)" class="w-full font-mono text-xs form-control-sm" placeholder="<?= _t('ph_btn_url') ?>">
    <p class="opacity-50 ml-1 text-[10px] text-theme-text"><?= _t('help_relative_path') ?></p>
  </div>
  <!-- Color and link options -->
  <div class="space-y-2 w-36 shrink-0">
    <select x-model="block.data.color" class="text-xs cursor-pointer form-control-sm">
      <?php foreach ($btn_colors as $key => $details): ?>
        <option value="<?= h($key) ?>"><?= h($details['label']) ?></option>
      <?php endforeach; ?>
    </select>
    <label class="flex items-center gap-2 text-theme-text text-xs cursor-pointer select-none">
      <input type="checkbox" x-model="block.data.external" class="bg-theme-bg border-theme-border rounded focus:ring-theme-primary w-5 h-5 text-theme-primary form-checkbox">
      <?= _t('lbl_new_tab') ?>
    </label>
  </div>
</div>
