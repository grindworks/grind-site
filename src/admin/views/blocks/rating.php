<?php

/** Rating Block View */
if (!defined('GRINDS_APP')) exit;

// Load color configuration
$rating_colors = $block_config['library']['marketing']['items']['rating']['colors'] ?? [];
?>
<div class="flex items-center gap-6 bg-theme-bg/40 p-4 border border-theme-border rounded-theme"
  x-init="if(block.data.score === undefined) block.data.score = 5; if(!block.data.color) block.data.color = 'gold'"
  x-data="{ colors: <?= htmlspecialchars(json_encode($rating_colors), ENT_QUOTES) ?>, previewScore: block.data.score || 5 }" x-effect="if(block.data.score !== undefined) previewScore = block.data.score">
  <!-- Score slider -->
  <div class="flex-1">
    <div class="flex justify-between mb-2">
      <label class="opacity-70 font-bold text-theme-text text-xs"><?= _t('lbl_rating_score') ?></label>
      <span class="font-bold text-theme-primary text-lg" x-text="previewScore"></span>
    </div>
    <!-- Range slider -->
    <input type="range" x-model="block.data.score" @input="previewScore = $event.target.value" min="0" max="5" step="0.5" class="bg-theme-border rounded-theme w-full h-2 accent-theme-primary appearance-none cursor-pointer">
    <!-- Scale -->
    <div class="flex justify-between opacity-40 mt-1 text-[10px] text-theme-text">
      <span>0</span>
      <span>1</span>
      <span>2</span>
      <span>3</span>
      <span>4</span>
      <span>5</span>
    </div>
  </div>
  <!-- Preview and color -->
  <div class="text-center shrink-0">
    <!-- Star preview -->
    <div class="text-2xl tracking-widest transition-colors"
      :class="(colors[block.data.color] || {}).class"
      :style="(colors[block.data.color] || {}).style">
      <template x-for="i in 5">
        <span x-text="i <= Math.round(previewScore) ? '★' : '☆'"></span>
      </template>
    </div>
    <!-- Color selector -->
    <select x-model="block.data.color" class="mx-auto mt-2 w-auto text-[10px] form-control-sm">
      <?php foreach ($rating_colors as $key => $details): ?>
        <option value="<?= h($key) ?>"><?= h($details['label']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
</div>
