<?php

/** Callout Block View */
if (!defined('GRINDS_APP')) exit;
$callout_styles = $block_config['library']['design']['items']['callout']['styles'] ?? [];
?>
<div class="flex gap-3 bg-theme-bg/40 p-4 border border-theme-border rounded-theme" x-init="if(!block.data.style) block.data.style = 'info'" x-data="{ styles: <?= htmlspecialchars(json_encode($callout_styles), ENT_QUOTES) ?> }">
  <!-- Style selector -->
  <div class="pt-1 shrink-0">
    <select x-model="block.data.style" class="w-20 text-xs cursor-pointer form-control-sm">
      <?php foreach ($callout_styles as $key => $details): ?>
        <option value="<?= h($key) ?>"><?= h($details['label']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <!-- Content area -->
  <div class="flex-1 p-4 border-l-4 rounded-r-theme" :class="(styles[block.data.style] || {}).class">
    <!-- Message input -->
    <textarea x-model="block.data.text" :id="'block-' + block.id + '-text'" rows="2" class="bg-transparent opacity-90 p-1 border-none focus:ring-0 w-full text-inherit text-sm placeholder-current" placeholder="<?= _t('ph_callout') ?>"></textarea>
  </div>
</div>
