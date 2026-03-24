<?php

/** Section Block View */
if (!defined('GRINDS_APP')) exit; ?>
<?php
$section_colors = $block_config['library']['layout']['items']['section']['colors'] ?? [];
?>
<!-- Main container -->
<div class="p-4 border rounded-theme transition-colors"
  x-init="if(!block.data.bgColor) block.data.bgColor = 'gray'"
  x-data="{ colors: <?= htmlspecialchars(json_encode($section_colors), ENT_QUOTES) ?> }"
  :class="(colors[block.data.bgColor] || {}).class"
  :style="(colors[block.data.bgColor] || {}).style">
  <!-- Top Bar: Name & Color -->
  <div class="flex justify-between items-center mb-2 gap-2">
    <input type="text" x-model="block.data.name" class="w-full text-xs form-control-sm bg-transparent !border-dashed !border-gray-400 focus:!border-solid focus:!border-theme-primary placeholder-gray-400" placeholder="<?= function_exists('_t') ? _t('ph_section_name') : 'Section Name (for SEO / Link ID)' ?>">
    <select x-model="block.data.bgColor" class="w-auto text-xs cursor-pointer form-control-sm shrink-0">
      <?php foreach ($section_colors as $key => $details): ?>
        <option value="<?= h($key) ?>"><?= h($details['label']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <!-- Content input -->
  <textarea x-model="block.data.text" rows="3" class="w-full text-inherit text-base form-control-sm" placeholder="<?= _t('ph_enter_text') ?>"></textarea>
</div>
