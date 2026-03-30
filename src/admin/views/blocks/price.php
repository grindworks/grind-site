<?php

/** Pricing Table Block View */
if (!defined('GRINDS_APP')) exit;
$price_styles = $block_config['library']['design']['items']['price']['styles'] ?? [];
?>
<div
  x-init="if(!block.data.items) block.data.items = [{id: generateId(), plan: <?= htmlspecialchars(json_encode(_t('def_plan_basic')), ENT_QUOTES) ?>, price: <?= htmlspecialchars(json_encode(_t('def_price_zero')), ENT_QUOTES) ?>, features:'', recommend:false}]; block.data.items.forEach(i => { if(!i.id) i.id = generateId(); })"
  x-data="{ styles: <?= htmlspecialchars(json_encode($price_styles), ENT_QUOTES) ?> }">
  <div class="gap-4 grid grid-cols-1 sm:grid-cols-2 mb-3">
    <!-- Loop plans -->
    <template x-for="(item, i) in block.data.items" :key="item.id">
      <div class="bg-theme-bg/40 p-4 pr-8 border rounded-theme transition-all" :class="item.recommend ? styles.recommend.class : styles.normal.class">
        <!-- Delete plan -->
        <button type="button" @click="block.data.items.splice(i, 1)" class="top-2 right-2 absolute hover:bg-theme-danger/10 p-1 rounded-theme text-theme-text/40 hover:text-theme-danger transition-colors" title="<?= h(_t('delete')) ?>">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-x-mark"></use>
          </svg>
        </button>
        <!-- Plan inputs -->
        <div class="space-y-3 pt-2">
          <div class="flex justify-between items-center mb-2">
            <span class="opacity-50 font-bold text-[10px] text-theme-text" x-text="<?= htmlspecialchars(json_encode(_t('lbl_plan_n')), ENT_QUOTES) ?> + ' ' + (i+1)"></span>
            <label class="flex items-center gap-1 pr-6 cursor-pointer">
              <input type="checkbox" x-model="item.recommend" class="bg-theme-bg border-theme-border rounded focus:ring-theme-primary w-5 h-5 text-theme-primary form-checkbox">
              <span class="font-bold text-[10px]" :class="item.recommend ? styles.recommend_text.class : 'text-theme-text opacity-50'"><?= _t('lbl_recommend') ?></span>
            </label>
          </div>
          <input type="text" x-model="item.plan" :id="'block-' + block.id + '-item-' + i + '-plan'" class="w-full font-bold text-center form-control-sm" placeholder="<?= h(_t('ph_plan_name')) ?>">
          <input type="text" x-model="item.price" :id="'block-' + block.id + '-item-' + i + '-price'" class="w-full font-black text-lg text-center form-control-sm" placeholder="<?= h(_t('ph_price')) ?>">
          <textarea x-model="item.features" :id="'block-' + block.id + '-item-' + i + '-features'" rows="6" class="w-full text-xs leading-normal form-control-sm" placeholder="<?= h(_t('ph_features')) ?>"></textarea>
        </div>
      </div>
    </template>
    <!-- Add plan button -->
    <button type="button" @click="block.data.items.push({id: generateId(), plan:'', price:'', features:'', recommend:false})" class="flex flex-col justify-center items-center opacity-50 p-3 border-2 border-theme-border hover:border-theme-primary/50 border-dashed rounded-theme min-h-[250px] text-theme-text hover:text-theme-primary transition-colors">
      <svg class="mb-1 w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-plus"></use>
      </svg>
      <span class="font-bold text-xs"><?= _t('btn_add_plan') ?></span>
    </button>
  </div>
</div>
