<?php

/**
 * Render icon list block view
 */
if (!defined('GRINDS_APP')) exit;
$il_icons = $block_config['library']['design']['items']['icon_list']['icons'] ?? [];
$il_colors = $block_config['library']['design']['items']['icon_list']['colors'] ?? [];
?>
<div class="bg-theme-bg/40 p-4 border border-theme-border rounded-theme"
  x-init="if(!block.data.items || block.data.items.length === 0) block.data.items = ['']; if(!block.data.icon) block.data.icon = 'check'; if(!block.data.color) block.data.color = 'green'"
  x-data="{ icons: <?= htmlspecialchars(json_encode($il_icons), ENT_QUOTES) ?>, colors: <?= htmlspecialchars(json_encode($il_colors), ENT_QUOTES) ?> }">

  <!-- Configure settings -->
  <div class="flex items-center gap-4 mb-4 pb-3 border-b border-theme-border/50">
    <div>
      <label class="block opacity-70 mb-1 font-bold text-theme-text text-[10px]"><?= _t('lbl_icon_type') ?></label>
      <select x-model="block.data.icon" class="form-control-sm cursor-pointer text-xs w-32">
        <?php foreach ($il_icons as $key => $details): ?>
          <option value="<?= h($key) ?>"><?= h($details['label']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block opacity-70 mb-1 font-bold text-theme-text text-[10px]"><?= _t('lbl_icon_color') ?></label>
      <select x-model="block.data.color" class="form-control-sm cursor-pointer text-xs w-28">
        <?php foreach ($il_colors as $key => $details): ?>
          <option value="<?= h($key) ?>"><?= h($details['label']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <!-- Render preview icon -->
    <div class="ml-auto flex items-center justify-center w-10 h-10 bg-theme-surface rounded-full shadow-theme border border-theme-border transition-colors" :class="(colors[block.data.color] || {}).class">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <use :href="'<?= grinds_asset_url('assets/img/sprite.svg') ?>#' + (icons[block.data.icon] ? icons[block.data.icon].svg : 'outline-check')"></use>
      </svg>
    </div>
  </div>

  <!-- Render items -->
  <div class="space-y-2 pl-1">
    <template x-for="(item, i) in block.data.items">
      <div class="group/item flex items-start gap-2">
        <div class="mt-2 shrink-0 transition-colors" :class="(colors[block.data.color] || {}).class">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use :href="'<?= grinds_asset_url('assets/img/sprite.svg') ?>#' + (icons[block.data.icon] ? icons[block.data.icon].svg : 'outline-check')"></use>
          </svg>
        </div>
        <!-- Handle text input -->
        <input type="text" x-model="block.data.items[i]" class="flex-1 bg-transparent py-1.5 border-theme-border focus:border-theme-primary border-b focus:outline-none text-theme-text text-sm placeholder-theme-text/30" placeholder="<?= _t('ph_enter_text') ?>" @keydown.enter.prevent="block.data.items.splice(i+1, 0, ''); $nextTick(() => { $el.closest('.space-y-2').querySelectorAll('input[type=text]')[i+1]?.focus() })">
        <!-- Remove item -->
        <button type="button" @click="block.data.items.splice(i, 1)" class="opacity-40 hover:opacity-100 mt-0.5 p-2 min-w-[40px] min-h-[40px] flex items-center justify-center text-xl text-theme-text hover:text-theme-danger transition-opacity" x-show="block.data.items.length > 1">&times;</button>
      </div>
    </template>

    <!-- Add new item -->
    <button type="button" @click="block.data.items.push(''); $nextTick(() => { $el.closest('.space-y-2').querySelectorAll('input[type=text]')[block.data.items.length-1]?.focus() })" class="flex items-center gap-1 mt-3 font-bold text-theme-primary text-xs hover:underline">
      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-plus"></use>
      </svg>
      <?= _t('btn_add_item') ?>
    </button>
  </div>
</div>
